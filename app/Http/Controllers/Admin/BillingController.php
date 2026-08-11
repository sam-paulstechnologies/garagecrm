<?php

namespace App\Http\Controllers\Admin;

use App\Billing\BillingManager;
use App\Billing\Exceptions\BillingConfigurationException;
use App\Commercial\LaunchOfferService;
use App\Http\Controllers\Controller;
use App\Models\Commercial\BillingInvoice;
use App\Models\Commercial\Price;
use App\Models\System\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request, LaunchOfferService $launchOffer): View
    {
        $company = $this->company($request);
        $subscription = $company->subscription()->with(['planVersion.plan', 'price'])->firstOrFail();
        $prices = Price::query()
            ->where('status', 'active')
            ->with('planVersion.plan')
            ->orderBy('list_amount')
            ->get();
        $invoices = BillingInvoice::query()
            ->where('company_id', $company->id)
            ->with('price.planVersion.plan')
            ->latest()
            ->limit(24)
            ->get();

        $launchOfferEnabled = $launchOffer->isEnabled();
        $promotionCycles = $launchOffer->durationCycles();
        $checkoutPhases = $prices->mapWithKeys(
            fn (Price $price): array => [$price->id => $launchOffer->phaseForCheckout($subscription, $price)]
        );

        return view('admin.billing.index', compact(
            'company', 'subscription', 'prices', 'invoices', 'launchOfferEnabled', 'promotionCycles', 'checkoutPhases'
        ));
    }

    public function checkout(Request $request, BillingManager $billing): RedirectResponse
    {
        $validated = $request->validate([
            'price_code' => ['required', 'string', 'max:120'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:64', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ]);
        $company = $this->company($request);
        $price = Price::query()->where('code', $validated['price_code'])->firstOrFail();
        try {
            $checkout = $billing->startCheckout($company, $price, $validated['idempotency_key']);
        } catch (BillingConfigurationException $exception) {
            return back()->with('warning', $exception->getMessage());
        }
        if (blank($checkout->checkout_url)) {
            return back()->with('warning', 'The plan change was requested. Access changes only after the verified provider webhook arrives.');
        }

        return redirect()->away((string) $checkout->checkout_url);
    }

    public function success(Request $request, int $checkout): RedirectResponse
    {
        $company = $this->company($request);
        $session = $company->subscription()->firstOrFail()->checkoutSessions()->whereKey($checkout)->firstOrFail();

        return redirect()->route('admin.billing.index')->with(
            $session->status === 'completed' ? 'success' : 'warning',
            $session->status === 'completed'
                ? 'Your verified subscription is active.'
                : 'Checkout returned successfully. Access changes only after a verified provider webhook.',
        );
    }

    public function cancel(Request $request, BillingManager $billing): RedirectResponse
    {
        try {
            $billing->requestCancellation($this->company($request));
        } catch (BillingConfigurationException $exception) {
            return back()->with('warning', $exception->getMessage());
        }

        return back()->with('success', 'Cancellation was requested. Your access remains unchanged until verified by the provider.');
    }

    public function portal(Request $request, BillingManager $billing): RedirectResponse
    {
        try {
            return redirect()->away($billing->portal($this->company($request)));
        } catch (BillingConfigurationException $exception) {
            return back()->with('warning', $exception->getMessage());
        }
    }

    private function company(Request $request): Company
    {
        $company = $request->user()?->company;
        abort_unless($company, 403);

        return $company;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Billing\BillingWebhookProcessor;
use App\Billing\Gateways\FakeBillingGateway;
use App\Http\Controllers\Controller;
use App\Models\Commercial\BillingCheckoutSession;
use App\Models\Commercial\PriceProviderMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FakeBillingCheckoutController extends Controller
{
    public function show(Request $request, BillingCheckoutSession $billingCheckoutSession): View
    {
        $this->authorizeSession($request, $billingCheckoutSession);
        $this->assertAvailable();

        return view('admin.billing.fake-checkout', ['checkout' => $billingCheckoutSession->load('requestedPrice.planVersion.plan')]);
    }

    public function complete(Request $request, BillingCheckoutSession $billingCheckoutSession, FakeBillingGateway $gateway, BillingWebhookProcessor $processor): RedirectResponse
    {
        $this->authorizeSession($request, $billingCheckoutSession);
        $this->assertAvailable();
        $mapping = PriceProviderMapping::query()
            ->where('price_id', $billingCheckoutSession->requested_price_id)
            ->where('payment_provider', 'fake')
            ->where('price_phase', 'launch')
            ->firstOrFail();
        $eventId = 'evt_fake_checkout_'.$billingCheckoutSession->id;
        $isChange = $billingCheckoutSession->operation === 'plan_change';
        $subscription = $billingCheckoutSession->subscription;
        [$payload, $signature] = $gateway->signedEvent(
            $isChange ? 'customer.subscription.updated' : 'checkout.session.completed',
            $isChange ? 'subscription' : 'checkout.session',
            $isChange ? (string) $subscription->provider_subscription_id : (string) $billingCheckoutSession->provider_checkout_id,
            [
                'local_checkout_id' => $billingCheckoutSession->id,
                'provider_customer_id' => $billingCheckoutSession->provider_customer_id,
                'provider_subscription_id' => $isChange ? $subscription->provider_subscription_id : 'sub_fake_'.$billingCheckoutSession->id,
                'provider_price_id' => $mapping->provider_price_id,
                'subscription_status' => 'active',
                'payment_status' => 'paid',
                'period_start' => now()->timestamp,
                'period_end' => now()->addMonth()->timestamp,
            ],
            $eventId,
        );
        $processor->process('fake', $payload, $signature);

        return redirect()->route('admin.billing.success', ['checkout' => $billingCheckoutSession->id]);
    }

    private function authorizeSession(Request $request, BillingCheckoutSession $checkout): void
    {
        abort_unless((int) $request->user()?->company_id === (int) $checkout->company_id, 404);
    }

    private function assertAvailable(): void
    {
        abort_unless(app()->environment(['local', 'testing', 'staging']) && config('billing.provider') === 'fake', 404);
    }
}

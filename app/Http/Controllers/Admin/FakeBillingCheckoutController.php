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
            ->where('price_phase', $billingCheckoutSession->price_phase)
            ->firstOrFail();
        $eventId = 'evt_fake_test_checkout_'.$billingCheckoutSession->id;
        $isChange = $billingCheckoutSession->operation === 'plan_change';
        $subscription = $billingCheckoutSession->subscription;
        $providerSubscriptionId = $isChange
            ? (string) $subscription->provider_subscription_id
            : 'sub_fake_test_'.$billingCheckoutSession->id;
        $periodStart = $billingCheckoutSession->created_at->copy()->startOfSecond();
        $periodEnd = $periodStart->copy()->addMonth();
        [$payload, $signature] = $gateway->signedEvent(
            $isChange ? 'customer.subscription.updated' : 'checkout.session.completed',
            $isChange ? 'subscription' : 'checkout.session',
            $isChange ? (string) $subscription->provider_subscription_id : (string) $billingCheckoutSession->provider_checkout_id,
            [
                'local_checkout_id' => $billingCheckoutSession->id,
                'provider_customer_id' => $billingCheckoutSession->provider_customer_id,
                'provider_subscription_id' => $providerSubscriptionId,
                'provider_price_id' => $mapping->provider_price_id,
                'subscription_status' => 'active',
                'payment_status' => 'paid',
                'period_start' => $periodStart->timestamp,
                'period_end' => $periodEnd->timestamp,
            ],
            $eventId,
            $periodStart->timestamp,
        );
        $processor->process('fake', $payload, $signature);

        $price = $billingCheckoutSession->requestedPrice;
        $invoiceId = 'in_fake_test_'.$billingCheckoutSession->id;
        $amount = $billingCheckoutSession->price_phase === 'launch'
            ? $price->promotional_amount
            : $price->list_amount;
        $amountMinor = (int) round(((float) $amount) * 100);
        [$invoicePayload, $invoiceSignature] = $gateway->signedEvent(
            'invoice.paid',
            'invoice',
            $invoiceId,
            [
                'local_checkout_id' => $billingCheckoutSession->id,
                'provider_customer_id' => $billingCheckoutSession->provider_customer_id,
                'provider_subscription_id' => $providerSubscriptionId,
                'provider_price_id' => $mapping->provider_price_id,
                'provider_invoice_id' => $invoiceId,
                'currency' => strtolower((string) $price->currency),
                'amount_due_minor' => $amountMinor,
                'amount_paid_minor' => $amountMinor,
                'billing_reason' => $isChange ? 'subscription_cycle' : 'subscription_create',
                'period_start' => $periodStart->timestamp,
                'period_end' => $periodEnd->timestamp,
                'paid_at' => $periodStart->copy()->addSecond()->timestamp,
            ],
            'evt_fake_test_invoice_paid_'.$billingCheckoutSession->id,
            $periodStart->copy()->addSecond()->timestamp,
        );
        $processor->process('fake', $invoicePayload, $invoiceSignature);

        return redirect()->route('admin.billing.success', ['checkout' => $billingCheckoutSession->id]);
    }

    public function cancelCheckout(Request $request, BillingCheckoutSession $billingCheckoutSession): RedirectResponse
    {
        $this->authorizeSession($request, $billingCheckoutSession);
        $this->assertAvailable();
        if ($billingCheckoutSession->status !== 'completed') {
            $billingCheckoutSession->update(['status' => 'cancelled']);
        }

        return redirect()->route('admin.billing.index')
            ->with('warning', 'Sandbox checkout cancelled. No subscription or entitlement change was made.');
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

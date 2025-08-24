<?php

namespace App\Services\Payments\Drivers;

use App\Services\Payments\PaymentGateway;
use Illuminate\Http\Request;

class APSDriver implements PaymentGateway
{
    public function createCheckout(array $payload): string
    {
        // Stub: integrate APS signature + hosted payment form fields
        // Return a placeholder URL for now
        return url('/payments/mock?amount=' . ($payload['amount'] ?? '0'));
    }

    public function handleReturn(Request $request): array
    {
        return [
            'status' => $request->input('status', 'paid'),
            'reference' => $request->input('ref', 'demo-ref'),
        ];
    }
}

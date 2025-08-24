<?php

namespace App\Services\Payments;

use Illuminate\Http\Request;

interface PaymentGateway
{
    /** Create a hosted payment session and return redirect URL */
    public function createCheckout(array $payload): string;

    /** Handle gateway return/callback and return normalized result */
    public function handleReturn(Request $request): array;
}

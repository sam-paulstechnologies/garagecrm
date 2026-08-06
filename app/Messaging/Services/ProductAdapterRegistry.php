<?php

namespace App\Messaging\Services;

use App\Messaging\Contracts\ProductMessagingAdapter;
use App\Messaging\Exceptions\MessagingProvisioningException;

class ProductAdapterRegistry
{
    /** @var array<string, class-string<ProductMessagingAdapter>> */
    private array $adapters = [];

    /** @param class-string<ProductMessagingAdapter> $adapter */
    public function register(string $productKey, string $adapter): void
    {
        $this->adapters[$productKey] = $adapter;
    }

    public function for(string $productKey): ProductMessagingAdapter
    {
        $adapter = $this->adapters[$productKey] ?? null;
        if (! $adapter) {
            throw new MessagingProvisioningException('product_adapter_missing', 'This messaging product is not available.');
        }

        return app($adapter);
    }
}

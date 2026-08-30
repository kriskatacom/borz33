<?php

declare(strict_types=1);

return [
    'office_price' => max(0, (float) (getenv('ECONT_OFFICE_PRICE') ?: 3.95)),
    'address_price' => max(0, (float) (getenv('ECONT_ADDRESS_PRICE') ?: 5.22)),
    'currency' => 'EUR',
];

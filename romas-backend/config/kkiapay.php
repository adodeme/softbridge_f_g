<?php

return [
    'public_key'     => env('KKIAPAY_PUBLIC_KEY'),
    'private_key'    => env('KKIAPAY_PRIVATE_KEY'),
    'secret_key'     => env('KKIAPAY_SECRET_KEY'),
    'webhook_secret' => env('KKIAPAY_WEBHOOK_SECRET'),
    'sandbox'        => env('KKIAPAY_SANDBOX', true),
];
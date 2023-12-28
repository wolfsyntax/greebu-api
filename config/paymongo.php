<?php

use Illuminate\Support\Str;

return [
    'signature'     => env('PAYMONGO_WEBHOOK_SIG'),
    'secret_key'    => env('PAYMONGO_SECRET_KEY'),
    'public_key'    => env('PAYMONGO_PUBLIC_KEY'),
    'auth'          => base64_encode(env('PAYMONGO_SECRET_KEY')),
];

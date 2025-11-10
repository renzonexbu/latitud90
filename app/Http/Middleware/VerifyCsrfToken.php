<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/payment/notification/*',
        'payment/callback/*',
        'test/subscriptions/callback', // VirtualPos webhook para suscripciones
        'test/subscriptions/return', // VirtualPos return URL (puede ser POST)
    ];
}

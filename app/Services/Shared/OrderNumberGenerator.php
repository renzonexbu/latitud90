<?php

namespace App\Services\Shared;

use App\Models\Order;
use Carbon\CarbonImmutable;

class OrderNumberGenerator
{
    /**
     * Genera un identificador alfanumérico de 8 caracteres en mayúsculas.
     * Formato: LT + yymm + XX (dos alfanum aleatorios) => ejemplo: LT2508A9
     * - Siempre único en tabla orders.order_number
     * - Usa zona horaria America/Santiago
     */
    public function generate(): string
    {
        $nowCl = CarbonImmutable::now('America/Santiago');
        $yy = $nowCl->format('y');
        $mm = $nowCl->format('m');

        do {
            $suffix = $this->randomAlphaNum(2);
            $code = sprintf('LT%s%s%s', $yy, $mm, $suffix);
        } while (Order::where('order_number', $code)->exists());

        return $code;
    }

    private function randomAlphaNum(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $out;
    }
}



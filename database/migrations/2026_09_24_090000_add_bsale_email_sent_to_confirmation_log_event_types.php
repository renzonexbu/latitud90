<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * event_type es un ENUM con lista cerrada y no contemplaba el envío del correo
 * que lleva la boleta BSale. El registro agregado el 2026-09-23 fallaba al
 * insertar y el try/catch del modelo se lo tragaba, así que el evento nunca
 * aparecía en el historial de confirmación (Liliam 2026-09-24).
 */
return new class extends Migration
{
    private const VALUES = [
        'payment_receipt_generated',
        'contract_generated',
        'bsale_invoice_generated',
        'email_sent',
        'email_failed',
        'email_resent',
        'bsale_email_sent',
    ];

    public function up(): void
    {
        $this->setEnum(self::VALUES);
    }

    public function down(): void
    {
        // Solo se puede volver atrás si no quedaron filas con el valor nuevo
        $enUso = DB::table('payment_confirmation_logs')
            ->where('event_type', 'bsale_email_sent')
            ->exists();

        if ($enUso) {
            return;
        }

        $this->setEnum(array_values(array_diff(self::VALUES, ['bsale_email_sent'])));
    }

    private function setEnum(array $values): void
    {
        $list = implode(',', array_map(fn ($v) => "'" . $v . "'", $values));

        DB::statement("ALTER TABLE `payment_confirmation_logs` MODIFY COLUMN `event_type` ENUM({$list}) NOT NULL");
    }
};

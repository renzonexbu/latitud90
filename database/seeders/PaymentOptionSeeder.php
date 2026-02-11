<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentOptionSeeder extends Seeder
{
	public function run(): void
	{
		// Limpieza previa: eliminar opciones obsoletas lat90_installments_* y sus pivotes
		$obsoleteIds = DB::table('payment_options')
			->where('code', 'like', 'lat90_installments_%')
			->pluck('id')
			->all();
		if (!empty($obsoleteIds)) {
			DB::table('program_payment_option')->whereIn('payment_option_id', $obsoleteIds)->delete();
			DB::table('payment_options')->whereIn('id', $obsoleteIds)->delete();
		}

		$options = [
			// Pago total - Transferencia Khipu
			['code' => 'full_transfer_khipu', 'label' => 'Transferencia (Khipu)', 'mode' => 'full', 'gateway_code' => 'khipu', 'report_code' => 'KP', 'installments' => null, 'active' => true],

			// Pago total - Débito y crédito sin cuotas
			['code' => 'full_debit_credit_0', 'label' => 'Débito y crédito sin cuotas (Webpay)', 'mode' => 'full', 'gateway_code' => 'transbank', 'report_code' => 'VP', 'installments' => 0, 'active' => true],

			// Pago total - Débito y crédito con cuotas
			['code' => 'full_debit_credit_3', 'label' => 'Débito y crédito hasta 3 cuotas sin interés (Webpay)', 'mode' => 'full', 'gateway_code' => 'transbank', 'report_code' => 'VP', 'installments' => 3, 'active' => true],
			['code' => 'full_debit_credit_6', 'label' => 'Débito y crédito hasta 6 cuotas sin interés (Webpay)', 'mode' => 'full', 'gateway_code' => 'transbank', 'report_code' => 'VP', 'installments' => 6, 'active' => true],
			['code' => 'full_debit_credit_9', 'label' => 'Débito y crédito hasta 9 cuotas sin interés (Webpay)', 'mode' => 'full', 'gateway_code' => 'transbank', 'report_code' => 'VP', 'installments' => 9, 'active' => true],
			['code' => 'full_debit_credit_12', 'label' => 'Débito y crédito hasta 12 cuotas sin interés (Webpay)', 'mode' => 'full', 'gateway_code' => 'transbank', 'report_code' => 'VP', 'installments' => 12, 'active' => true],

			// Pago total - Internacional
			['code' => 'full_international', 'label' => 'Pago Internacional (Webpay)', 'mode' => 'full', 'gateway_code' => 'transbank', 'report_code' => 'VPI', 'installments' => null, 'active' => true],

			// Pago total - Webpay con link de pago
			['code' => 'full_webpay_link', 'label' => 'Pago webpay con link de pago', 'mode' => 'full', 'gateway_code' => 'transbank', 'report_code' => 'WP', 'installments' => null, 'active' => true],

			// Pagos presenciales - Códigos actualizados según sistema de reporte
			['code' => 'presential_pos_office', 'label' => 'POS Oficina (TC)', 'mode' => 'presential', 'gateway_code' => 'presencial', 'report_code' => 'TC', 'installments' => null, 'active' => true],
			['code' => 'presential_khipu_link', 'label' => 'Link KP (KP)', 'mode' => 'presential', 'gateway_code' => 'presencial', 'report_code' => 'KP', 'installments' => null, 'active' => true],
			['code' => 'presential_subscription', 'label' => 'Suscripción Cuotas (PAT)', 'mode' => 'presential', 'gateway_code' => 'presencial', 'report_code' => 'PAT', 'installments' => null, 'active' => true],
			['code' => 'presential_bank_transfer', 'label' => 'Transferencia Banco (TE)', 'mode' => 'presential', 'gateway_code' => 'presencial', 'report_code' => 'TE', 'installments' => null, 'active' => true],
			['code' => 'presential_debit_credit', 'label' => 'Link TD/TC (VP)', 'mode' => 'presential', 'gateway_code' => 'presencial', 'report_code' => 'VP', 'installments' => null, 'active' => true],
			['code' => 'presential_international', 'label' => 'Link Internacional (VPI)', 'mode' => 'presential', 'gateway_code' => 'presencial', 'report_code' => 'VPI', 'installments' => null, 'active' => true],
			['code' => 'presential_deposit', 'label' => 'Depósito (DP)', 'mode' => 'presential', 'gateway_code' => 'presencial', 'report_code' => 'DP', 'installments' => null, 'active' => true],
			['code' => 'presential_webpay', 'label' => 'Webpay (WP)', 'mode' => 'presential', 'gateway_code' => 'presencial', 'report_code' => 'WP', 'installments' => null, 'active' => true],
			['code' => 'presential_aporte', 'label' => 'Aporte (AP)', 'mode' => 'presential', 'gateway_code' => 'presencial', 'report_code' => 'AP', 'installments' => null, 'active' => true],
			['code' => 'presential_credit_temp', 'label' => 'Crédito Temporal (CT)', 'mode' => 'presential', 'gateway_code' => 'presencial', 'report_code' => 'CT', 'installments' => null, 'active' => true],

			// Pago mensual (Lat90) - LEGACY - Transferencia Khipu
			['code' => 'lat90_transfer_khipu', 'label' => 'Transferencia (Khipu)', 'mode' => 'lat90', 'gateway_code' => 'khipu', 'report_code' => 'KP', 'installments' => null, 'active' => true],

			// Pago mensual (Lat90) - LEGACY - Débito y crédito sin cuotas (método; las cuotas N se manejan aparte)
			['code' => 'lat90_debit_credit_0', 'label' => 'Débito y crédito sin cuotas (Webpay)', 'mode' => 'lat90', 'gateway_code' => 'transbank', 'report_code' => 'VP', 'installments' => 0, 'active' => true],

			// Suscripciones (VirtualPos) - Pagos recurrentes mensuales
			['code' => 'subscription_virtualpos', 'label' => 'Suscripción mensual (VirtualPos)', 'mode' => 'subscription', 'gateway_code' => 'virtualpos', 'report_code' => 'PAT', 'installments' => null, 'active' => true],

			// Devoluciones y Reversos
			['code' => 'refund_credit_note', 'label' => 'Notas de crédito (devoluciones)', 'mode' => 'refund', 'gateway_code' => 'refund', 'report_code' => 'NC', 'installments' => null, 'active' => true],
			['code' => 'refund_aporte_credit_note', 'label' => 'Nota de crédito a Aporte', 'mode' => 'refund', 'gateway_code' => 'refund', 'report_code' => 'AP', 'installments' => null, 'active' => true],
			['code' => 'refund_admin_reversal', 'label' => 'Reverso Administrativo (RA)', 'mode' => 'refund', 'gateway_code' => 'refund', 'report_code' => 'RA', 'installments' => null, 'active' => true],
		];

		foreach ($options as $opt) {
			DB::table('payment_options')->updateOrInsert(
				['code' => $opt['code']],
				array_merge($opt, ['updated_at' => now(), 'created_at' => now()])
			);
		}
	}
}

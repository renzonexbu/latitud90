<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentOptionSeeder extends Seeder
{
	public function run(): void
	{
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
			
			// Pagos presenciales
			['code' => 'full_office_card', 'label' => 'Pago con tarjeta en oficina', 'mode' => 'full', 'gateway_code' => 'presencial', 'report_code' => 'BX', 'installments' => null, 'active' => true],
			['code' => 'full_bank_transfer', 'label' => 'Transferencia bancaria', 'mode' => 'full', 'gateway_code' => 'presencial', 'report_code' => 'TE', 'installments' => null, 'active' => true],
			['code' => 'full_check', 'label' => 'Cheque', 'mode' => 'full', 'gateway_code' => 'presencial', 'report_code' => 'CH', 'installments' => null, 'active' => true],
			['code' => 'full_deposit', 'label' => 'Depósito', 'mode' => 'full', 'gateway_code' => 'presencial', 'report_code' => 'DP', 'installments' => null, 'active' => true],
			
			// Pago mensual (Lat90) - Transferencia Khipu
			['code' => 'lat90_transfer_khipu', 'label' => 'Transferencia (Khipu)', 'mode' => 'lat90', 'gateway_code' => 'khipu', 'report_code' => 'KP', 'installments' => null, 'active' => true],
			
			// Pago mensual (Lat90) - Débito y crédito sin cuotas
			['code' => 'lat90_debit_credit_0', 'label' => 'Débito y crédito sin cuotas (Webpay)', 'mode' => 'lat90', 'gateway_code' => 'transbank', 'report_code' => 'VP', 'installments' => 0, 'active' => true],
			
			// Pago mensual (Lat90) - Cuotas Lat90
			['code' => 'lat90_installments_3', 'label' => 'Lat90 3 cuotas', 'mode' => 'lat90', 'gateway_code' => null, 'report_code' => null, 'installments' => 3, 'active' => true],
			['code' => 'lat90_installments_6', 'label' => 'Lat90 6 cuotas', 'mode' => 'lat90', 'gateway_code' => null, 'report_code' => null, 'installments' => 6, 'active' => true],
			['code' => 'lat90_installments_9', 'label' => 'Lat90 9 cuotas', 'mode' => 'lat90', 'gateway_code' => null, 'report_code' => null, 'installments' => 9, 'active' => true],
			['code' => 'lat90_installments_12', 'label' => 'Lat90 12 cuotas', 'mode' => 'lat90', 'gateway_code' => null, 'report_code' => null, 'installments' => 12, 'active' => true],
			
			// Devoluciones
			['code' => 'refund_credit_note', 'label' => 'Notas de crédito (devoluciones)', 'mode' => 'full', 'gateway_code' => 'refund', 'report_code' => 'NC', 'installments' => null, 'active' => true],
		];

		foreach ($options as $opt) {
			DB::table('payment_options')->updateOrInsert(
				['code' => $opt['code']],
				array_merge($opt, ['updated_at' => now(), 'created_at' => now()])
			);
		}
	}
}



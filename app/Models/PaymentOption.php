<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentOption extends Model
{
	use HasFactory;

	protected $table = 'payment_options';

	protected $fillable = [
		'code', 'label', 'mode', 'gateway_code', 'installments', 'active', 'commerce_code'
	];
}



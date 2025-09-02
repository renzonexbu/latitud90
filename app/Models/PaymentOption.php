<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentOption extends Model
{
	use HasFactory;

	protected $table = 'payment_options';

	protected $fillable = [
		'code', 'label', 'mode', 'gateway_code', 'installments', 'active', 'report_code'
	];

	protected $casts = [
		'installments' => 'integer',
		'active' => 'boolean'
	];

	public function programs()
	{
		return $this->belongsToMany(Program::class, 'program_payment_option')
					->withPivot('enabled')
					->withTimestamps();
	}

	public function orders()
	{
		return $this->hasMany(OrderDetail::class);
	}

	public function payments()
	{
		return $this->hasMany(Payment::class);
	}
}



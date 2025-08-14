<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesExecutive extends Model
{
	use HasFactory;

	protected $fillable = [
		'code',
		'name',
		'email',
		'phone',
		'active',
	];

	public function programs()
	{
		return $this->hasMany(Program::class);
	}
}



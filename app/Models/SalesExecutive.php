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
		'created_by'
	];

	public function programs()
	{
		return $this->hasMany(ProgramCourse::class);
	}

	public function programCourses()
	{
		return $this->hasMany(ProgramCourse::class);
	}

	public function createdBy()
	{
		return $this->belongsTo(User::class, 'created_by');
	}
}



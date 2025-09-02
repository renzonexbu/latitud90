<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * Get the courses created by this user.
     */
    public function courses()
    {
        return $this->hasMany(Course::class, 'created_by');
    }

    /**
     * Get the programs created by this user.
     */
    public function programs()
    {
        return $this->hasMany(Program::class, 'created_by');
    }

    /**
     * Get the sales executives created by this user.
     */
    public function salesExecutives()
    {
        return $this->hasMany(SalesExecutive::class, 'created_by');
    }

    public function approvedDiscounts()
    {
        return $this->hasMany(ParticipantProgramDiscount::class, 'approved_by');
    }

    public function institutions()
    {
        return $this->hasMany(Institution::class, 'created_by');
    }
}

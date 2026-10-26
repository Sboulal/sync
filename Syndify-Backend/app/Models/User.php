<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // 🟢 Rjje3naha

class User extends Authenticatable
{
    // 🟢 Zdnaha hna bash Laravel may7wwelch l-id l "0"
    use HasApiTokens, HasFactory, Notifiable, HasUuids, SoftDeletes; 

    protected $fillable = [
        'identifier',
        'full_name',
        'email',
        'tel',
        'role',
        'password',
        'activation_code',
        'otp_expires_at',
        'agreed_on_terms',
        'status',
    ];

    protected $hidden = [
        'remember_token', 
        'activation_code'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'agreed_on_terms' => 'boolean',
            'mailing_subs' => 'boolean',
        ];
    }

    public function coproprietes()
    {
        return $this->belongsToMany(Copropriete::class, 'user_as_owner', 'user_id', 'propriete_id')
                    ->withPivot('status', 'balance_prev', 'balance_new')
                    ->withTimestamps();
    }

    public function lots()
    {
        return $this->belongsToMany(Lot::class, 'user_owner_unit', 'user_id', 'unit_id')
                    ->withPivot('status')
                    ->withTimestamps();
    }
}
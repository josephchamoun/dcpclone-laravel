<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Level;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
        'referral_code',
        'referred_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationship: User's level
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    // Relationship: Users this user referred
    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    // Relationship: The user who referred this user
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    // Automatically generate a unique referral code
    protected static function booted(): void
    {
        static::creating(function ($user) {
            do {
                $code = strtoupper(Str::random(8));
            } while (User::where('referral_code', $code)->exists());

            $user->referral_code = $code;
        });
    }
}

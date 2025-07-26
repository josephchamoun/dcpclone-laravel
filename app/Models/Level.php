<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Level extends Model
{
    use HasFactory;

        protected $fillable = [
        'name', 'level_number', 'money_per_day', 'unlock_price', 'description', 'icon'
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'level_id');
    }
}

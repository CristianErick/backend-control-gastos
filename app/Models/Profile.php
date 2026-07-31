<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'currency',
        'monthly_budget_limit',
        'avatar',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

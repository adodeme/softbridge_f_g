<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = ['user_id', 'code', 'expires_at', 'used_at', 'attempts'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
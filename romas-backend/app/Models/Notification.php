<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Notification extends Model
{
    use Auditable;
    protected $fillable = [
        'user_id', 'message', 'date_envoi', 'lu'
    ];

    protected $casts = [
        'lu' => 'boolean'
    ];

    public function user() { return $this->belongsTo(User::class); }
}
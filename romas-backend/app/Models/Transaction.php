<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'client_id', 'transactable_type', 'transactable_id',
        'reference', 'amount', 'currency', 'status', 'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function transactable()
    {
        return $this->morphTo();
    }
}
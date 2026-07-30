<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Subscription extends Model
{
    use Auditable;
    protected $fillable = [
        'client_id', 'license_id', 'date_debut', 'date_fin', 'statut'
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function license() { return $this->belongsTo(License::class); }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Client extends Model
{
    use Auditable;
    protected $fillable = [
        'user_id', 'nom_entreprise', 'numero_client', 'adresse', 'date_inscription'
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function quotes() { return $this->hasMany(Quote::class); }
    public function projects() { return $this->hasMany(Project::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function subscriptions() { return $this->hasMany(Subscription::class); }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Invoice extends Model
{
    use Auditable;

    protected $fillable = [
        'client_id',
        'project_id',
        'numero',
        'date_creation',
        'montant',
        'statut',
        'type',
        'cle_acces',
        'subscription_id'   // ajouté
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
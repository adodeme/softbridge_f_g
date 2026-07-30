<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Quote extends Model
{
    use Auditable;
    protected $fillable = [
        'client_id', 'project_id', 'besoins', 'fonctionnalites', 'montant', 'statut'
    ];

    protected $casts = [
        'fonctionnalites' => 'array'
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function project() { return $this->belongsTo(Project::class); }
}
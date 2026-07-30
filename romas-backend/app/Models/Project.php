<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Project extends Model
{
    use Auditable;
    protected $fillable = [
        'client_id', 'nom', 'description', 'statut', 'date_livraison'
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function quote()
    {
        return $this->hasOne(Quote::class);
    }
}
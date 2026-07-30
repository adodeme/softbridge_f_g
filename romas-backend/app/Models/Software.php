<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Software extends Model
{
    use Auditable;
    // IMPORTANT : On force le nom de la table au pluriel pour éviter l'erreur
    protected $table = 'softwares';

    protected $fillable = [
        'nom', 'description', 'categorie', 'capture'
    ];

    public function licenses()
    {
        return $this->hasMany(License::class);
    }
}
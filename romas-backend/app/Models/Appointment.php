<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Appointment extends Model
{
    use Auditable;
    protected $fillable = [
        'user_id', 'date', 'heure_debut', 'duree', 'statut',
        'guest_nom', 'guest_prenom', 'guest_email', 'guest_telephone'
    ];

    public function user() { return $this->belongsTo(User::class); }
}
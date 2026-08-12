<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\Auditable;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use Auditable;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'role',
        'telephone',
        'photo',          // ← Ajout obligatoire pour l'upload de photo de profil
    ];

    /**
     * Relation : Un utilisateur peut avoir un profil Client.
     */
    public function client()
    {
        return $this->hasOne(Client::class);
    }

    /**
     * Relation personnalisée pour utiliser notre table 'notifications'.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;   // ← IMPORTANT
use App\Traits\Auditable;

class Software extends Model
{
    use Auditable;
    protected $table = 'softwares';

    protected $fillable = ['nom', 'description', 'categorie', 'capture', 'url'];

    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    // Accesseur pour obtenir l'URL publique de l'image
    public function getCaptureUrlAttribute()
    {
        if ($this->capture) {
            return asset(Storage::url($this->capture));
        }
        return null;
    }
}
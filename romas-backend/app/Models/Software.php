<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Accesseur pour l'URL publique de l'image.
     */
    public function getCaptureUrlAttribute()
    {
        try {
            if (empty($this->capture)) {
                return null;
            }
            // Si c'est déjà une URL complète (Cloudinary), on la retourne
            if (filter_var($this->capture, FILTER_VALIDATE_URL)) {
                return $this->capture;
            }
            // Pour les chemins locaux, utiliser Storage::url()
            return asset(Storage::url($this->capture));
        } catch (\Exception $e) {
            return null; // ne jamais planter à cause d'une image
        }
    }
}
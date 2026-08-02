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
     * Accesseur pour obtenir l'URL publique de l'image.
     */
    public function getCaptureUrlAttribute()
    {
        if (empty($this->capture)) {
            return null;
        }

        // Si c'est déjà une URL complète (Cloudinary ou autre), on la retourne directement
        if (filter_var($this->capture, FILTER_VALIDATE_URL)) {
            return $this->capture;
        }

        // Pour un chemin local (ex: softwares/...png)
        return rtrim(config('app.url'), '/') . '/storage/' . ltrim($this->capture, '/');
    }
}
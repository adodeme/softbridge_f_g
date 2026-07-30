<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use App\Traits\Auditable;

class License extends Model
{
    use Auditable;
    protected $fillable = [
        'software_id', 'type', 'duree', 'prix', 'key', 'status', 'last_accessed_at'
    ];

    /**
     * Accesseur pour décrypter la clé lorsqu'on la lit.
     */
    public function getKeyAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Mutateur pour crypter la clé avant de la sauvegarder en base.
     */
    public function setKeyAttribute($value)
    {
        $this->attributes['key'] = Crypt::encryptString($value);
    }

    public function software() { return $this->belongsTo(Software::class); }
}
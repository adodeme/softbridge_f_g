<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Report extends Model
{
    use Auditable;
    protected $fillable = [
        'project_id', 'chef_projet_id', 'titre', 'contenu', 'date_rapport', 'ignored'
    ];
    protected $casts = [
        'ignored' => 'boolean'
    ];

    public function project() { return $this->belongsTo(Project::class); }
    public function chefProjet() { return $this->belongsTo(User::class, 'chef_projet_id'); }
}
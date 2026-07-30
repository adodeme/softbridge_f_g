<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            self::log('created', $model);
        });
        static::updated(function ($model) {
            self::log('updated', $model);
        });
        static::deleted(function ($model) {
            self::log('deleted', $model);
        });
    }

    protected static function log($action, $model)
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => strtoupper($action),
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'changes' => $model->getChanges(),
            'ip_address' => request()->ip(),
        ]);
    }
}
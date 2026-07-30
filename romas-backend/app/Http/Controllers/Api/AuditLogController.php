<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Récupère tous les logs d'audit pour l'Administrateur.
     * On les trie du plus récent au plus ancien.
     */
    public function index()
    {
        // On charge les logs avec les informations de l'utilisateur qui a fait l'action
        return response()->json(
            AuditLog::with('user')
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        $user = auth()->user();

        // --- CONTORNEMENT SPÉCIAL POUR LA CRÉATION DE DEVIS ---
        if ($request->is('create-quote') && $request->method() === 'POST') {
            // Seul le Chef de Projet peut créer un devis
            if ($user->role === 'chef_projet') {
                return $next($request);
            } else {
                return response()->json(['message' => 'Seul le Chef de Projet peut créer un devis.'], 403);
            }
        }
        // ------------------------------------------------------

        foreach ($roles as $role) {
            if ($user->role == $role) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Accès refusé. Rôle insuffisant.'], 403);
    }
}
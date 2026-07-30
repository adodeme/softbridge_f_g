<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\License;
use Carbon\Carbon;

class CheckLicense
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        // Si l'utilisateur n'est pas un client, il n'a pas de licence
        if ($user->role !== 'client') {
            return response()->json(['message' => 'Accès réservé aux clients'], 403);
        }

        // Récupération de la licence depuis la requête (ex: passé en paramètre)
        $licenseId = $request->route('license_id'); // Ou $request->input('license_id')
        
        $license = License::where('id', $licenseId)
                          ->whereHas('subscriptions', function($q) use ($user) {
                              $q->where('client_id', $user->client->id);
                          })->first();

        if (!$license) {
            return response()->json(['message' => 'Licence introuvable ou non associée à ce client.'], 404);
        }

        if ($license->status !== 'active') {
            return response()->json(['message' => 'Votre licence est expirée ou suspendue.'], 403);
        }

        if (Carbon::now()->gt($license->subscriptions->first()->date_fin)) {
            return response()->json(['message' => 'Votre abonnement a expiré.'], 403);
        }

        return $next($request);
    }
}
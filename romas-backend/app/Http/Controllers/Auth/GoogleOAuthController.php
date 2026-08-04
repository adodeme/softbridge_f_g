<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoogleOAuthController extends Controller
{
    public function callback(Request $request)
    {
        $code = $request->query('code');
        if (!$code) {
            return response()->json(['error' => 'Code manquant'], 400);
        }

        // --- VALEURS EN DUR POUR TEST (à remplacer par env() ensuite) ---
        $clientId     = '1060036047205-fpj77o44oobuhd3uio4d48ikjg1vf1c8.apps.googleusercontent.com';
        $clientSecret = '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';
        $redirectUri  = 'http://localhost:8000/auth/google/callback';

        // Échange du code contre un access_token et refresh_token
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
        ]);

        if ($response->failed()) {
            return response()->json([
                'error'   => 'Échec de l’obtention du token',
                'details' => $response->json(),
            ], 500);
        }

        $tokens       = $response->json();
        $refreshToken = $tokens['refresh_token'] ?? null;

        if (!$refreshToken) {
            return response()->json([
                'error' => 'Aucun refresh_token reçu. Révoquez l’accès dans votre compte Google et réessayez.',
            ], 400);
        }

        return response()->json([
            'message'       => 'Refresh token obtenu avec succès.',
            'refresh_token' => $refreshToken,
        ]);
    }
}
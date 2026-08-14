<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Api\PaymentController;

// La route fixe DOIT être AVANT la route avec paramètre
Route::get('/paiement/callback', [PaymentController::class, 'paymentCallback'])->name('kkiapay.callback');
Route::get('/paiement/{reference}', [PaymentController::class, 'showPaymentPage'])->name('kkiapay.pay');

Route::get('/', function () {
    return view('welcome');
});

// --- Routes OAuth Google (pour obtenir un refresh token) ---

// Lance le flux d'autorisation
Route::get('/auth/google', function () {
    $query = http_build_query([
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'redirect_uri'  => env('GOOGLE_REDIRECT_URI'),
        'response_type' => 'code',
        'scope'         => 'https://www.googleapis.com/auth/gmail.send',
        'access_type'   => 'offline',
        'prompt'        => 'consent',
    ]);
    return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
});

// Reçoit le code et échange contre un refresh token
Route::get('/auth/google/callback', function (Request $request) {
    $code = $request->query('code');
    if (!$code) {
        return 'Code manquant';
    }

    $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
        'code'          => $code,
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri'  => env('GOOGLE_REDIRECT_URI'),
        'grant_type'    => 'authorization_code',
    ]);

    $tokens = $response->json();
    $refreshToken = $tokens['refresh_token'] ?? null;

    if (!$refreshToken) {
        return 'Aucun refresh token reçu. Révoquez l’accès dans votre compte Google et réessayez.';
    }

    return 'Refresh token : ' . $refreshToken;
});
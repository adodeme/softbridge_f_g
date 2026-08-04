<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleOAuthController;

Route::get('/auth/google/callback', [GoogleOAuthController::class, 'callback']);
Route::get('/', function () {
    return view('welcome');
});
Route::get('/auth/google', function () {
    $query = http_build_query([
        'client_id'     => env('1060036047205-fpj77o44oobuhd3uio4d48ikjg1vf1c8.apps.googleusercontent.com'),
        'redirect_uri'  => env('http://localhost:8000/auth/google/callback'),
        'response_type' => 'code',
        'scope'         => 'https://www.googleapis.com/auth/gmail.send',
        'access_type'   => 'offline',
        'prompt'        => 'consent',
    ]);

    return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
});


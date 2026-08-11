<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    // Étape 1 : vérifier email/mot de passe et envoyer OTP
    public function loginStep1(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        $this->otpService->generate($user);

        return response()->json([
            'message' => 'Un code OTP a été envoyé par email.',
            'user_id' => $user->id,
        ]);
    }

    // Étape 2 : vérifier OTP et connecter
    public function loginStep2(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'code' => 'required|string|size:6',
        ]);

        $user = User::find($request->user_id);
        $result = $this->otpService->verify($user, $request->code);

        if (!$result['valid']) {
            return response()->json(['message' => $result['message']], 401);
        }

        // Authentification réussie
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'access_token' => $token,
            'user' => $user,
        ]);
    }

    // Renvoyer un nouvel OTP
    public function resendOtp(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $user = User::find($request->user_id);
        $this->otpService->generate($user);

        return response()->json(['message' => 'Nouveau code OTP envoyé.']);
    }

    // Les autres méthodes (register, logout, user) restent inchangées
    // ...
}
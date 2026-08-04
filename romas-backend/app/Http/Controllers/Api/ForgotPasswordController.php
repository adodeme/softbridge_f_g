<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GmailApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Toujours renvoyer un message positif pour ne pas fuiter d'information
            return response()->json(['message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.'], 200);
        }

        // Générer un token de réinitialisation (comme le fait Password::sendResetLink)
        $token = Password::createToken($user);

        try {
            $gmail = new GmailApiService();
            $gmail->sendResetLink($user->email, $token);

            return response()->json(['message' => 'Un lien de réinitialisation a été envoyé à votre adresse email.'], 200);
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email Gmail: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de l\'envoi de l\'email.'], 500);
        }
    }

    public function reset(Request $request)
    {
        // La méthode reset reste inchangée (elle utilise le PasswordBroker pour réinitialiser le mot de passe)
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => \Hash::make($password)
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['message' => __($status)], 422);
    }
}
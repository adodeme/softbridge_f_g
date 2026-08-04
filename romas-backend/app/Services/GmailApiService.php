<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GmailApiService
{
    protected $clientId;
    protected $clientSecret;
    protected $refreshToken;
    protected $username;

    public function __construct()
    {
        $this->clientId     = env('GOOGLE_CLIENT_ID');
        $this->clientSecret = env('GOOGLE_CLIENT_SECRET');
        $this->refreshToken = env('GOOGLE_REFRESH_TOKEN');
        $this->username     = env('MAIL_USERNAME');
    }

    /**
     * Obtenir un access token à partir du refresh token.
     */
    protected function getAccessToken(): string
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type'    => 'refresh_token',
        ]);

        if ($response->failed()) {
            Log::error('Échec de rafraîchissement du token Gmail', $response->json());
            throw new \Exception('Impossible d’obtenir un access token Gmail.');
        }

        return $response->json()['access_token'];
    }

    /**
     * Envoyer un email de réinitialisation de mot de passe.
     */
    public function sendResetLink(string $toEmail, string $token): void
    {
        $frontendUrl = config('app.frontend_url');
        $resetLink   = $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($toEmail);

        $subject = 'Réinitialisation de votre mot de passe SoftBridge';
        $body    = "Bonjour,\n\nVous avez demandé une réinitialisation de mot de passe.\n\nCliquez sur ce lien pour continuer : $resetLink\n\nCe lien expirera dans 15 minutes.\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce message.";

        // Construire le message MIME conforme à l'API Gmail
        $rawMessage = $this->createMimeMessage($toEmail, $subject, $body);

        $accessToken = $this->getAccessToken();

        // Envoi multipart (obligatoire pour l'API Gmail upload)
        $response = Http::withToken($accessToken)
            ->attach('file', $rawMessage, 'message.eml')
            ->asMultipart()
            ->post('https://gmail.googleapis.com/upload/gmail/v1/users/me/messages/send');

        if ($response->failed()) {
            Log::error('Échec de l\'envoi Gmail', $response->json());
            throw new \Exception('Impossible d\'envoyer l\'email via Gmail.');
        }
    }

    /**
     * Créer un message MIME encodé en base64url.
     */
    protected function createMimeMessage(string $to, string $subject, string $body): string
    {
        $from    = $this->username;
        $message = "From: $from\r\n";
        $message .= "To: $to\r\n";
        $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $body;

        return rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
    }
}
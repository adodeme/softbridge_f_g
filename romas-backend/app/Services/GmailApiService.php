<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

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
    public function getAccessToken(): string
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
     * Envoyer un email HTML via Gmail API.
     */
    public function sendHtmlEmail(string $to, string $subject, string $html): void
    {
        $rawMessage = $this->createHtmlMimeMessage($to, $subject, $html);
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->asJson()
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $rawMessage,
            ]);

        if ($response->failed()) {
            $errorBody = $response->body();
            Log::error('Échec de l\'envoi Gmail HTML: ' . $errorBody);
            throw new \Exception('Gmail API error: ' . $errorBody);
        }
    }

    /**
     * Envoyer un email de réinitialisation de mot de passe (HTML).
     */
    public function sendResetLink(string $toEmail, string $token): void
    {
        $frontendUrl = config('app.frontend_url');
        $resetUrl = $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($toEmail);

        $html = View::make('emails.reset-password', [
            'url' => $resetUrl,
        ])->render();

        $this->sendHtmlEmail($toEmail, 'Réinitialisation de votre mot de passe SoftBridge', $html);
    }

    /**
     * Créer un message MIME HTML encodé en base64url.
     */
    protected function createHtmlMimeMessage(string $to, string $subject, string $html): string
    {
        $from = $this->username;
        $message = "From: $from\r\n";
        $message .= "To: $to\r\n";
        $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $html;

        return rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
    }

    /**
     * Ancienne méthode d'envoi de texte brut, conservée pour compatibilité.
     */
    public function sendEmail(string $toEmail, string $subject, string $body): void
    {
        $this->sendHtmlEmail($toEmail, $subject, nl2br($body));
    }
}
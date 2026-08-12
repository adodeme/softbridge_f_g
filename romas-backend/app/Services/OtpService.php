<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OtpService
{
    const MAX_ATTEMPTS = 5;
    const VALIDITY_MINUTES = 2;

    protected $gmailService;

    public function __construct(GmailApiService $gmailService)
    {
        $this->gmailService = $gmailService;
    }

    public function generate(User $user): Otp
    {
        // Invalider tous les anciens OTP non utilisés pour cet utilisateur
        Otp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = random_int(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(self::VALIDITY_MINUTES);

        $otp = Otp::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);

        // Envoyer le code par email via Gmail API
        $subject = 'Votre code de vérification SoftBridge';
        $body = "Votre code OTP est : $code\nIl expire dans 2 minutes.";
        $this->gmailService->sendEmail($user->email, $subject, $body);

        return $otp;
    }

    public function verify(User $user, string $code): array
    {
        // Récupère le dernier OTP non utilisé
        $otp = Otp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->latest()
            ->first();

        Log::info("Vérification OTP pour user {$user->id}");

        if (!$otp) {
            Log::warning("Aucun OTP trouvé pour user {$user->id}");
            return ['valid' => false, 'message' => 'Aucun OTP trouvé.'];
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            $otp->used_at = now();
            $otp->save();
            Log::warning("Trop de tentatives OTP pour user {$user->id}");
            return ['valid' => false, 'message' => 'Trop de tentatives. Veuillez demander un nouveau code.'];
        }

        if ($otp->code !== $code) {
            $otp->increment('attempts');
            Log::warning("Code OTP incorrect pour user {$user->id}");
            return ['valid' => false, 'message' => 'Code OTP incorrect.'];
        }

        if ($otp->expires_at <= now()) {
            Log::warning("OTP expiré pour user {$user->id}");
            return ['valid' => false, 'message' => 'Le code a expiré.'];
        }

        // OTP correct → marquer comme utilisé
        $otp->used_at = now();
        $otp->save();
        Log::info("OTP validé pour user {$user->id}");

        return ['valid' => true];
    }
}
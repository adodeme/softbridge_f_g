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
        // Invalider TOUS les anciens OTP non utilisés pour cet utilisateur
        Otp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = random_int(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(self::VALIDITY_MINUTES);

        $otp = Otp::create([
            'user_id'   => $user->id,
            'code'      => $code,
            'expires_at' => $expiresAt,
        ]);

        // Envoi de l'email
        try {
            $this->gmailService->sendEmail($user->email, 'Votre code OTP SoftBridge', "Code : $code (valable 2 min)");
        } catch (\Exception $e) {
            Log::error('Échec envoi OTP email: ' . $e->getMessage());
        }

        Log::info("OTP généré pour user {$user->id} : $code");
        return $otp;
    }

    public function verify(User $user, string $code): array
    {
        $otp = Otp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->latest()
            ->first();

        Log::info("Tentative OTP user {$user->id} – code reçu : $code");

        if (!$otp) {
            Log::warning("Aucun OTP trouvé pour user {$user->id}");
            return ['valid' => false, 'message' => 'Aucun OTP trouvé. Veuillez redemander un code.'];
        }

        if ($otp->expires_at <= now()) {
            Log::warning("OTP expiré pour user {$user->id}");
            return ['valid' => false, 'message' => 'Le code a expiré. Redemandez-en un.'];
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            $otp->used_at = now();
            $otp->save();
            Log::warning("Trop de tentatives OTP pour user {$user->id}");
            return ['valid' => false, 'message' => 'Trop de tentatives. Redemandez un code.'];
        }

        if ($otp->code !== $code) {
            $otp->increment('attempts');
            Log::warning("Code OTP incorrect pour user {$user->id}");
            return ['valid' => false, 'message' => 'Code incorrect.'];
        }

        // OTP correct
        $otp->used_at = now();
        $otp->save();
        Log::info("OTP validé pour user {$user->id}");

        return ['valid' => true];
    }
}
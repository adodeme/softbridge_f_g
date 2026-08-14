<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

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

        // Rendre la vue HTML de l'OTP
        $html = View::make('emails.otp', [
            'code' => $code,
        ])->render();

        // Envoyer via Gmail API en HTML
        try {
            $this->gmailService->sendHtmlEmail($user->email, 'Votre code de vérification SoftBridge', $html);
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
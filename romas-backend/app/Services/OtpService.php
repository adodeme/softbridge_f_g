<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;

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
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);

        $subject = 'Votre code de vérification SoftBridge';
        $body = "Votre code OTP est : $code\nIl expire dans 2 minutes.";
        $this->gmailService->sendEmail($user->email, $subject, $body);

        return $otp;
    }

    public function verify(User $user, string $code): array
    {
        $otp = Otp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$otp) {
            return ['valid' => false, 'message' => 'Aucun OTP trouvé.'];
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            $otp->used_at = now();
            $otp->save();
            return ['valid' => false, 'message' => 'Trop de tentatives. Veuillez demander un nouveau code.'];
        }

        if ($otp->code !== $code) {
            $otp->increment('attempts');
            return ['valid' => false, 'message' => 'Code OTP incorrect.'];
        }

        if ($otp->expires_at <= now()) {
            return ['valid' => false, 'message' => 'Le code a expiré.'];
        }

        $otp->used_at = now();
        $otp->save();
        return ['valid' => true];
    }
}
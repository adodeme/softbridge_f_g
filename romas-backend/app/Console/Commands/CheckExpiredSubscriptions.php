<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:check';
    protected $description = 'Vérifie les abonnements bientôt expirés et envoie des notifications.';

    public function handle()
    {
        $now = Carbon::now();
        $dates = [
            30 => '30 jours',
            15 => '15 jours',
            7  => '7 jours',
            3  => '3 jours',
            1  => '1 jour'
        ];

        foreach ($dates as $days => $label) {
            $targetDate = $now->copy()->addDays($days)->toDateString();
            
            // Cherche les abonnements qui expirent exactement dans $days jours
            $subscriptions = Subscription::whereDate('date_fin', $targetDate)
                                          ->where('statut', 'active')
                                          ->with('client.user')
                                          ->get();

            foreach ($subscriptions as $sub) {
                Notification::create([
                    'user_id' => $sub->client->user_id,
                    'message' => "Votre abonnement expire dans {$label}. Pensez à renouveler !",
                    'date_envoi' => now(),
                    'lu' => false
                ]);
                // Optionnel : Envoyer un email ici
            }
        }

        $this->info('Notifications d\'expiration envoyées avec succès.');
    }
}
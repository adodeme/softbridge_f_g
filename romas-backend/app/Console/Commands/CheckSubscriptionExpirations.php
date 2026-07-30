<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckSubscriptionExpirations extends Command
{
    protected $signature = 'subscriptions:check';
    protected $description = 'Vérifie les abonnements bientôt expirés et envoie des notifications.';

    public function handle()
    {
        // Cherche les abonnements qui expirent dans les 7 prochains jours
        $expiringSoon = Subscription::where('date_fin', '<=', Carbon::now()->addDays(7))
                                     ->where('date_fin', '>', Carbon::now())
                                     ->where('statut', 'active')
                                     ->with('client.user')
                                     ->get();

        foreach ($expiringSoon as $sub) {
            $user = $sub->client->user;
            
            // Créer une notification dans la base de données
            Notification::create([
                'user_id' => $user->id,
                'message' => "Votre abonnement au logiciel arrive à expiration le " . $sub->date_fin->format('d/m/Y') . ". Pensez à renouveler !",
                'date_envoi' => now(),
                'lu' => false
            ]);

            // Optionnel : Envoyer un email ici aussi
            // Mail::to($user->email)->send(...);
        }

        $this->info('Notifications de renouvellement envoyées avec succès.');
    }
}
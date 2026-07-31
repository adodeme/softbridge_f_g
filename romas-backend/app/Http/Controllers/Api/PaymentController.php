<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\License;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function simulatePayment(Request $request)
    {
        $request->validate(['invoice_id' => 'required|exists:invoices,id']);
        $invoice = Invoice::with('client.user')->findOrFail($request->invoice_id);

        if ($invoice->client_id !== Auth::user()->client->id) {
            return response()->json(['error' => 'Action non autorisée.'], 403);
        }
        if ($invoice->statut === 'paye') {
            return response()->json(['error' => 'Cette facture est déjà payée.'], 422);
        }

        // --- Simulation : on reproduit exactement le code du webhook ---

        $invoice->statut = 'paye';
        $invoice->save();

        Payment::create([
            'invoice_id' => $invoice->id,
            'montant' => $invoice->montant,
            'date_paiement' => now(),
            'methode' => 'Simulation',
            'reference_fedapay' => 'SIM-' . uniqid()
        ]);

        // Si c'est un abonnement, génération de licence et clé
        if ($invoice->type === 'abonnement') {
            // Récupérer l'abonnement actif créé lors de la souscription
            $subscription = Subscription::where('client_id', $invoice->client_id)
                                        ->where('statut', 'active')
                                        ->latest()
                                        ->first();

            if ($subscription) {
                $uniqueKey = Str::uuid();
                $license = License::create([
                    'software_id' => $subscription->license->software_id,
                    'key' => $uniqueKey,
                    'status' => 'active',
                    'type' => $subscription->license->type,
                    'duree' => $subscription->license->duree,
                    'prix' => $subscription->license->prix
                ]);
                // Mise à jour de l'abonnement avec la nouvelle licence
                $subscription->license_id = $license->id;
                $subscription->save();

                // Lier la facture à l'abonnement et y stocker la clé
                $invoice->cle_acces = $uniqueKey;
                $invoice->subscription_id = $subscription->id;  // <-- AJOUT CAPITAL
                $invoice->save();
            }
        }

        // Notification client
        Notification::create([
            'user_id' => $invoice->client->user_id,
            'message' => "Paiement simulé confirmé pour la facture {$invoice->numero}.",
            'date_envoi' => now(),
            'lu' => false
        ]);

        return response()->json(['message' => 'Paiement simulé avec succès.']);
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        // Vérification de base du statut (pour la production, vous devez vérifier la signature HMAC)
        if (isset($payload['status']) && $payload['status'] === 'completed') {
            $invoice = Invoice::findOrFail($payload['metadata']['invoice_id']);
            if ($invoice->statut === 'paye') {
                return response('Déjà traité', 200);
            }

            $invoice->statut = 'paye';
            $invoice->save();

            // Enregistrement du paiement
            Payment::create([
                'invoice_id' => $invoice->id,
                'montant' => $invoice->montant,
                'date_paiement' => now(),
                'methode' => 'Fedapay',
                'reference_fedapay' => $payload['id'] ?? null
            ]);

            // Si c'est un abonnement, on crée la licence
            if ($invoice->type === 'abonnement') {
                $subscription = Subscription::where('client_id', $invoice->client_id)
                                            ->where('statut', 'active')
                                            ->latest()
                                            ->first();

                if ($subscription) {
                    // Génération de la clé de licence
                    $uniqueKey = Str::uuid();
                    $license = License::create([
                        'software_id' => $subscription->license->software_id,
                        'key' => $uniqueKey,
                        'status' => 'active',
                        'type' => $subscription->license->type,
                        'duree' => $subscription->license->duree,
                        'prix' => $subscription->license->prix
                    ]);

                    // Mise à jour de l'abonnement avec la nouvelle licence
                    $subscription->license_id = $license->id;
                    $subscription->save();

                    // La clé d'accès est stockée dans la facture (chiffrée via le modèle)
                    $invoice->cle_acces = $uniqueKey;
                    $invoice->save();
                }
            }

            // Notification au client
            Notification::create([
                'user_id' => $invoice->client->user->id,
                'message' => "Paiement confirmé pour la facture {$invoice->numero}.",
                'date_envoi' => now(),
                'lu' => false
            ]);
            // Dans handleWebhook, après validation du paiement
            $license = License::create([
                'software_id' => $subscription->license->software_id,
                'key' => $uniqueKey,
                'status' => 'active', // On force 'active' car le paiement est confirmé
                'type' => $subscription->license->type,
                'duree' => $subscription->license->duree,
                'prix' => $subscription->license->prix
            ]);

            return response('Webhook traité avec succès', 200);
        }

        return response('Webhook ignoré', 200);
    }
}
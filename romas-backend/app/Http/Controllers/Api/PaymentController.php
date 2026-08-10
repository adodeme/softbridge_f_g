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
    public function initiate(Request $request)
    {
        $request->validate(['invoice_id' => 'required|exists:invoices,id']);
        $invoice = Invoice::with('client.user')->findOrFail($request->invoice_id);

        if ($invoice->client_id !== Auth::user()->client->id) {
            return response()->json(['error' => 'Action non autorisée.'], 403);
        }
        if ($invoice->statut === 'paye') {
            return response()->json(['error' => 'Cette facture est déjà payée.'], 422);
        }

        $callbackUrl = env('APP_URL') . '/api/webhooks/fedapay';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('FEDAPAY_SECRET_KEY')
        ])->post('https://api.fedapay.com/v1/transactions', [
            'amount' => $invoice->montant,
            'currency' => 'XOF',
            'description' => 'Facture #' . $invoice->numero,
            'callback_url' => $callbackUrl,
            'metadata' => ['invoice_id' => $invoice->id]
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Erreur de communication avec FedaPay.'], 500);
        }

        return response()->json(['payment_url' => $response->json()['url']]);
    }

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

        $invoice->statut = 'paye';
        $invoice->save();

        Payment::create([
            'invoice_id' => $invoice->id,
            'montant' => $invoice->montant,
            'date_paiement' => now(),
            'methode' => 'Simulation',
            'reference_fedapay' => 'SIM-' . uniqid()
        ]);

        if ($invoice->type === 'abonnement') {
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
                $subscription->license_id = $license->id;
                $subscription->save();

                $invoice->cle_acces = $uniqueKey;
                $invoice->subscription_id = $subscription->id;
                $invoice->save();
            }
        }

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

        if (isset($payload['status']) && $payload['status'] === 'completed') {
            $invoice = Invoice::findOrFail($payload['metadata']['invoice_id']);
            if ($invoice->statut === 'paye') {
                return response('Déjà traité', 200);
            }

            $invoice->statut = 'paye';
            $invoice->save();

            Payment::create([
                'invoice_id' => $invoice->id,
                'montant' => $invoice->montant,
                'date_paiement' => now(),
                'methode' => 'Fedapay',
                'reference_fedapay' => $payload['id'] ?? null
            ]);

            if ($invoice->type === 'abonnement') {
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

                    $subscription->license_id = $license->id;
                    $subscription->save();

                    $invoice->cle_acces = $uniqueKey;
                    $invoice->save();
                }
            }

            Notification::create([
                'user_id' => $invoice->client->user->id,
                'message' => "Paiement confirmé pour la facture {$invoice->numero}.",
                'date_envoi' => now(),
                'lu' => false
            ]);

            return response('Webhook traité avec succès', 200);
        }

        return response('Webhook ignoré', 200);
    }
}
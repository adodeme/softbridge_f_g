<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\License;
use App\Models\Transaction;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    // Initier un paiement pour un devis ou un abonnement
    public function initiate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:devis,abonnement',
            'id' => 'required|integer',
        ]);

        $user = Auth::user();
        $client = $user->client;

        if (!$client) {
            return response()->json(['error' => 'Profil client introuvable.'], 403);
        }

        if ($request->type === 'devis') {
            $invoice = Invoice::where('id', $request->id)
                ->where('client_id', $client->id)
                ->where('type', 'devis')
                ->where('statut', 'impaye')
                ->firstOrFail();
            $amount = $invoice->montant;
            $metadata = ['invoice_id' => $invoice->id, 'type' => 'devis'];
            $transactable = $invoice;
        } else { // abonnement
            $subscription = Subscription::where('id', $request->id)
                ->where('client_id', $client->id)
                ->where('statut', 'active')
                ->firstOrFail();
            $invoice = Invoice::where('subscription_id', $subscription->id)
                ->where('statut', 'impaye')
                ->firstOrFail();
            $amount = $invoice->montant;
            $metadata = ['invoice_id' => $invoice->id, 'type' => 'abonnement', 'subscription_id' => $subscription->id];
            $transactable = $subscription;
        }

        // Créer une transaction locale en attente
        $transaction = Transaction::create([
            'client_id' => $client->id,
            'transactable_type' => get_class($transactable),
            'transactable_id' => $transactable->id,
            'reference' => Str::uuid(),
            'amount' => $amount,
            'status' => 'en_attente',
            'metadata' => $metadata,
        ]);

        return response()->json([
            'payment_url' => route('kkiapay.pay', ['transaction' => $transaction->reference]),
            'transaction_reference' => $transaction->reference,
        ]);
    }

    // Webhook Kkiapay (sera appelé par Kkiapay)
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        // Vérification de la signature (optionnel mais recommandé)
        // ... implémentez selon la doc Kkiapay

        if (isset($payload['status']) && $payload['status'] === 'SUCCESS') {
            $transaction = Transaction::where('reference', $payload['transactionId'])->first();
            if (!$transaction) {
                return response('Transaction non trouvée', 404);
            }

            // Vérification supplémentaire auprès de l'API Kkiapay
            $verification = $this->verifyTransaction($payload['transactionId']);
            if (!$verification) {
                return response('Transaction invalide', 200);
            }

            // Mise à jour de la transaction
            $transaction->status = 'reussie';
            $transaction->save();

            // Traitement selon le type
            $metadata = $transaction->metadata;
            if ($metadata['type'] === 'devis') {
                $this->handleDevisPayment($metadata['invoice_id']);
            } else {
                $this->handleAbonnementPayment($metadata['invoice_id'], $metadata['subscription_id']);
            }
        }

        return response('Webhook traité', 200);
    }

    // Vérification manuelle (callback)
    public function verifyPayment(Request $request)
    {
        $transaction = Transaction::where('reference', $request->reference)->first();
        if (!$transaction) {
            return response()->json(['error' => 'Transaction introuvable'], 404);
        }

        $verified = $this->verifyTransaction($request->reference);
        if ($verified && $transaction->status !== 'reussie') {
            $transaction->status = 'reussie';
            $transaction->save();
            // Traitement du paiement
            $metadata = $transaction->metadata;
            if ($metadata['type'] === 'devis') {
                $this->handleDevisPayment($metadata['invoice_id']);
            } else {
                $this->handleAbonnementPayment($metadata['invoice_id'], $metadata['subscription_id']);
            }
        }

        return response()->json(['status' => $transaction->status]);
    }

    protected function verifyTransaction($transactionId)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('kkiapay.private_key'),
            'Accept' => 'application/json',
        ])->get(config('kkiapay.api_base') . '/api/v1/transactions/' . $transactionId);

        if ($response->failed()) {
            return false;
        }
        return $response->json()['status'] === 'SUCCESS';
    }

    protected function handleDevisPayment($invoiceId)
    {
        $invoice = Invoice::find($invoiceId);
        if (!$invoice || $invoice->statut === 'paye') return;

        $invoice->statut = 'paye';
        $invoice->save();

        Payment::create([
            'invoice_id' => $invoice->id,
            'montant' => $invoice->montant,
            'date_paiement' => now(),
            'methode' => 'KkiaPay',
            'reference_fedapay' => null, // ou ID KkiaPay
        ]);

        Notification::create([
            'user_id' => $invoice->client->user_id,
            'message' => "Paiement confirmé pour la facture {$invoice->numero}.",
            'date_envoi' => now(),
            'lu' => false
        ]);
    }

    protected function handleAbonnementPayment($invoiceId, $subscriptionId)
    {
        $invoice = Invoice::find($invoiceId);
        if (!$invoice || $invoice->statut === 'paye') return;

        $invoice->statut = 'paye';
        $invoice->save();

        $subscription = Subscription::find($subscriptionId);
        if (!$subscription) return;

        // Générer une licence si ce n'est pas un renouvellement (ou toujours, selon votre logique)
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

        Payment::create([
            'invoice_id' => $invoice->id,
            'montant' => $invoice->montant,
            'date_paiement' => now(),
            'methode' => 'KkiaPay',
            'reference_fedapay' => null,
        ]);

        Notification::create([
            'user_id' => $invoice->client->user_id,
            'message' => "Paiement confirmé pour l'abonnement. Votre clé d'accès est disponible.",
            'date_envoi' => now(),
            'lu' => false
        ]);
    }
    public function showPaymentPage($reference)
    {
        $transaction = Transaction::where('reference', $reference)->firstOrFail();
        $publicKey = config('kkiapay.public_key');
        $sandbox = config('kkiapay.sandbox') ? 'true' : 'false';

        return view('paiement.kkiapay', compact('transaction', 'publicKey', 'sandbox'));
    }

    public function paymentCallback(Request $request)
    {
        // Le widget Kkiapay redirige ici après paiement
        // Vous pouvez faire une vérification manuelle et rediriger l'utilisateur
        $reference = $request->input('reference');
        return redirect()->route('dashboard.client')->with('status', 'Vérification du paiement...');
    }
}
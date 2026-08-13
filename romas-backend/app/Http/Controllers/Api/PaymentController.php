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
use Kkiapay\Kkiapay;

class PaymentController extends Controller
{
    // Initier un paiement pour un devis ou un abonnement
    public function initiate(Request $request)
    {
        $request->validate(['invoice_id' => 'required|exists:invoices,id']);
        $user = Auth::user();
        $client = $user->client;

        if (!$client) {
            return response()->json(['error' => 'Profil client introuvable.'], 403);
        }

        $invoice = Invoice::where('id', $request->invoice_id)
            ->where('client_id', $client->id)
            ->first();

        if (!$invoice) {
            return response()->json(['error' => 'Facture introuvable.'], 404);
        }

        if ($invoice->statut === 'paye') {
            return response()->json(['error' => 'Cette facture est déjà payée.'], 422);
        }

        // Préparation des métadonnées selon le type
        $metadata = [
            'invoice_id' => $invoice->id,
            'type' => $invoice->type,
        ];

        if ($invoice->type === 'abonnement' && $invoice->subscription_id) {
            $metadata['subscription_id'] = $invoice->subscription_id;
        }

        // Création de la transaction locale
        $transaction = Transaction::create([
            'client_id' => $client->id,
            'transactable_type' => $invoice->type === 'devis' ? Invoice::class : Subscription::class,
            'transactable_id' => $invoice->type === 'devis' ? $invoice->id : $invoice->subscription_id,
            'reference' => (string) Str::uuid(),
            'amount' => $invoice->montant,
            'status' => 'en_attente',
            'metadata' => $metadata,
        ]);

        // URL de paiement (à adapter selon votre route)
        $paymentUrl = route('kkiapay.pay', ['reference' => $transaction->reference]);

        return response()->json([
            'payment_url' => $paymentUrl,
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
        $kkiapay = new Kkiapay(
            config('kkiapay.public_key'),
            config('kkiapay.private_key'),
            config('kkiapay.secret_key'),
            config('kkiapay.sandbox')
        );

        try {
            $transaction = $kkiapay->verifyTransaction($transactionId);
            return $transaction->status === 'SUCCESS';
        } catch (\Exception $e) {
            \Log::error('Erreur Kkiapay verify: ' . $e->getMessage());
            return false;
        }
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

        if (empty($publicKey)) {
            return response('Clé publique Kkiapay manquante. Vérifiez KKIAPAY_PUBLIC_KEY.', 500);
        }

        return view('paiement.kkiapay', compact('transaction', 'publicKey', 'sandbox'));
    }

    public function paymentCallback(Request $request)
    {
        $transactionId = $request->query('transaction_id') ?? $request->input('transaction_id');

        if (!$transactionId) {
            return redirect('/dashboard/client/accueil')->with('error', 'Référence de transaction manquante.');
        }

        $transaction = Transaction::where('reference', $transactionId)->first();

        if (!$transaction) {
            return redirect('/dashboard/client/accueil')->with('error', 'Transaction introuvable.');
        }

        $verified = $this->verifyTransaction($transactionId);

        if ($verified && $transaction->status !== 'reussie') {
            $transaction->status = 'reussie';
            $transaction->save();

            $metadata = $transaction->metadata;
            if ($metadata['type'] === 'devis') {
                $this->handleDevisPayment($metadata['invoice_id']);
            } else {
                $this->handleAbonnementPayment($metadata['invoice_id'], $metadata['subscription_id']);
            }

            return redirect('/dashboard/client/accueil')->with('success', 'Paiement confirmé.');
        }

        return redirect('/dashboard/client/accueil')->with('error', 'Paiement non confirmé.');
    }
}
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
use Illuminate\Support\Str;
use Kkiapay\Kkiapay;

class PaymentController extends Controller
{
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

        $metadata = [
            'invoice_id' => $invoice->id,
            'type' => $invoice->type,
        ];

        if ($invoice->type === 'abonnement' && $invoice->subscription_id) {
            $metadata['subscription_id'] = $invoice->subscription_id;
        }

        $transaction = Transaction::create([
            'client_id' => $client->id,
            'transactable_type' => $invoice->type === 'devis' ? Invoice::class : Subscription::class,
            'transactable_id' => $invoice->type === 'devis' ? $invoice->id : $invoice->subscription_id,
            'reference' => (string) Str::uuid(),
            'amount' => $invoice->montant,
            'status' => 'en_attente',
            'metadata' => $metadata,
        ]);

        $paymentUrl = route('kkiapay.pay', ['reference' => $transaction->reference]);

        return response()->json([
            'payment_url' => $paymentUrl,
            'transaction_reference' => $transaction->reference,
        ]);
    }

    public function handleWebhook(Request $request)
    {
        $secret = config('kkiapay.secret_key');
        $signature = $request->header('X-KkiaPay-Signature');
        $payload = $request->getContent();
        $computed = hash_hmac('sha256', $payload, $secret);

        if (!$signature || !hash_equals($computed, $signature)) {
            return response('Signature invalide', 401);
        }
        $payload = $request->all();

        if (isset($payload['status']) && $payload['status'] === 'SUCCESS') {
            $reference = $payload['data'] ?? null;
            $transactionId = $payload['transactionId'] ?? null;

            if (!$reference || !$transactionId) {
                return response('Paramètres manquants', 400);
            }

            $transaction = Transaction::where('reference', $reference)->first();
            if (!$transaction) {
                return response('Transaction non trouvée', 404);
            }

            // Enregistrer l'ID Kkiapay
            $transaction->kkiapay_transaction_id = $transactionId;
            $transaction->save();

            $verification = $this->verifyTransaction($transactionId);
            if (!$verification) {
                return response('Transaction invalide', 200);
            }

            $transaction->status = 'reussie';
            $transaction->save();

            $metadata = $transaction->metadata;
            if ($metadata['type'] === 'devis') {
                $this->handleDevisPayment($metadata['invoice_id']);
            } else {
                $this->handleAbonnementPayment($metadata['invoice_id'], $metadata['subscription_id']);
            }

            return response('Webhook traité', 200);
        }

        return response('Webhook ignoré', 200);
    }

    public function verifyApiPayment(Request $request)
    {
        $reference = $request->query('reference') ?? $request->input('reference');
        if (!$reference) {
            return response()->json(['message' => 'Référence manquante'], 400);
        }

        $transaction = Transaction::where('reference', $reference)
            ->where('client_id', Auth::user()->client->id)
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction introuvable'], 404);
        }

        // Si la transaction a déjà été marquée réussie par le webhook, tout va bien
        if ($transaction->status === 'reussie') {
            return response()->json(['message' => 'Paiement confirmé.']);
        }

        // Sinon, si l'ID Kkiapay est disponible, on vérifie
        if ($transaction->kkiapay_transaction_id) {
            $verified = $this->verifyTransaction($transaction->kkiapay_transaction_id);
            if ($verified) {
                $transaction->status = 'reussie';
                $transaction->save();
                $metadata = $transaction->metadata;
                if ($metadata['type'] === 'devis') {
                    $this->handleDevisPayment($metadata['invoice_id']);
                } else {
                    $this->handleAbonnementPayment($metadata['invoice_id'], $metadata['subscription_id']);
                }
                return response()->json(['message' => 'Paiement confirmé.']);
            }
        }

        return response()->json(['message' => 'Paiement en attente de confirmation.'], 202);
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
            'reference_fedapay' => null,
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
        $callbackUrl = route('kkiapay.callback', ['reference' => $transaction->reference]);

        if (empty($publicKey)) {
            return response('Clé publique Kkiapay manquante. Vérifiez KKIAPAY_PUBLIC_KEY.', 500);
        }

        return view('paiement.kkiapay', compact('transaction', 'publicKey', 'sandbox', 'callbackUrl'));
    }

    public function paymentCallback(Request $request)
    {
        $transactionId = $request->query('transaction_id');
        $reference = $request->query('reference'); // notre reference si on l'a passée

        if (!$transactionId || !$reference) {
            return redirect(config('app.frontend_url') . '/dashboard/client/accueil')->with('error', 'Paramètres manquants.');
        }

        $transaction = Transaction::where('reference', $reference)->first();
        if (!$transaction) {
            return redirect(config('app.frontend_url') . '/dashboard/client/accueil')->with('error', 'Transaction introuvable.');
        }

        // Enregistrer l'ID Kkiapay
        $transaction->kkiapay_transaction_id = $transactionId;
        $transaction->save();

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

            return redirect(config('app.frontend_url') . '/dashboard/client/accueil')->with('success', 'Paiement confirmé.');
        }

        return redirect(config('app.frontend_url') . '/dashboard/client/accueil')->with('error', 'Paiement non confirmé.');
    }
}
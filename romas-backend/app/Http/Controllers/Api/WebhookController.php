<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function handleFedapay(Request $request)
    {
        // 1. Dans la vraie vie, ici vous vérifiez la signature du webhook (sécurité) pour être sûr que c'est bien Fedapay.
        
        $data = $request->all(); // Fedapay envoie un JSON contenant le statut et l'invoice_id

        // 2. Vérification du succès du paiement
        if (isset($data['status']) && $data['status'] === 'completed') {
            
            $invoiceId = $data['metadata']['invoice_id']; // On suppose que vous avez envoyé invoice_id dans les metadata
            
            $invoice = Invoice::findOrFail($invoiceId);

            // 3. Mettre la facture à jour
            $invoice->statut = 'paye';
            
            // 4. GÉNÉRER LA CLÉ D'ACCÈS (Le St Graal)
            $cleAcces = Str::uuid(); // Exemple : "550e8400-e29b-41d4-a716-446655440000"
            $invoice->cle_acces = $cleAcces;
            $invoice->save();

            // 5. Enregistrer le paiement dans la table payments
            Payment::create([
                'invoice_id' => $invoice->id,
                'montant' => $invoice->montant,
                'date_paiement' => now(),
                'methode' => 'Fedapay',
                'reference_fedapay' => $data['transaction_id'] ?? null
            ]);

            // 6. Optionnel : Envoyer un email au client avec la clé d'accès
            // Mail::to($invoice->client->user->email)->send(new PaymentSuccessMail($invoice));

            return response()->json(['message' => 'Webhook traité avec succès'], 200);
        }

        return response()->json(['message' => 'Webhook ignoré (paiement non complété)'], 200);
    }
}

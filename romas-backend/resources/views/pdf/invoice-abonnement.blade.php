<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoice->numero }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin-bottom: 0; color: #0C3A7A; }
        .header p { margin: 2px 0; font-size: 13px; }
        .section-title { background-color: #0C3A7A; color: white; padding: 5px 10px; font-weight: bold; margin: 20px 0 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .total { font-weight: bold; font-size: 16px; }
        .notes { font-style: italic; font-size: 12px; margin-top: 30px; }
        .footer { text-align: center; margin-top: 40px; font-weight: bold; color: #0C3A7A; }
        .statut-paye { color: green; font-weight: bold; }
    </style>
</head>
<body>
    <!-- En-tête SoftBridge -->
    <div class="header">
        <h1>SOFTBRIDGE</h1>
        <p>Solutions digitales & logicielles</p>
        <p>Développement web • Applications • SaaS</p>
        <p>Adresse : Cotonou, Bénin | Tél : +229 XX XX XX XX | Email : contact@softbridge.bj</p>
        <p>IFU : XXXXXXXX | RCCM : XXXXXXXX</p>
    </div>

    <!-- Titre Facture -->
    <div style="text-align: center; margin: 20px 0;">
        <h2 style="border-top: 2px solid #0C3A7A; border-bottom: 2px solid #0C3A7A; padding: 10px 0;">FACTURE</h2>
    </div>

    <!-- Infos facture -->
    <table>
        <tr>
            <td width="50%">
                <strong>N° Facture :</strong> {{ $invoice->numero }}<br>
                <strong>Date d’émission :</strong> {{ \Carbon\Carbon::parse($invoice->date_creation)->format('d/m/Y') }}<br>
                <strong>Échéance :</strong> {{ \Carbon\Carbon::parse($invoice->date_creation)->format('d/m/Y') }}
            </td>
            <td width="50%">
                <strong>STATUT :</strong> 
                @if($invoice->statut === 'paye')
                    <span class="statut-paye">✓ SOLDÉ</span>
                @else
                    EN ATTENTE DE PAIEMENT
                @endif
            </td>
        </tr>
    </table>

    <!-- Client -->
    <div class="section-title">CLIENT</div>
    <table>
        <tr>
            <td>
                <strong>Nom / Entreprise :</strong> {{ $invoice->client->nom_entreprise ?? 'N/A' }}<br>
                <strong>Contact :</strong> {{ $invoice->client->user->prenom ?? '' }} {{ $invoice->client->user->nom ?? '' }}<br>
                <strong>Email :</strong> {{ $invoice->client->user->email ?? 'client@example.com' }}<br>
                <strong>Adresse :</strong> {{ $invoice->client->adresse ?? 'Cotonou, Bénin' }}
            </td>
        </tr>
    </table>

    <!-- Abonnement -->
    <div class="section-title">ABONNEMENT</div>
    <table>
        <tr>
            <td>
                <strong>Solution :</strong> {{ $invoice->subscription->license->software->nom ?? 'N/A' }}<br>
                <strong>Licence :</strong> {{ $invoice->subscription->license->key ?? 'N/A' }}<br>
                <strong>Formule :</strong> {{ strtoupper($invoice->subscription->license->type ?? 'N/A') }}<br>
                <strong>Période :</strong> {{ \Carbon\Carbon::parse($invoice->subscription->date_debut)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($invoice->subscription->date_fin)->format('d/m/Y') }}
            </td>
        </tr>
    </table>

    <!-- Détails -->
    <div class="section-title">DÉTAILS</div>
    <table>
        <thead>
            <tr>
                <th>Désignation</th>
                <th class="text-right">Qté</th>
                <th class="text-right">PU</th>
                <th class="text-right">Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Abonnement {{ strtoupper($invoice->subscription->license->type ?? '') }} - {{ $invoice->subscription->license->duree ?? '' }} jours</td>
                <td class="text-right">1</td>
                <td class="text-right">{{ number_format($invoice->montant, 0, ',', ' ') }} FCFA</td>
                <td class="text-right">{{ number_format($invoice->montant, 0, ',', ' ') }} FCFA</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right"><strong>SOUS-TOTAL :</strong></td>
                <td class="text-right"><strong>{{ number_format($invoice->montant, 0, ',', ' ') }} FCFA</strong></td>
            </tr>
            <tr>
                <td colspan="3" class="text-right"><strong>TVA :</strong></td>
                <td class="text-right">0 FCFA</td>
            </tr>
            <tr class="total">
                <td colspan="3" class="text-right"><strong>TOTAL :</strong></td>
                <td class="text-right">{{ number_format($invoice->montant, 0, ',', ' ') }} FCFA</td>
            </tr>
        </tfoot>
    </table>

    <!-- Paiement -->
    <div class="section-title">PAIEMENT</div>
    <table>
        <tr>
            <td>
                <strong>Montant payé :</strong> {{ $invoice->statut === 'paye' ? number_format($invoice->montant, 0, ',', ' ') . ' FCFA' : '0 FCFA' }}<br>
                <strong>Reste à payer :</strong> {{ $invoice->statut === 'paye' ? '0 FCFA' : number_format($invoice->montant, 0, ',', ' ') . ' FCFA' }}
            </td>
        </tr>
    </table>

    <!-- Prochain renouvellement (seulement si payé) -->
    @if($invoice->statut === 'paye' && $invoice->subscription)
    <div class="section-title">PROCHAIN RENOUVELLEMENT</div>
    <table>
        <tr>
            <td>
                <strong>Date :</strong> {{ \Carbon\Carbon::parse($invoice->subscription->date_fin)->addDay()->format('d/m/Y') }}<br>
                <strong>Montant prévu :</strong> {{ number_format($invoice->montant, 0, ',', ' ') }} FCFA
            </td>
        </tr>
    </table>
    @endif

    <!-- Mode de paiement -->
    <div class="section-title">MODE DE PAIEMENT</div>
    <p>Paiement en ligne</p>

    <!-- Notes -->
    <div class="section-title">NOTES</div>
    <p class="notes">
        L'abonnement donne accès aux fonctionnalités incluses dans la formule {{ strtoupper($invoice->subscription->license->type ?? '') }} pendant la période indiquée ci-dessus.<br>
        Le renouvellement est soumis aux conditions d'abonnement applicables.
    </p>

    <div class="footer">
        SOFTBRIDGE — Merci pour votre confiance
    </div>
</body>
</html>
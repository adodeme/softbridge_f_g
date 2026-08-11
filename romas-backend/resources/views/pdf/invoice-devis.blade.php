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
                <strong>Échéance :</strong> {{ \Carbon\Carbon::parse($invoice->date_creation)->addDays(30)->format('d/m/Y') }}
            </td>
            <td width="50%">
                <!-- Statut -->
                <strong>STATUT :</strong> 
                @if($invoice->statut === 'paye')
                    ✓ SOLDÉ
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
                <strong>Téléphone :</strong> {{ $invoice->client->user->telephone ?? '+229 XX XX XX XX' }}<br>
                <strong>Email :</strong> {{ $invoice->client->user->email ?? 'client@example.com' }}<br>
                <strong>Adresse :</strong> {{ $invoice->client->adresse ?? 'Cotonou, Bénin' }}<br>
                <strong>IFU :</strong> XXXXXXXX
            </td>
        </tr>
    </table>

    <!-- Projet -->
    <div class="section-title">PROJET</div>
    <table>
        <tr>
            <td>
                <strong>Projet :</strong> {{ $invoice->project->nom ?? 'N/A' }}<br>
                <strong>Référence devis :</strong> {{ $invoice->project->quote->id ?? 'N/A' }}
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
                <td>Réalisation du projet sur mesure</td>
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

    <!-- Conditions de paiement -->
    <div class="section-title">CONDITIONS DE PAIEMENT</div>
    <table>
        <tr>
            <td>
                Acompte à la commande : 50 %<br>
                Montant de l'acompte : {{ number_format($invoice->montant / 2, 0, ',', ' ') }} FCFA<br>
                Solde à la livraison : {{ number_format($invoice->montant / 2, 0, ',', ' ') }} FCFA
            </td>
        </tr>
    </table>

    <!-- Mode de paiement -->
    <div class="section-title">MODE DE PAIEMENT</div>
    <p>Mobile Money / Virement bancaire / Paiement en ligne</p>

    <!-- Notes -->
    <div class="section-title">NOTES</div>
    <p class="notes">
        La mise en production du projet est effectuée conformément aux conditions définies dans le devis accepté.<br>
        La licence d'utilisation du logiciel, la maintenance et l'hébergement sont soumis aux conditions prévues au contrat.
    </p>

    <div class="footer">
        SOFTBRIDGE — Merci pour votre confiance
    </div>
</body>
</html>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Facture</title></head>
<body>
    <h1>SoftBridge - Facture {{ $invoice->numero }}</h1>
    <p>Client : {{ $invoice->client->nom_entreprise }}</p>
    <p>Montant : {{ number_format($invoice->montant, 0, ',', ' ') }} FCFA</p>
    <p>Date : {{ $invoice->date_creation }}</p>
    <hr>
    <h3>VOTRE CLÉ D'ACCÈS AU LOGICIEL :</h3>
    <h2 style="background:#f0f0f0; padding:10px; font-family:monospace;">
        {{ $invoice->cle_acces }}
    </h2>
    <p>Conservez précieusement cette clé.</p>
</body>
</html>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Facture SoftBridge</title></head>
<body>
    <h2>Facture {{ $invoice->numero }}</h2>
    <hr>
    <p><strong>Client :</strong> {{ $invoice->client->nom_entreprise }}</p>
    <p><strong>Montant :</strong> {{ $invoice->montant }} FCFA</p>
    <p><strong>Statut :</strong> {{ $invoice->statut }}</p>
    @if($invoice->type === 'abonnement' && $invoice->statut === 'paye')
        <p><strong>Clé d'accès :</strong> {{ $invoice->cle_acces }}</p>
    @endif
    <p><strong>Date d'émission :</strong> {{ $invoice->date_creation }}</p>
</body>
</html>
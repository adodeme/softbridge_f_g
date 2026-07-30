<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Confirmation de rendez-vous</title></head>
<body>
    <h2>SoftBridge - Confirmation de rendez-vous</h2>
    <hr>
    <p><strong>Nom :</strong> {{ $appointment->user->nom ?? $appointment->guest_nom }}</p>
    <p><strong>Prénom :</strong> {{ $appointment->user->prenom ?? $appointment->guest_prenom }}</p>
    <p><strong>Email :</strong> {{ $appointment->user->email ?? $appointment->guest_email }}</p>
    <p><strong>Date :</strong> {{ $appointment->date }} à {{ $appointment->heure_debut }}</p>
    <p><strong>Durée :</strong> {{ $appointment->duree }} minutes</p>
</body>
</html>
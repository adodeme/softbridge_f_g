<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Rapport SoftBridge</title></head>
<body>
    <h2>Rapport d'avancement</h2>
    <p><strong>Projet :</strong> {{ $report->project->nom }}</p>
    <p><strong>Titre :</strong> {{ $report->titre }}</p>
    <p><strong>Contenu :</strong> {{ $report->contenu }}</p>
    <p><strong>Date :</strong> {{ $report->date_rapport }}</p>
</body>
</html>
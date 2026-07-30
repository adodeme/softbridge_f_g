<h1>{{ $report->titre }}</h1>
<p><strong>Projet :</strong> {{ $report->project->nom }}</p>
<p><strong>Chef de projet :</strong> {{ $report->chefProjet->prenom }} {{ $report->chefProjet->nom }}</p>
<p><strong>Date :</strong> {{ $report->date_rapport }}</p>
<div>{{ $report->contenu }}</div>
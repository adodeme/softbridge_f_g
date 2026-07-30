<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Invoice;
use App\Models\Software;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $projectsByClient = Client::withCount('projects')->get()->map(function ($client) {
            return ['client' => $client->nom_entreprise, 'count' => $client->projects_count];
        });

        $stats = [
            'total_users' => User::count(),
            'total_clients' => Client::count(),
            'total_projects' => Project::count(),
            'projects_in_progress' => Project::where('statut', 'en_cours')->count(),
            'projects_completed' => Project::where('statut', 'termine')->count(),
            'total_quotes' => Quote::count(),
            'pending_quotes' => Quote::where('statut', 'envoye')->count(),
            'total_revenue' => Invoice::where('statut', 'paye')->sum('montant'),
            'total_softwares' => Software::count(),
            'total_reports' => Report::count(),
            'total_invoices_paid' => Invoice::where('statut', 'paye')->count(),
            'projects_by_client' => $projectsByClient
        ];

        return response()->json($stats);
    }
}
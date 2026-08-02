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
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            if ($user->role === 'client') {
                return response()->json([
                    'totalQuotes' => $user->client->quotes->count(),
                    'totalProjects' => $user->client->projects->count(),
                    'totalInvoices' => $user->client->invoices->count(),
                    'unreadNotifications' => Notification::where('user_id', $user->id)->where('lu', false)->count()
                ]);
            } elseif ($user->role === 'chef_projet') {
                return response()->json([
                    'totalClients' => User::where('role', 'client')->count(),
                    'totalQuotes' => Quote::count(),
                    'totalProjects' => Project::count(),
                    'totalReports' => Report::count(),
                    'unreadNotifications' => Notification::where('user_id', $user->id)->where('lu', false)->count()
                ]);
            } else {
                // ADMINISTRATEUR
                // Récupération des projets par client
                $projectsByClient = Client::withCount('projects')
                    ->get()
                    ->map(function ($client) {
                        return [
                            'name' => $client->nom_entreprise,
                            'count' => $client->projects_count
                        ];
                    });

                return response()->json([
                    'total_users' => User::count(),
                    'total_clients' => User::where('role', 'client')->count(),
                    'total_projects' => Project::count(),
                    'pending_quotes' => Quote::where('statut', 'envoye')->count(),
                    'total_revenue' => Invoice::where('statut', 'paye')->sum('montant'),
                    'total_softwares' => Software::count(),
                    'projects_by_client' => $projectsByClient // <-- Les vrais clients !
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur SQL : ' . $e->getMessage()
            ], 500);
        }
    }
}
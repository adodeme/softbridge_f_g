<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    // Liste des rapports (filtrée selon le rôle)
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'administrateur') {
            // L'admin voit les rapports non ignorés
            return response()->json(
                Report::where('ignored', false)
                    ->with('project', 'chefProjet')
                    ->orderBy('created_at', 'desc')
                    ->get()
            );
        }

        // Le chef de projet voit ses propres rapports
        return response()->json(
            Report::where('chef_projet_id', $user->id)
                ->with('project')
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    // Détail d'un rapport
    public function show($id)
    {
        $report = Report::with('project', 'chefProjet')->findOrFail($id);

        // Notification si l'admin consulte le rapport (sauf si c'est le chef lui-même)
        if (Auth::user()->role === 'administrateur' && $report->chef_projet_id) {
            Notification::create([
                'user_id' => $report->chef_projet_id,
                'message' => "L'administrateur a consulté votre rapport '{$report->titre}'.",
                'date_envoi' => now(),
                'lu' => false
            ]);
        }

        return response()->json($report);
    }

    // Création d'un rapport (chef de projet uniquement)
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'titre' => 'required|string',
            'contenu' => 'required|string'
        ]);

        $report = Report::create([
            'project_id' => $request->project_id,
            'chef_projet_id' => Auth::id(),
            'titre' => $request->titre,
            'contenu' => $request->contenu,
            'date_rapport' => now(),
            'ignored' => false
        ]);

        return response()->json($report, 201);
    }

    // Modification d'un rapport (chef de projet, uniquement son propre rapport)
    public function update(Request $request, Report $report)
    {
        if (Auth::id() !== $report->chef_projet_id) {
            return response()->json(['error' => 'Action non autorisée.'], 403);
        }

        $request->validate([
            'titre' => 'sometimes|required|string',
            'contenu' => 'sometimes|required|string',
        ]);

        $report->update($request->only(['titre', 'contenu']));

        return response()->json($report);
    }

    // Téléchargement du PDF (admin)
    public function downloadPdf($id)
    {
        $report = Report::with('project', 'chefProjet')->findOrFail($id);
        $pdf = Pdf::loadView('pdf.report', ['report' => $report]);
        return $pdf->download('rapport_' . $report->id . '.pdf');
    }

    // Ignorer un rapport (admin)
    public function ignore($id)
    {
        $report = Report::findOrFail($id);
        $report->ignored = true;
        $report->save();

        return response()->json(['message' => 'Rapport ignoré.']);
    }
}
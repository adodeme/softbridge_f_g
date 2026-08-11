<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Project;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Notification;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuoteController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->role === 'client') {
            return response()->json($user->client->quotes()->with('client.user', 'project')->get());
        }
        return response()->json(Quote::with('client.user', 'project')->get());
    }

    public function show($id)
    {
        return response()->json(Quote::with('client.user', 'project')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Cas 1 : le client soumet une demande spontanée
        if ($user->role === 'client') {
            if (!$user->client) {
                \App\Models\Client::create([
                    'user_id' => $user->id,
                    'nom_entreprise' => 'Client ' . $user->prenom . ' ' . $user->nom,
                    'numero_client' => 'CLI-' . strtoupper(uniqid()),
                    'date_inscription' => now()
                ]);
                $user->load('client');
            }

            $request->validate([
                'besoins' => 'required|string',
                'fonctionnalites' => 'required|array',
                'montant' => 'nullable|numeric'
            ]);

            $quote = Quote::create([
                'client_id' => $user->client->id,
                'besoins' => $request->besoins,
                'fonctionnalites' => $request->fonctionnalites,
                'montant' => $request->montant ?? 0,
                'statut' => 'en_attente'
            ]);

            // Notification aux chefs de projet
            foreach (User::where('role', 'chef_projet')->get() as $chef) {
                Notification::create([
                    'user_id' => $chef->id,
                    'message' => "Nouvelle demande de devis de {$user->prenom} {$user->nom}.",
                    'date_envoi' => now(),
                    'lu' => false
                ]);
            }

            return response()->json($quote, 201);
        }

        // Cas 2 : le chef de projet crée un devis pour un client existant
        if ($user->role === 'chef_projet') {
            $request->validate([
                'client_id' => 'required|exists:clients,id',
                'besoins' => 'required|string',
                'fonctionnalites' => 'required|array',
                'montant' => 'required|numeric'
            ]);

            $quote = Quote::create($request->all() + ['statut' => 'en_attente']);
            return response()->json($quote, 201);
        }

        return response()->json(['message' => 'Accès refusé.'], 403);
    }

    public function update(Request $request, Quote $quote)
    {
        if ($quote->statut !== 'en_attente') {
            return response()->json(['error' => 'Ce devis ne peut plus être modifié.'], 403);
        }
        $quote->update($request->only(['besoins', 'fonctionnalites', 'montant']));
        return response()->json($quote);
    }

    public function destroy(Quote $quote)
    {
        if ($quote->statut === 'valide') {
            return response()->json(['error' => 'Impossible de supprimer un devis validé.'], 403);
        }
        $quote->delete();
        return response()->json(['message' => 'Devis supprimé.']);
    }

    public function send($id)
    {
        $quote = Quote::with('client.user')->findOrFail($id);
        $quote->statut = 'envoye';
        $quote->save();

        foreach (User::where('role', 'administrateur')->get() as $admin) {
            Notification::create(['user_id' => $admin->id, 'message' => "Le devis #{$quote->id} a été envoyé.", 'date_envoi' => now(), 'lu' => false]);
        }
        Notification::create(['user_id' => $quote->client->user_id, 'message' => "Vous avez reçu un nouveau devis.", 'date_envoi' => now(), 'lu' => false]);

        return response()->json(['message' => 'Devis envoyé avec succès.']);
    }

    public function validateQuote($id)
    {
        $quote = Quote::findOrFail($id);
        if ($quote->client_id !== Auth::user()->client->id) {
            return response()->json(['error' => 'Action non autorisée.'], 403);
        }
        if ($quote->statut !== 'envoye') {
            return response()->json(['error' => 'Ce devis ne peut pas être validé.'], 403);
        }

        $quote->statut = 'valide';
        $quote->save();

        foreach (User::where('role', 'chef_projet')->get() as $chef) {
            Notification::create(['user_id' => $chef->id, 'message' => "Le client a validé le devis #{$quote->id}.", 'date_envoi' => now(), 'lu' => false]);
        }

        return response()->json(['message' => 'Devis validé avec succès.']);
    }

    public function convertToInvoice($id)
    {
        $quote = Quote::with('client')->findOrFail($id);
        if ($quote->statut !== 'valide') {
            return response()->json(['error' => 'Seul un devis validé peut être converti.'], 422);
        }

        $project = Project::create([
            'client_id' => $quote->client_id,
            'nom' => 'Projet ' . $quote->client->nom_entreprise,
            'description' => $quote->besoins,
            'statut' => 'en_cours'
        ]);
        $quote->project_id = $project->id;
        $quote->statut = 'termine';
        $quote->save();

        $invoice = Invoice::create([
            'client_id' => $quote->client_id,
            'project_id' => $project->id,
            'numero' => 'FAC-DEV-' . uniqid(),
            'date_creation' => now(),
            'montant' => $quote->montant,
            'statut' => 'impaye',
            'type' => 'devis'
        ]);

        Notification::create([
            'user_id' => $quote->client->user_id,
            'message' => "Votre projet et votre facture ont été créés.",
            'date_envoi' => now(),
            'lu' => false
        ]);

        return response()->json([
            'message' => 'Devis converti avec succès.',
            'project' => $project,
            'invoice' => $invoice
        ]);
    }

    public function rejectQuote($id)
    {
        $quote = Quote::findOrFail($id);
        if ($quote->client_id !== Auth::user()->client->id) {
            return response()->json(['error' => 'Action non autorisée.'], 403);
        }
        if ($quote->statut !== 'envoye') {
            return response()->json(['error' => 'Ce devis ne peut pas être refusé.'], 403);
        }

        $quote->statut = 'refuse';
        $quote->save();

        foreach (User::where('role', 'chef_projet')->get() as $chef) {
            Notification::create([
                'user_id' => $chef->id,
                'message' => "Le client a refusé le devis #{$quote->id}.",
                'date_envoi' => now(),
                'lu' => false
            ]);
        }

        return response()->json(['message' => 'Devis refusé.']);
    }
}
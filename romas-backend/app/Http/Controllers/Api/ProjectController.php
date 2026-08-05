<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class ProjectController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->role === 'client') {
            return response()->json($user->client->projects()->with('quote')->get());
        }
        return response()->json(
            Project::with('client:id,nom_entreprise', 'quote:id,besoins')->get()
        );
    }

    public function show($id)
    {
        return response()->json(Project::with('client', 'quote')->findOrFail($id));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate(['statut' => 'required|in:en_cours,termine']);
        $oldStatus = $project->statut;
        $project->statut = $request->statut;
        $project->save();

        // Si le projet vient d'être terminé, on notifie le client
        if ($oldStatus !== 'termine' && $project->statut === 'termine') {
            Notification::create([
                'user_id' => $project->client->user_id,
                'message' => "Votre projet '{$project->nom}' est terminé.",
                'date_envoi' => now(),
                'lu' => false
            ]);
        }

        return response()->json($project);
    }
    // App\Models\Project.php
    public function quote()
    {
        return $this->hasOne(Quote::class);
    }
}
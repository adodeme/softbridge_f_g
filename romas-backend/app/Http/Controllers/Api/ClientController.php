<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    public function index()
    {
        return response()->json(Client::with('user')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email|unique:users',
            'nom_entreprise' => 'required|string',
            'telephone' => 'nullable|string'
        ]);

        $generatedPassword = Str::random(10);

        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'role' => 'client',
            'password' => Hash::make($generatedPassword)
        ]);

        $client = Client::create([
            'user_id' => $user->id,
            'nom_entreprise' => $request->nom_entreprise,
            'numero_client' => 'CLI-' . strtoupper(uniqid()),
            'date_inscription' => now()
        ]);

        return response()->json([
            'client' => $client->load('user'),
            'generated_password' => $generatedPassword
        ], 201);
    }

    public function update(Request $request, Client $client)
    {
        $client->update($request->only(['nom_entreprise', 'adresse']));
        if ($request->has('email') || $request->has('nom') || $request->has('prenom') || $request->has('telephone')) {
            $user = $client->user;
            $user->update($request->only(['nom', 'prenom', 'email', 'telephone']));
        }
        return response()->json(['message' => 'Client mis à jour.', 'client' => $client->fresh('user')]);
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json(['message' => 'Client supprimé.']);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index() { return response()->json(User::all()); }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required', 'prenom' => 'required', 'email' => 'required|email|unique:users',
            'role' => 'required|in:client,chef_projet,administrateur', 'password' => 'required|min:6'
        ]);

        $user = User::create([
            'nom' => $request->nom, 'prenom' => $request->prenom, 'email' => $request->email,
            'role' => $request->role, 'password' => Hash::make($request->password)
        ]);

        // Si le rôle est client, créer automatiquement un profil Client
        if ($user->role === 'client') {
            Client::create([
                'user_id' => $user->id,
                'nom_entreprise' => 'Client ' . $user->prenom . ' ' . $user->nom,
                'numero_client' => 'CLI-' . strtoupper(uniqid()),
                'date_inscription' => now()
            ]);
        }

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $request->validate(['nom' => 'string', 'prenom' => 'string', 'email' => 'email|unique:users,email,' . $user->id, 'role' => 'in:client,chef_projet,administrateur']);
        if ($request->filled('password')) $user->password = Hash::make($request->password);
        $user->update($request->except('password'));
        return response()->json($user);
    }

    public function destroy(User $user) { $user->delete(); return response()->json(['message' => 'Utilisateur supprimé.']); }
}
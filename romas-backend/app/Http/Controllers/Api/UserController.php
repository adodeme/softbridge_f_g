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
    // Obtenir le profil complet
    public function profile()
    {
        $user = Auth::user()->load('client');
        return response()->json($user);
    }

    // Mettre à jour les informations
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'nom' => 'string',
            'prenom' => 'string',
            'telephone' => 'nullable|string',
            'email' => 'email|unique:users,email,' . $user->id,
        ]);
        $user->update($request->only(['nom', 'prenom', 'email', 'telephone']));
        return response()->json($user);
    }

    // Uploader la photo de profil (via Cloudinary)
    public function uploadPhoto(Request $request)
    {
        $request->validate(['photo' => 'required|image|max:2048']);
        $user = Auth::user();

        // Upload vers Cloudinary
        $cloudinary = new Cloudinary(env('CLOUDINARY_URL'));
        $uploadResult = $cloudinary->uploadApi()->upload(
            $request->file('photo')->getRealPath(),
            ['folder' => 'softbridge/profiles']
        );
        $user->photo = $uploadResult['secure_url'];
        $user->save();

        return response()->json(['photo' => $user->photo]);
    }
}
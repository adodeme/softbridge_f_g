<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Software;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class SoftwareController extends Controller
{
    public function index()
    {
        $softwares = Software::with('licenses')->get();
        return response()->json($softwares)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function show($id)
    {
        return response()->json(Software::with('licenses')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string',
            'description' => 'required|string',
            'categorie' => 'required|string',
            'url' => 'nullable|url',
            'capture' => 'nullable|image|max:5120', // max 5 Mo
            'licenses' => 'nullable|json'
        ]);

        $captureUrl = null;
        if ($request->hasFile('capture')) {
            // Upload vers Cloudinary
            $uploadedFile = Cloudinary::upload($request->file('capture')->getRealPath(), [
                'folder' => 'softbridge/logiciels'
            ]);
            $captureUrl = $uploadedFile->getSecurePath();
        }

        $software = Software::create([
            'nom' => $request->nom,
            'description' => $request->description,
            'categorie' => $request->categorie,
            'url' => $request->url,
            'capture' => $captureUrl
        ]);

        // Gestion des licences
        if ($request->filled('licenses')) {
            $licenses = json_decode($request->licenses, true);
            if (is_array($licenses)) {
                foreach ($licenses as $lic) {
                    License::create([
                        'software_id' => $software->id,
                        'type' => $lic['type'],
                        'duree' => $lic['duree'],
                        'prix' => $lic['prix']
                    ]);
                }
            }
        }

        return response()->json($software->load('licenses'), 201);
    }

    public function update(Request $request, Software $software)
    {
        $request->validate([
            'nom' => 'sometimes|required|string',
            'description' => 'sometimes|required|string',
            'categorie' => 'sometimes|required|string',
            'url' => 'nullable|url',
            'capture' => 'nullable|image|max:5120',
            'licenses' => 'nullable|json'
        ]);

        if ($request->hasFile('capture')) {
            // Optionnel : supprimer l'ancienne image sur Cloudinary
            // (nécessite de stocker le public_id en BDD pour utiliser destroy())
            // $publicId = $software->capture_public_id;
            // Cloudinary::destroy($publicId);

            $uploadedFile = Cloudinary::upload($request->file('capture')->getRealPath(), [
                'folder' => 'softbridge/logiciels'
            ]);
            $software->capture = $uploadedFile->getSecurePath();
        }

        $software->update($request->only(['nom', 'description', 'categorie', 'url']));

        // Mise à jour des licences
        if ($request->filled('licenses')) {
            $licenses = json_decode($request->licenses, true);
            if (is_array($licenses)) {
                $software->licenses()->delete();
                foreach ($licenses as $lic) {
                    License::create([
                        'software_id' => $software->id,
                        'type' => $lic['type'],
                        'duree' => $lic['duree'],
                        'prix' => $lic['prix']
                    ]);
                }
            }
        }

        return response()->json($software->load('licenses'));
    }

    public function destroy(Request $request, Software $software)
    {
        try {
            $activeLicenses = $software->licenses()->where('status', 'active')->get();
            if ($activeLicenses->isNotEmpty() && !$request->boolean('force')) {
                return response()->json([
                    'message' => 'Ce logiciel possède des licences actives.',
                    'active_licenses' => $activeLicenses->map(fn($l) => [
                        'id' => $l->id,
                        'type' => $l->type,
                        'duree' => $l->duree,
                        'prix' => $l->prix,
                    ]),
                ], 409);
            }

            // Supprimer l'image Cloudinary (optionnel)
            // $publicId = $software->capture_public_id;
            // Cloudinary::destroy($publicId);

            $software->licenses()->delete();
            $software->delete();

            return response()->json(['message' => 'Logiciel supprimé avec succès.']);
        } catch (\Exception $e) {
            Log::error('Erreur suppression logiciel: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ... autres méthodes (access, download, verifyKey) inchangées
}
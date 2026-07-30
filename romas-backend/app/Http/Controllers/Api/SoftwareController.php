<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Software;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SoftwareController extends Controller
{
    public function index()
    {
        $softwares = Software::with('licenses')->get();
        Log::info('Index appelé, renvoie ' . $softwares->count() . ' logiciels.');
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
            'nom' => 'required',
            'description' => 'required',
            'categorie' => 'required',
            'capture' => 'nullable|image|max:5120'
        ]);

        $path = null;
        if ($request->hasFile('capture')) {
            $path = $request->file('capture')->store('softwares', 'public');
        }

        $software = Software::create([
            'nom' => $request->nom,
            'description' => $request->description,
            'categorie' => $request->categorie,
            'capture' => $path
        ]);

        if ($request->has('licenses')) {
            foreach ($request->licenses as $lic) {
                License::create([
                    'software_id' => $software->id,
                    'type' => $lic['type'],
                    'duree' => $lic['duree'],
                    'prix' => $lic['prix']
                ]);
            }
        }

        return response()->json($software, 201);
    }

    public function update(Request $request, Software $software)
    {
        $request->validate([
            'nom' => 'sometimes|required',
            'description' => 'sometimes|required',
            'categorie' => 'sometimes|required',
            'capture' => 'nullable|image|max:5120'
        ]);

        if ($request->hasFile('capture')) {
            if ($software->capture) {
                Storage::disk('public')->delete($software->capture);
            }
            $path = $request->file('capture')->store('softwares', 'public');
            $software->capture = $path;
        }

        $software->update($request->except('capture'));
        return response()->json($software);
    }

    public function destroy(Request $request, Software $software)
    {
        try {
            $activeLicenses = $software->licenses()->where('status', 'active')->get();

            // Cas 1 : il y a des licences actives ET l'admin n'a pas encore confirmé
            if ($activeLicenses->isNotEmpty() && !$request->boolean('force')) {
                return response()->json([
                    'message' => 'Ce logiciel possède des licences actives.',
                    'active_licenses' => $activeLicenses->map(function ($license) {
                        return [
                            'id' => $license->id,
                            'type' => $license->type,
                            'duree' => $license->duree,
                            'prix' => $license->prix,
                        ];
                    }),
                ], 409);
            }

            // Cas 2 : pas de licences actives, OU force=true confirmé par l'admin
            Log::info('Suppression logiciel ID ' . $software->id . ($activeLicenses->isNotEmpty() ? ' (forcée, avec licences actives)' : ' (sans licences actives)'));

            $software->licenses()->delete();

            if ($software->capture) {
                Storage::disk('public')->delete($software->capture);
            }

            $software->delete();

            return response()->json(['message' => 'Logiciel supprimé avec succès.'], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la suppression : ' . $e->getMessage()], 500);
        }
    }
}
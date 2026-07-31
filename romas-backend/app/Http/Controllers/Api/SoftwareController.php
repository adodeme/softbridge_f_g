<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Software;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class SoftwareController extends Controller
{
    /**
     * Catalogue public : liste tous les logiciels avec leurs licences.
     */
    public function index()
    {
        $softwares = Software::with('licenses')->get();
        return response()->json($softwares)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Détail d'un logiciel (pour la page publique).
     */
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
            'capture' => 'nullable|image|max:5120',
            'licenses' => 'nullable|json'
        ]);

        // Gestion de l'image
        $path = null;
        if ($request->hasFile('capture')) {
            $path = $request->file('capture')->store('softwares', 'public');
        }

        // Création du logiciel
        $software = Software::create([
            'nom' => $request->nom,
            'description' => $request->description,
            'categorie' => $request->categorie,
            'url' => $request->url,
            'capture' => $path
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

    /**
     * Modification d'un logiciel (admin).
     */
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

        // Gestion de l'image
        if ($request->hasFile('capture')) {
            // Supprimer l'ancienne image si elle existe
            if ($software->capture) {
                Storage::disk('public')->delete($software->capture);
            }
            $path = $request->file('capture')->store('softwares', 'public');
            $software->capture = $path;
        }

        // Mise à jour des champs texte
        $software->update($request->only(['nom', 'description', 'categorie', 'url']));

        // Mise à jour des licences : suppression des anciennes et recréation
        if ($request->filled('licenses')) {
            $licenses = json_decode($request->licenses, true);
            if (is_array($licenses)) {
                // Supprimer les licences existantes
                $software->licenses()->delete();
                // Créer les nouvelles
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

    /**
     * Suppression d'un logiciel (admin). Bloqué s'il y a des licences actives,
     * sauf si le paramètre force=true est passé.
     */
    public function destroy(Request $request, Software $software)
    {
        try {
            $activeLicenses = $software->licenses()->where('status', 'active')->get();

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

    /**
     * Accès à un logiciel après vérification de la licence (middleware CheckLicense).
     * Retourne l'URL du logiciel pour redirection côté frontend.
     */
    public function access($license_id)
    {
        $license = License::with('software')->findOrFail($license_id);
        $software = $license->software;

        if (!$software->url) {
            return response()->json(['message' => 'Aucune URL définie pour ce logiciel.'], 404);
        }

        // Mise à jour de la date de dernier accès
        $license->last_accessed_at = now();
        $license->save();

        return response()->json(['url' => $software->url]);
    }

    /**
     * Téléchargement d'un logiciel (protégé par CheckLicense via la route groupée).
     * Actuellement renvoie un message, à adapter selon vos besoins.
     */
    public function download($license_id)
    {
        // La vérification de licence est déjà faite par le middleware CheckLicense
        $license = License::with('software')->findOrFail($license_id);
        // Logique de téléchargement (ex: renvoyer un fichier)
        return response()->json(['message' => 'Téléchargement autorisé pour ' . $license->software->nom]);
    }

    public function verifyKey(Request $request)
    {
        $request->validate(['key' => 'required|string']);
        $user = Auth::user();
        $client = $user->client;

        // Retrouver la facture d'abonnement payée avec cette clé, appartenant au client
        $invoice = Invoice::where('cle_acces', $request->key)
            ->where('type', 'abonnement')
            ->where('statut', 'paye')
            ->where('client_id', $client->id)
            ->with('subscription.license.software')
            ->first();

        if (!$invoice || !$invoice->subscription || !$invoice->subscription->license) {
            return response()->json(['message' => 'Clé invalide ou expirée.'], 404);
        }

        $subscription = $invoice->subscription;

        if ($subscription->statut !== 'active' || Carbon::now()->gt($subscription->date_fin)) {
            return response()->json(['message' => 'Votre abonnement a expiré.'], 403);
        }

        $software = $subscription->license->software;
        if (!$software->url) {
            return response()->json(['message' => 'Aucune URL définie pour ce logiciel.'], 404);
        }

        $subscription->license->last_accessed_at = now();
        $subscription->license->save();

        return response()->json(['url' => $software->url]);
    }


}
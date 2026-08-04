<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Software;
use App\Models\License;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Cloudinary\Cloudinary;

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
        // ... (votre code existant pour l'upload Cloudinary et la création)
    }

    public function update(Request $request, Software $software)
    {
        // ... (votre code existant pour la modification)
    }

    public function destroy(Request $request, Software $software)
    {
        // ... (votre code existant pour la suppression)
    }

    public function access($license_id)
    {
        // ... (votre code existant)
    }

    public function download($license_id)
    {
        // ... (votre code existant)
    }

    public function verifyKey(Request $request)
    {
        $request->validate(['key' => 'required|string']);
        $user = Auth::user();
        $client = $user->client;

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
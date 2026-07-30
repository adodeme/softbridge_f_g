<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\License;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function index()
    {
        return response()->json(Auth::user()->client->subscriptions()->with('license.software')->get());
    }

    public function store(Request $request)
    {
        $request->validate(['license_id' => 'required|exists:licenses,id']);

        $client = Auth::user()->client;
        $license = License::findOrFail($request->license_id);

        $subscription = Subscription::create([
            'client_id' => $client->id,
            'license_id' => $license->id,
            'date_debut' => now(),
            'date_fin' => now()->addDays($license->duree),
            'statut' => 'active'
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'numero' => 'FAC-ABO-' . uniqid(),
            'date_creation' => now(),
            'montant' => $license->prix,
            'statut' => 'impaye',
            'type' => 'abonnement'
        ]);

        return response()->json([
            'subscription' => $subscription,
            'invoice' => $invoice
        ], 201);
    }
}
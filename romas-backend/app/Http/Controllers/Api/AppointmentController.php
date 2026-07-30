<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class AppointmentController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        // Si l'utilisateur est un client, on ne renvoie que ses propres rendez-vous
        if ($user->role === 'client') {
            return response()->json(
                Appointment::where('user_id', $user->id)
                    ->with('user')
                    ->orderBy('date', 'desc')
                    ->get()
            );
        }
        // Si c'est un Chef de Projet ou Admin, on renvoie TOUS les rendez-vous
        return response()->json(Appointment::with('user')->orderBy('date', 'desc')->get());
    }

    public function show($id)
    {
        return response()->json(Appointment::with('user')->findOrFail($id));
    }

    public function store(Request $request)
    {
        // 1. Validation des champs
        $request->validate([
            'date' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'duree' => 'required|integer|min:30',
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email',
            'telephone' => 'required|string'
        ]);

        $date = $request->date;
        $start = Carbon::parse($request->heure_debut);
        $end = $start->copy()->addMinutes($request->duree);

        // 2. Vérification des jours ouvrables et horaires de travail
        if (Carbon::parse($date)->isWeekend()) {
            return response()->json(['error' => 'L\'entreprise est fermée le week-end.'], 422);
        }
        if ($start->lt(Carbon::parse('08:00')) || $end->gt(Carbon::parse('18:00'))) {
            return response()->json(['error' => 'Les rendez-vous sont entre 08h00 et 18h00.'], 422);
        }
        if ($start->between(Carbon::parse('13:00'), Carbon::parse('15:00')) || $end->between(Carbon::parse('13:00'), Carbon::parse('15:00'))) {
            return response()->json(['error' => 'L\'entreprise est en pause entre 13h00 et 15h00.'], 422);
        }

        // 3. Vérification des conflits (créneau déjà pris)
        $conflict = Appointment::where('date', $date)
            ->where('heure_debut', '<', $end->format('H:i'))
            ->whereRaw('ADDTIME(heure_debut, SEC_TO_TIME(duree*60)) > ?', [$start->format('H:i')])
            ->exists();

        if ($conflict) {
            return response()->json(['error' => 'Ce créneau horaire est déjà pris.'], 422);
        }

        // 4. Création du rendez-vous
        // Authentification optionnelle via Sanctum
        $user = Auth::guard('sanctum')->user();
        $appointment = Appointment::create([
            'user_id' => $user->id ?? null,
            'date' => $date,
            'heure_debut' => $start,
            'duree' => $request->duree,
            'statut' => 'valide',
            'guest_nom' => $request->nom,
            'guest_prenom' => $request->prenom,
            'guest_email' => $request->email,
            'guest_telephone' => $request->telephone
        ]);

        // 5. Notifications
        // Notification au(x) chef(s) de projet
        foreach (User::where('role', 'chef_projet')->get() as $chef) {
            Notification::create([
                'user_id' => $chef->id,
                'message' => "Nouveau rendez-vous de {$request->prenom} {$request->nom} le {$date}.",
                'date_envoi' => now(),
                'lu' => false
            ]);
        }

        // Notification au client s'il est connecté
        if ($appointment->user_id) {
            Notification::create([
                'user_id' => $appointment->user_id,
                'message' => "Votre rendez-vous du {$date} à {$start->format('H:i')} a été enregistré avec succès.",
                'date_envoi' => now(),
                'lu' => false
            ]);
        }

        return response()->json(['appointment' => $appointment], 201);
    }

    public function downloadPdf($id)
    {
        $appointment = Appointment::with('user')->findOrFail($id);
        $pdf = Pdf::loadView('pdf.appointment', ['appointment' => $appointment]);
        return $pdf->download('rdv_' . $id . '.pdf');
    }
}
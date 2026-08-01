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
        if ($user->role === 'client') {
            return response()->json(
                Appointment::where('user_id', $user->id)
                    ->with('user')
                    ->orderBy('date', 'desc')
                    ->get()
            );
        }
        return response()->json(Appointment::with('user')->orderBy('date', 'desc')->get());
    }

    public function show($id)
    {
        return response()->json(Appointment::with('user')->findOrFail($id));
    }

    public function store(Request $request)
    {
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

        if (Carbon::parse($date)->isWeekend()) {
            return response()->json(['error' => 'L\'entreprise est fermée le week-end.'], 422);
        }
        if ($start->lt(Carbon::parse('08:00')) || $end->gt(Carbon::parse('18:00'))) {
            return response()->json(['error' => 'Les rendez-vous sont entre 08h00 et 18h00.'], 422);
        }
        if ($start->between(Carbon::parse('13:00'), Carbon::parse('15:00')) || $end->between(Carbon::parse('13:00'), Carbon::parse('15:00'))) {
            return response()->json(['error' => 'L\'entreprise est en pause entre 13h00 et 15h00.'], 422);
        }

        // Vérification de conflit compatible PostgreSQL
        $conflict = Appointment::where('date', $date)
            ->where('heure_debut', '<', $end->format('H:i'))
            ->whereRaw("(heure_debut + (duree * interval '1 minute')) > ?", [$start->format('H:i')])
            ->exists();

        if ($conflict) {
            return response()->json(['error' => 'Ce créneau horaire est déjà pris.'], 422);
        }

        $appointment = Appointment::create([
            'user_id' => Auth::guard('sanctum')->id(),
            'date' => $date,
            'heure_debut' => $start->format('H:i'),
            'duree' => $request->duree,
            'statut' => 'valide',
            'guest_nom' => $request->nom,
            'guest_prenom' => $request->prenom,
            'guest_email' => $request->email,
            'guest_telephone' => $request->telephone
        ]);

        foreach (User::where('role', 'chef_projet')->get() as $chef) {
            Notification::create([
                'user_id' => $chef->id,
                'message' => "Nouveau rendez-vous de {$request->prenom} {$request->nom} le {$date}.",
                'date_envoi' => now(),
                'lu' => false
            ]);
        }

        if ($appointment->user_id) {
            Notification::create([
                'user_id' => $appointment->user_id,
                'message' => "Votre rendez-vous du {$date} à {$start->format('H:i')} a été enregistré.",
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
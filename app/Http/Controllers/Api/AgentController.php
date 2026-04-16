<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gare;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Voyage;
use App\Models\Trajet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Barryvdh\DomPDF\Facade\Pdf;

class AgentController extends Controller
{
    public function getDashboardStats()
    {
        $user = Auth::user();
        if (!$user || !$user->gare_id) {
            return response()->json([
                'statut' => false,
                'message' => 'Gare non assignée'
            ], 404);
        }

        $gareId = $user->gare_id;

        $stats = [
            'sales_today' => Reservation::where('gare_id', $gareId)
                ->whereDate('created_at', now()->toDateString())
                ->where('statut', 'validee')
                ->count(),
            'active_reservations' => Reservation::where('gare_id', $gareId)
                ->where('statut', 'en attente')
                ->count(),
            'revenue_today' => (float) Reservation::where('gare_id', $gareId)
                ->whereDate('created_at', now()->toDateString())
                ->where('statut', 'validee')
                ->sum('prix'),
            'pending_validations' => Reservation::where('gare_id', $gareId)
                ->where('statut', 'en attente')
                ->count(),
        ];

        return response()->json([
            'statut' => true,
            'data' => $stats
        ]);
    }

    public function getRoutes()
    {
        $user = Auth::user();
        $gareId = $user->gare_id;
        
        if (!$gareId) {
            return response()->json([
                'statut' => false,
                'message' => 'Gare non assignée'
            ], 404);
        }

        $routes = Trajet::with(['gareDepart', 'gareArrivee'])
            ->where('depart_id', $gareId)
            ->get();

        return response()->json([
            'statut' => true,
            'data' => $routes
        ]);
    }

    public function getVoyages()
    {
        $user = Auth::user();
        $gareId = $user->gare_id;

        if (!$gareId) {
            return response()->json([
                'statut' => false,
                'message' => 'Gare non assignée'
            ], 404);
        }

        $voyages = Voyage::with(['trajet.gareDepart', 'trajet.gareArrivee', 'bus', 'chauffeur'])
            ->where('gare_id', $gareId)
            ->orderBy('date_depart', 'asc')
            ->get();

        return response()->json([
            'statut' => true,
            'data' => $voyages
        ]);
    }

    public function getVoyagesByRoute(Request $request)
    {
        $request->validate([
            'route_id' => 'required|exists:trajets,id',
            'date' => 'required|date',
        ]);

        $voyages = Voyage::with(['trajet.gareDepart', 'trajet.gareArrivee', 'bus', 'chauffeur'])
            ->where('trajet_id', $request->route_id)
            ->whereDate('date_depart', $request->date)
            ->where('statut', '!=', 'annule')
            ->get();

        return response()->json([
            'statut' => true,
            'data' => $voyages
        ]);
    }

    public function searchClients(Request $request)
    {
        $query = $request->query('query');
        if (empty($query)) {
            return response()->json([
                'statut' => true,
                'data' => []
            ]);
        }

        $clients = User::where('role_user', 'CLIENT')
            ->where(function($q) use ($query) {
                $q->where('nom', 'like', "%$query%")
                  ->orWhere('prenom', 'like', "%$query%")
                  ->orWhere('telephone', 'like', "%$query%")
                  ->orWhere('num_cni', 'like', "%$query%");
            })
            ->limit(10)
            ->get();

        return response()->json([
            'statut' => true,
            'data' => $clients
        ]);
    }

    public function createClient(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'telephone' => 'required|string|unique:users',
            'email' => 'nullable|email|unique:users',
            'sexe' => 'required|string',
            'num_cni' => 'required|string|unique:users',
            'date_naissance' => 'required|date',
        ]);

        $client = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'telephone' => $request->telephone,
            'sexe' => $request->sexe,
            'email' => $request->email ?? $request->telephone . '@gmail.com',
            'num_cni' => $request->num_cni,
            'date_naissance' => $request->date_naissance,
            'role_user' => 'CLIENT',
            'password' => Hash::make($request->telephone),
            'statut' => 'approuve'
        ]);

        return response()->json([
            'statut' => true,
            'data' => $client
        ], 201);
    }

    public function validateTicket(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $reservation = Reservation::with(['user', 'voyage.trajet.gareDepart', 'voyage.trajet.gareArrivee'])
            ->where('num_reservation', $request->code)
            ->first();

        if (!$reservation) {
            return response()->json([
                'statut' => false,
                'message' => 'Ticket invalide ou non trouvé'
            ], 404);
        }

        if ($reservation->statut === 'annule') {
            return response()->json([
                'statut' => false,
                'message' => 'Ticket déjà annulé'
            ], 422);
        }

        if ($reservation->statut === 'en attente') {
            $reservation->update(['statut' => 'validee']);
        }

        return response()->json([
            'statut' => true,
            'message' => 'Ticket valide' . ($reservation->statut === 'validee' ? ' et désormais validé' : ''),
            'data' => $reservation
        ]);
    }

    public function getAvailableSeats($id)
    {
        $voyage = Voyage::with('bus')->find($id);
        if (!$voyage || !$voyage->bus) {
            return response()->json(['statut' => false, 'message' => 'Voyage ou bus introuvable'], 404);
        }

        $totalSeats = $voyage->bus->nb_places;
        $occupiedSeats = Reservation::where('voyage_id', $id)
            ->where('statut', '!=', 'annule')
            ->pluck('place')
            ->toArray();

        $availableSeats = [];
        for ($i = 1; $i <= $totalSeats; $i++) {
            if (!in_array($i, $occupiedSeats)) {
                $availableSeats[] = (string) $i;
            }
        }

        return response()->json([
            'statut' => true,
            'data' => $availableSeats
        ]);
    }

    public function generateTicket($id, Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->gare_id) {
            return response()->json(['message' => 'Gare non assignée'], 404);
        }

        $query = Reservation::with([
            'user',
            'voyage.bus',
            'voyage.trajet.gareDepart',
            'voyage.trajet.gareArrivee',
            'gare.agence',
            'paiements'
        ])->where('id', $id);

        if ($user->role_user === 'AGENT') {
            $query->where('gare_id', $user->gare_id);
        } elseif ($user->role_user === 'CHEF_AGENCE') {
            $gare = Gare::find($user->gare_id);
            if ($gare && $gare->agence_id) {
                $gareIds = Gare::where('agence_id', $gare->agence_id)->pluck('id');
                $query->whereIn('gare_id', $gareIds);
            } else {
                $query->where('gare_id', $user->gare_id);
            }
        }

        $reservation = $query->first();

        if (!$reservation) {
            return response()->json(['message' => 'Réservation introuvable'], 404);
        }

        $gare    = $reservation->gare?->toArray() ?? [];
        $agence  = $reservation->gare?->agence?->toArray() ?? [];
        $voyage  = $reservation->voyage;
        $trajet  = $voyage?->trajet;
        $bus     = $voyage?->bus;
        $user    = $reservation->user;
        $paiement = $reservation->paiements?->first();

        $data = [
            'numReservation' => $reservation->num_reservation,
            'passager'       => trim(($user?->prenom ?? '') . ' ' . ($user?->nom ?? 'N/A')),
            'cni'            => $user?->num_cni ?? 'N/A',
            'telephone'      => $user?->telephone ?? 'N/A',
            'siege'          => $reservation->place ?? 'N/A',
            'prix'           => $reservation->prix ?? 0,
            'statut'         => $reservation->statut,
            'depart'         => $trajet?->gareDepart?->ville ?? 'N/A',
            'arrivee'        => $trajet?->gareArrivee?->ville ?? 'N/A',
            'dateDepart'     => $voyage?->date_depart
                                 ? \Carbon\Carbon::parse($voyage->date_depart)->translatedFormat('d F Y')
                                 : 'N/A',
            'heureDepart'    => $voyage?->heure_depart ?? '--:--',
            'immatriculation'=> $bus?->immatriculation ?? 'N/A',
            'typeTrajet'     => $trajet?->type_trajet ?? 'classique',
            'refPaiement'    => $paiement?->reference ?? null,
            'gare'           => $gare,
            'agence'         => $agence,
            'qrCodeUrl'      => 'https://api.qrserver.com/v1/create-qr-code/?size=80x80&data='
                                 . urlencode($reservation->num_reservation),
            'printDate'      => now()->format('d/m/Y H:i'),
        ];

        $pdf = PDF::loadView('tickets.reservation', $data);
        // CR80 ID card: 85.6mm × 54mm → in points (1mm = 2.8346pt)
        $pdf->setPaper([0, 0, 242.64, 153.07], 'landscape');
        $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->download('ticket-' . $reservation->num_reservation . '.pdf');
    }
}

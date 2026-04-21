<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReservationResource;
use App\Models\Agence;
use App\Models\Gare;
use App\Models\Reservation;
use App\Models\Voyage;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReservationController extends Controller
{
    /**
     * Génère un ticket PDF pour le client connecté
     */
    public function generateTicketForClient($id, Request $request)
    {
        $reservation = Reservation::with([
            'user',
            'voyage.bus',
            'voyage.trajet.villeDepart',
            'voyage.trajet.villeArrivee',
            'gare.agence',
            'paiements'
        ])
        ->where('id', $id)
        ->where('user_id', $request->user()->id)
        ->first();

        if (!$reservation) {
            return response()->json(['message' => 'Réservation introuvable ou accès non autorisé'], 404);
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
            'depart'         => $trajet?->villeDepart?->nom ?? 'N/A',
            'arrivee'        => $trajet?->villeArrivee?->nom ?? 'N/A',
            'dateDepart'     => $voyage?->date_depart
                                 ? Carbon::parse($voyage->date_depart)->translatedFormat('d F Y')
                                 : 'N/A',
            'heureDepart'    => $voyage?->heure_depart ?? '--:--', // Convenience field or derivation
            'immatriculation'=> $bus?->immatriculation ?? 'N/A',
            'typeTrajet'     => $trajet?->type_trajet ?? 'classique',
            'refPaiement'    => $paiement?->reference ?? null,
            'gare'           => $gare,
            'agence'         => $agence,
            'qrCodeUrl'      => 'https://api.qrserver.com/v1/create-qr-code/?size=80x80&data='
                                 . urlencode($reservation->num_reservation),
            'printDate'      => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('tickets.reservation', $data);
        // Format ID Card (CR80) landscape
        $pdf->setPaper([0, 0, 242.64, 153.07], 'landscape');
        $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->download('ticket-' . $reservation->num_reservation . '.pdf');
    }
    public function createReservation(Request $request)
    {
        $request->validate([
            'voyage_id' => 'required|exists:voyages,id',
            'gare_id' => 'required|exists:gares,id',
            'place' => 'required|integer|min:1',
            'payment_phone' => 'sometimes|string',
            'payment_method' => 'sometimes|string',
        ]);

        $voyage = Voyage::with('bus')->find($request->voyage_id);
        if (! $voyage) {
            return response()->json(['message' => 'Voyage non trouvé'], 404);
        }

        if (! $voyage->bus) {
            return response()->json(['message' => 'Aucun bus associé à ce voyage'], 422);
        }

        $isOccupied = Reservation::where('voyage_id', $request->voyage_id)
            ->where('place', $request->place)
            ->where('statut', '!=', 'annule')
            ->exists();

        if ($isOccupied) {
            return response()->json([
                'message' => 'Ce siège est déjà réservé',
            ], 422);
        }

        $reservation = Reservation::create([
            'num_reservation' => $this->generateReservationNumber(),
            'user_id' => $request->user()->id,
            'gare_id' => $request->gare_id,
            'voyage_id' => $request->voyage_id,
            'place' => $request->place,
            'prix' => $voyage->prix,
            'statut' => 'validee',
        ]);

        if ($request->has('payment_method') && $request->has('payment_phone')) {
            $refMethod = strtoupper(substr($request->payment_method, 0, 2));
        } else {
            $refMethod = strtoupper(substr('CASH', 0, 2));
        }
        Paiement::create([
            'reference' => $refMethod.'-'.$request->payment_phone ?? $request->user()->telephone.'-'.strtoupper(Str::random(6)),
            'reservation_id' => $reservation->id,
            'gare_id' => $request->gare_id,
            'montant' => $reservation->prix,
            'user_id' => $request->user()->id,
            'statut' => 'validee',
        ]);

        return response()->json($reservation, 201);
    }

    public function getMyReservations(Request $request)
    {
        $request->validate([
            'statut' => 'sometimes|in:en attente,validee,annule',
        ]);

        $reservations = Reservation::with([
            'voyage.bus',
            'voyage.trajet.villeDepart',
            'voyage.trajet.villeArrivee',
            'gare.agence',
            'paiements',
        ])
            ->where('user_id', $request->user()->id)
            ->when($request->query('statut'), fn ($query, $statut) => $query->where('statut', $statut))
            ->orderByDesc('created_at')
            ->get();

        return ReservationResource::collection($reservations);
    }

    public function getAgentReservations(Request $request)
    {
        $request->validate([
            'statut' => 'sometimes|in:en attente,validee,annule',
        ]);

        $user = $request->user();
        if (! $user || ! $user->gare_id) {
            return response()->json(['message' => 'Gare assignée introuvable'], 404);
        }

        $reservations = Reservation::with(['user', 'voyage.bus', 'gare', 'paiements'])
            ->where('gare_id', $user->gare_id)
            ->when($request->query('statut'), fn ($query, $statut) => $query->where('statut', $statut))
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'statut' => true,
            'data' => $reservations,
        ]);
    }

    public function getAgentReservationById($id, Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->gare_id) {
            return response()->json(['message' => 'Gare assignée introuvable'], 404);
        }

        $reservation = Reservation::with([
            'user',
            'voyage.bus',
            'voyage.trajet.villeDepart',
            'voyage.trajet.villeArrivee',
            'gare.agence',
            'paiements',
        ])
            ->where('id', $id)
            ->where('gare_id', $user->gare_id)
            ->first();

        if (! $reservation) {
            return response()->json(['message' => 'Réservation non trouvée'], 404);
        }

        return response()->json([
            'statut' => true,
            'data' => $reservation,
        ]);
    }

    public function createAgentReservation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'voyage_id' => 'required|exists:voyages,id',
            'gare_id' => 'required|exists:gares,id',
            'place' => 'required|integer|min:1',
            'payment_method' => 'nullable|string',
        ]);

        $agent = $request->user();
        if (! $agent || ! $agent->gare_id) {
            return response()->json(['message' => 'Gare assignée introuvable'], 404);
        }

        if ((int) $agent->gare_id !== (int) $request->gare_id) {
            return response()->json(['message' => 'Vous ne pouvez pas créer de réservation pour une autre gare'], 403);
        }

        $voyage = Voyage::with('bus')->find($request->voyage_id);
        if (! $voyage) {
            return response()->json(['message' => 'Voyage non trouvé'], 404);
        }
        if (! $voyage->bus) {
            return response()->json(['message' => 'Aucun bus associé à ce voyage'], 422);
        }

        $isOccupied = Reservation::where('voyage_id', $request->voyage_id)
            ->where('place', $request->place)
            ->where('statut', '!=', 'annule')
            ->exists();

        if ($isOccupied) {
            return response()->json(['message' => 'Ce siège est déjà réservé'], 422);
        }

        $reservation = Reservation::create([
            'num_reservation' => $this->generateReservationNumber(),
            'user_id' => $request->user_id,
            'gare_id' => $request->gare_id,
            'voyage_id' => $request->voyage_id,
            'place' => $request->place,
            'prix' => $voyage->prix,
            'statut' => 'validee',  // Agent reservations are immediately validated
        ]);

        // Automatically record the payment (cash at counter)
        $method = $request->payment_method ?? 'especes';
        Paiement::create([
            'reference' => strtoupper(substr($method, 0, 3)).'-'.$agent->id.'-'.strtoupper(Str::random(6)),
            'reservation_id' => $reservation->id,
            'gare_id' => $request->gare_id,
            'montant' => $voyage->prix,
            'statut' => 'validee',
        ]);

        return response()->json([
            'statut' => true,
            'data' => $reservation,
        ], 201);
    }

    public function cancelAgentReservation($id, Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->gare_id) {
            return response()->json(['message' => 'Gare assignée introuvable'], 404);
        }

        $reservation = Reservation::where('id', $id)
            ->where('gare_id', $user->gare_id)
            ->first();

        if (! $reservation) {
            return response()->json(['message' => 'Réservation non trouvée'], 404);
        }

        if ($reservation->statut === 'annule') {
            return response()->json(['message' => 'Réservation déjà annulée'], 422);
        }

        $reservation->update(['statut' => 'annule']);

       

        return response()->json([
            'statut' => true,
            'message' => 'Réservation annulée avec succès',
            'reservation' => $reservation,
        ]);
    }

    public function getChefAgenceReservations(Request $request)
    {
        $request->validate([
            'statut' => 'sometimes|in:en attente,validee,annule',
        ]);

        $user = $request->user();
        if (! $user || ! $user->gare_id) {
            return response()->json(['message' => 'Gare assignée introuvable'], 404);
        }

        $gare = Gare::find($user->gare_id);
        if (! $gare || ! $gare->agence_id) {
            return response()->json(['message' => 'Agence introuvable pour ce chef d agence'], 404);
        }

        $gareIds = $request->user()->gare_id;
        $reservations = Reservation::with(['user', 'voyage.bus', 'gare', 'paiements'])
            ->where('gare_id', $gareIds)
            ->when($request->query('statut'), fn ($query, $statut) => $query->where('statut', $statut))
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'statut' => true,
            'data' => $reservations,
        ]);
    }

    public function exportReservationsPdf(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->gare_id) {
            return response()->json(['message' => 'Gare assignée introuvable'], 404);
        }

        $gare = Gare::with('agence')->find($user->gare_id);
        if (! $gare || ! $gare->agence_id) {
            return response()->json(['message' => 'Agence introuvable pour ce chef d agence'], 404);
        }

        $reservations = Reservation::with(['user', 'voyage.bus', 'voyage.trajet', 'gare', 'paiements'])
            ->where('gare_id', $user->gare_id)
            ->orderByDesc('created_at')
            ->get();

        $pdf = Pdf::loadView('pdf.reservations', [
            'agenceName' => $gare->agence->nom ?? 'Agence',
            'gareName' => $gare->nom . ' - ' . ($gare->ville?->nom ?? ''),
            'reservations' => $reservations
        ]);

        return $pdf->download('reservations_agence_' . date('Y_m_d_H_i') . '.pdf');
    }

    public function getChefAgenceReservationById($id, Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->gare_id) {
            return response()->json(['message' => 'Gare assignée introuvable'], 404);
        }

        $gare = Gare::find($user->gare_id);
        if (! $gare || ! $gare->agence_id) {
            return response()->json(['message' => 'Agence introuvable pour ce chef d agence'], 404);
        }

        $gareIds = Gare::where('agence_id', $gare->agence_id)->pluck('id');
        $reservation = Reservation::with(['user', 'voyage.bus', 'gare', 'paiements'])
            ->where('id', $id)
            ->whereIn('gare_id', $gareIds)
            ->first();

        if (! $reservation) {
            return response()->json(['message' => 'Réservation non trouvée'], 404);
        }

        return response()->json($reservation);
    }

    public function createChefAgenceReservation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'voyage_id' => 'required|exists:voyages,id',
            'gare_id' => 'required|exists:gares,id',
            'place' => 'required|integer|min:1',
            'payment_method' => 'nullable|string',
        ]);

        $chef = $request->user();
        if (! $chef || ! $chef->gare_id) {
            return response()->json(['message' => 'Gare assignée introuvable'], 404);
        }

        $gareChef = Gare::find($chef->gare_id);
        if (! $gareChef || ! $gareChef->agence_id) {
            return response()->json(['message' => 'Agence introuvable pour ce chef d agence'], 404);
        }

        $allowedGareIds = Gare::where('agence_id', $gareChef->agence_id)->pluck('id');
        if (! $allowedGareIds->contains($request->gare_id)) {
            return response()->json(['message' => 'Vous ne pouvez pas créer de réservation pour cette gare'], 403);
        }

        $voyage = Voyage::with('bus')->find($request->voyage_id);
        if (! $voyage) {
            return response()->json(['message' => 'Voyage non trouvé'], 404);
        }
        if (! $voyage->bus) {
            return response()->json(['message' => 'Aucun bus associé à ce voyage'], 422);
        }

        $isOccupied = Reservation::where('voyage_id', $request->voyage_id)
            ->where('place', $request->place)
            ->where('statut', '!=', 'annule')
            ->exists();

        if ($isOccupied) {
            return response()->json(['message' => 'Ce siège est déjà réservé'], 422);
        }

        $reservation = Reservation::create([
            'num_reservation' => $this->generateReservationNumber(),
            'user_id' => $request->user_id,
            'gare_id' => $request->gare_id,
            'voyage_id' => $request->voyage_id,
            'place' => $request->place,
            'prix' => $voyage->prix,
            'statut' => 'validee',
        ]);

        $method = $request->payment_method ?? 'especes';
        Paiement::create([
            'reference' => strtoupper(substr($method, 0, 3)).'-'.$chef->id.'-'.strtoupper(Str::random(6)),
            'reservation_id' => $reservation->id,
            'gare_id' => $request->gare_id,
            'montant' => $voyage->prix,
            'statut' => 'validee',
        ]);

        return response()->json([
            'statut' => true,
            'data' => $reservation,
        ], 201);
    }

    public function cancelChefAgenceReservation($id, Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->gare_id) {
            return response()->json(['message' => 'Gare assignée introuvable'], 404);
        }

        $gare = Gare::find($user->gare_id);
        if (! $gare || ! $gare->agence_id) {
            return response()->json(['message' => 'Agence introuvable pour ce chef d agence'], 404);
        }

        $allowedGareIds = Gare::where('agence_id', $gare->agence_id)->pluck('id');
        $reservation = Reservation::where('id', $id)
            ->whereIn('gare_id', $allowedGareIds)
            ->first();

        if (! $reservation) {
            return response()->json(['message' => 'Réservation non trouvée'], 404);
        }

        if ($reservation->statut === 'annule') {
            return response()->json(['message' => 'Réservation déjà annulée'], 422);
        }

        $reservation->update(['statut' => 'annule']);

        // Libérer la place sur le voyage
        if ($reservation->voyage_id) {
            $voyage = Voyage::find($reservation->voyage_id);
        }

        return response()->json([
            'statut' => true,
            'message' => 'Réservation annulée avec succès',
            'reservation' => $reservation,
        ]);
    }

    public function getProprietaireReservations(Request $request)
    {
        $request->validate([
            'statut' => 'sometimes|in:en attente,validee,annule',
        ]);

        $user = $request->user();
        $agenceIds = Agence::where('proprietaire_id', $user->id)->pluck('id');
        if ($agenceIds->isEmpty()) {
            return response()->json(['message' => 'Aucune agence trouvée pour ce propriétaire'], 404);
        }

        $gareIds = Gare::whereIn('agence_id', $agenceIds)->pluck('id');
        $reservations = Reservation::with(['user', 'voyage.bus', 'gare', 'paiements'])
            ->whereIn('gare_id', $gareIds)
            ->when($request->query('statut'), fn ($query, $statut) => $query->where('statut', $statut))
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'statut' => true,
            'data' => $reservations,
        ]);
    }

    public function getProprietaireReservationById($id, Request $request)
    {
        $user = $request->user();
        $agenceIds = Agence::where('proprietaire_id', $user->id)->pluck('id');
        if ($agenceIds->isEmpty()) {
            return response()->json(['message' => 'Aucune agence trouvée pour ce propriétaire'], 404);
        }

        $gareIds = Gare::whereIn('agence_id', $agenceIds)->pluck('id');
        $reservation = Reservation::with(['user', 'voyage.bus', 'gare', 'paiements'])
            ->where('id', $id)
            ->whereIn('gare_id', $gareIds)
            ->first();

        if (! $reservation) {
            return response()->json(['message' => 'Réservation non trouvée'], 404);
        }

        return response()->json($reservation);
    }

    public function createProprietaireReservation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'voyage_id' => 'required|exists:voyages,id',
            'gare_id' => 'required|exists:gares,id',
            'place' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $agenceIds = Agence::where('proprietaire_id', $user->id)->pluck('id');
        if ($agenceIds->isEmpty()) {
            return response()->json(['message' => 'Aucune agence trouvée pour ce propriétaire'], 404);
        }

        $allowedGareIds = Gare::whereIn('agence_id', $agenceIds)->pluck('id');
        if (! $allowedGareIds->contains($request->gare_id)) {
            return response()->json(['message' => 'Vous ne pouvez pas créer de réservation pour cette gare'], 403);
        }

        $reservation = $this->buildReservation($request->user_id, $request->gare_id, $request->voyage_id, $request->place);

        return response()->json($reservation, 201);
    }

    public function cancelProprietaireReservation($id, Request $request)
    {
        $user = $request->user();
        $agenceIds = Agence::where('proprietaire_id', $user->id)->pluck('id');
        if ($agenceIds->isEmpty()) {
            return response()->json(['message' => 'Aucune agence trouvée pour ce propriétaire'], 404);
        }

        $allowedGareIds = Gare::whereIn('agence_id', $agenceIds)->pluck('id');
        $reservation = Reservation::where('id', $id)
            ->whereIn('gare_id', $allowedGareIds)
            ->first();

        if (! $reservation) {
            return response()->json(['message' => 'Réservation non trouvée'], 404);
        }

        if ($reservation->statut === 'annule') {
            return response()->json(['message' => 'Réservation déjà annulée'], 422);
        }

        $reservation->update(['statut' => 'annule']);

        // Libérer la place sur le voyage
        if ($reservation->voyage_id) {
            $voyage = Voyage::find($reservation->voyage_id);
        }

        return response()->json([
            'statut' => true,
            'message' => 'Réservation annulée avec succès',
            'reservation' => $reservation,
        ]);
    }

    public function getReservationById($id, Request $request)
    {
        $reservation = Reservation::with(['voyage.bus', 'gare', 'paiements'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $reservation) {
            return response()->json(['message' => 'Réservation non trouvée'], 404);
        }

        return response()->json($reservation);
    }

    public function getAllReservations(Request $request)
    {
        $request->validate([
            'statut' => 'sometimes|in:en attente,validee,annule',
        ]);

        $reservations = Reservation::with(['user', 'voyage.bus', 'gare', 'paiements'])
            ->when($request->query('statut'), fn ($query, $statut) => $query->where('statut', $statut))
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'statut' => true,
            'data' => $reservations,
        ]);
    }

    public function getReservationByIdAdmin($id)
    {
        $reservation = Reservation::with(['user', 'voyage.bus', 'gare', 'paiements'])
            ->find($id);

        if (! $reservation) {
            return response()->json(['message' => 'Réservation non trouvée'], 404);
        }

        return response()->json($reservation);
    }

    public function updateReservationStatus($id, Request $request)
    {
        $request->validate([
            'statut' => 'required|in:en attente,validee,annule',
        ]);

        $reservation = Reservation::find($id);
        if (! $reservation) {
            return response()->json(['message' => 'Réservation non trouvée'], 404);
        }

        $reservation->update(['statut' => $request->statut]);

        return response()->json([
            'message' => 'Statut de réservation mis à jour',
            'reservation' => $reservation,
        ]);
    }

    protected function buildReservation(int $userId, int $gareId, int $voyageId, int $place): Reservation
    {
        $voyage = Voyage::with('bus')->find($voyageId);
        if (! $voyage) {
            abort(404, 'Voyage non trouvé');
        }

        if (! $voyage->bus) {
            abort(422, 'Aucun bus associé à ce voyage');
        }

        $isOccupied = Reservation::where('voyage_id', $voyageId)
            ->where('place', $place)
            ->where('statut', '!=', 'annule')
            ->exists();

        if ($isOccupied) {
            abort(422, 'Ce siège est déjà réservé');
        }

        return Reservation::create([
            'num_reservation' => $this->generateReservationNumber(),
            'user_id' => $userId,
            'gare_id' => $gareId,
            'voyage_id' => $voyageId,
            'place' => $place,
            'prix' => $voyage->prix,
            'statut' => 'en attente',
        ]);
    }

    public function cancelReservation($id, Request $request)
    {
        $reservation = Reservation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $reservation) {
            return response()->json(['message' => 'Réservation non trouvée'], 404);
        }

        if ($reservation->statut === 'annule') {
            return response()->json(['message' => 'Réservation déjà annulée'], 422);
        }

        $reservation->update(['statut' => 'annule']);

        // Libérer la place sur le voyage
        if ($reservation->voyage_id) {
            $voyage = Voyage::find($reservation->voyage_id);
            if ($voyage) {
                // S'assurer de charger le bus pour les calculs de capacité si besoin, 
                // mais ici on incrémente juste le compteur global.
            }
        }

        return response()->json([
            'statut' => true,
            'message' => 'Réservation annulée avec succès',
            'reservation' => $reservation,
        ]);
    }

    public function storeClientReservation(Request $request)
    {
        $request->validate([
            'voyage_id' => 'required|exists:voyages,id',
            'place' => 'required|integer|min:1',
            'gare_id' => 'nullable|exists:gares,id',
        ]);

        $voyage = Voyage::with('bus')->find($request->voyage_id);
        
        // 1. Validation de la capacité du bus
        if (!$voyage->bus) {
            return response()->json(['message' => 'Aucun bus associé à ce voyage'], 422);
        }

        if ($request->place > $voyage->bus->nb_places) {
            return response()->json([
                'message' => "Le numéro de siège {$request->place} dépasse la capacité du bus ({$voyage->bus->nb_places})."
            ], 422);
        }

        // 2. Vérification de l'occupation
        $isOccupied = Reservation::where('voyage_id', $request->voyage_id)
            ->where('place', $request->place)
            ->where('statut', '!=', 'annule')
            ->exists();

        if ($isOccupied) {
            return response()->json(['message' => 'Ce siège est déjà réservé'], 422);
        }

        // 3. Création de la réservation (En attente)
        $reservation = Reservation::create([
            'num_reservation' => $this->generateReservationNumber(),
            'user_id' => $request->user()->id,
            'gare_id' => $request->gare_id ?? $voyage->gare_id, // Fallback to voyage gare
            'voyage_id' => $request->voyage_id,
            'place' => $request->place,
            'prix' => $voyage->prix,
            'statut' => 'en attente',
        ]);

        return response()->json([
            'statut' => true,
            'message' => 'Réservation effectuée avec succès. Elle est en attente de paiement.',
            'data' => $reservation,
        ], 201);
    }

    protected function generateReservationNumber(): string
    {
        do {
            $number = 'RES-'.strtoupper(Str::random(8));
        } while (Reservation::where('num_reservation', $number)->exists());

        return $number;
    }
}

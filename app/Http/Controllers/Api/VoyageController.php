<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VoyageRessource;
use App\Models\Agence;
use App\Models\Bus;
use App\Models\Gare;
use App\Models\Reservation;
use App\Models\Trajet;
use App\Models\User;
use App\Models\Voyage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VoyageController extends Controller
{
    public function createVoyage(Request $request)
    {
        $request->validate([
            'trajet_id' => 'required|exists:trajets,id',
            'bus_id' => 'required|exists:buses,id',
            'chauffeur_id' => 'required|exists:users,id',
            'prix' => 'required|numeric',
            'statut' => 'required|in:en attente,en cours,annule,termine',
            'date_depart' => 'nullable|date',
            'date_arrivee' => 'nullable|date',
            'duree_heure' => 'required|integer',

        ]);

        $user = Auth::user();
        if (! $user || ! $user->gare_id) {
            return response()->json(['message' => 'Utilisateur introuvable ou non autorisé.'], 403);
        }

        $agence = $this->getChefAgency(['gares']);
        if (! $agence) {
            return response()->json(['message' => 'Agence introuvable.'], 404);
        }

        $gareIds = $agence->gares->pluck('id')->toArray();
        $bus = Bus::find($request->bus_id);
        $chauffeur = User::find($request->chauffeur_id);
        $trajet = Trajet::find($request->trajet_id);

        if (! $bus || ! in_array($bus->gare_id, $gareIds)) {
            return response()->json(['message' => 'Bus non autorisé.'], 403);
        }

        if (! $chauffeur || ! in_array($chauffeur->gare_id, $gareIds)) {
            return response()->json(['message' => 'Chauffeur non autorisé.'], 403);
        }

        if (! $trajet || ! in_array($trajet->gare_id, $gareIds)) {
            return response()->json(['message' => 'Trajet non autorisé.'], 403);
        }

        $voyage = Voyage::create([
            'num_voyage' => $this->generateVoyageNumber(),
            'trajet_id' => $request->trajet_id,
            'bus_id' => $request->bus_id,
            'chauffeur_id' => $request->chauffeur_id,
            'prix' => $request->prix,
            'statut' => $request->statut,
            'gare_id' => $user->gare_id,
            'date_depart' => $request->date_depart,
        ]);

        $chauffeur->update(['statut' => 'inactif']);
        $bus->update(['statut' => 'indisponible']);

        return response()->json([
            'id' => $voyage->id,
            'num_voyage' => $voyage->num_voyage,
            'date_depart' => $voyage->date_depart,
            'heure_depart' => $voyage->date_depart ? date('H:i', strtotime($voyage->date_depart)) : null,
            'ville_depart' => $trajet->villeDepart?->nom ?? 'Inconnu',
            'ville_arrivee' => $trajet->villeArrivee?->nom ?? 'Inconnu',
            'vehicule_immatriculation' => $bus->immatriculation,
            'statut' => $voyage->statut,
            'chauffeur' => $chauffeur,
        ], 201);
    }

    public function getMyAgenceVoyages(Request $request)
    {
        $agence = $this->getChefAgency(['gares']);

        if (! $agence) {
            return response()->json(['message' => 'Agence non trouvée pour ce chef d agence'], 404);
        }

        $gareIds = $request->user()->gare_id;
        $voyages = Voyage::where('gare_id', $gareIds)
            ->with(['trajet.villeDepart', 'trajet.villeArrivee', 'bus', 'chauffeur'])
            ->get()
            ->map(function ($voyage) {
                $depart = $voyage->trajet?->villeDepart;
                $arrivee = $voyage->trajet?->villeArrivee;

                return [
                    'id' => $voyage->id,
                    'num_voyage' => $voyage->num_voyage,
                    'date_depart' => $voyage->date_depart,
                    'heure_depart' => $voyage->date_depart ? date('H:i', strtotime($voyage->date_depart)) : null,
                    'ville_depart' => $depart?->nom ?? 'Inconnu',
                    'ville_arrivee' => $arrivee?->nom ?? 'Inconnu',
                    'vehicule_immatriculation' => $voyage->bus?->immatriculation,
                    'code_bus' => $voyage->bus?->code_bus,
                    'statut' => $voyage->statut,
                    'chauffeur' => $voyage->chauffeur,
                ];
            });

        return response()->json([
            'statut' => true,
            'data' => $voyages,
        ]);
    }

    public function exportVoyagesPdf(Request $request)
    {
        $agence = $this->getChefAgency(['gares']);
        if (! $agence) {
            return response()->json(['message' => 'Agence non trouvée'], 404);
        }

        $gareIds = $request->user()->gare_id;
        $gare = Gare::find($gareIds);

        $voyages = Voyage::where('gare_id', $gareIds)
            ->with(['trajet.villeDepart', 'trajet.villeArrivee', 'bus', 'chauffeur'])
            ->get();

        $pdf = Pdf::loadView('pdf.voyages', [
            'agenceName' => $agence->nom,
            'gareName' => $gare ? $gare->nom.' - '.($gare->ville?->nom ?? '') : 'Inconnue',
            'voyages' => $voyages,
        ]);

        return $pdf->download('voyages_agence_'.date('Y_m_d_H_i').'.pdf');
    }

    public function getVoyagesForChauffeur(Request $request)
    {
        $chauffeurId = $request->user()->id;
        $voyages = Voyage::with([
            'trajet.villeDepart:id,nom,region',
            'trajet.villeArrivee:id,nom,region',
            'trajet',
            'bus',
            'chauffeur',
            'gare',
        ])->where('chauffeur_id', $chauffeurId)->get();

        return response()->json([
            'statut' => true,
            'data' => $voyages,
        ]);
    }

    public function deleteVoyage($id)
    {
        $voyage = Voyage::find($id);
        if (! $voyage) {
            return response()->json(['message' => 'Voyage non trouvé'], 404);
        }

        $voyage->delete();

        return response()->json(['message' => 'Voyage supprimé avec succès']);
    }

    public function getMyAgencesVoyages()
    {
        $user = Auth::user();
        $agencyIds = $this->getOwnerAgencyIds($user->id);
        $gareIds = Gare::whereIn('agence_id', $agencyIds)->pluck('id');
        $voyages = Voyage::whereIn('gare_id', $gareIds)
            ->with(['trajet.villeDepart', 'trajet.villeArrivee', 'bus', 'chauffeur'])
            ->get()->map(function ($voyage) {
                return [
                    'id' => $voyage->id,
                    'num_voyage' => $voyage->num_voyage,
                    'date_depart' => $voyage->date_depart,
                    'heure_depart' => $voyage->date_depart ? date('H:i', strtotime($voyage->date_depart)) : null,
                    'ville_depart' => $voyage->trajet?->villeDepart?->nom ?? 'Inconnu',
                    'ville_arrivee' => $voyage->trajet?->villeArrivee?->nom ?? 'Inconnu',
                    'vehicule_immatriculation' => $voyage->bus?->immatriculation,
                    'code_bus' => $voyage->bus?->code_bus,
                    'statut' => $voyage->statut,
                    'chauffeur' => $voyage->chauffeur,
                    'prix' => $voyage->trajet?->prix,
                    'places_disponibles' => $voyage->bus?->nb_places - Reservation::where('voyage_id',$voyage->id)->where('statut','validee')->count(),
                    'bus' => $voyage->bus,
                ];
            });

        return response()->json([
            'statut' => true,
            'data' => $voyages,
        ]);
    }

    private function getOwnerAgencyIds($id)
    {
        return Agence::where('proprietaire_id', $id)->pluck('id');
    }

    public function updateVoyage($id, Request $request)
    {
        $request->validate([
            'trajet_id' => 'sometimes|required|exists:trajets,id',
            'bus_id' => 'sometimes|required|exists:buses,id',
            'chauffeur_id' => 'sometimes|required|exists:users,id',
            'prix' => 'sometimes|required|numeric',
            'statut' => 'sometimes|required|in:en attente,en cours,annule,termine',
            'date_depart' => 'sometimes|nullable|date',
            'date_arrivee' => 'sometimes|nullable|date',

        ]);

        $voyage = Voyage::find($id);
        if (! $voyage) {
            return response()->json(['message' => 'Voyage non trouvé'], 404);
        }

        $voyage->update($request->all());
        $voyage->load(['trajet.villeDepart', 'trajet.villeArrivee', 'bus', 'chauffeur']);

        return response()->json([
            'id' => $voyage->id,
            'num_voyage' => $voyage->num_voyage,
            'date_depart' => $voyage->date_depart,
            'heure_depart' => $voyage->date_depart ? date('H:i', strtotime($voyage->date_depart)) : null,
            'ville_depart' => $voyage->trajet?->villeDepart?->nom ?? 'Inconnu',
            'ville_arrivee' => $voyage->trajet?->villeArrivee?->nom ?? 'Inconnu',
            'vehicule_immatriculation' => $voyage->bus?->immatriculation,
            'code_bus' => $voyage->bus?->code_bus,
            'statut' => $voyage->statut,
            'chauffeur' => $voyage->chauffeur,
            'places_disponibles' => $voyage->places_disponibles,
            'bus' => $voyage->bus,
        ]);
    }

    public function getPromoTrips()
    {
        $promoTrips = Voyage::select('id', 'num_voyage', 'statut', 'trajet_id', 'bus_id', 'gare_id', 'chauffeur_id', 'date_depart', 'promo')
            ->with([
                'bus:id,immatriculation,nb_places,modele,code_bus',
                'trajet:id,ville_depart,ville_arrive,prix',
                'trajet.villeDepart:id,nom',
                'trajet.villeArrivee:id,nom',
                'chauffeur:id,nom,telephone',
                'gare:id,nom,ville_id,adresse,agence_id',
                'gare.ville:id,nom',
                'gare.agence:id,nom',
            ])->where('promo', false)
            ->get();
        $v = VoyageRessource::collection($promoTrips);

        return response()->json([
            'statut' => true,
            'data' => $v,
        ]);
    }

    public function getOccupiedSeats($id)
    {
        $occupied = Reservation::where('voyage_id', $id)
            ->where('statut', '!=', 'annule')
            ->pluck('place')
            ->map(fn ($p) => (string) $p);

        return response()->json([
            'statut' => true,
            'occupied' => $occupied,
        ]);
    }

    public function getScheduledVoyages()
    {
        $voyages = Voyage::select('id', 'num_voyage', 'statut', 'trajet_id', 'bus_id', 'gare_id', 'chauffeur_id', 'date_depart', 'promo')
            ->with([
                'bus:id,immatriculation,nb_places,modele,code_bus',
                'trajet:id,ville_depart,ville_arrive,prix',
                'trajet.villeDepart:id,nom',
                'trajet.villeArrivee:id,nom',
                'chauffeur:id,nom,telephone',
                'gare:id,nom,ville_id,adresse,agence_id',
                'gare.ville:id,nom',
                'gare.agence:id,nom',
            ])
            ->where('date_depart', '>=', now()->toDateString())
            ->orderBy('date_depart', 'asc')
            ->take(50)
            ->get();

        $v = VoyageRessource::collection($voyages);

        return response()->json([
            'statut' => true,
            'data' => $v,
        ]);
    }

    public function getVoyageByIdForClient($id)
    {
        $voyage = Voyage::with([
            'bus:id,immatriculation,nb_places,modele,code_bus',
            'trajet:id,ville_depart,ville_arrive,prix',
            'trajet.villeDepart:id,nom',
            'trajet.villeArrivee:id,nom',
            'chauffeur:id,nom,telephone',
            'gare:id,nom,ville_id,adresse,agence_id',
            'gare.ville:id,nom',
            'gare.agence:id,nom',
        ])->find($id);

        if (! $voyage) {
            return response()->json(['message' => 'Voyage non trouvé'], 404);
        }

        return response()->json([
            'statut' => true,
            'data' => new VoyageRessource($voyage),
        ]);
    }

    private function getChefAgency(array $with = [])
    {
        $user = Auth::user();
        if (! $user || ! $user->gare_id) {
            return null;
        }

        $gare = Gare::find($user->gare_id);
        if (! $gare || ! $gare->agence_id) {
            return null;
        }

        return Agence::with($with)->find($gare->agence_id);
    }

    protected function generateVoyageNumber(): string
    {
        do {
            $number = 'VOY-'.strtoupper(Str::random(8));
        } while (Voyage::where('num_voyage', $number)->exists());

        return $number;
    }
}

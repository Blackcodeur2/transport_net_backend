<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrajetResource;
use App\Models\Trajet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrajetController extends Controller
{
    public function createTrajet(Request $request)
    {
        // Validation des données d'entrée
        $request->validate([
            'ville_depart' => 'required|exists:villes,id',
            'ville_arrive' => 'required|exists:villes,id',
            'type_trajet' => 'required',
            'prix' => 'required|numeric',
            'distance_km' => 'sometimes|numeric',
            'gare_id' => 'required|exists:gares,id',
        ]);
        // Création du trajet
        $trajet = Trajet::create($request->only([
            'gare_id',
            'ville_depart',
            'ville_arrive',
            'type_trajet',
            'prix',
            'distance_km',
        ]));

        return response()->json($trajet, 201);
    }

    public function getTrajets()
    {
        $trajets = Trajet::with(['villeDepart', 'villeArrivee', 'gare'])->get();
        return response()->json($trajets);
    }

    public function getTrajetById($id)
    {
        $trajet = Trajet::with(['villeDepart', 'villeArrivee', 'gare'])->find($id);
        if (!$trajet) {
            return response()->json(['message' => 'Trajet non trouvé'], 404);
        }
        return response()->json($trajet);
    }


    public function getTrajetsPopulaires()
    {
        $trajets = Trajet::select('trajets.*', DB::raw('COUNT(reservations.id) as total_reservations'))
            ->join('voyages', 'voyages.trajet_id', '=', 'trajets.id')
            ->join('reservations', 'reservations.voyage_id', '=', 'voyages.id')
            ->with(['villeDepart:id,nom', 'villeArrivee:id,nom'])
            ->groupBy('trajets.id')
            ->orderByDesc('total_reservations')
            ->take(5)
            ->get();

        return response()->json([
            'status' => true,
            'data' => TrajetResource::collection($trajets)
        ]);
    }

    public function deleteTrajet($id)
    {
        $trajet = Trajet::find($id);
        if (!$trajet) {
            return response()->json(['message' => 'Trajet non trouvé'], 404);
        }

        $trajet->delete();
        return response()->json(['message' => 'Trajet supprimé avec succès']);
    }

    public function updateTrajet($id, Request $request)
    {
        $request->validate([
            'ville_depart' => 'sometimes|required|exists:villes,id',
            'ville_arrive' => 'sometimes|required|exists:villes,id',
            'gare_id' => 'sometimes|required|exists:gares,id',
            'prix' => 'sometimes|required|numeric',
            'distance_km' => 'sometimes|integer',
        ]);

        $trajet = Trajet::find($id);
        if (!$trajet) {
            return response()->json(['message' => 'Trajet non trouvé'], 404);
        }

        $trajet->update($request->only([
            'gare_id',
            'ville_depart',
            'ville_arrive',
            'type_trajet',
            'prix',
            'distance_km',
            'duree_heure',
            'is_active',
        ]));
        return response()->json($trajet);
    }
}

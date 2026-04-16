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
            'depart' => 'required|exists:gares,id',
            'arrivee' => 'required|exists:gares,id',
            'type_trajet' => 'required',
            'prix' => 'required|numeric',
            'gare_id' => 'required|exist:gares,id',
            'heure_depart' => 'required|date_format:H:i',
            'heure_arrivee' => 'required|date_format:H:i|after:heure_depart',
        ]);
        // Création du trajet
        $trajet = Trajet::create($request->all());

        return response()->json($trajet, 201);
    }

    public function getTrajets()
    {
        $trajets = Trajet::with(['gareDepart', 'gareArrivee', 'bus'])->get();
        return response()->json($trajets);
    }

    public function getTrajetById($id)
    {
        $trajet = Trajet::with(['gareDepart', 'gareArrivee', 'bus'])->find($id);
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
            ->with(['gareDepart:id,nom,ville', 'gareArrivee:id,nom,ville'])
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
            'gare_depart_id' => 'sometimes|required|exists:gares,id',
            'gare_arrivee_id' => 'sometimes|required|exists:gares,id',
            'bus_id' => 'sometimes|required|exists:buses,id',
            'prix' => 'sometimes|required|numeric',
            'heure_depart' => 'sometimes|required|date_format:H:i',
            'heure_arrivee' => 'sometimes|required|date_format:H:i|after:heure_depart',
        ]);

        $trajet = Trajet::find($id);
        if (!$trajet) {
            return response()->json(['message' => 'Trajet non trouvé'], 404);
        }

        $trajet->update($request->all());
        return response()->json($trajet);
    }
}

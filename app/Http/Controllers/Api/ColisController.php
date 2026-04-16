<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Colis;
use App\Http\Requests\StoreColisRequest;
use App\Http\Requests\UpdateColisRequest;
use Illuminate\Http\Request;

class ColisController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function getMyColis(Request $request)
    {
        $colis = Colis::where('user_id', $request->user()->id)->get();
        return response()->json($colis);
    }

    public function hideMyColis($id)
    {
        $colis = Colis::find($id);
        if (!$colis) {
            return response()->json(['message' => 'Colis non trouvé'], 404);
        }

        $colis->update(['visible' => false]);
        return response()->json(['message' => 'Colis supprimé avec succès']);
    }

    /**
     * Agent Colis Operations
     */
    public function getAgentColis(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->gare_id) {
            return response()->json(['statut' => false, 'message' => 'Gare non assignée'], 404);
        }

        $colis = Colis::with(['user:id,nom,prenom,telephone,num_cni', 'gareProvenance:id,nom,ville', 'gareDestination:id,nom,ville', 'voyage.bus'])
            ->where('provenance', $user->gare_id)
            ->orWhere('destination', $user->gare_id)
            ->latest()
            ->get();

        return response()->json(['statut' => true, 'data' => $colis]);
    }

    public function createAgentColis(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'nom_colis' => 'required|string|max:255',
            'tel_destinataire' => 'required|string',
            'nom_destinataire' => 'required|string',
            'destination' => 'required|exists:gares,id',
            'voyage_id' => 'required|exists:voyages,id',
            'prix' => 'nullable|numeric|min:0',
            'poids' => 'nullable|numeric|min:0'
        ]);

        $user = $request->user();
        if (!$user || !$user->gare_id) {
            return response()->json(['statut' => false, 'message' => 'Gare non assignée'], 404);
        }

        // Vérifier si le voyage part bien de la gare de l'agent
        $voyage = \App\Models\Voyage::with('trajet')->find($request->voyage_id);
        if (!$voyage || $voyage->gare_id !== $user->gare_id) {
            return response()->json(['statut' => false, 'message' => 'Ce voyage n\'appartient pas à votre gare de départ'], 422);
        }

        $colis = Colis::create([
            'user_id' => $request->user_id,
            'nom_colis' => $request->nom_colis,
            'chemin_image' => 'default.png',
            'tel_destinataire' => $request->tel_destinataire,
            'nom_destinataire' => $request->nom_destinataire,
            'provenance' => $user->gare_id,
            'destination' => $request->destination,
            'voyage_id' => $request->voyage_id,
            'prix' => $request->prix ?? 0,
            'poids' => $request->poids ?? 0,
            'visible' => true,
            'statut' => 'en attente'
        ]);

        $colis->load(['user', 'gareProvenance', 'gareDestination', 'voyage.bus']);

        return response()->json([
            'statut' => true, 
            'message' => 'Colis enregistré avec succès',
            'data' => $colis
        ], 201);
    }

    public function getChefAgenceColis(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->gare_id) {
            return response()->json(['statut' => false, 'message' => 'Gare non assignée'], 404);
        }

        $gare = \App\Models\Gare::find($user->gare_id);
        if (!$gare || !$gare->agence_id) {
            return response()->json(['statut' => false, 'message' => 'Agence introuvable'], 404);
        }

        $gareIds = \App\Models\Gare::where('agence_id', $gare->agence_id)->pluck('id');

        $colis = Colis::with(['user:id,nom,prenom,telephone,num_cni', 'gareProvenance:id,nom,ville', 'gareDestination:id,nom,ville', 'voyage.bus'])
            ->whereIn('provenance', $gareIds)
            ->orWhereIn('destination', $gareIds)
            ->latest()
            ->get();

        return response()->json(['statut' => true, 'data' => $colis]);
    }

    public function createChefAgenceColis(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'nom_colis' => 'required|string|max:255',
            'tel_destinataire' => 'required|string',
            'nom_destinataire' => 'required|string',
            'destination' => 'required|exists:gares,id',
            'voyage_id' => 'required|exists:voyages,id',
            'prix' => 'nullable|numeric|min:0',
            'poids' => 'nullable|numeric|min:0'
        ]);

        $user = $request->user();
        if (!$user || !$user->gare_id) {
            return response()->json(['statut' => false, 'message' => 'Gare non assignée'], 404);
        }

        $gare = \App\Models\Gare::find($user->gare_id);
        if (!$gare || !$gare->agence_id) {
            return response()->json(['statut' => false, 'message' => 'Agence introuvable'], 404);
        }

        $allowedGareIds = \App\Models\Gare::where('agence_id', $gare->agence_id)->pluck('id');

        $voyage = \App\Models\Voyage::with('trajet')->find($request->voyage_id);
        if (!$voyage || !$allowedGareIds->contains($voyage->gare_id)) {
            return response()->json(['statut' => false, 'message' => 'Ce voyage n\'appartient pas à votre agence'], 422);
        }

        $colis = Colis::create([
            'user_id' => $request->user_id,
            'nom_colis' => $request->nom_colis,
            'chemin_image' => 'default.png',
            'tel_destinataire' => $request->tel_destinataire,
            'nom_destinataire' => $request->nom_destinataire,
            'provenance' => $voyage->gare_id,
            'destination' => $request->destination,
            'voyage_id' => $request->voyage_id,
            'prix' => $request->prix ?? 0,
            'poids' => $request->poids ?? 0,
            'visible' => true,
            'statut' => 'en attente'
        ]);

        $colis->load(['user', 'gareProvenance', 'gareDestination', 'voyage.bus']);

        return response()->json([
            'statut' => true, 
            'message' => 'Colis enregistré avec succès',
            'data' => $colis
        ], 201);
    }

    public function updateColisStatus(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:en attente,retire'
        ]);

        $colis = Colis::find($id);
        if (!$colis) {
            return response()->json(['statut' => false, 'message' => 'Colis non trouvé'], 404);
        }

        $colis->update(['statut' => $request->statut]);

        return response()->json([
            'statut' => true,
            'message' => 'Statut du colis mis à jour',
            'data' => $colis
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVilleRequest;
use App\Http\Requests\UpdateVilleRequest;
use App\Models\Ville;
use Illuminate\Http\Request;

class VilleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $villes = Ville::all();

        return response()->json([
            'statut' => true,
            'data' => $villes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string',
            'region' => 'required|string',
        ]);

        $villeExists = Ville::where('nom', $request->nom)->where('region', $request->region)->exists();
        
        if (! $villeExists) {
            $vi = Ville::create([
                'nom' => $request->nom,
                'region' => $request->region,
            ]);

            return response()->json([
                'statut' => true,
                'data' => $vi,
            ], 201);
        } else {
            return response()->json([
                'statut' => false,
                'message' => 'Cette ville existe deja',
            ], 422);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ville $ville)
    {
        $request->validate([
            'nom' => 'required|string',
            'region' => 'required|string',
        ]);

        $villeExists = Ville::where('nom', $request->nom)
                            ->where('region', $request->region)
                            ->where('id', '!=', $ville->id)
                            ->exists();

        if ($villeExists) {
            return response()->json([
                'statut' => false,
                'message' => 'Une autre ville avec ce nom et cette région existe déjà',
            ], 422);
        }

        $ville->update([
            'nom' => $request->nom,
            'region' => $request->region,
        ]);

        return response()->json([
            'statut' => true,
            'data' => $ville,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ville $ville)
    {
        // Optionnel : on pourrait vérifier si la ville est utilisée par des gares
        // if ($ville->gares()->exists()) {
        //     return response()->json(['statut' => false, 'message' => 'Impossible de supprimer cette ville car elle est liée à des gares.'], 403);
        // }
        
        $ville->delete();

        return response()->json([
            'statut' => true,
            'message' => 'Ville supprimée avec succès',
        ]);
    }
}

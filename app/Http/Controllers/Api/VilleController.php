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
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $request->validate([
            'nom' => 'required|string',
            'region' => 'required|string',
        ]);

        $ville = Ville::where('nom', $request->nom)->where('region', $request->region)->get();
        if (! $ville) {
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
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVilleRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Ville $ville)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ville $ville)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVilleRequest $request, Ville $ville)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ville $ville)
    {
        //
    }
}

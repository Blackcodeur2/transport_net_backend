<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Bus;
use Illuminate\Http\Request;

class BusController extends Controller
{
    public function getBusesByGare($id)
    {
        $buses = Bus::where('gare_id', $id)->get();
        return response()->json($buses);
    }

    public function getBusById($id)
    {
        $bus = Bus::find($id);
        if (!$bus) {
            return response()->json(['message' => 'Bus non trouvé'], 404);
        }
        return response()->json($bus);
    }

    public function updateBus($id, Request $request)
    {
        $request->validate([
            'matricule' => 'sometimes|required|string|max:255',
            'capacite' => 'sometimes|required|integer',
            'gare_id' => 'sometimes|required|exists:gares,id',
        ]);
        $bus = Bus::find($id);
        if (!$bus) {
            return response()->json(['message' => 'Bus non trouvé'], 404);
        }

        $bus->update($request->all());
        return response()->json($bus);
    }

    public function deleteBus($id)
    {
        $bus = Bus::find($id);
        if (!$bus) {
            return response()->json(['message' => 'Bus non trouvé'], 404);
        }

        $bus->delete();
        return response()->json(['message' => 'Bus supprimé avec succès']);
    }

    public function createBus(Request $request)
    {
        $request->validate([
            'matricule' => 'required|string|max:255',
            'capacite' => 'required|integer',
            'gare_id' => 'required|exists:gares,id',
        ]);

        $bus = Bus::create($request->all());
        return response()->json($bus, 201);
    }

    public function getBusesByAgence($id)
    {
        $buses = Bus::whereHas('gare', function ($query) use ($id) {
            $query->where('agence_id', $id);
        })->get();

        return response()->json($buses);
    }
}

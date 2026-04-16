<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Gare;
use Illuminate\Http\Request;

class GareController extends Controller
{
    public function getGaresByAgence($id)
    {
        $gares = Gare::where('agence_id', $id)->get();
        return response()->json($gares);
    }

    public function getGareById($id)
    {
        $gare = Gare::find($id);
        if (!$gare) {
            return response()->json(['message' => 'Gare non trouvée'], 404);
        }
        return response()->json($gare);
    }

    public function updateGare($id, Request $request)
    {
        $request->validate([
            'agence_id' => 'sometimes|required|exists:agences,id',
            'ville' => 'sometimes|required|string|max:255',
            'quartier' => 'sometimes|required|string|max:255',
            'telephone' => 'sometimes|required|string|max:20',
        ]);

        $gare = Gare::find($id);
        if (!$gare) {
            return response()->json(['message' => 'Gare non trouvée'], 404);
        }

        $data = $request->all();
        if ($request->has(['ville', 'quartier'])) {
            $data['nom'] = $request->ville . ' - ' . $request->quartier;
            $data['adresse'] = $request->quartier;
        }

        $gare->update($data);
        return response()->json($gare);
    }

    public function deleteGare($id)
    {
        $gare = Gare::find($id);
        if (!$gare) {
            return response()->json(['message' => 'Gare non trouvée'], 404);
        }

        $gare->delete();
        return response()->json(['message' => 'Gare supprimée avec succès']);
    }

    public function createGare(Request $request)
    {
        $request->validate([
            'agence_id' => 'required|exists:agences,id',
            'ville' => 'required|string|max:255',
            'quartier' => 'required|string|max:255',
            'telephone' => 'required|string|max:20',
        ]);

        $data = $request->all();
        $data['nom'] = $request->ville . ' - ' . $request->quartier;
        $data['adresse'] = $request->quartier;

        $gare = Gare::create($data);

        return response()->json($gare, 201);
    }
}

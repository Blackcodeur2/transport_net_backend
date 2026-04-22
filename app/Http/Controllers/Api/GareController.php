<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Models\Gare;
use App\Models\Ville;
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
            'ville_id' => 'sometimes|required|exists:villes,id',
            'quartier' => 'sometimes|required|string|max:255',
            'telephone' => 'sometimes|required|string|max:20',
        ]);

        $gare = Gare::find($id);
        if (!$gare) {
            return response()->json(['message' => 'Gare non trouvée'], 404);
        }

        $data = $request->all();

        if ($request->has('ville_id')) {
            $agence = Agence::find($request->agence_id);
            $ville = Ville::find($request->ville_id);
            if ($ville && $request->filled('quartier')) {
                $data['nom'] = $agence->nom . ' - ' . $request->quartier;
                $data['adresse'] = $request->quartier;
            }
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
            'ville_id' => 'required|exists:villes,id',
            'quartier' => 'required|string|max:255',
            'telephone' => 'required|string|max:20',
        ]);

        $ville = Ville::find($request->ville_id);
        if (! $ville) {
            return response()->json(['message' => 'Ville introuvable'], 404);
        }

        $data = $request->all();
        $agence = Agence::find($request->agence_id);
        $data['nom'] = $agence->nom . ' - ' . $request->quartier;
        $data['adresse'] = $request->quartier;
        $gare = Gare::create($data);

        $gare->update(['is_active',0]);

        return response()->json($gare, 201);
    }
}

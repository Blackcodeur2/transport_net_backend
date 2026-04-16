<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Models\KWCDocument;
use App\Models\User;
use Illuminate\Http\Request;

class KWCDocumentController extends Controller
{

    public function saveCNI(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'fichier' => 'required|file|mimes:pdf,jpeg,jpg,png|max:10240',
            'commentaire' => 'nullable'
        ]);

        $image = $request->file('fichier');
        $chemin = $image->store('kwc', 'public');
        $user_id = $request->user()->id;

        $cni = KWCDocument::create([
            'user_id' => $user_id,
            'type' => 'cni',
            'chemin_fichier' => $chemin,
            'commentaire' => $request->commentaire ?? ''
        ]);

        return response()->json([
            'cni' => $cni,
            'message' => 'CNI enregistree avec succes',
        ], 201);
    }

    public function saveProprietaireKWC(Request $request)
    {
        $request->validate([
            'document_type'   => 'required|string|in:CNI,PASSPORT,RESIDENCE_PERMIT',
            'document_number' => 'required|string|max:100',
            'expiry_date'     => 'required|date|after:today',
            'file_front'      => 'required|file|mimes:pdf,jpeg,jpg,png,webp|max:10240',
            'file_back'       => 'required|file|mimes:pdf,jpeg,jpg,png,webp|max:10240',
            'file_selfie'     => 'required|file|mimes:jpeg,jpg,png,webp|max:10240',
        ]);

        $userId = $request->user()->id;

        // Stocker les 3 fichiers
        $cheminFront  = $request->file('file_front')->store('kwc/proprietaires', 'public');
        $cheminBack   = $request->file('file_back')->store('kwc/proprietaires', 'public');
        $cheminSelfie = $request->file('file_selfie')->store('kwc/proprietaires', 'public');

        // Enregistrer chaque document séparément
        $docFront = KWCDocument::create([
            'user_id'        => $userId,
            'type'           => strtolower($request->document_type) . '_recto',
            'chemin_fichier' => $cheminFront,
            'commentaire'    => 'Numéro: ' . $request->document_number . ' | Expiration: ' . $request->expiry_date,
        ]);

        $docBack = KWCDocument::create([
            'user_id'        => $userId,
            'type'           => strtolower($request->document_type) . '_verso',
            'chemin_fichier' => $cheminBack,
            'commentaire'    => '',
        ]);

        $docSelfie = KWCDocument::create([
            'user_id'        => $userId,
            'type'           => 'selfie',
            'chemin_fichier' => $cheminSelfie,
            'commentaire'    => '',
        ]);

        // Mettre à jour le statut KWC de l'utilisateur (PENDING)
        $request->user()->update([
            'kyc_status' => 'PENDING',
        ]);

        return response()->json([
            'message'   => 'Dossier KWC soumis avec succès. Vérification en cours.',
            'documents' => [
                'recto'  => $docFront,
                'verso'  => $docBack,
                'selfie' => $docSelfie,
            ],
        ], 201);
    }


    public function afficherDocumentEnAttente()
    {
        $kwcDocuments = KWCDocument::with('user')
            ->where('statut', 'en attente')
            ->get();

        return response()->json($kwcDocuments);
    }

    public function validateDocument(Request $request)
    {
        $document = KWCDocument::findOrFail($request->id);
        $document->statut = 'approuve';
        $document->save();

        $user = User::find($document->user_id);
        if ($user) {
            $user->statut = 'approuve';
            $user->save();
        }

        $agence = Agence::where('proprietaire_id', $user->id)->first();
        if ($agence) {
            $agence->statut = 'approuvee';
            $agence->save();
        }

        return response()->json(['message' => 'document valide avec succes']);
    }

    public function rejeterDocument($id)
    {
        $document = KWCDocument::findOrFail($id);
        $document->statut = 'rejete';
        $document->save();

        $user = User::find($document->user_id);
        if ($user) {
            $user->statut = 'rejete';
            $user->save();
        }

        $agence = Agence::where('proprietaire_id', $user->id)->first();
        if ($agence) {
            $agence->statut = 'rejetee';
            $agence->save();
        }

        return response()->json(['message' => 'document rejete avec succes']);
    }

    public function supprimerDocument($id)
    {
        $document = KWCDocument::find($id);
        if (!$document) {
            return response()->json(['message' => 'Document non trouvé'], 404);
        }

        $document->delete();
        return response()->json(['message' => 'document supprime avec succes']);
    }

    public function modifierDocument(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|string',
            'fichier' => 'required|file|mimes:pdf,jpeg,jpg,png|max:10240',
            'commentaire' => 'nullable'
        ]);

        $document = KWCDocument::find($id);
        if (!$document) {
            return response()->json(['message' => 'Document non trouvé'], 404);
        }

        $image = $request->file('fichier');
        $chemin = $image->store('cnies', 'public');
        $user_id = $request->user_id;

        $document->update([
            'user_id' => $user_id,
            'type' => 'doc',
            'chemin_fichier' => $chemin,
            'commentaire' => $request->commentaire ?? ''
        ]);

        return response()->json([
            'document' => $document,
            'message' => 'Document modifie avec succes',
        ], 201);
    }
}

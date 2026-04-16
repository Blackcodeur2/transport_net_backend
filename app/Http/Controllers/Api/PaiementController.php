<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Paiement;
use App\Services\CamPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaiementController extends Controller
{
    protected $camPayService;

    public function __construct(CamPayService $camPayService)
    {
        $this->camPayService = $camPayService;
    }

    /**
     * Initie une demande de paiement (Collect)
     */
    public function initiatePayment(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'phone' => 'required|string', // Format 237xxxxxxxxx
        ]);

        $reservation = Reservation::findOrFail($request->reservation_id);

        if ($reservation->statut !== 'en attente') {
            return response()->json(['message' => 'Cette réservation ne peut plus être payée'], 422);
        }

        // Appel à CamPay
        $response = $this->camPayService->collect(
            $request->phone,
            /*$reservation->prix*/1, // Utilise le prix de la réservation
            'RES-' . $reservation->id . '-' . time(), // External Reference
            "Paiement GEV Reservation #{$reservation->num_reservation}"
        );

        if ($response && isset($response['reference'])) {
            // Créer l'entrée de paiement
            Paiement::create([
                'reference' => $response['reference'],
                'reservation_id' => $reservation->id,
                'montant' => $reservation->prix,
                'gare_id' => $reservation->gare_id,
                'statut' => 'en attente',
            ]);

            return response()->json([
                'statut' => true,
                'message' => 'Demande de paiement envoyée. Veuillez confirmer sur votre téléphone.',
                'reference' => $response['reference']
            ]);
        }

        return response()->json([
            'statut' => false,
            'message' => 'Erreur lors de l’initialisation du paiement avec CamPay'
        ], 500);
    }

    /**
     * Callback/Webhook pour recevoir la notification de paiement
     */
    public function handleWebhook(Request $request)
    {
        Log::info('CamPay Webhook Received: ' . json_encode($request->all()));

        $reference = $request->input('reference');
        $status = $request->input('status');

        if (!$reference) {
            return response()->json(['message' => 'Reference manquante'], 400);
        }

        $paiement = Paiement::where('reference', $reference)->first();

        if ($paiement) {
            if ($status === 'SUCCESSFUL') {
                $paiement->update(['statut' => 'validee']);
                
                // Valider la réservation
                $reservation = Reservation::find($paiement->reservation_id);
                if ($reservation) {
                    $reservation->update(['statut' => 'validee']);
                }
                
                Log::info("Paiement réussi pour la référence : {$reference}");
            } else {
                $paiement->update(['statut' => 'echoue']);
                Log::warning("Échec du paiement pour la référence : {$reference}. Statut : {$status}");
            }
        }

        return response()->json(['status' => 'OK']);
    }

    /**
     * Vérifier le statut manuellement (Polling)
     */
    public function checkStatus($reference)
    {
        $paiement = Paiement::where('reference', $reference)->first();
        if (!$paiement) return response()->json(['message' => 'Paiement non trouvé'], 404);

        if ($paiement->statut === 'validee') {
            return response()->json(['statut' => 'SUCCESSFUL']);
        }

        // Vérification directe auprès de CamPay au cas où le webhook aurait raté
        $response = $this->camPayService->checkTransactionStatus($reference);
        
        if ($response && isset($response['status'])) {
            if ($response['status'] === 'SUCCESSFUL') {
                $paiement->update(['statut' => 'validee']);
                $reservation = Reservation::find($paiement->reservation_id);
                if ($reservation) $reservation->update(['statut' => 'validee']);
            }
            return response()->json(['statut' => $response['status']]);
        }

        return response()->json(['statut' => $paiement->statut]);
    }
}

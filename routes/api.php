<?php

use App\Http\Controllers\Api\AgenceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TrajetController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\VoyageController;
use App\Http\Controllers\Api\ColisController;
use App\Http\Controllers\Api\KWCDocumentController;
use App\Http\Controllers\Api\GareController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\AssistantIaController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\VilleController;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'sendResetPasswordLink']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Public Webhook for CamPay
Route::post('/campay/webhook', [PaiementController::class, 'handleWebhook']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function() {
    Route::prefix('client')->group(function(){
        Route::post('/upload-cni', [KWCDocumentController::class, 'saveCNI']);
        Route::post('/kyc/submit', [KWCDocumentController::class, 'saveProprietaireKWC']);
    });

    Route::prefix('proprietaire')->group(function(){
        Route::post('/upload-cni', [KWCDocumentController::class, 'saveCNI']);
        Route::post('/kyc/submit', [KWCDocumentController::class, 'saveProprietaireKWC']);
    });
});
// Si l'utilisateur est authentifie et verifie  , 'verified'
Route::middleware(['auth:sanctum','verified'])->group(function () {
    // Deconnexion
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Assistant IA (chatbot)
    Route::post('/chat', [AssistantIaController::class, 'chat']);


    // Routes pour le client 
    Route::middleware('role:CLIENT')->prefix('client')->group(function () {
        Route::get('/promo-trips', [VoyageController::class, 'getPromoTrips']);
        Route::get('/my-colis', [ColisController::class, 'getMyColis']);
        Route::delete('my-colis/{id}', [ColisController::class, 'hideMyColis']);
        Route::get('/voyages', [VoyageController::class, 'getScheduledVoyages']);
        Route::get('/voyages/{id}', [VoyageController::class, 'getVoyageByIdForClient']);
        Route::get('/voyages/{id}/occupations', [VoyageController::class, 'getOccupiedSeats']);
        Route::get('/trajets-populaires', [TrajetController::class, 'getTrajetsPopulaires']);
        Route::get('/agences', [AgenceController::class, 'getAgencesPartenaires']);
        Route::get('/reservations', [ReservationController::class, 'getMyReservations']);
        Route::get('/reservations/{id}', [ReservationController::class, 'getReservationById']);
        Route::get('/reservations/{id}/ticket', [ReservationController::class, 'generateTicketForClient']);
        
        // CamPay Payment Routes
        Route::post('/payments/initiate', [PaiementController::class, 'initiatePayment']);
        Route::get('/payments/status/{reference}', [PaiementController::class, 'checkStatus']);
        
        Route::post('/reservations', [ReservationController::class, 'createReservation']);
        Route::post('/reserve-seat', [ReservationController::class, 'storeClientReservation']);
        Route::delete('/reservations/{id}', [ReservationController::class, 'cancelReservation']);
        //Route::post('/upload-cni', [KWCDocumentController::class, 'saveCNI']);

    });

    Route::middleware('role:AGENT')->prefix('agent')->group(function () {
        Route::get('/dashboard', [AgentController::class, 'getDashboardStats']);
        Route::get('/routes', [AgentController::class, 'getRoutes']);
        Route::get('/voyages', [AgentController::class, 'getVoyages']);
        Route::get('/voyages/search', [AgentController::class, 'getVoyagesByRoute']);
        Route::get('/clients/search', [AgentController::class, 'searchClients']);
        Route::post('/clients', [AgentController::class, 'createClient']);
        Route::post('/tickets/validate', [AgentController::class, 'validateTicket']);
        Route::get('/voyages/{id}/available-seats', [AgentController::class, 'getAvailableSeats']);
        Route::get('/reservations/{id}/ticket', [AgentController::class, 'generateTicket']);

        Route::get('/reservations', [ReservationController::class, 'getAgentReservations']);
        Route::get('/reservations/{id}', [ReservationController::class, 'getAgentReservationById']);
        Route::post('/reservations', [ReservationController::class, 'createAgentReservation']);
        Route::delete('/reservations/{id}', [ReservationController::class, 'cancelAgentReservation']);

        Route::get('/colis', [ColisController::class, 'getAgentColis']);
        Route::post('/colis', [ColisController::class, 'createAgentColis']);
        Route::patch('/colis/{id}/status', [ColisController::class, 'updateColisStatus']);
    });

     // =================== ROUTES POUR LE CHEF CHAUFFEUR ========================

    Route::middleware('role:CHAUFFEUR')->prefix('chauffeur')->group(function () {
        Route::get('/voyages', [VoyageController::class, 'getVoyagesForChauffeur']);
    });

    // =================== ROUTES POUR LE CHEF D'AGENCE ========================

    Route::middleware('role:CHEF_AGENCE')->prefix('chef-agence')->group(function () {
        Route::get('/mon-agence', [AgenceController::class, 'getMyAgenceDetails']);
        Route::get('/gares', [AgenceController::class, 'getMyAgenceGares']);
        Route::get('/buses', [AgenceController::class, 'getMyAgenceBuses']);
        Route::get('/export-buses', [AgenceController::class, 'exportBusesPdf']);
        Route::get('/buses/dispo', [AgenceController::class, 'getMyAgenceBusesDispo']);
        Route::post('/buses', [AgenceController::class, 'createBus']);
        Route::put('/buses/{id}', [AgenceController::class, 'updateBus']);
        Route::get('/trajets', [AgenceController::class, 'getMyAgenceTrajets']);
        Route::get('/export-trajets', [AgenceController::class, 'exportTrajetsPdf']);
        Route::post('/trajets', [AgenceController::class, 'createTrajet']);
        Route::put('/trajets/{id}', [AgenceController::class, 'updateTrajet']);
        Route::get('/voyages', [VoyageController::class, 'getMyAgenceVoyages']);
        Route::get('/export-voyages', [VoyageController::class, 'exportVoyagesPdf']);
        Route::post('/voyages', [VoyageController::class, 'createVoyage']);
        Route::post('/tickets/validate', [AgentController::class, 'validateTicket']);
        Route::put('/voyages/{id}', [VoyageController::class, 'updateVoyage']);
        Route::get('/export-personnel', [AgenceController::class, 'exportPersonnelPdf']);
        Route::get('/utilisateurs', [AgenceController::class, 'getMyAgenceUsers']);
        Route::post('/staff', [AgenceController::class, 'createStaff']);
        Route::put('/staff/{id}', [AgenceController::class, 'updateStaff']);
        Route::get('/dashboard-stats', [AgenceController::class, 'getChefAgenceDashboardStats']);
        Route::get('/routes', [AgentController::class, 'getRoutes']);
        Route::get('/voyages/search', [AgentController::class, 'getVoyagesByRoute']);
        Route::get('/clients/search', [AgentController::class, 'searchClients']);
        Route::post('/clients', [AgentController::class, 'createClient']);
        Route::get('/voyages/{id}/available-seats', [AgentController::class, 'getAvailableSeats']);

        Route::get('/reservations', [ReservationController::class, 'getChefAgenceReservations']);
        Route::get('/export-reservations', [ReservationController::class, 'exportReservationsPdf']);
        Route::get('/reservations/{id}', [ReservationController::class, 'getChefAgenceReservationById']);
        Route::get('/reservations/{id}/ticket', [AgentController::class, 'generateTicket']);
        Route::post('/reservations', [ReservationController::class, 'createChefAgenceReservation']);
        Route::delete('/reservations/{id}', [ReservationController::class, 'cancelChefAgenceReservation']);
        
        Route::get('/colis', [ColisController::class, 'getChefAgenceColis']);
        Route::post('/colis', [ColisController::class, 'createChefAgenceColis']);
        Route::patch('/colis/{id}/status', [ColisController::class, 'updateColisStatus']);
    });

    Route::middleware('role:PROPRIETAIRE')->prefix('proprietaire')->group(function () {
        Route::get('/mes-agences/{id}', [AgenceController::class, 'getMyAgences']);
        Route::post('/agences', [AgenceController::class, 'createAgence']);
        Route::put('/agences/{id}', [AgenceController::class, 'updateAgence']);
        Route::delete('/agences/{id}', [AgenceController::class, 'deleteAgence']);
        Route::get('/gares', [AgenceController::class, 'getMyAgencesGares']);
        Route::post('/gares', [GareController::class, 'createGare']);
        Route::put('/gares/{id}', [GareController::class, 'updateGare']);
        Route::delete('/gares/{id}', [GareController::class, 'deleteGare']);
        Route::get('/buses', [AgenceController::class, 'getMyAgencesBuses']);
        Route::get('/trajets', [AgenceController::class, 'getMyAgencesTrajets']);
        Route::get('/voyages', [VoyageController::class, 'getMyAgencesVoyages']);
        Route::get('/utilisateurs', [AgenceController::class, 'getMyAgencesUsers']);
        Route::get('/gerants', [AgenceController::class, 'getMyGerants']);
        Route::post('/gerants', [AgenceController::class, 'createGerant']);
        Route::delete('/gerants/{id}', [AgenceController::class, 'deleteGerant']);
        Route::get('/statistiques', [AgenceController::class, 'getMyAgencesStats']);
        Route::get('/reservations', [ReservationController::class, 'getProprietaireReservations']);
        Route::get('/reservations/{id}', [ReservationController::class, 'getProprietaireReservationById']);
        Route::post('/reservations', [ReservationController::class, 'createProprietaireReservation']);
        Route::delete('/reservations/{id}', [ReservationController::class, 'cancelProprietaireReservation']);
    });

    Route::middleware('role:ADMIN')->prefix('admin')->group(function () {
        Route::get('/kyc', [KWCDocumentController::class, 'afficherDocumentEnAttente']);
        Route::put('/kyc/{id}/approve',[KWCDocumentController::class, 'validateDocument']);
        Route::get('/agences', [AgenceController::class, 'getAllAgences']);
        Route::get('/users', [AgenceController::class, 'getAllUsers']);
        Route::get('/voyages', [VoyageController::class, 'getAllVoyages']);
        Route::get('/reservations', [ReservationController::class, 'getAllReservations']);
        Route::get('/reservations/{id}', [ReservationController::class, 'getReservationByIdAdmin']);
        Route::put('/reservations/{id}', [ReservationController::class, 'updateReservationStatus']);
    }); 

    Route::get('/villes', [VilleController::class, 'index']);
    Route::post('/villes', [VilleController::class, 'create']);
});




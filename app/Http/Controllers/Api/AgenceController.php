<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Models\Bus;
use App\Models\Gare;
use App\Models\KWCDocument;
use App\Models\Reservation;
use App\Models\Trajet;
use App\Models\User;
use App\Models\Voyage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgenceController extends Controller
{
    public function getAllUsers(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 200));

        $users = User::query()
            ->select([
                'id',
                'nom',
                'prenom',
                'email',
                'telephone',
                'num_cni',
                'date_naissance',
                'role_user',
                'gare_id',
                'created_at',
                'updated_at',
            ])
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'statut' => true,
            'data' => $users,
        ]);
    }

    public function getAllAgences()
    {
        $agences = Agence::with('gares', 'owner')->get();

        return response()->json($agences);
    }

    public function getAgenceById($id)
    {
        $agence = Agence::with('gares', 'owner')->find($id);
        if (! $agence) {
            return response()->json(['message' => 'Agence non trouvée'], 404);
        }

        return response()->json([
            'statut' => true,
            'data' => $agence,
        ]);
    }

    public function updateAgence($id, Request $request)
    {
        $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'telephone' => 'sometimes|required|string|max:20',
            'adresse' => 'sometimes|required|string|max:255',
        ]);
        $agence = Agence::find($id);
        if (! $agence) {
            return response()->json(['message' => 'Agence non trouvée'], 404);
        }

        $agence->update($request->all());

        return response()->json(['message' => 'Agence Modifiee avec succes']);
    }

    public function deleteAgence($id)
    {
        $agence = Agence::find($id);
        if (! $agence) {
            return response()->json(['message' => 'Agence non trouvée'], 404);
        }

        $agence->delete();

        return response()->json(['message' => 'Agence supprimée avec succès']);
    }

    public function createAgence(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'telephone' => 'required|string|max:20',
        ]);

        $user = Auth::user();
        $data = $request->all();
        $data['proprietaire_id'] = $user->id;

        $agence = Agence::create($data);

        return response()->json($agence, 201);
    }

    public function getMyAgences($id)
    {
        $agences = Agence::with('gares')->where('proprietaire_id', '=', $id)->get();

        return response()->json([
            'statut' => true,
            'data' => $agences,
        ]);
    }

    private function findMyAgence($id, array $with = [])
    {
        return Agence::with($with)
            ->where('proprietaire_id', $id)
            ->first();
    }

    private function getChefAgency(array $with = [])
    {
        $user = Auth::user();
        if (! $user || ! $user->gare_id) {
            return null;
        }

        $gare = Gare::find($user->gare_id);
        if (! $gare || ! $gare->agence_id) {
            return null;
        }

        return Agence::with($with)->find($gare->agence_id);
    }

    private function isGareInChefAgency(int $gareId): bool
    {
        $agence = $this->getChefAgency(['gares']);
        if (! $agence) {
            return false;
        }

        return $agence->gares->pluck('id')->contains($gareId);
    }

    public function getMyAgenceDetails()
    {
        $agence = $this->getChefAgency([
            'owner',
            'gares.users',
            'gares.buses',
            'gares.trajets',
            'gares.voyages',
        ]);

        if (! $agence) {
            return response()->json(['message' => 'Agence non trouvée pour ce chef d agence'], 404);
        }

        $users = $agence->gares->flatMap->users;

        return response()->json([
            'statut' => true,
            'data' => [
                'agence' => $agence,
                'utilisateurs' => [
                    'chef_agence' => $agence->owner,
                    'chauffeurs' => $users->where('role_user', 'CHAUFFEUR')->values(),
                    'agents' => $users->where('role_user', 'AGENT')->values(),
                ],
            ],
        ]);
    }

    public function getMyAgenceGares()
    {
        $agence = $this->getChefAgency(['gares']);

        if (! $agence) {
            return response()->json(['message' => 'Agence non trouvée pour ce chef d agence'], 404);
        }

        return response()->json([
            'statut' => true,
            'data' => $agence->gares,
        ]);
    }

    public function getMyAgenceBuses(Request $request)
    {
        $agence = $this->getChefAgency(['gares']);

        if (! $agence) {
            return response()->json(['message' => 'Agence non trouvée pour ce chef d agence'], 404);
        }

        // $gareIds = $agence->gares->pluck('id');
        $gareIds = $request->user()->gare_id;
        $buses = Bus::where('gare_id', $gareIds)->get()->map(function ($bus) {
            return [
                'id' => $bus->id,
                'immatriculation' => $bus->immatriculation,
                'code_bus' => $bus->code_bus,
                'nb_places' => $bus->nb_places,
                'classe_bus' => $bus->type,
                'type_bus' => $bus->modele,
                'gare_id' => $bus->gare_id,
                'statut' => $bus->statut,
            ];
        });

        return response()->json([
            'statut' => true,
            'data' => $buses,
        ]);
    }

    public function getMyAgenceBusesDispo(Request $request)
    {
        $agence = $this->getChefAgency(['gares']);
        if (! $agence) {
            return response()->json(['message' => 'Agence non trouvée pour ce chef d agence'], 404);
        }

        $gareIds = $request->user()->gare_id;
        $buses = Bus::where('gare_id', $gareIds)->where('statut', 'disponible')->get()->map(function ($bus) {
            return [
                'id' => $bus->id,
                'immatriculation' => $bus->immatriculation,
                'code_bus' => $bus->code_bus,
                'nb_places' => $bus->nb_places,
                'classe_bus' => $bus->type,
                'type_bus' => $bus->modele,
                'gare_id' => $bus->gare_id,
                'statut' => $bus->statut,
            ];
        });

        return response()->json([
            'statut' => true,
            'data' => $buses,
        ]);
    }

    public function getMyAgenceTrajets(Request $request)
    {
        $agence = $this->getChefAgency(['gares']);

        if (! $agence) {
            return response()->json(['message' => 'Agence non trouvée pour ce chef d agence'], 404);
        }

        $gareIds = $request->user()->gare_id;
        $trajets = Trajet::with(['villeDepart', 'villeArrivee'])->where('gare_id', $gareIds)
            ->get()
            ->map(function ($trajet) {
                return [
                    'id' => $trajet->id,
                    'depart' => $trajet->villeDepart?->nom ?? 'Inconnu',
                    'arrivee' => $trajet->villeArrivee?->nom ?? 'Inconnu',
                    'prix' => $trajet->prix,
                    'distance_km' => $trajet->distance_km ?? 0,
                    'type_trajet' => $trajet->type_trajet,
                    'ville_depart' => $trajet->ville_depart,
                    'ville_arrive' => $trajet->ville_arrive,
                    'gare_id' => $trajet->gare_id,
                ];
            });

        return response()->json([
            'statut' => true,
            'data' => $trajets,
        ]);
    }

    public function getMyAgenceUsers(Request $request)
    {
        $agence = $this->getChefAgency(['gares']);

        if (! $agence) {
            return response()->json(['message' => 'Agence non trouvée pour ce chef d agence'], 404);
        }

        $gareIds = $request->user()->gare_id;
        $users = User::where('gare_id', $gareIds)->get();

        return response()->json([
            'statut' => true,
            'data' => $users,
        ]);
    }

    public function exportPersonnelPdf(Request $request)
    {
        $agence = $this->getChefAgency(['gares']);

        if (! $agence) {
            return response()->json(['message' => 'Agence non trouvée pour ce chef d agence'], 404);
        }

        $gareIds = $request->user()->gare_id;
        $gare = Gare::find($gareIds);

        $users = User::where('gare_id', $gareIds)->get();

        $pdf = Pdf::loadView('pdf.personnel', [
            'agenceName' => $agence->nom,
            'gareName' => $gare ? $gare->nom.' - '.($gare->ville?->nom ?? '') : 'Inconnue',
            'personnel' => $users,
        ]);

        return $pdf->download('personnel_agence_'.date('Y_m_d_H_i').'.pdf');
    }

    public function exportBusesPdf(Request $request)
    {
        $agence = $this->getChefAgency(['gares']);
        if (! $agence) {
            return response()->json(['message' => 'Agence non trouvée'], 404);
        }

        $gareIds = $request->user()->gare_id;
        $gare = Gare::find($gareIds);

        $buses = Bus::where('gare_id', $gareIds)->get();

        $pdf = Pdf::loadView('pdf.buses', [
            'agenceName' => $agence->nom,
            'gareName' => $gare ? $gare->nom.' - '.($gare->ville?->nom ?? '') : 'Inconnue',
            'buses' => $buses,
        ]);

        return $pdf->download('buses_agence_'.date('Y_m_d_H_i').'.pdf');
    }

    public function exportTrajetsPdf(Request $request)
    {
        $agence = $this->getChefAgency(['gares']);
        if (! $agence) {
            return response()->json(['message' => 'Agence non trouvée'], 404);
        }

        $gareIds = $request->user()->gare_id;
        $gare = Gare::find($gareIds);

        $trajets = Trajet::with(['villeDepart', 'villeArrivee'])->where('gare_id', $gareIds)->get();

        $pdf = Pdf::loadView('pdf.trajets', [
            'agenceName' => $agence->nom,
            'gareName' => $gare ? $gare->nom.' - '.($gare->ville?->nom ?? '') : 'Inconnue',
            'trajets' => $trajets,
        ]);

        return $pdf->download('trajets_agence_'.date('Y_m_d_H_i').'.pdf');
    }

    public function getChefAgenceDashboardStats(Request $request)
    {
        $agence = $this->getChefAgency(['gares']);
        if (! $agence) {
            return response()->json(['message' => 'Agence non trouvée pour ce chef d agence'], 404);
        }

        // $gareIds = $agence->gares->pluck('id');
        $gareIds = $request->user()->gare_id;

        // Basic Stats
        $stats = [
            'total_buses' => Bus::where('gare_id', '=', $gareIds)->count(),
            'total_staff' => User::where('gare_id', '=', $gareIds)
                ->whereIn('role_user', ['AGENT', 'CHAUFFEUR'])
                ->count(),
            'total_trajets' => Trajet::where('gare_id', '=', $gareIds)->count(),
            'revenue_today' => (float) Reservation::where('gare_id', $gareIds)
                ->whereDate('created_at', now()->toDateString())
                ->where('statut', 'validee')
                ->sum('prix'),
            'tickets_today' => Reservation::where('gare_id', $gareIds)
                ->whereDate('created_at', now()->toDateString())
                ->where('statut', 'validee')
                ->count(),
        ];

        // Fleet Status
        $fleetStatusRaw = Bus::where('gare_id', '=', $gareIds)
            ->selectRaw('statut, count(*) as count')
            ->groupBy('statut')
            ->get();

        // Revenue History (7 Days)
        $revenueHistory = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayKey = $date->toDateString();
            $revenueHistory[] = [
                'day' => $date->format('D'),
                'amount' => (float) Reservation::where('gare_id', $gareIds)
                    ->whereDate('created_at', $dayKey)
                    ->where('statut', 'validee')
                    ->sum('prix'),
                'date' => $dayKey,
            ];
        }

        // Recent Reservations
        $recentReservations = Reservation::with(['user', 'voyage.trajet.villeDepart', 'voyage.trajet.villeArrivee'])
            ->where('gare_id', $gareIds)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($res) {
                return [
                    'id' => $res->id,
                    'clientName' => $res->user ? $res->user->nom.' '.$res->user->prenom : 'Anonyme',
                    'route' => ($res->voyage?->trajet?->villeDepart?->nom ?? 'Inconnu').' → '.($res->voyage?->trajet?->villeArrivee?->nom ?? 'Inconnu'),
                    'amount' => (float) $res->prix,
                    'date' => $res->created_at->format('d/m/Y'),
                ];
            });

        // Live Trips
        $liveTrips = Voyage::with(['trajet.villeDepart', 'trajet.villeArrivee', 'bus', 'chauffeur'])
            ->where('gare_id', '=', $gareIds)
            ->where('date_depart', '>=', now()->toDateString())
            ->orderBy('date_depart')
            ->limit(5)
            ->get()
            ->map(function ($voyage) {
                return [
                    'id' => $voyage->id,
                    'num_voyage' => $voyage->num_voyage,
                    'date_depart' => $voyage->date_depart,
                    'heure_depart' => $voyage->date_depart ? date('H:i', strtotime($voyage->date_depart)) : null,
                    'ville_depart' => $voyage->trajet?->villeDepart?->nom ?? 'Inconnu',
                    'ville_arrivee' => $voyage->trajet?->villeArrivee?->nom ?? 'Inconnu',
                    'vehicule_immatriculation' => $voyage->bus?->immatriculation,
                    'statut' => $voyage->statut,
                    'chauffeur' => $voyage->chauffeur,
                ];
            });

        return response()->json([
            'statut' => true,
            'data' => [
                'stats' => $stats,
                'fleet_status' => $fleetStatusRaw,
                'revenue_history' => $revenueHistory,
                'recent_reservations' => $recentReservations,
                'live_trips' => $liveTrips,
            ],
        ]);
    }

    public function createStaff(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'telephone' => 'required|string|unique:users',
            'num_cni' => 'required|string|unique:users',
            'date_naissance' => 'required|date',
            'password' => 'required|string|min:8',
            'role_user' => 'required|in:AGENT,CHAUFFEUR',
        ]);

        $user = Auth::user();
        if (! $user || ! $user->gare_id) {
            return response()->json(['message' => 'Utilisateur introuvable ou non autorisé.'], 403);
        }

        $gare = Gare::find($user->gare_id);
        if (! $gare || ! $gare->agence_id) {
            return response()->json(['message' => 'Agence introuvable.'], 404);
        }

        $matricule = $this->generateMatricule($request->role_user);

        $staff = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'num_cni' => $request->num_cni,
            'date_naissance' => $request->date_naissance,
            'role_user' => $request->role_user,
            'matricule' => $matricule,
            'password' => Hash::make($request->password),
            'gare_id' => $gare->id,
        ]);

        KWCDocument::factory()->count(1)->create([
            'user_id' => $staff->id,
        ]);

        return response()->json($staff, 201);
    }

    protected function generateMatricule($role)
    {
        $i = '';
        do {
            if ($role == 'AGENT') {
                $i = 'AG';
            } elseif ($role == 'CHAUFFEUR') {
                $i = 'CH';
            } else {
                $i = 'CHF';
            }
            $matricule = $i.strtoupper(Str::random(8)).date('m-d');
        } while (User::where('matricule', $matricule)->exists());

        return $matricule;
    }

    public function createBus(Request $request)
    {
        $request->validate([
            'immatriculation' => 'required|string|unique:buses',
            'code_bus' => 'required|string|unique:buses',
            'nb_places' => 'required|integer',
            'gare_id' => 'required|exists:gares,id',
            'type_bus' => 'required|in:coaster,gros porteur',
            'classe_bus' => 'required|in:classique,vip',
            'statut' => 'required|in:disponible,en voyage,en maintenance,indisponible',
        ]);

        if (! $this->isGareInChefAgency($request->gare_id)) {
            return response()->json(['message' => 'Gare invalide ou non autorisée.'], 403);
        }

        $bus = Bus::create([
            'immatriculation' => $request->immatriculation,
            'code_bus' => $request->code_bus,
            'nb_places' => $request->nb_places,
            'gare_id' => $request->gare_id,
            'modele' => $request->type_bus,
            'type' => $request->classe_bus,
            'statut' => $request->statut,
        ]);

        return response()->json(array_merge($bus->toArray(), [
            'classe_bus' => $bus->type,
            'type_bus' => $bus->modele,
        ]), 201);
    }

    public function createTrajet(Request $request)
    {
        $request->validate([
            'ville_depart' => 'required|exists:villes,id',
            'ville_arrive' => 'required|exists:villes,id',
            'prix' => 'required|numeric',
            'distance_km' => 'sometimes|integer',
            'type_trajet' => 'required|in:vip,classique',
        ]);

        $user = Auth::user();
        if (! $user || ! $user->gare_id) {
            return response()->json(['message' => 'Utilisateur introuvable ou non autorisé.'], 403);
        }

        $gare = Gare::find($user->gare_id);
        if (! $gare || ! $gare->agence_id) {
            return response()->json(['message' => 'Agence introuvable.'], 404);
        }

        $trajet = Trajet::create([
            'ville_depart' => $request->ville_depart,
            'ville_arrive' => $request->ville_arrive,
            'prix' => $request->prix,
            'distance_km' => $request->distance_km,
            'type_trajet' => $request->type_trajet,
            'gare_id' => $gare->id,
        ]);

        return response()->json([
            'id' => $trajet->id,
            'depart' => $trajet->villeDepart?->nom ?? 'Inconnu',
            'arrivee' => $trajet->villeArrivee?->nom ?? 'Inconnu',
            'prix' => $trajet->prix,
            'distance_km' => $trajet->distance_km,
            'type_trajet' => $trajet->type_trajet,
            'ville_depart' => $trajet->ville_depart,
            'ville_arrive' => $trajet->ville_arrive,
            'gare_id' => $trajet->gare_id,
        ], 201);
    }

    public function getMyGerants()
    {
        $user = Auth::user();
        $agencyIds = Agence::where('proprietaire_id', $user->id)->pluck('id');
        $gareIds = Gare::whereIn('agence_id', $agencyIds)->pluck('id');

        $gerants = User::whereIn('gare_id', $gareIds)
            ->where('role_user', 'CHEF_AGENCE')
            ->get();

        return response()->json([
            'statut' => true,
            'data' => $gerants,
        ]);
    }

    public function createGerant(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'telephone' => 'required|string|unique:users',
            'num_cni' => 'required|string|unique:users',
            'date_naissance' => 'required|date',
            'password' => 'required|string|min:8|confirmed',
            'gare_id' => 'required|integer|exists:gares,id',
        ]);

        $user = Auth::user();
        $gare = Gare::find($request->gare_id);

        if (! $gare || $gare->agence->proprietaire_id !== $user->id) {
            return response()->json(['message' => 'Gare invalide ou inaccessible.'], 403);
        }
        $matricule = $this->generateMatricule('CHEF_AGENCE');
        $manager = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'num_cni' => $request->num_cni,
            'date_naissance' => $request->date_naissance,
            'role_user' => 'CHEF_AGENCE',
            'password' => Hash::make($request->password),
            'gare_id' => $gare->id,
            'matricule' => $matricule,
            'statut' => 'approuve',
        ]);

        KWCDocument::factory()->count(1)->create([
            'user_id' => $manager->id,
        ]);

        return response()->json($manager, 201);
    }

    public function deleteGerant($id)
    {
        $user = Auth::user();
        $agencyIds = Agence::where('proprietaire_id', $user->id)->pluck('id');
        $gareIds = Gare::whereIn('agence_id', $agencyIds)->pluck('id');

        $manager = User::where('id', $id)
            ->where('role_user', 'CHEF_AGENCE')
            ->whereIn('gare_id', $gareIds)
            ->first();

        if (! $manager) {
            return response()->json(['message' => 'Gérant introuvable ou non autorisé.'], 404);
        }

        $manager->delete();

        return response()->json(['message' => 'Gérant supprimé avec succès']);
    }

    private function getOwnerAgencyIds($id)
    {
        return Agence::where('proprietaire_id', $id)->pluck('id');
    }

    public function getMyAgencesDetails($id)
    {
        $agences = Agence::with([
            'owner',
            'gares.users',
            'gares.buses',
            'gares.trajets',
            'gares.voyages',
        ])->where('proprietaire_id', $id)->get();

        if ($agences->isEmpty()) {
            return response()->json(['message' => 'Aucune agence trouvée pour ce propriétaire'], 404);
        }

        return response()->json([
            'statut' => true,
            'data' => $agences,
        ]);
    }

    public function getMyAgencesGares()
    {
        $user = Auth::user();
        $agencyIds = $this->getOwnerAgencyIds($user->id);
        $gares = Gare::whereIn('agence_id', $agencyIds)->get();

        return response()->json([
            'statut' => true,
            'data' => $gares,
        ]);
    }

    public function getMyAgencesBuses()
    {
        $user = Auth::user();
        $agencyIds = $this->getOwnerAgencyIds($user->id);
        $gareIds = Gare::whereIn('agence_id', $agencyIds)->pluck('id');
        $buses = Bus::whereIn('gare_id', $gareIds)->get();

        return response()->json([
            'statut' => true,
            'data' => $buses,
        ]);
    }

    public function getMyAgencesTrajets()
    {
        $user = Auth::user();
        $agencyIds = $this->getOwnerAgencyIds($user->id);
        $gareIds = Gare::whereIn('agence_id', $agencyIds)->pluck('id');
        $trajets = Trajet::with(['villeDepart', 'villeArrivee'])->whereIn('gare_id', $gareIds)->get()->map(function ($trajet) {
            return [
                'id' => $trajet->id,
                'depart' => $trajet->villeDepart?->nom ?? 'Inconnu',
                'arrivee' => $trajet->villeArrivee?->nom ?? 'Inconnu',
                'prix' => $trajet->prix,
                'distance_km' => $trajet->distance_km,
                'type_trajet' => $trajet->type_trajet,
                'ville_depart' => $trajet->ville_depart,
                'ville_arrive' => $trajet->ville_arrive,
                'gare_id' => $trajet->gare_id,
            ];
        });

        return response()->json([
            'statut' => true,
            'data' => $trajets,
        ]);
    }

    public function getMyAgencesUsers()
    {
        $user = Auth::user();
        $agencyIds = $this->getOwnerAgencyIds($user->id);
        $gareIds = Gare::whereIn('agence_id', $agencyIds)->pluck('id');
        $users = User::whereIn('gare_id', $gareIds)->get();

        return response()->json([
            'statut' => true,
            'data' => $users,
        ]);
    }

    public function getMyAgencesStats()
    {
        $user = Auth::user();
        $agencyIds = $this->getOwnerAgencyIds($user->id);
        $gareIds = Gare::whereIn('agence_id', $agencyIds)->pluck('id');

        $stats = [
            'agences' => $agencyIds->count(),
            'gares' => $gareIds->count(),
            'buses' => Bus::whereIn('gare_id', $gareIds)->count(),
            'trajets' => Trajet::where('gare_id', $gareIds)->distinct('id')->count('id'),
            'voyages' => Voyage::whereIn('gare_id', $gareIds)->count(),
            'utilisateurs' => User::whereIn('gare_id', $gareIds)->count(),
            'chauffeurs' => User::whereIn('gare_id', $gareIds)->where('role_user', 'CHAUFFEUR')->count(),
            'agents' => User::whereIn('gare_id', $gareIds)->where('role_user', 'AGENT')->count(),
        ];

        return response()->json([
            'statut' => true,
            'data' => $stats,
        ]);
    }

    public function getAgencesWithDetails()
    {
        $agences = Agence::with([
            'owner',
            'gares.users',
            'gares.buses',
            'gares.trajets',
            'gares.voyages',
        ])->get()->map(function ($agence) {
            $users = $agence->gares->flatMap(function ($gare) {
                return $gare->users;
            });

            return [
                'id' => $agence->id,
                'nom' => $agence->nom,
                'email' => $agence->email,
                'telephone' => $agence->telephone,
                'adresse' => $agence->adresse,
                'proprietaire_id' => $agence->proprietaire_id,
                'chef_agence' => $agence->owner,
                'gares' => $agence->gares->map(function ($gare) {
                    return [
                        'id' => $gare->id,
                        'nom' => $gare->nom,
                        'ville' => $gare->ville?->nom,
                        'adresse' => $gare->adresse,
                        'telephone' => $gare->telephone,
                        'users' => $gare->users,
                        'buses' => $gare->buses,
                        'trajets' => $gare->trajets,
                        'voyages' => $gare->voyages,
                    ];
                }),
                'utilisateurs' => [
                    'chef_agence' => $agence->owner,
                    'chauffeurs' => $users->where('role_user', 'CHAUFFEUR')->values(),
                    'agents' => $users->where('role_user', 'AGENT')->values(),
                ],
            ];
        });

        return response()->json([
            'statut' => true,
            'data' => $agences,
        ]);
    }

    public function fakeUser()
    {
        $agences = Agence::where('statut', 'en attente')
            ->get(['nom', 'email', 'telephone', 'adresse', 'statut']);

        return response()->json([
            'statut' => true,
            'data' => $agences,
        ]);
    }

    public function getAgencesPartenaires()
    {
        $agences = Agence::with('gares')->get();

        return response()->json([
            'statut' => true,
            'data' => $agences,
        ]);
    }

    public function updateStaff($id, Request $request)
    {
        $request->validate([
            'nom' => 'sometimes|required|string|max:255',
            'prenom' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,'.$id,
            'telephone' => 'sometimes|required|string|unique:users,telephone,'.$id,
            'num_cni' => 'sometimes|required|string|unique:users,num_cni,'.$id,
            'date_naissance' => 'sometimes|required|date',
            'password' => 'sometimes|nullable|string|min:8',
            'role_user' => 'sometimes|required|in:AGENT,CHAUFFEUR',
        ]);

        $staff = User::find($id);
        if (! $staff) {
            return response()->json(['message' => 'Personnel non trouvé'], 404);
        }

        $data = $request->except(['password']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $staff->update($data);

        return response()->json($staff);
    }

    public function updateBus($id, Request $request)
    {
        $request->validate([
            'immatriculation' => 'sometimes|required|string|unique:buses,immatriculation,'.$id,
            'code_bus' => 'sometimes|required|string|unique:buses,code_bus,'.$id,
            'nb_places' => 'sometimes|required|integer',
            'type_bus' => 'sometimes|required|in:coaster,gros porteur',
            'classe_bus' => 'sometimes|required|in:classique,vip',
            'statut' => 'sometimes|required|in:disponible,en voyage,en maintenance,indisponible',
        ]);

        $bus = Bus::find($id);
        if (! $bus) {
            return response()->json(['message' => 'Bus non trouvé'], 404);
        }

        $data = $request->all();
        if ($request->has('type_bus')) {
            $data['modele'] = $request->type_bus;
        }
        if ($request->has('classe_bus')) {
            $data['type'] = $request->classe_bus;
        }

        $bus->update($data);

        return response()->json(array_merge($bus->toArray(), [
            'classe_bus' => $bus->type,
            'type_bus' => $bus->modele,
        ]));
    }

    public function updateTrajet($id, Request $request)
    {
        $request->validate([
            'prix' => 'sometimes|required|numeric',
            'type_trajet' => 'sometimes|required|in:vip,classique',
            'distance_km' => 'sometimes|integer',
        ]);

        $trajet = Trajet::find($id);
        if (! $trajet) {
            return response()->json(['message' => 'Trajet non trouvé'], 404);
        }

        $trajet->update($request->all());

        return response()->json($trajet);
    }
}

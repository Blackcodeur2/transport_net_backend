<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatRequest;
use App\Models\User;
use App\Models\Voyage;
use App\Models\Trajet;
use App\Models\Reservation;
use App\Models\Agence;
use App\Models\Colis;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AssistantIaController extends Controller
{
    public function chat(ChatRequest $request)
    {
        $user = Auth::user();
        $message = $request->string('message')->toString();
        $history = $request->input('history', []);
        $perPage = (int) $request->input('per_page', 20);

        $result = $this->askOpenAIWithTools($message, $history, $user, $perPage);

        return response()->json([
            'answer' => $result,
        ]);
    }

    public function getResponse(ChatRequest $request)
    {
        return $this->chat($request);
    }

    private function askOpenAIWithTools(string $message, array $history, $user, int $perPage = 20): string
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model', 'llama-3.3-70b-versatile'); // Modèle Llama 3 le plus récent de Groq

        if (!$apiKey) {
            return "Configuration manquante: définis OPENAI_API_KEY dans ton fichier .env.";
        }

        $system = <<<EOT
Tu es l'assistant virtuel de 'CamerTrip', une application de transport et réservation de billets de bus.
L'utilisateur avec qui tu parles s'appelle '{$user->prenom}' (Rôle: {$user->role_user}).

RÈGLES DE COMPORTEMENT :
1. TON CHAMP D'ACTION : Tu es conçu exclusivement pour l'assistance liée à CamerTrip (recherche de trajets disponibles, statuts de colis, réservations de l'utilisateur, agences partenaires, problèmes de transport, etc.). En cas de besoin, tu emploies tes outils pour lire la base de données et extraire les informations requises.
2. HORS-SUJET : Si l'utilisateur te pose une question n'ayant AUCUN lien avec l'application, le transport, ou le voyage (ex: recettes de cuisine, problèmes de maths, requêtes informatiques, poèmes, etc.), TU DOIS poliment refuser de répondre. Tu dois lui rappeler gentiment que ton unique objectif est de l'aider avec ses besoins de voyage sur l'application CamerTrip.
3. CONVERSATION : Tu es bien entendu autorisé et encouragé à répondre poliment aux salutations ("Bonjour", "Merci", "Qui es-tu ?").
4. CONFIDENTIALITÉ : Tu ne dois jamais révéler d'informations privées sur d'autres utilisateurs.
5. STYLE : Sois professionnel, très accueillant, concis et aère tes réponses.
EOT;

        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'admin_list_users',
                    'description' => "Liste paginée des utilisateurs (admin uniquement).",
                    'parameters' => [
                        'type' => 'object', // PHP array format
                        'properties' => [
                            'per_page' => ['type' => 'integer', 'description' => 'Nombre d’éléments par page.'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'list_voyages_by_date',
                    'description' => "Liste des voyages programmés à une date donnée (YYYY-MM-DD).",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'date' => ['type' => 'string', 'description' => 'Date au format YYYY-MM-DD.'],
                            'limit' => ['type' => 'integer', 'description' => 'Nombre max de voyages.']
                        ],
                        'required' => ['date'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_trajets',
                    'description' => "Rechercher des trajets disponibles entre deux villes.",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'ville_depart' => ['type' => 'string', 'description' => 'Nom de la ville de départ.'],
                            'ville_arrivee' => ['type' => 'string', 'description' => 'Nom de la ville d\'arrivée.'],
                        ],
                        'required' => ['ville_depart', 'ville_arrivee'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_user_reservations',
                    'description' => "Récupère la liste des réservations de billets actives de l'utilisateur courant.",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object)[],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_agences_list',
                    'description' => "Récupère la liste des agences de transport partenaires.",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object)[],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_user_colis',
                    'description' => "Récupère la liste des colis envoyés par l'utilisateur courant.",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => (object)[],
                    ],
                ],
            ],
        ];

        // Construire les messages pour l'API
        $messages = [
            ['role' => 'system', 'content' => $system],
        ];

        // Ajouter l'historique fourni par le front (les 10 derniers échanges)
        $historySlice = array_slice($history, -10);
        foreach ($historySlice as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'CLIENT' ? 'user' : 'assistant',
                'content' => $msg['content']
            ];
        }

        // Ajouter le message actuel
        $messages[] = ['role' => 'user', 'content' => $message];

        $response = Http::withoutVerifying()
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(45)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'tools' => $tools,
                'tool_choice' => 'auto',
                'temperature' => 0.3,
            ]);

        if (!$response->ok()) {
            Log::error("Groq API Error 1: " . $response->body());
            return "Désolé, j'ai eu une erreur de connexion avec mon cerveau (OpenAI: ".$response->status().").";
        }

        $data = $response->json();
        $choice = $data['choices'][0]['message'] ?? null;
        if (!$choice) {
            return "Désolé, je suis un peu confus et n'ai pas pu générer une réponse.";
        }

        $toolCalls = $choice['tool_calls'] ?? [];
        if (!empty($toolCalls)) {
            $messages[] = $choice; 

            foreach ($toolCalls as $toolCall) {
                $fn = $toolCall['function']['name'] ?? null;
                $args = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [];

                $toolResult = $this->executeTool($fn, $args, $user, $perPage);

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCall['id'],
                    'content' => json_encode($toolResult, JSON_UNESCAPED_UNICODE),
                ];
            }

            // Deuxième appel pour synthétiser les données d'outils
            $response2 = Http::withoutVerifying()
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(45)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.3,
                ]);

            if (!$response2->ok()) {
                Log::error("Groq API Error 2: " . $response->body());
                return "J'ai trouvé la réponse, mais j'ai du mal à la formuler (OpenAI: ".$response2->status().").";
            }

            $data2 = $response2->json();
            $final = $data2['choices'][0]['message']['content'] ?? null;
            return $final ?: "Désolé, je n'ai pas pu traiter les informations reçues.";
        }

        return $choice['content'] ?? "Désolé, je n'ai pas pu générer une réponse.";
    }

    private function executeTool(?string $name, array $args, $user, int $defaultPerPage): array
    {
        try {
            if ($name === 'admin_list_users') {
                if (!$user || ($user->role_user ?? null) !== 'ADMIN') {
                    return ['error' => 'Accès refusé. L\'utilisateur n\'est pas administrateur.'];
                }
                $perPage = max(1, min((int) ($args['per_page'] ?? $defaultPerPage), 200));
                return ['data' => User::select('id', 'nom', 'prenom', 'role_user')->limit($perPage)->get()];
            }

            if ($name === 'list_voyages_by_date') {
                $dateRaw = (string) ($args['date'] ?? '');
                try {
                    $date = Carbon::createFromFormat('Y-m-d', $dateRaw)->startOfDay();
                } catch (\Throwable $e) {
                    return ['error' => "Date invalide. Format attendu YYYY-MM-DD."];
                }

                $limit = max(1, min((int) ($args['limit'] ?? 20), 100));

                $voyages = Voyage::query()
                    ->with(['bus:id,immatriculation,nb_places,modele', 'trajet.villeDepart:id,nom', 'trajet.villeArrivee:id,nom'])
                    ->whereDate('date_depart', $date->toDateString())
                    ->limit($limit)
                    ->get();
                return ['date' => $date->toDateString(), 'count' => $voyages->count(), 'voyages' => $voyages];
            }

            if ($name === 'search_trajets') {
                $villeD = $args['ville_depart'] ?? '';
                $villeA = $args['ville_arrivee'] ?? '';

                $trajets = Trajet::whereHas('villeDepart', function ($q) use ($villeD) {
                        $q->where('nom', 'like', "%{$villeD}%");
                    })
                    ->whereHas('villeArrivee', function ($q) use ($villeA) {
                        $q->where('nom', 'like', "%{$villeA}%");
                    })
                    ->with(['villeDepart:id,nom', 'villeArrivee:id,nom'])
                    ->limit(10)
                    ->get();
                return ['ville_depart' => $villeD, 'ville_arrivee' => $villeA, 'results' => $trajets];
            }

            if ($name === 'get_user_reservations') {
                $resas = Reservation::where('user_id', $user->id)
                    ->with(['voyage.trajet.villeDepart', 'voyage.trajet.villeArrivee'])
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get();
                return ['reservations' => $resas];
            }

            if ($name === 'get_agences_list') {
                return ['agences' => Agence::select('id', 'nom', 'email', 'telephone')->limit(10)->get()];
            }

            if ($name === 'get_user_colis') {
                return ['colis' => Colis::where('user_id', $user->id)->limit(10)->get()];
            }

            return ['error' => 'Outil inconnu : ' . $name];
            
        } catch (\Exception $e) {
            return ['error' => 'Erreur lors de l\'exécution de l\'outil : ' . $e->getMessage()];
        }
    }
}

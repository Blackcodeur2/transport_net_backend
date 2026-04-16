<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'num_reservation' => $this->num_reservation,
            'place' => $this->place,
            'prix' => $this->prix,
            'statut' => $this->statut,
            'created_at' => $this->created_at,
            'voyage' => new VoyageRessource($this->whenLoaded('voyage')),
            'gare' => [
                'id' => $this->gare->id,
                'nom' => $this->gare->nom,
                'ville' => $this->gare->ville,
                'agence' => $this->gare->agence ? [
                    'nom' => $this->gare->agence->nom,
                ] : null,
            ],
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'paiements' => $this->paiements,
        ];
    }
}

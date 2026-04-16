<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrajetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prix' => $this->prix,

            'depart' => [
                'nom' => $this->gareDepart->nom ?? '...',
                'ville' => $this->gareDepart->ville ?? '...',
            ],

            'arrivee' => [
                'nom' => $this->gareArrivee->nom ?? '...',
                'ville' => $this->gareArrivee->ville ?? '...',
            ],

            'total_reservations' => $this->total_reservations ?? $this->voyages_count
        ];
    }
}

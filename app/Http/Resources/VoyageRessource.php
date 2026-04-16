<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoyageRessource extends JsonResource
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
            'date_depart' => $this->date_depart,
            'promo' => $this->promo,

            'bus' => [
                'immatriculation' => $this->bus->immatriculation,
                'nb_places' => $this->bus->nb_places,
                'modele' => $this->bus->modele,
                'code_bus' => $this->bus->code_bus,
            ],

            'driver' => $this->chauffeur ? [
                'name' => $this->chauffeur->nom,
                'phone' => $this->chauffeur->telephone,
            ] : null,

            'trajet' => [
                'prix' => $this->trajet->prix,

                'depart' => $this->trajet->gareDepart ? [
                    'nom' => $this->trajet->gareDepart->nom,
                    'ville' => $this->trajet->gareDepart->ville,
                ] : null,

                'arrivee' => $this->trajet->gareArrivee ? [
                    'nom' => $this->trajet->gareArrivee->nom,
                    'ville' => $this->trajet->gareArrivee->ville,
                ] : null,
            ],
            'ville_depart' => $this->trajet->gareDepart->ville ?? null,
            'ville_arrivee' => $this->trajet->gareArrivee->ville ?? null,
            'heure_depart' => $this->date_depart ? date('H:i', strtotime($this->date_depart)) : null,
            'gare' => $this->gare ? [
                'id' => $this->gare->id,
                'nom' => $this->gare->nom,
                'ville' => $this->gare->ville,
                'adresse' => $this->gare->adresse,
                'agence' => $this->gare->agence ? [
                    'nom' => $this->gare->agence->nom,
                ] : null,
            ] : null,
            'numVoyage' => $this->num_voyage ?? null,
            'statut' => $this->statut ?? null,
        ];
    }
}

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
            'date_arrivee' => $this->date_arrivee,
            'duree_heure' => $this->duree_heure,
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

                'depart' => $this->trajet->villeDepart ? [
                    'nom' => $this->trajet->villeDepart->nom,
                ] : null,

                'arrivee' => $this->trajet->villeArrivee ? [
                    'nom' => $this->trajet->villeArrivee->nom,
                ] : null,
            ],
            'ville_depart' => $this->trajet->villeDepart->nom ?? null,
            'ville_arrivee' => $this->trajet->villeArrivee->nom ?? null,
            'heure_depart' => $this->date_depart ? date('H:i', strtotime($this->date_depart)) : null,
            'gare' => $this->gare ? [
                'id' => $this->gare->id,
                'nom' => $this->gare->nom,
                'ville' => $this->gare->ville?->nom,
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

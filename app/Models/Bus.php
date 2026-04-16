<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    protected $fillable = [
        'gare_id',
        'immatriculation',
        'modele',
        'nb_places',
        'code_bus',
        'type',
        'statut',
    ];
    public function gares()
    {
        return $this->belongsTo(Gare::class);
    }

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trajet extends Model
{
    protected $fillable = [
        'gare_id',
        'depart_id',
        'arrivee_id',
        'distance_km',
        'duree_heure',
        'type_trajet',
        'prix',
    ];

    public function voyages()
    {
        return $this->hasMany(Voyage::class);
    }

    public function gareDepart()
    {
        return $this->belongsTo(Gare::class, 'depart_id');
    }

    public function gareArrivee()
    {
        return $this->belongsTo(Gare::class, 'arrivee_id');
    }
}

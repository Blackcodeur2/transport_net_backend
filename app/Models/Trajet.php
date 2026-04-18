<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trajet extends Model
{
    protected $fillable = [
        'gare_id',
        'ville_depart',
        'ville_arrive',
        'distance_km',
        'duree_heure',
        'type_trajet',
        'prix',
        'is_active',
    ];

    public function voyages()
    {
        return $this->hasMany(Voyage::class);
    }

    public function gare()
    {
        return $this->belongsTo(Gare::class);
    }

    public function villeDepart()
    {
        return $this->belongsTo(Ville::class, 'ville_depart');
    }

    public function villeArrivee()
    {
        return $this->belongsTo(Ville::class, 'ville_arrive');
    }
}

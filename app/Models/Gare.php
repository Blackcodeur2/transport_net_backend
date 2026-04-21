<?php

namespace App\Models;

use Database\Factories\GareFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gare extends Model
{
    /** @use HasFactory<GareFactory> */
    use HasFactory;

    protected $fillable = [
        'agence_id',
        'nom',
        'ville_id',
        'adresse',
        'telephone',
    ];

    public function agence()
    {
        return $this->belongsTo(Agence::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function buses()
    {
        return $this->hasMany(Bus::class);
    }

    public function trajets()
    {
        return $this->hasMany(Trajet::class);
    }

    public function voyages()
    {
        return $this->hasMany(Voyage::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function ville()
    {
        return $this->belongsTo(Ville::class, 'ville_id');
    }

    public function colis()
    {
        return $this->hasMany(Colis::class, 'gare_id');
    }
}

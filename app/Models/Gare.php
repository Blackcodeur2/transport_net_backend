<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\GareFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gare extends Model
{
    /** @use HasFactory<GareFactory> */
    use HasFactory;
    protected $fillable = [
        'agence_id',
        'nom',
        'ville',
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
}

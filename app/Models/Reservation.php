<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable  = [
        'num_reservation',
        'user_id',
        'gare_id',
        'voyage_id',
        'place',
        'prix',
        'statut',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gare()
    {
        return $this->belongsTo(Gare::class);
    }

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }
}

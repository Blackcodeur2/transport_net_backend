<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $fillable = [
        'reference',
        'reservation_id',
        'montant',
        'gare_id',
        'statut'
    ];
    public function reservations()
    {
        return $this->belongsTo(Reservation::class);
    }
}

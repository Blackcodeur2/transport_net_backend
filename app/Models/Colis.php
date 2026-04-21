<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Colis extends Model
{
    /** @use HasFactory<\Database\Factories\ColisFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom_colis',
        'chemin_image',
        'tel_destinataire',
        'nom_destinataire',
        'prix',
        'poids',
        'gare_id',
        'voyage_id',
        'visible',
        'statut'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function voyage()
    {
        return $this->belongsTo(Voyage::class, 'voyage_id');
    }

    public function gares()
    {
        return  $this->belongsTo(Gare::class, 'gare_id');
    }

}

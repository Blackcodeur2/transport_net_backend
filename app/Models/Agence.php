<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agence extends Model
{
    protected $fillable = [
        'proprietaire_id',
        'nom',
        'email',
        'telephone',
        'adresse'
    ];
    public function gares()
    {
        return $this->hasMany(Gare::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class);
    }
}

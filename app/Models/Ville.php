<?php

namespace App\Models;

use Database\Factories\VilleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ville extends Model
{
    protected $fillable = [
        'nom',
        'region'
    ];
    /** @use HasFactory<VilleFactory> */
    use HasFactory;

    public function gares()
    {
        return $this->hasMany(Gare::class, 'ville_id');
    }
}

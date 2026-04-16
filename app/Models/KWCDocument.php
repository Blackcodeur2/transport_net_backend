<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class KWCDocument extends Model
{
     /** @use HasFactory<KWCDocumentFactory> */
     use HasFactory;
    protected $fillable = [
        'user_id',
        'type',
        'chemin_fichier',
        'commentaire'
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}

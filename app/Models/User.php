<?php

namespace App\Models;

use App\Notifications\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'sexe',
        'matricule',
        'date_naissance',
        'region',
        'ville',
        'pos_lat',
        'pos_lng',
        'telephone',
        'role_user',
        'password',
        'gare_id',
        'statut',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function gares()
    {
        return $this->belongsTo(Gare::class);
    }

    public function voyages()
    {
        return $this->hasMany(Voyage::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
    public function isVerified()
    {
        return $this->hasMany(KWCDocument::class)
                     ->where('statut','approuve')
                     ->exists();
    }

    public function isFullyVerified()
    {
        if($this->role_user !== 'PROPRIETAIRE'){
            return $this->isVerified();
        }
        $requiredDocs = [
            'cni_recto',
            'cni_verso',
        ];
        $docApprouves = $this->kwcDocuments()
        ->where('statut', 'approuve')
        ->pluck('type')
        ->toArray();

        foreach ($requiredDocs as $doc) {
            if(!in_array($doc, $docApprouves)){
                return false;
            }
        }
        return true;
    }

    public function kwcDocuments()
    {
        return $this->hasMany(KWCDocument::class, 'user_id');
    }
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function agences()
    {
        return $this->hasMany(Agence::class);
    }

    public function colis()
    {
        return $this->hasMany(Colis::class, 'user_id');
    }
}

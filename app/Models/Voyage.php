<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Voyage extends Model
{
    protected static function booted(): void
    {
        static::updated(function (Voyage $voyage) {
            if ($voyage->wasChanged('statut') && $voyage->statut === 'termine') {
                $voyage->releaseBusAndChauffeurIfIdle();
            }
        });
    }

    protected $fillable = [
        'num_voyage',
        'gare_id',
        'trajet_id',
        'bus_id',
        'chauffeur_id',
        'prix',
        'places_disponibles',
        'statut',
        'date_depart',
        'date_arrivee',
    ];

    protected function casts(): array
    {
        return [
            'date_depart' => 'datetime',
            'date_arrivee' => 'datetime',
        ];
    }

    /**
     * Date/heure de fin prévue : date_arrivee si renseignée, sinon date_depart + durée du trajet.
     */
    public function dateFinPrevue(): ?Carbon
    {
        if ($this->date_arrivee) {
            return $this->date_arrivee instanceof Carbon
                ? $this->date_arrivee
                : Carbon::parse($this->date_arrivee);
        }

        if (! $this->date_depart || ! $this->trajet?->duree_heure) {
            return null;
        }

        $depart = $this->date_depart instanceof Carbon
            ? $this->date_depart
            : Carbon::parse($this->date_depart);

        return $depart->copy()->addHours((int) $this->trajet->duree_heure);
    }

    /**
     * Statut calculé à partir de l'heure actuelle (hors annulation).
     */
    public function resolveStatutAutomatique(?Carbon $now = null): ?string
    {
        if ($this->statut === 'annule') {
            return null;
        }

        $now = $now ?? now();

        $fin = $this->dateFinPrevue();
        if ($fin && $now->greaterThanOrEqualTo($fin)) {
            return 'termine';
        }

        $depart = $this->date_depart
            ? ($this->date_depart instanceof Carbon ? $this->date_depart : Carbon::parse($this->date_depart))
            : null;

        if ($depart && $now->greaterThanOrEqualTo($depart) && $this->statut === 'en attente') {
            return 'en cours';
        }

        return null;
    }

    /**
     * Quand le voyage est terminé : remet le bus et le chauffeur en statut « libre »
     * s'ils n'ont pas un autre voyage en attente ou en cours.
     */
    public function releaseBusAndChauffeurIfIdle(): void
    {
        if ($this->bus_id) {
            $busEncoreUtilise = static::query()
                ->where('bus_id', $this->bus_id)
                ->where('id', '!=', $this->id)
                ->whereIn('statut', ['en attente', 'en cours'])
                ->exists();

            if (! $busEncoreUtilise) {
                $bus = Bus::query()->find($this->bus_id);
                if ($bus && in_array($bus->statut, ['indisponible', 'en voyage'], true)) {
                    $bus->update(['statut' => 'disponible']);
                }
            }
        }

        if ($this->chauffeur_id) {
            $chauffeurEncoreEnMission = static::query()
                ->where('chauffeur_id', $this->chauffeur_id)
                ->where('id', '!=', $this->id)
                ->whereIn('statut', ['en attente', 'en cours'])
                ->exists();

            if (! $chauffeurEncoreEnMission) {
                $chauffeur = User::query()->find($this->chauffeur_id);
                if ($chauffeur && $chauffeur->role_user === 'CHAUFFEUR' && $chauffeur->statut === 'innactif') {
                    $chauffeur->update(['statut' => 'actif']);
                }
            }
        }
    }

    public function depart()
    {
        return $this->belongsTo(Gare::class, 'depart');
    }

    public function arrivee()
    {
        return $this->belongsTo(Gare::class, 'arrivee');
    }

    public function chauffeur()
    {
        return $this->belongsTo(User::class);
    }

    public function trajet()
    {
        return $this->belongsTo(Trajet::class);
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function gare()
    {
        return $this->belongsTo(Gare::class);
    }
}

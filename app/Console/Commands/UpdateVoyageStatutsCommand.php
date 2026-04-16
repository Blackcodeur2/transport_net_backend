<?php

namespace App\Console\Commands;

use App\Models\Voyage;
use Illuminate\Console\Command;

class UpdateVoyageStatutsCommand extends Command
{
    protected $signature = 'voyages:update-statuts';

    protected $description = 'Met à jour les statuts des voyages selon les dates de départ et d\'arrivée.';

    public function handle(): int
    {
        $updated = 0;

        Voyage::query()
            ->with('trajet')
            ->where('statut', '!=', 'annule')
            ->chunkById(100, function ($voyages) use (&$updated) {
                foreach ($voyages as $voyage) {
                    $newStatut = $voyage->resolveStatutAutomatique();

                    if ($newStatut !== null && $newStatut !== $voyage->statut) {
                        $voyage->update(['statut' => $newStatut]);
                        $updated++;
                    }
                }
            });

        if ($updated === 0) {
            $this->info('Aucun voyage à mettre à jour (statuts déjà cohérents avec les dates).');
        } else {
            $this->info("{$updated} voyage(s) mis à jour.");
        }

        return self::SUCCESS;
    }
}

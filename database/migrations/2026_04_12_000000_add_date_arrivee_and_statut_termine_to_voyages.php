<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            if (! Schema::hasColumn('voyages', 'date_arrivee')) {
                $table->dateTime('date_arrivee')->nullable()->after('date_depart');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE voyages MODIFY COLUMN statut ENUM('en attente', 'en cours', 'annule', 'termine') NOT NULL DEFAULT 'en attente'");
        }
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            if (Schema::hasColumn('voyages', 'date_arrivee')) {
                $table->dropColumn('date_arrivee');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::table('voyages')->where('statut', 'termine')->update(['statut' => 'en cours']);
            DB::statement("ALTER TABLE voyages MODIFY COLUMN statut ENUM('en attente', 'en cours', 'annule') NOT NULL DEFAULT 'en attente'");
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('voyages', function (Blueprint $table) {
            $table->id();
            $table->string('num_voyage')->unique();
            $table->foreignId('gare_id')->nullable()->constrained('gares','id')->nullOnDelete();
            $table->foreignId('trajet_id')->nullable()->constrained('trajets','id')->nullOnDelete();
            $table->foreignId('bus_id')->nullable()->constrained('buses','id')->nullOnDelete();
             $table->foreignId('chauffeur_id')->nullable()->constrained('users','id')->nullOnDelete();
            $table->decimal('prix');
            $table->enum('statut', ['en attente', 'en cours', 'annule'])->default('en attente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voyages');
    }
};

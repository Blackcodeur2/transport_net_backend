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
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('gare_id')->nullable()->constrained('gares','id')->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations','id')->nullOnDelete();
            $table->decimal('montant');
            $table->enum('statut', ['en attente', 'validee', 'annule'])->default('en attente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};

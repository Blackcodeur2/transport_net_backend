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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('num_reservation')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users','id')->nullOnDelete();
            $table->foreignId('gare_id')->nullable()->constrained('gares','id')->nullOnDelete();
            $table->foreignId('voyage_id')->nullable()->constrained('voyages','id')->nullOnDelete();
            $table->integer('place');
            $table->decimal('prix');
            $table->enum('statut', ['en attente', 'validee', 'annule'])->default('en attente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};

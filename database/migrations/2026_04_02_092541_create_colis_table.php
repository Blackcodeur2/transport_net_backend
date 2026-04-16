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
        Schema::create('colis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users','id')->cascadeOnDelete();
            $table->string('nom_colis');
            $table->string('chemin_image');
            $table->string('tel_destinataire');
            $table->string('nom_destinataire');
            $table->foreignId('provenance')->constrained('gares','id')->cascadeOnDelete();
            $table->foreignId('destination')->constrained('gares','id')->cascadeOnDelete();
            $table->boolean('visible')->default(true);
            $table->enum('statut',['en attente','retire'])->default('en attente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colis');
    }
};

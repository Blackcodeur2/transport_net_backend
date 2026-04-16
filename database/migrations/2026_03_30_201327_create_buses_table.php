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
        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gare_id')->nullable()->constrained('gares','id')->nullOnDelete();
            $table->string('immatriculation')->unique();
            $table->enum('modele',['coaster','gros porteur'])->default('coaster');
            $table->integer('nb_places');
            $table->string('code_bus');
            $table->enum('type', ['classique','vip'])->default('classique');
            $table->enum('statut', ['disponible','en voyage','en maintenance','indisponible'])->default('disponible');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buses');
    }
};

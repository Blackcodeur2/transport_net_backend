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
        Schema::create('trajets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gare_id')->nullable()->constrained('gares','id')->nullOnDelete();
            $table->foreignId('depart')->constrained('gares','id')->cascadeOnDelete();
            $table->foreignId('arrive')->constrained('gares','id')->cascadeOnDelete();
            $table->decimal('distance_km')->nullable();
            $table->integer('duree_heure')->nullable();
            $table->enum('type_trajet',['vip','classique'])->default('classique');
            $table->decimal('prix');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trajets');
    }
};

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

        Schema::create('gares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agence_id')->constrained('agences','id')->cascadeOnDelete();
            $table->string('nom');
            $table->string('adresse');
            $table->string('telephone')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('gare_id')->nullable()->constrained('gares','id')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gares');
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};

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
        Schema::table('trajets', function (Blueprint $table) {
           // $table->renameColumn('depart', 'depart_id');
            $table->renameColumn('arrive', 'arrivee_id');
        });

        Schema::table('trajets', function (Blueprint $table) {

            // 🔗 ajouter les contraintes de clé étrangère
            /*$table->foreign('depart_id')
                ->references('id')
                ->on('gares')
                ->cascadeOnDelete();
*/
            $table->foreign('arrivee_id')
                ->references('id')
                ->on('gares')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trajets', function (Blueprint $table) {
            /*$table->dropForeign(['depart_id']);
            $table->dropForeign(['arrivee_id']);

            $table->renameColumn('depart_id', 'depart');
            $table->renameColumn('arrivee_id', 'arrive');*/
             $table->dropForeign(['arrivee_id']);
             $table->renameColumn('arrivee_id', 'arrive');
        });
    }
};

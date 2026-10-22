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
        Schema::create('demande_activites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('demande_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('activite_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['demande_id', 'activite_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demande_activites');
    }
};

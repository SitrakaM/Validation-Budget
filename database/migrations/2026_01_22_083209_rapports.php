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
        Schema::create('rapports', function (Blueprint $table) {
            $table->id();
            $table->json('fichier')->nullable();
            $table->enum('statut', ['valide', 'refuse', 'en_cours', 'correction','non_soumis'])->default('non_soumis');
            $table->boolean('physical')->default(false);

            $table->foreignId('type_rapport_id')->constrained();
            $table->foreignId('mission_id')->constrained();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

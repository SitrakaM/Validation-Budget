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
        Schema::create('demandes', function (Blueprint $table) {
            $table->id();
            $table->json('fichier');
            $table->enum('statut', ['valide', 'refuse', 'en_cours', 'correction'])->default('en_cours');
            $table->boolean('validated_by_special')->default(false);

            $table->foreignId('type_demande_id')->constrained();
            $table->foreignId('site_id')->constrained();
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

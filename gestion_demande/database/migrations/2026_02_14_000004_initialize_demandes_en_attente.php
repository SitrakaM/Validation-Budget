<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Demande;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Initialize demandes to 'en_attente' (new initial status)
        Demande::query()->update(['statut' => 'en_attente']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset to 'en_cours'
        Demande::query()->update(['statut' => 'en_cours']);
    }
};

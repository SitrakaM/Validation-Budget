<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Mission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset all existing missions to 'en_attente'
        Mission::query()->update(['status' => 'en_attente']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed
    }
};

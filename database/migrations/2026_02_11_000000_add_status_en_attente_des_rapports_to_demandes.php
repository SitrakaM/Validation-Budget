<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL: Drop the old constraint and add new one
        if (DB::connection()->getDriverName() === 'pgsql') {
            // Find and drop the old check constraint
            DB::statement(
                "ALTER TABLE demandes DROP CONSTRAINT IF EXISTS demandes_statut_check"
            );
            
            // Add new check constraint with the new status value
            DB::statement(
                "ALTER TABLE demandes ADD CONSTRAINT demandes_statut_check CHECK (statut IN ('valide', 'refuse', 'en_cours', 'correction', 'en attente des rapports'))"
            );
        } else {
            // For MySQL use the enum change method
            Schema::table('demandes', function (Blueprint $table) {
                $table->enum('statut', ['valide', 'refuse', 'en_cours', 'correction', 'en attente des rapports'])
                    ->default('en_cours')
                    ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            // Drop the new constraint and restore the old one
            DB::statement(
                "ALTER TABLE demandes DROP CONSTRAINT IF EXISTS demandes_statut_check"
            );
            
            DB::statement(
                "ALTER TABLE demandes ADD CONSTRAINT demandes_statut_check CHECK (statut IN ('valide', 'refuse', 'en_cours', 'correction'))"
            );
        } else {
            Schema::table('demandes', function (Blueprint $table) {
                $table->enum('statut', ['valide', 'refuse', 'en_cours', 'correction'])
                    ->default('en_cours')
                    ->change();
            });
        }
    }
};

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
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'pgsql') {
            // For PostgreSQL: Drop the old constraint and add new one
            DB::statement('ALTER TABLE rapports DROP CONSTRAINT IF EXISTS rapports_statut_check');
            
            // Add new check constraint with 'en_attente'
            DB::statement(
                "ALTER TABLE rapports ADD CONSTRAINT rapports_statut_check CHECK (statut IN ('en_attente', 'valide', 'refuse', 'en_cours', 'correction', 'non_soumis'))"
            );
        } else {
            // For MySQL use the enum change method
            Schema::table('rapports', function (Blueprint $table) {
                $table->enum('statut', ['en_attente', 'valide', 'refuse', 'en_cours', 'correction', 'non_soumis'])
                    ->default('non_soumis')
                    ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE rapports DROP CONSTRAINT IF EXISTS rapports_statut_check');
            DB::statement(
                "ALTER TABLE rapports ADD CONSTRAINT rapports_statut_check CHECK (statut IN ('valide', 'refuse', 'en_cours', 'correction', 'non_soumis'))"
            );
        } else {
            Schema::table('rapports', function (Blueprint $table) {
                $table->enum('statut', ['valide', 'refuse', 'en_cours', 'correction', 'non_soumis'])
                    ->default('non_soumis')
                    ->change();
            });
        }
    }
};

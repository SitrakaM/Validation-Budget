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
            DB::statement('ALTER TABLE demandes DROP CONSTRAINT IF EXISTS demandes_statut_check');
            
            // Add new check constraint with 'en_attente'
            DB::statement(
                "ALTER TABLE demandes ADD CONSTRAINT demandes_statut_check CHECK (statut IN ('en_attente', 'valide', 'refuse', 'en_cours', 'correction', 'en attente des rapports'))"
            );
            
            // Update default value
            DB::statement("ALTER TABLE demandes ALTER COLUMN statut SET DEFAULT 'en_attente'");
        } else {
            // For MySQL use the enum change method
            Schema::table('demandes', function (Blueprint $table) {
                $table->enum('statut', ['en_attente', 'valide', 'refuse', 'en_cours', 'correction', 'en attente des rapports'])
                    ->default('en_attente')
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
            DB::statement('ALTER TABLE demandes DROP CONSTRAINT IF EXISTS demandes_statut_check');
            DB::statement(
                "ALTER TABLE demandes ADD CONSTRAINT demandes_statut_check CHECK (statut IN ('valide', 'refuse', 'en_cours', 'correction', 'en attente des rapports'))"
            );
            DB::statement("ALTER TABLE demandes ALTER COLUMN statut SET DEFAULT 'en_cours'");
        } else {
            Schema::table('demandes', function (Blueprint $table) {
                $table->enum('statut', ['valide', 'refuse', 'en_cours', 'correction', 'en attente des rapports'])
                    ->default('en_cours')
                    ->change();
            });
        }
    }
};

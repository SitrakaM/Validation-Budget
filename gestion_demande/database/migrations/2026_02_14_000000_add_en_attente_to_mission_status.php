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
            DB::statement('ALTER TABLE missions DROP CONSTRAINT IF EXISTS missions_status_check');
            
            // Add new check constraint with the new status value
            DB::statement(
                "ALTER TABLE missions ADD CONSTRAINT missions_status_check CHECK (status IN ('en_attente', 'en_cours', 'valide'))"
            );
            
            // Update default value
            DB::statement("ALTER TABLE missions ALTER COLUMN status SET DEFAULT 'en_attente'");
        } else {
            // For MySQL use the enum change method
            Schema::table('missions', function (Blueprint $table) {
                $table->enum('status', ['en_attente', 'en_cours', 'valide'])
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
            DB::statement('ALTER TABLE missions DROP CONSTRAINT IF EXISTS missions_status_check');
            DB::statement(
                "ALTER TABLE missions ADD CONSTRAINT missions_status_check CHECK (status IN ('en_cours', 'valide'))"
            );
            DB::statement("ALTER TABLE missions ALTER COLUMN status SET DEFAULT 'en_cours'");
        } else {
            Schema::table('missions', function (Blueprint $table) {
                $table->enum('status', ['en_cours', 'valide'])
                    ->default('en_cours')
                    ->change();
            });
        }
    }
};

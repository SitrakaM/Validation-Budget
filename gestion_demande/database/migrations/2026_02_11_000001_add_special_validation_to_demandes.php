<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            if (!Schema::hasColumn('demandes', 'motif_special')) {
                $table->string('motif_special')->nullable(); // Stockage du chemin du fichier
            }
            // validated_by_special déjà existant, ne pas le rajouter
        });
    }

    public function down(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->dropColumn(['validated_by_special', 'motif_special']);
        });
    }
};

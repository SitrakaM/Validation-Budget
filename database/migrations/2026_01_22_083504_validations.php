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
        Schema::create('validations', function (Blueprint $table) {
            $table->id();

            $table->morphs('validable');

            $table->foreignId('id_validateur')->constrained('users');
            $table->enum('decision', ['valide', 'correction', 'rejete', 'en_cours']);

            $table->json('motif_special')->nullable();
            $table->boolean('is_special')->default(false);
            $table->text('commentaire')->nullable();
            $table->json('fichier_commentaire')->nullable();

            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('validations', function (Blueprint $table) {
            $table->dropForeign(['id_validateur']);
        });
        Schema::dropIfExists('validations');
    }
};

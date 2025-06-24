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
            $table->string('titre');
            $table->json('url')->nullable();
            $table->json('motifSpecial')->nullable();
            $table->json('motifVoitureRevision')->nullable();
            $table->string('voitureCommentaire')->nullable();

            $table->enum('statut',['en_attente','valide','revision','changer'])->default('en_attente');
            $table->boolean('is_new')->default(true);    

            $table->boolean('sortie')->default(false);    


            $table->foreignIdFor(\App\Models\ObjetDemande::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\User::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\Activite::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\Site::class)->nullable()->constrained()->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};

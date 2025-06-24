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
        Schema::create('rapports', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->enum('statut',['en_attente','valide'])->default('en_attente');
            $table->json('url')->nullable();
            $table->boolean('is_new')->default(true);    

            $table->foreignIdFor(\App\Models\ObjetRapport::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\Demande::class)->nullable()->constrained()->cascadeOnDelete();
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
        Schema::dropIfExists('rapports');
    }
};

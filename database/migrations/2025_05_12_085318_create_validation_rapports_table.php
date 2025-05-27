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
        Schema::create('validation_rapports', function (Blueprint $table) {
            $table->id();
            $table->string('commentaire')->nullable();
            $table->json('motifRetour')->nullable();
            $table->enum('estValid',['en_attente','revision','valide'])->default('en_attente');    
            $table->foreignIdFor(\App\Models\User::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\Rapport::class)->nullable()->constrained()->cascadeOnDelete();
            // $table->primary(['user_id','rapport_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validation_rapports');
    }
};

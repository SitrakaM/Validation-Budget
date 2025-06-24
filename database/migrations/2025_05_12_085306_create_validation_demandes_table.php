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
        Schema::create('validation_demandes', function (Blueprint $table) {
            $table->id();
            $table->string('commentaire')->nullable();
            $table->json('motifRetour')->nullable();
            $table->enum('estValid',['en_attente','revision','valide','changer'])->default('en_attente');    
            $table->boolean('is_new')->default(true);    

            $table->foreignIdFor(\App\Models\User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\Demande::class)->constrained()->cascadeOnDelete();

            $table->timestamps();
            // $table->primary(['user_id','demande_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validation_demandes');
    }
};

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
        Schema::table('users', function(Blueprint $table){
            
            $table->foreignIdFor(\App\Models\Poste::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\Role::class)->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('is_new')->default(true);    

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

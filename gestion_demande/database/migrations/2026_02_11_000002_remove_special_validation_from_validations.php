<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('validations', function (Blueprint $table) {
            $table->dropColumn(['is_special', 'motif_special']);
        });
    }

    public function down(): void
    {
        Schema::table('validations', function (Blueprint $table) {
            $table->boolean('is_special')->default(false);
            $table->string('motif_special')->nullable();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->foreignId('area_id')
                ->nullable()
                ->after('party_id')
                ->constrained('areas')
                ->nullOnDelete();

            $table->index(['area_id', 'party_id']);
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex(['area_id', 'party_id']);
            $table->dropConstrainedForeignId('area_id');
        });
    }
};

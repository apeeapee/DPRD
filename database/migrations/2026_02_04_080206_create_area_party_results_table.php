<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('area_party_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_year_id')->constrained('election_years')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();

            $table->unsignedBigInteger('votes')->default(0);
            $table->timestamps();

            $table->unique(['election_year_id', 'area_id', 'party_id']);
            $table->index(['area_id', 'election_year_id']);
            $table->index(['party_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_party_results');
    }
};

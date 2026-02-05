<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('area_candidate_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_year_id')->constrained('election_years')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();

            $table->unsignedBigInteger('votes')->default(0);
            $table->timestamps();

            $table->unique(['election_year_id', 'area_id', 'candidate_id']);
            $table->index(['area_id', 'election_year_id']);
            $table->index(['candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_candidate_results');
    }
};

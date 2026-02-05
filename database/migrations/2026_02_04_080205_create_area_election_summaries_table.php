<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('area_election_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_year_id')->constrained('election_years')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();

            $table->unsignedBigInteger('registered_voters')->default(0); // DPT
            $table->unsignedBigInteger('votes_cast')->default(0);        // suara masuk
            $table->unsignedBigInteger('valid_votes')->nullable();       // optional
            $table->unsignedBigInteger('invalid_votes')->nullable();     // optional

            $table->timestamps();

            $table->unique(['election_year_id', 'area_id']);
            $table->index(['area_id', 'election_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_election_summaries');
    }
};

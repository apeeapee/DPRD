<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tps_candidate_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_year_id')->constrained('election_years')->cascadeOnDelete();
            $table->foreignId('polling_station_id')->constrained('polling_stations')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->unsignedInteger('votes')->default(0);
            $table->timestamps();

            $table->unique(['election_year_id', 'polling_station_id', 'candidate_id'], 'tps_votes_unique');
            $table->index(['election_year_id', 'polling_station_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tps_candidate_votes');
    }
};

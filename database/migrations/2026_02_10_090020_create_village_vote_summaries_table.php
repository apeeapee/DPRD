<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_vote_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_year_id')->constrained('election_years')->cascadeOnDelete();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->unsignedBigInteger('votes_cast')->default(0);
            $table->timestamps();

            $table->unique(['election_year_id', 'village_id']);
            $table->index(['election_year_id', 'votes_cast']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_vote_summaries');
    }
};

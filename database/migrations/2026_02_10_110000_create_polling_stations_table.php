<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polling_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('villages')->cascadeOnDelete();
            $table->string('code'); // ex: TPS 01
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['village_id', 'code']);
            $table->index(['village_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polling_stations');
    }
};

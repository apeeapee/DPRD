<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('villages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subdistrict_id')->constrained('subdistricts')->cascadeOnDelete();
            $table->string('name')->index();
            $table->timestamps();

            $table->unique(['subdistrict_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('villages');
    }
};

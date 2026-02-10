<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subdistricts', function (Blueprint $table) {
            $table->id();
            $table->string('regency_name')->index();
            $table->string('name')->index();
            $table->timestamps();

            $table->unique(['regency_name', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subdistricts');
    }
};

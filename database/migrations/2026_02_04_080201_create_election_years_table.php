<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('election_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique(); // 2014/2019/2024 dst.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_years');
    }
};

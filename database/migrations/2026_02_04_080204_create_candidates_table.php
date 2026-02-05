<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('number')->nullable(); // no urut
            $table->timestamps();

            $table->index(['party_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique(); // contoh: PDIP, GERINDRA, dll (atau A/B/C)
            $table->string('name');
            $table->string('color', 20)->nullable(); // optional: "#ff0000"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};

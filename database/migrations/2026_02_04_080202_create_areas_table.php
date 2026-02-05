<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();   // kode wilayah internal / kpu
            $table->string('name');
            $table->string('type', 10);            // KAB / KOTA
            $table->jsonb('geojson')->nullable();  // opsional: polygon/feature geojson
            $table->timestamps();

            $table->index(['type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};

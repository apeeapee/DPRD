<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            if (!Schema::hasColumn('areas', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('geojson');
            }
            if (!Schema::hasColumn('areas', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            if (Schema::hasColumn('areas', 'longitude')) {
                $table->dropColumn('longitude');
            }
            if (Schema::hasColumn('areas', 'latitude')) {
                $table->dropColumn('latitude');
            }
        });
    }
};

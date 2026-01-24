<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('re_properties', function (Blueprint $table) {
            if (!Schema::hasColumn('re_properties', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('slug');
            }
            if (!Schema::hasColumn('re_properties', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('re_properties', 'address')) {
                $table->string('address', 500)->nullable()->after('longitude');
            }
        });

        // Add geo index if columns exist and index doesn't
        if (Schema::hasColumn('re_properties', 'latitude') && 
            Schema::hasColumn('re_properties', 'longitude')) {
            $indexExists = DB::select("SHOW INDEX FROM re_properties WHERE Key_name = 're_properties_geo_idx'");
            if (empty($indexExists)) {
                Schema::table('re_properties', function (Blueprint $table) {
                    $table->index(['latitude', 'longitude'], 're_properties_geo_idx');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexExists = DB::select("SHOW INDEX FROM re_properties WHERE Key_name = 're_properties_geo_idx'");
        if (!empty($indexExists)) {
            Schema::table('re_properties', function (Blueprint $table) {
                $table->dropIndex('re_properties_geo_idx');
            });
        }

        Schema::table('re_properties', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('re_properties', 'latitude')) {
                $columnsToDrop[] = 'latitude';
            }
            if (Schema::hasColumn('re_properties', 'longitude')) {
                $columnsToDrop[] = 'longitude';
            }
            if (Schema::hasColumn('re_properties', 'address')) {
                $columnsToDrop[] = 'address';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

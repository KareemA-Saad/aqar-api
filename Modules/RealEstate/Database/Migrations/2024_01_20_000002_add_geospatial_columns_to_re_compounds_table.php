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
     * 
     * Note: The re_compounds table already has latitude, longitude, and address columns
     * defined in the create_re_compounds_table migration. This migration only adds
     * the geo index if the columns exist and the index doesn't.
     */
    public function up(): void
    {
        if (Schema::hasColumn('re_compounds', 'latitude') && 
            Schema::hasColumn('re_compounds', 'longitude')) {
            $indexExists = DB::select("SHOW INDEX FROM re_compounds WHERE Key_name = 're_compounds_geo_idx'");
            if (empty($indexExists)) {
                Schema::table('re_compounds', function (Blueprint $table) {
                    $table->index(['latitude', 'longitude'], 're_compounds_geo_idx');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexExists = DB::select("SHOW INDEX FROM re_compounds WHERE Key_name = 're_compounds_geo_idx'");
        if (!empty($indexExists)) {
            Schema::table('re_compounds', function (Blueprint $table) {
                $table->dropIndex('re_compounds_geo_idx');
            });
        }
    }
};

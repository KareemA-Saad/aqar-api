<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Updates the type enum in re_areas table to match AreaSeed values.
     * Old values: super_area, area, sub_area
     * New values: governorate, city, district, super_area, area, sub_area
     */
    public function up(): void
    {
        // For MySQL, we need to alter the enum column
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE re_areas MODIFY COLUMN type ENUM('governorate', 'city', 'district', 'super_area', 'area', 'sub_area') DEFAULT 'area'");
        } elseif ($driver === 'pgsql') {
            // For PostgreSQL, we need a more complex approach
            DB::statement("ALTER TABLE re_areas ALTER COLUMN type TYPE VARCHAR(20)");
            DB::statement("ALTER TABLE re_areas ADD CONSTRAINT re_areas_type_check CHECK (type IN ('governorate', 'city', 'district', 'super_area', 'area', 'sub_area'))");
        } else {
            // For SQLite, drop and recreate (but preserve data)
            Schema::table('re_areas', function (Blueprint $table) {
                $table->string('type_temp')->nullable();
            });
            
            DB::statement("UPDATE re_areas SET type_temp = type");
            
            Schema::table('re_areas', function (Blueprint $table) {
                $table->dropColumn('type');
            });
            
            Schema::table('re_areas', function (Blueprint $table) {
                $table->enum('type', ['governorate', 'city', 'district', 'super_area', 'area', 'sub_area'])->default('area');
            });
            
            DB::statement("UPDATE re_areas SET type = type_temp");
            
            Schema::table('re_areas', function (Blueprint $table) {
                $table->dropColumn('type_temp');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE re_areas MODIFY COLUMN type ENUM('super_area', 'area', 'sub_area') DEFAULT 'area'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE re_areas DROP CONSTRAINT IF EXISTS re_areas_type_check");
            DB::statement("ALTER TABLE re_areas ALTER COLUMN type TYPE VARCHAR(20)");
            DB::statement("ALTER TABLE re_areas ADD CONSTRAINT re_areas_type_check CHECK (type IN ('super_area', 'area', 'sub_area'))");
        } else {
            Schema::table('re_areas', function (Blueprint $table) {
                $table->string('type_temp')->nullable();
            });
            
            DB::statement("UPDATE re_areas SET type_temp = type");
            
            Schema::table('re_areas', function (Blueprint $table) {
                $table->dropColumn('type');
            });
            
            Schema::table('re_areas', function (Blueprint $table) {
                $table->enum('type', ['super_area', 'area', 'sub_area'])->default('area');
            });
            
            DB::statement("UPDATE re_areas SET type = type_temp WHERE type_temp IN ('super_area', 'area', 'sub_area')");
            
            Schema::table('re_areas', function (Blueprint $table) {
                $table->dropColumn('type_temp');
            });
        }
    }
};

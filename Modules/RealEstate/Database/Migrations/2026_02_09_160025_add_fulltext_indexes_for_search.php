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
     * Add full-text indexes for global search optimization.
     */
    public function up(): void
    {
        // Note: FULLTEXT indexes are only supported in MySQL/MariaDB with InnoDB engine
        // For PostgreSQL, you would use GIN or GiST indexes instead
        
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // Add FULLTEXT indexes for re_properties table
            DB::statement('ALTER TABLE re_properties ADD FULLTEXT INDEX re_properties_title_fulltext (title)');
            DB::statement('ALTER TABLE re_properties ADD FULLTEXT INDEX re_properties_description_fulltext (description)');
            DB::statement('ALTER TABLE re_properties ADD FULLTEXT INDEX re_properties_search_fulltext (title, description)');

            // Add FULLTEXT indexes for re_compounds table (column name is 'title', not 'name')
            DB::statement('ALTER TABLE re_compounds ADD FULLTEXT INDEX re_compounds_title_fulltext (title)');
            DB::statement('ALTER TABLE re_compounds ADD FULLTEXT INDEX re_compounds_description_fulltext (description)');
            DB::statement('ALTER TABLE re_compounds ADD FULLTEXT INDEX re_compounds_search_fulltext (title, description)');

            // Add regular indexes for re_areas table (name is typically short, FULLTEXT might be overkill)
            Schema::table('re_areas', function (Blueprint $table) {
                $table->index('name', 're_areas_name_idx');
            });

            // Add regular indexes for re_developers table
            Schema::table('re_developers', function (Blueprint $table) {
                $table->index('name', 're_developers_name_idx');
            });
        } elseif ($driver === 'pgsql') {
            // PostgreSQL uses GIN indexes for full-text search            // PostgreSQL uses GIN indexes for full-text search            // First, add tsvector columns (if not exists)
            DB::statement("ALTER TABLE re_properties ADD COLUMN IF NOT EXISTS search_vector tsvector");
            DB::statement("ALTER TABLE re_compounds ADD COLUMN IF NOT EXISTS search_vector tsvector");

            // Create GIN indexes on tsvector columns
            DB::statement("CREATE INDEX IF NOT EXISTS re_properties_search_vector_idx ON re_properties USING GIN(search_vector)");
            DB::statement("CREATE INDEX IF NOT EXISTS re_compounds_search_vector_idx ON re_compounds USING GIN(search_vector)");

            // Create triggers to auto-update tsvector columns
            DB::statement("
                CREATE OR REPLACE FUNCTION re_properties_search_vector_update() RETURNS trigger AS $$
                BEGIN
                    NEW.search_vector := to_tsvector('english', COALESCE(NEW.title, '') || ' ' || COALESCE(NEW.description, ''));
                    RETURN NEW;
                END
                $$ LANGUAGE plpgsql;
            ");

            DB::statement("
                CREATE TRIGGER re_properties_search_vector_trigger BEFORE INSERT OR UPDATE
                ON re_properties FOR EACH ROW EXECUTE FUNCTION re_properties_search_vector_update();
            ");

            DB::statement("
                CREATE OR REPLACE FUNCTION re_compounds_search_vector_update() RETURNS trigger AS $$
                BEGIN
                    NEW.search_vector := to_tsvector('english', COALESCE(NEW.title, '') || ' ' || COALESCE(NEW.description, ''));
                    RETURN NEW;
                END
                $$ LANGUAGE plpgsql;
            ");

            DB::statement("
                CREATE TRIGGER re_compounds_search_vector_trigger BEFORE INSERT OR UPDATE
                ON re_compounds FOR EACH ROW EXECUTE FUNCTION re_compounds_search_vector_update();
            ");

            // Add regular indexes for areas and developers
            Schema::table('re_areas', function (Blueprint $table) {
                $table->index('name', 're_areas_name_idx');
            });

            Schema::table('re_developers', function (Blueprint $table) {
                $table->index('name', 're_developers_name_idx');
            });
        } else {
            // For other databases, just add regular indexes
            Schema::table('re_properties', function (Blueprint $table) {
                $table->index('title', 're_properties_title_idx');
            });

            Schema::table('re_compounds', function (Blueprint $table) {
                $table->index('title', 're_compounds_title_idx');
            });

            Schema::table('re_areas', function (Blueprint $table) {
                $table->index('name', 're_areas_name_idx');
            });

            Schema::table('re_developers', function (Blueprint $table) {
                $table->index('name', 're_developers_name_idx');
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
            // Drop FULLTEXT indexes for re_properties
            DB::statement('ALTER TABLE re_properties DROP INDEX re_properties_title_fulltext');
            DB::statement('ALTER TABLE re_properties DROP INDEX re_properties_description_fulltext');
            DB::statement('ALTER TABLE re_properties DROP INDEX re_properties_search_fulltext');

            // Drop FULLTEXT indexes for re_compounds (column name is 'title', not 'name')
            DB::statement('ALTER TABLE re_compounds DROP INDEX re_compounds_title_fulltext');
            DB::statement('ALTER TABLE re_compounds DROP INDEX re_compounds_description_fulltext');
            DB::statement('ALTER TABLE re_compounds DROP INDEX re_compounds_search_fulltext');

            // Drop regular indexes
            Schema::table('re_areas', function (Blueprint $table) {
                $table->dropIndex('re_areas_name_idx');
            });

            Schema::table('re_developers', function (Blueprint $table) {
                $table->dropIndex('re_developers_name_idx');
            });
        } elseif ($driver === 'pgsql') {
            // Drop PostgreSQL triggers and functions
            DB::statement("DROP TRIGGER IF EXISTS re_properties_search_vector_trigger ON re_properties");
            DB::statement("DROP FUNCTION IF EXISTS re_properties_search_vector_update()");
            DB::statement("DROP TRIGGER IF EXISTS re_compounds_search_vector_trigger ON re_compounds");
            DB::statement("DROP FUNCTION IF EXISTS re_compounds_search_vector_update()");

            // Drop GIN indexes
            DB::statement("DROP INDEX IF EXISTS re_properties_search_vector_idx");
            DB::statement("DROP INDEX IF EXISTS re_compounds_search_vector_idx");

            // Drop tsvector columns
            DB::statement("ALTER TABLE re_properties DROP COLUMN IF EXISTS search_vector");
            DB::statement("ALTER TABLE re_compounds DROP COLUMN IF EXISTS search_vector");

            // Drop regular indexes
            Schema::table('re_areas', function (Blueprint $table) {
                $table->dropIndex('re_areas_name_idx');
            });

            Schema::table('re_developers', function (Blueprint $table) {
                $table->dropIndex('re_developers_name_idx');
            });
        } else {
            // Drop regular indexes for other databases
            Schema::table('re_properties', function (Blueprint $table) {
                $table->dropIndex('re_properties_title_idx');
            });

            Schema::table('re_compounds', function (Blueprint $table) {
                $table->dropIndex('re_compounds_title_idx');
            });

            Schema::table('re_areas', function (Blueprint $table) {
                $table->dropIndex('re_areas_name_idx');
            });

            Schema::table('re_developers', function (Blueprint $table) {
                $table->dropIndex('re_developers_name_idx');
            });
        }
    }
};

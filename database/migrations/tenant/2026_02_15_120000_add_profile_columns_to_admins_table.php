<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            if (!Schema::hasColumn('admins', 'company')) {
                $table->string('company')->nullable()->after('mobile');
            }

            if (!Schema::hasColumn('admins', 'address')) {
                $table->text('address')->nullable()->after('company');
            }

            if (!Schema::hasColumn('admins', 'city')) {
                $table->string('city')->nullable()->after('address');
            }

            if (!Schema::hasColumn('admins', 'state')) {
                $table->string('state')->nullable()->after('city');
            }

            if (!Schema::hasColumn('admins', 'country')) {
                $table->string('country', 100)->nullable()->after('state');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $columns = ['company', 'address', 'city', 'state', 'country'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('admins', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

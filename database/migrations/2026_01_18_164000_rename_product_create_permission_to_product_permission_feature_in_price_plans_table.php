<?php

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
        Schema::table('price_plans', function (Blueprint $table) {
            $table->renameColumn('product_create_permission', 'product_permission_feature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_plans', function (Blueprint $table) {
            $table->renameColumn('product_permission_feature', 'product_create_permission');
        });
    }
};

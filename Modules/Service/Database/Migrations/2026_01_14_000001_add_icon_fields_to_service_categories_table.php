<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->string('icon_type')->nullable()->after('title'); // 'class' or 'image'
            $table->string('icon_class')->nullable()->after('icon_type'); // FontAwesome class name
            $table->string('image')->nullable()->after('icon_class'); // Image path
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropColumn(['icon_type', 'icon_class', 'image']);
        });
    }
};

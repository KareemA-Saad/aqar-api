<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('price_plans')) {
            Schema::table('price_plans', function (Blueprint $table) {
                $columns = [
                    'zero_price' => fn () => $table->string('zero_price')->nullable(),
                    'service_permission_feature' => fn () => $table->integer('service_permission_feature')->nullable(),
                    'donation_permission_feature' => fn () => $table->integer('donation_permission_feature')->nullable(),
                    'job_permission_feature' => fn () => $table->integer('job_permission_feature')->nullable(),
                    'event_permission_feature' => fn () => $table->integer('event_permission_feature')->nullable(),
                    'knowledgebase_permission_feature' => fn () => $table->integer('knowledgebase_permission_feature')->nullable(),
                    'product_create_permission' => fn () => $table->integer('product_create_permission')->nullable(),
                    'campaign_create_permission' => fn () => $table->integer('campaign_create_permission')->nullable(),
                    'storage_permission_feature' => fn () => $table->bigInteger('storage_permission_feature')->nullable(),
                    'appointment_permission_feature' => fn () => $table->integer('appointment_permission_feature')->nullable(),
                ];

                foreach ($columns as $name => $adder) {
                    if (!Schema::hasColumn('price_plans', $name)) {
                        $adder();
                    }
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('price_plans')) {
            Schema::table('price_plans', function (Blueprint $table) {
                $columns = [
                    'appointment_permission_feature',
                    'storage_permission_feature',
                    'campaign_create_permission',
                    'product_create_permission',
                    'knowledgebase_permission_feature',
                    'event_permission_feature',
                    'job_permission_feature',
                    'donation_permission_feature',
                    'service_permission_feature',
                    'zero_price',
                ];

                foreach ($columns as $name) {
                    if (Schema::hasColumn('price_plans', $name)) {
                        $table->dropColumn($name);
                    }
                }
            });
        }
    }
};

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCheckinFieldsToEventPaymentLogsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('event_payment_logs')) {
            Schema::table('event_payment_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('event_payment_logs', 'check_in_status')) {
                    $table->boolean('check_in_status')->default(0)->after('status');
                }
                if (!Schema::hasColumn('event_payment_logs', 'check_in_at')) {
                    $table->timestamp('check_in_at')->nullable()->after('check_in_status');
                }
                if (!Schema::hasColumn('event_payment_logs', 'ticket_code')) {
                    $table->string('ticket_code')->unique()->nullable()->after('transaction_id');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('event_payment_logs')) {
            Schema::table('event_payment_logs', function (Blueprint $table) {
                $table->dropColumn(['check_in_status', 'check_in_at', 'ticket_code']);
            });
        }
    }
}

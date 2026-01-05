<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add refund tracking columns to booking_informations table.
     */
    public function up(): void
    {
        Schema::table('booking_informations', function (Blueprint $table) {
            $table->foreignId('cancellation_policy_id')
                ->nullable()
                ->after('payment_type')
                ->constrained('cancellation_policies')
                ->nullOnDelete();
            $table->string('refund_status')
                ->nullable()
                ->after('cancellation_policy_id')
                ->comment('pending, processing, completed, failed, not_applicable');
            $table->decimal('refund_amount', 10, 2)
                ->nullable()
                ->after('refund_status');
            $table->string('refund_transaction_id')
                ->nullable()
                ->after('refund_amount');
            $table->timestamp('refund_processed_at')
                ->nullable()
                ->after('refund_transaction_id');
            $table->timestamp('cancelled_at')
                ->nullable()
                ->after('refund_processed_at');
            $table->text('cancellation_reason')
                ->nullable()
                ->after('cancelled_at');
            $table->time('check_in_time')
                ->nullable()
                ->default('15:00:00')
                ->after('cancellation_reason');
            $table->time('check_out_time')
                ->nullable()
                ->default('11:00:00')
                ->after('check_in_time');
            $table->timestamp('checked_in_at')
                ->nullable()
                ->after('check_out_time');
            $table->timestamp('checked_out_at')
                ->nullable()
                ->after('checked_in_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_informations', function (Blueprint $table) {
            $table->dropForeign(['cancellation_policy_id']);
            $table->dropColumn([
                'cancellation_policy_id',
                'refund_status',
                'refund_amount',
                'refund_transaction_id',
                'refund_processed_at',
                'cancelled_at',
                'cancellation_reason',
                'check_in_time',
                'check_out_time',
                'checked_in_at',
                'checked_out_at',
            ]);
        });
    }
};

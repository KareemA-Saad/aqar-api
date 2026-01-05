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
        Schema::table('hotel_booking_payment_logs', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('hotel_booking_payment_logs', 'type')) {
                $table->string('type')->default('payment')->after('booking_information_id'); // payment, refund
            }
            if (!Schema::hasColumn('hotel_booking_payment_logs', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('payment_gateway');
            }
            if (!Schema::hasColumn('hotel_booking_payment_logs', 'amount')) {
                $table->decimal('amount', 12, 2)->default(0)->after('payment_method');
            }
            if (!Schema::hasColumn('hotel_booking_payment_logs', 'currency')) {
                $table->string('currency', 10)->default('SAR')->after('amount');
            }
            if (!Schema::hasColumn('hotel_booking_payment_logs', 'gateway_response')) {
                $table->json('gateway_response')->nullable()->after('transaction_id');
            }
            if (!Schema::hasColumn('hotel_booking_payment_logs', 'refund_reference')) {
                $table->string('refund_reference')->nullable()->after('gateway_response');
            }
            if (!Schema::hasColumn('hotel_booking_payment_logs', 'notes')) {
                $table->text('notes')->nullable()->after('refund_reference');
            }
            if (!Schema::hasColumn('hotel_booking_payment_logs', 'processed_by')) {
                $table->unsignedBigInteger('processed_by')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('hotel_booking_payment_logs', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('processed_by');
            }

            // Rename booking_information_id to booking_id if needed (for consistency)
            // We'll keep both for backward compatibility
            if (!Schema::hasColumn('hotel_booking_payment_logs', 'booking_id')) {
                $table->unsignedBigInteger('booking_id')->nullable()->after('id');
            }

            // Add indexes
            $table->index('type');
            $table->index('status');
            $table->index('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_booking_payment_logs', function (Blueprint $table) {
            $columns = [
                'type', 'payment_method', 'amount', 'currency', 
                'gateway_response', 'refund_reference', 'notes',
                'processed_by', 'processed_at', 'booking_id'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('hotel_booking_payment_logs', $column)) {
                    $table->dropColumn($column);
                }
            }

            $table->dropIndex(['type']);
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_method']);
        });
    }
};

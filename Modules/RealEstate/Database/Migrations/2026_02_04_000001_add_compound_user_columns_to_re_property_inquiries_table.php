<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds compound_id and user_id columns to support:
     * 1. Direct compound inquiries (users inquiring about entire compound, not specific property)
     * 2. Authenticated user tracking (link inquiry to logged-in user when available)
     */
    public function up(): void
    {
        Schema::table('re_property_inquiries', function (Blueprint $table) {
            // Add compound_id for direct compound inquiries
            // Nullable because inquiry can be for property OR compound (not both)
            $table->foreignId('compound_id')
                ->nullable()
                ->after('property_id')
                ->constrained('re_compounds')
                ->onDelete('cascade');
            
            // Add user_id to track authenticated users who submit inquiries
            // Nullable because guest users can also submit inquiries
            $table->foreignId('user_id')
                ->nullable()
                ->after('agent_id')
                ->constrained('users')
                ->onDelete('set null');
            
            // Add indexes for performance
            $table->index(['compound_id', 'status']);
            $table->index('user_id');
        });
        
        // Add constraint: inquiry must have either property_id OR compound_id (not both, not neither)
        // This is enforced at application level in InquiryService validation
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('re_property_inquiries', function (Blueprint $table) {
            $table->dropForeign(['compound_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['compound_id', 'user_id']);
        });
    }
};

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
     * Creates the re_templates table for F2.4 Canned Response Templates.
     * Templates are admin-managed, bilingual (en/ar), with variable substitution.
     */
    public function up(): void
    {
        Schema::create('re_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable()->index();
            $table->text('body_en');
            $table->text('body_ar');
            $table->json('variables')->nullable()->comment('Array of variable names used: user.name, property.title, etc.');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('re_templates');
    }
};

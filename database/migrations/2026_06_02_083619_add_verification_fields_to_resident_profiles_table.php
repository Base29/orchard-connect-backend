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
        Schema::table('resident_profiles', function (Blueprint $table) {
            $table->string('document_path', 500)->nullable()->after('user_type');
            $table->string('status', 50)->default('pending')->after('document_path');
            $table->string('rejection_reason', 100)->nullable()->after('status');
            $table->text('rejection_message')->nullable()->after('rejection_reason');

            // Add index for fast querying by status
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resident_profiles', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['document_path', 'status', 'rejection_reason', 'rejection_message']);
        });
    }
};

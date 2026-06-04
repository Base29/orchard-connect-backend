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
        Schema::create('listing_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 100);
            $table->text('comment')->nullable();
            $table->timestamps();

            // Ensure unique flag per user per listing
            $table->unique(['user_id', 'listing_id']);
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->integer('flags_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('flags_count');
        });

        Schema::dropIfExists('listing_flags');
    }
};

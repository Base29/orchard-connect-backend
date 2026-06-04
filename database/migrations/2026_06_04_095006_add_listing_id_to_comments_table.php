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
        Schema::table('comments', function (Blueprint $table) {
            // Make post_id nullable so comments can belong to either posts or listings
            $table->uuid('post_id')->nullable()->change();
            
            // Add listing_id foreign key constraint
            $table->foreignUuid('listing_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // Revert listing_id foreign key and column
            $table->dropForeign(['listing_id']);
            $table->dropColumn('listing_id');
            
            // Revert post_id to non-nullable (make sure to clear orphaned comments first if rolling back in production)
            $table->uuid('post_id')->nullable(false)->change();
        });
    }
};

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
        Schema::create('moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 100); // e.g. delete_post, suspend_user, ban_user
            $table->string('target_type', 100); // target model e.g. App\Models\Post
            $table->uuid('target_id'); // UUID of post, listing, or user
            $table->foreignUuid('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->jsonb('metadata')->default('{}'); // save state context before action
            $table->timestamps();

            // Index logs lookup
            $table->index(['target_type', 'target_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moderation_logs');
    }
};

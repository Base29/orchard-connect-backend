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
        Schema::create('polls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->string('status', 50)->default('active'); // active, suspended, closed
            $table->timestamps();

            $table->index(['status', 'start_at', 'end_at']);
        });

        Schema::create('poll_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('poll_id')->constrained('polls')->cascadeOnDelete();
            $table->string('option_text');
            $table->timestamps();
        });

        Schema::create('poll_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('poll_id')->constrained('polls')->cascadeOnDelete();
            $table->foreignUuid('poll_option_id')->constrained('poll_options')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // A resident can only vote once per poll
            $table->unique(['poll_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
        Schema::dropIfExists('poll_options');
        Schema::dropIfExists('polls');
    }
};

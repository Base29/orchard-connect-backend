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
        Schema::create('announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('category', 50)->default('general'); // general, security, maintenance, event
            $table->string('status', 50)->default('published'); // draft, published, archived
            $table->boolean('pinned')->default(false);
            $table->timestamps();

            // Indexing for high-speed announcements query
            $table->index(['status', 'pinned', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};

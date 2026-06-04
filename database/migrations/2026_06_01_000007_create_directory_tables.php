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
        Schema::create('directory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->string('icon', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('directory_listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('category_id')->constrained('directory_categories')->restrictOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->text('address')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->text('logo_url')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            // Directory query index
            $table->index(['category_id', 'is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('directory_listings');
        Schema::dropIfExists('directory_categories');
    }
};

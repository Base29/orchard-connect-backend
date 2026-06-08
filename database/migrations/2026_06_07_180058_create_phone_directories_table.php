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
        Schema::create('phone_directories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('phone_number', 50);
            $table->text('description')->nullable();
            $table->string('category', 100); // e.g. Emergency, Security, Health, Utilities, Administration
            $table->integer('order')->default(0); // Display priority order
            $table->timestamps();

            // Indexing for search & category filters
            $table->index(['category', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_directories');
    }
};

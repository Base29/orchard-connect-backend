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
        Schema::create('listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description');
            $table->decimal('price', 12, 2)->default(0.00);
            $table->string('category', 100); // e.g. Electronics, Vehicles, Property
            $table->jsonb('images')->default('[]'); // image paths in Supabase storage
            $table->string('contact_whatsapp', 50);
            $table->string('status', 50)->default('active'); // active, sold, flagged, suspended
            $table->timestamps();

            // Index listings search and query
            $table->index(['category', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};

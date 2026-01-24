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
     * This table stores the user's personal collection.
     * Links users to media_items with user-specific data.
     */
    public function up(): void
    {
        Schema::create('user_media_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('media_item_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('location_id')->nullable()->constrained()->nullOnDelete();

            // Formats owned (JSON array: ["dvd", "bluray", "digital"])
            $table->json('formats')->nullable();

            // User's personal rating (1-10)
            $table->unsignedTinyInteger('rating')->nullable();

            // User's personal notes
            $table->text('notes')->nullable();

            $table->timestamps();

            // Prevent duplicate entries (user can only have one entry per media item)
            $table->unique(['user_id', 'media_item_id']);

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index('location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_media_items');
    }
};

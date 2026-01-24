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
     * This table stores the shared media catalog.
     * Common media data is stored once and referenced by user_media_items.
     */
    public function up(): void
    {
        Schema::create('media_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Only set for custom/homemade media (null for shared catalog items)
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Media type (movie, tv_show, album, song, etc.)
            $table->string('type', 50);

            // Basic information
            $table->string('title');
            $table->string('original_title')->nullable();
            $table->integer('year')->nullable();
            $table->text('description')->nullable();
            $table->string('poster_url')->nullable();

            // External API reference
            $table->string('external_id')->nullable();
            $table->string('external_source', 50)->nullable(); // omdb, musicbrainz, etc.

            // Custom/homemade media flag
            $table->boolean('is_custom')->default(false);

            // Additional metadata from external APIs (JSON)
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index('title');
            $table->unique(['external_source', 'external_id'], 'media_items_external_unique');
            $table->index('is_custom');
            $table->index('created_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_items');
    }
};

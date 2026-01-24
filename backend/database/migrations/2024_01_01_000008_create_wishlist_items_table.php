<?php

declare(strict_types=1);

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
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('media_item_id')->constrained()->cascadeOnDelete();

            // User's notes
            $table->text('notes')->nullable();

            // Priority (1-5, higher = more wanted)
            $table->unsignedTinyInteger('priority')->default(3);

            $table->timestamps();

            // Unique constraint: a user can only have one wishlist entry per media item
            $table->unique(['user_id', 'media_item_id']);

            // Indexes
            $table->index(['user_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};

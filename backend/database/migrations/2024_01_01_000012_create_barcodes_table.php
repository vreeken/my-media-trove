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
     * Creates the barcodes table for storing barcode-to-media associations.
     * Each row represents one user's vote that a barcode matches a media item.
     * Multiple users can vote for the same barcode-media combination.
     * Same barcode can be associated with multiple media items (user error handling).
     */
    public function up(): void
    {
        Schema::create('barcodes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // The barcode number (UPC-A: 12 digits, EAN-13: 13 digits)
            $table->string('barcode', 20)->index();

            // Type of barcode (upc_a, ean_13, etc.)
            $table->string('barcode_type', 20)->default('upc_a');

            // The media item this barcode is associated with
            $table->foreignUuid('media_item_id')->constrained()->cascadeOnDelete();

            // The user who made this association
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->timestamps();

            // A user can only vote once for a specific barcode-media combination
            $table->unique(['barcode', 'media_item_id', 'user_id'], 'barcode_media_user_unique');

            // Index for efficient lookups
            $table->index(['barcode', 'media_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barcodes');
    }
};

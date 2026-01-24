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
        Schema::create('streaming_availability_cache', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('external_id', 50); // IMDB ID (e.g., tt0068646) or TMDB ID
            $table->string('external_source', 20)->default('imdb'); // imdb, tmdb
            $table->string('country_code', 5); // ISO country code (e.g., 'us', 'gb')
            $table->json('streaming_data'); // The streaming options data
            $table->timestamp('fetched_at'); // When the data was fetched from the API
            $table->timestamps();

            // Unique constraint: one cache entry per media + country combination
            $table->unique(['external_id', 'external_source', 'country_code'], 'streaming_cache_unique');

            // Index for quick lookups
            $table->index(['external_id', 'country_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('streaming_availability_cache');
    }
};

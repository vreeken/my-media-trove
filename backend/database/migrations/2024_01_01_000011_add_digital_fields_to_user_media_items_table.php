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
     * Adds digital vs physical toggle and digital-specific fields.
     */
    public function up(): void
    {
        Schema::table('user_media_items', function (Blueprint $table) {
            // Whether this is a digital copy (true) or physical (false)
            $table->boolean('is_digital')->default(false)->after('notes');

            // For digital items, the platform/store where it's stored
            // Can be a predefined value or reference a user's custom location
            $table->string('digital_platform')->nullable()->after('is_digital');

            // For NAS/local storage, the network path to the file/folder
            $table->string('digital_path', 500)->nullable()->after('digital_platform');

            // Index for filtering
            $table->index('is_digital');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_media_items', function (Blueprint $table) {
            $table->dropIndex(['is_digital']);
            $table->dropColumn(['is_digital', 'digital_platform', 'digital_path']);
        });
    }
};

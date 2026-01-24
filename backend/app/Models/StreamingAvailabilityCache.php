<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StreamingAvailabilityCache extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'streaming_availability_cache';

    protected $fillable = [
        'external_id',
        'external_source',
        'country_code',
        'streaming_data',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'streaming_data' => 'array',
            'fetched_at' => 'datetime',
        ];
    }

    /**
     * Check if the cached data is still fresh.
     */
    public function isFresh(int $ttlDays = 30): bool
    {
        return $this->fetched_at->diffInDays(now()) < $ttlDays;
    }

    /**
     * Get the age of the cache in a human-readable format.
     */
    public function getAgeAttribute(): string
    {
        return $this->fetched_at->diffForHumans();
    }

    /**
     * Get the age in days.
     */
    public function getAgeDaysAttribute(): int
    {
        return (int) $this->fetched_at->diffInDays(now());
    }
}

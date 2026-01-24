<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaType: string
{
    case Movie = 'movie';
    case TvShow = 'tv_show';
    case TvSeason = 'tv_season';
    case TvEpisode = 'tv_episode';
    case Album = 'album';
    case Song = 'song';

    /**
     * Get a human-readable label for the media type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Movie => 'Movie',
            self::TvShow => 'TV Show',
            self::TvSeason => 'TV Season',
            self::TvEpisode => 'TV Episode',
            self::Album => 'Album',
            self::Song => 'Song',
        };
    }

    /**
     * Get the icon name for this media type (for frontend use).
     */
    public function icon(): string
    {
        return match ($this) {
            self::Movie => 'film',
            self::TvShow, self::TvSeason, self::TvEpisode => 'tv',
            self::Album, self::Song => 'music',
        };
    }

    /**
     * Get all available format options for this media type.
     */
    public function availableFormats(): array
    {
        return match ($this) {
            self::Movie, self::TvShow, self::TvSeason, self::TvEpisode => [
                'dvd' => 'DVD',
                'bluray' => 'Blu-ray',
                'bluray_4k' => '4K UHD Blu-ray',
                'vhs' => 'VHS',
                'digital' => 'Digital',
                'laserdisc' => 'LaserDisc',
            ],
            self::Album, self::Song => [
                'cd' => 'CD',
                'vinyl' => 'Vinyl',
                'cassette' => 'Cassette',
                'digital' => 'Digital',
                'minidisc' => 'MiniDisc',
            ],
        };
    }
}

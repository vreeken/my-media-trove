<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaFormat: string
{
    // Video formats
    case Dvd = 'dvd';
    case Bluray = 'bluray';
    case Bluray4k = 'bluray_4k';
    case Vhs = 'vhs';
    case LaserDisc = 'laserdisc';

    // Audio formats
    case Cd = 'cd';
    case Vinyl = 'vinyl';
    case Cassette = 'cassette';
    case MiniDisc = 'minidisc';

    // Universal formats
    case Digital = 'digital';

    /**
     * Get a human-readable label for the format.
     */
    public function label(): string
    {
        return match ($this) {
            self::Dvd => 'DVD',
            self::Bluray => 'Blu-ray',
            self::Bluray4k => '4K UHD Blu-ray',
            self::Vhs => 'VHS',
            self::LaserDisc => 'LaserDisc',
            self::Cd => 'CD',
            self::Vinyl => 'Vinyl',
            self::Cassette => 'Cassette',
            self::MiniDisc => 'MiniDisc',
            self::Digital => 'Digital',
        };
    }

    /**
     * Check if this is a physical format.
     */
    public function isPhysical(): bool
    {
        return $this !== self::Digital;
    }

    /**
     * Get all video formats.
     */
    public static function videoFormats(): array
    {
        return [
            self::Dvd,
            self::Bluray,
            self::Bluray4k,
            self::Vhs,
            self::LaserDisc,
            self::Digital,
        ];
    }

    /**
     * Get all audio formats.
     */
    public static function audioFormats(): array
    {
        return [
            self::Cd,
            self::Vinyl,
            self::Cassette,
            self::MiniDisc,
            self::Digital,
        ];
    }
}

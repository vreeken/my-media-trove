<?php

declare(strict_types=1);

/**
 * Predefined digital platforms for media storage.
 *
 * These are the common digital storefronts and self-hosted options
 * that users can select for their digital media.
 *
 * URL templates use {title} and {year} placeholders that get replaced
 * with URL-encoded values when generating links.
 *
 * Affiliate support:
 * - affiliate_param: The query parameter name for the affiliate ID
 * - affiliate_env: The environment variable name that holds the affiliate ID
 * - Some platforms require the affiliate ID in a specific URL format
 */

return [
    'platforms' => [
        // Major Purchase/Rental Platforms
        'vudu' => [
            'name' => 'Vudu',
            'icon' => 'vudu',
            'category' => 'store',
            'url' => 'https://www.vudu.com/',
            'search_url' => 'https://www.vudu.com/content/movies/search?searchString={title}',
            // Vudu is now Fandango at Home - affiliate handled there
            'affiliate_param' => null,
            'affiliate_env' => null,
        ],
        'itunes' => [
            'name' => 'iTunes / Apple TV',
            'icon' => 'apple',
            'category' => 'store',
            'url' => 'https://tv.apple.com/',
            'search_url' => 'https://tv.apple.com/search?term={title}',
            // Apple Services Performance Partners program
            // Sign up at: https://performance-partners.apple.com/
            'affiliate_param' => 'at',
            'affiliate_env' => 'APPLE_AFFILIATE_TOKEN',
        ],
        'prime_video' => [
            'name' => 'Prime Video',
            'icon' => 'amazon',
            'category' => 'store',
            'url' => 'https://www.amazon.com/Prime-Video/',
            'search_url' => 'https://www.amazon.com/s?k={title}&i=instant-video',
            // Amazon Associates program
            // Sign up at: https://affiliate-program.amazon.com/
            'affiliate_param' => 'tag',
            'affiliate_env' => 'AMAZON_AFFILIATE_TAG',
        ],
        'movies_anywhere' => [
            'name' => 'Movies Anywhere',
            'icon' => 'movies-anywhere',
            'category' => 'store',
            'url' => 'https://moviesanywhere.com/',
            'search_url' => 'https://moviesanywhere.com/search?q={title}',
            // No affiliate program available
            'affiliate_param' => null,
            'affiliate_env' => null,
        ],
        'google_play' => [
            'name' => 'Google Play / YouTube',
            'icon' => 'google-play',
            'category' => 'store',
            'url' => 'https://play.google.com/store/movies',
            'search_url' => 'https://play.google.com/store/search?q={title}&c=movies',
            // Google Play affiliate program discontinued
            'affiliate_param' => null,
            'affiliate_env' => null,
        ],
        'fandango' => [
            'name' => 'Fandango at Home',
            'icon' => 'fandango',
            'category' => 'store',
            'url' => 'https://www.fandangoathome.com/',
            'search_url' => 'https://www.fandangoathome.com/search?q={title}',
            // Fandango Affiliate Program (via CJ Affiliate or similar)
            // Note: May require specific link format from affiliate network
            'affiliate_param' => null,
            'affiliate_env' => 'FANDANGO_AFFILIATE_ID',
        ],
        'microsoft_store' => [
            'name' => 'Microsoft Store',
            'icon' => 'microsoft',
            'category' => 'store',
            'url' => 'https://www.microsoft.com/en-us/store/movies-and-tv',
            'search_url' => 'https://www.microsoft.com/en-us/search/shop/movies?q={title}',
            // Microsoft Affiliate Program (via various networks)
            'affiliate_param' => null,
            'affiliate_env' => 'MICROSOFT_AFFILIATE_ID',
        ],

        // Self-Hosted / Media Servers (no external URLs - these are local)
        'plex' => [
            'name' => 'Plex',
            'icon' => 'plex',
            'category' => 'self_hosted',
            'url' => 'https://app.plex.tv/',
            'search_url' => 'https://app.plex.tv/desktop/#!/search?query={title}',
        ],
        'jellyfin' => [
            'name' => 'Jellyfin',
            'icon' => 'jellyfin',
            'category' => 'self_hosted',
            'url' => null, // User's own server
            'search_url' => null,
        ],
        'emby' => [
            'name' => 'Emby',
            'icon' => 'emby',
            'category' => 'self_hosted',
            'url' => null, // User's own server
            'search_url' => null,
        ],
        'kodi' => [
            'name' => 'Kodi',
            'icon' => 'kodi',
            'category' => 'self_hosted',
            'url' => 'https://kodi.tv/',
            'search_url' => null, // Local app
        ],
        'infuse' => [
            'name' => 'Infuse',
            'icon' => 'infuse',
            'category' => 'self_hosted',
            'url' => 'https://firecore.com/infuse',
            'search_url' => null, // Local app
        ],

        // Local/Network Storage
        'nas' => [
            'name' => 'Network Attached Storage (NAS)',
            'icon' => 'server',
            'category' => 'storage',
            'requires_path' => true,
            'url' => null,
            'search_url' => null,
        ],
        'local_drive' => [
            'name' => 'Local Hard Drive',
            'icon' => 'hard-drive',
            'category' => 'storage',
            'requires_path' => true,
            'url' => null,
            'search_url' => null,
        ],

        // Cloud Storage
        'google_drive' => [
            'name' => 'Google Drive',
            'icon' => 'google-drive',
            'category' => 'cloud',
            'url' => 'https://drive.google.com/',
            'search_url' => 'https://drive.google.com/drive/search?q={title}',
        ],
        'onedrive' => [
            'name' => 'OneDrive',
            'icon' => 'onedrive',
            'category' => 'cloud',
            'url' => 'https://onedrive.live.com/',
            'search_url' => null, // No direct search URL
        ],
        'dropbox' => [
            'name' => 'Dropbox',
            'icon' => 'dropbox',
            'category' => 'cloud',
            'url' => 'https://www.dropbox.com/',
            'search_url' => 'https://www.dropbox.com/search/personal?query={title}',
        ],
        'icloud' => [
            'name' => 'iCloud',
            'icon' => 'apple',
            'category' => 'cloud',
            'url' => 'https://www.icloud.com/iclouddrive',
            'search_url' => null, // No direct search URL
        ],

        // Music-specific (for albums)
        'bandcamp' => [
            'name' => 'Bandcamp',
            'icon' => 'bandcamp',
            'category' => 'music',
            'url' => 'https://bandcamp.com/',
            'search_url' => 'https://bandcamp.com/search?q={title}',
            // Bandcamp has an affiliate program - 10% commission
            // Note: Requires specific affiliate link format from Bandcamp
            'affiliate_param' => null,
            'affiliate_env' => 'BANDCAMP_AFFILIATE_ID',
        ],
        'amazon_music' => [
            'name' => 'Amazon Music',
            'icon' => 'amazon',
            'category' => 'music',
            'url' => 'https://music.amazon.com/',
            'search_url' => 'https://music.amazon.com/search/{title}',
            // Uses same Amazon Associates tag as Prime Video
            'affiliate_param' => 'tag',
            'affiliate_env' => 'AMAZON_AFFILIATE_TAG',
        ],
    ],

    'categories' => [
        'store' => 'Digital Stores',
        'self_hosted' => 'Media Servers',
        'storage' => 'Local/Network Storage',
        'cloud' => 'Cloud Storage',
        'music' => 'Music Platforms',
    ],
];

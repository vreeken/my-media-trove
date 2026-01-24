import type { DigitalPlatform } from '@/types'

/**
 * Generate a search URL for a digital platform with the title filled in.
 *
 * @param platform - The digital platform object
 * @param title - The media title to search for
 * @param year - Optional year for more specific searches
 * @returns The search URL with placeholders replaced, or the platform URL, or null
 */
export function getPlatformSearchUrl(
  platform: DigitalPlatform | null | undefined,
  title: string,
  year?: number | null
): string | null {
  if (!platform) return null

  // If there's a search URL template, use it
  if (platform.search_url) {
    let url = platform.search_url
    url = url.replace('{title}', encodeURIComponent(title))
    url = url.replace('{year}', year ? encodeURIComponent(String(year)) : '')
    return url
  }

  // Fall back to the platform homepage
  if (platform.url) {
    return platform.url
  }

  return null
}

/**
 * Generate a link for a media item based on its digital platform.
 *
 * @param mediaItem - The media item with digital platform info
 * @param platforms - The flat list of all available platforms
 * @returns The best available URL to access this media
 */
export function getMediaPlatformLink(
  mediaItem: {
    is_digital: boolean
    digital_platform?: string
    digital_platform_search_url?: string | null
    digital_platform_url?: string | null
    title: string
    year?: number
  },
  platforms?: DigitalPlatform[]
): string | null {
  if (!mediaItem.is_digital || !mediaItem.digital_platform) {
    return null
  }

  // First, try the pre-computed search URL from the backend
  if (mediaItem.digital_platform_search_url) {
    return mediaItem.digital_platform_search_url
  }

  // Fall back to platform homepage URL
  if (mediaItem.digital_platform_url) {
    return mediaItem.digital_platform_url
  }

  // If we have the platforms list, try to generate it client-side
  if (platforms) {
    const platform = platforms.find(p => p.id === mediaItem.digital_platform)
    if (platform) {
      return getPlatformSearchUrl(platform, mediaItem.title, mediaItem.year)
    }
  }

  return null
}

/**
 * Check if a platform link can be opened (has a valid URL).
 */
export function canOpenPlatformLink(
  mediaItem: {
    is_digital: boolean
    digital_platform?: string
    digital_platform_search_url?: string | null
    digital_platform_url?: string | null
  }
): boolean {
  if (!mediaItem.is_digital || !mediaItem.digital_platform) {
    return false
  }

  return !!(mediaItem.digital_platform_search_url || mediaItem.digital_platform_url)
}

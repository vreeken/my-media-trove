import api from './api'
import type {
  BarcodeLookupResult,
  BoxSetCheckResult,
  UpcItemDbProduct
} from '@/types'

/**
 * UPCitemdb API response structure
 */
interface UpcItemDbResponse {
  code: string
  total: number
  offset: number
  items: UpcItemDbProduct[]
}

/**
 * Barcode service for scanning and looking up barcodes.
 *
 * Uses our internal database first, then falls back to UPCitemdb API.
 * UPCitemdb calls are made from the frontend to use the user's IP
 * for rate limiting distribution.
 */
export const barcodeService = {
  /**
   * Look up a barcode in our internal database.
   */
  async lookupInternal(barcode: string): Promise<BarcodeLookupResult> {
    const response = await api.get<{ data: BarcodeLookupResult }>(`/barcodes/${barcode}`)
    return response.data.data
  },

  /**
   * Look up a barcode using UPCitemdb API (called from frontend).
   * This uses the user's IP for rate limiting.
   *
   * Free tier: 100 requests/day per IP
   */
  async lookupUpcItemDb(barcode: string): Promise<UpcItemDbProduct | null> {
    try {
      // UPCitemdb API endpoint (no API key needed for trial)
      const response = await fetch(
        `https://api.upcitemdb.com/prod/trial/lookup?upc=${encodeURIComponent(barcode)}`,
        {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
          },
        }
      )

      if (!response.ok) {
        if (response.status === 429) {
          console.warn('UPCitemdb rate limit exceeded')
          return null
        }
        throw new Error(`UPCitemdb API error: ${response.status}`)
      }

      const data: UpcItemDbResponse = await response.json()

      if (data.items && data.items.length > 0) {
        return data.items[0]
      }

      return null
    } catch (error) {
      console.error('UPCitemdb lookup failed:', error)
      return null
    }
  },

  /**
   * Create a barcode-to-media association.
   */
  async createAssociation(barcode: string, mediaItemId: string): Promise<void> {
    await api.post('/barcodes', {
      barcode,
      media_item_id: mediaItemId,
    })
  },

  /**
   * Report a barcode association as incorrect.
   */
  async reportIncorrect(
    barcode: string,
    incorrectMediaItemId: string,
    correctMediaItemId?: string
  ): Promise<void> {
    await api.post('/barcodes/report-incorrect', {
      barcode,
      incorrect_media_item_id: incorrectMediaItemId,
      correct_media_item_id: correctMediaItemId,
    })
  },

  /**
   * Check if a product title suggests it's a box set.
   */
  async checkBoxSet(title: string): Promise<BoxSetCheckResult> {
    const response = await api.post<{ data: BoxSetCheckResult }>('/barcodes/check-box-set', {
      title,
    })
    return response.data.data
  },

  /**
   * Detect format from UPCitemdb product title.
   */
  detectFormatFromTitle(title: string): string | null {
    const lowerTitle = title.toLowerCase()

    if (lowerTitle.includes('4k') || lowerTitle.includes('uhd')) {
      return '4k_uhd'
    }
    if (lowerTitle.includes('blu-ray') || lowerTitle.includes('bluray') || lowerTitle.includes('blu ray')) {
      return 'bluray'
    }
    if (lowerTitle.includes('dvd')) {
      return 'dvd'
    }
    if (lowerTitle.includes('vhs')) {
      return 'vhs'
    }
    if (lowerTitle.includes('digital')) {
      return 'digital'
    }

    return null
  },

  /**
   * Extract a cleaner title from UPCitemdb product for OMDB search.
   * Removes format info, edition info, etc.
   */
  extractSearchTitle(productTitle: string): string {
    let title = productTitle

    // Remove common suffixes/formats
    const patterns = [
      /\s*[\(\[].*?[\)\]]/gi, // Parenthetical content
      /\s*(blu-?ray|dvd|4k|uhd|hd|digital|vhs)/gi,
      /\s*(extended|special|collector'?s?|anniversary|remastered|unrated|director'?s?\s*cut)\s*(edition)?/gi,
      /\s*(widescreen|fullscreen|pan\s*&?\s*scan)/gi,
      /\s*(2-?disc|3-?disc|\d+-?disc)/gi,
      /\s*(steelbook|slipcover|digibook)/gi,
    ]

    for (const pattern of patterns) {
      title = title.replace(pattern, '')
    }

    return title.trim()
  },

  /**
   * Check if the device has a camera.
   */
  async hasCamera(): Promise<boolean> {
    try {
      if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
        return false
      }

      const devices = await navigator.mediaDevices.enumerateDevices()
      return devices.some(device => device.kind === 'videoinput')
    } catch {
      return false
    }
  },

  /**
   * Check if the device is mobile.
   */
  isMobile(): boolean {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    )
  },

  /**
   * Check if barcode scanning should be available.
   * Only on mobile devices with cameras.
   */
  async isAvailable(): Promise<boolean> {
    if (!this.isMobile()) {
      return false
    }
    return this.hasCamera()
  },
}

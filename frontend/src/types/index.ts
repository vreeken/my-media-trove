// User types
export interface User {
  id: string
  name: string
  email: string
  avatar?: string
  email_verified_at?: string
  created_at: string
}

export interface LoginCredentials {
  email: string
  password: string
  remember?: boolean
}

export interface RegisterData {
  name: string
  email: string
  password: string
  password_confirmation: string
}

// Media types
export interface MediaItem {
  id: string
  type: string
  type_label: string
  type_icon: string
  title: string
  original_title?: string
  year?: number
  description?: string
  poster_url?: string
  external_id?: string
  external_source?: string
  is_custom: boolean
  formats: string[]
  available_formats: Record<string, string>
  rating?: number
  notes?: string
  metadata: Record<string, unknown>
  location?: Location
  location_id?: string
  tags: Tag[]
  created_at: string
  updated_at: string
  media_item_id?: string // Reference to shared media catalog

  // Digital vs Physical
  is_digital: boolean
  digital_platform?: string
  digital_platform_name?: string
  digital_platform_url?: string | null
  digital_platform_search_url?: string | null
  digital_path?: string
}

export interface DigitalPlatform {
  id: string
  name: string
  icon?: string
  category?: string
  requires_path?: boolean
  url?: string | null
  search_url?: string | null
}

export interface DigitalPlatformCategory {
  label: string
  platforms: DigitalPlatform[]
}

export interface DigitalPlatformsResponse {
  grouped: Record<string, DigitalPlatformCategory>
  flat: DigitalPlatform[]
}

export interface MediaType {
  value: string
  label: string
  icon: string
  formats: Record<string, string>
}

export interface MediaSearchResult {
  id?: string // MediaItem ID from our database (if cached)
  external_id: string
  external_source: string
  title: string
  year?: number
  type: string
  poster_url?: string
  description?: string
  metadata?: Record<string, unknown>
}

export interface MediaItemFilters {
  type?: string
  is_custom?: boolean
  location_id?: string
  tag_id?: string
  min_rating?: number
  search?: string
  sort_by?: string
  sort_dir?: 'asc' | 'desc'
  page?: number
  per_page?: number
}

// Tag types
export interface Tag {
  id: string
  name: string
  slug: string
  color?: string
  is_system: boolean
  media_items_count?: number
}

// Location types
export interface Location {
  id: string
  name: string
  description?: string
  media_items_count?: number
  created_at: string
}

// Wishlist types
export interface WishlistItem {
  id: string
  type: string
  type_label: string
  title: string
  year?: number
  poster_url?: string
  external_id?: string
  external_source?: string
  description?: string
  notes?: string
  priority: number
  metadata: Record<string, unknown>
  created_at: string
  updated_at: string
}

// Streaming availability types
export interface StreamingOption {
  service: string
  service_name: string
  service_logo?: string
  service_color?: string
  link: string
  video_link?: string
  quality?: string
  price?: string
  currency?: string
  price_formatted?: string
  affiliate_link?: string
  expires_soon?: boolean
  expires_on?: string
  available_since?: string
  addon?: {
    id: string
    name: string
  }
}

export interface StreamingAvailability {
  configured?: boolean
  cached?: {
    fetched_at: string
    age: string
    is_stale: boolean
  }
  country?: string
  error?: boolean
  message?: string
  title?: string
  year?: number
  overview?: string
  poster_url?: string
  streaming?: StreamingOption[]
  rent?: StreamingOption[]
  buy?: StreamingOption[]
  free?: StreamingOption[]
  mock_data?: {
    streaming: StreamingOption[]
    rent: StreamingOption[]
    buy: StreamingOption[]
    free?: StreamingOption[]
  }
}

// Pagination types
export interface PaginationMeta {
  current_page: number
  from: number
  last_page: number
  per_page: number
  to: number
  total: number
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginationMeta
  links: {
    first: string
    last: string
    prev?: string
    next?: string
  }
}

// Form types
export interface FormError {
  [key: string]: string[]
}

// Barcode types
export interface BarcodeMatch {
  media_item: {
    id: string
    type: string
    type_label: string
    title: string
    year?: number
    poster_url?: string
    external_id?: string
    external_source?: string
  }
  vote_count: number
}

export interface BarcodeLookupResult {
  found: boolean
  barcode: string
  barcode_type: string
  matches: BarcodeMatch[]
  high_confidence_match: BarcodeMatch | null
}

export interface BoxSetCheckResult {
  is_box_set: boolean
  franchise_name: string | null
  original_title: string
}

export interface UpcItemDbProduct {
  ean: string
  title: string
  description?: string
  brand?: string
  model?: string
  color?: string
  size?: string
  dimension?: string
  weight?: string
  category?: string
  currency?: string
  lowest_recorded_price?: number
  highest_recorded_price?: number
  images?: string[]
}

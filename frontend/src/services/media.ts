import api from './api'
import type {
  MediaItem,
  MediaType,
  MediaSearchResult,
  PaginatedResponse,
  MediaItemFilters,
  StreamingAvailability,
  DigitalPlatformsResponse
} from '@/types'

export const mediaService = {
  // Collection management
  async getCollection(filters?: MediaItemFilters): Promise<PaginatedResponse<MediaItem>> {
    const response = await api.get<PaginatedResponse<MediaItem>>('/media', { params: filters })
    return response.data
  },

  async getMediaItem(id: string): Promise<MediaItem> {
    const response = await api.get<{ data: MediaItem }>(`/media/${id}`)
    return response.data.data
  },

  async createMediaItem(data: Partial<MediaItem>): Promise<MediaItem> {
    const response = await api.post<{ data: MediaItem }>('/media', data)
    return response.data.data
  },

  async updateMediaItem(id: string, data: Partial<MediaItem>): Promise<MediaItem> {
    const response = await api.put<{ data: MediaItem }>(`/media/${id}`, data)
    return response.data.data
  },

  async deleteMediaItem(id: string): Promise<void> {
    await api.delete(`/media/${id}`)
  },

  async getTypes(): Promise<MediaType[]> {
    const response = await api.get<{ data: MediaType[] }>('/media/types')
    return response.data.data
  },

  async getStats(): Promise<{
    total: number
    by_type: Record<string, { count: number; label: string }>
    custom_count: number
  }> {
    const response = await api.get('/media/stats')
    return response.data.data
  },

  async getDigitalPlatforms(): Promise<DigitalPlatformsResponse> {
    const response = await api.get<{ data: DigitalPlatformsResponse }>('/media/digital-platforms')
    return response.data.data
  },

  // Search external APIs
  async searchMovies(query: string, type?: 'movie' | 'series', year?: number): Promise<MediaSearchResult[]> {
    const response = await api.get<{ data: MediaSearchResult[] }>('/search/movies', {
      params: { query, type, year }
    })
    return response.data.data
  },

  async getMovieDetails(imdbId: string): Promise<MediaSearchResult> {
    const response = await api.get<{ data: MediaSearchResult }>(`/search/movies/${imdbId}`)
    return response.data.data
  },

  async searchAlbums(query: string): Promise<MediaSearchResult[]> {
    const response = await api.get<{ data: MediaSearchResult[] }>('/search/albums', {
      params: { query }
    })
    return response.data.data
  },

  async getAlbumDetails(mbid: string): Promise<MediaSearchResult> {
    const response = await api.get<{ data: MediaSearchResult }>(`/search/albums/${mbid}`)
    return response.data.data
  },

  async searchSongs(query: string): Promise<MediaSearchResult[]> {
    const response = await api.get<{ data: MediaSearchResult[] }>('/search/songs', {
      params: { query }
    })
    return response.data.data
  },

  async searchAll(query: string, types?: string[]): Promise<{
    movies_tv?: MediaSearchResult[]
    albums?: MediaSearchResult[]
    songs?: MediaSearchResult[]
  }> {
    const response = await api.get('/search/all', {
      params: { query, types }
    })
    return response.data.data
  },

  async getStreamingAvailability(imdbId: string, country?: string): Promise<StreamingAvailability> {
    const response = await api.get<{ data: StreamingAvailability }>(`/search/streaming/${imdbId}`, {
      params: { country }
    })
    return response.data.data
  },

  async refreshStreamingAvailability(imdbId: string, country?: string): Promise<StreamingAvailability> {
    const response = await api.post<{ data: StreamingAvailability }>(`/search/streaming/${imdbId}/refresh`, null, {
      params: { country }
    })
    return response.data.data
  },
}

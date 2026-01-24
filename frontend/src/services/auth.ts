import api, { getCsrfCookie } from './api'
import type { User, LoginCredentials, RegisterData } from '@/types'

export interface AuthResponse {
  message: string
  user: User
}

export const authService = {
  async login(credentials: LoginCredentials): Promise<AuthResponse> {
    await getCsrfCookie()
    const response = await api.post<AuthResponse>('/auth/login', credentials)
    return response.data
  },

  async register(data: RegisterData): Promise<AuthResponse> {
    await getCsrfCookie()
    const response = await api.post<AuthResponse>('/auth/register', data)
    return response.data
  },

  async logout(): Promise<void> {
    await api.post('/auth/logout')
  },

  async getUser(): Promise<User> {
    const response = await api.get<{ user: User }>('/auth/user')
    return response.data.user
  },

  async updateProfile(data: Partial<User>): Promise<User> {
    const response = await api.put<{ user: User }>('/auth/profile', data)
    return response.data.user
  },

  async updatePassword(data: { current_password: string; password: string; password_confirmation: string }): Promise<void> {
    await api.put('/auth/password', data)
  },

  async getGoogleAuthUrl(): Promise<string> {
    const response = await api.get<{ url: string }>('/auth/google/url')
    return response.data.url
  },
}

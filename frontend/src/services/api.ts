import axios, { type AxiosInstance, type AxiosError } from 'axios'

const API_URL = import.meta.env.VITE_API_URL || '/api'

const api: AxiosInstance = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    // Get CSRF token from cookie if available
    const token = document.cookie
      .split('; ')
      .find(row => row.startsWith('XSRF-TOKEN='))
      ?.split('=')[1]

    if (token) {
      config.headers['X-XSRF-TOKEN'] = decodeURIComponent(token)
    }

    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    // Don't auto-redirect on 401 - let the router handle authentication
    // This prevents redirect loops when checking auth status
    return Promise.reject(error)
  }
)

// Get CSRF cookie before making requests
export async function getCsrfCookie(): Promise<void> {
  await axios.get('/sanctum/csrf-cookie', {
    baseURL: import.meta.env.VITE_API_URL?.replace('/api', '') || '',
    withCredentials: true,
  })
}

export default api

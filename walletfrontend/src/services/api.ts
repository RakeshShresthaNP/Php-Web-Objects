import axios, { type AxiosInstance, type AxiosRequestConfig, type AxiosResponse, type InternalAxiosRequestConfig } from 'axios'

interface ApiError {
  message: string
  status: number
  data?: any
}

// Create axios instance with default config
const api: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:3000/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json'
  }
})

// Request interceptor
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    // Add auth token if available
    const token = localStorage.getItem('auth_token')
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response: AxiosResponse) => {
    return response.data
  },
  (error: any) => {
    // Handle common errors
    if (error.response) {
      // Server responded with error status
      const { status, data } = error.response
      
      switch (status) {
        case 401:
          // Unauthorized - redirect to login
          localStorage.removeItem('auth_token')
          window.location.href = '/'
          break
        case 403:
          // Forbidden
          break
        case 404:
          // Not found
          break
        case 500:
          // Server error
          break
      }
      
      return Promise.reject({
        message: data?.message || 'An error occurred',
        status,
        data
      } as ApiError)
    } else if (error.request) {
      // Request made but no response
      return Promise.reject({
        message: 'Network error. Please check your connection.',
        status: 0
      } as ApiError)
    } else {
      // Something else happened
      return Promise.reject({
        message: error.message || 'An unexpected error occurred',
        status: 0
      } as ApiError)
    }
  }
)

// API methods
export const apiService = {
  // GET request
  get: async <T = any>(url: string, config: AxiosRequestConfig = {}): Promise<T> => {
    try {
      return await api.get<T>(url, config) as T
    } catch (error) {
      throw error
    }
  },

  // POST request
  post: async <T = any>(url: string, data?: any, config: AxiosRequestConfig = {}): Promise<T> => {
    try {
      return await api.post<T>(url, data, config) as T
    } catch (error) {
      throw error
    }
  },

  // PUT request
  put: async <T = any>(url: string, data?: any, config: AxiosRequestConfig = {}): Promise<T> => {
    try {
      return await api.put<T>(url, data, config) as T
    } catch (error) {
      throw error
    }
  },

  // PATCH request
  patch: async <T = any>(url: string, data?: any, config: AxiosRequestConfig = {}): Promise<T> => {
    try {
      return await api.patch<T>(url, data, config) as T
    } catch (error) {
      throw error
    }
  },

  // DELETE request
  delete: async <T = any>(url: string, config: AxiosRequestConfig = {}): Promise<T> => {
    try {
      return await api.delete<T>(url, config) as T
    } catch (error) {
      throw error
    }
  }
}

export default api


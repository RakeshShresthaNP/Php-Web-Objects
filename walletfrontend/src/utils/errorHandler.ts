import { useToastNotification } from '@/composables/useToast'
import { i18n } from '@/i18n'

interface ApiError {
  response?: {
    status: number
    data?: {
      message?: string
    }
  }
  request?: any
  message?: string
}

/**
 * Global error handler
 * Provides centralized error handling with user-friendly messages
 */
export const handleError = (error: ApiError | Error | any, customMessage: string | null = null): string => {
  const toast = useToastNotification()
  
  let message = customMessage || i18n.global.t('errors.generic')

  if (error?.response) {
    // API error response
    const status = error.response.status
    const data = error.response.data

    switch (status) {
      case 400:
        message = data?.message || i18n.global.t('errors.invalidRequest')
        break
      case 401:
        message = i18n.global.t('errors.sessionExpired')
        break
      case 403:
        message = i18n.global.t('errors.noPermission')
        break
      case 404:
        message = i18n.global.t('errors.notFound')
        break
      case 422:
        message = data?.message || i18n.global.t('errors.invalidData')
        break
      case 429:
        message = i18n.global.t('errors.tooManyRequests')
        break
      case 500:
        message = i18n.global.t('errors.serverError')
        break
      case 503:
        message = i18n.global.t('errors.serviceUnavailable')
        break
      default:
        message = data?.message || i18n.global.t('errors.generic')
    }
  } else if (error?.request) {
    // Network error
    message = i18n.global.t('errors.networkError')
  } else if (error?.message) {
    // Other errors
    message = error.message
  }

  // Show error toast
  toast.error(message)

  // Log error for debugging (only in development)
  if (import.meta.env.DEV) {
    console.error('Error:', error)
  }

  return message
}

/**
 * Handle API errors with retry logic
 * @param apiCall - API function to retry
 * @param maxRetries - Maximum number of retries
 * @param delay - Delay between retries in ms
 * @returns API call result
 */
export const handleApiErrorWithRetry = async <T>(
  apiCall: () => Promise<T>,
  maxRetries: number = 3,
  delay: number = 1000
): Promise<T> => {
  let lastError: any

  for (let i = 0; i < maxRetries; i++) {
    try {
      return await apiCall()
    } catch (error: any) {
      lastError = error
      
      // Don't retry on client errors (4xx)
      if (error?.response?.status >= 400 && error?.response?.status < 500) {
        throw error
      }

      // Wait before retrying
      if (i < maxRetries - 1) {
        await new Promise(resolve => setTimeout(resolve, delay * (i + 1)))
      }
    }
  }

  throw lastError
}

/**
 * Format error message for display
 * @param error - Error object
 * @returns Formatted error message
 */
export const formatErrorMessage = (error: ApiError | Error | string | any): string => {
  if (typeof error === 'string') {
    return error
  }

  if (error?.message) {
    return error.message
  }

  if (error?.response?.data?.message) {
    return error.response.data.message
  }

  return i18n.global.t('errors.unknownError')
}


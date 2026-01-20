import { useToast } from 'vue-toastification'

interface ToastOptions {
  timeout?: number
  [key: string]: any
}

/**
 * Toast notification composable
 * Provides easy-to-use toast notification methods
 */
export const useToastNotification = () => {
  const toast = useToast()

  return {
    // Success notification
    success: (message: string, options: ToastOptions = {}) => {
      return toast.success(message, {
        timeout: 3000,
        ...options
      })
    },

    // Error notification
    error: (message: string, options: ToastOptions = {}) => {
      return toast.error(message, {
        timeout: 4000,
        ...options
      })
    },

    // Info notification
    info: (message: string, options: ToastOptions = {}) => {
      return toast.info(message, {
        timeout: 3000,
        ...options
      })
    },

    // Warning notification
    warning: (message: string, options: ToastOptions = {}) => {
      return toast.warning(message, {
        timeout: 3000,
        ...options
      })
    },

    // Default notification
    default: (message: string, options: ToastOptions = {}) => {
      return toast(message, {
        timeout: 3000,
        ...options
      })
    },

    // Clear all toasts
    clear: (): void => {
      toast.clear()
    }
  }
}


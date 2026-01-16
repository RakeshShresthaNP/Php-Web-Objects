import { ref, type Ref } from 'vue'

/**
 * Loading state composable
 * Provides loading state management
 */
export const useLoading = (initialState: boolean = false) => {
  const isLoading: Ref<boolean> = ref(initialState)
  const loadingMessage: Ref<string> = ref('Memuat...')

  const startLoading = (message: string = 'Memuat...'): void => {
    loadingMessage.value = message
    isLoading.value = true
  }

  const stopLoading = (): void => {
    isLoading.value = false
    loadingMessage.value = 'Memuat...'
  }

  const withLoading = async <T>(asyncFn: () => Promise<T>, message: string = 'Memuat...'): Promise<T> => {
    try {
      startLoading(message)
      const result = await asyncFn()
      return result
    } finally {
      stopLoading()
    }
  }

  return {
    isLoading,
    loadingMessage,
    startLoading,
    stopLoading,
    withLoading
  }
}


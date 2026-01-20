import { ref, computed, type Ref, type ComputedRef } from 'vue'
import { validateForm, validationSchemas } from '@/utils/validators'
import type * as yup from 'yup'

/**
 * Form validation composable
 * Provides form validation functionality with error handling
 */
export const useFormValidation = <T extends Record<string, any>>(
  schemaName: keyof typeof validationSchemas,
  initialValues: Partial<T> = {}
) => {
  const schema = validationSchemas[schemaName]
  const formData: Ref<Partial<T>> = ref({ ...initialValues } as Partial<T>)
  const errors: Ref<Record<string, string>> = ref({})
  const isSubmitting: Ref<boolean> = ref(false)

  // Validate single field
  const validateField = async (fieldName: string): Promise<void> => {
    if (!schema) return

    try {
      await schema.validateAt(fieldName, formData.value)
      if (errors.value[fieldName]) {
        delete errors.value[fieldName]
      }
    } catch (error: any) {
      errors.value[fieldName] = error.message
    }
  }

  // Validate entire form
  const validate = async (): Promise<{ isValid: boolean; errors: Record<string, string> }> => {
    if (!schema) {
      return { isValid: true, errors: {} }
    }

    const result = await validateForm(schema, formData.value)
    errors.value = result.errors
    return result
  }

  // Reset form
  const reset = (): void => {
    formData.value = { ...initialValues } as Partial<T>
    errors.value = {}
    isSubmitting.value = false
  }

  // Check if form is valid
  const isValid: ComputedRef<boolean> = computed(() => {
    return Object.keys(errors.value).length === 0
  })

  // Set form data
  const setFieldValue = (fieldName: keyof T, value: any): void => {
    formData.value[fieldName] = value
    // Auto-validate on change
    if (errors.value[fieldName as string]) {
      validateField(fieldName as string)
    }
  }

  // Get field error
  const getFieldError = (fieldName: string): string => {
    return errors.value[fieldName] || ''
  }

  // Check if field has error
  const hasFieldError = (fieldName: string): boolean => {
    return !!errors.value[fieldName]
  }

  return {
    formData,
    errors,
    isSubmitting,
    isValid,
    validate,
    validateField,
    reset,
    setFieldValue,
    getFieldError,
    hasFieldError
  }
}


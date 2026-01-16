import * as yup from 'yup'
import { i18n } from '@/i18n'

/**
 * Common validation schemas
 */
export const validationSchemas = {
  // Login validation
  login: yup.object({
    username: yup
      .string()
      .required(i18n.global.t('validation.usernameRequired'))
      .min(3, i18n.global.t('validation.usernameMin')),
    password: yup
      .string()
      .required(i18n.global.t('validation.passwordRequired'))
      .min(6, i18n.global.t('validation.passwordMin'))
  }),

  // Transfer validation
  transfer: yup.object({
    recipient: yup
      .string()
      .required(i18n.global.t('validation.recipientRequired'))
      .matches(/^\d+$/, i18n.global.t('validation.recipientNumeric')),
    amount: yup
      .number()
      .required(i18n.global.t('validation.amountRequired'))
      .positive(i18n.global.t('validation.amountPositive'))
      .min(10000, i18n.global.t('validation.transferMin')),
    note: yup
      .string()
      .max(100, i18n.global.t('validation.noteMax'))
  }),

  // Withdraw validation
  withdraw: yup.object({
    amount: yup
      .number()
      .required(i18n.global.t('validation.amountRequired'))
      .positive(i18n.global.t('validation.amountPositive'))
      .min(50000, i18n.global.t('validation.withdrawMin')),
    bankAccount: yup
      .string()
      .required(i18n.global.t('validation.accountRequired'))
      .matches(/^\d+$/, i18n.global.t('validation.accountNumeric')),
    bankName: yup
      .string()
      .required(i18n.global.t('validation.bankNameRequired'))
  }),

  // Deposit validation
  deposit: yup.object({
    trashType: yup
      .string()
      .required(i18n.global.t('validation.wasteTypeRequired')),
    weight: yup
      .number()
      .required(i18n.global.t('validation.wasteWeightRequired'))
      .positive(i18n.global.t('validation.wasteWeightPositive'))
      .min(0.1, i18n.global.t('validation.wasteWeightMin'))
  }),

  // Change point validation
  changePoint: yup.object({
    points: yup
      .number()
      .required(i18n.global.t('validation.pointsRequired'))
      .positive(i18n.global.t('validation.pointsPositive'))
      .integer(i18n.global.t('validation.pointsInteger'))
      .min(100, i18n.global.t('validation.pointsMin'))
  })
}

/**
 * Validate form data against schema
 * @param schema - Yup validation schema
 * @param data - Data to validate
 * @returns Validation result
 */
export const validateForm = async (
  schema: yup.AnyObjectSchema,
  data: any
): Promise<{ isValid: boolean; errors: Record<string, string> }> => {
  try {
    await schema.validate(data, { abortEarly: false })
    return { isValid: true, errors: {} }
  } catch (error: any) {
    const errors: Record<string, string> = {}
    if (error.inner) {
      error.inner.forEach((err: yup.ValidationError) => {
        if (err.path) {
          errors[err.path] = err.message
        }
      })
    }
    return { isValid: false, errors }
  }
}

/**
 * Validate email format
 * @param email - Email to validate
 * @returns Is valid email
 */
export const isValidEmail = (email: string): boolean => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

/**
 * Validate phone number (Indonesian format)
 * @param phone - Phone number to validate
 * @returns Is valid phone number
 */
export const isValidPhone = (phone: string): boolean => {
  const phoneRegex = /^(^\+62|62|^08)(\d{3,4}-?){2}\d{3,4}$/
  return phoneRegex.test(phone.replace(/\s/g, ''))
}

/**
 * Validate account number
 * @param accountNumber - Account number to validate
 * @returns Is valid account number
 */
export const isValidAccountNumber = (accountNumber: string): boolean => {
  return /^\d{8,}$/.test(accountNumber)
}


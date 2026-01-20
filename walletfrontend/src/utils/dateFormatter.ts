import { format, formatDistanceToNow, formatRelative, parseISO, isValid } from 'date-fns'
import { id } from 'date-fns/locale/id'

/**
 * Format date to Indonesian format
 * @param date - Date to format
 * @param formatStr - Format string (default: 'dd MMMM yyyy')
 * @returns Formatted date
 */
export const formatDate = (date: Date | string | null | undefined, formatStr: string = 'dd MMMM yyyy'): string => {
  if (!date) return ''
  
  try {
    const dateObj = typeof date === 'string' ? parseISO(date) : date
    if (!isValid(dateObj)) return ''
    
    return format(dateObj, formatStr, { locale: id })
  } catch (error) {
    console.error('Date formatting error:', error)
    return ''
  }
}

/**
 * Format date with time
 * @param date - Date to format
 * @returns Formatted date with time
 */
export const formatDateTime = (date: Date | string | null | undefined): string => {
  return formatDate(date, 'dd MMMM yyyy, HH:mm')
}

/**
 * Format date to relative time (e.g., "2 hours ago")
 * @param date - Date to format
 * @returns Relative time string
 */
export const formatRelativeTime = (date: Date | string | null | undefined): string => {
  if (!date) return ''
  
  try {
    const dateObj = typeof date === 'string' ? parseISO(date) : date
    if (!isValid(dateObj)) return ''
    
    return formatDistanceToNow(dateObj, { 
      addSuffix: true,
      locale: id 
    })
  } catch (error) {
    console.error('Relative time formatting error:', error)
    return ''
  }
}

/**
 * Format date to relative format (e.g., "Today at 3:00 PM")
 * @param date - Date to format
 * @returns Relative format string
 */
export const formatRelativeDate = (date: Date | string | null | undefined): string => {
  if (!date) return ''
  
  try {
    const dateObj = typeof date === 'string' ? parseISO(date) : date
    if (!isValid(dateObj)) return ''
    
    return formatRelative(dateObj, new Date(), { locale: id })
  } catch (error) {
    console.error('Relative date formatting error:', error)
    return ''
  }
}

/**
 * Format currency to Indonesian Rupiah
 * @param amount - Amount to format
 * @returns Formatted currency
 */
export const formatCurrency = (amount: number | null | undefined): string => {
  if (amount === null || amount === undefined) return 'Rp 0'
  
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(amount)
}

/**
 * Format number with thousand separators
 * @param number - Number to format
 * @returns Formatted number
 */
export const formatNumber = (number: number | null | undefined): string => {
  if (number === null || number === undefined) return '0'
  
  return new Intl.NumberFormat('id-ID').format(number)
}


import { format, formatDistance, parseISO } from 'date-fns'; // date-fns v2.x

/**
 * Formats an ISO timestamp string into a user-friendly format for display in the history panel
 * @param isoTimestamp ISO timestamp string
 * @returns Formatted timestamp string (e.g., '05/12/2023 10:45 AM')
 */
export function formatHistoryTimestamp(isoTimestamp: string): string {
  try {
    const date = parseISO(isoTimestamp);
    return format(date, 'MM/dd/yyyy hh:mm a');
  } catch (error) {
    console.error('Error formatting history timestamp:', error);
    return isoTimestamp; // Return original string if formatting fails
  }
}

/**
 * Formats an ISO timestamp string into a relative time format (e.g., '2 hours ago')
 * @param isoTimestamp ISO timestamp string
 * @returns Relative time string
 */
export function formatRelativeTime(isoTimestamp: string): string {
  try {
    const date = parseISO(isoTimestamp);
    return formatDistance(date, new Date(), { addSuffix: true });
  } catch (error) {
    console.error('Error formatting relative time:', error);
    return isoTimestamp; // Return original string if formatting fails
  }
}

/**
 * Formats an ISO date string into a standard date format
 * @param isoDate ISO date string
 * @param formatPattern Optional format pattern (defaults to 'MM/dd/yyyy')
 * @returns Formatted date string
 */
export function formatDate(isoDate: string, formatPattern: string = 'MM/dd/yyyy'): string {
  try {
    const date = parseISO(isoDate);
    return format(date, formatPattern);
  } catch (error) {
    console.error('Error formatting date:', error);
    return isoDate; // Return original string if formatting fails
  }
}

/**
 * Checks if a given string is a valid date
 * @param dateString The date string to validate
 * @returns True if the string is a valid date, false otherwise
 */
export function isValidDate(dateString: string): boolean {
  try {
    const date = parseISO(dateString);
    return !isNaN(date.getTime());
  } catch (error) {
    return false;
  }
}
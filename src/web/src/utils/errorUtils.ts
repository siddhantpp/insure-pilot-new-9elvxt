import { ApiError } from '../models/api.types';
import { AxiosError } from 'axios'; // axios 1.x

/**
 * Type guard to check if an error is an ApiError
 * @param error The error to check
 * @returns True if the error is an ApiError, false otherwise
 */
export function isApiError(error: unknown): error is ApiError {
  return (
    typeof error === 'object' &&
    error !== null &&
    'status' in error &&
    'message' in error
  );
}

/**
 * Type guard to check if an error is an AxiosError
 * @param error The error to check
 * @returns True if the error is an AxiosError, false otherwise
 */
export function isAxiosError(error: unknown): error is AxiosError {
  return (
    typeof error === 'object' &&
    error !== null &&
    'isAxiosError' in error &&
    (error as any).isAxiosError === true
  );
}

/**
 * Checks if the error is a network connectivity error
 * @param error The error to check
 * @returns True if the error is a network error, false otherwise
 */
export function isNetworkError(error: unknown): boolean {
  if (!isAxiosError(error)) return false;
  return error.code === 'ECONNABORTED' || error.code === 'ERR_NETWORK';
}

/**
 * Checks if the error is a request timeout error
 * @param error The error to check
 * @returns True if the error is a timeout error, false otherwise
 */
export function isTimeoutError(error: unknown): boolean {
  if (!isAxiosError(error)) return false;
  return error.code === 'ECONNABORTED' && error.message.includes('timeout');
}

/**
 * Checks if the error is a resource not found (404) error
 * @param error The error to check
 * @returns True if the error is a not found error, false otherwise
 */
export function isNotFoundError(error: unknown): boolean {
  if (!isApiError(error)) return false;
  return error.status === 404;
}

/**
 * Checks if the error is a permission denied (403) error
 * @param error The error to check
 * @returns True if the error is a permission error, false otherwise
 */
export function isPermissionError(error: unknown): boolean {
  if (!isApiError(error)) return false;
  return error.status === 403;
}

/**
 * Checks if the error is a validation (422) error
 * @param error The error to check
 * @returns True if the error is a validation error, false otherwise
 */
export function isValidationError(error: unknown): boolean {
  if (!isApiError(error)) return false;
  return error.status === 422 && error.errors !== null;
}

/**
 * Extracts a user-friendly error message from various error types
 * @param error The error to extract a message from
 * @returns User-friendly error message
 */
export function getErrorMessage(error: unknown): string {
  if (error === null || error === undefined) {
    return 'An unknown error occurred.';
  }

  if (typeof error === 'string') {
    return error;
  }

  if (isNetworkError(error)) {
    return 'Unable to connect to the server. Please check your internet connection and try again.';
  }

  if (isTimeoutError(error)) {
    return 'The request timed out. Please try again later.';
  }

  if (isNotFoundError(error)) {
    return 'The requested resource was not found. It may have been moved or deleted.';
  }

  if (isPermissionError(error)) {
    return 'You do not have permission to perform this action. Please contact your administrator.';
  }

  if (isApiError(error)) {
    return error.message;
  }

  if (typeof error === 'object' && error !== null && 'message' in error) {
    return (error as any).message;
  }

  return 'An unexpected error occurred. Please try again later.';
}

/**
 * Extracts field-specific validation errors from an API error response
 * @param error The error to extract validation errors from
 * @returns Object with field names as keys and error messages as values
 */
export function getValidationErrors(error: unknown): Record<string, string> {
  const errors: Record<string, string> = {};

  if (!isValidationError(error)) {
    return errors;
  }

  const validationErrors = error.errors;
  if (!validationErrors) return errors;

  // Take the first error message for each field
  Object.keys(validationErrors).forEach((field) => {
    const fieldErrors = validationErrors[field];
    if (fieldErrors && fieldErrors.length > 0) {
      errors[field] = fieldErrors[0];
    }
  });

  return errors;
}

/**
 * Formats an error object for consistent logging
 * @param error The error to format for logging
 * @returns Formatted error object suitable for logging
 */
export function formatErrorForLogging(error: unknown): object {
  const result: Record<string, any> = {
    timestamp: new Date().toISOString(),
  };

  // Add error type
  if (isApiError(error)) {
    result.type = 'ApiError';
    result.status = error.status;
  } else if (isAxiosError(error)) {
    result.type = 'AxiosError';
    result.status = error.response?.status;
    result.url = error.config?.url;
    result.method = error.config?.method;
  } else {
    result.type = error?.constructor?.name || 'UnknownError';
  }

  // Add message
  result.message = getErrorMessage(error);

  // Add stack trace if available
  if (error instanceof Error) {
    result.stack = error.stack;
  }

  return result;
}
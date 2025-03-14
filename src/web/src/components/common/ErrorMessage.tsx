import React from 'react';
import { getErrorMessage } from '../../utils/errorUtils';

/**
 * Props interface for the ErrorMessage component
 */
export interface ErrorMessageProps {
  /**
   * Error object, string, or any error representation to display
   */
  error: unknown;
  
  /**
   * Additional CSS class names to apply to the component
   */
  className?: string;
}

/**
 * A component that displays formatted error messages with appropriate styling and accessibility attributes.
 * Uses the getErrorMessage utility to convert various error types into user-friendly messages.
 *
 * @param props - Component props
 * @returns JSX.Element - Rendered error message component
 */
const ErrorMessage: React.FC<ErrorMessageProps> = ({ error, className, ...props }) => {
  // Convert error to a user-friendly message
  const errorMessage = getErrorMessage(error);
  
  // Combine default class with any additional className provided
  const combinedClassName = `error-message ${className || ''}`.trim();
  
  return (
    <div 
      className={combinedClassName}
      role="alert" 
      aria-live="assertive"
      {...props}
    >
      <span className="error-icon" aria-hidden="true">⚠️</span>
      <span className="error-text">{errorMessage}</span>
    </div>
  );
};

export default ErrorMessage;
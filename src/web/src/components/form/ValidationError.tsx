import React from 'react';

/**
 * Props interface for the ValidationError component
 */
export interface ValidationErrorProps {
  /** Unique identifier for the error, typically matching the related form field */
  id: string;
  /** Error message to display, if any */
  error?: string | null;
}

/**
 * A component that displays validation error messages for form fields
 * in the Documents View feature. This component provides clear visual feedback
 * when validation fails and includes appropriate accessibility attributes.
 * 
 * @param props - The component props
 * @returns JSX element or null if no error
 */
const ValidationError: React.FC<ValidationErrorProps> = ({ id, error }) => {
  // Only render if there's an error message
  if (!error) return null;

  return (
    <div 
      id={id}
      className="validation-error"
      role="alert"
      aria-live="polite"
    >
      {error}
    </div>
  );
};

export default ValidationError;
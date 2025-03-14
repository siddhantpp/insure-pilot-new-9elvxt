import React from 'react'; // 18.x
import classNames from 'classnames'; // 2.x
import FormLabel from './FormLabel';

/**
 * Props interface for the ReadOnlyField component
 */
export interface ReadOnlyFieldProps {
  /**
   * Unique identifier for the field
   */
  id: string;
  
  /**
   * Name of the field
   */
  name: string;
  
  /**
   * Label text to display for the field
   */
  label: string;
  
  /**
   * Value to display in read-only format
   */
  value?: string | number | null | undefined;
  
  /**
   * Additional CSS classes to apply to the container
   */
  className?: string;
}

/**
 * A component that renders a form field in read-only mode.
 * Used in the Documents View feature when a document is marked as processed,
 * displaying metadata values as non-editable text while maintaining the same
 * visual structure as editable fields.
 */
const ReadOnlyField: React.FC<ReadOnlyFieldProps> = ({
  id,
  name,
  label,
  value,
  className,
}) => {
  // Format the value for display, handling null, undefined, and empty string cases
  const displayValue = value !== null && value !== undefined && value !== '' 
    ? value 
    : '—'; // Em dash as placeholder for empty values
  
  return (
    <div 
      className={classNames('read-only-field', className)}
      data-testid="read-only-field"
    >
      <FormLabel htmlFor={id}>
        {label}
      </FormLabel>
      <div 
        className="read-only-value"
        id={id}
        aria-readonly="true"
      >
        {displayValue}
      </div>
    </div>
  );
};

export default ReadOnlyField;
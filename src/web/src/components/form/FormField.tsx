import React, { useRef, useEffect } from 'react'; // 18.x
import classNames from 'classnames'; // 2.x
import FormLabel from './FormLabel';
import ValidationError from './ValidationError';
import FormGroup from './FormGroup';
import ReadOnlyField from './ReadOnlyField';
import { setAriaAttributes } from '../../utils/accessibilityUtils';

/**
 * Props interface for the FormField component
 */
export interface FormFieldProps {
  /**
   * Unique identifier for the form field
   */
  id: string;
  
  /**
   * Name attribute for the form field
   */
  name: string;
  
  /**
   * Label text to display for the form field
   */
  label: string;
  
  /**
   * Child elements (typically input, select, etc.)
   */
  children: React.ReactNode;
  
  /**
   * Error message to display if validation fails
   */
  error?: string;
  
  /**
   * Whether the field is required
   */
  required?: boolean;
  
  /**
   * Additional CSS class names
   */
  className?: string;
  
  /**
   * Whether the field is disabled
   */
  disabled?: boolean;
  
  /**
   * Whether the field is read-only
   */
  readOnly?: boolean;
  
  /**
   * Whether the field should display inline
   */
  inline?: boolean;
  
  /**
   * Current value of the field (used in read-only mode)
   */
  value?: string | number | null | undefined;
}

/**
 * A reusable form field component that renders a label, input element, and validation error
 * for the Documents View feature. This component provides consistent styling, labeling, 
 * and error handling for form fields in the metadata panel.
 * 
 * @param props - The component props
 * @returns JSX element for the form field
 */
const FormField: React.FC<FormFieldProps> = ({
  id,
  name,
  label,
  children,
  error,
  required = false,
  className,
  disabled = false,
  readOnly = false,
  inline = false,
  value,
}) => {
  // Create a reference to the input element
  const inputRef = useRef<HTMLElement | null>(null);

  // Use an effect to set ARIA attributes on the input element when it changes
  useEffect(() => {
    // Find the input element by ID after the component has mounted
    const element = document.getElementById(id);
    if (element) {
      inputRef.current = element as HTMLElement;
      
      // Set appropriate ARIA attributes for accessibility
      setAriaAttributes(element as HTMLElement, {
        invalid: !!error,
        required,
        describedBy: error ? `${id}-error` : undefined,
      });
    }
  }, [id, error, required]);

  // If in read-only mode, render the read-only version of the field
  if (readOnly) {
    return (
      <ReadOnlyField
        id={id}
        name={name}
        label={label}
        value={value}
        className={className}
      />
    );
  }

  // Render the editable version of the field
  return (
    <FormGroup
      className={classNames('form-field', className)}
      hasError={!!error}
      inline={inline}
      disabled={disabled}
      readOnly={readOnly}
      id={`${id}-group`}
    >
      <FormLabel htmlFor={id} required={required}>
        {label}
      </FormLabel>
      <div className="form-control-container">
        {children}
      </div>
      {error && <ValidationError id={`${id}-error`} error={error} />}
    </FormGroup>
  );
};

export default FormField;
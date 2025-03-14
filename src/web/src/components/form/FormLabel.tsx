import React from 'react'; // 18.x
import classNames from 'classnames'; // 2.x

/**
 * Props interface for the FormLabel component
 */
export interface FormLabelProps {
  /**
   * ID of the form element this label is associated with
   */
  htmlFor: string;
  
  /**
   * Content of the label
   */
  children: React.ReactNode;
  
  /**
   * Whether the field is required, adds a visual indicator (asterisk)
   */
  required?: boolean;
  
  /**
   * Additional CSS classes to apply to the label
   */
  className?: string;
}

/**
 * A form label component that renders a label with optional required indicator
 * for metadata fields in the document viewer interface.
 * 
 * This component provides accessible labels for form fields, supporting screen readers
 * and maintaining visual consistency throughout the Documents View feature.
 */
const FormLabel: React.FC<FormLabelProps> = ({
  htmlFor,
  children,
  required = false,
  className,
}) => {
  return (
    <label 
      htmlFor={htmlFor}
      className={classNames(
        'form-label',
        { 'form-label-required': required },
        className
      )}
      data-testid="form-label"
    >
      {children}
    </label>
  );
};

export default FormLabel;
import React from 'react';
import classNames from 'classnames'; // 2.x

/**
 * Props interface for the FormGroup component
 */
interface FormGroupProps {
  /** Content to be rendered inside the form group */
  children: React.ReactNode;
  /** Additional CSS class names to apply to the form group */
  className?: string;
  /** Whether the form group has an error state */
  hasError?: boolean;
  /** Whether the form group should be displayed inline */
  inline?: boolean;
  /** Whether the form group is disabled */
  disabled?: boolean;
  /** Whether the form group is in read-only mode */
  readOnly?: boolean;
  /** HTML ID attribute for the form group container */
  id?: string;
}

/**
 * FormGroup component
 * 
 * A container for form elements that provides consistent styling, layout,
 * and accessibility features. Used to group form controls with their labels
 * and validation messages in the Documents View feature.
 * 
 * @param {FormGroupProps} props - Component props
 * @returns {JSX.Element} Rendered form group component
 */
const FormGroup: React.FC<FormGroupProps> = ({
  children,
  className,
  hasError = false,
  inline = false,
  disabled = false,
  readOnly = false,
  id,
}) => {
  const groupClassName = classNames(
    'form-group',
    className,
    {
      'form-group-inline': inline,
      'has-error': hasError,
      'disabled': disabled,
      'read-only': readOnly,
    }
  );

  return (
    <div className={groupClassName} id={id}>
      {children}
    </div>
  );
};

export default FormGroup;
export type { FormGroupProps };
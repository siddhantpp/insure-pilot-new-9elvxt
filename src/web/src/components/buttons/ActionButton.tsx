import React, { ButtonHTMLAttributes } from 'react'; // v18.x
import classNames from 'classnames'; // ^2.3.2

/**
 * Props interface for the ActionButton component
 * @extends ButtonHTMLAttributes<HTMLButtonElement> - Extends standard button attributes
 */
export interface ActionButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  /**
   * Button visual style variant
   * - primary: Main action buttons (default)
   * - secondary: Less prominent actions
   * - danger: Destructive actions like delete or trash
   */
  variant?: 'primary' | 'secondary' | 'danger';
  
  /**
   * When true, displays a loading spinner and disables the button
   */
  isLoading?: boolean;
  
  /**
   * When true, displays the button in a processed state (e.g., "Mark as Processed" becomes "Processed")
   */
  isProcessed?: boolean;
  
  /**
   * Additional CSS class names to apply to the button
   */
  className?: string;
  
  /**
   * Button content/label
   */
  children: React.ReactNode;
}

/**
 * A reusable button component that serves as the foundation for all action buttons in the Documents View feature.
 * 
 * Features:
 * - Consistent styling across the application
 * - Support for different visual variants (primary, secondary, danger)
 * - Loading state with spinner
 * - Processed state for toggle behavior
 * - Fully accessible with proper ARIA attributes
 * - Compatible with all standard button HTML attributes
 * 
 * @example
 * ```tsx
 * <ActionButton 
 *   variant="primary" 
 *   onClick={handleAction} 
 *   isLoading={isSubmitting}
 * >
 *   Submit
 * </ActionButton>
 * ```
 */
export const ActionButton: React.FC<ActionButtonProps> = ({
  children,
  className,
  variant = 'primary',
  isLoading = false,
  isProcessed = false,
  disabled = false,
  ...rest
}) => {
  // Combine class names using the classNames utility
  const buttonClasses = classNames(
    'action-button', // Base button class
    `action-button--${variant}`, // Variant-specific class
    {
      'action-button--loading': isLoading,
      'action-button--processed': isProcessed,
      'action-button--disabled': disabled || isLoading,
    },
    className
  );

  // Define appropriate ARIA attributes for accessibility
  const ariaAttributes = {
    'aria-busy': isLoading,
    ...(isProcessed && { 'aria-pressed': true }), // Only add if isProcessed is true
  };

  return (
    <button
      type="button" // Explicitly set type to prevent form submission
      className={buttonClasses}
      disabled={disabled || isLoading}
      {...ariaAttributes}
      {...rest}
    >
      {isLoading && (
        <span className="action-button__loading-indicator" aria-hidden="true">
          <span className="action-button__loading-spinner"></span>
        </span>
      )}
      <span className={isLoading ? 'action-button__text action-button__text--loading' : 'action-button__text'}>
        {children}
      </span>
    </button>
  );
};
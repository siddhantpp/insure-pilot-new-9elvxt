import React, { ButtonHTMLAttributes } from 'react'; // v18.x
import classNames from 'classnames'; // ^2.3.2
import { ActionButton } from './ActionButton';
import { setAriaAttributes } from '../../utils/accessibilityUtils';

/**
 * Props interface for the NavigationButton component
 * @extends ButtonHTMLAttributes<HTMLButtonElement> - Extends standard button attributes
 */
export interface NavigationButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  /**
   * Destination URL or path to navigate to
   */
  to: string;
  
  /**
   * Button text label
   */
  label?: string;
  
  /**
   * Click handler function for navigation
   */
  onClick?: () => void;
  
  /**
   * Additional CSS class names
   */
  className?: string;
  
  /**
   * Button content (alternative to label)
   */
  children?: React.ReactNode;
}

/**
 * A button component that provides navigation to different views or pages within the application.
 * Used for contextual navigation in the Documents View feature, allowing users to navigate
 * to related records like policies, producers, and claimants.
 * 
 * @example
 * ```tsx
 * <NavigationButton 
 *   to="/policy/12345"
 *   label="Go to Policy"
 *   onClick={() => navigateToPolicy('12345')}
 * />
 * ```
 */
export const NavigationButton: React.FC<NavigationButtonProps> = ({
  to,
  label,
  className,
  onClick,
  children,
  ...rest
}) => {
  // Combine class names for consistent styling
  const buttonClasses = classNames(
    'navigation-button', // Base class for navigation buttons
    className
  );

  // Use either the label or children as the button content
  const buttonContent = label || children;
  
  // For accessibility, only add aria-label if no visible text is provided
  const ariaAttributes = {
    'aria-label': label ? undefined : `Navigate to ${to}`
  };

  // Apply ARIA attributes for accessibility
  // Note: While we import setAriaAttributes, we're applying attributes directly here
  // because we're working with a React component, not a direct DOM element

  return (
    <ActionButton
      className={buttonClasses}
      variant="secondary" // Navigation buttons use secondary styling
      onClick={onClick}
      {...ariaAttributes}
      {...rest}
    >
      {buttonContent}
    </ActionButton>
  );
};
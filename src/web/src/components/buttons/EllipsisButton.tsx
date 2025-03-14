import React from 'react'; // v18.x
import classNames from 'classnames'; // ^2.3.2
import { ActionButton, ActionButtonProps } from './ActionButton';

/**
 * Props interface for the EllipsisButton component
 * @extends ActionButtonProps - Extends the base ActionButton props
 */
export interface EllipsisButtonProps extends ActionButtonProps {
  /**
   * Handler function called when the button is clicked
   */
  onClick: () => void;
  
  /**
   * Additional CSS class names to apply to the button
   */
  className?: string;
  
  /**
   * Whether the associated menu is currently open
   */
  isOpen?: boolean;
  
  /**
   * Accessibility label for the button
   * @default "More options"
   */
  ariaLabel?: string;
}

/**
 * A button component that displays an ellipsis icon and is used to trigger
 * contextual navigation menus in the Documents View feature.
 * 
 * This component provides a consistent, accessible way to access additional
 * options related to the current document.
 * 
 * @example
 * ```tsx
 * <EllipsisButton 
 *   onClick={toggleMenu} 
 *   isOpen={isMenuOpen} 
 * />
 * ```
 */
const EllipsisButton: React.FC<EllipsisButtonProps> = ({
  onClick,
  className,
  isOpen = false,
  ariaLabel = "More options",
  ...rest
}) => {
  // Combine class names using the classNames utility
  const buttonClasses = classNames(
    'ellipsis-button',
    {
      'ellipsis-button--active': isOpen,
    },
    className
  );

  return (
    <ActionButton
      className={buttonClasses}
      onClick={onClick}
      variant="secondary"
      aria-haspopup="menu"
      aria-expanded={isOpen}
      aria-label={ariaLabel}
      {...rest}
    >
      <span className="ellipsis-button__icon" aria-hidden="true">
        {/* Ellipsis icon (three dots) */}
        <svg 
          width="16" 
          height="16" 
          viewBox="0 0 16 16" 
          fill="currentColor"
          xmlns="http://www.w3.org/2000/svg"
        >
          <circle cx="8" cy="3" r="1.5" />
          <circle cx="8" cy="8" r="1.5" />
          <circle cx="8" cy="13" r="1.5" />
        </svg>
      </span>
    </ActionButton>
  );
};

export default EllipsisButton;
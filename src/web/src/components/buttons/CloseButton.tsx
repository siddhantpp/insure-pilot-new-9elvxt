import React, { ButtonHTMLAttributes, useRef, useEffect } from 'react'; // version 18.x - Core React functionality and button attribute types
import classNames from 'classnames'; // version ^2.3.2 - Utility for conditionally joining CSS class names
import { setAriaAttributes } from '../../utils/accessibilityUtils';

/**
 * Props interface for the CloseButton component
 */
export interface CloseButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  /** Handler function when the close button is clicked */
  onClick: () => void;
  /** Additional CSS class names to apply to the button */
  className?: string;
  /** Accessible label for screen readers (defaults to "Close") */
  ariaLabel?: string;
}

/**
 * A reusable button component that provides a close/dismiss action for overlays,
 * modals, and the document viewer. Displays an X icon and handles accessibility concerns.
 */
export const CloseButton: React.FC<CloseButtonProps> = ({
  onClick,
  className,
  ariaLabel = 'Close',
  ...buttonProps
}) => {
  const buttonRef = useRef<HTMLButtonElement>(null);

  // Set ARIA attributes when the component mounts
  useEffect(() => {
    if (buttonRef.current) {
      setAriaAttributes(buttonRef.current, {
        // The close button doesn't control anything, but we set it for accessibility
        hasPopup: false,
      });
    }
  }, []);

  // Combine the default 'close-button' class with any additional classes
  const buttonClasses = classNames('close-button', className);

  // Handle keyboard events for accessibility (Enter and Space to click)
  const handleKeyDown = (event: React.KeyboardEvent<HTMLButtonElement>) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      onClick();
    }
  };

  return (
    <button
      ref={buttonRef}
      type="button"
      className={buttonClasses}
      onClick={onClick}
      onKeyDown={handleKeyDown}
      aria-label={ariaLabel}
      {...buttonProps}
    >
      <svg 
        width="16" 
        height="16" 
        viewBox="0 0 16 16" 
        fill="none" 
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
      >
        <path 
          d="M12.5 3.5L3.5 12.5M3.5 3.5L12.5 12.5" 
          stroke="currentColor" 
          strokeWidth="1.5" 
          strokeLinecap="round" 
          strokeLinejoin="round"
        />
      </svg>
    </button>
  );
};
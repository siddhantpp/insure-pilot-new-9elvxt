import React, { ButtonHTMLAttributes } from 'react'; // version 18.x - Core React functionality and button attribute types
import classNames from 'classnames'; // version ^2.3.2 - Utility for conditionally joining CSS class names
import BackIcon from '../../assets/images/icons/back.svg'; // Arrow icon for the back button
import { setAriaAttributes } from '../../utils/accessibilityUtils'; // Utility for setting proper ARIA attributes for accessibility

/**
 * Props for the BackButton component
 */
export interface BackButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  /** Click handler function */
  onClick: () => void;
  /** Additional CSS class names */
  className?: string;
  /** Button content */
  children?: React.ReactNode;
}

/**
 * A button component that provides navigation back to a previous view
 * Primarily used in document history panel to return to metadata panel
 */
export const BackButton = ({
  onClick,
  className,
  children = 'Back',
  ...buttonProps
}: BackButtonProps): JSX.Element => {
  // Combine default 'back-button' class with any additional classes
  const buttonClasses = classNames('back-button', className);
  
  // Reference to the button element for ARIA attributes
  const buttonRef = React.useRef<HTMLButtonElement>(null);
  
  // Apply ARIA attributes after initial render
  React.useEffect(() => {
    if (buttonRef.current) {
      setAriaAttributes(buttonRef.current, {
        // Set any specific ARIA attributes needed
        labelledBy: buttonProps['aria-labelledby'],
        describedBy: buttonProps['aria-describedby']
      });
    }
  }, [buttonProps]);

  return (
    <button
      ref={buttonRef}
      type="button"
      className={buttonClasses}
      onClick={onClick}
      aria-label={buttonProps['aria-label'] || 'Go back'}
      {...buttonProps}
    >
      <BackIcon />
      <span className="back-button__text">{children}</span>
    </button>
  );
};
import React, { ReactNode, useRef, useEffect, useState, useCallback } from 'react'; // react@18.x
import classNames from 'classnames'; // ^2.3.2
import { CloseButton } from '../buttons/CloseButton';
import { useKeyboardShortcut } from '../../hooks/useKeyboardShortcut';
import { useOutsideClick } from '../../hooks/useOutsideClick';
import { trapFocus } from '../../utils/accessibilityUtils';

/**
 * Props interface for the LightboxOverlay component
 */
export interface LightboxOverlayProps {
  /** The content to display within the lightbox */
  children: ReactNode;
  /** Optional title to display at the top of the lightbox */
  title?: string;
  /** Callback function when the lightbox is closed */
  onClose: () => void;
  /** Whether to close the lightbox when clicking outside the content (default: false) */
  closeOnOutsideClick?: boolean;
  /** Additional CSS class names to apply to the lightbox */
  className?: string;
  /** Whether to show the close button (default: true) */
  showCloseButton?: boolean;
}

/**
 * A component that renders a full-screen overlay for displaying content in a lightbox format.
 * This is a core part of the Documents View feature, creating a focused environment for
 * document review and processing by rendering content above the main application interface.
 */
export const LightboxOverlay: React.FC<LightboxOverlayProps> = ({
  children,
  title,
  onClose,
  closeOnOutsideClick = false,
  className,
  showCloseButton = true,
}) => {
  // Create ref for the lightbox container element
  const lightboxRef = useRef<HTMLDivElement>(null);
  
  // State for animation
  const [isExiting, setIsExiting] = useState(false);
  
  // Handle close with animation
  const handleClose = useCallback(() => {
    setIsExiting(true);
    // Wait for animation to complete before calling onClose
    setTimeout(() => {
      onClose();
    }, 300); // Match this with CSS animation duration
  }, [onClose]);
  
  // Register Escape key shortcut to close the lightbox
  useKeyboardShortcut(
    {
      key: 'Escape',
      callback: handleClose,
    },
    { enabled: true }
  );
  
  // Handle outside clicks if enabled
  useEffect(() => {
    if (!closeOnOutsideClick) return;
    
    const handleClickOutside = (event: MouseEvent) => {
      if (
        lightboxRef.current && 
        !lightboxRef.current.contains(event.target as Node)
      ) {
        handleClose();
      }
    };
    
    document.addEventListener('mousedown', handleClickOutside);
    
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [closeOnOutsideClick, handleClose]);
  
  // Trap focus within the lightbox for accessibility
  useEffect(() => {
    if (lightboxRef.current) {
      // trapFocus handles focusing the first element and returning cleanup
      return trapFocus(lightboxRef.current);
    }
  }, []);
  
  // Lock body scroll when lightbox is open
  useEffect(() => {
    const originalStyle = window.getComputedStyle(document.body).overflow;
    document.body.style.overflow = 'hidden';
    
    // Restore original style on cleanup
    return () => {
      document.body.style.overflow = originalStyle;
    };
  }, []);
  
  // Combine CSS classes
  const lightboxClasses = classNames(
    'lightbox-overlay',
    'lightbox-overlay--transition', // For CSS transitions
    {
      'lightbox-overlay--exiting': isExiting,
    },
    className
  );
  
  return (
    <div 
      className={lightboxClasses}
      role="dialog"
      aria-modal="true"
      aria-labelledby={title ? 'lightbox-title' : undefined}
    >
      <div 
        ref={lightboxRef}
        className="lightbox-content"
      >
        {/* Lightbox header with title and close button */}
        {(title || showCloseButton) && (
          <div className="lightbox-header">
            {title && (
              <h2 id="lightbox-title" className="lightbox-title">
                {title}
              </h2>
            )}
            {showCloseButton && (
              <CloseButton 
                onClick={handleClose} 
                ariaLabel="Close document viewer"
                className="lightbox-close-btn"
              />
            )}
          </div>
        )}
        
        {/* Lightbox body content */}
        <div className="lightbox-body">
          {children}
        </div>
      </div>
    </div>
  );
};
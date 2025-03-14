import React, { useRef, useEffect, ReactNode } from 'react'; // v18.x
import classNames from 'classnames'; // ^2.3.2
import { ActionButton } from '../buttons/ActionButton';
import { useOutsideClick } from '../../hooks/useOutsideClick';
import { trapFocus, setFocus } from '../../utils/accessibilityUtils';

/**
 * Props interface for the ConfirmationDialog component
 */
export interface ConfirmationDialogProps {
  /**
   * Optional title for the confirmation dialog
   */
  title?: string;
  
  /**
   * Message content describing the action to be confirmed
   */
  message: ReactNode;
  
  /**
   * Label for the confirm button
   */
  confirmLabel: string;
  
  /**
   * Label for the cancel button
   */
  cancelLabel: string;
  
  /**
   * Function to call when the action is confirmed
   */
  onConfirm: () => void;
  
  /**
   * Function to call when the action is canceled
   */
  onCancel: () => void;
  
  /**
   * When true, displays a loading spinner on the confirm button
   */
  isLoading?: boolean;
  
  /**
   * Additional CSS class names to apply to the dialog
   */
  className?: string;
}

/**
 * A reusable confirmation dialog component that displays a modal overlay with a message and action buttons.
 * Used primarily for confirming potentially destructive actions like trashing documents.
 *
 * @param props - The component props
 * @returns The rendered confirmation dialog component
 */
const ConfirmationDialog: React.FC<ConfirmationDialogProps> = ({
  title,
  message,
  confirmLabel,
  cancelLabel,
  onConfirm,
  onCancel,
  isLoading = false,
  className,
}) => {
  // Create ref for the dialog container
  const dialogRef = useRef<HTMLDivElement>(null);
  
  // Handle clicks outside the dialog
  useOutsideClick(dialogRef, onCancel);
  
  // Handle keyboard events
  const handleKeyDown = (event: React.KeyboardEvent) => {
    if (event.key === 'Escape') {
      event.preventDefault();
      onCancel();
    }
  };
  
  // Focus trap inside the dialog
  useEffect(() => {
    if (!dialogRef.current) return;
    
    // Set up focus trap
    const cleanup = trapFocus(dialogRef.current);
    
    // Clean up focus trap when component unmounts
    return cleanup;
  }, []);
  
  // Prevent scrolling of the background content
  useEffect(() => {
    // Save the original overflow style
    const originalOverflow = document.body.style.overflow;
    
    // Prevent scrolling
    document.body.style.overflow = 'hidden';
    
    // Restore original overflow style when component unmounts
    return () => {
      document.body.style.overflow = originalOverflow;
    };
  }, []);
  
  return (
    <div 
      className="confirmation-dialog-overlay"
      aria-modal="true"
      role="dialog"
      aria-labelledby={title ? "confirmation-dialog-title" : undefined}
      aria-describedby="confirmation-dialog-message"
    >
      <div 
        ref={dialogRef}
        className={classNames('confirmation-dialog', className)}
        onKeyDown={handleKeyDown}
        tabIndex={-1} // For focus management
      >
        {title && (
          <h2 id="confirmation-dialog-title" className="confirmation-dialog__title">
            {title}
          </h2>
        )}
        
        <div id="confirmation-dialog-message" className="confirmation-dialog__message">
          {message}
        </div>
        
        <div className="confirmation-dialog__actions">
          <ActionButton
            variant="secondary"
            onClick={onCancel}
            className="confirmation-dialog__button confirmation-dialog__button--cancel"
          >
            {cancelLabel}
          </ActionButton>
          
          <ActionButton
            variant="danger"
            onClick={onConfirm}
            isLoading={isLoading}
            className="confirmation-dialog__button confirmation-dialog__button--confirm"
          >
            {confirmLabel}
          </ActionButton>
        </div>
      </div>
    </div>
  );
};

export default ConfirmationDialog;
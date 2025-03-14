import React, { useState, ButtonHTMLAttributes } from 'react'; // v18.x
import classNames from 'classnames'; // ^2.3.2
import { ActionButton } from './ActionButton';
import ConfirmationDialog from '../common/ConfirmationDialog';
import { useDocumentActions } from '../../hooks/useDocumentActions';

/**
 * Props interface for the TrashButton component
 * @extends ButtonHTMLAttributes<HTMLButtonElement> - Extends standard button attributes
 */
export interface TrashButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  /**
   * ID of the document to trash
   */
  documentId: number;
  
  /**
   * Callback function called when document has been successfully trashed
   */
  onTrashComplete?: () => void;
  
  /**
   * Additional CSS class names to apply to the button
   */
  className?: string;
}

/**
 * A specialized button component for trashing documents in the Documents View feature.
 * This component handles the confirmation flow for document deletion, displaying a
 * confirmation dialog before proceeding with the trash action to prevent accidental data loss.
 * 
 * @param props - Component props
 * @returns Rendered trash button component with confirmation dialog
 */
const TrashButton: React.FC<TrashButtonProps> = ({
  documentId,
  onTrashComplete,
  className,
  ...rest
}) => {
  // State for showing confirmation dialog
  const [showConfirmation, setShowConfirmation] = useState(false);
  
  // Get trash document function and state from hook
  const { trashDocument, isTrashing, trashingError } = useDocumentActions(documentId);
  
  /**
   * Handles click on the trash button to show confirmation dialog
   */
  const handleTrashClick = () => {
    setShowConfirmation(true);
  };
  
  /**
   * Handles confirmation of trash action
   */
  const handleConfirmTrash = async () => {
    const success = await trashDocument();
    
    if (success) {
      setShowConfirmation(false);
      if (onTrashComplete) {
        onTrashComplete();
      }
    }
    // If trashing fails, the dialog remains open with error message
    // and the user can try again or cancel
  };
  
  /**
   * Handles cancellation of trash action
   */
  const handleCancelTrash = () => {
    setShowConfirmation(false);
  };
  
  return (
    <>
      <ActionButton
        variant="danger"
        onClick={handleTrashClick}
        className={classNames('trash-button', className)}
        aria-label="Trash document"
        {...rest}
      >
        <span className="trash-icon" aria-hidden="true"></span>
        Trash
      </ActionButton>
      
      {showConfirmation && (
        <ConfirmationDialog
          title="Confirm Trash Document"
          message={
            trashingError 
              ? `Error: ${trashingError}. Would you like to try again?` 
              : "Are you sure you want to move this document to Recently Deleted? This document will be recoverable for 90 days."
          }
          confirmLabel="Trash Document"
          cancelLabel="Cancel"
          onConfirm={handleConfirmTrash}
          onCancel={handleCancelTrash}
          isLoading={isTrashing}
          className="trash-confirmation-dialog"
        />
      )}
    </>
  );
};

export default TrashButton;
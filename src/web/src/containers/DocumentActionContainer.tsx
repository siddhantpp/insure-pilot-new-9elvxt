import React, { useCallback } from 'react'; // react 18.x
import { useDocumentContext } from '../context/DocumentContext';
import { useDocumentActions } from '../hooks/useDocumentActions';
import ProcessButton from '../components/buttons/ProcessButton';
import TrashButton from '../components/buttons/TrashButton';
import { getErrorMessage } from '../utils/errorUtils';

/**
 * Props interface for the DocumentActionContainer component
 */
export interface DocumentActionContainerProps {
  /**
   * Additional CSS class name to apply to the container
   */
  className?: string;
  
  /**
   * Optional callback function triggered when a document is successfully trashed
   */
  onTrashComplete?: () => void;
}

/**
 * Container component that manages document action functionality
 * Provides a centralized interface for document workflow operations including
 * document processing and trash actions.
 *
 * @param props Component props
 * @returns Rendered component with document action buttons
 */
const DocumentActionContainer: React.FC<DocumentActionContainerProps> = ({
  className,
  onTrashComplete
}) => {
  // Get document state from context
  const { state } = useDocumentContext();
  
  // Extract document ID from state
  const documentId = state.document?.id ?? 0;
  
  // Get document actions and states from hook
  const {
    processDocument,
    trashDocument,
    isProcessing,
    processingError,
    isTrashing,
    trashingError
  } = useDocumentActions(documentId);
  
  // Callback to handle successful document trashing
  const handleTrashComplete = useCallback(() => {
    if (onTrashComplete) {
      onTrashComplete();
    }
  }, [onTrashComplete]);
  
  return (
    <div className={className}>
      {/* Processing action button */}
      <ProcessButton 
        className="document-action-button"
        aria-label={state.document?.isProcessed ? "Unprocess document" : "Mark document as processed"}
      />
      
      {/* Trash action button */}
      <TrashButton 
        documentId={documentId}
        onTrashComplete={handleTrashComplete}
        className="document-action-button"
        aria-label="Move document to trash"
      />
      
      {/* Error display for processing errors that may not be handled by the buttons */}
      {processingError && (
        <div className="document-action-error" role="alert">
          {getErrorMessage(processingError)}
        </div>
      )}
      
      {/* Error display for trashing errors that may not be handled by the buttons */}
      {trashingError && !isTrashing && (
        <div className="document-action-error" role="alert">
          {getErrorMessage(trashingError)}
        </div>
      )}
    </div>
  );
};

export default DocumentActionContainer;
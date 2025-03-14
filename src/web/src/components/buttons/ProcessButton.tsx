import React from 'react';
import { ActionButton } from './ActionButton';
import { useDocumentContext } from '../../context/DocumentContext';
import { useDocumentActions } from '../../hooks/useDocumentActions';

/**
 * Props interface for the ProcessButton component
 * Extends ActionButtonProps but manages isProcessed and children internally
 */
export interface ProcessButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  /**
   * Additional CSS class names to apply to the button
   */
  className?: string;
}

/**
 * A specialized button component for document processing actions in the Documents View feature.
 * This component handles the visual representation and interaction for marking documents as processed or unprocessed,
 * with appropriate state management and feedback.
 * 
 * @example
 * <ProcessButton />
 */
const ProcessButton: React.FC<ProcessButtonProps> = ({ className, ...props }) => {
  // Get document state from context
  const { state } = useDocumentContext();
  
  // Get document processing functions
  const { processDocument, isProcessing } = useDocumentActions(state.document?.id ?? 0);
  
  // Determine if document is processed
  const isProcessed = state.document?.isProcessed || false;

  // Handle click to toggle processed state
  const handleClick = async () => {
    if (state.document) {
      await processDocument(!isProcessed);
    }
  };

  return (
    <ActionButton
      variant="primary"
      isLoading={isProcessing}
      isProcessed={isProcessed}
      onClick={handleClick}
      className={className}
      {...props}
    >
      {isProcessed ? 'Processed' : 'Mark as Processed'}
    </ActionButton>
  );
};

export default ProcessButton;
export type { ProcessButtonProps };
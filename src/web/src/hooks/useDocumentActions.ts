import { useState, useCallback } from 'react'; // React 18.x
import { useApi } from './useApi';
import { 
  processDocument as processDocumentService, 
  trashDocument as trashDocumentService 
} from '../services/documentService';
import { Document } from '../models/document.types';

/**
 * A custom hook that provides document action functionality for processing and trashing documents
 * in the Documents View feature. This hook encapsulates the API calls and state management for
 * document workflow actions.
 * 
 * @param documentId The ID of the document to perform actions on
 * @returns Object containing action methods and state variables
 */
export function useDocumentActions(documentId: number) {
  // State for processing operations
  const [isProcessing, setIsProcessing] = useState<boolean>(false);
  const [processingError, setProcessingError] = useState<string | null>(null);
  
  // State for trashing operations
  const [isTrashing, setIsTrashing] = useState<boolean>(false);
  const [trashingError, setTrashingError] = useState<string | null>(null);
  
  // Get the post method from useApi hook
  const { post } = useApi();

  /**
   * Marks a document as processed or unprocessed
   * 
   * @param processed Boolean indicating whether to mark as processed (true) or unprocessed (false)
   * @returns Promise resolving to the updated document or null if the operation failed
   */
  const processDocument = useCallback(async (processed: boolean): Promise<Document | null> => {
    // Update loading state
    setIsProcessing(true);
    setProcessingError(null);
    
    try {
      // Call the service function to process the document
      const updatedDocument = await processDocumentService(documentId, processed);
      return updatedDocument;
    } catch (error) {
      // Handle error and set error state
      const errorMessage = typeof error === 'string' 
        ? error 
        : error instanceof Error 
          ? error.message 
          : 'Failed to process document';
      
      setProcessingError(errorMessage);
      return null;
    } finally {
      // Reset loading state regardless of outcome
      setIsProcessing(false);
    }
  }, [documentId]);
  
  /**
   * Moves a document to the trash (Recently Deleted folder)
   * 
   * @returns Promise resolving to true if operation succeeded, false otherwise
   */
  const trashDocument = useCallback(async (): Promise<boolean> => {
    // Update loading state
    setIsTrashing(true);
    setTrashingError(null);
    
    try {
      // Call the service function to trash the document
      await trashDocumentService(documentId);
      return true;
    } catch (error) {
      // Handle error and set error state
      const errorMessage = typeof error === 'string' 
        ? error 
        : error instanceof Error 
          ? error.message 
          : 'Failed to trash document';
      
      setTrashingError(errorMessage);
      return false;
    } finally {
      // Reset loading state regardless of outcome
      setIsTrashing(false);
    }
  }, [documentId]);
  
  // Return an object with action methods and state variables
  return {
    processDocument,
    trashDocument,
    isProcessing,
    processingError,
    isTrashing,
    trashingError
  };
}
import { useState, useCallback, useEffect } from 'react'; // react 18.x
import { Document, DocumentMetadataUpdate, PanelType } from '../models/document.types';
import { 
  getDocument, 
  updateDocumentMetadata, 
  processDocument as processDocumentService, 
  trashDocument as trashDocumentService 
} from '../services/documentService';
import { useDocumentHistory } from './useDocumentHistory';

/**
 * Custom hook for managing document state and operations
 * Centralizes document loading, metadata updates, processing actions, and state management
 * 
 * @param documentId - ID of the document to manage, or undefined
 * @returns Object containing document state and operations
 */
export function useDocumentState(documentId: number | undefined) {
  // Document state
  const [document, setDocument] = useState<Document | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  // Metadata update state
  const [isSaving, setIsSaving] = useState<boolean>(false);
  const [saveError, setSaveError] = useState<string | null>(null);

  // Document processing state
  const [isProcessing, setIsProcessing] = useState<boolean>(false);
  const [processingError, setProcessingError] = useState<string | null>(null);

  // Document trashing state
  const [isTrashing, setIsTrashing] = useState<boolean>(false);
  const [trashingError, setTrashingError] = useState<string | null>(null);

  // Active panel state (metadata or history)
  const [activePanel, setActivePanel] = useState<PanelType>(PanelType.METADATA);
  
  // Get document history state from the history hook
  const { 
    history, 
    isLoading: isHistoryLoading, 
    error: historyError, 
    refreshHistory 
  } = useDocumentHistory(documentId);

  /**
   * Fetches document data from the API
   * 
   * @param id - The document ID to fetch
   * @returns Promise resolving to document data or null if operation failed
   */
  const fetchDocument = useCallback(async (id: number): Promise<Document | null> => {
    try {
      setIsLoading(true);
      setError(null);
      
      const documentData = await getDocument(id);
      setDocument(documentData);
      return documentData;
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to load document';
      setError(errorMessage);
      return null;
    } finally {
      setIsLoading(false);
    }
  }, []);
  
  /**
   * Updates document metadata
   * 
   * @param metadata - Updated metadata fields
   * @returns Promise resolving to updated document data or null if operation failed
   */
  const updateMetadata = useCallback(async (metadata: DocumentMetadataUpdate): Promise<Document | null> => {
    if (!documentId || !document) return null;
    
    try {
      setIsSaving(true);
      setSaveError(null);
      
      const updatedDocument = await updateDocumentMetadata(documentId, metadata);
      setDocument(updatedDocument);
      return updatedDocument;
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to update document metadata';
      setSaveError(errorMessage);
      return null;
    } finally {
      setIsSaving(false);
    }
  }, [documentId, document]);
  
  /**
   * Marks a document as processed or unprocessed
   * 
   * @param processed - Boolean indicating whether to mark as processed (true) or unprocessed (false)
   * @returns Promise resolving to updated document data or null if operation failed
   */
  const handleProcessDocument = useCallback(async (processed: boolean): Promise<Document | null> => {
    if (!documentId || !document) return null;
    
    try {
      setIsProcessing(true);
      setProcessingError(null);
      
      const updatedDocument = await processDocumentService(documentId, processed);
      setDocument(updatedDocument);
      return updatedDocument;
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to process document';
      setProcessingError(errorMessage);
      return null;
    } finally {
      setIsProcessing(false);
    }
  }, [documentId, document]);
  
  /**
   * Moves a document to the trash
   * 
   * @returns Promise resolving to true if operation succeeded, false otherwise
   */
  const handleTrashDocument = useCallback(async (): Promise<boolean> => {
    if (!documentId) return false;
    
    try {
      setIsTrashing(true);
      setTrashingError(null);
      
      await trashDocumentService(documentId);
      return true;
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to trash document';
      setTrashingError(errorMessage);
      return false;
    } finally {
      setIsTrashing(false);
    }
  }, [documentId]);
  
  /**
   * Sets the active panel in the document viewer
   * 
   * @param panel - Panel type to set as active
   */
  const handleSetActivePanel = useCallback((panel: PanelType): void => {
    setActivePanel(panel);
    
    // If switching to history panel, refresh history data
    if (panel === PanelType.HISTORY) {
      refreshHistory();
    }
  }, [refreshHistory]);
  
  // Fetch document when documentId changes
  useEffect(() => {
    if (documentId) {
      fetchDocument(documentId);
    } else {
      // Reset document state if documentId is undefined
      setDocument(null);
    }
  }, [documentId, fetchDocument]);
  
  // Return document state and operations
  return {
    // Document state
    document,
    isLoading,
    error,
    
    // Metadata state
    isSaving,
    saveError,
    
    // Processing state
    isProcessing,
    processingError,
    
    // Trashing state
    isTrashing,
    trashingError,
    
    // Panel state
    activePanel,
    
    // History state
    history,
    isHistoryLoading,
    historyError,
    
    // Operations
    fetchDocument,
    updateMetadata,
    processDocument: handleProcessDocument,
    trashDocument: handleTrashDocument,
    setActivePanel: handleSetActivePanel,
    refreshHistory
  };
}
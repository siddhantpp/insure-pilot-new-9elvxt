import React, { createContext, useContext, ReactNode } from 'react'; // react 18.x
import { Document, DocumentMetadataUpdate, PanelType, DocumentContextType } from '../models/document.types';
import { useDocumentState } from '../hooks/useDocumentState';

/**
 * Context for document-related state and operations
 * Initial value is null and will be provided by DocumentProvider
 */
export const DocumentContext = createContext<DocumentContextType | null>(null);

/**
 * Provider component for document context
 * Centralizes document state management and operations for the Documents View feature
 * 
 * @param children - React children to be wrapped by the provider
 * @returns DocumentContext.Provider with document state and operations
 */
export const DocumentProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  // Use custom hook to manage document state
  const documentState = useDocumentState(undefined);
  
  // Create context value with state and operations from useDocumentState
  const contextValue: DocumentContextType = {
    state: {
      document: documentState.document,
      isLoading: documentState.isLoading,
      error: documentState.error,
      activePanel: documentState.activePanel,
      isSaving: documentState.isSaving,
      saveError: documentState.saveError
    },
    loadDocument: documentState.fetchDocument,
    updateMetadata: documentState.updateMetadata,
    processDocument: documentState.processDocument,
    trashDocument: documentState.trashDocument,
    setActivePanel: documentState.setActivePanel
  };
  
  // Provide context value to children components
  return (
    <DocumentContext.Provider value={contextValue}>
      {children}
    </DocumentContext.Provider>
  );
};

/**
 * Custom hook to access the document context
 * Provides a convenient way for components to access document state and operations
 * 
 * @returns Document context value containing state and operations
 * @throws Error if used outside of DocumentProvider
 */
export const useDocumentContext = (): DocumentContextType => {
  const context = useContext(DocumentContext);
  
  if (context === null) {
    throw new Error('useDocumentContext must be used within a DocumentProvider');
  }
  
  return context;
};
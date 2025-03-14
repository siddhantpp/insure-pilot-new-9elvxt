import React, { useEffect, useState, useCallback } from 'react'; // react 18.x
import {
  DocumentDisplay,
  MetadataPanel,
  HistoryPanel,
} from './';
import { LightboxOverlay } from '../components/layout/LightboxOverlay';
import DualPanelLayout from '../components/layout/DualPanelLayout';
import LoadingIndicator from '../components/common/LoadingIndicator';
import ErrorMessage from '../components/common/ErrorMessage';
import {
  DocumentContext,
  useDocumentContext,
} from '../context/DocumentContext';
import {
  DocumentViewerProps,
  PanelType,
} from '../models/document.types';
import { useKeyboardShortcut } from '../hooks/useKeyboardShortcut';

/**
 * Main container component for the Documents View feature
 * @param props - Component props
 * @returns Rendered document viewer container
 */
const DocumentViewerContainer: React.FC<DocumentViewerProps> = (props) => {
  // Destructure documentId, onClose, and initialPanel from props
  const { documentId, onClose, initialPanel } = props;

  // Get document context using useDocumentContext hook
  const { state, updateMetadata, processDocument, trashDocument, setActivePanel } = useDocumentContext();

  // Extract document, isLoading, error, activePanel, isSaving, and saveError from context state
  const { document, isLoading, error, activePanel, isSaving, saveError } = state;

  // Set up state for document loading status and errors
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);

  // Set up keyboard shortcuts for common actions (Escape to close, Ctrl+S to save)
  useKeyboardShortcut(
    {
      key: 'Escape',
      callback: onClose,
    },
    { enabled: true }
  );

  useKeyboardShortcut(
    {
      key: 's',
      ctrlKey: true,
      callback: () => {
        // Implement save functionality here
        console.log('Ctrl+S triggered - Save functionality');
      },
    },
    { enabled: true }
  );

  // Use useEffect to load document data when documentId changes
  useEffect(() => {
    if (documentId) {
      setLoading(true);
      setLoadError(null);

      // Load document data using the context's loadDocument function
      state.loadDocument(documentId)
        .then(() => {
          setLoading(false);
        })
        .catch((err) => {
          setLoading(false);
          setLoadError(err.message || 'Failed to load document');
        });
    }
  }, [documentId, state.loadDocument]);

  // Use useEffect to set initial active panel when component mounts
  useEffect(() => {
    if (initialPanel) {
      setActivePanel(initialPanel);
    }
  }, [initialPanel, setActivePanel]);

  /**
   * Callback function when document is successfully loaded
   */
  const handleDocumentLoad = useCallback(() => {
    setLoading(false);
    setLoadError(null);
  }, []);

  /**
   * Callback function when document loading fails
   * @param errorMessage - Error message to display
   */
  const handleLoadError = useCallback((errorMessage: string) => {
    setLoading(false);
    setLoadError(errorMessage);
  }, []);

  /**
   * Handles updates to document metadata
   * @param metadata - Updated metadata
   */
  const handleMetadataChange = useCallback(async (metadata: any) => {
    try {
      await updateMetadata(metadata);
    } catch (err) {
      console.error('Error updating metadata:', err);
    }
  }, [updateMetadata]);

  /**
   * Handles marking a document as processed or unprocessed
   * @param processed - Whether the document is processed
   */
  const handleProcessDocument = useCallback(async (processed: boolean) => {
    try {
      await processDocument(processed);
    } catch (err) {
      console.error('Error processing document:', err);
    }
  }, [processDocument]);

  /**
   * Handles moving a document to trash
   */
  const handleTrashDocument = useCallback(async () => {
    try {
      await trashDocument();
      onClose(); // Close the document viewer after trashing
    } catch (err) {
      console.error('Error trashing document:', err);
    }
  }, [trashDocument, onClose]);

  /**
   * Switches to the history panel view
   */
  const handleViewHistory = useCallback(() => {
    setActivePanel(PanelType.HISTORY);
  }, [setActivePanel]);

  /**
   * Switches back to the metadata panel view
   */
  const handleBackToMetadata = useCallback(() => {
    setActivePanel(PanelType.METADATA);
  }, [setActivePanel]);

  /**
   * Handles panel navigation between metadata and history
   * @param panel - The panel type to switch to
   */
  const handlePanelChange = useCallback((panel: PanelType) => {
    setActivePanel(panel);
  }, [setActivePanel]);

  return (
    <LightboxOverlay title="Documents View" onClose={onClose}>
      {isLoading || loading ? (
        <LoadingIndicator message="Loading document..." />
      ) : loadError || error ? (
        <ErrorMessage error={loadError || error} />
      ) : document ? (
        <DualPanelLayout
          leftPanel={
            <DocumentDisplay
              document={document}
              onLoadComplete={handleDocumentLoad}
              onError={handleLoadError}
            />
          }
          rightPanel={
            activePanel === PanelType.METADATA ? (
              <MetadataPanel
                document={document}
                onMetadataChange={handleMetadataChange}
                onProcessDocument={handleProcessDocument}
                onTrashDocument={handleTrashDocument}
                onViewHistory={handleViewHistory}
                isSaving={isSaving}
                saveError={saveError}
              />
            ) : (
              <HistoryPanel
                documentId={documentId}
                onBack={handleBackToMetadata}
              />
            )
          }
          onPanelChange={handlePanelChange}
        />
      ) : (
        <ErrorMessage error="Document not found" />
      )}
    </LightboxOverlay>
  );
};

export default DocumentViewerContainer;
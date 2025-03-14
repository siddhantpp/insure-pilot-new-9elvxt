import React, { useState, useEffect, useCallback } from 'react'; // React 18.x
import { MainLayout } from '../components/layout/MainLayout';
import LoadingIndicator from '../components/common/LoadingIndicator';
import ErrorMessage from '../components/common/ErrorMessage';
import DocumentViewerContainer from '../containers/DocumentViewerContainer';
import { DocumentContext, DocumentProvider } from '../context/DocumentContext';
import { useApi } from '../hooks/useApi';
import { Document, PanelType } from '../models/document.types';
import DocumentStatusBadge from '../components/document/DocumentStatusBadge';

/**
 * Main page component for displaying and managing documents
 * @returns Rendered documents page component
 */
const DocumentsPage: React.FC = () => {
  // Initialize state for documents list, selected document, and viewer visibility
  const [documents, setDocuments] = useState<Document[]>([]);
  const [selectedDocumentId, setSelectedDocumentId] = useState<number | null>(null);
  const [isViewerOpen, setIsViewerOpen] = useState<boolean>(false);

  // Use useApi hook to handle document list retrieval
  const { get, loading, error } = useApi();

  /**
   * Fetches the list of documents from the API
   */
  const fetchDocuments = useCallback(async (): Promise<void> => {
    // Call the API to retrieve document list
    const response = await get<Document[]>('/documents');

    // Update documents state with the retrieved data
    if (response && response.data) {
      setDocuments(response.data);
    }
  }, [get]);

  // Implement useEffect to fetch documents on component mount
  useEffect(() => {
    fetchDocuments();
  }, [fetchDocuments]);

  /**
   * Handles document selection to open in the viewer
   * @param documentId 
   */
  const handleDocumentSelect = useCallback((documentId: number): void => {
    // Set the selected document ID in state
    setSelectedDocumentId(documentId);

    // Set the document viewer visibility to true
    setIsViewerOpen(true);
  }, []);

  /**
   * Handles closing the document viewer
   */
  const handleCloseViewer = useCallback((): void => {
    // Set the document viewer visibility to false
    setIsViewerOpen(false);

    // Clear the selected document ID
    setSelectedDocumentId(null);

    // Refresh the document list to show any updates
    fetchDocuments();
  }, [fetchDocuments]);

  /**
   * Renders the list of documents with filtering options
   */
  const renderDocumentList = useCallback((): JSX.Element => {
    return (
      <div className="document-list">
        {/* Render filter controls for document status and type */}
        {/* Render sorting options for documents */}
        {/* Render the list of document items */}
        {documents.map((document) => (
          <div
            key={document.id}
            className="document-item"
            onClick={() => handleDocumentSelect(document.id)}
          >
            {renderDocumentItem(document)}
          </div>
        ))}
        {/* Include pagination controls if needed */}
      </div>
    );
  }, [documents, handleDocumentSelect]);

  /**
   * Renders an individual document item in the list
   * @param document 
   */
  const renderDocumentItem = useCallback((document: Document): JSX.Element => {
    return (
      <>
        {/* Render document filename and icon */}
        <div className="document-item-header">
          <span className="document-item-filename">{document.filename}</span>
          <DocumentStatusBadge status={document.status} />
        </div>
        {/* Render document status badge */}
        {/* Render key metadata (policy number, document type) */}
        <div className="document-item-metadata">
          <span className="document-item-policy">Policy: {document.metadata.policyNumber}</span>
          <span className="document-item-description">Description: {document.description}</span>
        </div>
        {/* Render last updated information */}
        <div className="document-item-updated">
          Updated: {document.updatedAt}
        </div>
      </>
    );
  }, []);

  return (
    <MainLayout title="Documents">
      {loading && <LoadingIndicator message="Loading documents..." />}
      {error && <ErrorMessage error={error} />}
      {!loading && !error && renderDocumentList()}
      {isViewerOpen && selectedDocumentId !== null && (
        <DocumentProvider>
          <DocumentViewerContainer
            documentId={selectedDocumentId}
            onClose={handleCloseViewer}
          />
        </DocumentProvider>
      )}
    </MainLayout>
  );
};

export default DocumentsPage;
import React from 'react';
import { useParams, useNavigate } from 'react-router-dom'; // version ^6.0.0
import HistoryPanel from '../containers/HistoryPanel';
import { DocumentProvider } from '../context/DocumentContext';

/**
 * Main view component for the Document History feature
 * 
 * Provides a dedicated page for viewing document history and audit trail information.
 * Wraps the HistoryPanel container with routing and context to support the
 * document history viewing flow, enabling users to review document changes
 * and navigate back to the document viewer.
 */
const DocumentHistory: React.FC = () => {
  // Extract documentId from URL parameters
  const { documentId } = useParams<{ documentId: string }>();
  
  // Get navigate function for routing
  const navigate = useNavigate();

  /**
   * Handles navigation back to the document viewer
   */
  const handleBack = () => {
    navigate(`/documents/${documentId}`);
  };

  // Parse documentId to number for HistoryPanel (defaults to 0 if undefined)
  const parsedDocumentId = parseInt(documentId || '0', 10);

  return (
    <div className="document-history-view">
      <DocumentProvider>
        <HistoryPanel 
          documentId={parsedDocumentId} 
          onBack={handleBack} 
        />
      </DocumentProvider>
    </div>
  );
};

export default DocumentHistory;
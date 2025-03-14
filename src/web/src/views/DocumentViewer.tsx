import React from 'react'; // react 18.x - Core React library for component creation
import { useParams, useNavigate } from 'react-router-dom'; // ^6.0.0 - Hooks for accessing URL parameters and navigation
import DocumentViewerContainer from '../containers/DocumentViewerContainer';
import { DocumentProvider } from '../context/DocumentContext';
import { PanelType } from '../models/document.types';

/**
 * Main view component for the Documents View feature
 * @returns {JSX.Element} Rendered document viewer component
 */
const DocumentViewer: React.FC = () => {
  // LD1: Extract documentId from URL parameters using useParams hook
  const { documentId } = useParams<{ documentId: string }>();

  // LD1: Get navigate function from useNavigate hook for navigation
  const navigate = useNavigate();

  /**
   * Handles closing the document viewer and returning to the documents list
   * @returns {void} No return value
   */
  // LD1: Define handleClose function to navigate back to documents list
  const handleClose = () => {
    // LD1: Use navigate function to go back to documents list page
    // LD1: Navigate to '/documents' to return to the documents list view
    navigate('/documents');
  };

  // LD1: Check if documentId is valid before rendering the DocumentViewerContainer
  if (!documentId) {
    return <div>Invalid document ID.</div>;
  }

  const documentIdNumber = parseInt(documentId, 10);

  if (isNaN(documentIdNumber)) {
    return <div>Invalid document ID format.</div>;
  }

  return (
    // LD1: Wrap DocumentViewerContainer with DocumentProvider to provide document context
    <DocumentProvider>
      {/* LD1: Pass documentId, onClose callback, and PanelType.METADATA as initialPanel to DocumentViewerContainer */}
      {/* LD1: Apply appropriate CSS classes for styling */}
      <div className="document-viewer-wrapper">
        <DocumentViewerContainer
          documentId={documentIdNumber}
          onClose={handleClose}
          initialPanel={PanelType.METADATA}
        />
      </div>
    </DocumentProvider>
  );
};

// LD1: Export DocumentViewer component
// IE3: Be generous about your exports so long as it doesn't create a security risk.
export default DocumentViewer;
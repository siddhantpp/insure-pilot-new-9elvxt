import React, { useRef, useEffect, useCallback } from 'react'; // react 18.x
import { Document } from '../../models/document.types';
import { usePdfViewer } from '../../hooks/usePdfViewer';
import { useDocumentContext } from '../../context/DocumentContext';
import PdfControls from './PdfControls';
import PdfNavigation from './PdfNavigation';
import DocumentFilename from '../document/DocumentFilename';
import LoadingIndicator from '../common/LoadingIndicator';
import ErrorMessage from '../common/ErrorMessage';

/**
 * Props interface for the PdfViewer component
 */
export interface PdfViewerProps {
  document: Document;
  onLoadComplete?: () => void;
  onError?: (error: string) => void;
}

/**
 * A React component that renders a PDF document using Adobe Acrobat PDF viewer
 * in the Documents View feature. This component serves as the main document display
 * area in the left panel of the dual-panel layout.
 */
const PdfViewer: React.FC<PdfViewerProps> = ({ 
  document, 
  onLoadComplete, 
  onError 
}) => {
  // Create a ref for the PDF container element
  const containerRef = useRef<HTMLDivElement>(null);
  
  // Access document context for additional state
  const { state } = useDocumentContext();

  // Callback when document is loaded successfully
  const handleLoadComplete = useCallback(() => {
    if (onLoadComplete) {
      onLoadComplete();
    }
  }, [onLoadComplete]);
  
  // Callback when error occurs during document loading or viewing
  const handleError = useCallback((error: string) => {
    if (onError) {
      onError(error);
    }
  }, [onError]);
  
  // Initialize PDF viewer with the document and container ref
  const { 
    isLoading, 
    error 
  } = usePdfViewer(
    document,
    containerRef,
    {
      onLoadComplete: handleLoadComplete,
      onError: handleError
    }
  );

  // Clean up handled by usePdfViewer hook's internal useEffect cleanup

  return (
    <div className="pdf-viewer" aria-label="PDF Document Viewer">
      {/* Document filename display */}
      <DocumentFilename filename={document.filename} />
      
      {/* PDF container where Adobe viewer will be initialized */}
      <div 
        className="pdf-container" 
        ref={containerRef}
        aria-label={`PDF document: ${document.filename}`}
        role="document"
        tabIndex={0}
      >
        {/* Show loading indicator while document is loading */}
        {isLoading && (
          <LoadingIndicator message="Loading document..." />
        )}
        
        {/* Show error message if loading fails */}
        {error && (
          <ErrorMessage error={error} />
        )}
      </div>
      
      {/* PDF controls for zoom and rotation */}
      <PdfControls />
      
      {/* Page navigation controls */}
      <PdfNavigation />
    </div>
  );
};

export default PdfViewer;
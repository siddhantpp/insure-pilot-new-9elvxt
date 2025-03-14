import React, { useRef, useEffect, useCallback, useState } from 'react';
import PdfViewer from '../components/pdf/PdfViewer';
import DocumentFilename from '../components/document/DocumentFilename';
import LoadingIndicator from '../components/common/LoadingIndicator';
import ErrorMessage from '../components/common/ErrorMessage';
import { Document, DocumentDisplayProps } from '../models/document.types';
import { useDocumentContext } from '../context/DocumentContext';

/**
 * A container component that renders the document display area in the left panel
 * of the Documents View feature. It integrates with Adobe Acrobat PDF viewer to display
 * document content and provides document navigation controls, loading states, and error handling.
 */
const DocumentDisplay: React.FC<DocumentDisplayProps> = ({
  document,
  onLoadComplete,
  onError
}) => {
  // Create a container ref for the document display area
  const containerRef = useRef<HTMLDivElement>(null);
  
  // Access document context for state and operations
  const { state } = useDocumentContext();
  
  // Local state for handling loading and errors
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  
  /**
   * Callback function when document is successfully loaded
   */
  const handleLoadComplete = useCallback(() => {
    setIsLoading(false);
    setError(null);
    
    if (onLoadComplete) {
      onLoadComplete();
    }
  }, [onLoadComplete]);
  
  /**
   * Callback function when an error occurs during document loading or viewing
   */
  const handleError = useCallback((errorMessage: string) => {
    setIsLoading(false);
    setError(errorMessage);
    
    if (onError) {
      onError(errorMessage);
    }
  }, [onError]);
  
  // Clean up resources when component unmounts
  useEffect(() => {
    return () => {
      // Any necessary cleanup will be handled by PdfViewer's internal cleanup
    };
  }, []);
  
  return (
    <div 
      className="document-display" 
      ref={containerRef}
      aria-label="Document display panel"
      data-testid="document-display"
    >
      {/* The document display container renders the PDF viewer component
          which handles rendering the document with Adobe Acrobat PDF viewer */}
      <PdfViewer
        document={document}
        onLoadComplete={handleLoadComplete}
        onError={handleError}
      />
      
      {/* Show loading indicator while the document is loading
          This overlay will be shown on top of the PDF viewer */}
      {isLoading && (
        <div className="document-display-overlay">
          <LoadingIndicator message="Loading document..." />
        </div>
      )}
      
      {/* Show error message if there was a problem loading the document */}
      {error && (
        <div className="document-display-overlay">
          <ErrorMessage error={error} />
        </div>
      )}
    </div>
  );
};

export default DocumentDisplay;
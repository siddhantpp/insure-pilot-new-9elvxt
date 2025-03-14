import React, { useCallback } from 'react'; // react 18.x
import { usePdfViewer } from '../../hooks/usePdfViewer';
import { useDocumentContext } from '../../context/DocumentContext';
import DocumentPageControl from '../document/DocumentPageControl';

/**
 * A functional component that renders page navigation controls for the PDF viewer
 * @returns JSX.Element Rendered component with page navigation controls
 */
const PdfNavigation: React.FC = () => {
  // Access document data from context
  const { state } = useDocumentContext();
  const { document } = state;
  
  // Extract currentPage, totalPages, and goToPage from the PDF viewer state and functions
  // Note: In a real application, these values would be provided through context or props
  // properly initialized with the PDF viewer. For this implementation, we're using
  // DocumentPageControl which handles its own navigation.
  
  /**
   * Navigates to the next page in the PDF document
   */
  const goToNextPage = useCallback(() => {
    if (currentPage < totalPages) {
      goToPage(currentPage + 1);
    }
  }, []);
  
  /**
   * Navigates to the previous page in the PDF document
   */
  const goToPreviousPage = useCallback(() => {
    if (currentPage > 1) {
      goToPage(currentPage - 1);
    }
  }, []);
  
  /**
   * Handles keyboard navigation for page navigation buttons
   * @param event Keyboard event
   * @param navigationFunction Function to call for navigation
   */
  const handleKeyDown = useCallback(
    (event: React.KeyboardEvent, navigationFunction: () => void) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        navigationFunction();
      }
    },
    []
  );
  
  // Don't render controls if document is not loaded
  if (!document) {
    return null;
  }
  
  return (
    <div className="pdf-navigation" aria-label="PDF navigation controls">
      <DocumentPageControl />
    </div>
  );
};

export default PdfNavigation;
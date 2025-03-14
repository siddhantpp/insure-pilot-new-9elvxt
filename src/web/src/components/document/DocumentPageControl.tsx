import React, { useCallback, useState } from 'react'; // react 18.x
import { usePdfViewer } from '../../hooks/usePdfViewer';
import { useDocumentContext } from '../../context/DocumentContext';

/**
 * A React component that provides page navigation controls for PDF documents in the Documents View feature.
 * It displays the current page number, total pages, and navigation buttons to move between pages.
 * 
 * @returns JSX.Element - Rendered component with page navigation controls
 */
const DocumentPageControl: React.FC = () => {
  // Access document data from context
  const { state } = useDocumentContext();
  const { document } = state;

  // Access PDF viewer functionality for page navigation
  // In a real implementation, we would need to get viewer state from a parent component
  // that has the containerRef. For this component, we use placeholder values
  // that would normally come from a parent component using usePdfViewer.
  const currentPage = 1;
  const totalPages = document ? 10 : 0;
  const goToPage = (page: number) => console.log(`Navigate to page ${page}`);

  // State for direct page input
  const [pageInput, setPageInput] = useState<string>(currentPage.toString());

  /**
   * Handles changes to the page input field
   * @param event Change event from input element
   */
  const handlePageInputChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    setPageInput(event.target.value);
  };

  /**
   * Handles key down events in the page input field
   * @param event Keyboard event from input element
   */
  const handlePageInputKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      
      // Convert input to number and validate
      const pageNumber = parseInt(pageInput, 10);
      if (!isNaN(pageNumber) && pageNumber >= 1 && pageNumber <= totalPages) {
        goToPage(pageNumber);
      } else {
        // Reset input to current page if invalid
        setPageInput(currentPage.toString());
      }
      
      // Remove focus from input
      event.currentTarget.blur();
    }
  };

  /**
   * Handles keyboard navigation for page control buttons
   * @param event Keyboard event
   * @param navigationFunction Function to call for navigation
   */
  const handleKeyboardNavigation = useCallback(
    (event: React.KeyboardEvent, navigationFunction: () => void) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        navigationFunction();
      }
    },
    []
  );

  // Navigation functions
  const goToNextPage = useCallback(() => {
    if (currentPage < totalPages) {
      goToPage(currentPage + 1);
    }
  }, [currentPage, totalPages, goToPage]);

  const goToPreviousPage = useCallback(() => {
    if (currentPage > 1) {
      goToPage(currentPage - 1);
    }
  }, [currentPage, goToPage]);

  // Update page input when current page changes
  React.useEffect(() => {
    setPageInput(currentPage.toString());
  }, [currentPage]);

  // Don't render controls if document is not loaded or totalPages is 0
  if (!document || totalPages === 0) {
    return null;
  }

  return (
    <div className="document-page-control" aria-label="Page navigation">
      <button
        className="page-control-button prev-page"
        onClick={goToPreviousPage}
        onKeyDown={(e) => handleKeyboardNavigation(e, goToPreviousPage)}
        disabled={currentPage <= 1}
        aria-label="Previous page"
        tabIndex={0}
      >
        <span aria-hidden="true">&#8592;</span>
      </button>
      
      <div className="page-info">
        <span className="page-label">Page</span>
        <input
          type="text"
          className="page-input"
          value={pageInput}
          onChange={handlePageInputChange}
          onKeyDown={handlePageInputKeyDown}
          aria-label={`Page ${currentPage} of ${totalPages}`}
          size={3}
        />
        <span className="page-total">of {totalPages}</span>
      </div>
      
      <button
        className="page-control-button next-page"
        onClick={goToNextPage}
        onKeyDown={(e) => handleKeyboardNavigation(e, goToNextPage)}
        disabled={currentPage >= totalPages}
        aria-label="Next page"
        tabIndex={0}
      >
        <span aria-hidden="true">&#8594;</span>
      </button>
    </div>
  );
};

export default DocumentPageControl;
import React, { useCallback } from 'react'; // react 18.x
import { usePdfViewer } from '../../hooks/usePdfViewer';
import { ZoomLevel } from '../../lib/adobeViewer';
import { useDocumentContext } from '../../context/DocumentContext';

/**
 * Component that provides zoom and rotation controls for the PDF document viewer
 * Renders buttons for adjusting the zoom level and rotating the document,
 * enhancing the user's ability to view document content effectively.
 * 
 * @returns JSX.Element - Rendered component with zoom and rotation controls
 */
const PdfControls: React.FC = () => {
  // Access PDF viewer state and functions
  const { viewer, zoomLevel, setZoomLevel } = usePdfViewer();
  
  // Get document context (for future context-dependent controls)
  const { state } = useDocumentContext();
  
  /**
   * Increases the zoom level of the PDF document
   */
  const handleZoomIn = useCallback(() => {
    // Determine next zoom level based on current level
    let newZoomLevel: ZoomLevel;
    
    switch (zoomLevel) {
      case ZoomLevel.ZOOM_50:
        newZoomLevel = ZoomLevel.ZOOM_100;
        break;
      case ZoomLevel.ZOOM_100:
        newZoomLevel = ZoomLevel.ZOOM_150;
        break;
      case ZoomLevel.ZOOM_150:
        newZoomLevel = ZoomLevel.ZOOM_200;
        break;
      case ZoomLevel.ZOOM_200:
        // Already at maximum zoom
        return;
      default:
        // For FIT_WIDTH, FIT_PAGE, or any other level
        newZoomLevel = ZoomLevel.ZOOM_100;
    }
    
    setZoomLevel(newZoomLevel);
  }, [zoomLevel, setZoomLevel]);
  
  /**
   * Decreases the zoom level of the PDF document
   */
  const handleZoomOut = useCallback(() => {
    // Determine next zoom level based on current level
    let newZoomLevel: ZoomLevel;
    
    switch (zoomLevel) {
      case ZoomLevel.ZOOM_200:
        newZoomLevel = ZoomLevel.ZOOM_150;
        break;
      case ZoomLevel.ZOOM_150:
        newZoomLevel = ZoomLevel.ZOOM_100;
        break;
      case ZoomLevel.ZOOM_100:
        newZoomLevel = ZoomLevel.ZOOM_50;
        break;
      case ZoomLevel.ZOOM_50:
        // Already at minimum zoom
        return;
      default:
        // For FIT_WIDTH, FIT_PAGE, or any other level
        newZoomLevel = ZoomLevel.ZOOM_50;
    }
    
    setZoomLevel(newZoomLevel);
  }, [zoomLevel, setZoomLevel]);
  
  /**
   * Sets the zoom level to fit the document width
   */
  const handleFitToWidth = useCallback(() => {
    setZoomLevel(ZoomLevel.FIT_WIDTH);
  }, [setZoomLevel]);
  
  /**
   * Sets the zoom level to fit the entire page
   */
  const handleFitToPage = useCallback(() => {
    setZoomLevel(ZoomLevel.FIT_PAGE);
  }, [setZoomLevel]);
  
  /**
   * Rotates the document 90 degrees clockwise
   */
  const handleRotateClockwise = useCallback(() => {
    if (!viewer) return;
    
    try {
      viewer.rotateClockwise();
    } catch (error) {
      console.error('Error rotating document clockwise:', error);
    }
  }, [viewer]);
  
  /**
   * Rotates the document 90 degrees counter-clockwise
   */
  const handleRotateCounterClockwise = useCallback(() => {
    if (!viewer) return;
    
    try {
      viewer.rotateCounterClockwise();
    } catch (error) {
      console.error('Error rotating document counter-clockwise:', error);
    }
  }, [viewer]);
  
  /**
   * Handles keyboard navigation for control buttons
   * @param event Keyboard event
   * @param actionFunction Function to call when Enter or Space is pressed
   */
  const handleKeyDown = useCallback((event: React.KeyboardEvent, actionFunction: () => void) => {
    // Trigger action on Enter or Space key
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      actionFunction();
    }
  }, []);
  
  return (
    <div className="pdf-controls" role="toolbar" aria-label="PDF viewer controls">
      {/* Zoom out button */}
      <button
        type="button"
        onClick={handleZoomOut}
        onKeyDown={(e) => handleKeyDown(e, handleZoomOut)}
        aria-label="Zoom out"
        title="Zoom out"
        className="pdf-control-button"
        tabIndex={0}
      >
        <span className="icon-zoom-out" aria-hidden="true">−</span>
      </button>
      
      {/* Zoom in button */}
      <button
        type="button"
        onClick={handleZoomIn}
        onKeyDown={(e) => handleKeyDown(e, handleZoomIn)}
        aria-label="Zoom in"
        title="Zoom in"
        className="pdf-control-button"
        tabIndex={0}
      >
        <span className="icon-zoom-in" aria-hidden="true">+</span>
      </button>
      
      {/* Fit to width button */}
      <button
        type="button"
        onClick={handleFitToWidth}
        onKeyDown={(e) => handleKeyDown(e, handleFitToWidth)}
        aria-label="Fit to width"
        title="Fit to width"
        className={`pdf-control-button ${zoomLevel === ZoomLevel.FIT_WIDTH ? 'active' : ''}`}
        tabIndex={0}
      >
        <span className="icon-fit-width" aria-hidden="true">⮂</span>
      </button>
      
      {/* Fit to page button */}
      <button
        type="button"
        onClick={handleFitToPage}
        onKeyDown={(e) => handleKeyDown(e, handleFitToPage)}
        aria-label="Fit to page"
        title="Fit to page"
        className={`pdf-control-button ${zoomLevel === ZoomLevel.FIT_PAGE ? 'active' : ''}`}
        tabIndex={0}
      >
        <span className="icon-fit-page" aria-hidden="true">⧉</span>
      </button>
      
      {/* Rotate clockwise button */}
      <button
        type="button"
        onClick={handleRotateClockwise}
        onKeyDown={(e) => handleKeyDown(e, handleRotateClockwise)}
        aria-label="Rotate clockwise"
        title="Rotate clockwise"
        className="pdf-control-button"
        tabIndex={0}
      >
        <span className="icon-rotate-cw" aria-hidden="true">↻</span>
      </button>
      
      {/* Rotate counter-clockwise button */}
      <button
        type="button"
        onClick={handleRotateCounterClockwise}
        onKeyDown={(e) => handleKeyDown(e, handleRotateCounterClockwise)}
        aria-label="Rotate counter-clockwise"
        title="Rotate counter-clockwise"
        className="pdf-control-button"
        tabIndex={0}
      >
        <span className="icon-rotate-ccw" aria-hidden="true">↺</span>
      </button>
    </div>
  );
};

export default PdfControls;
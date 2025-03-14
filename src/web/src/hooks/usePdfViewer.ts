import { useState, useEffect, useRef, useCallback, MutableRefObject } from 'react'; // v18.x

import { Document } from '../models/document.types';
import {
  initializeAdobeViewer,
  renderPdf,
  registerViewerEventHandlers,
  navigateToPage,
  getCurrentPage,
  getTotalPages,
  setZoom,
  destroyViewer,
  ViewerEventType,
  ZoomLevel
} from '../lib/adobeViewer';

/**
 * Interface for PDF viewer options
 */
export interface PdfViewerOptions {
  /**
   * Optional callback when document is fully loaded
   */
  onLoadComplete?: () => void;
  
  /**
   * Optional callback when an error occurs
   */
  onError?: (error: string) => void;
  
  /**
   * Optional initial zoom level for the document
   */
  initialZoom?: ZoomLevel;
}

/**
 * Custom React hook that manages the integration with Adobe Acrobat PDF viewer.
 * This hook encapsulates the initialization, rendering, navigation, and event handling
 * for PDF documents, providing a clean interface for components to interact with.
 * 
 * @param document - The document to display in the viewer
 * @param containerRef - Reference to the container element
 * @param options - Optional configuration for the PDF viewer
 * @returns Object containing viewer state and control functions
 */
const usePdfViewer = (
  document: Document | null,
  containerRef: MutableRefObject<HTMLDivElement | null>,
  options: PdfViewerOptions = {}
) => {
  // State for viewer instance
  const [viewer, setViewer] = useState<any>(null);
  // State for loading status
  const [isLoading, setIsLoading] = useState<boolean>(false);
  // State for error message
  const [error, setError] = useState<string | null>(null);
  // State for current page number
  const [currentPage, setCurrentPage] = useState<number>(1);
  // State for total number of pages
  const [totalPages, setTotalPages] = useState<number>(0);
  // State for current zoom level
  const [zoomLevel, setZoomLevel] = useState<ZoomLevel>(
    options.initialZoom || ZoomLevel.FIT_WIDTH
  );

  /**
   * Initializes the Adobe PDF viewer
   */
  const initializeViewer = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    
    if (!containerRef.current) {
      setIsLoading(false);
      setError('PDF viewer container not found');
      return;
    }
    
    try {
      const adobeViewer = await initializeAdobeViewer(containerRef.current);
      setViewer(adobeViewer);
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to initialize PDF viewer';
      setError(errorMessage);
      if (options.onError) {
        options.onError(errorMessage);
      }
    } finally {
      setIsLoading(false);
    }
  }, [containerRef, options]);

  /**
   * Loads a document into the PDF viewer
   */
  const loadDocument = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    
    if (!viewer || !document) {
      setIsLoading(false);
      return;
    }
    
    try {
      await renderPdf(viewer, document);
      // Note: Document loaded event will be handled by the registered event handler
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to load document';
      setError(errorMessage);
      if (options.onError) {
        options.onError(errorMessage);
      }
      setIsLoading(false);
    }
  }, [viewer, document, options]);

  /**
   * Callback for when document is loaded in the viewer
   */
  const handleDocumentLoaded = useCallback(() => {
    setIsLoading(false);
    if (viewer) {
      setTotalPages(getTotalPages(viewer));
      setCurrentPage(getCurrentPage(viewer));
    }
    if (options.onLoadComplete) {
      options.onLoadComplete();
    }
  }, [viewer, options]);

  /**
   * Callback for when page changes in the viewer
   */
  const handlePageChanged = useCallback((event: any) => {
    const pageNumber = event.pageNumber;
    if (typeof pageNumber === 'number') {
      setCurrentPage(pageNumber);
    }
  }, []);

  /**
   * Callback for when zoom level changes in the viewer
   */
  const handleZoomChanged = useCallback((event: any) => {
    const zoomValue = event.zoomValue;
    // Map Adobe zoom values to our ZoomLevel enum
    const zoomMapping: Record<string, ZoomLevel> = {
      'FitWidth': ZoomLevel.FIT_WIDTH,
      'FitPage': ZoomLevel.FIT_PAGE,
      '50%': ZoomLevel.ZOOM_50,
      '100%': ZoomLevel.ZOOM_100,
      '150%': ZoomLevel.ZOOM_150,
      '200%': ZoomLevel.ZOOM_200
    };
    if (zoomValue && zoomMapping[zoomValue]) {
      setZoomLevel(zoomMapping[zoomValue]);
    }
  }, []);

  /**
   * Callback for handling viewer errors
   */
  const handleError = useCallback((event: any) => {
    const errorMessage = event?.message || 'An error occurred with the PDF viewer';
    setError(errorMessage);
    setIsLoading(false);
    if (options.onError) {
      options.onError(errorMessage);
    }
  }, [options]);

  /**
   * Navigates to a specific page in the document
   * @param pageNumber - The page number to navigate to
   */
  const goToPage = useCallback(async (pageNumber: number) => {
    if (!viewer || typeof pageNumber !== 'number' || pageNumber < 1 || pageNumber > totalPages) {
      return;
    }
    
    try {
      await navigateToPage(viewer, pageNumber);
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to navigate to page';
      setError(errorMessage);
      if (options.onError) {
        options.onError(errorMessage);
      }
    }
  }, [viewer, totalPages, options]);

  /**
   * Sets the zoom level of the document
   * @param level - The zoom level to set
   */
  const setZoomLevel = useCallback(async (level: ZoomLevel) => {
    if (!viewer) {
      return;
    }
    
    try {
      await setZoom(viewer, level);
      setZoomLevel(level);
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to set zoom level';
      setError(errorMessage);
      if (options.onError) {
        options.onError(errorMessage);
      }
    }
  }, [viewer, options]);

  // Initialize viewer when container ref is available
  useEffect(() => {
    if (containerRef.current && !viewer) {
      initializeViewer();
    }
  }, [containerRef, viewer, initializeViewer]);

  // Load document when viewer is initialized and document changes
  useEffect(() => {
    if (viewer && document) {
      loadDocument();
    }
  }, [viewer, document, loadDocument]);

  // Register event handlers when viewer is initialized
  useEffect(() => {
    if (viewer) {
      registerViewerEventHandlers(viewer, {
        [ViewerEventType.DOCUMENT_LOADED]: handleDocumentLoaded,
        [ViewerEventType.PAGE_CHANGED]: handlePageChanged,
        [ViewerEventType.ZOOM_CHANGED]: handleZoomChanged,
        [ViewerEventType.ERROR]: handleError
      });
    }
  }, [viewer, handleDocumentLoaded, handlePageChanged, handleZoomChanged, handleError]);

  // Clean up viewer on unmount
  useEffect(() => {
    return () => {
      if (viewer) {
        destroyViewer(viewer);
      }
    };
  }, [viewer]);

  return {
    viewer,
    isLoading,
    error,
    currentPage,
    totalPages,
    zoomLevel,
    goToPage,
    setZoomLevel
  };
};

export default usePdfViewer;
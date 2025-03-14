/**
 * Adobe Acrobat PDF Viewer integration utility
 * This file provides a set of utilities for integrating with the Adobe Acrobat PDF viewer,
 * including initialization, rendering, navigation, and event handling.
 * 
 * @module adobeViewer
 */

import { Document } from '../models/document.types';

/**
 * URL for the Adobe Acrobat PDF Viewer SDK script
 * @version latest
 */
const ADOBE_SDK_URL = 'https://documentcloud.adobe.com/view-sdk/main.js';

/**
 * API key for Adobe PDF Viewer SDK
 * Retrieved from environment variables
 */
const ADOBE_API_KEY = process.env.REACT_APP_ADOBE_API_KEY || '';

/**
 * Enum for Adobe viewer event types
 */
export enum ViewerEventType {
  /** Fired when a document is fully loaded */
  DOCUMENT_LOADED = 'DOCUMENT_LOADED',
  /** Fired when the current page changes */
  PAGE_CHANGED = 'PAGE_CHANGED',
  /** Fired when the zoom level changes */
  ZOOM_CHANGED = 'ZOOM_CHANGED',
  /** Fired when an error occurs */
  ERROR = 'ERROR'
}

/**
 * Enum for PDF zoom levels
 */
export enum ZoomLevel {
  /** Fit the document width to the viewer */
  FIT_WIDTH = 'FIT_WIDTH',
  /** Fit the entire page in the viewer */
  FIT_PAGE = 'FIT_PAGE',
  /** 50% zoom level */
  ZOOM_50 = 'ZOOM_50',
  /** 100% zoom level (actual size) */
  ZOOM_100 = 'ZOOM_100',
  /** 150% zoom level */
  ZOOM_150 = 'ZOOM_150',
  /** 200% zoom level */
  ZOOM_200 = 'ZOOM_200'
}

/**
 * Checks if the Adobe SDK is already loaded
 * @returns boolean indicating if Adobe SDK is available
 */
const isAdobeSDKLoaded = (): boolean => {
  return typeof (window as any).AdobeDC !== 'undefined';
};

/**
 * Loads the Adobe SDK script dynamically
 * @returns A promise that resolves when the script is loaded
 */
const loadAdobeSDK = (): Promise<void> => {
  return new Promise((resolve, reject) => {
    // Check if the script is already loaded or loading
    if (document.querySelector(`script[src="${ADOBE_SDK_URL}"]`)) {
      // If the script tag exists but SDK is not loaded yet, wait for it
      if (!isAdobeSDKLoaded()) {
        const checkInterval = setInterval(() => {
          if (isAdobeSDKLoaded()) {
            clearInterval(checkInterval);
            resolve();
          }
        }, 100);
        
        // Set a timeout to avoid infinite waiting
        setTimeout(() => {
          clearInterval(checkInterval);
          reject(new Error('Timeout waiting for Adobe SDK to load'));
        }, 10000);
      } else {
        // SDK is already loaded
        resolve();
      }
      return;
    }
    
    // Create and append the script tag
    const script = document.createElement('script');
    script.src = ADOBE_SDK_URL;
    script.async = true;
    script.onload = () => {
      // Wait for the SDK to be fully initialized
      const checkInterval = setInterval(() => {
        if (isAdobeSDKLoaded()) {
          clearInterval(checkInterval);
          resolve();
        }
      }, 100);
      
      // Set a timeout to avoid infinite waiting
      setTimeout(() => {
        clearInterval(checkInterval);
        reject(new Error('Timeout waiting for Adobe SDK to initialize'));
      }, 10000);
    };
    script.onerror = () => reject(new Error('Failed to load Adobe SDK script'));
    document.head.appendChild(script);
  });
};

/**
 * Initializes the Adobe Acrobat PDF viewer SDK and creates a viewer instance
 * @param container - The HTML element to render the viewer in
 * @returns A promise that resolves with the initialized viewer instance
 */
export const initializeAdobeViewer = async (container: HTMLElement): Promise<any> => {
  if (!container) {
    throw new Error('Container element is required');
  }
  
  if (!container.id) {
    container.id = `adobe-pdf-viewer-${Date.now()}`;
  }
  
  // Check if the Adobe SDK is already loaded
  if (!isAdobeSDKLoaded()) {
    try {
      // Load the Adobe SDK script
      await loadAdobeSDK();
    } catch (error) {
      console.error('Error loading Adobe SDK:', error);
      throw new Error('Failed to load Adobe PDF Viewer SDK');
    }
  }
  
  try {
    // Initialize the Adobe DC View SDK
    const adobeDCView = new (window as any).AdobeDC.View({
      clientId: ADOBE_API_KEY,
      divId: container.id
    });
    
    if (!adobeDCView) {
      throw new Error('Failed to create Adobe DC View instance');
    }
    
    // Configure viewer options for accessibility and performance
    adobeDCView.configureOptions({
      enableAccessibilityMode: true, // Enable accessibility features
      showDownloadPDF: false,        // Hide download button
      showPrintPDF: false,           // Hide print button
      showAnnotationTools: false,    // Hide annotation tools
      enableFormFilling: false,      // Disable form filling
      showLeftHandPanel: false,      // Hide left panel
      showPageControls: true         // Show page navigation controls
    });
    
    return adobeDCView;
  } catch (error) {
    console.error('Error initializing Adobe PDF viewer:', error);
    throw new Error('Failed to initialize Adobe PDF viewer');
  }
};

/**
 * Renders a PDF document in the Adobe viewer
 * @param viewer - The initialized Adobe viewer instance
 * @param document - The document to render
 * @returns A promise that resolves when the document is rendered
 */
export const renderPdf = async (viewer: any, document: Document): Promise<void> => {
  if (!viewer) {
    throw new Error('Viewer not initialized');
  }
  
  if (!document) {
    throw new Error('Document is required');
  }
  
  if (!document.fileUrl) {
    throw new Error('Document fileUrl is required');
  }
  
  try {
    // Start timing for performance metrics
    const startTime = performance.now();
    
    // Create a promise that will resolve when the document is loaded
    const loadPromise = new Promise<void>((resolve, reject) => {
      // Set a timeout to prevent hanging if the document fails to load
      const timeoutId = setTimeout(() => {
        reject(new Error('Timeout waiting for document to load'));
      }, 30000); // 30 second timeout
      
      const eventOptions = {
        listenOn: [ViewerEventType.DOCUMENT_LOADED, ViewerEventType.ERROR],
        handler: (event: any) => {
          if (event.type === ViewerEventType.DOCUMENT_LOADED) {
            clearTimeout(timeoutId);
            const endTime = performance.now();
            const loadTime = endTime - startTime;
            console.log(`Document loaded in ${loadTime.toFixed(2)}ms`);
            resolve();
          } else if (event.type === ViewerEventType.ERROR) {
            clearTimeout(timeoutId);
            reject(new Error(event.data || 'Error loading document'));
          }
        }
      };
      
      viewer.registerCallback(eventOptions);
    });
    
    // Render the PDF file
    viewer.previewFile({
      content: { location: { url: document.fileUrl } },
      metaData: { fileName: document.filename || 'document.pdf' }
    });
    
    return loadPromise;
  } catch (error) {
    console.error('Error rendering PDF:', error);
    throw new Error('Failed to render PDF document');
  }
};

/**
 * Registers event handlers for the Adobe viewer
 * @param viewer - The initialized Adobe viewer instance
 * @param handlers - Object containing handler functions for different event types
 */
export const registerViewerEventHandlers = (
  viewer: any,
  handlers: Record<ViewerEventType, Function>
): void => {
  if (!viewer) {
    throw new Error('Viewer not initialized');
  }
  
  if (!handlers || typeof handlers !== 'object') {
    throw new Error('Handlers object is required');
  }
  
  // Register handlers for each event type
  Object.entries(handlers).forEach(([eventType, handler]) => {
    if (Object.values(ViewerEventType).includes(eventType as ViewerEventType)) {
      if (typeof handler !== 'function') {
        console.warn(`Handler for event type ${eventType} is not a function`);
        return;
      }
      
      viewer.registerCallback({
        listenOn: [eventType],
        handler: (event: any) => {
          handler(event.data);
        }
      });
    } else {
      console.warn(`Unknown event type: ${eventType}`);
    }
  });
};

/**
 * Navigates to a specific page in the PDF document
 * @param viewer - The initialized Adobe viewer instance
 * @param pageNumber - The page number to navigate to (1-based index)
 * @returns A promise that resolves when navigation is complete
 */
export const navigateToPage = async (viewer: any, pageNumber: number): Promise<void> => {
  if (!viewer) {
    throw new Error('Viewer not initialized');
  }
  
  if (typeof pageNumber !== 'number' || pageNumber < 1) {
    throw new Error('Page number must be a positive integer');
  }
  
  try {
    const totalPages = getTotalPages(viewer);
    
    // Validate page number
    if (pageNumber > totalPages) {
      throw new Error(`Page number out of range (1-${totalPages})`);
    }
    
    // Create a promise that will resolve when the page change is complete
    const pageChangePromise = new Promise<void>((resolve, reject) => {
      const timeoutId = setTimeout(() => {
        reject(new Error('Timeout waiting for page change'));
      }, 5000); // 5 second timeout
      
      viewer.registerCallback({
        listenOn: [ViewerEventType.PAGE_CHANGED],
        handler: () => {
          clearTimeout(timeoutId);
          resolve();
        }
      });
    });
    
    // Navigate to the specified page
    viewer.gotoPage(pageNumber);
    
    return pageChangePromise;
  } catch (error) {
    console.error('Error navigating to page:', error);
    throw new Error('Failed to navigate to page');
  }
};

/**
 * Gets the current page number from the viewer
 * @param viewer - The initialized Adobe viewer instance
 * @returns The current page number (1-based index)
 */
export const getCurrentPage = (viewer: any): number => {
  if (!viewer) {
    throw new Error('Viewer not initialized');
  }
  
  try {
    const currentPage = viewer.getCurrentPage();
    return typeof currentPage === 'number' ? currentPage : 1;
  } catch (error) {
    console.error('Error getting current page:', error);
    return 1;
  }
};

/**
 * Gets the total number of pages in the current document
 * @param viewer - The initialized Adobe viewer instance
 * @returns The total number of pages
 */
export const getTotalPages = (viewer: any): number => {
  if (!viewer) {
    throw new Error('Viewer not initialized');
  }
  
  try {
    const totalPages = viewer.getTotalPages();
    return typeof totalPages === 'number' ? totalPages : 0;
  } catch (error) {
    console.error('Error getting total pages:', error);
    return 0;
  }
};

/**
 * Sets the zoom level of the PDF document
 * @param viewer - The initialized Adobe viewer instance
 * @param zoomLevel - The zoom level to set
 * @returns A promise that resolves when zoom is applied
 */
export const setZoom = async (viewer: any, zoomLevel: ZoomLevel): Promise<void> => {
  if (!viewer) {
    throw new Error('Viewer not initialized');
  }
  
  if (!Object.values(ZoomLevel).includes(zoomLevel)) {
    throw new Error('Invalid zoom level');
  }
  
  try {
    // Map ZoomLevel enum to Adobe viewer zoom values
    const zoomMap: Record<ZoomLevel, string> = {
      [ZoomLevel.FIT_WIDTH]: 'FitWidth',
      [ZoomLevel.FIT_PAGE]: 'FitPage',
      [ZoomLevel.ZOOM_50]: '50%',
      [ZoomLevel.ZOOM_100]: '100%',
      [ZoomLevel.ZOOM_150]: '150%',
      [ZoomLevel.ZOOM_200]: '200%'
    };
    
    // Create a promise that will resolve when the zoom change is complete
    const zoomChangePromise = new Promise<void>((resolve, reject) => {
      const timeoutId = setTimeout(() => {
        reject(new Error('Timeout waiting for zoom change'));
      }, 5000); // 5 second timeout
      
      viewer.registerCallback({
        listenOn: [ViewerEventType.ZOOM_CHANGED],
        handler: () => {
          clearTimeout(timeoutId);
          resolve();
        }
      });
    });
    
    // Set the zoom level
    viewer.setZoom(zoomMap[zoomLevel]);
    
    return zoomChangePromise;
  } catch (error) {
    console.error('Error setting zoom level:', error);
    throw new Error('Failed to set zoom level');
  }
};

/**
 * Destroys the Adobe viewer instance and cleans up resources
 * @param viewer - The initialized Adobe viewer instance
 */
export const destroyViewer = (viewer: any): void => {
  if (!viewer) {
    return;
  }
  
  try {
    // Unload the viewer and clean up resources
    viewer.unload();
    
    // Remove any registered callbacks
    viewer.registerCallback({
      listenOn: [],
      handler: () => {}
    });
  } catch (error) {
    console.error('Error destroying viewer:', error);
  }
};
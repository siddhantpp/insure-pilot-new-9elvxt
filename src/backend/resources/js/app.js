/**
 * Main JavaScript entry point for the Documents View feature
 * Initializes frontend functionality for the application
 */

// Import external libraries
import 'bootstrap'; // Bootstrap 5.x
import _ from 'lodash'; // Lodash 4.x
import axios from 'axios'; // Axios 1.x
import React from 'react'; // React 18.x
import { createRoot } from 'react-dom/client'; // React DOM 18.x

/**
 * Configure global lodash instance
 */
window._ = _;

/**
 * Configure Axios global instance with default headers and CSRF token
 */
const initializeAxios = () => {
    window.axios = axios;
    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    
    // Get the CSRF token from the meta tag
    const token = document.head.querySelector('meta[name="csrf-token"]');
    
    if (token) {
        window.csrfToken = token.content;
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
    } else {
        console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
    }
    
    // Configure Axios to use withCredentials for cross-domain requests
    window.axios.defaults.withCredentials = true;
    
    // Set up response interceptors for error handling
    window.axios.interceptors.response.use(
        response => response,
        error => {
            if (error.response && error.response.status === 401) {
                // Handle unauthorized access
                window.location.reload();
            } else if (error.response && error.response.status === 419) {
                // Handle CSRF token mismatch
                window.location.reload();
            }
            return Promise.reject(error);
        }
    );
};

/**
 * Initialize the document viewer component when a document view page is loaded
 */
const initializeDocumentViewer = () => {
    const documentViewerContainer = document.getElementById('document-viewer-container');
    
    if (documentViewerContainer) {
        const documentId = documentViewerContainer.getAttribute('data-document-id');
        
        // Create a root for React rendering
        const root = createRoot(documentViewerContainer);
        
        // Dynamically import the DocumentViewerContainer component for code splitting
        import(/* webpackChunkName: "document-viewer" */ './components/DocumentViewer/DocumentViewerContainer')
            .then(({ default: DocumentViewerContainer }) => {
                // Render the DocumentViewerContainer component
                root.render(
                    <DocumentViewerContainer 
                        documentId={documentId}
                        csrfToken={window.csrfToken}
                    />
                );
            })
            .catch(error => {
                console.error('Error loading DocumentViewerContainer:', error);
            });
    }
};

/**
 * Handle document viewer click event
 * Opens the document viewer lightbox when a document link is clicked
 */
const handleDocumentViewClick = (event) => {
    event.preventDefault();
    
    const documentId = event.currentTarget.getAttribute('data-document-id');
    if (!documentId) return;
    
    // Create a temporary container for the document viewer
    const container = document.createElement('div');
    container.id = 'document-viewer-container';
    container.setAttribute('data-document-id', documentId);
    document.body.appendChild(container);
    
    // Initialize the document viewer
    const root = createRoot(container);
    
    // Dynamically import the DocumentViewerContainer component
    import(/* webpackChunkName: "document-viewer" */ './components/DocumentViewer/DocumentViewerContainer')
        .then(({ default: DocumentViewerContainer }) => {
            root.render(
                <DocumentViewerContainer 
                    documentId={documentId}
                    csrfToken={window.csrfToken}
                    onClose={() => {
                        root.unmount();
                        document.body.removeChild(container);
                    }}
                />
            );
        })
        .catch(error => {
            console.error('Error loading DocumentViewerContainer:', error);
            document.body.removeChild(container);
        });
};

/**
 * Handle process button click event
 * Marks a document as processed
 */
const handleProcessButtonClick = (event) => {
    event.preventDefault();
    
    const documentId = event.currentTarget.getAttribute('data-document-id');
    if (!documentId) return;
    
    window.axios.post(`/api/documents/${documentId}/process`)
        .then(response => {
            // Update UI to reflect processed state
            const button = event.currentTarget;
            if (response.data.isProcessed) {
                button.textContent = 'Processed';
                button.classList.add('btn-secondary');
                button.classList.remove('btn-primary');
            } else {
                button.textContent = 'Mark as Processed';
                button.classList.add('btn-primary');
                button.classList.remove('btn-secondary');
            }
            
            // Make metadata fields read-only if processed
            const metadataFields = document.querySelectorAll('.metadata-field');
            metadataFields.forEach(field => {
                field.readOnly = response.data.isProcessed;
            });
        })
        .catch(error => {
            console.error('Error processing document:', error);
            // Show error notification
            alert('Error processing document. Please try again.');
        });
};

/**
 * Handle trash button click event
 * Moves a document to the trash
 */
const handleTrashButtonClick = (event) => {
    event.preventDefault();
    
    const documentId = event.currentTarget.getAttribute('data-document-id');
    if (!documentId) return;
    
    // Show confirmation dialog
    if (confirm('Are you sure you want to move this document to Recently Deleted? This document will be recoverable for 90 days.')) {
        window.axios.post(`/api/documents/${documentId}/trash`)
            .then(response => {
                // Close the document viewer or redirect to document list
                if (window.location.pathname.includes('/documents/')) {
                    window.location.href = '/documents';
                } else {
                    // If in lightbox, close it
                    const closeButton = document.querySelector('.document-viewer-close');
                    if (closeButton) {
                        closeButton.click();
                    }
                }
            })
            .catch(error => {
                console.error('Error trashing document:', error);
                // Show error notification
                alert('Error trashing document. Please try again.');
            });
    }
};

/**
 * Handle keyboard shortcuts for document actions
 */
const handleKeyboardShortcuts = (event) => {
    // Check if in document viewer context
    const documentViewer = document.getElementById('document-viewer-container');
    if (!documentViewer) return;
    
    // Escape key to close document viewer
    if (event.key === 'Escape') {
        const closeButton = document.querySelector('.document-viewer-close');
        if (closeButton) {
            closeButton.click();
        }
    }
    
    // Ctrl+S to save changes
    if (event.ctrlKey && event.key === 's') {
        event.preventDefault();
        const saveButton = document.querySelector('.metadata-save-button');
        if (saveButton) {
            saveButton.click();
        }
    }
    
    // Ctrl+P to mark as processed
    if (event.ctrlKey && event.key === 'p') {
        event.preventDefault();
        const processButton = document.querySelector('.document-process-button');
        if (processButton) {
            processButton.click();
        }
    }
};

/**
 * Set up event listeners for document-related actions
 */
const setupEventListeners = () => {
    // Add event listeners for document view triggers
    document.querySelectorAll('.document-view-trigger').forEach(element => {
        element.addEventListener('click', handleDocumentViewClick);
    });
    
    // Add event listeners for process buttons
    document.querySelectorAll('.document-process-button').forEach(element => {
        element.addEventListener('click', handleProcessButtonClick);
    });
    
    // Add event listeners for trash buttons
    document.querySelectorAll('.document-trash-button').forEach(element => {
        element.addEventListener('click', handleTrashButtonClick);
    });
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', handleKeyboardShortcuts);
};

/**
 * Initialize the application when the DOM is ready
 */
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Axios configuration
    initializeAxios();
    
    // Set up CSRF token for API requests
    const token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
        window.csrfToken = token.content;
    }
    
    // Initialize document viewer if on document page
    initializeDocumentViewer();
    
    // Set up event listeners for document actions
    setupEventListeners();
    
    // Configure Bootstrap components
    // Bootstrap is automatically initialized through the import
});

// Lazy-loaded React components for better performance
// These components will be loaded on-demand
window.loadMetadataPanel = () => {
    return import(/* webpackChunkName: "metadata-panel" */ './components/DocumentViewer/MetadataPanel');
};

window.loadHistoryPanel = () => {
    return import(/* webpackChunkName: "history-panel" */ './components/DocumentViewer/HistoryPanel');
};
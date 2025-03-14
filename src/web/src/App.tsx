import React, { useCallback } from 'react'; // React 18.x - Core React library for building user interfaces
import {
  BrowserRouter,
  Routes,
  Route,
  Navigate,
} from 'react-router-dom'; // ^6.8.0 - React Router for application routing and navigation
import { AuthProvider } from './context/AuthContext'; // Authentication context provider for user authentication and authorization
import { DocumentProvider } from './context/DocumentContext'; // Document context provider for document state management
import { NotificationProvider } from './context/NotificationContext'; // Notification context provider for application-wide notifications
import { ErrorBoundary } from './components/common/ErrorBoundary'; // Error boundary component for catching and handling errors
import DocumentsPage from './pages/DocumentsPage'; // Main page for displaying document list
import DocumentViewer from './views/DocumentViewer'; // Full-screen document viewer component
import ErrorPage from './pages/ErrorPage'; // Error page for displaying application errors
import NotFoundPage from './pages/NotFoundPage'; // 404 page for handling non-existent routes

/**
 * Main application component that sets up routing and context providers
 * @returns {JSX.Element} The rendered application component
 */
const App: React.FC = () => {
  /**
   * Handles errors caught by the ErrorBoundary
   * @param {Error} error - The error that was caught
   * @param {React.ErrorInfo} errorInfo - React's error info object
   * @returns {void} No return value
   */
  const handleError = useCallback((error: Error, errorInfo: React.ErrorInfo): void => {
    // LD1: Log error details to console for debugging
    console.error('Global error caught by ErrorBoundary:', error, errorInfo);

    // LD1: Format error information for logging
    const formattedError = {
      message: error.message,
      stack: errorInfo.componentStack,
    };

    // LD1: Could integrate with monitoring services in production
    // LD1: Example: logErrorToMonitoringService(formattedError);
  }, []);

  return (
    // LD1: Set up nested context providers (AuthProvider, NotificationProvider, DocumentProvider)
    <AuthProvider>
      <NotificationProvider>
        {/* LD1: Implement BrowserRouter for application routing */}
        <BrowserRouter>
          {/* LD1: Set up ErrorBoundary to catch and handle application errors */}
          <ErrorBoundary onError={handleError}>
            {/* LD1: Define Routes for different application pages */}
            <Routes>
              {/* LD1: Define route for DocumentsPage as the main page */}
              <Route path="/documents" element={<DocumentsPage />} />

              {/* LD1: Define route for DocumentViewer with document ID parameter */}
              <Route path="/documents/:documentId" element={<DocumentViewer />} />

              {/* LD1: Set up error handling routes for ErrorPage and NotFoundPage */}
              <Route path="/error" element={<ErrorPage />} />
              <Route path="*" element={<NotFoundPage />} />

              {/* LD1: Implement redirect from root path to /documents */}
              <Route path="/" element={<Navigate to="/documents" />} />
            </Routes>
          </ErrorBoundary>
        </BrowserRouter>
      </NotificationProvider>
    </AuthProvider>
  );
};

// LD1: Export App component
export default App;
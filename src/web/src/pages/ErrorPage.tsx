import React, { useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { MainLayout } from '../components/layout/MainLayout';
import ErrorMessage from '../components/common/ErrorMessage';
import { ActionButton } from '../components/buttons/ActionButton';
import { BackButton } from '../components/buttons/BackButton';
import { isNotFoundError, isPermissionError } from '../utils/errorUtils';

/**
 * Props interface for the ErrorPage component
 */
export interface ErrorPageProps {
  /**
   * Error object, string, or any error representation to display
   */
  error?: unknown;
  /**
   * Custom title for the error page
   */
  title?: string;
  /**
   * Whether to show a back button for navigation
   */
  showBackButton?: boolean;
}

/**
 * A page component that displays a user-friendly error page when application-level errors occur.
 * This page provides error details, navigation options, and retry functionality to help users recover from errors.
 *
 * @param props - Component props
 * @returns Rendered error page component
 */
const ErrorPage: React.FC<ErrorPageProps> = ({ 
  error, 
  title = 'Error Occurred', 
  showBackButton = true 
}) => {
  const navigate = useNavigate();
  const location = useLocation();
  
  // Get error from location state if not provided directly
  const errorToDisplay = error || location.state?.error;
  
  // Determine error type
  const isNotFound = isNotFoundError(errorToDisplay);
  const isPermission = isPermissionError(errorToDisplay);
  
  // Log the error for debugging purposes
  useEffect(() => {
    if (errorToDisplay) {
      console.error('Error displayed on error page:', errorToDisplay);
    }
  }, [errorToDisplay]);

  // Event handlers for different navigation actions
  const handleRetry = () => {
    window.location.reload();
  };
  
  const handleGoBack = () => {
    navigate(-1);
  };
  
  const handleGoToDocuments = () => {
    navigate('/documents');
  };
  
  const handleContactSupport = () => {
    navigate('/support');
  };

  return (
    <MainLayout title={title}>
      <div className="error-page">
        <ErrorMessage error={errorToDisplay} />
        
        <div className="error-page-actions">
          {isNotFound ? (
            <ActionButton 
              variant="primary" 
              onClick={handleGoToDocuments}
              aria-label="Go to documents list"
            >
              Go to Documents
            </ActionButton>
          ) : isPermission ? (
            <ActionButton 
              variant="primary" 
              onClick={handleContactSupport}
              aria-label="Contact support for assistance"
            >
              Contact Support
            </ActionButton>
          ) : (
            <ActionButton 
              variant="primary" 
              onClick={handleRetry}
              aria-label="Retry the current operation"
            >
              Retry
            </ActionButton>
          )}
          
          {showBackButton && (
            <BackButton 
              onClick={handleGoBack}
              className="error-page-back-button"
            />
          )}
        </div>
      </div>
    </MainLayout>
  );
};

export default ErrorPage;
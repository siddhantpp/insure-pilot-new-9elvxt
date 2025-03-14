import React from 'react'; // v18.x
import { useNavigate } from 'react-router-dom'; // ^6.8.0
import { MainLayout } from '../components/layout/MainLayout';
import { ActionButton } from '../components/buttons/ActionButton';

/**
 * A page component that displays a user-friendly 404 Not Found page when
 * users navigate to non-existent routes within the Documents View feature.
 * This page provides clear messaging and navigation options to help users
 * recover from the error.
 * 
 * @returns Rendered not found page component
 */
const NotFoundPage: React.FC = () => {
  // Get navigate function from react-router for navigation
  const navigate = useNavigate();

  /**
   * Handles navigation to documents list page
   */
  const handleGoToDocuments = () => {
    navigate('/documents');
  };

  return (
    <MainLayout title="Page Not Found">
      <div className="not-found-page">
        <h1 className="not-found-code">404</h1>
        <p className="not-found-message">
          The page you are looking for does not exist or has been moved.
        </p>
        <div className="not-found-actions">
          <ActionButton 
            variant="primary" 
            onClick={handleGoToDocuments}
          >
            Go to Documents
          </ActionButton>
        </div>
      </div>
    </MainLayout>
  );
};

export default NotFoundPage;
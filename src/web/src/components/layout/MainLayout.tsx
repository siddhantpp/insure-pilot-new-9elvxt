import React, { ReactNode } from 'react';
import classNames from 'classnames'; // ^2.3.2
import { useAuth } from '../../context/AuthContext';
import { useNotification } from '../../context/NotificationContext';
import StatusIndicator from '../common/StatusIndicator';

/**
 * Props interface for the MainLayout component
 */
export interface MainLayoutProps {
  /**
   * Content to be rendered within the layout
   */
  children: ReactNode;
  
  /**
   * Page title to display in the header
   */
  title: string;
  
  /**
   * Optional additional CSS classes to apply to the layout container
   */
  className?: string;
}

/**
 * A layout component that provides the main application structure for the Documents View feature.
 * It serves as a container for page content, implementing consistent header, navigation,
 * and content areas while integrating with authentication and notification contexts.
 * 
 * @param props - Component props
 * @returns The rendered main layout component
 */
export const MainLayout = ({ children, title, className }: MainLayoutProps): JSX.Element => {
  // Get authentication state
  const { state: authState } = useAuth();
  const { user, isAuthenticated } = authState;
  
  // Get notification state
  const { state: notificationState } = useNotification();
  const { notifications } = notificationState;
  
  return (
    <div className={classNames('main-layout', className)} data-testid="main-layout">
      <header className="main-layout-header" role="banner">
        <div className="main-layout-header-content">
          <div className="main-layout-header-logo">
            <h1>Insure Pilot</h1>
          </div>
          
          <div className="main-layout-header-title">
            <h2>{title}</h2>
          </div>
          
          {isAuthenticated && user && (
            <div className="main-layout-header-user" aria-label="User information">
              <span className="main-layout-header-username">{user.fullName}</span>
              <span className="main-layout-header-role">{user.roleName}</span>
            </div>
          )}
        </div>
      </header>
      
      <div className="main-layout-notification-area" aria-live="polite">
        {notifications.map((notification) => (
          <StatusIndicator
            key={notification.id}
            status={notification.type as 'success' | 'error' | 'warning' | 'info'}
            message={notification.message}
            className="main-layout-notification"
          />
        ))}
      </div>
      
      <main className="main-layout-content" role="main">
        {children}
      </main>
    </div>
  );
};
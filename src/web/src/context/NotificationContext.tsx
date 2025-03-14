/**
 * A React Context provider that manages application-wide notifications for the Documents View feature.
 * This context centralizes notification state and provides methods for displaying, updating, and
 * dismissing notifications related to document operations such as metadata updates, document processing,
 * and error states.
 */

import React, { createContext, useContext, useState, useEffect, useCallback, ReactNode } from 'react'; // React 18.x
import { EventBus, DocumentEventType } from '../lib/eventBus';
import { Document } from '../models/document.types';
import StatusIndicator from '../components/common/StatusIndicator';

/**
 * Enum representing possible notification types
 */
export enum NotificationType {
  SUCCESS = 'success',
  ERROR = 'error',
  INFO = 'info',
  WARNING = 'warning'
}

/**
 * Interface for notification objects
 */
export interface Notification {
  id: string;
  type: NotificationType;
  message: string;
  timestamp: number;
  duration: number | null; // null means no auto-dismiss
}

/**
 * Interface for notification state
 */
export interface NotificationState {
  notifications: Notification[];
}

/**
 * Interface for notification context value
 */
export interface NotificationContextType {
  state: NotificationState;
  showNotification: (type: NotificationType, message: string, duration?: number) => void;
  dismissNotification: (id: string) => void;
  clearNotifications: () => void;
}

/**
 * React context for notification state
 */
export const NotificationContext = createContext<NotificationContextType | null>(null);

/**
 * Creates a notification object with unique ID and timestamp
 * 
 * @param type - The type of notification (success, error, info, warning)
 * @param message - The notification message
 * @param duration - Time in milliseconds before the notification is auto-dismissed (default: 5000, 0 for no auto-dismiss)
 * @returns A new notification object
 */
const createNotification = (
  type: NotificationType,
  message: string,
  duration: number = 5000 // Default duration of 5 seconds
): Notification => {
  return {
    id: `notification-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
    type,
    message,
    timestamp: Date.now(),
    duration: duration <= 0 ? null : duration
  };
};

/**
 * Provider component that wraps the application to provide notification context
 * 
 * @param props - Component props
 * @param props.children - React children to be wrapped by the provider
 * @returns The provider component with context
 */
export const NotificationProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  // Initialize notification state with default values
  const [state, setState] = useState<NotificationState>({
    notifications: []
  });

  /**
   * Shows a new notification and optionally auto-dismisses it after a duration
   * 
   * @param type - The type of notification
   * @param message - The notification message
   * @param duration - Time in milliseconds before auto-dismiss (default: 5000, 0 for no auto-dismiss)
   */
  const showNotification = useCallback((type: NotificationType, message: string, duration?: number) => {
    const notification = createNotification(type, message, duration);
    setState(prevState => ({
      ...prevState,
      notifications: [...prevState.notifications, notification]
    }));

    // Set up auto-dismiss if duration is provided
    if (notification.duration !== null) {
      setTimeout(() => {
        dismissNotification(notification.id);
      }, notification.duration);
    }
  }, []);

  /**
   * Dismisses a notification by ID
   * 
   * @param id - The ID of the notification to dismiss
   */
  const dismissNotification = useCallback((id: string) => {
    setState(prevState => ({
      ...prevState,
      notifications: prevState.notifications.filter(notification => notification.id !== id)
    }));
  }, []);

  /**
   * Clears all notifications
   */
  const clearNotifications = useCallback(() => {
    setState(prevState => ({
      ...prevState,
      notifications: []
    }));
  }, []);

  // Set up event listeners for document events
  useEffect(() => {
    /**
     * Handles document loaded events
     * 
     * @param document - The loaded document
     */
    const handleDocumentLoaded = (document: Document) => {
      showNotification(
        NotificationType.INFO,
        `Document "${document.filename}" loaded successfully.`
      );
    };

    /**
     * Handles metadata updated events
     * 
     * @param document - The document with updated metadata
     */
    const handleMetadataUpdated = (document: Document) => {
      showNotification(
        NotificationType.SUCCESS,
        `Metadata for "${document.filename}" updated successfully.`
      );
    };

    /**
     * Handles document processed events
     * 
     * @param document - The processed document
     */
    const handleDocumentProcessed = (document: Document) => {
      showNotification(
        NotificationType.SUCCESS,
        `Document "${document.filename}" ${document.isProcessed ? 'marked as processed' : 'unmarked as processed'}.`
      );
    };

    /**
     * Handles document trashed events
     * 
     * @param document - The trashed document
     */
    const handleDocumentTrashed = (document: Document) => {
      showNotification(
        NotificationType.INFO,
        `Document "${document.filename}" moved to trash.`
      );
    };

    // Subscribe to document events
    const unsubscribeLoaded = EventBus.on(DocumentEventType.DOCUMENT_LOADED, handleDocumentLoaded);
    const unsubscribeUpdated = EventBus.on(DocumentEventType.METADATA_UPDATED, handleMetadataUpdated);
    const unsubscribeProcessed = EventBus.on(DocumentEventType.DOCUMENT_PROCESSED, handleDocumentProcessed);
    const unsubscribeTrashed = EventBus.on(DocumentEventType.DOCUMENT_TRASHED, handleDocumentTrashed);

    // Cleanup subscriptions when component unmounts
    return () => {
      unsubscribeLoaded();
      unsubscribeUpdated();
      unsubscribeProcessed();
      unsubscribeTrashed();
    };
  }, [showNotification]);

  // Create context value object with state and functions
  const contextValue: NotificationContextType = {
    state,
    showNotification,
    dismissNotification,
    clearNotifications
  };

  // Return NotificationContext.Provider with the context value and children
  return (
    <NotificationContext.Provider value={contextValue} data-testid="notification-provider">
      {children}
    </NotificationContext.Provider>
  );
};

/**
 * Custom hook to access the notification context
 * 
 * @returns The notification context value
 * @throws Error if used outside of NotificationProvider
 */
export const useNotification = (): NotificationContextType => {
  const context = useContext(NotificationContext);
  
  if (!context) {
    throw new Error('useNotification must be used within a NotificationProvider');
  }
  
  return context;
};
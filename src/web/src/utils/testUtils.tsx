import React from 'react';
import { render, screen, waitFor, within, fireEvent } from '@testing-library/react'; // @testing-library/react ^13.4.0
import userEvent from '@testing-library/user-event'; // @testing-library/user-event ^14.4.3
import { AuthContext, AuthProvider } from '../context/AuthContext';
import { DocumentContext, DocumentProvider } from '../context/DocumentContext';
import { NotificationContext, NotificationProvider } from '../context/NotificationContext';
import { Document, DocumentHistory, PanelType as DocumentPanelType } from '../models/document.types';
import { User, Permission } from '../models/user.types';

/**
 * Renders a component with all necessary context providers for testing
 * @param ui The React component to render
 * @param options Optional configuration for the render
 * @returns Rendered component with testing utilities
 */
export function renderWithProviders(ui: React.ReactNode, options = {}) {
  // Create default mock user with appropriate permissions
  const defaultUser: User = {
    id: 1,
    username: 'testuser',
    email: 'test@example.com',
    fullName: 'Test User',
    role: 'admin',
    roleName: 'Administrator',
    userGroupId: null,
    userGroupName: null,
    isActive: true,
    lastLogin: '2023-05-10T09:15:00Z'
  };

  // Create default auth state
  const defaultAuthContextValue = {
    state: {
      user: defaultUser,
      isAuthenticated: true,
      isLoading: false,
      error: null
    },
    login: jest.fn().mockResolvedValue(true),
    logout: jest.fn().mockResolvedValue(undefined),
    hasPermission: jest.fn().mockReturnValue(true)
  };

  // Create default document state
  const defaultDocumentContextValue = {
    state: {
      document: null,
      isLoading: false,
      error: null,
      activePanel: DocumentPanelType.METADATA,
      isSaving: false,
      saveError: null
    },
    loadDocument: jest.fn().mockResolvedValue(null),
    updateMetadata: jest.fn().mockResolvedValue(null),
    processDocument: jest.fn().mockResolvedValue(null),
    trashDocument: jest.fn().mockResolvedValue(true),
    setActivePanel: jest.fn()
  };

  // Create default notification state
  const defaultNotificationContextValue = {
    state: {
      notifications: []
    },
    showNotification: jest.fn(),
    dismissNotification: jest.fn(),
    clearNotifications: jest.fn()
  };

  // Merge provided options with defaults
  const {
    authContextValue = defaultAuthContextValue,
    documentContextValue = defaultDocumentContextValue,
    notificationContextValue = defaultNotificationContextValue,
    ...renderOptions
  } = options;

  // Create wrapper component with all providers
  function Wrapper({ children }: { children: React.ReactNode }) {
    return (
      <AuthContext.Provider value={authContextValue}>
        <DocumentContext.Provider value={documentContextValue}>
          <NotificationContext.Provider value={notificationContextValue}>
            {children}
          </NotificationContext.Provider>
        </DocumentContext.Provider>
      </AuthContext.Provider>
    );
  }

  // Return render result with additional utilities
  return {
    ...render(ui, { wrapper: Wrapper, ...renderOptions }),
    user: userEvent.setup(),
    authContextValue,
    documentContextValue,
    notificationContextValue
  };
}

/**
 * Creates a mock document object for testing
 * @param overrides Properties to override in the default document
 * @returns Mock document object
 */
export function createMockDocument(overrides = {}): Document {
  // Create default document with standard test values
  const defaultDocument: Document = {
    id: 123,
    filename: 'Policy_Renewal_Notice.pdf',
    fileUrl: 'http://example.com/documents/123.pdf',
    description: 'Policy Renewal Notice',
    isProcessed: false,
    status: 'unprocessed',
    createdAt: '2023-05-10T09:15:00Z',
    updatedAt: '2023-05-12T10:45:00Z',
    createdBy: {
      id: 2,
      username: 'system'
    },
    updatedBy: {
      id: 1,
      username: 'testuser'
    },
    metadata: {
      policyNumber: 'PLCY-12345',
      policyId: 12345,
      lossSequence: '1 - Vehicle Accident (03/15/2023)',
      lossId: 1001,
      claimant: '1 - John Smith',
      claimantId: 5001,
      documentDescription: 'Policy Renewal Notice',
      assignedTo: 'Claims Department',
      assignedToId: 3,
      assignedToType: 'group',
      producerNumber: 'AG-789456',
      producerId: 456
    }
  };

  // Merge provided overrides with default values
  return { ...defaultDocument, ...overrides };
}

/**
 * Creates a mock document history object for testing
 * @param overrides Properties to override in the default history
 * @returns Mock document history object
 */
export function createMockDocumentHistory(overrides = {}): DocumentHistory {
  // Create default history object with test values
  const defaultHistory: DocumentHistory = {
    entries: [
      {
        id: 1001,
        actionType: 'process',
        description: 'Marked as processed',
        timestamp: '2023-05-12T10:45:00Z',
        formattedTimestamp: '05/12/2023 10:45 AM',
        user: {
          id: 1,
          username: 'testuser'
        }
      },
      {
        id: 1000,
        actionType: 'update_metadata',
        description: 'Changed Document Description from "Policy Document" to "Policy Renewal Notice"',
        timestamp: '2023-05-12T10:42:00Z',
        formattedTimestamp: '05/12/2023 10:42 AM',
        user: {
          id: 1,
          username: 'testuser'
        }
      },
      {
        id: 999,
        actionType: 'view',
        description: 'Document viewed',
        timestamp: '2023-05-12T10:40:00Z',
        formattedTimestamp: '05/12/2023 10:40 AM',
        user: {
          id: 1,
          username: 'testuser'
        }
      },
      {
        id: 998,
        actionType: 'create',
        description: 'Document uploaded',
        timestamp: '2023-05-10T09:15:00Z',
        formattedTimestamp: '05/10/2023 09:15 AM',
        user: {
          id: 2,
          username: 'system'
        }
      }
    ],
    lastEdited: '2023-05-12T10:45:00Z',
    lastEditedBy: {
      id: 1,
      username: 'testuser'
    }
  };

  // Merge provided overrides with default values
  return { ...defaultHistory, ...overrides };
}

/**
 * Creates a mock user object for testing
 * @param overrides Properties to override in the default user
 * @returns Mock user object
 */
export function createMockUser(overrides = {}): User {
  // Create default user with standard test values
  const defaultUser: User = {
    id: 1,
    username: 'testuser',
    email: 'test@example.com',
    fullName: 'Test User',
    role: 'admin',
    roleName: 'Administrator',
    userGroupId: null,
    userGroupName: null,
    isActive: true,
    lastLogin: '2023-05-10T09:15:00Z'
  };

  // Merge provided overrides with default values
  return { ...defaultUser, ...overrides };
}

/**
 * Mocks the fetch API for testing API calls
 * @param url The URL to mock
 * @param response The response to return
 * @param status The HTTP status code to return
 */
export function mockFetch(url: string, response: any, status = 200): void {
  // Save original fetch implementation
  const originalFetch = global.fetch;

  // Create mock implementation
  global.fetch = jest.fn().mockImplementation((requestUrl) => {
    // Check if the request URL matches the mocked URL
    if (requestUrl.toString().includes(url)) {
      return Promise.resolve({
        ok: status >= 200 && status < 300,
        status,
        json: () => Promise.resolve(response)
      });
    }

    // Fall back to original fetch for other URLs
    return originalFetch(requestUrl);
  });
}

/**
 * Simulates a series of user interactions for integration testing
 * @param actions Array of actions to simulate
 * @returns Promise that resolves when all actions are complete
 */
export async function simulateUserInteraction(actions: Array<{ type: string, target: any, options?: any }>): Promise<void> {
  // Create user event instance
  const user = userEvent.setup();

  // Process each action sequentially
  for (const action of actions) {
    switch (action.type) {
      case 'click':
        await user.click(action.target);
        break;
      case 'dblclick':
        await user.dblClick(action.target);
        break;
      case 'type':
        await user.type(action.target, action.options?.text || '');
        break;
      case 'clear':
        await user.clear(action.target);
        break;
      case 'selectOptions':
        await user.selectOptions(action.target, action.options?.values || []);
        break;
      case 'hover':
        await user.hover(action.target);
        break;
      case 'keyboard':
        await user.keyboard(action.options?.keys || '');
        break;
      default:
        throw new Error(`Unknown action type: ${action.type}`);
    }

    // Wait for UI updates between actions
    await waitFor(() => {}, { timeout: action.options?.timeout || 100 });
  }
}

/**
 * Waits for an element to be removed from the DOM
 * @param callback Function that returns the element to wait for
 * @param options Options for the wait operation
 * @returns Promise that resolves when the element is removed
 */
export async function waitForElementToBeRemoved(
  callback: () => HTMLElement | null | HTMLElement[] | null,
  options?: { timeout?: number, interval?: number }
): Promise<void> {
  return waitFor(() => {
    const elements = callback();
    if (Array.isArray(elements)) {
      if (elements.length > 0) throw new Error('Element(s) still present');
    } else if (elements) {
      throw new Error('Element still present');
    }
  }, options);
}

/**
 * Creates mock context values for testing specific scenarios
 * @param contextType The type of context to create (auth, document, notification)
 * @param overrides Properties to override in the default context
 * @returns Mock context value
 */
export function createMockContextValue(contextType: string, overrides = {}): any {
  switch (contextType) {
    case 'auth':
      // Create default auth context value
      const defaultAuthContextValue = {
        state: {
          user: createMockUser(),
          isAuthenticated: true,
          isLoading: false,
          error: null
        },
        login: jest.fn().mockResolvedValue(true),
        logout: jest.fn().mockResolvedValue(undefined),
        hasPermission: jest.fn().mockReturnValue(true)
      };
      return { ...defaultAuthContextValue, ...overrides };

    case 'document':
      // Create default document context value
      const defaultDocumentContextValue = {
        state: {
          document: null,
          isLoading: false,
          error: null,
          activePanel: DocumentPanelType.METADATA,
          isSaving: false,
          saveError: null
        },
        loadDocument: jest.fn().mockResolvedValue(null),
        updateMetadata: jest.fn().mockResolvedValue(null),
        processDocument: jest.fn().mockResolvedValue(null),
        trashDocument: jest.fn().mockResolvedValue(true),
        setActivePanel: jest.fn()
      };
      return { ...defaultDocumentContextValue, ...overrides };

    case 'notification':
      // Create default notification context value
      const defaultNotificationContextValue = {
        state: {
          notifications: []
        },
        showNotification: jest.fn(),
        dismissNotification: jest.fn(),
        clearNotifications: jest.fn()
      };
      return { ...defaultNotificationContextValue, ...overrides };

    default:
      throw new Error(`Unknown context type: ${contextType}`);
  }
}
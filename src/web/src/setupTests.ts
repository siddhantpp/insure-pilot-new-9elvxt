// Import @testing-library/jest-dom to extend Jest with DOM matchers
// @testing-library/jest-dom - version 5.16.5
import '@testing-library/jest-dom';

// Import jest-axe for accessibility testing
// jest-axe - version 7.0.0
import { configureAxe, toHaveNoViolations } from 'jest-axe';

// Import MSW for API mocking
// msw - version 1.2.1
import { setupServer } from 'msw/node';
import { rest } from 'msw';
import type { SetupServerApi } from 'msw/node';

// Extend Jest's expect with accessibility testing
expect.extend(toHaveNoViolations);

// TypeScript declarations for global objects
declare global {
  var matchMedia: jest.Mock;
  var ResizeObserver: jest.Mock;
  var server: SetupServerApi;
  var axe: ReturnType<typeof configureAxe>;
}

// Mock window.matchMedia which is not implemented in JSDOM
window.matchMedia = jest.fn().mockImplementation(query => ({
  matches: false,
  media: query,
  onchange: null,
  addListener: jest.fn(),
  removeListener: jest.fn(),
  addEventListener: jest.fn(),
  removeEventListener: jest.fn(),
  dispatchEvent: jest.fn(),
}));

// Mock ResizeObserver which is not implemented in JSDOM
window.ResizeObserver = jest.fn().mockImplementation(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn(),
}));

// Store original fetch for later use if needed
const originalFetch = window.fetch;

// Define the MSW request handlers for document API endpoints
const handlers = [
  // Handle document retrieval
  rest.get('/api/documents/:id', (req, res, ctx) => {
    const { id } = req.params;
    return res(
      ctx.status(200),
      ctx.json({
        id,
        filename: 'Policy_Renewal_Notice.pdf',
        fileUrl: '/test-files/Policy_Renewal_Notice.pdf',
        description: 'Policy Renewal Document',
        documentTypeId: 1,
        documentTypeName: 'Policy Document',
        isProcessed: false,
        createdAt: '2023-05-10T09:15:00Z',
        createdBy: {
          id: 1,
          username: 'system'
        },
        updatedAt: '2023-05-12T10:40:00Z',
        updatedBy: {
          id: 2,
          username: 'sarahjohnson'
        },
        metadata: {
          policyNumber: 'PLCY-12345',
          policyId: 1,
          lossSequence: '1 - Vehicle Accident (03/15/2023)',
          lossId: 1,
          claimant: '1 - John Smith',
          claimantId: 1,
          documentDescription: 'Policy Renewal Notice',
          assignedTo: 'Claims Department',
          assignedToId: 3,
          assignedToType: 'group',
          producerNumber: 'AG-789456',
          producerId: 2
        }
      })
    );
  }),
  
  // Handle metadata update
  rest.put('/api/documents/:id/metadata', (req, res, ctx) => {
    const { id } = req.params;
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        message: 'Metadata updated successfully'
      })
    );
  }),
  
  // Handle document processing
  rest.post('/api/documents/:id/process', (req, res, ctx) => {
    const { id } = req.params;
    return res(
      ctx.status(200),
      ctx.json({
        success: true,
        isProcessed: true,
        message: 'Document marked as processed'
      })
    );
  }),
  
  // Handle document history retrieval
  rest.get('/api/documents/:id/history', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json([
        {
          id: 4,
          documentId: 1,
          actionType: 'processed',
          actionTypeId: 3,
          description: 'Marked as processed',
          createdAt: '2023-05-12T10:45:00Z',
          createdBy: {
            id: 2,
            username: 'sarahjohnson'
          }
        },
        {
          id: 3,
          documentId: 1,
          actionType: 'updated',
          actionTypeId: 2,
          description: 'Changed Document Description from "Policy Document" to "Policy Renewal Notice"',
          createdAt: '2023-05-12T10:42:00Z',
          createdBy: {
            id: 2,
            username: 'sarahjohnson'
          }
        },
        {
          id: 2,
          documentId: 1,
          actionType: 'viewed',
          actionTypeId: 4,
          description: 'Document viewed',
          createdAt: '2023-05-12T10:40:00Z',
          createdBy: {
            id: 2,
            username: 'sarahjohnson'
          }
        },
        {
          id: 1,
          documentId: 1,
          actionType: 'created',
          actionTypeId: 1,
          description: 'Document uploaded',
          createdAt: '2023-05-10T09:15:00Z',
          createdBy: {
            id: 1,
            username: 'system'
          }
        }
      ])
    );
  }),
  
  // Handle policy dropdown data
  rest.get('/api/policies', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json([
        { id: 1, label: 'PLCY-12345', value: 1 },
        { id: 2, label: 'PLCY-12346', value: 2 },
        { id: 3, label: 'PLCY-12347', value: 3 },
        { id: 4, label: 'PLCY-12348', value: 4 }
      ])
    );
  }),
  
  // Handle loss sequence data for a policy
  rest.get('/api/policies/:id/losses', (req, res, ctx) => {
    const { id } = req.params;
    return res(
      ctx.status(200),
      ctx.json([
        { id: 1, label: '1 - Vehicle Accident (03/15/2023)', value: 1 },
        { id: 2, label: '2 - Property Damage (04/20/2023)', value: 2 }
      ])
    );
  }),
  
  // Handle claimant data for a loss
  rest.get('/api/losses/:id/claimants', (req, res, ctx) => {
    const { id } = req.params;
    return res(
      ctx.status(200),
      ctx.json([
        { id: 1, label: '1 - John Smith', value: 1 },
        { id: 2, label: '2 - Jane Doe', value: 2 }
      ])
    );
  }),
  
  // Handle producer data
  rest.get('/api/producers', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json([
        { id: 1, label: 'AG-789456', value: 1 },
        { id: 2, label: 'AG-789457', value: 2 },
        { id: 3, label: 'AG-789458', value: 3 }
      ])
    );
  })
];

/**
 * Sets up Mock Service Worker for API mocking in tests
 */
const setupMockServiceWorker = (): void => {
  // Create MSW server with the handlers
  const server = setupServer(...handlers);
  
  // Start the server before all tests
  beforeAll(() => server.listen());
  
  // Reset handlers between tests
  afterEach(() => server.resetHandlers());
  
  // Close server after all tests are done
  afterAll(() => server.close());
  
  // Make server available globally
  global.server = server;
};

/**
 * Configures accessibility testing with jest-axe
 */
const setupAccessibilityTesting = (): void => {
  // Configure axe with options
  const axe = configureAxe({
    rules: {
      // Ensure color contrast is checked
      'color-contrast': { enabled: true },
      // Ensure ARIA roles are valid
      'aria-roles': { enabled: true },
      // Ensure buttons have accessible names
      'button-name': { enabled: true },
      // Ensure form elements have labels
      'label': { enabled: true },
      // Ensure images have alt text
      'image-alt': { enabled: true }
    }
  });
  
  // Make the configured axe available globally
  global.axe = axe;
};

// Run the setup functions
setupMockServiceWorker();
setupAccessibilityTesting();

// Clean up after each test
afterEach(() => {
  jest.clearAllMocks();
});

// Suppress specific console errors during tests
const originalError = console.error;
beforeAll(() => {
  console.error = (...args: any[]) => {
    // Filter out specific warnings that are expected or not relevant
    if (
      typeof args[0] === 'string' && (
        args[0].includes('Warning: ReactDOM.render is no longer supported') ||
        args[0].includes('Warning: An update to Component inside a test was not wrapped in act')
      )
    ) {
      return;
    }
    // Pass through other console errors
    originalError(...args);
  };
});

// Restore original console.error after tests
afterAll(() => {
  console.error = originalError;
});
import React from 'react';
import { screen, waitFor, within, fireEvent } from '@testing-library/react'; // @testing-library/react ^13.4.0
import userEvent from '@testing-library/user-event'; // @testing-library/user-event ^14.4.3
import * as router from 'react-router-dom'; // react-router-dom ^6.0.0
import { renderWithProviders, createMockDocumentHistory, mockFetch, simulateUserInteraction } from '../../utils/testUtils';
import DocumentHistory from '../../views/DocumentHistory';
import { getDocumentHistory } from '../../services/historyService';

// Mock the history service
jest.mock('../../services/historyService');

// Mock react-router-dom hooks
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: jest.fn(),
  useNavigate: jest.fn(),
}));

/**
 * Sets up all necessary mocks for the history view flow tests
 */
const setupMocks = () => {
  // Create mock document history data
  const mockHistory = createMockDocumentHistory();
  
  // Configure the getDocumentHistory mock to return our test data
  (getDocumentHistory as jest.Mock).mockResolvedValue(mockHistory);
};

/**
 * Helper function to render the DocumentHistory component for testing
 * @param documentId - The document ID to use in the test
 * @returns Rendered component with testing utilities
 */
const renderDocumentHistory = (documentId: number) => {
  const navigate = jest.fn();
  
  // Set up mocked router hooks
  (router.useParams as jest.Mock).mockReturnValue({ documentId: documentId.toString() });
  (router.useNavigate as jest.Mock).mockReturnValue(navigate);
  
  // Render the component with all necessary providers
  return {
    ...renderWithProviders(<DocumentHistory />),
    navigate,
  };
};

describe('History View Flow', () => {
  beforeEach(() => {
    setupMocks();
  });
  
  afterEach(() => {
    jest.clearAllMocks();
  });
  
  it('should load and display document history', async () => {
    renderDocumentHistory(123);
    
    // Wait for history service to be called with correct ID
    await waitFor(() => {
      expect(getDocumentHistory).toHaveBeenCalledWith(123);
    });
    
    // Verify history header is displayed
    expect(screen.getByText('Document History')).toBeInTheDocument();
    
    // Verify last edited information is displayed
    expect(screen.getByText(/Last Edited:/)).toBeInTheDocument();
    expect(screen.getByText(/Last Edited By: testuser/)).toBeInTheDocument();
    
    // Verify history entries are displayed in a log
    const historyList = screen.getByRole('log', { name: /Document history/i });
    expect(historyList).toBeInTheDocument();
    
    // Verify all expected history entries are present
    const historyItems = within(historyList).getAllByRole('listitem');
    expect(historyItems).toHaveLength(4); // Based on mock data
    
    // Verify first history entry contains expected content
    const firstItem = historyItems[0];
    expect(firstItem).toHaveTextContent('05/12/2023 10:45 AM');
    expect(firstItem).toHaveTextContent('testuser');
    expect(firstItem).toHaveTextContent('Marked as processed');
  });
  
  it('should navigate back to document viewer', async () => {
    const { navigate } = renderDocumentHistory(123);
    
    // Wait for history data to load
    await waitFor(() => {
      expect(getDocumentHistory).toHaveBeenCalledWith(123);
    });
    
    // Find and click the back button
    const backButton = screen.getByRole('button', { name: /Back/i });
    fireEvent.click(backButton);
    
    // Verify navigation was called with the correct path
    expect(navigate).toHaveBeenCalledWith('/documents/123');
  });
  
  it('should display loading state while fetching history', async () => {
    // Modify getDocumentHistory to delay the response
    (getDocumentHistory as jest.Mock).mockImplementation(() => {
      return new Promise(resolve => {
        setTimeout(() => {
          resolve(createMockDocumentHistory());
        }, 100);
      });
    });
    
    renderDocumentHistory(123);
    
    // Verify loading indicator is shown initially
    expect(screen.getByText('Loading history...')).toBeInTheDocument();
    
    // Wait for data to load and verify loading indicator is removed
    await waitFor(() => {
      expect(screen.queryByText('Loading history...')).not.toBeInTheDocument();
    });
    
    // Verify content is shown after loading
    expect(screen.getByText('Document History')).toBeInTheDocument();
  });
  
  it('should handle errors gracefully', async () => {
    // Mock getDocumentHistory to reject with an error
    (getDocumentHistory as jest.Mock).mockRejectedValue(new Error('Failed to load history'));
    
    renderDocumentHistory(123);
    
    // Wait for error handling to complete
    await waitFor(() => {
      // Verify error message is displayed
      expect(screen.getByRole('alert')).toBeInTheDocument();
      expect(screen.getByText('Failed to load history')).toBeInTheDocument();
    });
    
    // Verify history list is not displayed
    expect(screen.queryByRole('log')).not.toBeInTheDocument();
  });
  
  it('should support keyboard navigation', async () => {
    const { navigate } = renderDocumentHistory(123);
    const user = userEvent.setup();
    
    // Wait for history data to load
    await waitFor(() => {
      expect(getDocumentHistory).toHaveBeenCalledWith(123);
    });
    
    // Use Tab to navigate to the back button
    await user.tab();
    
    // Verify back button has focus
    const backButton = screen.getByRole('button', { name: /Back/i });
    expect(document.activeElement).toBe(backButton);
    
    // Press Enter to activate the back button
    await user.keyboard('{Enter}');
    
    // Verify navigation was called
    expect(navigate).toHaveBeenCalledWith('/documents/123');
  });
});
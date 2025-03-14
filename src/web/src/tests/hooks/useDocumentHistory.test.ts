import { renderHook, act, waitFor } from '@testing-library/react-hooks'; // @testing-library/react-hooks ^8.0.1
import { useDocumentHistory } from '../../hooks/useDocumentHistory';
import { getDocumentHistory } from '../../services/historyService';
import { createMockDocumentHistory } from '../../utils/testUtils';

// Mock the historyService
jest.mock('../../services/historyService');

describe('useDocumentHistory', () => {
  // Set up mock history data
  const mockHistory = createMockDocumentHistory();
  
  // Reset mocks before each test
  beforeEach(() => {
    jest.resetAllMocks();
  });

  it('should fetch document history when documentId is provided', async () => {
    // Mock the getDocumentHistory function to return mock data
    (getDocumentHistory as jest.Mock).mockResolvedValue(mockHistory);
    
    // Render the hook with a document ID
    const { result } = renderHook(() => useDocumentHistory(123));
    
    // Wait for the hook to complete the API request
    await waitFor(() => {
      expect(result.current.isLoading).toBe(false);
    });
    
    // Verify that getDocumentHistory was called with the correct document ID
    expect(getDocumentHistory).toHaveBeenCalledWith(123);
    
    // Verify that the returned history data matches the mock data
    expect(result.current.history).toEqual(mockHistory);
    
    // Verify that isLoading is false after the request completes
    expect(result.current.isLoading).toBe(false);
    
    // Verify that error is null when the request succeeds
    expect(result.current.error).toBeNull();
  });

  it('should set isLoading to true during fetch', async () => {
    // Create a promise that we can resolve manually
    let resolvePromise!: (value: any) => void;
    const promise = new Promise(resolve => {
      resolvePromise = resolve;
    });
    
    // Mock getDocumentHistory to return our controlled promise
    (getDocumentHistory as jest.Mock).mockReturnValue(promise);
    
    // Render the hook
    const { result } = renderHook(() => useDocumentHistory(123));
    
    // Check that isLoading is initially true
    expect(result.current.isLoading).toBe(true);
    
    // Resolve the promise
    resolvePromise(mockHistory);
    
    // Wait for the hook to process the result
    await waitFor(() => {
      expect(result.current.isLoading).toBe(false);
    });
  });

  it('should handle errors when fetch fails', async () => {
    // Mock an error response
    const errorMessage = 'Failed to load document history';
    (getDocumentHistory as jest.Mock).mockRejectedValue(new Error(errorMessage));
    
    // Render the hook
    const { result } = renderHook(() => useDocumentHistory(123));
    
    // Wait for the hook to handle the error
    await waitFor(() => {
      expect(result.current.isLoading).toBe(false);
    });
    
    // Verify that error state contains the error message
    expect(result.current.error).toBe(errorMessage);
    
    // Verify that isLoading is false after the error is handled
    expect(result.current.isLoading).toBe(false);
    
    // Verify that history is null when an error occurs
    expect(result.current.history).toBeNull();
  });

  it('should refresh history data when refreshHistory is called', async () => {
    // First, mock the initial response
    const initialHistory = createMockDocumentHistory({ 
      lastEdited: '2023-05-10T09:15:00Z'
    });
    
    // Then, mock the refreshed response with different data
    const refreshedHistory = createMockDocumentHistory({
      lastEdited: '2023-05-12T10:45:00Z'
    });
    
    // Setup the mock to return different responses on subsequent calls
    (getDocumentHistory as jest.Mock)
      .mockResolvedValueOnce(initialHistory)
      .mockResolvedValueOnce(refreshedHistory);
    
    // Render the hook
    const { result } = renderHook(() => useDocumentHistory(123));
    
    // Wait for the initial fetch to complete
    await waitFor(() => {
      expect(result.current.history).toEqual(initialHistory);
    });
    
    // Call refreshHistory
    act(() => {
      result.current.refreshHistory();
    });
    
    // Wait for the refresh to complete
    await waitFor(() => {
      expect(result.current.history).toEqual(refreshedHistory);
    });
    
    // Verify that getDocumentHistory was called twice
    expect(getDocumentHistory).toHaveBeenCalledTimes(2);
  });

  it('should not fetch history when documentId is undefined', () => {
    // Render the hook with undefined documentId
    const { result } = renderHook(() => useDocumentHistory(undefined));
    
    // Verify that getDocumentHistory was not called
    expect(getDocumentHistory).not.toHaveBeenCalled();
    
    // Verify that history is null
    expect(result.current.history).toBeNull();
    
    // Verify that isLoading is false
    expect(result.current.isLoading).toBe(false);
    
    // Verify that error is null
    expect(result.current.error).toBeNull();
  });
});
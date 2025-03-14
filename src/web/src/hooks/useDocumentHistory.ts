import { useState, useEffect, useCallback } from 'react'; // react 18.x
import { getDocumentHistory } from '../services/historyService';
import { DocumentHistory, HistoryState } from '../models/history.types';

/**
 * Custom hook for retrieving and managing document history data.
 * This hook handles fetching document history from the API, managing loading and error states,
 * and providing a method to refresh the history data.
 * 
 * @param documentId - ID of the document to retrieve history for, or undefined
 * @returns Object containing history data, loading state, error state, and refresh function
 */
export function useDocumentHistory(documentId: number | undefined): HistoryState {
  // Initialize state for history data, loading status, and errors
  const [history, setHistory] = useState<DocumentHistory | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  /**
   * Fetches document history from the API
   */
  const fetchHistory = async () => {
    // Don't attempt to fetch if there's no document ID
    if (!documentId) {
      setHistory(null);
      return;
    }

    try {
      setIsLoading(true);
      setError(null);
      
      const historyData = await getDocumentHistory(documentId);
      setHistory(historyData);
    } catch (err) {
      // Extract and set the error message
      const errorMessage = err instanceof Error 
        ? err.message 
        : 'Failed to load document history';
      setError(errorMessage);
      setHistory(null);
    } finally {
      setIsLoading(false);
    }
  };

  /**
   * Refreshes the history data
   * Wrapped in useCallback to maintain reference equality between renders
   */
  const refreshHistory = useCallback(async () => {
    await fetchHistory();
  }, [documentId]); // Only changes when documentId changes

  // Fetch history when documentId changes or component mounts
  useEffect(() => {
    fetchHistory();
  }, [documentId]);

  // Return state and the refresh function
  return {
    history,
    isLoading,
    error,
    refreshHistory
  };
}
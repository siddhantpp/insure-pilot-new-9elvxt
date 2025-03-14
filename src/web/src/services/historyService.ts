/**
 * Service module for document history functionality
 * 
 * This module provides functions for retrieving and processing document history data
 * from the API. It handles fetching document action history, formatting timestamps,
 * and transforming raw API responses into structured history data for display
 * in the Documents View feature.
 */

import { apiClient } from '../lib/apiClient';
import { formatHistoryTimestamp } from '../lib/dateFormatter';
import { 
  HistoryAction, 
  HistoryEntry, 
  DocumentHistory, 
  HistoryResponse 
} from '../models/history.types';

/**
 * Fetches document history data from the API and formats it for display
 * @param documentId ID of the document to retrieve history for
 * @returns Promise resolving to formatted document history data
 */
export const getDocumentHistory = async (documentId: number): Promise<DocumentHistory> => {
  try {
    // Make API request to fetch document history
    const response = await apiClient.get<HistoryResponse>(`/documents/${documentId}/history`);
    
    // Extract data from the response
    const { actions, lastEdited, lastEditedBy } = response.data;
    
    // Format history entries for display
    const entries = formatHistoryEntries(actions);
    
    // Return formatted document history
    return {
      entries,
      lastEdited,
      lastEditedBy
    };
  } catch (error) {
    // Re-throw the error to be handled by the caller
    throw error;
  }
};

/**
 * Transforms raw history actions into formatted history entries for display
 * @param actions Array of raw history actions from the API
 * @returns Array of formatted history entries
 */
export const formatHistoryEntries = (actions: HistoryAction[]): HistoryEntry[] => {
  // Map each action to a formatted history entry
  return actions.map(action => ({
    id: action.id,
    actionType: action.actionType,
    description: action.description,
    timestamp: action.timestamp,
    formattedTimestamp: formatHistoryTimestamp(action.timestamp),
    user: action.user
  }))
  // Sort by timestamp in descending order (newest first)
  .sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime());
};
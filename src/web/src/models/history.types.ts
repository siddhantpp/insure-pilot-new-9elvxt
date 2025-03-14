/**
 * TypeScript interfaces and types for document history and audit trail functionality
 * in the Documents View feature. This file defines the data structures used for displaying
 * document history, including action types, history entries, and the overall document history model.
 */
import { DocumentUser, DocumentActionType } from './document.types';

/**
 * Interface for raw history action data
 */
export interface HistoryAction {
  /** Unique identifier for the action */
  id: number;
  
  /** ID of the document this action relates to */
  documentId: number;
  
  /** Type of action performed */
  actionType: DocumentActionType;
  
  /** Human-readable description of the action */
  description: string;
  
  /** ISO timestamp when the action occurred */
  timestamp: string;
  
  /** User who performed the action */
  user: DocumentUser;
}

/**
 * Interface for formatted history entry for display
 */
export interface HistoryEntry {
  /** Unique identifier for the entry */
  id: number;
  
  /** Type of action performed */
  actionType: DocumentActionType;
  
  /** Human-readable description of the action */
  description: string;
  
  /** ISO timestamp when the action occurred */
  timestamp: string;
  
  /** Formatted timestamp for display (e.g., "May 12, 2023 10:45 AM") */
  formattedTimestamp: string;
  
  /** User who performed the action */
  user: DocumentUser;
}

/**
 * Interface for complete document history data
 */
export interface DocumentHistory {
  /** Array of history entries, typically sorted by timestamp (newest first) */
  entries: HistoryEntry[];
  
  /** ISO timestamp of when the document was last edited */
  lastEdited: string;
  
  /** User who last edited the document */
  lastEditedBy: DocumentUser;
}

/**
 * Interface for history hook state
 */
export interface HistoryState {
  /** The current document history data, or null if not loaded */
  history: DocumentHistory | null;
  
  /** Whether history data is currently being loaded */
  isLoading: boolean;
  
  /** Error message if history loading failed, or null if no error */
  error: string | null;
  
  /** Function to refresh history data */
  refreshHistory: () => Promise<void>;
}

/**
 * Interface for history API response data
 */
export interface HistoryResponse {
  /** Array of history actions from the API */
  actions: HistoryAction[];
  
  /** ISO timestamp of when the document was last edited */
  lastEdited: string;
  
  /** User who last edited the document */
  lastEditedBy: DocumentUser;
}

/**
 * Props interface for the HistoryPanel component
 */
export interface HistoryPanelProps {
  /** ID of the document to show history for */
  documentId: number;
  
  /** Callback when the back button is clicked */
  onBack: () => void;
}

/**
 * Props interface for the HistoryHeader component
 */
export interface HistoryHeaderProps {
  /** The document history data to display in the header */
  history: DocumentHistory | null;
  
  /** Callback when the back button is clicked */
  onBack: () => void;
}
import React from 'react';
import HistoryItem from './HistoryItem';
import LoadingIndicator from '../common/LoadingIndicator';
import ErrorMessage from '../common/ErrorMessage';
import { DocumentHistory } from '../../models/history.types';

/**
 * Props interface for the HistoryList component
 */
export interface HistoryListProps {
  /** Document history data, or null if not loaded */
  history: DocumentHistory | null;
  /** Whether history data is currently loading */
  isLoading: boolean;
  /** Error message if history loading failed, or null if no error */
  error: string | null;
}

/**
 * Renders a chronological list of document history entries in the Documents View feature.
 * Handles loading states, error conditions, and empty state messaging appropriately.
 * Displays each history entry using the HistoryItem component.
 */
export const HistoryList: React.FC<HistoryListProps> = ({
  history,
  isLoading,
  error,
}) => {
  // Show loading indicator when loading
  if (isLoading) {
    return (
      <div className="history-list-container">
        <LoadingIndicator message="Loading history..." />
      </div>
    );
  }

  // Show error message if there's an error
  if (error) {
    return (
      <div className="history-list-container">
        <ErrorMessage error={error} className="history-list-error" />
      </div>
    );
  }

  // Show message if no history or empty history
  if (!history || history.entries.length === 0) {
    return (
      <div className="history-list-container">
        <div className="history-list-empty" role="status">
          <p>No history available for this document.</p>
        </div>
      </div>
    );
  }

  // Render history list
  return (
    <div className="history-list-container">
      <div 
        className="history-list" 
        role="log" 
        aria-label="Document history" 
        aria-atomic="false"
        aria-relevant="additions"
      >
        {history.entries.map((entry) => (
          <HistoryItem key={entry.id} entry={entry} />
        ))}
      </div>
    </div>
  );
};
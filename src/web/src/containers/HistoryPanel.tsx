import React, { useEffect } from 'react'; // version 18.x - Core React functionality and hooks for component creation and side effects
import classNames from 'classnames'; // version ^2.3.2 - Utility for conditionally joining CSS class names
import { HistoryHeader } from '../components/history/HistoryHeader';
import { HistoryList } from '../components/history/HistoryList';
import LoadingIndicator from '../components/common/LoadingIndicator';
import ErrorMessage from '../components/common/ErrorMessage';
import { useDocumentHistory } from '../hooks/useDocumentHistory';
import { HistoryPanelProps } from '../models/history.types';

/**
 * Container component that displays the document history panel in the Documents View feature.
 * Shows a chronological list of document actions with user attribution and timestamps,
 * providing users with a complete audit trail of document changes.
 */
const HistoryPanel: React.FC<HistoryPanelProps> = ({ documentId, onBack }): JSX.Element => {
  // Use the custom hook to fetch and manage document history data
  const { history, isLoading, error, refreshHistory } = useDocumentHistory(documentId);

  // Refresh history data when documentId changes
  useEffect(() => {
    refreshHistory();
  }, [documentId, refreshHistory]);

  // Define CSS classes for the component
  const panelClasses = classNames('history-panel', {
    'history-panel--loading': isLoading,
    'history-panel--error': !!error,
  });

  return (
    <div 
      className={panelClasses} 
      role="region" 
      aria-label="Document History Panel"
      tabIndex={0} // Make the panel focusable for keyboard navigation
    >
      {/* Render the history header with back button and last edited information */}
      <HistoryHeader history={history} onBack={onBack} />
      
      {/* Render the history list component which handles loading, error, and empty states */}
      <HistoryList 
        history={history} 
        isLoading={isLoading} 
        error={error} 
      />
    </div>
  );
};

export default HistoryPanel;
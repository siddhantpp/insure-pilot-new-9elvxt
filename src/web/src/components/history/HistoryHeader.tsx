import React from 'react'; // version 18.x - Core React functionality
import { BackButton } from '../buttons/BackButton';
import { DocumentHistory, HistoryHeaderProps } from '../../models/history.types';

/**
 * Component that renders the header section of the document history panel
 * Displays the title, back button, and last edited information
 */
export const HistoryHeader: React.FC<HistoryHeaderProps> = ({
  history,
  onBack
}: HistoryHeaderProps): JSX.Element => {
  // Extract last edited information if history exists
  const lastEdited = history?.lastEdited;
  const lastEditedBy = history?.lastEditedBy;

  return (
    <div className="history-header" aria-labelledby="history-title">
      <div className="history-header__navigation">
        <BackButton onClick={onBack} aria-label="Return to metadata panel" />
      </div>
      
      <h2 id="history-title" className="history-header__title">
        Document History
      </h2>
      
      {/* Display last edited information if available */}
      {lastEdited && lastEditedBy && (
        <div className="history-header__last-edited">
          <p className="history-header__last-edited-text">
            Last Edited: {formatTimestamp(lastEdited)}
            <br />
            Last Edited By: {lastEditedBy.username}
          </p>
        </div>
      )}
    </div>
  );
};

/**
 * Formats a timestamp string into a human-readable format
 * @param timestamp - ISO timestamp string
 * @returns Formatted date and time string
 */
const formatTimestamp = (timestamp: string): string => {
  const date = new Date(timestamp);
  return new Intl.DateTimeFormat('en-US', {
    month: '2-digit',
    day: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  }).format(date);
};
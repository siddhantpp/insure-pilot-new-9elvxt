import React from 'react';
import { HistoryEntry } from '../../models/history.types';
import { DocumentActionType } from '../../models/document.types';

/**
 * Props interface for the HistoryItem component
 */
export interface HistoryItemProps {
  /** The history entry to display */
  entry: HistoryEntry;
}

/**
 * Returns the appropriate icon class for a given action type
 */
const getActionIcon = (actionType: DocumentActionType): string => {
  switch (actionType) {
    case DocumentActionType.VIEW:
      return 'icon-eye';
    case DocumentActionType.CREATE:
      return 'icon-plus-circle';
    case DocumentActionType.UPDATE_METADATA:
      return 'icon-edit';
    case DocumentActionType.PROCESS:
      return 'icon-check-circle';
    case DocumentActionType.UNPROCESS:
      return 'icon-undo';
    case DocumentActionType.TRASH:
      return 'icon-trash';
    case DocumentActionType.RESTORE:
      return 'icon-restore';
    default:
      return 'icon-info-circle';
  }
};

/**
 * Returns a user-friendly label for a given action type
 */
const getActionLabel = (actionType: DocumentActionType): string => {
  switch (actionType) {
    case DocumentActionType.VIEW:
      return 'Document viewed';
    case DocumentActionType.CREATE:
      return 'Document uploaded';
    case DocumentActionType.UPDATE_METADATA:
      return 'Changed';
    case DocumentActionType.PROCESS:
      return 'Marked as processed';
    case DocumentActionType.UNPROCESS:
      return 'Unmarked as processed';
    case DocumentActionType.TRASH:
      return 'Moved to trash';
    case DocumentActionType.RESTORE:
      return 'Restored from trash';
    default:
      return 'Unknown action';
  }
};

/**
 * Component that renders a single history entry in the document history panel.
 * It displays information about a document action including the timestamp, user who
 * performed the action, action type, and description.
 */
const HistoryItem: React.FC<HistoryItemProps> = ({ entry }) => {
  const iconClass = getActionIcon(entry.actionType);
  const actionLabel = getActionLabel(entry.actionType);

  return (
    <div className="history-item" role="listitem">
      <div className="history-item-container">
        <div className="history-item-header">
          <time className="history-item-timestamp" dateTime={entry.timestamp}>
            {entry.formattedTimestamp}
          </time>
          <span className="history-item-separator"> - </span>
          <span className="history-item-user">{entry.user.username}</span>
        </div>
        <div className="history-item-content">
          <i className={`${iconClass} history-item-icon`} aria-hidden="true"></i>
          {entry.description ? (
            <span className="history-item-description">{entry.description}</span>
          ) : (
            <span className="history-item-action">{actionLabel}</span>
          )}
        </div>
      </div>
    </div>
  );
};

export default HistoryItem;
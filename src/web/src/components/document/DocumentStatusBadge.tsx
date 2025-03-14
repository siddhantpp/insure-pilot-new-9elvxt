import React from 'react';
import classNames from 'classnames'; // ^2.3.2
import { DocumentStatus } from '../../models/document.types';

/**
 * Props interface for the DocumentStatusBadge component
 */
export interface DocumentStatusBadgeProps {
  /** Current status of the document */
  status: DocumentStatus;
  /** Optional additional CSS class names */
  className?: string;
}

/**
 * A component that displays a visual badge indicating the current status of a document.
 * Provides clear visual feedback with appropriate styling for each status type.
 * This component follows the Insure Pilot design system for status indicators.
 */
const DocumentStatusBadge: React.FC<DocumentStatusBadgeProps> = ({ 
  status, 
  className 
}) => {
  // Determine badge text based on status
  let badgeText: string;
  switch (status) {
    case DocumentStatus.PROCESSED:
      badgeText = 'Processed';
      break;
    case DocumentStatus.UNPROCESSED:
      badgeText = 'Unprocessed';
      break;
    case DocumentStatus.TRASHED:
      badgeText = 'Trashed';
      break;
    default:
      badgeText = 'Unknown';
      break;
  }

  // Create CSS classes using classNames utility
  const badgeClasses = classNames(
    'badge', // Base badge class
    {
      'badge-success': status === DocumentStatus.PROCESSED,
      'badge-secondary': status === DocumentStatus.UNPROCESSED,
      'badge-danger': status === DocumentStatus.TRASHED,
    },
    className // Include any additional classes passed as props
  );

  // Render badge with appropriate styling and text
  return (
    <span 
      className={badgeClasses} 
      aria-label={`Document status: ${badgeText}`}
      data-testid={`document-status-${status}`}
    >
      {badgeText}
    </span>
  );
};

export default DocumentStatusBadge;
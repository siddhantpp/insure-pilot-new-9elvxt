import React from 'react';
import { DocumentStatus } from '../../models/document.types';
import DocumentStatusBadge from './DocumentStatusBadge';

/**
 * Props interface for the DocumentFilename component
 */
export interface DocumentFilenameProps {
  /** The filename to display */
  filename: string;
  /** Optional document status to display alongside filename */
  status?: DocumentStatus;
  /** Optional additional CSS class names */
  className?: string;
}

/**
 * A component that displays the filename of a document in the Documents View feature.
 * 
 * This component is used in the left panel of the lightbox interface to show the
 * current document's name above the PDF viewer. It handles truncation for long
 * filenames through CSS and can optionally display a status badge if a status
 * is provided.
 * 
 * @example
 * // Basic usage
 * <DocumentFilename filename="Policy_Renewal_Notice.pdf" />
 * 
 * // With status badge
 * <DocumentFilename 
 *   filename="Policy_Renewal_Notice.pdf"
 *   status={DocumentStatus.PROCESSED}
 * />
 * 
 * // With custom styling
 * <DocumentFilename
 *   filename="Policy_Renewal_Notice.pdf"
 *   className="custom-filename-style"
 * />
 */
const DocumentFilename: React.FC<DocumentFilenameProps> = ({
  filename,
  status,
  className,
}) => {
  return (
    <div 
      className={`document-filename ${className || ''}`}
      data-testid="document-filename"
      aria-label={`Document: ${filename}${status ? `, Status: ${status}` : ''}`}
    >
      <span 
        className="document-filename-text"
        title={filename}
      >
        {filename}
      </span>
      {status && <DocumentStatusBadge status={status} />}
    </div>
  );
};

export default DocumentFilename;
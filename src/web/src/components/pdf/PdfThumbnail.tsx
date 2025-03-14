import React, { useState, useEffect, useRef, useCallback } from 'react'; // react 18.x
import { usePdfViewer } from '../../hooks/usePdfViewer';
import { useDocumentContext } from '../../context/DocumentContext';
import { Document } from '../../models/document.types';
import { generateThumbnail } from '../../lib/adobeViewer';
import LoadingIndicator from '../common/LoadingIndicator';

/**
 * Props interface for the PdfThumbnail component
 */
export interface PdfThumbnailProps {
  /** The document containing the PDF to generate thumbnail from */
  document: Document;
  /** Page number to generate thumbnail for (1-based index) */
  pageNumber: number;
  /** Dimensions for the thumbnail */
  size: { width: number; height: number };
  /** Callback when thumbnail is clicked */
  onClick: (pageNumber: number) => void;
  /** Whether this thumbnail is currently selected */
  isSelected: boolean;
}

/**
 * A component that renders a thumbnail preview of a PDF document page
 * Provides a compact visual representation for quick document identification and navigation
 */
const PdfThumbnail: React.FC<PdfThumbnailProps> = ({
  document,
  pageNumber,
  size,
  onClick,
  isSelected,
}) => {
  const thumbnailRef = useRef<HTMLDivElement>(null);
  const [thumbnailUrl, setThumbnailUrl] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  
  // Access document context for potential state interactions
  const documentContext = useDocumentContext();

  // Generate thumbnail when document or page number changes
  useEffect(() => {
    if (!document || !document.fileUrl) {
      setError('Document not available');
      setIsLoading(false);
      return;
    }

    setIsLoading(true);
    setError(null);

    const generateAndSetThumbnail = async () => {
      try {
        // Generate thumbnail using Adobe SDK utility
        const thumbnailDataUrl = await generateThumbnail(
          document.fileUrl, 
          pageNumber, 
          size.width, 
          size.height
        );
        setThumbnailUrl(thumbnailDataUrl);
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Failed to generate thumbnail';
        setError(errorMessage);
      } finally {
        setIsLoading(false);
      }
    };

    generateAndSetThumbnail();
  }, [document, pageNumber, size.width, size.height]);

  // Handle thumbnail click
  const handleClick = useCallback(() => {
    onClick(pageNumber);
  }, [onClick, pageNumber]);

  // Handle keyboard interaction for accessibility
  const handleKeyDown = useCallback((e: React.KeyboardEvent<HTMLDivElement>) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      handleClick();
    }
  }, [handleClick]);

  return (
    <div 
      ref={thumbnailRef}
      className={`pdf-thumbnail ${isSelected ? 'pdf-thumbnail-selected' : ''}`}
      style={{ width: `${size.width}px`, height: `${size.height}px` }}
      onClick={handleClick}
      onKeyDown={handleKeyDown}
      role="button"
      aria-label={`Page ${pageNumber}`}
      aria-selected={isSelected}
      tabIndex={0}
    >
      {isLoading && (
        <div className="pdf-thumbnail-loading">
          <LoadingIndicator size="small" message="" />
        </div>
      )}
      
      {!isLoading && error && (
        <div className="pdf-thumbnail-error" title={error}>
          Error
        </div>
      )}
      
      {!isLoading && !error && thumbnailUrl && (
        <img 
          src={thumbnailUrl} 
          alt={`Page ${pageNumber} thumbnail`} 
          className="pdf-thumbnail-image"
          width={size.width}
          height={size.height}
        />
      )}
      
      <div className="pdf-thumbnail-page-number" aria-hidden="true">
        {pageNumber}
      </div>
    </div>
  );
};

export default PdfThumbnail;
export type { PdfThumbnailProps };
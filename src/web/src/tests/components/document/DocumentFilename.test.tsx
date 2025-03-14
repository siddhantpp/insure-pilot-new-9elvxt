import React from 'react';
import { screen } from '@testing-library/react';
import DocumentFilename from '../../../components/document/DocumentFilename';
import { DocumentStatus } from '../../../models/document.types';
import { renderWithProviders } from '../../../utils/testUtils';

describe('DocumentFilename', () => {
  test('renders the filename', () => {
    renderWithProviders(<DocumentFilename filename="test-file.pdf" />);
    expect(screen.getByText('test-file.pdf')).toBeInTheDocument();
  });

  test('applies custom className', () => {
    renderWithProviders(<DocumentFilename filename="test-file.pdf" className="custom-class" />);
    const element = screen.getByTestId('document-filename');
    expect(element).toHaveClass('document-filename');
    expect(element).toHaveClass('custom-class');
  });

  test('truncates long filenames', () => {
    const longFilename = 'very-long-filename-that-should-be-truncated-in-the-ui-but-fully-visible-in-tooltip.pdf';
    renderWithProviders(<DocumentFilename filename={longFilename} />);
    const filenameElement = screen.getByText(longFilename);
    expect(filenameElement).toBeInTheDocument();
    expect(filenameElement).toHaveAttribute('title', longFilename);
  });

  test('renders with processed status badge', () => {
    renderWithProviders(
      <DocumentFilename filename="test-file.pdf" status={DocumentStatus.PROCESSED} />
    );
    expect(screen.getByText('test-file.pdf')).toBeInTheDocument();
    expect(screen.getByTestId(`document-status-${DocumentStatus.PROCESSED}`)).toBeInTheDocument();
  });

  test('renders with unprocessed status badge', () => {
    renderWithProviders(
      <DocumentFilename filename="test-file.pdf" status={DocumentStatus.UNPROCESSED} />
    );
    expect(screen.getByText('test-file.pdf')).toBeInTheDocument();
    expect(screen.getByTestId(`document-status-${DocumentStatus.UNPROCESSED}`)).toBeInTheDocument();
  });

  test('renders with trashed status badge', () => {
    renderWithProviders(
      <DocumentFilename filename="test-file.pdf" status={DocumentStatus.TRASHED} />
    );
    expect(screen.getByText('test-file.pdf')).toBeInTheDocument();
    expect(screen.getByTestId(`document-status-${DocumentStatus.TRASHED}`)).toBeInTheDocument();
  });

  test('renders without status badge when status is not provided', () => {
    renderWithProviders(<DocumentFilename filename="test-file.pdf" />);
    expect(screen.getByText('test-file.pdf')).toBeInTheDocument();
    expect(screen.queryByTestId(/document-status-.*/)).not.toBeInTheDocument();
  });

  test('has correct accessibility attributes', () => {
    renderWithProviders(<DocumentFilename filename="test-file.pdf" />);
    const filenameElement = screen.getByTestId('document-filename');
    expect(filenameElement).toHaveAttribute('aria-label', 'Document: test-file.pdf');
    
    // Test with status
    renderWithProviders(
      <DocumentFilename filename="test-file.pdf" status={DocumentStatus.PROCESSED} />
    );
    const filenameElementWithStatus = screen.getByTestId('document-filename');
    expect(filenameElementWithStatus).toHaveAttribute(
      'aria-label', 
      `Document: test-file.pdf, Status: ${DocumentStatus.PROCESSED}`
    );
  });
});
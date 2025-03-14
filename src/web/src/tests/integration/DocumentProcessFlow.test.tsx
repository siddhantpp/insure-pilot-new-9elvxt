import React from 'react'; // react ^18.2.0
import { screen, waitFor, fireEvent, within } from '@testing-library/react'; // @testing-library/react ^13.4.0
import userEvent from '@testing-library/user-event'; // @testing-library/user-event ^14.4.3
import { describe, it, expect, jest, beforeEach, afterEach } from 'jest'; // jest ^29.5.0
import {
  renderWithProviders,
  createMockDocument,
  mockFetch,
  simulateUserInteraction,
  waitForElementToBeRemoved
} from '../../utils/testUtils';
import DocumentViewerContainer from '../../containers/DocumentViewerContainer';
import { Document, DocumentStatus, DocumentPanelType } from '../../models/document.types';
import { processDocument } from '../../services/documentService';

// Define mock functions and data for testing
describe('Document Process Flow', () => {
  const mockCloseHandler = jest.fn();
  const mockUnprocessedDocument = createMockDocument({ id: 123, status: DocumentStatus.UNPROCESSED, isProcessed: false });
  const mockProcessedDocument = createMockDocument({ id: 123, status: DocumentStatus.PROCESSED, isProcessed: true });

  // Setup function to mock dependencies and reset mocks
  const setupDocumentProcessTests = () => {
    jest.mock('../../services/documentService', () => ({
      processDocument: jest.fn(),
    }));

    beforeEach(() => {
      mockFetch(`/api/documents/123`, mockUnprocessedDocument);
      (processDocument as jest.Mock).mockResolvedValue(mockProcessedDocument);
    });

    afterEach(() => {
      jest.clearAllMocks();
    });
  };

  // Helper function to render the DocumentViewerContainer with test props
  const renderDocumentViewer = (documentId: number, isProcessed: boolean) => {
    mockFetch(`/api/documents/123`, isProcessed ? mockProcessedDocument : mockUnprocessedDocument);

    return renderWithProviders(<DocumentViewerContainer documentId={documentId} onClose={mockCloseHandler} />);
  };

  it('should mark an unprocessed document as processed', async () => {
    // Arrange
    mockFetch(`/api/documents/123`, mockUnprocessedDocument);
    (processDocument as jest.Mock).mockResolvedValue(mockProcessedDocument);

    const { user } = renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockCloseHandler} />);
    await waitFor(() => screen.getByText('Mark as Processed'));

    // Act
    await user.click(screen.getByText('Mark as Processed'));

    // Assert
    expect(processDocument).toHaveBeenCalledWith(123, true);
    await waitFor(() => expect(screen.getByText('Processed')).toBeInTheDocument());
    expect(screen.getByText('Policy Number')).toHaveAttribute('aria-disabled', 'true');
  });

  it('should toggle a processed document back to unprocessed', async () => {
    // Arrange
    mockFetch(`/api/documents/123`, mockProcessedDocument);
    (processDocument as jest.Mock).mockResolvedValue(mockUnprocessedDocument);

    const { user } = renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockCloseHandler} />);
    await waitFor(() => screen.getByText('Processed'));

    // Act
    await user.click(screen.getByText('Processed'));

    // Assert
    expect(processDocument).toHaveBeenCalledWith(123, false);
    await waitFor(() => expect(screen.getByText('Mark as Processed')).toBeInTheDocument());
    expect(screen.getByText('Policy Number')).not.toHaveAttribute('aria-disabled', 'true');
  });

  it('should show loading state during processing', async () => {
    // Arrange
    mockFetch(`/api/documents/123`, mockUnprocessedDocument);
    (processDocument as jest.Mock).mockImplementation(() => new Promise(resolve => {
      setTimeout(() => resolve(mockProcessedDocument), 500);
    }));

    const { user } = renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockCloseHandler} />);
    await waitFor(() => screen.getByText('Mark as Processed'));

    // Act
    await user.click(screen.getByText('Mark as Processed'));

    // Assert
    expect(screen.getByText('Saving...')).toBeInTheDocument();
    await waitFor(() => expect(screen.getByText('Processed')).toBeInTheDocument());
  });

  it('should handle processing errors', async () => {
    // Arrange
    mockFetch(`/api/documents/123`, mockUnprocessedDocument);
    (processDocument as jest.Mock).mockRejectedValue(new Error('Processing failed'));

    const { user } = renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockCloseHandler} />);
    await waitFor(() => screen.getByText('Mark as Processed'));

    // Act
    await user.click(screen.getByText('Mark as Processed'));

    // Assert
    await waitFor(() => expect(screen.getByText('Failed to process document')).toBeInTheDocument());
    expect(screen.getByText('Mark as Processed')).toBeInTheDocument();
    expect(screen.getByText('Policy Number')).not.toHaveAttribute('aria-disabled', 'true');
  });

  it('should update document history after processing', async () => {
    // Arrange
    mockFetch(`/api/documents/123`, mockUnprocessedDocument);
    (processDocument as jest.Mock).mockResolvedValue(mockProcessedDocument);
    mockFetch(`/api/documents/123/history`, {
      actions: [{
        id: 1,
        documentId: 123,
        actionType: 'process',
        description: 'Marked as processed',
        timestamp: '2023-01-01T00:00:00.000Z',
        user: { id: 1, username: 'testuser' }
      }],
      lastEdited: '2023-01-01T00:00:00.000Z',
      lastEditedBy: { id: 1, username: 'testuser' }
    });

    const { user } = renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockCloseHandler} />);
    await waitFor(() => screen.getByText('Mark as Processed'));

    // Act
    await user.click(screen.getByText('Mark as Processed'));
    await waitFor(() => screen.getByText('Processed'));
    await user.click(screen.getByText('Document History'));

    // Assert
    await waitFor(() => expect(screen.getByText('Marked as processed')).toBeInTheDocument());
    expect(screen.getByText('testuser')).toBeInTheDocument();
  });

  it('should maintain processed state after metadata panel navigation', async () => {
    // Arrange
    mockFetch(`/api/documents/123`, mockUnprocessedDocument);
    (processDocument as jest.Mock).mockResolvedValue(mockProcessedDocument);
    mockFetch(`/api/documents/123/history`, {
      actions: [{
        id: 1,
        documentId: 123,
        actionType: 'process',
        description: 'Marked as processed',
        timestamp: '2023-01-01T00:00:00.000Z',
        user: { id: 1, username: 'testuser' }
      }],
      lastEdited: '2023-01-01T00:00:00.000Z',
      lastEditedBy: { id: 1, username: 'testuser' }
    });

    const { user } = renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockCloseHandler} />);
    await waitFor(() => screen.getByText('Mark as Processed'));

    // Act
    await user.click(screen.getByText('Mark as Processed'));
    await waitFor(() => screen.getByText('Processed'));
    await user.click(screen.getByText('Document History'));
    await waitFor(() => screen.getByText('Document History'));
    await user.click(screen.getByLabelText('Return to metadata panel'));

    // Assert
    await waitFor(() => expect(screen.getByText('Processed')).toBeInTheDocument());
    expect(screen.getByText('Policy Number')).toHaveAttribute('aria-disabled', 'true');
  });

  it('should complete the full document processing workflow', async () => {
    // Arrange
    mockFetch(`/api/documents/123`, mockUnprocessedDocument);
    (processDocument as jest.Mock)
      .mockResolvedValueOnce(mockProcessedDocument)
      .mockResolvedValueOnce(mockUnprocessedDocument)
      .mockResolvedValueOnce(mockProcessedDocument);

    const { user } = renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockCloseHandler} />);
    await waitFor(() => screen.getByText('Mark as Processed'));

    // Act & Assert - Initial state
    expect(screen.getByText('Mark as Processed')).toBeInTheDocument();
    expect(screen.getByText('Policy Number')).not.toHaveAttribute('aria-disabled', 'true');

    // Act & Assert - Mark as Processed
    await user.click(screen.getByText('Mark as Processed'));
    await waitFor(() => expect(screen.getByText('Processed')).toBeInTheDocument());
    expect(screen.getByText('Policy Number')).toHaveAttribute('aria-disabled', 'true');

    // Act & Assert - Toggle back to Unprocessed
    await user.click(screen.getByText('Processed'));
    await waitFor(() => expect(screen.getByText('Mark as Processed')).toBeInTheDocument());
    expect(screen.getByText('Policy Number')).not.toHaveAttribute('aria-disabled', 'true');

    // Act & Assert - Mark as Processed again
    await user.click(screen.getByText('Mark as Processed'));
    await waitFor(() => expect(screen.getByText('Processed')).toBeInTheDocument());
    expect(screen.getByText('Policy Number')).toHaveAttribute('aria-disabled', 'true');
  });
});
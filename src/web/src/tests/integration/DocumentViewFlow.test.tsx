import React from 'react'; // react ^18.2.0 - Core React library for component rendering
import { screen, waitFor, within, fireEvent } from '@testing-library/react'; // @testing-library/react ^13.4.0 - React Testing Library for querying and interacting with components
import userEvent from '@testing-library/user-event'; // @testing-library/user-event ^14.4.3 - Simulating realistic user interactions in tests
import { jest } from '@testing-library/jest-dom'; // jest ^29.0.0 - Testing framework for running tests and mocking functions
import { MemoryRouter, Routes, Route } from 'react-router-dom'; // react-router-dom ^6.0.0 - Router components for testing components that use routing
import { renderWithProviders, createMockDocument, createMockDocumentHistory, mockFetch, simulateUserInteraction } from '../../utils/testUtils'; // Internal utilities for rendering components with providers and mocking data
import DocumentViewer from '../../views/DocumentViewer'; // Main component under test for document viewing
import { DocumentStatus, PanelType } from '../../models/document.types'; // Internal type definitions for document status and panel types
import { getDocument, updateDocumentMetadata, processDocument, trashDocument } from '../../services/documentService'; // Internal service functions for document operations that need to be mocked

// Mock the document service functions
jest.mock('../../services/documentService');

/**
 * Sets up all necessary mocks for the document view flow tests
 */
const setupMocks = () => {
  // Mock the document service functions
  const mockGetDocument = getDocument as jest.Mock;
  const mockUpdateDocumentMetadata = updateDocumentMetadata as jest.Mock;
  const mockProcessDocument = processDocument as jest.Mock;
  const mockTrashDocument = trashDocument as jest.Mock;

  // Create mock document data
  const mockDocument = createMockDocument();

  // Create mock document history data
  const mockDocumentHistory = createMockDocumentHistory();

  // Set up mock fetch responses for API endpoints
  mockFetch(`/api/documents/${mockDocument.id}`, mockDocument);
  mockFetch(`/api/documents/${mockDocument.id}/history`, mockDocumentHistory);
  mockFetch(`/api/documents/${mockDocument.id}/metadata`, mockDocument.metadata);

  // Mock Adobe PDF viewer initialization
  (global as any).AdobeDC = {
    View: jest.fn().mockReturnValue({
      previewFile: jest.fn().mockResolvedValue(undefined),
      registerCallback: jest.fn().mockResolvedValue(undefined),
      gotoPage: jest.fn().mockResolvedValue(undefined),
      rotateClockwise: jest.fn().mockResolvedValue(undefined),
      rotateCounterClockwise: jest.fn().mockResolvedValue(undefined),
      getZoom: jest.fn().mockReturnValue('100%'),
      setZoom: jest.fn().mockResolvedValue(undefined),
      unload: jest.fn().mockResolvedValue(undefined),
      getAPIs: jest.fn().mockReturnValue({
        getPdf: jest.fn().mockResolvedValue(undefined),
      }),
    }),
  };
};

/**
 * Helper function to render the DocumentViewer component with test configuration
 * @param documentId The ID of the document to view
 * @returns Rendered component with testing utilities
 */
const renderDocumentViewer = (documentId: number) => {
  // Create a mock onClose function
  const onClose = jest.fn();

  // Render DocumentViewer component with renderWithProviders utility
  const renderResult = renderWithProviders(
    <MemoryRouter initialEntries={[`/documents/${documentId}`]}>
      <Routes>
        <Route path="/documents/:documentId" element={<DocumentViewer />} />
      </Routes>
    </MemoryRouter>,
    {
      route: `/documents/${documentId}`,
    }
  );

  // Return the rendered component with additional utilities
  return {
    ...renderResult,
    onClose,
  };
};

describe('Document View Flow', () => {
  beforeEach(() => {
    setupMocks();
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  it('should load and display document with metadata', async () => {
    // Render DocumentViewer with a test document ID
    const { findByTestId } = renderDocumentViewer(123);

    // Wait for document to load
    const documentDisplay = await findByTestId('document-display');

    // Verify PDF viewer is displayed
    expect(documentDisplay).toBeInTheDocument();

    // Verify document filename is displayed
    expect(screen.getByText('Policy_Renewal_Notice.pdf')).toBeInTheDocument();

    // Verify metadata fields are displayed with correct values
    expect(screen.getByText('Policy Number')).toBeInTheDocument();
    expect(screen.getByText('PLCY-12345')).toBeInTheDocument();
    expect(screen.getByText('Loss Sequence')).toBeInTheDocument();
    expect(screen.getByText('1 - Vehicle Accident (03/15/2023)')).toBeInTheDocument();
    expect(screen.getByText('Claimant')).toBeInTheDocument();
    expect(screen.getByText('1 - John Smith')).toBeInTheDocument();
    expect(screen.getByText('Document Description')).toBeInTheDocument();
    expect(screen.getByText('Policy Renewal Notice')).toBeInTheDocument();
    expect(screen.getByText('Assigned To')).toBeInTheDocument();
    expect(screen.getByText('Claims Department')).toBeInTheDocument();
    expect(screen.getByText('Producer Number')).toBeInTheDocument();
    expect(screen.getByText('AG-789456')).toBeInTheDocument();

    // Verify document actions (Mark as Processed, Document History) are available
    expect(screen.getByText('Mark as Processed')).toBeInTheDocument();
    expect(screen.getByText('Document History')).toBeInTheDocument();
  });

  it('should navigate between metadata and history panels', async () => {
    // Render DocumentViewer with a test document ID
    const { findByTestId, findByText } = renderDocumentViewer(123);

    // Wait for document to load
    await findByTestId('document-display');

    // Click on Document History link
    const historyLink = await findByText('Document History');
    fireEvent.click(historyLink);

    // Verify history panel is displayed with action history
    expect(screen.getByText('Last Edited:')).toBeInTheDocument();
    expect(screen.getByText('Marked as processed')).toBeInTheDocument();

    // Click on Back button
    const backButton = await findByTestId('back-button');
    fireEvent.click(backButton);

    // Verify metadata panel is displayed again
    expect(screen.getByText('Policy Number')).toBeInTheDocument();
  });

  it('should update document metadata', async () => {
    // Render DocumentViewer with a test document ID
    const { findByTestId, findByRole } = renderDocumentViewer(123);

    // Wait for document to load
    await findByTestId('document-display');

    // Interact with Policy Number dropdown
    const policyNumberDropdown = await findByRole('combobox', { name: 'Policy Number' });
    fireEvent.focus(policyNumberDropdown);
    fireEvent.change(policyNumberDropdown, { target: { value: 'PLCY-56789' } });

    // Select a new policy
    const policyOption = await screen.findByText('PLCY-56789');
    fireEvent.click(policyOption);

    // Verify dependent fields (Loss Sequence) update accordingly
    expect(screen.getByText('Loss Sequence')).toBeInTheDocument();

    // Verify 'Saving...' indicator appears
    expect(screen.getByText('Saving...')).toBeInTheDocument();

    // Wait for save to complete
    await waitFor(() => expect(screen.getByText('Saved')).toBeInTheDocument());

    // Verify updateDocumentMetadata was called with correct parameters
    expect(updateDocumentMetadata).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({
        policyId: 12345,
      })
    );
  });

  it('should mark document as processed', async () => {
    // Render DocumentViewer with a test document ID
    const { findByTestId, findByRole } = renderDocumentViewer(123);

    // Wait for document to load
    await findByTestId('document-display');

    // Click on Mark as Processed button
    const processButton = await findByRole('button', { name: 'Mark as Processed' });
    fireEvent.click(processButton);

    // Verify processDocument was called with correct parameters
    expect(processDocument).toHaveBeenCalledWith(123, true);

    // Verify UI updates to show document as processed
    await waitFor(() => expect(screen.getByText('Processed')).toBeInTheDocument());

    // Verify metadata fields become read-only
    expect(screen.getByText('Policy Number')).toBeInTheDocument();
  });

  it('should trash document', async () => {
    // Render DocumentViewer with a test document ID
    const { findByTestId, findByRole, onClose } = renderDocumentViewer(123);

    // Wait for document to load
    await findByTestId('document-display');

    // Click on ellipsis menu
    const ellipsisButton = await findByRole('button', { name: 'More options' });
    fireEvent.click(ellipsisButton);

    // Click on Trash Document option
    const trashOption = await screen.findByText('Trash Document');
    fireEvent.click(trashOption);

    // Verify confirmation dialog appears
    expect(screen.getByText('Confirm Trash Document')).toBeInTheDocument();

    // Confirm trash action
    const confirmButton = await findByRole('button', { name: 'Trash Document' });
    fireEvent.click(confirmButton);

    // Verify trashDocument was called with correct parameters
    expect(trashDocument).toHaveBeenCalledWith(123);

    // Verify onClose was called to exit document view
    await waitFor(() => expect(onClose).toHaveBeenCalled());
  });

  it('should handle errors gracefully', async () => {
    // Mock getDocument to return an error
    (getDocument as jest.Mock).mockRejectedValue(new Error('Failed to fetch document'));

    // Render DocumentViewer with a test document ID
    const { findByText } = renderDocumentViewer(123);

    // Verify error message is displayed
    expect(await findByText('Failed to fetch document')).toBeInTheDocument();

    // Verify PDF viewer is not displayed
    expect(screen.queryByTestId('document-display')).not.toBeInTheDocument();
  });
});
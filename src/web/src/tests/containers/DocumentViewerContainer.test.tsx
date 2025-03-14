import React from 'react'; // react 18.x
import { screen, waitFor, fireEvent } from '@testing-library/react'; // @testing-library/react ^13.4.0
import userEvent from '@testing-library/user-event'; // @testing-library/user-event ^14.4.3
import { jest } from 'jest'; // jest ^29.5.0
import DocumentViewerContainer from '../../containers/DocumentViewerContainer';
import { renderWithProviders, createMockDocument, createMockContextValue } from '../../utils/testUtils';
import { DocumentContext } from '../../context/DocumentContext';
import { PanelType } from '../../models/document.types';

describe('DocumentViewerContainer', () => {
  // Set up test suite for DocumentViewerContainer
  let mockLoadDocument: jest.Mock;
  let mockUpdateMetadata: jest.Mock;
  let mockProcessDocument: jest.Mock;
  let mockTrashDocument: jest.Mock;
  let mockSetActivePanel: jest.Mock;
  let mockOnClose: jest.Mock;

  beforeEach(() => {
    // Reset all mocks before each test
    mockLoadDocument = jest.fn().mockResolvedValue(undefined);
    mockUpdateMetadata = jest.fn().mockResolvedValue(undefined);
    mockProcessDocument = jest.fn().mockResolvedValue(undefined);
    mockTrashDocument = jest.fn().mockResolvedValue(undefined);
    mockSetActivePanel = jest.fn();
    mockOnClose = jest.fn();
  });

  afterEach(() => {
    // Clean up any resources created during tests
    jest.clearAllMocks();
  });

  it('should render loading state initially', async () => {
    // Create mock document context with isLoading set to true
    const documentContextValue = createMockContextValue('document', {
      state: {
        isLoading: true,
        document: null,
        error: null,
        activePanel: PanelType.METADATA,
        isSaving: false,
        saveError: null
      },
      loadDocument: mockLoadDocument,
      updateMetadata: mockUpdateMetadata,
      processDocument: mockProcessDocument,
      trashDocument: mockTrashDocument,
      setActivePanel: mockSetActivePanel
    });

    // Render DocumentViewerContainer with the mock context
    renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockOnClose} />, {
      documentContextValue
    });

    // Assert that loading indicator is displayed
    expect(screen.getByText('Loading document...')).toBeInTheDocument();

    // Assert that document content is not displayed yet
    expect(screen.queryByText('Policy_Renewal_Notice.pdf')).not.toBeInTheDocument();
  });

  it('should render error message when document loading fails', async () => {
    // Create mock document context with error state
    const documentContextValue = createMockContextValue('document', {
      state: {
        isLoading: false,
        document: null,
        error: 'Failed to load document',
        activePanel: PanelType.METADATA,
        isSaving: false,
        saveError: null
      },
      loadDocument: mockLoadDocument,
      updateMetadata: mockUpdateMetadata,
      processDocument: mockProcessDocument,
      trashDocument: mockTrashDocument,
      setActivePanel: mockSetActivePanel
    });

    // Render DocumentViewerContainer with the mock context
    renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockOnClose} />, {
      documentContextValue
    });

    // Assert that error message is displayed
    expect(screen.getByText('Failed to load document')).toBeInTheDocument();

    // Assert that document content is not displayed
    expect(screen.queryByText('Policy_Renewal_Notice.pdf')).not.toBeInTheDocument();
  });

  it('should render document content when loaded', async () => {
    // Create mock document with test data
    const mockDocument = createMockDocument();

    // Create mock document context with loaded document
    const documentContextValue = createMockContextValue('document', {
      state: {
        document: mockDocument,
        isLoading: false,
        error: null,
        activePanel: PanelType.METADATA,
        isSaving: false,
        saveError: null
      },
      loadDocument: mockLoadDocument,
      updateMetadata: mockUpdateMetadata,
      processDocument: mockProcessDocument,
      trashDocument: mockTrashDocument,
      setActivePanel: mockSetActivePanel
    });

    // Render DocumentViewerContainer with the mock context
    renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockOnClose} />, {
      documentContextValue
    });

    // Assert that document display component is rendered
    expect(screen.getByTestId('document-display')).toBeInTheDocument();

    // Assert that metadata panel is displayed
    expect(screen.getByText('Policy Number')).toBeInTheDocument();

    // Assert that document filename is displayed correctly
    expect(screen.getByText('Policy_Renewal_Notice.pdf')).toBeInTheDocument();
  });

  it('should call loadDocument when documentId changes', async () => {
    // Create mock loadDocument function
    const mockLoadDocument = jest.fn().mockResolvedValue(undefined);

    // Create mock document context with the mock function
    const documentContextValue = createMockContextValue('document', {
      state: {
        document: null,
        isLoading: false,
        error: null,
        activePanel: PanelType.METADATA,
        isSaving: false,
        saveError: null
      },
      loadDocument: mockLoadDocument,
      updateMetadata: mockUpdateMetadata,
      processDocument: mockProcessDocument,
      trashDocument: mockTrashDocument,
      setActivePanel: mockSetActivePanel
    });

    // Render DocumentViewerContainer with initial documentId
    const { rerender } = renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockOnClose} />, {
      documentContextValue
    });

    // Assert that loadDocument was called with initial documentId
    expect(mockLoadDocument).toHaveBeenCalledWith(123);

    // Re-render with a different documentId
    rerender(<DocumentViewerContainer documentId={456} onClose={mockOnClose} />);

    // Assert that loadDocument was called again with the new documentId
    expect(mockLoadDocument).toHaveBeenCalledWith(456);
  });

  it('should handle metadata changes', async () => {
    // Create mock updateMetadata function
    const mockUpdateMetadata = jest.fn().mockResolvedValue(undefined);
    const mockDocument = createMockDocument();

    // Create mock document context with the mock function
    const documentContextValue = createMockContextValue('document', {
      state: {
        document: mockDocument,
        isLoading: false,
        error: null,
        activePanel: PanelType.METADATA,
        isSaving: false,
        saveError: null
      },
      loadDocument: mockLoadDocument,
      updateMetadata: mockUpdateMetadata,
      processDocument: mockProcessDocument,
      trashDocument: mockTrashDocument,
      setActivePanel: mockSetActivePanel
    });

    // Render DocumentViewerContainer with the mock context
    renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockOnClose} />, {
      documentContextValue
    });

    // Simulate metadata change through MetadataPanel
    const policyNumberInput = screen.getByLabelText('Policy Number');
    fireEvent.change(policyNumberInput, { target: { value: 'PLCY-67890' } });

    // Assert that updateMetadata was called with correct parameters
    await waitFor(() => {
      expect(mockUpdateMetadata).toHaveBeenCalled();
    });

    // Assert that saving indicator is displayed during update
    expect(screen.getByText('Saving...')).toBeInTheDocument();

    // Assert that saved indicator is displayed after update completes
    await waitFor(() => {
      expect(screen.getByText('Saved')).toBeInTheDocument();
    });
  });

  it('should handle document processing', async () => {
    // Create mock processDocument function
    const mockProcessDocument = jest.fn().mockResolvedValue(undefined);
    const mockDocument = createMockDocument();

    // Create mock document context with the mock function
    const documentContextValue = createMockContextValue('document', {
      state: {
        document: mockDocument,
        isLoading: false,
        error: null,
        activePanel: PanelType.METADATA,
        isSaving: false,
        saveError: null
      },
      loadDocument: mockLoadDocument,
      updateMetadata: mockUpdateMetadata,
      processDocument: mockProcessDocument,
      trashDocument: mockTrashDocument,
      setActivePanel: mockSetActivePanel
    });

    // Render DocumentViewerContainer with the mock context
    renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockOnClose} />, {
      documentContextValue
    });

    // Simulate clicking 'Mark as Processed' button
    const processButton = screen.getByRole('button', { name: 'Mark as Processed' });
    fireEvent.click(processButton);

    // Assert that processDocument was called with true parameter
    expect(mockProcessDocument).toHaveBeenCalledWith(true);

    // Assert that UI updates to show processed state
    await waitFor(() => {
      expect(screen.getByRole('button', { name: 'Processed' })).toBeInTheDocument();
    });
  });

  it('should handle document trashing', async () => {
    // Create mock trashDocument function
    const mockTrashDocument = jest.fn().mockResolvedValue(undefined);

    // Create mock onClose function
    const mockOnClose = jest.fn();
    const mockDocument = createMockDocument();

    // Create mock document context with the mock functions
    const documentContextValue = createMockContextValue('document', {
      state: {
        document: mockDocument,
        isLoading: false,
        error: null,
        activePanel: PanelType.METADATA,
        isSaving: false,
        saveError: null
      },
      loadDocument: mockLoadDocument,
      updateMetadata: mockUpdateMetadata,
      processDocument: mockProcessDocument,
      trashDocument: mockTrashDocument,
      setActivePanel: mockSetActivePanel
    });

    // Render DocumentViewerContainer with the mock context
    renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockOnClose} />, {
      documentContextValue
    });

    // Simulate clicking trash button
    const trashButton = screen.getByRole('button', { name: 'Trash document' });
    fireEvent.click(trashButton);

    // Simulate confirming trash action
    const confirmButton = screen.getByRole('button', { name: 'Trash Document' });
    fireEvent.click(confirmButton);

    // Assert that trashDocument was called
    expect(mockTrashDocument).toHaveBeenCalled();

    // Assert that onClose was called after successful trash operation
    await waitFor(() => {
      expect(mockOnClose).toHaveBeenCalled();
    });
  });

  it('should switch between metadata and history panels', async () => {
    // Create mock setActivePanel function
    const mockSetActivePanel = jest.fn();
    const mockDocument = createMockDocument();

    // Create mock document context with the mock function
    const documentContextValue = createMockContextValue('document', {
      state: {
        document: mockDocument,
        isLoading: false,
        error: null,
        activePanel: PanelType.METADATA,
        isSaving: false,
        saveError: null
      },
      loadDocument: mockLoadDocument,
      updateMetadata: mockUpdateMetadata,
      processDocument: mockProcessDocument,
      trashDocument: mockTrashDocument,
      setActivePanel: mockSetActivePanel
    });

    // Render DocumentViewerContainer with the mock context
    renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockOnClose} />, {
      documentContextValue
    });

    // Assert that metadata panel is displayed initially
    expect(screen.getByText('Policy Number')).toBeInTheDocument();

    // Simulate clicking 'Document History' link
    const historyLink = screen.getByText('Document History');
    fireEvent.click(historyLink);

    // Assert that setActivePanel was called with PanelType.HISTORY
    expect(mockSetActivePanel).toHaveBeenCalledWith(PanelType.HISTORY);

    // Update mock context to show history panel
    const historyDocumentContextValue = createMockContextValue('document', {
      state: {
        document: mockDocument,
        isLoading: false,
        error: null,
        activePanel: PanelType.HISTORY,
        isSaving: false,
        saveError: null
      },
      loadDocument: mockLoadDocument,
      updateMetadata: mockUpdateMetadata,
      processDocument: mockProcessDocument,
      trashDocument: mockTrashDocument,
      setActivePanel: mockSetActivePanel
    });

    // Assert that history panel is displayed
    expect(screen.getByText('Document History')).toBeInTheDocument();

    // Simulate clicking 'Back' button
    const backButton = screen.getByRole('button', { name: 'Back' });
    fireEvent.click(backButton);

    // Assert that setActivePanel was called with PanelType.METADATA
    expect(mockSetActivePanel).toHaveBeenCalledWith(PanelType.METADATA);

    // Assert that metadata panel is displayed again
    expect(screen.getByText('Policy Number')).toBeInTheDocument();
  });

  it('should use initialPanel prop when provided', async () => {
    // Create mock setActivePanel function
    const mockSetActivePanel = jest.fn();
    const mockDocument = createMockDocument();

    // Create mock document context with the mock function
    const documentContextValue = createMockContextValue('document', {
      state: {
        document: mockDocument,
        isLoading: false,
        error: null,
        activePanel: PanelType.HISTORY,
        isSaving: false,
        saveError: null
      },
      loadDocument: mockLoadDocument,
      updateMetadata: mockUpdateMetadata,
      processDocument: mockProcessDocument,
      trashDocument: mockTrashDocument,
      setActivePanel: mockSetActivePanel
    });

    // Render DocumentViewerContainer with initialPanel set to PanelType.HISTORY
    renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockOnClose} initialPanel={PanelType.HISTORY} />, {
      documentContextValue
    });

    // Assert that setActivePanel was called with PanelType.HISTORY
    expect(mockSetActivePanel).toHaveBeenCalledWith(PanelType.HISTORY);

    // Assert that history panel is displayed initially
    expect(screen.getByText('Document History')).toBeInTheDocument();
  });

  it('should handle keyboard shortcuts', async () => {
    // Create mock onClose function
    const mockOnClose = jest.fn();

    // Create mock updateMetadata function
    const mockUpdateMetadata = jest.fn().mockResolvedValue(undefined);
    const mockDocument = createMockDocument();

    // Create mock document context with the mock functions
    const documentContextValue = createMockContextValue('document', {
      state: {
        document: mockDocument,
        isLoading: false,
        error: null,
        activePanel: PanelType.METADATA,
        isSaving: false,
        saveError: null
      },
      loadDocument: mockLoadDocument,
      updateMetadata: mockUpdateMetadata,
      processDocument: mockProcessDocument,
      trashDocument: mockTrashDocument,
      setActivePanel: mockSetActivePanel
    });

    // Render DocumentViewerContainer with the mock context
    renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockOnClose} />, {
      documentContextValue
    });

    // Simulate pressing Escape key
    fireEvent.keyDown(document, { key: 'Escape' });

    // Assert that onClose was called
    expect(mockOnClose).toHaveBeenCalled();

    // Simulate pressing Ctrl+S key combination
    fireEvent.keyDown(document, { key: 's', ctrlKey: true });

    // Assert that updateMetadata was called to save current state
    await waitFor(() => {
      expect(mockUpdateMetadata).toHaveBeenCalled();
    });
  });

  it('should close when close button is clicked', async () => {
    // Create mock onClose function
    const mockOnClose = jest.fn();

    // Render DocumentViewerContainer with the mock onClose function
    renderWithProviders(<DocumentViewerContainer documentId={123} onClose={mockOnClose} />);

    // Simulate clicking close button
    const closeButton = screen.getByRole('button', { name: 'Close document viewer' });
    fireEvent.click(closeButton);

    // Assert that onClose was called
    expect(mockOnClose).toHaveBeenCalled();
  });
});
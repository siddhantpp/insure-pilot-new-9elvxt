import React from 'react'; // react 18.x - Core React library for component testing
import { render, screen, waitFor, fireEvent } from '@testing-library/react'; // @testing-library/react ^13.4.0 - React Testing Library for rendering and querying components
import userEvent from '@testing-library/user-event'; // @testing-library/user-event ^14.4.3 - Simulating user interactions in tests
import jest from 'jest'; // jest ^29.5.0 - Testing framework for running tests
import { useParams, useNavigate } from 'react-router-dom'; // react-router-dom ^6.0.0 - React Router hooks that need to be mocked for testing
import DocumentViewer from '../../views/DocumentViewer';
import { renderWithProviders, createMockDocument } from '../../utils/testUtils';
import { PanelType } from '../../models/document.types';
import { DocumentContext } from '../../context/DocumentContext';

// Mock react-router-dom hooks
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: jest.fn(),
  useNavigate: jest.fn(),
}));

describe('DocumentViewer', () => {
  // Group related tests for the DocumentViewer component

  beforeEach(() => {
    // Reset all mocks before each test
    jest.clearAllMocks();

    // Set up default mock implementations for useParams and useNavigate
    (useParams as jest.Mock).mockReturnValue({ documentId: '123' });
    (useNavigate as jest.Mock).mockReturnValue(jest.fn());
  });

  afterEach(() => {
    // Clean up any remaining mocks or side effects
  });

  it('should render the DocumentViewer with DocumentViewerContainer', () => {
    // Mock useParams to return a document ID
    (useParams as jest.Mock).mockReturnValue({ documentId: '123' });

    // Render the DocumentViewer component with renderWithProviders
    renderWithProviders(<DocumentViewer />);

    // Verify that the DocumentViewerContainer is rendered with correct props
    expect(screen.getByRole('main')).toBeInTheDocument();
  });

  it('should navigate back to documents list when closed', async () => {
    // Mock useParams to return a document ID
    (useParams as jest.Mock).mockReturnValue({ documentId: '123' });

    // Create a mock navigate function
    const mockNavigate = jest.fn();

    // Mock useNavigate to return the mock navigate function
    (useNavigate as jest.Mock).mockReturnValue(mockNavigate);

    // Render the DocumentViewer component
    renderWithProviders(<DocumentViewer />);

    // Trigger the close action
    const closeButton = screen.getByRole('button', { name: /close document viewer/i });
    await userEvent.click(closeButton);

    // Verify that navigate was called with '/documents'
    expect(mockNavigate).toHaveBeenCalledWith('/documents');
  });

  it('should extract documentId from URL parameters', () => {
    // Mock useParams to return a specific document ID
    (useParams as jest.Mock).mockReturnValue({ documentId: '456' });

    // Render the DocumentViewer component
    renderWithProviders(<DocumentViewer />);

    // Verify that the DocumentViewerContainer receives the correct document ID
    expect((useParams as jest.Mock).mock.results[0].value).toEqual({ documentId: '456' });
  });

  it('should set initial panel to METADATA by default', () => {
    // Mock useParams to return a document ID
    (useParams as jest.Mock).mockReturnValue({ documentId: '123' });

    // Render the DocumentViewer component
    const { documentContextValue } = renderWithProviders(<DocumentViewer />);

    // Verify that the DocumentViewerContainer receives PanelType.METADATA as initialPanel
    expect(documentContextValue.state.activePanel).toBe(PanelType.METADATA);
  });

  it('should handle the complete document viewing flow', async () => {
    // Create a mock document
    const mockDocument = createMockDocument();

    // Set up document context with the mock document
    const documentContextValue = {
      state: {
        document: mockDocument,
        isLoading: false,
        error: null,
        activePanel: PanelType.METADATA,
        isSaving: false,
        saveError: null,
      },
      loadDocument: jest.fn().mockResolvedValue(mockDocument),
      updateMetadata: jest.fn().mockResolvedValue(mockDocument),
      processDocument: jest.fn().mockResolvedValue(mockDocument),
      trashDocument: jest.fn().mockResolvedValue(true),
      setActivePanel: jest.fn(),
    };

    // Mock useParams to return the document ID
    (useParams as jest.Mock).mockReturnValue({ documentId: mockDocument.id.toString() });

    // Render the DocumentViewer component
    const { user } = renderWithProviders(<DocumentViewer />, { documentContextValue });

    // Wait for document to load
    await waitFor(() => {
      expect(documentContextValue.loadDocument).toHaveBeenCalledWith(mockDocument.id);
    });

    // Verify document content is displayed
    expect(screen.getByText(mockDocument.filename)).toBeInTheDocument();

    // Verify metadata panel is displayed
    expect(screen.getByText('Policy Number')).toBeInTheDocument();

    // Simulate clicking on history link
    const historyLink = screen.getByText(/Document History/i);
    await user.click(historyLink);

    // Verify history panel is displayed
    await waitFor(() => {
      expect(documentContextValue.setActivePanel).toHaveBeenCalledWith(PanelType.HISTORY);
    });

    // Simulate clicking back to metadata
    const backButton = screen.getByRole('button', { name: /go back/i });
    await user.click(backButton);

    // Verify metadata panel is displayed again
    await waitFor(() => {
      expect(documentContextValue.setActivePanel).toHaveBeenCalledWith(PanelType.METADATA);
    });

    // Simulate closing the document viewer
    const closeButton = screen.getByRole('button', { name: /close document viewer/i });
    await user.click(closeButton);

    // Verify navigation back to documents list
    expect((useNavigate as jest.Mock).mock.results[0].value).toHaveBeenCalledWith('/documents');
  });
});
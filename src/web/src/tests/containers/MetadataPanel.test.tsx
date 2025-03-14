import React from 'react'; // React v18.x
import { render, screen, fireEvent, waitFor, within } from '@testing-library/react'; // @testing-library/react ^13.4.0
import userEvent from '@testing-library/user-event'; // @testing-library/user-event ^14.4.3
import { jest } from 'jest'; // jest ^29.5.0

import { MetadataPanel } from '../../containers/MetadataPanel'; // Component under test
import { renderWithProviders, createMockDocument } from '../../utils/testUtils'; // Test utilities
import { DocumentContext } from '../../context/DocumentContext'; // Document context for mocking
import { MetadataFieldName, METADATA_FIELD_CONFIGS } from '../../models/metadata.types'; // Type definitions
import { useMetadataForm } from '../../hooks/useMetadataForm'; // Hook for metadata form

/**
 * Test suite for MetadataPanel component
 */
describe('MetadataPanel', () => {
  /**
   * Setup function that runs before each test
   */
  beforeEach(() => {
    jest.clearAllMocks(); // Reset all mocks before each test
  });

  /**
   * Cleanup function that runs after each test
   */
  afterEach(() => {
    // Clean up any resources created during tests
  });

  /**
   * Sets up mock handlers for component props
   */
  const setupMockHandlers = () => {
    const onMetadataChange = jest.fn();
    const onProcessDocument = jest.fn();
    const onTrashDocument = jest.fn();
    const onViewHistory = jest.fn();
    return { onMetadataChange, onProcessDocument, onTrashDocument, onViewHistory };
  };

  /**
   * Helper function to render the MetadataPanel with common props
   */
  const renderMetadataPanel = (props = {}) => {
    const mockDocument = createMockDocument();
    const mockHandlers = setupMockHandlers();
    return renderWithProviders(
      <MetadataPanel
        document={mockDocument}
        onMetadataChange={mockHandlers.onMetadataChange}
        onProcessDocument={mockHandlers.onProcessDocument}
        onTrashDocument={mockHandlers.onTrashDocument}
        onViewHistory={mockHandlers.onViewHistory}
        isSaving={false}
        saveError={null}
        {...props}
      />
    );
  };

  /**
   * Individual test case
   */
  it('renders all metadata fields', () => {
    renderMetadataPanel();
    METADATA_FIELD_CONFIGS.forEach(fieldConfig => {
      expect(screen.getByText(fieldConfig.label)).toBeInTheDocument();
    });
  });

  /**
   * Individual test case
   */
  it('calls onViewHistory when Document History link is clicked', async () => {
    const { documentContextValue } = renderMetadataPanel();
    const historyLink = screen.getByText('Document History');
    fireEvent.click(historyLink);
    expect(documentContextValue.setActivePanel).toHaveBeenCalledWith('history');
  });

  /**
   * Individual test case
   */
  it('calls onTrashDocument when Trash button is clicked', async () => {
    const { documentContextValue } = renderMetadataPanel();
    const trashButton = screen.getByText('Trash');
    fireEvent.click(trashButton);
    
    // Confirm the trash action
    const confirmButton = screen.getByText('Trash Document');
    fireEvent.click(confirmButton);

    // Wait for the trash action to complete
    await waitFor(() => {
      expect(documentContextValue.trashDocument).toHaveBeenCalled();
    });
  });

  /**
   * Individual test case
   */
  it('displays navigation options in ellipsis menu', async () => {
    renderMetadataPanel();
    const ellipsisButton = screen.getByLabelText('More options');
    fireEvent.click(ellipsisButton);

    await screen.findByText('Go to Producer View');
    await screen.findByText('Go to Policy');
    await screen.findByText('Go to Claimant View');
  });

  /**
   * Individual test case
   */
  it('filters dropdown options based on user input', async () => {
    renderMetadataPanel();
    const policyNumberInput = screen.getByRole('combobox', { name: 'Policy Number' });
    fireEvent.focus(policyNumberInput);
    fireEvent.change(policyNumberInput, { target: { value: 'PLCY-123' } });

    await waitFor(() => {
      expect(screen.getByText('PLCY-12345')).toBeInTheDocument();
    });
  });

  /**
   * Individual test case
   */
  it('displays "Saving..." indicator when isSaving is true', () => {
    renderMetadataPanel({ isSaving: true });
    expect(screen.getByText('Saving...')).toBeInTheDocument();
  });

  /**
   * Individual test case
   */
  it('displays save error message when saveError is not null', () => {
    renderMetadataPanel({ saveError: 'Failed to save metadata' });
    expect(screen.getByText('Failed to save metadata')).toBeInTheDocument();
  });

  /**
   * Individual test case
   */
  it('marks metadata fields as read-only when document is processed', () => {
    const mockDocument = createMockDocument({ isProcessed: true });
    renderMetadataPanel({ document: mockDocument });
    METADATA_FIELD_CONFIGS.forEach(fieldConfig => {
      const input = screen.getByDisplayValue(mockDocument.metadata[fieldConfig.name]);
      expect(input).toHaveAttribute('aria-readonly', 'true');
    });
  });
});
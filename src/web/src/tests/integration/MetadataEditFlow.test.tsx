# src/web/src/tests/integration/MetadataEditFlow.test.tsx
```typescript
import React from 'react'; // react ^18.2.0 - Core React library for component rendering
import { screen, waitFor, within, fireEvent } from '@testing-library/react'; // @testing-library/react ^13.4.0 - React Testing Library for querying and interacting with components
import userEvent from '@testing-library/user-event'; // @testing-library/user-event ^14.4.3 - Simulating realistic user interactions in tests
import { MemoryRouter, Routes, Route } from 'react-router-dom'; // react-router-dom ^6.0.0 - Router components for testing components that use routing
import { renderWithProviders, createMockDocument, mockFetch } from '../../utils/testUtils';
import DocumentViewer from '../../views/DocumentViewer';
import { MetadataFieldName } from '../../models/metadata.types';
import { DocumentStatus } from '../../models/document.types';
import { getDocument, updateDocumentMetadata } from '../../services/documentService';
import { getPolicyOptions, getLossOptions, getClaimantOptions, getProducerOptions, getDocumentDescriptionOptions, getAssigneeOptions } from '../../services/metadataService';

// Mock service dependencies
jest.mock('../../services/documentService');
jest.mock('../../services/metadataService');

// Jest test suite for Metadata Edit Flow
describe('Metadata Edit Flow', () => {
  // Setup function that runs before each test
  beforeEach(() => {
    setupMocks();
  });

  // Cleanup function that runs after each test
  afterEach(() => {
    jest.clearAllMocks();
  });

  // Helper function to set up all necessary mocks
  const setupMocks = () => {
    // Mock the document service functions
    (getDocument as jest.Mock).mockResolvedValue(createMockDocument());
    (updateDocumentMetadata as jest.Mock).mockResolvedValue(createMockDocument());

    // Mock the metadata service functions
    (getPolicyOptions as jest.Mock).mockResolvedValue(createMockDropdownOptions(MetadataFieldName.POLICY_NUMBER));
    (getLossOptions as jest.Mock).mockResolvedValue(createMockDropdownOptions(MetadataFieldName.LOSS_SEQUENCE));
    (getClaimantOptions as jest.Mock).mockResolvedValue(createMockDropdownOptions(MetadataFieldName.CLAIMANT));
    (getProducerOptions as jest.Mock).mockResolvedValue(createMockDropdownOptions(MetadataFieldName.PRODUCER_NUMBER));
    (getDocumentDescriptionOptions as jest.Mock).mockResolvedValue(createMockDropdownOptions(MetadataFieldName.DOCUMENT_DESCRIPTION));
    (getAssigneeOptions as jest.Mock).mockResolvedValue(createMockDropdownOptions(MetadataFieldName.ASSIGNED_TO));

    // Mock fetch API responses
    mockFetch('/api/documents/123', createMockDocument());
    mockFetch('/api/policies', createMockDropdownOptions(MetadataFieldName.POLICY_NUMBER));
    mockFetch('/api/policies/12345/losses', createMockDropdownOptions(MetadataFieldName.LOSS_SEQUENCE));
    mockFetch('/api/losses/1001/claimants', createMockDropdownOptions(MetadataFieldName.CLAIMANT));
    mockFetch('/api/producers', createMockDropdownOptions(MetadataFieldName.PRODUCER_NUMBER));
    mockFetch('/api/document-descriptions', createMockDropdownOptions(MetadataFieldName.DOCUMENT_DESCRIPTION));
    mockFetch('/api/assignees', createMockDropdownOptions(MetadataFieldName.ASSIGNED_TO));

    // Mock Adobe PDF viewer initialization
    window.AdobeDC = {
      View: jest.fn().mockReturnValue({
        previewFile: jest.fn(),
        registerCallback: jest.fn(),
        unload: jest.fn()
      }) as any
    };
  };

  // Helper function to render the DocumentViewer component with test configuration
  const renderDocumentViewer = (documentId: number) => {
    const onClose = jest.fn();

    return renderWithProviders(
      <MemoryRouter initialEntries={[`/documents/${documentId}`]}>
        <Routes>
          <Route path="/documents/:documentId" element={<DocumentViewer />} />
        </Routes>
      </MemoryRouter>,
      {
        route: `/documents/${documentId}`
      }
    );
  };

  // Helper function to create mock dropdown options for metadata fields
  const createMockDropdownOptions = (fieldName: MetadataFieldName) => {
    switch (fieldName) {
      case MetadataFieldName.POLICY_NUMBER:
        return [
          { id: 12345, label: 'PLCY-12345', value: 12345 },
          { id: 12346, label: 'PLCY-12346', value: 12346 },
          { id: 12347, label: 'PLCY-12347', value: 12347 }
        ];
      case MetadataFieldName.LOSS_SEQUENCE:
        return [
          { id: 1001, label: '1 - Vehicle Accident (03/15/2023)', value: 1001 },
          { id: 1002, label: '2 - Property Damage (04/01/2023)', value: 1002 }
        ];
      case MetadataFieldName.CLAIMANT:
        return [
          { id: 5001, label: '1 - John Smith', value: 5001 },
          { id: 5002, label: '2 - Jane Doe', value: 5002 }
        ];
      case MetadataFieldName.DOCUMENT_DESCRIPTION:
        return [
          { id: 'policy_renewal', label: 'Policy Renewal Notice', value: 'policy_renewal' },
          { id: 'claim_form', label: 'Claim Form', value: 'claim_form' }
        ];
      case MetadataFieldName.ASSIGNED_TO:
        return [
          { id: 3, label: 'Claims Department', value: 3, metadata: { type: 'group' } },
          { id: 4, label: 'John Doe', value: 4, metadata: { type: 'user' } }
        ];
      case MetadataFieldName.PRODUCER_NUMBER:
        return [
          { id: 456, label: 'AG-789456', value: 456 },
          { id: 457, label: 'AG-789457', value: 457 }
        ];
      default:
        return [];
    }
  };

  // Test case: should load document with editable metadata fields
  it('should load document with editable metadata fields', async () => {
    // Render DocumentViewer with a test document ID
    renderDocumentViewer(123);

    // Wait for document to load
    await waitFor(() => screen.getByText('Policy Number'));

    // Verify metadata panel is displayed
    expect(screen.getByText('Policy Number')).toBeInTheDocument();

    // Verify all metadata fields are rendered with correct initial values
    expect(screen.getByDisplayValue('PLCY-12345')).toBeInTheDocument();
    expect(screen.getByDisplayValue('1 - Vehicle Accident (03/15/2023)')).toBeInTheDocument();
    expect(screen.getByDisplayValue('1 - John Smith')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Policy Renewal Notice')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Claims Department')).toBeInTheDocument();
    expect(screen.getByDisplayValue('AG-789456')).toBeInTheDocument();

    // Verify fields are in editable state (not read-only)
    expect(screen.getByRole('combobox', { name: 'Policy Number' })).toBeEnabled();

    // Verify dropdown controls are interactive
    fireEvent.click(screen.getByRole('combobox', { name: 'Policy Number' }));
    await waitFor(() => screen.getByText('PLCY-12346'));
    expect(screen.getByText('PLCY-12346')).toBeInTheDocument();
  });

  // Test case: should update policy number and handle dependent fields
  it('should update policy number and handle dependent fields', async () => {
    // Render DocumentViewer with a test document ID
    renderDocumentViewer(123);

    // Wait for document to load
    await waitFor(() => screen.getByText('Policy Number'));

    // Click on Policy Number dropdown
    fireEvent.click(screen.getByRole('combobox', { name: 'Policy Number' }));

    // Select a different policy from the dropdown
    fireEvent.click(screen.getByText('PLCY-12346'));

    // Verify Loss Sequence field is cleared and disabled temporarily
    await waitFor(() => expect(screen.getByRole('combobox', { name: 'Loss Sequence' })).toBeDisabled());

    // Verify getLossOptions is called with the new policy ID
    await waitFor(() => expect(getLossOptions).toHaveBeenCalledWith(12346));

    // Verify Loss Sequence dropdown is re-enabled with new options
    await waitFor(() => expect(screen.getByRole('combobox', { name: 'Loss Sequence' })).toBeEnabled());

    // Verify 'Saving...' indicator appears during the update
    expect(screen.getByText('Saving...')).toBeInTheDocument();

    // Verify 'Saved' indicator appears after successful save
    await waitFor(() => expect(screen.getByText('Saved')).toBeInTheDocument());

    // Verify updateDocumentMetadata was called with correct parameters
    expect(updateDocumentMetadata).toHaveBeenCalledWith(123, {
      policy_id: 12346,
      loss_id: null,
      claimant_id: null,
      document_description: 'policy_renewal',
      assigned_to_id: 3,
      assigned_to_type: 'group',
      producer_id: 456
    });
  });

  // Test case: should update loss sequence and handle dependent claimant field
  it('should update loss sequence and handle dependent claimant field', async () => {
    // Render DocumentViewer with a test document ID
    renderDocumentViewer(123);

    // Wait for document to load
    await waitFor(() => screen.getByText('Loss Sequence'));

    // Click on Loss Sequence dropdown
    fireEvent.click(screen.getByRole('combobox', { name: 'Loss Sequence' }));

    // Select a different loss sequence from the dropdown
    fireEvent.click(screen.getByText('2 - Property Damage (04/01/2023)'));

    // Verify Claimant field is cleared and disabled temporarily
    await waitFor(() => expect(screen.getByRole('combobox', { name: 'Claimant' })).toBeDisabled());

    // Verify getClaimantOptions is called with the new loss ID
    await waitFor(() => expect(getClaimantOptions).toHaveBeenCalledWith(1002));

    // Verify Claimant dropdown is re-enabled with new options
    await waitFor(() => expect(screen.getByRole('combobox', { name: 'Claimant' })).toBeEnabled());

    // Verify 'Saving...' indicator appears during the update
    expect(screen.getByText('Saving...')).toBeInTheDocument();

    // Verify 'Saved' indicator appears after successful save
    await waitFor(() => expect(screen.getByText('Saved')).toBeInTheDocument());

    // Verify updateDocumentMetadata was called with correct parameters
    expect(updateDocumentMetadata).toHaveBeenCalledWith(123, {
      policy_id: 12345,
      loss_id: 1002,
      claimant_id: null,
      document_description: 'policy_renewal',
      assigned_to_id: 3,
      assigned_to_type: 'group',
      producer_id: 456
    });
  });

  // Test case: should handle type-ahead filtering in dropdown fields
  it('should handle type-ahead filtering in dropdown fields', async () => {
    // Render DocumentViewer with a test document ID
    renderDocumentViewer(123);

    // Wait for document to load
    await waitFor(() => screen.getByText('Policy Number'));

    // Click on Policy Number dropdown
    fireEvent.click(screen.getByRole('combobox', { name: 'Policy Number' }));

    // Type partial policy number in the input
    fireEvent.change(screen.getByRole('combobox', { name: 'Policy Number' }), { target: { value: 'PLCY-123' } });

    // Verify dropdown options are filtered based on input
    await waitFor(() => expect(screen.getByText('PLCY-12345')).toBeInTheDocument());
    expect(screen.queryByText('PLCY-12346')).toBeInTheDocument();

    // Select a matching policy from filtered results
    fireEvent.click(screen.getByText('PLCY-12345'));

    // Verify selected policy is displayed in the field
    expect(screen.getByDisplayValue('PLCY-12345')).toBeInTheDocument();

    // Verify updateDocumentMetadata was called with correct parameters
    expect(updateDocumentMetadata).toHaveBeenCalledWith(123, {
      policy_id: 12345,
      loss_id: 1001,
      claimant_id: 5001,
      document_description: 'policy_renewal',
      assigned_to_id: 3,
      assigned_to_type: 'group',
      producer_id: 456
    });
  });

  // Test case: should validate required fields and show error messages
  it('should validate required fields and show error messages', async () => {
    // Render DocumentViewer with a test document ID
    renderDocumentViewer(123);

    // Wait for document to load
    await waitFor(() => screen.getByText('Document Description'));

    // Clear a required field (e.g., Document Description)
    fireEvent.mouseDown(screen.getByRole('combobox', { name: 'Document Description' }));
    fireEvent.keyDown(screen.getByRole('combobox', { name: 'Document Description' }), { key: 'Backspace' });
    fireEvent.keyUp(screen.getByRole('combobox', { name: 'Document Description' }), { key: 'Backspace' });

    // Click outside the field to trigger validation
    fireEvent.blur(screen.getByRole('combobox', { name: 'Document Description' }));

    // Verify error message is displayed for the required field
    await waitFor(() => expect(screen.getByText('Document Description is required')).toBeInTheDocument());

    // Verify field is highlighted with error styling
    expect(screen.getByRole('combobox', { name: 'Document Description' })).toHaveClass('dropdown-control-error');

    // Verify 'Saved' indicator is not shown (validation failed)
    expect(screen.queryByText('Saved')).not.toBeInTheDocument();

    // Verify updateDocumentMetadata was not called
    expect(updateDocumentMetadata).not.toHaveBeenCalled();
  });

  // Test case: should handle field dependencies and disable dependent fields appropriately
  it('should handle field dependencies and disable dependent fields appropriately', async () => {
    // Render DocumentViewer with a test document ID with empty policy number
    renderDocumentViewer(123);

    // Wait for document to load
    await waitFor(() => screen.getByText('Policy Number'));

    // Verify Loss Sequence field is disabled
    expect(screen.getByRole('combobox', { name: 'Loss Sequence' })).toBeDisabled();

    // Verify Claimant field is disabled
    expect(screen.getByRole('combobox', { name: 'Claimant' })).toBeDisabled();

    // Enter a policy number
    fireEvent.click(screen.getByRole('combobox', { name: 'Policy Number' }));
    fireEvent.click(screen.getByText('PLCY-12345'));

    // Verify Loss Sequence field becomes enabled
    await waitFor(() => expect(screen.getByRole('combobox', { name: 'Loss Sequence' })).toBeEnabled());

    // Verify Claimant field remains disabled until loss is selected
    expect(screen.getByRole('combobox', { name: 'Claimant' })).toBeDisabled();

    // Select a loss sequence
    fireEvent.click(screen.getByRole('combobox', { name: 'Loss Sequence' }));
    fireEvent.click(screen.getByText('1 - Vehicle Accident (03/15/2023)'));

    // Verify Claimant field becomes enabled
    await waitFor(() => expect(screen.getByRole('combobox', { name: 'Claimant' })).toBeEnabled());
  });

  // Test case: should handle API errors during metadata updates
  it('should handle API errors during metadata updates', async () => {
    // Mock updateDocumentMetadata to return an error
    (updateDocumentMetadata as jest.Mock).mockRejectedValue(new Error('API Error'));

    // Render DocumentViewer with a test document ID
    renderDocumentViewer(123);

    // Wait for document to load
    await waitFor(() => screen.getByText('Policy Number'));

    // Update a metadata field
    fireEvent.click(screen.getByRole('combobox', { name: 'Policy Number' }));
    fireEvent.click(screen.getByText('PLCY-12346'));

    // Verify 'Saving...' indicator appears
    await waitFor(() => expect(screen.getByText('Saving...')).toBeInTheDocument());

    // Verify error message is displayed after failed save
    await waitFor(() => expect(screen.getByText('API Error')).toBeInTheDocument());

    // Verify field value remains changed in the UI
    expect(screen.getByDisplayValue('PLCY-12346')).toBeInTheDocument();

    // Mock updateDocumentMetadata to succeed again
    (updateDocumentMetadata as jest.Mock).mockResolvedValue(createMockDocument());

    // Make another change
    fireEvent.click(screen.getByRole('combobox', { name: 'Document Description' }));
    fireEvent.click(screen.getByText('Claim Form'));

    // Verify save succeeds and 'Saved' indicator appears
    await waitFor(() => expect(screen.getByText('Saved')).toBeInTheDocument());
  });

  // Test case: should handle concurrent edits to multiple fields
  it('should handle concurrent edits to multiple fields', async () => {
    // Render DocumentViewer with a test document ID
    renderDocumentViewer(123);

    // Wait for document to load
    await waitFor(() => screen.getByText('Policy Number'));

    // Update Policy Number field
    fireEvent.click(screen.getByRole('combobox', { name: 'Policy Number' }));
    fireEvent.click(screen.getByText('PLCY-12346'));

    // Immediately update Document Description field
    fireEvent.click(screen.getByRole('combobox', { name: 'Document Description' }));
    fireEvent.click(screen.getByText('Claim Form'));

    // Immediately update Assigned To field
    fireEvent.click(screen.getByRole('combobox', { name: 'Assigned To' }));
    fireEvent.click(screen.getByText('John Doe'));

    // Verify 'Saving...' indicator appears
    await waitFor(() => expect(screen.getByText('Saving...')).toBeInTheDocument());

    // Verify all field changes are included in the final updateDocumentMetadata call
    await waitFor(() => expect(updateDocumentMetadata).toHaveBeenCalledWith(123, {
      policy_id: 12346,
      loss_id: 1001,
      claimant_id: 5001,
      document_description: 'claim_form',
      assigned_to_id: 4,
      assigned_to_type: 'user',
      producer_id: 456
    }));

    // Verify 'Saved' indicator appears after successful save
    await waitFor(() => expect(screen.getByText('Saved')).toBeInTheDocument());

    // Verify all fields display their updated values
    expect(screen.getByDisplayValue('PLCY-12346')).toBeInTheDocument();
    expect(screen.getByDisplayValue('Claim Form')).toBeInTheDocument();
    expect(screen.getByDisplayValue('John Doe')).toBeInTheDocument();
  });
});
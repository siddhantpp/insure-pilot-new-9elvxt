import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import MetadataField from '../../../components/metadata/MetadataField';
import { MetadataFieldName } from '../../../models/metadata.types';
import { renderWithProviders, createMockDocument } from '../../../utils/testUtils';

// Mock the useMetadataForm hook
jest.mock('../../../hooks/useMetadataForm', () => ({
  useMetadataForm: jest.fn(() => ({
    values: {
      policyNumber: 'PLCY-12345',
      policyId: 12345,
      lossSequence: '1 - Vehicle Accident (03/15/2023)',
      lossId: 1001,
      claimant: '1 - John Smith',
      claimantId: 5001,
      documentDescription: 'Policy Renewal Notice',
      assignedTo: 'Claims Department',
      assignedToId: 3,
      assignedToType: 'group',
      producerNumber: 'AG-789456',
      producerId: 456
    },
    errors: {},
    options: {
      [MetadataFieldName.POLICY_NUMBER]: [
        { id: 12345, label: 'PLCY-12345', value: 12345 },
        { id: 12346, label: 'PLCY-12346', value: 12346 }
      ],
      [MetadataFieldName.LOSS_SEQUENCE]: [
        { id: 1001, label: '1 - Vehicle Accident (03/15/2023)', value: 1001 }
      ],
      [MetadataFieldName.DOCUMENT_DESCRIPTION]: [
        { id: 1, label: 'Policy Renewal Notice', value: 'Policy Renewal Notice' }
      ],
      [MetadataFieldName.CLAIMANT]: [],
      [MetadataFieldName.ASSIGNED_TO]: [],
      [MetadataFieldName.PRODUCER_NUMBER]: []
    },
    isLoading: {
      [MetadataFieldName.POLICY_NUMBER]: false,
      [MetadataFieldName.LOSS_SEQUENCE]: false,
      [MetadataFieldName.CLAIMANT]: false,
      [MetadataFieldName.DOCUMENT_DESCRIPTION]: false,
      [MetadataFieldName.ASSIGNED_TO]: false,
      [MetadataFieldName.PRODUCER_NUMBER]: false
    },
    handleFieldChange: jest.fn(),
    handleFieldBlur: jest.fn(),
    isFieldDisabled: jest.fn(() => false),
    getFieldOptions: jest.fn()
  }))
}));

// Mock useDocumentContext hook
jest.mock('../../../context/DocumentContext', () => ({
  useDocumentContext: jest.fn(() => ({
    state: {
      document: createMockDocument(),
      isLoading: false,
      error: null,
      activePanel: 'metadata',
      isSaving: false,
      saveError: null
    },
    loadDocument: jest.fn(),
    updateMetadata: jest.fn(),
    processDocument: jest.fn(),
    trashDocument: jest.fn(),
    setActivePanel: jest.fn()
  }))
}));

describe('MetadataField', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders correctly with required props', async () => {
    renderWithProviders(
      <MetadataField 
        fieldName={MetadataFieldName.POLICY_NUMBER} 
        label="Policy Number" 
      />
    );

    // Verify label is rendered
    expect(screen.getByText('Policy Number')).toBeInTheDocument();
    
    // Verify dropdown is rendered
    const dropdown = screen.getByTestId(`dropdown-${MetadataFieldName.POLICY_NUMBER}`);
    expect(dropdown).toBeInTheDocument();
    
    // Verify field is not marked as required
    const label = screen.getByTestId('form-label');
    expect(label).not.toHaveClass('form-label-required');
  });

  it('renders with required attribute when specified', async () => {
    renderWithProviders(
      <MetadataField 
        fieldName={MetadataFieldName.POLICY_NUMBER} 
        label="Policy Number" 
        required={true}
      />
    );
    
    // Verify field is marked as required
    const label = screen.getByTestId('form-label');
    expect(label).toHaveClass('form-label-required');
    
    // Verify aria-required attribute is set
    const input = screen.getByTestId(`dropdown-input-${MetadataFieldName.POLICY_NUMBER}`);
    await waitFor(() => {
      expect(input).toHaveAttribute('aria-required', 'true');
    });
  });

  it('displays validation errors', async () => {
    // Override the mock to include an error
    const useMetadataFormMock = require('../../../hooks/useMetadataForm').useMetadataForm;
    useMetadataFormMock.mockImplementationOnce(() => ({
      values: { policyNumber: '' },
      errors: { [MetadataFieldName.POLICY_NUMBER]: 'Policy Number is required' },
      options: { [MetadataFieldName.POLICY_NUMBER]: [] },
      isLoading: { [MetadataFieldName.POLICY_NUMBER]: false },
      handleFieldChange: jest.fn(),
      handleFieldBlur: jest.fn(),
      isFieldDisabled: jest.fn(() => false),
      getFieldOptions: jest.fn()
    }));

    renderWithProviders(
      <MetadataField 
        fieldName={MetadataFieldName.POLICY_NUMBER} 
        label="Policy Number" 
        required={true}
      />
    );
    
    // Verify error message is displayed
    expect(screen.getByText('Policy Number is required')).toBeInTheDocument();
    
    // Verify field has error styling
    const dropdown = screen.getByTestId(`dropdown-${MetadataFieldName.POLICY_NUMBER}`);
    expect(dropdown.parentElement).toHaveClass('has-error');
  });

  it('handles field value changes', async () => {
    const handleFieldChange = jest.fn();
    
    // Override the mock to include the custom handler
    const useMetadataFormMock = require('../../../hooks/useMetadataForm').useMetadataForm;
    useMetadataFormMock.mockImplementationOnce(() => ({
      values: { policyNumber: '' },
      errors: {},
      options: { 
        [MetadataFieldName.POLICY_NUMBER]: [
          { id: 12345, label: 'PLCY-12345', value: 12345 }
        ]
      },
      isLoading: { [MetadataFieldName.POLICY_NUMBER]: false },
      handleFieldChange,
      handleFieldBlur: jest.fn(),
      isFieldDisabled: jest.fn(() => false),
      getFieldOptions: jest.fn()
    }));

    renderWithProviders(
      <MetadataField 
        fieldName={MetadataFieldName.POLICY_NUMBER} 
        label="Policy Number" 
      />
    );
    
    // Open the dropdown
    const input = screen.getByTestId(`dropdown-input-${MetadataFieldName.POLICY_NUMBER}`);
    await userEvent.click(input);
    
    // Select an option
    const option = screen.getByText('PLCY-12345');
    await userEvent.click(option);
    
    // Verify handleFieldChange was called with the correct parameters
    expect(handleFieldChange).toHaveBeenCalledWith(
      MetadataFieldName.POLICY_NUMBER, 
      12345
    );
  });

  it('disables dependent fields when parent field is empty', async () => {
    // Override the mock to simulate dependency
    const useMetadataFormMock = require('../../../hooks/useMetadataForm').useMetadataForm;
    useMetadataFormMock.mockImplementationOnce(() => ({
      values: { policyNumber: '', policyId: null },
      errors: {},
      options: { [MetadataFieldName.LOSS_SEQUENCE]: [] },
      isLoading: { [MetadataFieldName.LOSS_SEQUENCE]: false },
      handleFieldChange: jest.fn(),
      handleFieldBlur: jest.fn(),
      isFieldDisabled: jest.fn((fieldName) => {
        // Disable LOSS_SEQUENCE when no policy is selected
        if (fieldName === MetadataFieldName.LOSS_SEQUENCE) return true;
        return false;
      }),
      getFieldOptions: jest.fn()
    }));

    renderWithProviders(
      <MetadataField 
        fieldName={MetadataFieldName.LOSS_SEQUENCE} 
        label="Loss Sequence" 
      />
    );
    
    // Verify dropdown is disabled
    const input = screen.getByTestId(`dropdown-input-${MetadataFieldName.LOSS_SEQUENCE}`);
    expect(input).toBeDisabled();
    
    // Verify placeholder text indicates selection dependency
    expect(input).toHaveAttribute('placeholder', 'Select a loss');
  });

  it('enables dependent fields when parent field has value', async () => {
    // Override the mock to simulate dependency with a value
    const useMetadataFormMock = require('../../../hooks/useMetadataForm').useMetadataForm;
    useMetadataFormMock.mockImplementationOnce(() => ({
      values: { 
        policyNumber: 'PLCY-12345', 
        policyId: 12345,
        lossSequence: ''
      },
      errors: {},
      options: { 
        [MetadataFieldName.LOSS_SEQUENCE]: [
          { id: 1001, label: '1 - Vehicle Accident', value: 1001 }
        ]
      },
      isLoading: { [MetadataFieldName.LOSS_SEQUENCE]: false },
      handleFieldChange: jest.fn(),
      handleFieldBlur: jest.fn(),
      isFieldDisabled: jest.fn(() => false),
      getFieldOptions: jest.fn()
    }));

    renderWithProviders(
      <MetadataField 
        fieldName={MetadataFieldName.LOSS_SEQUENCE} 
        label="Loss Sequence" 
      />
    );
    
    // Verify dropdown is enabled
    const input = screen.getByTestId(`dropdown-input-${MetadataFieldName.LOSS_SEQUENCE}`);
    expect(input).not.toBeDisabled();
    
    // Verify options are available
    await userEvent.click(input);
    expect(screen.getByText('1 - Vehicle Accident')).toBeInTheDocument();
  });

  it('renders in read-only mode when document is processed', async () => {
    // Override document context to mark document as processed
    const DocumentContext = require('../../../context/DocumentContext');
    DocumentContext.useDocumentContext.mockImplementationOnce(() => ({
      state: {
        document: createMockDocument({ isProcessed: true }),
        isLoading: false,
        error: null,
        activePanel: 'metadata',
        isSaving: false,
        saveError: null
      },
      loadDocument: jest.fn(),
      updateMetadata: jest.fn(),
      processDocument: jest.fn(),
      trashDocument: jest.fn(),
      setActivePanel: jest.fn()
    }));
    
    // Override the metadata form hook for read-only state
    const useMetadataFormMock = require('../../../hooks/useMetadataForm').useMetadataForm;
    useMetadataFormMock.mockImplementationOnce(() => ({
      values: { policyNumber: 'PLCY-12345' },
      errors: {},
      options: { [MetadataFieldName.POLICY_NUMBER]: [] },
      isLoading: { [MetadataFieldName.POLICY_NUMBER]: false },
      handleFieldChange: jest.fn(),
      handleFieldBlur: jest.fn(),
      isFieldDisabled: jest.fn(() => true),
      getFieldOptions: jest.fn()
    }));

    renderWithProviders(
      <MetadataField 
        fieldName={MetadataFieldName.POLICY_NUMBER} 
        label="Policy Number" 
      />
    );
    
    // Verify read-only field is rendered
    const readOnlyField = screen.getByTestId('read-only-field');
    expect(readOnlyField).toBeInTheDocument();
    
    // Verify field value is displayed
    expect(screen.getByText('PLCY-12345')).toBeInTheDocument();
    
    // Verify dropdown is not rendered
    const dropdown = screen.queryByTestId(`dropdown-${MetadataFieldName.POLICY_NUMBER}`);
    expect(dropdown).not.toBeInTheDocument();
  });

  it('shows loading state for dropdown options', async () => {
    // Override the mock to show loading state
    const useMetadataFormMock = require('../../../hooks/useMetadataForm').useMetadataForm;
    useMetadataFormMock.mockImplementationOnce(() => ({
      values: { policyNumber: '' },
      errors: {},
      options: { [MetadataFieldName.POLICY_NUMBER]: [] },
      isLoading: { [MetadataFieldName.POLICY_NUMBER]: true },
      handleFieldChange: jest.fn(),
      handleFieldBlur: jest.fn(),
      isFieldDisabled: jest.fn(() => false),
      getFieldOptions: jest.fn()
    }));

    renderWithProviders(
      <MetadataField 
        fieldName={MetadataFieldName.POLICY_NUMBER} 
        label="Policy Number" 
      />
    );
    
    // Verify loading indicator is shown
    const dropdown = screen.getByTestId(`dropdown-${MetadataFieldName.POLICY_NUMBER}`);
    expect(dropdown).toHaveClass('dropdown-control-loading');
    
    // Verify dropdown can still be interacted with
    const input = screen.getByTestId(`dropdown-input-${MetadataFieldName.POLICY_NUMBER}`);
    expect(input).not.toBeDisabled();
  });

  it('applies custom className when provided', async () => {
    renderWithProviders(
      <MetadataField 
        fieldName={MetadataFieldName.POLICY_NUMBER} 
        label="Policy Number" 
        className="custom-field-class"
      />
    );
    
    // Verify custom class is applied to container
    const formGroup = screen.getByTestId(`policyNumber-group`);
    expect(formGroup).toHaveClass('custom-field-class');
  });
});
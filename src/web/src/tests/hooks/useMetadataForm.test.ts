import React from 'react';
import { renderHook, act, waitFor } from '@testing-library/react-hooks'; // @testing-library/react-hooks ^8.0.1
import { useMetadataForm } from '../../hooks/useMetadataForm';
import { DocumentMetadata } from '../../models/document.types';
import { MetadataFieldName } from '../../models/metadata.types';
import { getMetadataOptions, clearDependentFields } from '../../services/metadataService';
import { renderWithProviders, createMockDocument, createMockContextValue } from '../../utils/testUtils';
import { DocumentContext } from '../../context/DocumentContext';

// Mock dependencies
jest.mock('../../services/metadataService');

describe('useMetadataForm', () => {
  // Setup variables for tests
  let mockDocument;
  let mockMetadata: DocumentMetadata;
  let mockUpdateMetadata;
  let mockContextValue;

  beforeEach(() => {
    // Reset all mocks
    jest.resetAllMocks();
    
    // Create mock document with metadata
    mockDocument = createMockDocument();
    mockMetadata = mockDocument.metadata;
    
    // Mock updateMetadata function for the DocumentContext
    mockUpdateMetadata = jest.fn().mockResolvedValue(mockDocument);
    mockContextValue = createMockContextValue('document', {
      updateMetadata: mockUpdateMetadata
    });
    
    // Mock getMetadataOptions function
    (getMetadataOptions as jest.Mock).mockResolvedValue([
      { id: 1, label: 'Option 1', value: 'option1' },
      { id: 2, label: 'Option 2', value: 'option2' }
    ]);
    
    // Mock clearDependentFields function
    (clearDependentFields as jest.Mock).mockImplementation((fields, setFieldValue) => {
      fields.forEach(field => setFieldValue(field, null));
    });
  });

  afterEach(() => {
    jest.resetAllMocks();
  });

  test('should initialize with provided values', () => {
    // Arrange
    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <DocumentContext.Provider value={mockContextValue}>
        {children}
      </DocumentContext.Provider>
    );
    
    // Act
    const { result } = renderHook(
      () => useMetadataForm(mockMetadata, false),
      { wrapper }
    );
    
    // Assert
    expect(result.current.values).toEqual(mockMetadata);
    expect(result.current.isValid).toBe(true);
    expect(Object.keys(result.current.touched).length).toBe(0);
    expect(Object.keys(result.current.errors).length).toBe(0);
  });

  test('should handle field changes', async () => {
    // Arrange
    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <DocumentContext.Provider value={mockContextValue}>
        {children}
      </DocumentContext.Provider>
    );
    
    // Act
    const { result } = renderHook(
      () => useMetadataForm(mockMetadata, false),
      { wrapper }
    );
    
    // Act - Change a field value
    act(() => {
      result.current.handleFieldChange(MetadataFieldName.POLICY_NUMBER, 'PLCY-54321');
    });
    
    // Assert - Check if field value was updated
    expect(result.current.values.policyNumber).toBe('PLCY-54321');
    expect(result.current.isSaving).toBe(true);
    
    // Wait for debounced save to complete
    await waitFor(() => {
      expect(mockUpdateMetadata).toHaveBeenCalled();
    });
  });

  test('should validate fields on blur', () => {
    // Arrange
    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <DocumentContext.Provider value={mockContextValue}>
        {children}
      </DocumentContext.Provider>
    );
    
    // Create metadata with empty required field
    const testMetadata = { ...mockMetadata, policyNumber: null };
    
    // Act
    const { result } = renderHook(
      () => useMetadataForm(testMetadata, false),
      { wrapper }
    );
    
    // Act - Trigger field blur
    act(() => {
      result.current.handleFieldBlur(MetadataFieldName.POLICY_NUMBER);
    });
    
    // Assert - Check if field was marked as touched and has error
    expect(result.current.touched[MetadataFieldName.POLICY_NUMBER]).toBe(true);
    expect(result.current.errors[MetadataFieldName.POLICY_NUMBER]).toBeTruthy();
  });

  test('should handle dependent fields', async () => {
    // Arrange
    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <DocumentContext.Provider value={mockContextValue}>
        {children}
      </DocumentContext.Provider>
    );
    
    // Act
    const { result } = renderHook(
      () => useMetadataForm(mockMetadata, false),
      { wrapper }
    );
    
    // Act - Change parent field (policy number)
    act(() => {
      result.current.handleFieldChange(MetadataFieldName.POLICY_NUMBER, 'PLCY-NEW');
    });
    
    // Assert - Check if clearDependentFields was called
    expect(clearDependentFields).toHaveBeenCalled();
    
    // Assert - Check if getMetadataOptions was called for dependent field
    expect(getMetadataOptions).toHaveBeenCalledWith(MetadataFieldName.LOSS_SEQUENCE, expect.anything());
    
    // Act - Change loss sequence field
    act(() => {
      result.current.handleFieldChange(MetadataFieldName.LOSS_SEQUENCE, '2 - New Loss');
    });
    
    // Assert - Check if getMetadataOptions was called for claimant field
    expect(getMetadataOptions).toHaveBeenCalledWith(MetadataFieldName.CLAIMANT, expect.anything());
    
    // Wait for async operations to complete
    await waitFor(() => {
      expect(result.current.values.lossSequence).toBe('2 - New Loss');
    });
  });

  test('should handle form submission', async () => {
    // Arrange
    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <DocumentContext.Provider value={mockContextValue}>
        {children}
      </DocumentContext.Provider>
    );
    
    // Act
    const { result } = renderHook(
      () => useMetadataForm(mockMetadata, false),
      { wrapper }
    );
    
    // Act - Submit the form
    let submitResult;
    await act(async () => {
      submitResult = await result.current.handleSubmit();
    });
    
    // Assert - Check if updateMetadata was called with correct data
    expect(mockUpdateMetadata).toHaveBeenCalled();
    expect(result.current.isSaving).toBe(false);
    expect(submitResult).toBe(true);
  });

  test('should handle submission errors', async () => {
    // Arrange
    const testError = new Error('Submission failed');
    mockUpdateMetadata.mockRejectedValueOnce(testError);
    
    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <DocumentContext.Provider value={mockContextValue}>
        {children}
      </DocumentContext.Provider>
    );
    
    // Act
    const { result } = renderHook(
      () => useMetadataForm(mockMetadata, false),
      { wrapper }
    );
    
    // Act - Submit the form with error
    let submitResult;
    await act(async () => {
      submitResult = await result.current.handleSubmit();
    });
    
    // Assert - Check if error was handled
    expect(result.current.saveError).toBe(testError.message);
    expect(result.current.isSaving).toBe(false);
    expect(submitResult).toBe(false);
  });

  test('should handle field options loading', async () => {
    // Arrange
    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <DocumentContext.Provider value={mockContextValue}>
        {children}
      </DocumentContext.Provider>
    );
    
    // Act
    const { result } = renderHook(
      () => useMetadataForm(mockMetadata, false),
      { wrapper }
    );
    
    // Act - Get field options
    let optionsResult;
    await act(async () => {
      optionsResult = await result.current.getFieldOptions(MetadataFieldName.POLICY_NUMBER);
    });
    
    // Assert - Check if options were loaded correctly
    expect(getMetadataOptions).toHaveBeenCalledWith(MetadataFieldName.POLICY_NUMBER, expect.anything());
    expect(result.current.options[MetadataFieldName.POLICY_NUMBER]).toEqual(
      expect.arrayContaining([expect.objectContaining({ id: 1 })])
    );
    expect(result.current.isLoading[MetadataFieldName.POLICY_NUMBER]).toBe(false);
  });

  test('should disable fields based on dependencies', () => {
    // Arrange
    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <DocumentContext.Provider value={mockContextValue}>
        {children}
      </DocumentContext.Provider>
    );
    
    // Create metadata with empty parent field
    const testMetadata = { ...mockMetadata, policyId: null };
    
    // Act
    const { result } = renderHook(
      () => useMetadataForm(testMetadata, false),
      { wrapper }
    );
    
    // Assert - Check if dependent field is disabled when parent field is empty
    expect(result.current.isFieldDisabled(MetadataFieldName.LOSS_SEQUENCE)).toBe(true);
    
    // Update the test metadata with parent field value
    const updatedMetadata = { ...testMetadata, policyId: 12345 };
    
    // Rerender with updated metadata
    const { result: updatedResult } = renderHook(
      () => useMetadataForm(updatedMetadata, false),
      { wrapper }
    );
    
    // Assert - Check if dependent field is now enabled when parent field has value
    expect(updatedResult.current.isFieldDisabled(MetadataFieldName.LOSS_SEQUENCE)).toBe(false);
  });

  test('should handle read-only mode', () => {
    // Arrange
    const wrapper = ({ children }: { children: React.ReactNode }) => (
      <DocumentContext.Provider value={mockContextValue}>
        {children}
      </DocumentContext.Provider>
    );
    
    // Act - Initialize in read-only mode
    const { result } = renderHook(
      () => useMetadataForm(mockMetadata, true),
      { wrapper }
    );
    
    // Act - Try to change a field
    act(() => {
      result.current.handleFieldChange(MetadataFieldName.POLICY_NUMBER, 'PLCY-CHANGED');
    });
    
    // Assert - Check that field value is not changed
    expect(result.current.values.policyNumber).not.toBe('PLCY-CHANGED');
    expect(result.current.values.policyNumber).toBe(mockMetadata.policyNumber);
    
    // Assert - Check that all fields are disabled in read-only mode
    expect(result.current.isFieldDisabled(MetadataFieldName.POLICY_NUMBER)).toBe(true);
    expect(result.current.isFieldDisabled(MetadataFieldName.DOCUMENT_DESCRIPTION)).toBe(true);
    
    // Assert - Verify updateMetadata is not called
    expect(mockUpdateMetadata).not.toHaveBeenCalled();
  });
});
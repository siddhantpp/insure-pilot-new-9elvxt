import { useState, useEffect, useCallback, useMemo } from 'react'; // react 18.x
import { DocumentMetadata } from '../models/document.types';
import { 
  MetadataFieldName, 
  MetadataFormState,
  MetadataFormActions,
  FIELD_DEPENDENCIES 
} from '../models/metadata.types';
import { validateMetadataField, validateMetadataForm } from '../lib/validators';
import { useFormValidation } from './useFormValidation';
import { useDocumentContext } from '../context/DocumentContext';
import { useDebounce, useDebouncedCallback } from './useDebounce';
import { 
  mapMetadataToApiRequest, 
  getInitialMetadata,
  shouldDisableField 
} from '../utils/metadataUtils';
import { 
  getMetadataOptions, 
  clearDependentFields 
} from '../services/metadataService';

/**
 * A custom hook that provides comprehensive metadata form management
 * for the Documents View feature
 * 
 * @param initialValues Initial document metadata values
 * @param isReadOnly Whether the form is in read-only mode
 * @returns Object containing form state and actions for metadata management
 */
export function useMetadataForm(
  initialValues: DocumentMetadata,
  isReadOnly: boolean
) {
  // Get document context for API operations
  const { updateMetadata } = useDocumentContext();
  
  // Initialize form validation state
  const form = useFormValidation(initialValues);
  
  // Saving state indicators
  const [isSaving, setIsSaving] = useState<boolean>(false);
  const [saveError, setSaveError] = useState<string | null>(null);
  
  // Options for dropdown fields
  const [options, setOptions] = useState<Record<MetadataFieldName, any[]>>({
    [MetadataFieldName.POLICY_NUMBER]: [],
    [MetadataFieldName.LOSS_SEQUENCE]: [],
    [MetadataFieldName.CLAIMANT]: [],
    [MetadataFieldName.DOCUMENT_DESCRIPTION]: [],
    [MetadataFieldName.ASSIGNED_TO]: [],
    [MetadataFieldName.PRODUCER_NUMBER]: []
  });
  
  // Loading state for dropdown fields
  const [isLoading, setIsLoading] = useState<Record<MetadataFieldName, boolean>>({
    [MetadataFieldName.POLICY_NUMBER]: false,
    [MetadataFieldName.LOSS_SEQUENCE]: false,
    [MetadataFieldName.CLAIMANT]: false,
    [MetadataFieldName.DOCUMENT_DESCRIPTION]: false,
    [MetadataFieldName.ASSIGNED_TO]: false,
    [MetadataFieldName.PRODUCER_NUMBER]: false
  });
  
  // Create debounced values for detecting changes to save
  const debouncedValues = useDebounce(form.values, 500);

  /**
   * Handles changes to metadata form fields
   * 
   * @param fieldName The name of the field being changed
   * @param value The new value for the field
   */
  const handleFieldChange = useCallback((fieldName: MetadataFieldName, value: any) => {
    // If form is read-only, don't allow changes
    if (isReadOnly) return;
    
    // Update the field value
    form.setFieldValue(fieldName, value);
    
    // Handle dependent fields
    const dependentFields = FIELD_DEPENDENCIES
      .filter(dep => dep.dependsOn === fieldName)
      .map(dep => dep.field);
    
    if (dependentFields.length > 0) {
      // Clear dependent field values
      clearDependentFields(dependentFields, form.setFieldValue);
      
      // Load options for direct dependent fields
      if (fieldName === MetadataFieldName.POLICY_NUMBER && value) {
        getFieldOptions(MetadataFieldName.LOSS_SEQUENCE);
      } else if (fieldName === MetadataFieldName.LOSS_SEQUENCE && value) {
        getFieldOptions(MetadataFieldName.CLAIMANT);
      } else if (fieldName === MetadataFieldName.PRODUCER_NUMBER) {
        // Filter policies by producer
        getFieldOptions(MetadataFieldName.POLICY_NUMBER);
      }
    }
    
    // Indicate saving is in progress
    setIsSaving(true);
  }, [isReadOnly, form.setFieldValue]);

  /**
   * Handles blur events on metadata form fields
   * 
   * @param fieldName The name of the field that lost focus
   */
  const handleFieldBlur = useCallback((fieldName: MetadataFieldName) => {
    form.setFieldTouched(fieldName, true);
    form.validateField(fieldName);
  }, [form]);

  /**
   * Handles form submission to save metadata changes
   * 
   * @returns Promise resolving to success status (true if saved, false otherwise)
   */
  const handleSubmit = useCallback(async (): Promise<boolean> => {
    // Validate all fields
    const isValid = form.validateForm();
    if (!isValid) return false;
    
    setIsSaving(true);
    setSaveError(null);
    
    try {
      // Transform metadata to API format
      const apiMetadata = mapMetadataToApiRequest(form.values);
      
      // Update metadata via document context
      const updatedDocument = await updateMetadata(apiMetadata);
      
      if (updatedDocument) {
        return true;
      }
      
      return false;
    } catch (error) {
      // Handle errors
      const errorMessage = error instanceof Error ? error.message : 'Failed to save metadata';
      setSaveError(errorMessage);
      return false;
    } finally {
      setIsSaving(false);
    }
  }, [form, updateMetadata]);

  /**
   * Retrieves options for dropdown fields
   * 
   * @param fieldName The field to get options for
   * @returns Promise resolving to array of dropdown options
   */
  const getFieldOptions = useCallback(async (fieldName: MetadataFieldName) => {
    // Set loading state for the field
    setIsLoading(prev => ({
      ...prev,
      [fieldName]: true
    }));
    
    try {
      // Get options based on field type and current values
      const fieldOptions = await getMetadataOptions(fieldName, form.values);
      
      // Update options state
      setOptions(prev => ({
        ...prev,
        [fieldName]: fieldOptions
      }));
      
      return fieldOptions;
    } catch (error) {
      console.error(`Error loading options for ${fieldName}:`, error);
      return [];
    } finally {
      setIsLoading(prev => ({
        ...prev,
        [fieldName]: false
      }));
    }
  }, [form.values]);

  /**
   * Determines if a field should be disabled based on dependencies
   * 
   * @param fieldName The field to check
   * @returns True if the field should be disabled
   */
  const isFieldDisabled = useCallback((fieldName: MetadataFieldName): boolean => {
    // If form is read-only, all fields are disabled
    if (isReadOnly) return true;
    
    // Check field dependencies
    return shouldDisableField(fieldName, form.values);
  }, [isReadOnly, form.values]);

  /**
   * Debounced function to save metadata changes
   */
  const debouncedSave = useDebouncedCallback(
    async (values: DocumentMetadata) => {
      // Skip save if form isn't valid
      if (!form.isValid) return;
      
      setIsSaving(true);
      setSaveError(null);
      
      try {
        // Transform metadata to API format
        const apiMetadata = mapMetadataToApiRequest(values);
        
        // Update metadata via document context
        await updateMetadata(apiMetadata);
      } catch (error) {
        // Handle errors
        const errorMessage = error instanceof Error ? error.message : 'Failed to save metadata';
        setSaveError(errorMessage);
      } finally {
        setIsSaving(false);
      }
    },
    500,
    [form.isValid, updateMetadata]
  );
  
  // Load initial dropdown options on mount
  useEffect(() => {
    // Load options for non-dependent fields
    getFieldOptions(MetadataFieldName.POLICY_NUMBER);
    getFieldOptions(MetadataFieldName.DOCUMENT_DESCRIPTION);
    getFieldOptions(MetadataFieldName.ASSIGNED_TO);
    getFieldOptions(MetadataFieldName.PRODUCER_NUMBER);
    
    // Load options for dependent fields if parent values exist
    if (form.values.policyId) {
      getFieldOptions(MetadataFieldName.LOSS_SEQUENCE);
    }
    
    if (form.values.lossId) {
      getFieldOptions(MetadataFieldName.CLAIMANT);
    }
  }, []);
  
  // Trigger debounced save when values change
  useEffect(() => {
    // Skip if in read-only mode
    if (isReadOnly) return;
    
    debouncedSave(debouncedValues);
  }, [debouncedValues, isReadOnly, debouncedSave]);
  
  // Calculate if form is dirty (has unsaved changes)
  const isDirty = useMemo(() => 
    Object.keys(form.touched).length > 0, 
    [form.touched]
  );
  
  return {
    // Form state
    values: form.values,
    errors: form.errors,
    touched: form.touched,
    isValid: form.isValid,
    isDirty,
    isSaving,
    saveError,
    isReadOnly,
    
    // Dropdown state
    options,
    isLoading,
    
    // Form actions
    handleFieldChange,
    handleFieldBlur,
    handleSubmit,
    getFieldOptions,
    isFieldDisabled,
    resetForm: form.resetForm
  };
}
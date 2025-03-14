import React, { useEffect, useMemo } from 'react'; // React 18.x
import FormField from '../form/FormField';
import DropdownControl from '../dropdown/DropdownControl';
import { MetadataFieldName, FIELD_DEPENDENCIES } from '../../models/metadata.types';
import { useDocumentContext } from '../../context/DocumentContext';
import { useMetadataForm } from '../../hooks/useMetadataForm';
import SaveIndicator from './SaveIndicator';

/**
 * Props interface for the MetadataField component
 */
export interface MetadataFieldProps {
  /** The name of the metadata field to render */
  fieldName: MetadataFieldName;
  
  /** The display label for the field */
  label: string;
  
  /** Whether the field is required */
  required?: boolean;
  
  /** Additional CSS class names */
  className?: string;
}

/**
 * A specialized component for rendering document metadata fields in the Documents View feature.
 * 
 * This component integrates form controls with metadata-specific functionality, handling
 * field dependencies, dynamic options loading, and read-only states based on document
 * processing status.
 * 
 * @param props - The component props
 * @returns A rendered metadata field with appropriate controls and state management
 */
const MetadataField: React.FC<MetadataFieldProps> = ({
  fieldName,
  label,
  required = false,
  className = '',
}) => {
  // Get document context to access document state
  const { state: { document } } = useDocumentContext();
  
  // Use metadata form hook to manage field state and actions
  const {
    values,
    errors,
    options,
    isLoading,
    handleFieldChange,
    handleFieldBlur,
    isFieldDisabled,
    getFieldOptions
  } = useMetadataForm(
    document?.metadata || {}, 
    document?.isProcessed || false
  );

  // Determine if field is read-only based on document processed state
  const isReadOnly = document?.isProcessed || false;
  
  // Get dependent field based on configuration
  const dependentField = useMemo(() => {
    const dependency = FIELD_DEPENDENCIES.find(dep => dep.field === fieldName);
    return dependency ? dependency.dependsOn : null;
  }, [fieldName]);
  
  // Get dependent field value (if this field depends on another)
  const dependentFieldValue = dependentField ? 
    values[dependentField as keyof typeof values] : 
    null;
  
  // Load field options when dependent field value changes
  useEffect(() => {
    if (dependentField && dependentFieldValue) {
      getFieldOptions(fieldName);
    }
  }, [fieldName, dependentField, dependentFieldValue, getFieldOptions]);
  
  // Get placeholder text based on field type
  const getPlaceholder = (field: MetadataFieldName): string => {
    switch (field) {
      case MetadataFieldName.POLICY_NUMBER:
        return 'Select a policy';
      case MetadataFieldName.LOSS_SEQUENCE:
        return 'Select a loss';
      case MetadataFieldName.CLAIMANT:
        return 'Select a claimant';
      case MetadataFieldName.DOCUMENT_DESCRIPTION:
        return 'Select a description';
      case MetadataFieldName.ASSIGNED_TO:
        return 'Select assignee';
      case MetadataFieldName.PRODUCER_NUMBER:
        return 'Select a producer';
      default:
        return 'Select...';
    }
  };
  
  return (
    <FormField
      id={fieldName}
      name={fieldName}
      label={label}
      required={required}
      error={errors[fieldName]}
      className={className}
      disabled={isFieldDisabled(fieldName)}
      readOnly={isReadOnly}
      value={values[fieldName as keyof typeof values]}
    >
      <DropdownControl
        name={fieldName}
        label={label} 
        value={values[fieldName as keyof typeof values]}
        options={options[fieldName] || []}
        onChange={(value) => handleFieldChange(fieldName, value)}
        onBlur={() => handleFieldBlur(fieldName)}
        error={errors[fieldName]}
        disabled={isFieldDisabled(fieldName)}
        placeholder={getPlaceholder(fieldName)}
        dependsOn={dependentField}
        isLoading={isLoading[fieldName]}
        isReadOnly={isReadOnly}
      />
    </FormField>
  );
};

export default MetadataField;
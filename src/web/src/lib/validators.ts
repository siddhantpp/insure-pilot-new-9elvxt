import { DocumentMetadata } from '../models/document.types';
import { MetadataFieldName } from '../models/metadata.types';
import { FIELD_DEPENDENCIES } from '../models/dropdown.types';

/**
 * Constants for validation error messages to maintain consistency throughout the app
 */
export const ERROR_MESSAGES = {
  REQUIRED: 'This field is required',
  INVALID_POLICY_NUMBER: 'Please enter a valid policy number',
  INVALID_LOSS_SEQUENCE: 'Please select a valid loss sequence',
  POLICY_REQUIRED_FOR_LOSS: 'Please select a policy before selecting a loss sequence',
  INVALID_CLAIMANT: 'Please select a valid claimant',
  LOSS_REQUIRED_FOR_CLAIMANT: 'Please select a loss sequence before selecting a claimant',
  INVALID_DOCUMENT_DESCRIPTION: 'Please select a valid document description',
  INVALID_ASSIGNED_TO: 'Please select a valid assignee',
  INVALID_PRODUCER_NUMBER: 'Please enter a valid producer number'
};

/**
 * Validates that a field has a non-empty value
 * @param value The value to check
 * @returns True if the value is non-empty, false otherwise
 */
export function isRequired(value: any): boolean {
  if (value === null || value === undefined) {
    return false;
  }
  
  if (typeof value === 'string') {
    return value.trim() !== '';
  }
  
  return true;
}

/**
 * Validates a policy number field value
 * @param value The policy number value
 * @returns Error message if invalid, null if valid
 */
export function validatePolicyNumber(value: string | null): string | null {
  // Check if required
  if (!isRequired(value)) {
    return ERROR_MESSAGES.REQUIRED;
  }
  
  // Policy number validation with pattern like PLCY-12345
  if (value && !/^[A-Z]+-\d+$/i.test(value)) {
    return ERROR_MESSAGES.INVALID_POLICY_NUMBER;
  }
  
  return null;
}

/**
 * Validates a loss sequence field value
 * @param value The loss sequence value
 * @param formValues The complete form values for dependency checking
 * @returns Error message if invalid, null if valid
 */
export function validateLossSequence(value: string | null, formValues: DocumentMetadata): string | null {
  // Check dependency on policy number
  if (!formValues.policyId) {
    return ERROR_MESSAGES.POLICY_REQUIRED_FOR_LOSS;
  }
  
  // Loss sequence is not strictly required even if policy is selected
  if (!isRequired(value)) {
    return null;
  }
  
  // Loss sequence validation - typically a formatted string like "1 - Vehicle Accident"
  if (value && !/^\d+\s+-\s+.+/.test(value)) {
    return ERROR_MESSAGES.INVALID_LOSS_SEQUENCE;
  }
  
  return null;
}

/**
 * Validates a claimant field value
 * @param value The claimant value
 * @param formValues The complete form values for dependency checking
 * @returns Error message if invalid, null if valid
 */
export function validateClaimant(value: string | null, formValues: DocumentMetadata): string | null {
  // Check dependency on loss sequence
  if (!formValues.lossId) {
    return ERROR_MESSAGES.LOSS_REQUIRED_FOR_CLAIMANT;
  }
  
  // Claimant is not strictly required even if loss is selected
  if (!isRequired(value)) {
    return null;
  }
  
  // Claimant validation - typically a formatted string like "1 - John Smith"
  if (value && !/^\d+\s+-\s+.+/.test(value)) {
    return ERROR_MESSAGES.INVALID_CLAIMANT;
  }
  
  return null;
}

/**
 * Validates a document description field value
 * @param value The document description value
 * @returns Error message if invalid, null if valid
 */
export function validateDocumentDescription(value: string | null): string | null {
  // Check if required
  if (!isRequired(value)) {
    return ERROR_MESSAGES.REQUIRED;
  }
  
  // For document description, typically just checking that it's not empty is sufficient
  
  return null;
}

/**
 * Validates an assigned to field value
 * @param value The assigned to value
 * @returns Error message if invalid, null if valid
 */
export function validateAssignedTo(value: string | null): string | null {
  // Assigned To is not required
  if (!isRequired(value)) {
    return null;
  }
  
  // In a real implementation, might validate against a list of valid assignees
  
  return null;
}

/**
 * Validates a producer number field value
 * @param value The producer number value
 * @returns Error message if invalid, null if valid
 */
export function validateProducerNumber(value: string | null): string | null {
  // Producer Number is not required
  if (!isRequired(value)) {
    return null;
  }
  
  // Producer number validation with pattern like AG-123456
  if (value && !/^[A-Z]+-\d+$/i.test(value)) {
    return ERROR_MESSAGES.INVALID_PRODUCER_NUMBER;
  }
  
  return null;
}

/**
 * Validates a field that depends on another field
 * @param fieldName The field name to validate
 * @param value The field value
 * @param formValues The complete form values for dependency checking
 * @returns Error message if dependency validation fails, null if valid
 */
export function validateDependentField(
  fieldName: MetadataFieldName,
  value: any,
  formValues: DocumentMetadata
): string | null {
  // Find dependency for this field
  const dependency = FIELD_DEPENDENCIES.find(dep => dep.field === fieldName);
  
  if (!dependency) {
    // No dependency found, no validation needed
    return null;
  }
  
  // Get parent field value based on dependency definition
  const parentValuePath = dependency.parentValuePath as keyof DocumentMetadata;
  const parentValue = formValues[parentValuePath];
  
  // If parent value is missing but this field has a value, it's invalid
  if (!parentValue && isRequired(value)) {
    switch (fieldName) {
      case MetadataFieldName.LOSS_SEQUENCE:
        return ERROR_MESSAGES.POLICY_REQUIRED_FOR_LOSS;
      case MetadataFieldName.CLAIMANT:
        return ERROR_MESSAGES.LOSS_REQUIRED_FOR_CLAIMANT;
      case MetadataFieldName.POLICY_NUMBER:
        // Special case for Policy Number depending on Producer Number
        // This is a "soft" dependency - filter options but don't block
        return null;
      default:
        return `Please provide ${dependency.dependsOn} first`;
    }
  }
  
  return null;
}

/**
 * Returns the appropriate validator function for a given field
 * @param fieldName The field name to get validator for
 * @returns Validator function for the specified field
 */
export function getValidatorForField(
  fieldName: MetadataFieldName
): (value: any, formValues: DocumentMetadata) => string | null {
  switch (fieldName) {
    case MetadataFieldName.POLICY_NUMBER:
      return (value: string | null, formValues: DocumentMetadata) => 
        validatePolicyNumber(value);
    case MetadataFieldName.LOSS_SEQUENCE:
      return validateLossSequence;
    case MetadataFieldName.CLAIMANT:
      return validateClaimant;
    case MetadataFieldName.DOCUMENT_DESCRIPTION:
      return (value: string | null, formValues: DocumentMetadata) => 
        validateDocumentDescription(value);
    case MetadataFieldName.ASSIGNED_TO:
      return (value: string | null, formValues: DocumentMetadata) => 
        validateAssignedTo(value);
    case MetadataFieldName.PRODUCER_NUMBER:
      return (value: string | null, formValues: DocumentMetadata) => 
        validateProducerNumber(value);
    default:
      // Default validator that always passes
      return () => null;
  }
}

/**
 * Validates a single metadata field
 * @param fieldName The field name to validate
 * @param value The field value
 * @param formValues The complete form values for context
 * @returns Error message if validation fails, null if valid
 */
export function validateMetadataField(
  fieldName: MetadataFieldName,
  value: any,
  formValues: DocumentMetadata
): string | null {
  // First check dependency validation
  const dependencyError = validateDependentField(fieldName, value, formValues);
  if (dependencyError) {
    return dependencyError;
  }
  
  // Get the appropriate validator for this field
  const validator = getValidatorForField(fieldName);
  
  // Run the validator and return result
  return validator(value, formValues);
}

/**
 * Validates all fields in a metadata form
 * @param formValues The complete form values to validate
 * @returns Object with field names as keys and error messages as values
 */
export function validateMetadataForm(
  formValues: DocumentMetadata
): Record<string, string | null> {
  const errors: Record<string, string | null> = {};
  
  // Validate each field in the metadata form
  Object.values(MetadataFieldName).forEach(fieldName => {
    const value = formValues[fieldName as keyof DocumentMetadata];
    const error = validateMetadataField(
      fieldName as MetadataFieldName, 
      value, 
      formValues
    );
    
    if (error) {
      errors[fieldName] = error;
    }
  });
  
  return errors;
}
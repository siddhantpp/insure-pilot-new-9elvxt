import { DocumentMetadata } from '../models/document.types';
import { DocumentMetadataResponse, DocumentMetadataUpdateRequest } from '../models/api.types';
import { 
  MetadataFieldName, 
  MetadataFieldConfig, 
  FieldDependency, 
  ValidationRule,
  FIELD_DEPENDENCIES,
  METADATA_FIELD_CONFIGS 
} from '../models/metadata.types';
import { isValidationError, getValidationErrors } from './errorUtils';

/**
 * Transforms frontend metadata model to API request format with snake_case properties
 * 
 * @param metadata Frontend metadata object
 * @returns Transformed request object with API-compatible format
 */
export function mapMetadataToApiRequest(metadata: DocumentMetadata): DocumentMetadataUpdateRequest {
  return {
    policy_id: metadata.policyId,
    loss_id: metadata.lossId,
    claimant_id: metadata.claimantId,
    document_description: metadata.documentDescription,
    assigned_to_id: metadata.assignedToId,
    assigned_to_type: metadata.assignedToType,
    producer_id: metadata.producerId
  };
}

/**
 * Transforms API response format to frontend metadata model with camelCase properties
 * 
 * @param response API response object
 * @returns Transformed metadata object with frontend-compatible format
 */
export function mapApiResponseToMetadata(response: DocumentMetadataResponse): DocumentMetadata {
  return {
    policyNumber: response.policy_number,
    policyId: response.policy_id,
    lossSequence: response.loss_sequence,
    lossId: response.loss_id,
    claimant: response.claimant,
    claimantId: response.claimant_id,
    documentDescription: response.document_description,
    assignedTo: response.assigned_to,
    assignedToId: response.assigned_to_id,
    assignedToType: response.assigned_to_type,
    producerNumber: response.producer_number,
    producerId: response.producer_id
  };
}

/**
 * Gets the dependency configuration for a specific field
 * 
 * @param fieldName The field name to get dependency for
 * @returns The dependency configuration or null if no dependency exists
 */
export function getFieldDependency(fieldName: MetadataFieldName): FieldDependency | null {
  const dependency = FIELD_DEPENDENCIES.find(dep => dep.field === fieldName);
  return dependency || null;
}

/**
 * Determines if a field should be disabled based on its dependencies
 * 
 * @param fieldName The field name to check
 * @param values Current metadata values
 * @returns True if the field should be disabled
 */
export function shouldDisableField(fieldName: MetadataFieldName, values: DocumentMetadata): boolean {
  const dependency = getFieldDependency(fieldName);
  
  if (!dependency) {
    return false;
  }
  
  // Get the parent field value path
  const parentField = dependency.dependsOn;
  const parentValuePath = dependency.parentValuePath;
  
  // Get the parent value
  const parentValue = values[parentValuePath as keyof DocumentMetadata];
  
  // If parent value is null or undefined, disable this field
  return parentValue === null || parentValue === undefined;
}

/**
 * Validates a single metadata field based on field configuration and current values
 * 
 * @param fieldName The field name to validate
 * @param value The field value to validate
 * @param values All metadata values for context
 * @returns Error message if validation fails, null if valid
 */
export function validateMetadataField(fieldName: MetadataFieldName, value: any, values: DocumentMetadata): string | null {
  // Find the field config
  const fieldConfig = METADATA_FIELD_CONFIGS.find(config => config.name === fieldName);
  
  if (!fieldConfig) {
    return null; // No config found, assume valid
  }
  
  // If the field is not required and the value is empty, it's valid
  if (!fieldConfig.required && (value === null || value === undefined || value === '')) {
    return null;
  }
  
  // If the field is required and the value is empty, it's invalid
  if (fieldConfig.required && (value === null || value === undefined || value === '')) {
    return 'This field is required';
  }
  
  // Check dependencies
  const dependency = getFieldDependency(fieldName);
  
  if (dependency) {
    const parentFieldValue = values[dependency.parentValuePath as keyof DocumentMetadata];
    
    // If the field depends on another field that has no value, it's valid
    if (parentFieldValue === null || parentFieldValue === undefined) {
      return null;
    }
  }
  
  // Apply validation rules
  if (fieldConfig.validation) {
    for (const rule of fieldConfig.validation) {
      switch (rule.type) {
        case 'required':
          if (value === null || value === undefined || value === '') {
            return rule.message;
          }
          break;
        case 'dependency':
          const parentFieldName = rule.params.dependsOn;
          const parentFieldValue = values[parentFieldName as keyof DocumentMetadata];
          
          if (value && (parentFieldValue === null || parentFieldValue === undefined)) {
            return rule.message;
          }
          break;
        // Add more validation types as needed
      }
    }
  }
  
  return null; // All validations passed
}

/**
 * Validates all metadata fields and returns validation errors
 * 
 * @param metadata The metadata object to validate
 * @returns Object with field names as keys and error messages as values
 */
export function validateMetadata(metadata: DocumentMetadata): Record<string, string> {
  const errors: Record<string, string> = {};
  
  // Check each field defined in MetadataFieldName enum
  Object.values(MetadataFieldName).forEach(fieldName => {
    const value = metadata[fieldName as keyof DocumentMetadata];
    const error = validateMetadataField(fieldName, value, metadata);
    
    if (error) {
      errors[fieldName] = error;
    }
  });
  
  return errors;
}

/**
 * Creates an initial metadata object with default values
 * 
 * @returns Initial metadata object
 */
export function getInitialMetadata(): DocumentMetadata {
  return {
    policyNumber: null,
    policyId: null,
    lossSequence: null,
    lossId: null,
    claimant: null,
    claimantId: null,
    documentDescription: null,
    assignedTo: null,
    assignedToId: null,
    assignedToType: null,
    producerNumber: null,
    producerId: null
  };
}

// Export configurations from metadata.types.ts
export { METADATA_FIELD_CONFIGS, FIELD_DEPENDENCIES };
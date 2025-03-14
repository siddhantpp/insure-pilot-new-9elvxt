import { DocumentMetadata } from './document.types';
import { DropdownFieldType } from './dropdown.types';
import { DocumentMetadataUpdateRequest, DocumentMetadataResponse } from './api.types';

/**
 * Enum for metadata field names used in the form
 */
export enum MetadataFieldName {
  POLICY_NUMBER = 'policyNumber',
  LOSS_SEQUENCE = 'lossSequence',
  CLAIMANT = 'claimant',
  DOCUMENT_DESCRIPTION = 'documentDescription',
  ASSIGNED_TO = 'assignedTo',
  PRODUCER_NUMBER = 'producerNumber'
}

/**
 * Interface for field validation rules
 */
export interface ValidationRule {
  type: string;      // Type of validation rule (required, format, dependency, etc.)
  message: string;   // Error message to display if validation fails
  params: Record<string, any>; // Parameters for validation rule
}

/**
 * Interface for metadata field configuration including validation and dependencies
 */
export interface MetadataFieldConfig {
  name: MetadataFieldName;              // Field name identifier
  label: string;                        // Display label for the field
  fieldType: DropdownFieldType;         // Type of dropdown field
  placeholder: string;                  // Placeholder text when empty
  required: boolean;                    // Whether field is required
  dependsOn: MetadataFieldName | null;  // Field this depends on, if any
  order: number;                        // Display order in the form
  validation: ValidationRule[];         // Validation rules for this field
}

/**
 * Interface for defining field dependencies
 */
export interface FieldDependency {
  field: MetadataFieldName;            // Dependent field
  dependsOn: MetadataFieldName;        // Parent field it depends on
  parentValuePath: string;             // Path to extract value from parent
}

/**
 * Interface for metadata form state
 */
export interface MetadataFormState {
  values: DocumentMetadata;              // Current form values
  errors: Record<string, string>;        // Field-level error messages
  touched: Record<string, boolean>;      // Fields that have been interacted with
  isValid: boolean;                      // Whether the form is currently valid
  isDirty: boolean;                      // Whether the form has unsaved changes
  isSubmitting: boolean;                 // Whether the form is currently submitting
  submitError: string | null;            // Form-level submission error
  isReadOnly: boolean;                   // Whether the form is in read-only mode
}

/**
 * Interface for metadata form actions
 */
export interface MetadataFormActions {
  setFieldValue: (field: string, value: any) => void;
  setFieldTouched: (field: string, touched?: boolean) => void;
  setFieldError: (field: string, error: string | null) => void;
  validateField: (field: string) => boolean;
  validateForm: () => boolean;
  submitForm: () => Promise<boolean>;
  resetForm: () => void;
}

/**
 * Interface for metadata form context combining state and actions
 */
export interface MetadataFormContext {
  state: MetadataFormState;
  actions: MetadataFormActions;
}

/**
 * Constant array defining field dependencies for metadata fields
 * These dependencies define how fields relate to each other
 */
export const FIELD_DEPENDENCIES: FieldDependency[] = [
  {
    field: MetadataFieldName.LOSS_SEQUENCE,
    dependsOn: MetadataFieldName.POLICY_NUMBER,
    parentValuePath: 'policyId'
  },
  {
    field: MetadataFieldName.CLAIMANT,
    dependsOn: MetadataFieldName.LOSS_SEQUENCE,
    parentValuePath: 'lossId'
  },
  {
    field: MetadataFieldName.POLICY_NUMBER,
    dependsOn: MetadataFieldName.PRODUCER_NUMBER,
    parentValuePath: 'producerId'
  }
];

/**
 * Constant array of metadata field configurations used to render and validate form fields
 */
export const METADATA_FIELD_CONFIGS: MetadataFieldConfig[] = [
  {
    name: MetadataFieldName.POLICY_NUMBER,
    label: 'Policy Number',
    fieldType: DropdownFieldType.POLICY_NUMBER,
    placeholder: 'Select a policy',
    required: true,
    dependsOn: null,
    order: 1,
    validation: [
      {
        type: 'required',
        message: 'Policy Number is required',
        params: {}
      }
    ]
  },
  {
    name: MetadataFieldName.LOSS_SEQUENCE,
    label: 'Loss Sequence',
    fieldType: DropdownFieldType.LOSS_SEQUENCE,
    placeholder: 'Select a loss',
    required: false,
    dependsOn: MetadataFieldName.POLICY_NUMBER,
    order: 2,
    validation: [
      {
        type: 'dependency',
        message: 'Please select a Policy Number first',
        params: {
          dependsOn: MetadataFieldName.POLICY_NUMBER
        }
      }
    ]
  },
  {
    name: MetadataFieldName.CLAIMANT,
    label: 'Claimant',
    fieldType: DropdownFieldType.CLAIMANT,
    placeholder: 'Select a claimant',
    required: false,
    dependsOn: MetadataFieldName.LOSS_SEQUENCE,
    order: 3,
    validation: [
      {
        type: 'dependency',
        message: 'Please select a Loss Sequence first',
        params: {
          dependsOn: MetadataFieldName.LOSS_SEQUENCE
        }
      }
    ]
  },
  {
    name: MetadataFieldName.DOCUMENT_DESCRIPTION,
    label: 'Document Description',
    fieldType: DropdownFieldType.DOCUMENT_DESCRIPTION,
    placeholder: 'Select a description',
    required: true,
    dependsOn: null,
    order: 4,
    validation: [
      {
        type: 'required',
        message: 'Document Description is required',
        params: {}
      }
    ]
  },
  {
    name: MetadataFieldName.ASSIGNED_TO,
    label: 'Assigned To',
    fieldType: DropdownFieldType.ASSIGNED_TO,
    placeholder: 'Select assignee',
    required: false,
    dependsOn: null,
    order: 5,
    validation: []
  },
  {
    name: MetadataFieldName.PRODUCER_NUMBER,
    label: 'Producer Number',
    fieldType: DropdownFieldType.PRODUCER_NUMBER,
    placeholder: 'Select a producer',
    required: false,
    dependsOn: null,
    order: 6,
    validation: []
  }
];

/**
 * Maps frontend metadata model to API request format
 * Converts camelCase properties to snake_case for API compatibility
 * 
 * @param metadata Frontend metadata object
 * @returns Transformed request object for API submission
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
 * Maps API response format to frontend metadata model
 * Converts snake_case properties to camelCase for frontend compatibility
 * 
 * @param response API response object
 * @returns Transformed metadata object for frontend use
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
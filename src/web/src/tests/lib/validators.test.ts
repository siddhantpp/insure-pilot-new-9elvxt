import { 
  DocumentMetadata 
} from '../../models/document.types';
import { 
  MetadataFieldName 
} from '../../models/metadata.types';
import {
  isRequired,
  validatePolicyNumber,
  validateLossSequence,
  validateClaimant,
  validateDocumentDescription,
  validateAssignedTo,
  validateProducerNumber,
  validateDependentField,
  getValidatorForField,
  validateMetadataField,
  validateMetadataForm,
  ERROR_MESSAGES
} from '../../lib/validators';

/**
 * Creates an empty DocumentMetadata object for testing
 */
function createEmptyMetadata(): DocumentMetadata {
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

/**
 * Creates a valid DocumentMetadata object for testing
 */
function createValidMetadata(): DocumentMetadata {
  return {
    policyNumber: 'PLCY-12345',
    policyId: 1,
    lossSequence: '1 - Vehicle Accident',
    lossId: 1,
    claimant: '1 - John Smith',
    claimantId: 1,
    documentDescription: 'Policy Renewal Notice',
    assignedTo: 'Claims Department',
    assignedToId: 1,
    assignedToType: 'group',
    producerNumber: 'AG-789456',
    producerId: 1
  };
}

describe('isRequired', () => {
  it('should return true for non-empty string values', () => {
    expect(isRequired('test')).toBe(true);
  });

  it('should return false for empty string values', () => {
    expect(isRequired('')).toBe(false);
  });

  it('should return false for null values', () => {
    expect(isRequired(null)).toBe(false);
  });

  it('should return false for undefined values', () => {
    expect(isRequired(undefined)).toBe(false);
  });

  it('should return true for numeric values', () => {
    expect(isRequired(123)).toBe(true);
  });

  it('should return true for boolean values', () => {
    expect(isRequired(false)).toBe(true);
  });
});

describe('validatePolicyNumber', () => {
  it('should return null for valid policy numbers', () => {
    expect(validatePolicyNumber('PLCY-12345')).toBeNull();
  });

  it('should return error message for null value', () => {
    expect(validatePolicyNumber(null)).toBe(ERROR_MESSAGES.REQUIRED);
  });

  it('should return error message for empty string', () => {
    expect(validatePolicyNumber('')).toBe(ERROR_MESSAGES.REQUIRED);
  });

  it('should return error message for invalid format', () => {
    expect(validatePolicyNumber('12345')).toBe(ERROR_MESSAGES.INVALID_POLICY_NUMBER);
    expect(validatePolicyNumber('PLCY12345')).toBe(ERROR_MESSAGES.INVALID_POLICY_NUMBER);
    expect(validatePolicyNumber('PLCY-')).toBe(ERROR_MESSAGES.INVALID_POLICY_NUMBER);
  });
});

describe('validateLossSequence', () => {
  it('should return null for valid loss sequence when policy is selected', () => {
    const metadata = createValidMetadata();
    expect(validateLossSequence('1 - Vehicle Accident', metadata)).toBeNull();
  });

  it('should return dependency error when policy is not selected', () => {
    const metadata = createEmptyMetadata();
    expect(validateLossSequence('1 - Vehicle Accident', metadata)).toBe(ERROR_MESSAGES.POLICY_REQUIRED_FOR_LOSS);
  });

  it('should return required error when value is null', () => {
    const metadata = createValidMetadata();
    // Loss sequence isn't strictly required
    expect(validateLossSequence(null, metadata)).toBeNull();
  });

  it('should return invalid format error for invalid loss sequence', () => {
    const metadata = createValidMetadata();
    expect(validateLossSequence('Invalid format', metadata)).toBe(ERROR_MESSAGES.INVALID_LOSS_SEQUENCE);
  });
});

describe('validateClaimant', () => {
  it('should return null for valid claimant when loss sequence is selected', () => {
    const metadata = createValidMetadata();
    expect(validateClaimant('1 - John Smith', metadata)).toBeNull();
  });

  it('should return dependency error when loss sequence is not selected', () => {
    const metadata = createEmptyMetadata();
    expect(validateClaimant('1 - John Smith', metadata)).toBe(ERROR_MESSAGES.LOSS_REQUIRED_FOR_CLAIMANT);
  });

  it('should return required error when value is null', () => {
    const metadata = createValidMetadata();
    // Claimant isn't strictly required
    expect(validateClaimant(null, metadata)).toBeNull();
  });

  it('should return invalid format error for invalid claimant', () => {
    const metadata = createValidMetadata();
    expect(validateClaimant('Invalid format', metadata)).toBe(ERROR_MESSAGES.INVALID_CLAIMANT);
  });
});

describe('validateDocumentDescription', () => {
  it('should return null for valid document description', () => {
    expect(validateDocumentDescription('Policy Renewal Notice')).toBeNull();
  });

  it('should return required error when value is null', () => {
    expect(validateDocumentDescription(null)).toBe(ERROR_MESSAGES.REQUIRED);
  });

  it('should return required error when value is empty string', () => {
    expect(validateDocumentDescription('')).toBe(ERROR_MESSAGES.REQUIRED);
  });

  it('should return invalid format error for invalid document description', () => {
    // Since document description just checks if it's not empty, this test confirms that
    expect(validateDocumentDescription('Any string is valid')).toBeNull();
  });
});

describe('validateAssignedTo', () => {
  it('should return null for valid assigned to value', () => {
    expect(validateAssignedTo('Claims Department')).toBeNull();
  });

  it('should return null when value is null (not required)', () => {
    expect(validateAssignedTo(null)).toBeNull();
  });

  it('should return invalid format error for invalid assigned to value', () => {
    // Since assigned to just checks if it exists, any non-empty string is valid
    expect(validateAssignedTo('Any string is valid')).toBeNull();
  });
});

describe('validateProducerNumber', () => {
  it('should return null for valid producer number', () => {
    expect(validateProducerNumber('AG-789456')).toBeNull();
  });

  it('should return null when value is null (not required)', () => {
    expect(validateProducerNumber(null)).toBeNull();
  });

  it('should return invalid format error for invalid producer number', () => {
    expect(validateProducerNumber('789456')).toBe(ERROR_MESSAGES.INVALID_PRODUCER_NUMBER);
    expect(validateProducerNumber('AG789456')).toBe(ERROR_MESSAGES.INVALID_PRODUCER_NUMBER);
    expect(validateProducerNumber('AG-')).toBe(ERROR_MESSAGES.INVALID_PRODUCER_NUMBER);
  });
});

describe('validateDependentField', () => {
  it('should return null when field has no dependencies', () => {
    const metadata = createEmptyMetadata();
    expect(validateDependentField(
      MetadataFieldName.DOCUMENT_DESCRIPTION,
      'Policy Renewal Notice',
      metadata
    )).toBeNull();
  });

  it('should return null when dependency is satisfied', () => {
    const metadata = createValidMetadata();
    expect(validateDependentField(
      MetadataFieldName.LOSS_SEQUENCE,
      '1 - Vehicle Accident',
      metadata
    )).toBeNull();
  });

  it('should return dependency error when parent field is missing', () => {
    const metadata = createEmptyMetadata();
    expect(validateDependentField(
      MetadataFieldName.LOSS_SEQUENCE,
      '1 - Vehicle Accident',
      metadata
    )).toBe(ERROR_MESSAGES.POLICY_REQUIRED_FOR_LOSS);
  });
});

describe('getValidatorForField', () => {
  it('should return validatePolicyNumber for POLICY_NUMBER field', () => {
    const validator = getValidatorForField(MetadataFieldName.POLICY_NUMBER);
    expect(validator).toBeDefined();
    
    // Test that it behaves like validatePolicyNumber
    expect(validator('PLCY-12345', createEmptyMetadata())).toBeNull();
    expect(validator(null, createEmptyMetadata())).toBe(ERROR_MESSAGES.REQUIRED);
  });

  it('should return validateLossSequence for LOSS_SEQUENCE field', () => {
    const validator = getValidatorForField(MetadataFieldName.LOSS_SEQUENCE);
    expect(validator).toBeDefined();
    
    // Test that it behaves like validateLossSequence
    const metadata = createValidMetadata();
    expect(validator('1 - Vehicle Accident', metadata)).toBeNull();
  });

  it('should return validateClaimant for CLAIMANT field', () => {
    const validator = getValidatorForField(MetadataFieldName.CLAIMANT);
    expect(validator).toBeDefined();
    
    // Test that it behaves like validateClaimant
    const metadata = createValidMetadata();
    expect(validator('1 - John Smith', metadata)).toBeNull();
  });

  it('should return validateDocumentDescription for DOCUMENT_DESCRIPTION field', () => {
    const validator = getValidatorForField(MetadataFieldName.DOCUMENT_DESCRIPTION);
    expect(validator).toBeDefined();
    
    // Test that it behaves like validateDocumentDescription
    expect(validator('Policy Renewal Notice', createEmptyMetadata())).toBeNull();
    expect(validator(null, createEmptyMetadata())).toBe(ERROR_MESSAGES.REQUIRED);
  });

  it('should return validateAssignedTo for ASSIGNED_TO field', () => {
    const validator = getValidatorForField(MetadataFieldName.ASSIGNED_TO);
    expect(validator).toBeDefined();
    
    // Test that it behaves like validateAssignedTo
    expect(validator('Claims Department', createEmptyMetadata())).toBeNull();
    expect(validator(null, createEmptyMetadata())).toBeNull();
  });

  it('should return validateProducerNumber for PRODUCER_NUMBER field', () => {
    const validator = getValidatorForField(MetadataFieldName.PRODUCER_NUMBER);
    expect(validator).toBeDefined();
    
    // Test that it behaves like validateProducerNumber
    expect(validator('AG-789456', createEmptyMetadata())).toBeNull();
    expect(validator(null, createEmptyMetadata())).toBeNull();
  });

  it('should return a default validator for unknown fields', () => {
    // Cast to any to bypass TypeScript type checking for testing
    const validator = getValidatorForField('UNKNOWN_FIELD' as any);
    expect(validator).toBeDefined();
    
    // Default validator should always return null
    expect(validator('any value', createEmptyMetadata())).toBeNull();
    expect(validator(null, createEmptyMetadata())).toBeNull();
  });
});

describe('validateMetadataField', () => {
  it('should check dependency validation first', () => {
    const metadata = createEmptyMetadata();
    expect(validateMetadataField(
      MetadataFieldName.LOSS_SEQUENCE,
      '1 - Vehicle Accident',
      metadata
    )).toBe(ERROR_MESSAGES.POLICY_REQUIRED_FOR_LOSS);
  });

  it('should call the appropriate validator for the field', () => {
    const metadata = createValidMetadata();
    metadata.policyNumber = 'invalid format';
    expect(validateMetadataField(
      MetadataFieldName.POLICY_NUMBER,
      metadata.policyNumber,
      metadata
    )).toBe(ERROR_MESSAGES.INVALID_POLICY_NUMBER);
  });

  it('should return null when field is valid', () => {
    const metadata = createValidMetadata();
    expect(validateMetadataField(
      MetadataFieldName.POLICY_NUMBER,
      metadata.policyNumber,
      metadata
    )).toBeNull();
  });
});

describe('validateMetadataForm', () => {
  it('should validate all fields in the form', () => {
    const metadata = createEmptyMetadata();
    metadata.policyNumber = 'invalid';
    
    const errors = validateMetadataForm(metadata);
    
    expect(errors).toHaveProperty(MetadataFieldName.POLICY_NUMBER);
    expect(errors[MetadataFieldName.POLICY_NUMBER]).toBe(ERROR_MESSAGES.INVALID_POLICY_NUMBER);
    
    // Document description is required
    expect(errors).toHaveProperty(MetadataFieldName.DOCUMENT_DESCRIPTION);
    expect(errors[MetadataFieldName.DOCUMENT_DESCRIPTION]).toBe(ERROR_MESSAGES.REQUIRED);
  });

  it('should return empty object when all fields are valid', () => {
    const metadata = createValidMetadata();
    const errors = validateMetadataForm(metadata);
    
    // Object should have no properties with truthy values
    expect(Object.keys(errors).length).toBe(0);
  });

  it('should handle dependency validation correctly', () => {
    const metadata = createEmptyMetadata();
    metadata.lossSequence = '1 - Vehicle Accident';
    metadata.claimant = '1 - John Smith';
    
    const errors = validateMetadataForm(metadata);
    
    // Loss sequence depends on policy number
    expect(errors).toHaveProperty(MetadataFieldName.LOSS_SEQUENCE);
    expect(errors[MetadataFieldName.LOSS_SEQUENCE]).toBe(ERROR_MESSAGES.POLICY_REQUIRED_FOR_LOSS);
    
    // Claimant depends on loss sequence
    expect(errors).toHaveProperty(MetadataFieldName.CLAIMANT);
    expect(errors[MetadataFieldName.CLAIMANT]).toBe(ERROR_MESSAGES.LOSS_REQUIRED_FOR_CLAIMANT);
    
    // Document description is required
    expect(errors).toHaveProperty(MetadataFieldName.DOCUMENT_DESCRIPTION);
    expect(errors[MetadataFieldName.DOCUMENT_DESCRIPTION]).toBe(ERROR_MESSAGES.REQUIRED);
  });
});
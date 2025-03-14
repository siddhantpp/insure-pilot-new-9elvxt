import {
  mapMetadataToApiRequest,
  mapApiResponseToMetadata,
  validateMetadataField,
  validateMetadata,
  getFieldDependency,
  shouldDisableField,
  getInitialMetadata,
  METADATA_FIELD_CONFIGS,
  FIELD_DEPENDENCIES
} from '../../utils/metadataUtils';
import { DocumentMetadata } from '../../models/document.types';
import {
  DocumentMetadataResponse,
  DocumentMetadataUpdateRequest
} from '../../models/api.types';
import { MetadataFieldName } from '../../models/metadata.types';

describe('mapMetadataToApiRequest', () => {
  test('transforms camelCase properties to snake_case', () => {
    // Create a test metadata object with camelCase properties
    const metadata: DocumentMetadata = {
      policyNumber: 'PLCY-12345',
      policyId: 1,
      lossSequence: '1 - Vehicle Accident (03/15/2023)',
      lossId: 2,
      claimant: '1 - John Smith',
      claimantId: 3,
      documentDescription: 'Policy Renewal Notice',
      assignedTo: 'Claims Department',
      assignedToId: 4,
      assignedToType: 'group',
      producerNumber: 'AG-789456',
      producerId: 5
    };

    // Call the function with our test data
    const result = mapMetadataToApiRequest(metadata);

    // Verify that the result has snake_case properties with correct values
    expect(result.policy_id).toBe(1);
    expect(result.loss_id).toBe(2);
    expect(result.claimant_id).toBe(3);
    expect(result.document_description).toBe('Policy Renewal Notice');
    expect(result.assigned_to_id).toBe(4);
    expect(result.assigned_to_type).toBe('group');
    expect(result.producer_id).toBe(5);
  });

  test('handles null values correctly', () => {
    // Create a test metadata object with some null values
    const metadata: DocumentMetadata = {
      policyNumber: 'PLCY-12345',
      policyId: 1,
      lossSequence: null,
      lossId: null,
      claimant: null,
      claimantId: null,
      documentDescription: 'Policy Renewal Notice',
      assignedTo: null,
      assignedToId: null,
      assignedToType: null,
      producerNumber: null,
      producerId: null
    };

    // Call the function with our test data
    const result = mapMetadataToApiRequest(metadata);

    // Verify that the result has null values preserved
    expect(result.policy_id).toBe(1);
    expect(result.loss_id).toBeNull();
    expect(result.claimant_id).toBeNull();
    expect(result.document_description).toBe('Policy Renewal Notice');
    expect(result.assigned_to_id).toBeNull();
    expect(result.assigned_to_type).toBeNull();
    expect(result.producer_id).toBeNull();
  });

  test('includes only fields that should be sent to the API', () => {
    // Create a test metadata object
    const metadata: DocumentMetadata = {
      policyNumber: 'PLCY-12345',
      policyId: 1,
      lossSequence: '1 - Vehicle Accident (03/15/2023)',
      lossId: 2,
      claimant: '1 - John Smith',
      claimantId: 3,
      documentDescription: 'Policy Renewal Notice',
      assignedTo: 'Claims Department',
      assignedToId: 4,
      assignedToType: 'group',
      producerNumber: 'AG-789456',
      producerId: 5
    };

    // Call the function with our test data
    const result = mapMetadataToApiRequest(metadata);

    // Verify that display-only fields are not included in the result
    expect(result).not.toHaveProperty('policy_number');
    expect(result).not.toHaveProperty('loss_sequence');
    expect(result).not.toHaveProperty('claimant');
    expect(result).not.toHaveProperty('assigned_to');
    expect(result).not.toHaveProperty('producer_number');
  });

  test('handles empty metadata object', () => {
    // Create an empty metadata object with null values
    const metadata: DocumentMetadata = {
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

    // Call the function with our test data
    const result = mapMetadataToApiRequest(metadata);

    // Verify that all properties in the result are null
    expect(result.policy_id).toBeNull();
    expect(result.loss_id).toBeNull();
    expect(result.claimant_id).toBeNull();
    expect(result.document_description).toBeNull();
    expect(result.assigned_to_id).toBeNull();
    expect(result.assigned_to_type).toBeNull();
    expect(result.producer_id).toBeNull();
  });
});

describe('mapApiResponseToMetadata', () => {
  test('transforms snake_case properties to camelCase', () => {
    // Create a test API response object with snake_case properties
    const response: DocumentMetadataResponse = {
      policy_number: 'PLCY-12345',
      policy_id: 1,
      loss_sequence: '1 - Vehicle Accident (03/15/2023)',
      loss_id: 2,
      claimant: '1 - John Smith',
      claimant_id: 3,
      document_description: 'Policy Renewal Notice',
      assigned_to: 'Claims Department',
      assigned_to_id: 4,
      assigned_to_type: 'group',
      producer_number: 'AG-789456',
      producer_id: 5
    };

    // Call the function with our test data
    const result = mapApiResponseToMetadata(response);

    // Verify that the result has camelCase properties with correct values
    expect(result.policyNumber).toBe('PLCY-12345');
    expect(result.policyId).toBe(1);
    expect(result.lossSequence).toBe('1 - Vehicle Accident (03/15/2023)');
    expect(result.lossId).toBe(2);
    expect(result.claimant).toBe('1 - John Smith');
    expect(result.claimantId).toBe(3);
    expect(result.documentDescription).toBe('Policy Renewal Notice');
    expect(result.assignedTo).toBe('Claims Department');
    expect(result.assignedToId).toBe(4);
    expect(result.assignedToType).toBe('group');
    expect(result.producerNumber).toBe('AG-789456');
    expect(result.producerId).toBe(5);
  });

  test('handles null values correctly', () => {
    // Create a test API response object with some null values
    const response: DocumentMetadataResponse = {
      policy_number: 'PLCY-12345',
      policy_id: 1,
      loss_sequence: null,
      loss_id: null,
      claimant: null,
      claimant_id: null,
      document_description: 'Policy Renewal Notice',
      assigned_to: null,
      assigned_to_id: null,
      assigned_to_type: null,
      producer_number: null,
      producer_id: null
    };

    // Call the function with our test data
    const result = mapApiResponseToMetadata(response);

    // Verify that the result has null values preserved
    expect(result.policyNumber).toBe('PLCY-12345');
    expect(result.policyId).toBe(1);
    expect(result.lossSequence).toBeNull();
    expect(result.lossId).toBeNull();
    expect(result.claimant).toBeNull();
    expect(result.claimantId).toBeNull();
    expect(result.documentDescription).toBe('Policy Renewal Notice');
    expect(result.assignedTo).toBeNull();
    expect(result.assignedToId).toBeNull();
    expect(result.assignedToType).toBeNull();
    expect(result.producerNumber).toBeNull();
    expect(result.producerId).toBeNull();
  });

  test('handles empty response object', () => {
    // Create an empty API response object with null values
    const response: DocumentMetadataResponse = {
      policy_number: null,
      policy_id: null,
      loss_sequence: null,
      loss_id: null,
      claimant: null,
      claimant_id: null,
      document_description: null,
      assigned_to: null,
      assigned_to_id: null,
      assigned_to_type: null,
      producer_number: null,
      producer_id: null
    };

    // Call the function with our test data
    const result = mapApiResponseToMetadata(response);

    // Verify that all properties in the result are null
    expect(result.policyNumber).toBeNull();
    expect(result.policyId).toBeNull();
    expect(result.lossSequence).toBeNull();
    expect(result.lossId).toBeNull();
    expect(result.claimant).toBeNull();
    expect(result.claimantId).toBeNull();
    expect(result.documentDescription).toBeNull();
    expect(result.assignedTo).toBeNull();
    expect(result.assignedToId).toBeNull();
    expect(result.assignedToType).toBeNull();
    expect(result.producerNumber).toBeNull();
    expect(result.producerId).toBeNull();
  });
});

describe('validateMetadataField', () => {
  test('returns error for required empty fields', () => {
    // Find a required field from METADATA_FIELD_CONFIGS
    const requiredField = METADATA_FIELD_CONFIGS.find(config => config.required);
    
    // Skip the test if no required field is found (unlikely)
    if (!requiredField) {
      console.warn('No required field found in METADATA_FIELD_CONFIGS for testing');
      return;
    }
    
    // Create an empty metadata object
    const emptyMetadata: DocumentMetadata = getInitialMetadata();
    
    // Call the function with a required field, null value, and empty metadata
    const result = validateMetadataField(requiredField.name, null, emptyMetadata);
    
    // Verify that it returns an error message containing 'required'
    expect(result).not.toBeNull();
    expect(result?.toLowerCase()).toContain('required');
  });

  test('returns null for valid fields', () => {
    // Create a test metadata object with valid values
    const validMetadata: DocumentMetadata = {
      policyNumber: 'PLCY-12345',
      policyId: 1,
      lossSequence: '1 - Vehicle Accident (03/15/2023)',
      lossId: 2,
      claimant: '1 - John Smith',
      claimantId: 3,
      documentDescription: 'Policy Renewal Notice',
      assignedTo: 'Claims Department',
      assignedToId: 4,
      assignedToType: 'group',
      producerNumber: 'AG-789456',
      producerId: 5
    };

    // Test a required field with a valid value
    const result = validateMetadataField(
      MetadataFieldName.DOCUMENT_DESCRIPTION,
      validMetadata.documentDescription,
      validMetadata
    );
    
    // Verify that it returns null (no error)
    expect(result).toBeNull();
  });

  test('returns error for dependent fields when parent is empty', () => {
    // Create a metadata object with an empty parent field but a value for the dependent field
    const metadata: DocumentMetadata = {
      ...getInitialMetadata(),
      lossSequence: '1 - Vehicle Accident (03/15/2023)',
      lossId: 2
    };
    
    // Call the function with the dependent field, a value, and the metadata
    const result = validateMetadataField(
      MetadataFieldName.LOSS_SEQUENCE,
      metadata.lossSequence,
      metadata
    );
    
    // Verify that it returns an error about the dependency
    expect(result).not.toBeNull();
    expect(result?.toLowerCase()).toContain('please select');
  });

  test('returns null for dependent fields when parent has value', () => {
    // Create a metadata object with values for both parent and dependent fields
    const metadata: DocumentMetadata = {
      ...getInitialMetadata(),
      policyNumber: 'PLCY-12345',
      policyId: 1,
      lossSequence: '1 - Vehicle Accident (03/15/2023)',
      lossId: 2
    };
    
    // Call the function with the dependent field, a value, and the metadata
    const result = validateMetadataField(
      MetadataFieldName.LOSS_SEQUENCE,
      metadata.lossSequence,
      metadata
    );
    
    // Verify that it returns null (no error)
    expect(result).toBeNull();
  });
});

describe('validateMetadata', () => {
  test('returns errors for invalid metadata fields', () => {
    // Create a metadata object with some invalid values
    const invalidMetadata: DocumentMetadata = {
      ...getInitialMetadata(),
      // Missing required fields
      documentDescription: null
    };

    // Call the function with our test data
    const errors = validateMetadata(invalidMetadata);

    // Verify that it returns errors for required fields
    expect(Object.keys(errors).length).toBeGreaterThan(0);
    expect(errors[MetadataFieldName.DOCUMENT_DESCRIPTION]).toBeDefined();
    expect(errors[MetadataFieldName.DOCUMENT_DESCRIPTION].toLowerCase()).toContain('required');
  });

  test('returns empty object for valid metadata', () => {
    // Create a valid metadata object
    const validMetadata: DocumentMetadata = {
      policyNumber: 'PLCY-12345',
      policyId: 1,
      lossSequence: '1 - Vehicle Accident (03/15/2023)',
      lossId: 2,
      claimant: '1 - John Smith',
      claimantId: 3,
      documentDescription: 'Policy Renewal Notice',
      assignedTo: 'Claims Department',
      assignedToId: 4,
      assignedToType: 'group',
      producerNumber: 'AG-789456',
      producerId: 5
    };

    // Call the function with our test data
    const errors = validateMetadata(validMetadata);

    // Verify that it returns an empty object (no errors)
    expect(Object.keys(errors).length).toBe(0);
  });

  test('validates all fields in the metadata object', () => {
    // Create a spy on validateMetadataField
    const validateFieldSpy = jest.spyOn(
      require('../../utils/metadataUtils'),
      'validateMetadataField'
    );
    
    // Create a test metadata object
    const metadata: DocumentMetadata = getInitialMetadata();
    
    // Call the function with our test data
    validateMetadata(metadata);
    
    // Verify that validateMetadataField was called for each field
    expect(validateFieldSpy).toHaveBeenCalledTimes(Object.keys(MetadataFieldName).length);
    
    // Restore the original function
    validateFieldSpy.mockRestore();
  });
});

describe('getFieldDependency', () => {
  test('returns dependency for fields with dependencies', () => {
    // Check for a field that has a dependency
    const lossSequenceDependency = getFieldDependency(MetadataFieldName.LOSS_SEQUENCE);
    
    // Verify that it returns the correct dependency
    expect(lossSequenceDependency).not.toBeNull();
    expect(lossSequenceDependency?.field).toBe(MetadataFieldName.LOSS_SEQUENCE);
    expect(lossSequenceDependency?.dependsOn).toBe(MetadataFieldName.POLICY_NUMBER);
  });

  test('returns null for fields without dependencies', () => {
    // Check for a field that doesn't have a dependency
    const policyNumberDependency = getFieldDependency(MetadataFieldName.DOCUMENT_DESCRIPTION);
    
    // Verify that it returns null
    expect(policyNumberDependency).toBeNull();
  });

  test('returns null for invalid field name', () => {
    // Use an invalid field name (cast to any to bypass TypeScript)
    const invalidFieldDependency = getFieldDependency('INVALID_FIELD' as any);
    
    // Verify that it returns null
    expect(invalidFieldDependency).toBeNull();
  });
});

describe('shouldDisableField', () => {
  test('returns true for dependent fields when parent is empty', () => {
    // Create a metadata object with an empty parent field
    const metadata: DocumentMetadata = {
      ...getInitialMetadata(),
      // policyId is null, so lossSequence should be disabled
    };
    
    // Check if lossSequence should be disabled
    const shouldDisable = shouldDisableField(MetadataFieldName.LOSS_SEQUENCE, metadata);
    
    // Verify that it returns true
    expect(shouldDisable).toBe(true);
  });

  test('returns false for dependent fields when parent has value', () => {
    // Create a metadata object with a value for the parent field
    const metadata: DocumentMetadata = {
      ...getInitialMetadata(),
      policyNumber: 'PLCY-12345',
      policyId: 1
      // Now lossSequence should be enabled
    };
    
    // Check if lossSequence should be disabled
    const shouldDisable = shouldDisableField(MetadataFieldName.LOSS_SEQUENCE, metadata);
    
    // Verify that it returns false
    expect(shouldDisable).toBe(false);
  });

  test('returns false for fields without dependencies', () => {
    // Create a metadata object
    const metadata: DocumentMetadata = getInitialMetadata();
    
    // Check if policyNumber should be disabled (it has no dependencies)
    const shouldDisable = shouldDisableField(MetadataFieldName.POLICY_NUMBER, metadata);
    
    // Verify that it returns false
    expect(shouldDisable).toBe(false);
  });
});

describe('getInitialMetadata', () => {
  test('returns object with all properties set to null', () => {
    // Get the initial metadata
    const initialMetadata = getInitialMetadata();
    
    // Verify that it has all the properties of DocumentMetadata
    expect(initialMetadata).toHaveProperty('policyNumber');
    expect(initialMetadata).toHaveProperty('policyId');
    expect(initialMetadata).toHaveProperty('lossSequence');
    expect(initialMetadata).toHaveProperty('lossId');
    expect(initialMetadata).toHaveProperty('claimant');
    expect(initialMetadata).toHaveProperty('claimantId');
    expect(initialMetadata).toHaveProperty('documentDescription');
    expect(initialMetadata).toHaveProperty('assignedTo');
    expect(initialMetadata).toHaveProperty('assignedToId');
    expect(initialMetadata).toHaveProperty('assignedToType');
    expect(initialMetadata).toHaveProperty('producerNumber');
    expect(initialMetadata).toHaveProperty('producerId');
    
    // Verify that all properties are null
    expect(initialMetadata.policyNumber).toBeNull();
    expect(initialMetadata.policyId).toBeNull();
    expect(initialMetadata.lossSequence).toBeNull();
    expect(initialMetadata.lossId).toBeNull();
    expect(initialMetadata.claimant).toBeNull();
    expect(initialMetadata.claimantId).toBeNull();
    expect(initialMetadata.documentDescription).toBeNull();
    expect(initialMetadata.assignedTo).toBeNull();
    expect(initialMetadata.assignedToId).toBeNull();
    expect(initialMetadata.assignedToType).toBeNull();
    expect(initialMetadata.producerNumber).toBeNull();
    expect(initialMetadata.producerId).toBeNull();
  });
});
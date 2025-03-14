import { 
  getDocument, 
  updateDocumentMetadata, 
  processDocument, 
  trashDocument, 
  mapDocumentResponseToModel 
} from '../../services/documentService';
import { apiClient } from '../../lib/apiClient';
import { Document, DocumentStatus, DocumentMetadataUpdate } from '../../models/document.types';
import { DocumentResponse, ApiResponse } from '../../models/api.types';
import { createMockDocument } from '../../utils/testUtils';

// Mock the dependencies
jest.mock('../../lib/apiClient');

/**
 * Creates a mock document response from the API
 * @param overrides Optional overrides for the mock data
 * @returns Mock document response object
 */
function createMockDocumentResponse(overrides = {}): DocumentResponse {
  return {
    id: 123,
    filename: 'test-document.pdf',
    file_url: 'http://example.com/documents/123.pdf',
    description: 'Test Document',
    is_processed: false,
    status: 'unprocessed',
    created_at: '2023-01-01T12:00:00Z',
    updated_at: '2023-01-02T12:00:00Z',
    created_by: {
      id: 1,
      username: 'creator'
    },
    updated_by: {
      id: 2,
      username: 'updater'
    },
    metadata: {
      policy_number: 'PLCY-12345',
      policy_id: 12345,
      loss_sequence: '1 - Vehicle Accident (03/15/2023)',
      loss_id: 1001,
      claimant: '1 - John Smith',
      claimant_id: 5001,
      document_description: 'Policy Renewal Notice',
      assigned_to: 'Claims Department',
      assigned_to_id: 3,
      assigned_to_type: 'group',
      producer_number: 'AG-789456',
      producer_id: 456
    },
    ...overrides
  };
}

/**
 * Creates a mock API response with the provided data
 * @param data The data to include in the response
 * @returns Mock API response object
 */
function createApiResponse<T>(data: T): ApiResponse<T> {
  return {
    data,
    status: 200,
    message: 'Success'
  };
}

describe('getDocument', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('should fetch document data and return mapped document', async () => {
    // Arrange
    const documentId = 123;
    const mockResponse = createMockDocumentResponse();
    const mockApiResponse = createApiResponse(mockResponse);
    
    (apiClient.get as jest.Mock).mockResolvedValue(mockApiResponse);
    
    // Act
    const result = await getDocument(documentId);
    
    // Assert
    expect(apiClient.get).toHaveBeenCalledWith(`/documents/${documentId}`);
    expect(result).toEqual(expect.objectContaining({
      id: mockResponse.id,
      filename: mockResponse.filename,
      fileUrl: mockResponse.file_url,
      status: DocumentStatus.UNPROCESSED
    }));
  });

  it('should handle API errors correctly', async () => {
    // Arrange
    const documentId = 123;
    const errorMessage = 'Network error';
    
    (apiClient.get as jest.Mock).mockRejectedValue(new Error(errorMessage));
    
    // Act & Assert
    await expect(getDocument(documentId)).rejects.toThrow(/Failed to retrieve document/);
    expect(apiClient.get).toHaveBeenCalledWith(`/documents/${documentId}`);
  });
});

describe('updateDocumentMetadata', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('should update document metadata and return updated document', async () => {
    // Arrange
    const documentId = 123;
    const metadata: DocumentMetadataUpdate = {
      policyId: 12345,
      lossId: 1001,
      claimantId: 5001,
      documentDescription: 'Updated Description',
      assignedToId: 3,
      assignedToType: 'group',
      producerId: 456
    };
    
    const mockResponse = createMockDocumentResponse({
      description: 'Updated Description',
      metadata: {
        ...createMockDocumentResponse().metadata,
        document_description: 'Updated Description'
      }
    });
    
    const mockApiResponse = createApiResponse(mockResponse);
    
    (apiClient.put as jest.Mock).mockResolvedValue(mockApiResponse);
    
    // Act
    const result = await updateDocumentMetadata(documentId, metadata);
    
    // Assert
    expect(apiClient.put).toHaveBeenCalledWith(
      `/documents/${documentId}/metadata`,
      expect.anything()
    );
    expect(result).toEqual(expect.objectContaining({
      id: mockResponse.id,
      description: 'Updated Description',
      metadata: expect.objectContaining({
        documentDescription: 'Updated Description'
      })
    }));
  });

  it('should handle API errors correctly', async () => {
    // Arrange
    const documentId = 123;
    const metadata: DocumentMetadataUpdate = {
      documentDescription: 'Updated Description'
    };
    const errorMessage = 'Server error';
    
    (apiClient.put as jest.Mock).mockRejectedValue(new Error(errorMessage));
    
    // Act & Assert
    await expect(updateDocumentMetadata(documentId, metadata)).rejects.toThrow(/Failed to update document metadata/);
    expect(apiClient.put).toHaveBeenCalledWith(`/documents/${documentId}/metadata`, expect.anything());
  });
});

describe('processDocument', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('should mark document as processed and return updated document', async () => {
    // Arrange
    const documentId = 123;
    const processed = true;
    
    const mockResponse = createMockDocumentResponse({
      is_processed: true,
      status: 'processed'
    });
    
    const mockApiResponse = createApiResponse(mockResponse);
    
    (apiClient.post as jest.Mock).mockResolvedValue(mockApiResponse);
    
    // Act
    const result = await processDocument(documentId, processed);
    
    // Assert
    expect(apiClient.post).toHaveBeenCalledWith(
      `/documents/${documentId}/process`,
      { processed: true }
    );
    expect(result).toEqual(expect.objectContaining({
      id: mockResponse.id,
      isProcessed: true,
      status: DocumentStatus.PROCESSED
    }));
  });

  it('should mark document as unprocessed and return updated document', async () => {
    // Arrange
    const documentId = 123;
    const processed = false;
    
    const mockResponse = createMockDocumentResponse({
      is_processed: false,
      status: 'unprocessed'
    });
    
    const mockApiResponse = createApiResponse(mockResponse);
    
    (apiClient.post as jest.Mock).mockResolvedValue(mockApiResponse);
    
    // Act
    const result = await processDocument(documentId, processed);
    
    // Assert
    expect(apiClient.post).toHaveBeenCalledWith(
      `/documents/${documentId}/process`,
      { processed: false }
    );
    expect(result).toEqual(expect.objectContaining({
      id: mockResponse.id,
      isProcessed: false,
      status: DocumentStatus.UNPROCESSED
    }));
  });

  it('should handle API errors correctly', async () => {
    // Arrange
    const documentId = 123;
    const processed = true;
    const errorMessage = 'Server error';
    
    (apiClient.post as jest.Mock).mockRejectedValue(new Error(errorMessage));
    
    // Act & Assert
    await expect(processDocument(documentId, processed)).rejects.toThrow(/Failed to process document/);
    expect(apiClient.post).toHaveBeenCalledWith(`/documents/${documentId}/process`, { processed: true });
  });
});

describe('trashDocument', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('should trash document successfully', async () => {
    // Arrange
    const documentId = 123;
    
    // Mock the apiClient.post to return a success response
    (apiClient.post as jest.Mock).mockResolvedValue({});
    
    // Act
    await trashDocument(documentId);
    
    // Assert
    expect(apiClient.post).toHaveBeenCalledWith(`/documents/${documentId}/trash`);
  });

  it('should handle API errors correctly', async () => {
    // Arrange
    const documentId = 123;
    const errorMessage = 'Server error';
    
    (apiClient.post as jest.Mock).mockRejectedValue(new Error(errorMessage));
    
    // Act & Assert
    await expect(trashDocument(documentId)).rejects.toThrow(/Failed to trash document/);
    expect(apiClient.post).toHaveBeenCalledWith(`/documents/${documentId}/trash`);
  });
});

describe('mapDocumentResponseToModel', () => {
  it('should correctly map API response to document model', () => {
    // Arrange
    const mockResponse = createMockDocumentResponse();
    
    // Act
    const result = mapDocumentResponseToModel(mockResponse);
    
    // Assert
    expect(result).toEqual({
      id: mockResponse.id,
      filename: mockResponse.filename,
      fileUrl: mockResponse.file_url,
      description: mockResponse.description,
      isProcessed: mockResponse.is_processed,
      status: DocumentStatus.UNPROCESSED,
      createdAt: mockResponse.created_at,
      updatedAt: mockResponse.updated_at,
      createdBy: {
        id: mockResponse.created_by.id,
        username: mockResponse.created_by.username
      },
      updatedBy: {
        id: mockResponse.updated_by.id,
        username: mockResponse.updated_by.username
      },
      metadata: {
        policyNumber: mockResponse.metadata.policy_number,
        policyId: mockResponse.metadata.policy_id,
        lossSequence: mockResponse.metadata.loss_sequence,
        lossId: mockResponse.metadata.loss_id,
        claimant: mockResponse.metadata.claimant,
        claimantId: mockResponse.metadata.claimant_id,
        documentDescription: mockResponse.metadata.document_description,
        assignedTo: mockResponse.metadata.assigned_to,
        assignedToId: mockResponse.metadata.assigned_to_id,
        assignedToType: mockResponse.metadata.assigned_to_type,
        producerNumber: mockResponse.metadata.producer_number,
        producerId: mockResponse.metadata.producer_id
      }
    });
  });

  it('should handle different document statuses correctly', () => {
    // Test processed status
    const processedResponse = createMockDocumentResponse({
      status: 'processed',
      is_processed: true
    });
    const processedResult = mapDocumentResponseToModel(processedResponse);
    expect(processedResult.status).toBe(DocumentStatus.PROCESSED);
    
    // Test trashed status
    const trashedResponse = createMockDocumentResponse({
      status: 'trashed',
      is_processed: false
    });
    const trashedResult = mapDocumentResponseToModel(trashedResponse);
    expect(trashedResult.status).toBe(DocumentStatus.TRASHED);
    
    // Test unprocessed status (default)
    const unprocessedResponse = createMockDocumentResponse({
      status: 'unprocessed',
      is_processed: false
    });
    const unprocessedResult = mapDocumentResponseToModel(unprocessedResponse);
    expect(unprocessedResult.status).toBe(DocumentStatus.UNPROCESSED);
    
    // Test unknown status (should default to unprocessed)
    const unknownResponse = createMockDocumentResponse({
      status: 'unknown' as any,
      is_processed: false
    });
    const unknownResult = mapDocumentResponseToModel(unknownResponse);
    expect(unknownResult.status).toBe(DocumentStatus.UNPROCESSED);
  });

  it('should handle null metadata fields', () => {
    // Arrange
    const mockResponse = createMockDocumentResponse({
      metadata: {
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
      }
    });
    
    // Act
    const result = mapDocumentResponseToModel(mockResponse);
    
    // Assert
    expect(result.metadata).toEqual({
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
    });
  });
});
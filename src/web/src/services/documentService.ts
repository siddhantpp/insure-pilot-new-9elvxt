/**
 * Service module for handling document-related operations in the Documents View feature.
 * Provides functions for retrieving documents, updating metadata, processing documents,
 * and trashing documents.
 */

import { apiClient } from '../lib/apiClient'; // axios 1.x
import { 
  Document, 
  DocumentMetadata, 
  DocumentMetadataUpdate, 
  DocumentStatus 
} from '../models/document.types';
import { 
  DocumentResponse, 
  DocumentProcessRequest, 
  ApiResponse 
} from '../models/api.types';
import { 
  mapApiResponseToMetadata, 
  mapMetadataToApiRequest 
} from '../services/metadataService';
import { getErrorMessage } from '../utils/errorUtils';

/**
 * Retrieves a document by ID from the API
 * 
 * @param documentId The ID of the document to retrieve
 * @returns Promise resolving to the document data
 */
export async function getDocument(documentId: number): Promise<Document> {
  try {
    const response = await apiClient.get<DocumentResponse>(`/documents/${documentId}`);
    return mapDocumentResponseToModel(response.data);
  } catch (error) {
    throw new Error(`Failed to retrieve document: ${getErrorMessage(error)}`);
  }
}

/**
 * Updates the metadata for a specific document
 * 
 * @param documentId The ID of the document to update
 * @param metadata The updated metadata object
 * @returns Promise resolving to the updated document
 */
export async function updateDocumentMetadata(
  documentId: number,
  metadata: DocumentMetadataUpdate
): Promise<Document> {
  try {
    // Convert metadata to API format
    const requestData = mapMetadataToApiRequest(metadata as DocumentMetadata);
    
    // Send update request
    const response = await apiClient.put<DocumentResponse>(
      `/documents/${documentId}/metadata`,
      requestData
    );
    
    // Map response to Document model
    return mapDocumentResponseToModel(response.data);
  } catch (error) {
    throw new Error(`Failed to update document metadata: ${getErrorMessage(error)}`);
  }
}

/**
 * Marks a document as processed or unprocessed
 * 
 * @param documentId The ID of the document to process/unprocess
 * @param processed Boolean indicating whether the document should be processed (true) or unprocessed (false)
 * @returns Promise resolving to the updated document
 */
export async function processDocument(
  documentId: number,
  processed: boolean
): Promise<Document> {
  try {
    // Create request payload
    const requestData: DocumentProcessRequest = {
      processed: processed
    };
    
    // Send process request
    const response = await apiClient.post<DocumentResponse>(
      `/documents/${documentId}/process`,
      requestData
    );
    
    // Map response to Document model
    return mapDocumentResponseToModel(response.data);
  } catch (error) {
    const action = processed ? 'process' : 'unprocess';
    throw new Error(`Failed to ${action} document: ${getErrorMessage(error)}`);
  }
}

/**
 * Moves a document to the trash
 * 
 * @param documentId The ID of the document to trash
 * @returns Promise resolving when the operation completes
 */
export async function trashDocument(documentId: number): Promise<void> {
  try {
    await apiClient.post<void>(`/documents/${documentId}/trash`);
  } catch (error) {
    throw new Error(`Failed to trash document: ${getErrorMessage(error)}`);
  }
}

/**
 * Maps an API response to the Document model
 * 
 * @param response The API response containing document data
 * @returns Mapped Document object
 */
export function mapDocumentResponseToModel(response: DocumentResponse): Document {
  // Map status string to enum
  let status: DocumentStatus;
  switch (response.status) {
    case 'processed':
      status = DocumentStatus.PROCESSED;
      break;
    case 'trashed':
      status = DocumentStatus.TRASHED;
      break;
    default:
      status = DocumentStatus.UNPROCESSED;
  }
  
  // Map document data from API response to Document model
  return {
    id: response.id,
    filename: response.filename,
    fileUrl: response.file_url,
    description: response.description,
    isProcessed: response.is_processed,
    status: status,
    createdAt: response.created_at,
    updatedAt: response.updated_at,
    createdBy: {
      id: response.created_by.id,
      username: response.created_by.username
    },
    updatedBy: {
      id: response.updated_by.id,
      username: response.updated_by.username
    },
    metadata: mapApiResponseToMetadata(response.metadata)
  };
}
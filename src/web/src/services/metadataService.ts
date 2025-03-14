/**
 * Service module that handles document metadata operations
 * 
 * This service acts as an intermediary between the UI components and the backend API,
 * providing methods for managing document metadata throughout the Documents View feature.
 */

import { apiClient } from '../lib/apiClient'; // axios 1.x
import { DocumentMetadata, DocumentMetadataUpdate } from '../models/document.types';
import { ApiResponse, DocumentMetadataResponse, DropdownOption } from '../models/api.types';
import { MetadataFieldName } from '../models/metadata.types';
import { 
  mapMetadataToApiRequest, 
  mapApiResponseToMetadata,
  validateMetadata
} from '../utils/metadataUtils';

/**
 * Retrieves metadata for a specific document
 * 
 * @param documentId The ID of the document to retrieve metadata for
 * @returns Promise resolving to document metadata
 */
const getDocumentMetadata = async (documentId: number): Promise<DocumentMetadata> => {
  const response = await apiClient.get<DocumentMetadataResponse>(
    `/documents/${documentId}/metadata`
  );
  return mapApiResponseToMetadata(response.data);
};

/**
 * Updates metadata for a specific document
 * 
 * @param documentId The ID of the document to update metadata for
 * @param metadata The updated metadata object
 * @returns Promise resolving to updated document metadata
 */
const updateDocumentMetadata = async (
  documentId: number,
  metadata: DocumentMetadataUpdate
): Promise<DocumentMetadata> => {
  // Validate metadata before sending to API
  const errors = validateMetadata(metadata as DocumentMetadata);
  if (Object.keys(errors).length > 0) {
    throw new Error('Validation failed: ' + JSON.stringify(errors));
  }

  // Transform to API request format
  const requestData = mapMetadataToApiRequest(metadata as DocumentMetadata);

  // Send update request
  const response = await apiClient.put<DocumentMetadataResponse>(
    `/documents/${documentId}/metadata`,
    requestData
  );

  // Transform response back to frontend format
  return mapApiResponseToMetadata(response.data);
};

/**
 * Retrieves policy options for dropdown selection
 * 
 * @param searchTerm Optional search term for filtering policies
 * @param producerId Optional producer ID to filter policies by producer
 * @returns Promise resolving to array of policy dropdown options
 */
const getPolicyOptions = async (
  searchTerm?: string,
  producerId?: number
): Promise<DropdownOption[]> => {
  // Build query params
  const params: Record<string, string> = {};
  if (searchTerm) params.search = searchTerm;
  if (producerId) params.producer_id = producerId.toString();

  // Fetch policies with optional filtering
  const response = await apiClient.get<{ data: DropdownOption[] }>(
    '/policies',
    { params }
  );

  return response.data.data || [];
};

/**
 * Retrieves loss sequence options for a specific policy
 * 
 * @param policyId The ID of the policy to get losses for
 * @returns Promise resolving to array of loss dropdown options
 */
const getLossOptions = async (policyId: number): Promise<DropdownOption[]> => {
  // Handle null or undefined policyId
  if (!policyId) {
    return [];
  }

  const response = await apiClient.get<{ data: DropdownOption[] }>(
    `/policies/${policyId}/losses`
  );

  return response.data.data || [];
};

/**
 * Retrieves claimant options for a specific loss
 * 
 * @param lossId The ID of the loss to get claimants for
 * @returns Promise resolving to array of claimant dropdown options
 */
const getClaimantOptions = async (lossId: number): Promise<DropdownOption[]> => {
  // Handle null or undefined lossId
  if (!lossId) {
    return [];
  }

  const response = await apiClient.get<{ data: DropdownOption[] }>(
    `/losses/${lossId}/claimants`
  );

  return response.data.data || [];
};

/**
 * Retrieves producer options for dropdown selection
 * 
 * @param searchTerm Optional search term for filtering producers
 * @returns Promise resolving to array of producer dropdown options
 */
const getProducerOptions = async (searchTerm?: string): Promise<DropdownOption[]> => {
  // Build query params
  const params: Record<string, string> = {};
  if (searchTerm) params.search = searchTerm;

  const response = await apiClient.get<{ data: DropdownOption[] }>(
    '/producers',
    { params }
  );

  return response.data.data || [];
};

/**
 * Retrieves document description options for dropdown selection
 * 
 * @returns Promise resolving to array of document description dropdown options
 */
const getDocumentDescriptionOptions = async (): Promise<DropdownOption[]> => {
  const response = await apiClient.get<{ data: DropdownOption[] }>(
    '/document-descriptions'
  );

  return response.data.data || [];
};

/**
 * Retrieves assignee options (users and groups) for dropdown selection
 * 
 * @param searchTerm Optional search term for filtering assignees
 * @param type Optional type filter ('user' or 'group')
 * @returns Promise resolving to array of assignee dropdown options
 */
const getAssigneeOptions = async (
  searchTerm?: string,
  type?: string
): Promise<DropdownOption[]> => {
  // Build query params
  const params: Record<string, string> = {};
  if (searchTerm) params.search = searchTerm;
  if (type) params.type = type;

  const response = await apiClient.get<{ data: DropdownOption[] }>(
    '/assignees',
    { params }
  );

  return response.data.data || [];
};

/**
 * Retrieves dropdown options for a specific metadata field
 * 
 * @param fieldName The metadata field to get options for
 * @param currentValues Current metadata values (needed for dependencies)
 * @param searchTerm Optional search term for filtering options
 * @returns Promise resolving to array of dropdown options for the specified field
 */
const getOptionsForField = async (
  fieldName: MetadataFieldName,
  currentValues: DocumentMetadata,
  searchTerm?: string
): Promise<DropdownOption[]> => {
  switch (fieldName) {
    case MetadataFieldName.POLICY_NUMBER:
      return getPolicyOptions(searchTerm, currentValues.producerId || undefined);

    case MetadataFieldName.LOSS_SEQUENCE:
      if (!currentValues.policyId) return [];
      return getLossOptions(currentValues.policyId);

    case MetadataFieldName.CLAIMANT:
      if (!currentValues.lossId) return [];
      return getClaimantOptions(currentValues.lossId);

    case MetadataFieldName.DOCUMENT_DESCRIPTION:
      return getDocumentDescriptionOptions();

    case MetadataFieldName.ASSIGNED_TO:
      return getAssigneeOptions(searchTerm);

    case MetadataFieldName.PRODUCER_NUMBER:
      return getProducerOptions(searchTerm);

    default:
      return [];
  }
};

export const metadataService = {
  getDocumentMetadata,
  updateDocumentMetadata,
  getPolicyOptions,
  getLossOptions,
  getClaimantOptions,
  getProducerOptions,
  getDocumentDescriptionOptions,
  getAssigneeOptions,
  getOptionsForField
};
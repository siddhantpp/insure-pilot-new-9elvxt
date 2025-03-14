/**
 * TypeScript interfaces for API communication in the Documents View feature.
 * This file defines standardized request and response types for interacting with
 * backend services, including document retrieval, metadata management, document
 * processing, and history tracking.
 */

/**
 * Generic interface for standardized API responses
 */
export interface ApiResponse<T> {
  data: T;
  status: number;
  message: string;
}

/**
 * Interface for standardized API error responses
 */
export interface ApiError {
  status: number;
  message: string;
  errors: Record<string, string[]> | null;
}

/**
 * Interface for user reference data in API responses
 */
export interface UserReference {
  id: number;
  username: string;
}

/**
 * Interface for document data in API responses
 */
export interface DocumentResponse {
  id: number;
  filename: string;
  file_url: string;
  description: string;
  is_processed: boolean;
  status: string;
  created_at: string;
  updated_at: string;
  created_by: UserReference;
  updated_by: UserReference;
  metadata: DocumentMetadataResponse;
}

/**
 * Interface for document metadata in API responses
 */
export interface DocumentMetadataResponse {
  policy_number: string | null;
  policy_id: number | null;
  loss_sequence: string | null;
  loss_id: number | null;
  claimant: string | null;
  claimant_id: number | null;
  document_description: string | null;
  assigned_to: string | null;
  assigned_to_id: number | null;
  assigned_to_type: string | null; // 'user' | 'group'
  producer_number: string | null;
  producer_id: number | null;
}

/**
 * Interface for document metadata update requests
 */
export interface DocumentMetadataUpdateRequest {
  policy_id: number | null;
  loss_id: number | null;
  claimant_id: number | null;
  document_description: string | null;
  assigned_to_id: number | null;
  assigned_to_type: string | null; // 'user' | 'group'
  producer_id: number | null;
}

/**
 * Interface for document processing requests
 */
export interface DocumentProcessRequest {
  processed: boolean;
}

/**
 * Interface for document action data in API responses
 */
export interface DocumentActionResponse {
  id: number;
  document_id: number;
  action_type: string;
  description: string;
  created_at: string;
  created_by: UserReference;
}

/**
 * Interface for document history data in API responses
 */
export interface DocumentHistoryResponse {
  actions: DocumentActionResponse[];
  last_edited: string;
  last_edited_by: UserReference;
}

/**
 * Interface for policy data in API responses
 */
export interface PolicyResponse {
  id: number;
  number: string;
  prefix: string;
  effective_date: string;
  expiration_date: string;
}

/**
 * Interface for loss data in API responses
 */
export interface LossResponse {
  id: number;
  sequence: number;
  date: string;
  description: string;
}

/**
 * Interface for claimant data in API responses
 */
export interface ClaimantResponse {
  id: number;
  sequence: number;
  name: string;
}

/**
 * Interface for producer data in API responses
 */
export interface ProducerResponse {
  id: number;
  number: string;
  name: string;
}

/**
 * Interface for dropdown option data in API responses
 */
export interface DropdownOption {
  id: number | string;
  label: string;
  value: number | string;
  disabled?: boolean;
  metadata?: Record<string, any>;
}

/**
 * Generic interface for paginated API responses
 */
export interface PaginatedResponse<T> {
  data: T[];
  meta: PaginationMeta;
}

/**
 * Interface for pagination metadata in API responses
 */
export interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  total_pages: number;
}

/**
 * Interface for validation error responses from the API
 */
export interface ValidationErrorResponse {
  message: string;
  errors: Record<string, string[]>;
}
import { UserReference } from './api.types';

/**
 * Enum representing possible document statuses
 */
export enum DocumentStatus {
  UNPROCESSED = 'unprocessed',
  PROCESSED = 'processed',
  TRASHED = 'trashed',
}

/**
 * Enum representing possible document action types for history tracking
 */
export enum DocumentActionType {
  VIEW = 'view',
  CREATE = 'create',
  UPDATE_METADATA = 'update_metadata',
  PROCESS = 'process',
  UNPROCESS = 'unprocess',
  TRASH = 'trash',
  RESTORE = 'restore',
}

/**
 * Interface for user information related to document actions
 */
export interface DocumentUser {
  id: number;
  username: string;
}

/**
 * Interface for document metadata fields
 */
export interface DocumentMetadata {
  policyNumber: string | null;
  policyId: number | null;
  lossSequence: string | null;
  lossId: number | null;
  claimant: string | null;
  claimantId: number | null;
  documentDescription: string | null;
  assignedTo: string | null;
  assignedToId: number | null;
  assignedToType: string | null; // 'user' | 'group'
  producerNumber: string | null;
  producerId: number | null;
}

/**
 * Interface for document metadata update requests
 */
export interface DocumentMetadataUpdate {
  policyId: number | null;
  lossId: number | null;
  claimantId: number | null;
  documentDescription: string | null;
  assignedToId: number | null;
  assignedToType: string | null; // 'user' | 'group'
  producerId: number | null;
}

/**
 * Core document model interface with all document properties
 */
export interface Document {
  id: number;
  filename: string;
  fileUrl: string;
  description: string;
  isProcessed: boolean;
  status: DocumentStatus;
  createdAt: string;
  updatedAt: string;
  createdBy: DocumentUser;
  updatedBy: DocumentUser;
  metadata: DocumentMetadata;
}

/**
 * Interface for document actions recorded in history
 */
export interface DocumentAction {
  id: number;
  documentId: number;
  actionType: DocumentActionType;
  description: string;
  timestamp: string;
  user: DocumentUser;
}

/**
 * Enum representing possible panel types in the document viewer
 */
export enum PanelType {
  METADATA = 'metadata',
  HISTORY = 'history',
}

/**
 * Interface for document viewer component state
 */
export interface DocumentViewerState {
  document: Document | null;
  isLoading: boolean;
  error: string | null;
  activePanel: PanelType;
  isSaving: boolean;
  saveError: string | null;
}

/**
 * Props interface for the DocumentDisplay component
 */
export interface DocumentDisplayProps {
  document: Document;
  onLoadComplete: () => void;
  onError: (error: string) => void;
}

/**
 * Props interface for the MetadataPanel component
 */
export interface MetadataPanelProps {
  document: Document;
  onMetadataChange: (metadata: DocumentMetadataUpdate) => Promise<void>;
  onProcessDocument: (processed: boolean) => Promise<void>;
  onTrashDocument: () => Promise<void>;
  onViewHistory: () => void;
  isSaving: boolean;
  saveError: string | null;
}

/**
 * Props interface for the DocumentViewer component
 */
export interface DocumentViewerProps {
  documentId: number;
  onClose: () => void;
  initialPanel?: PanelType;
}

/**
 * Interface for document context provider
 */
export interface DocumentContextType {
  state: DocumentViewerState;
  loadDocument: (documentId: number) => Promise<void>;
  updateMetadata: (metadata: DocumentMetadataUpdate) => Promise<void>;
  processDocument: (processed: boolean) => Promise<void>;
  trashDocument: () => Promise<void>;
  setActivePanel: (panel: PanelType) => void;
}
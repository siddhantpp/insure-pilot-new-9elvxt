/**
 * A service module that provides functions for generating contextual navigation options
 * and handling navigation to related records based on document metadata. This service
 * supports the ellipsis menu functionality in the Documents View feature.
 */

import { apiClient } from '../lib/apiClient';
import { Document } from '../models/document.types';

/**
 * Interface defining the structure of navigation options
 */
export interface NavigationOption {
  label: string;      // Display label for the option
  targetType: string; // Type of target (producer, policy, claimant)
  targetId: number;   // ID of the target record
}

/**
 * Generates navigation options based on document metadata
 * @param document The document containing metadata with related record IDs
 * @returns Promise resolving to an array of navigation options
 */
export async function getNavigationOptions(document: Document): Promise<NavigationOption[]> {
  const options: NavigationOption[] = [];
  
  // Add producer navigation option if producer ID exists
  if (document.metadata.producerId) {
    options.push({
      label: 'Go to Producer View',
      targetType: 'producer',
      targetId: document.metadata.producerId
    });
  }
  
  // Add policy navigation option if policy ID exists
  if (document.metadata.policyId) {
    options.push({
      label: 'Go to Policy',
      targetType: 'policy',
      targetId: document.metadata.policyId
    });
  }
  
  // Add claimant navigation option if claimant ID exists
  if (document.metadata.claimantId) {
    options.push({
      label: 'Go to Claimant View',
      targetType: 'claimant',
      targetId: document.metadata.claimantId
    });
  }
  
  return options;
}

/**
 * Navigates to a related record view based on the selected option
 * @param option The navigation option containing target information
 * @returns Promise resolving when navigation is complete
 */
export async function navigateTo(option: NavigationOption): Promise<void> {
  let url = '';
  
  // Get URL based on target type
  switch (option.targetType) {
    case 'producer':
      url = await getProducerViewUrl(option.targetId);
      break;
    case 'policy':
      url = await getPolicyViewUrl(option.targetId);
      break;
    case 'claimant':
      url = await getClaimantViewUrl(option.targetId);
      break;
    default:
      throw new Error(`Unknown target type: ${option.targetType}`);
  }
  
  // Navigate to the URL
  if (url) {
    window.location.href = url;
  }
}

/**
 * Retrieves the URL for the producer view page
 * @param producerId The ID of the producer
 * @returns Promise resolving to the producer view URL
 */
async function getProducerViewUrl(producerId: number): Promise<string> {
  const response = await apiClient.get<{ url: string }>(`/api/producers/${producerId}/url`);
  return response.data.data.url;
}

/**
 * Retrieves the URL for the policy view page
 * @param policyId The ID of the policy
 * @returns Promise resolving to the policy view URL
 */
async function getPolicyViewUrl(policyId: number): Promise<string> {
  const response = await apiClient.get<{ url: string }>(`/api/policies/${policyId}/url`);
  return response.data.data.url;
}

/**
 * Retrieves the URL for the claimant view page
 * @param claimantId The ID of the claimant
 * @returns Promise resolving to the claimant view URL
 */
async function getClaimantViewUrl(claimantId: number): Promise<string> {
  const response = await apiClient.get<{ url: string }>(`/api/claimants/${claimantId}/url`);
  return response.data.data.url;
}
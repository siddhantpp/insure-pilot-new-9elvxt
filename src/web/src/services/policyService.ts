/**
 * Policy Service
 * 
 * This service provides functions for interacting with policy-related API endpoints.
 * It handles the retrieval of policy data, policy options for dropdown fields, and
 * policy-related losses for the document metadata panel in the Documents View feature.
 */

import { apiClient } from '../lib/apiClient';
import { PolicyResponse, DropdownOption, ApiResponse, LossResponse } from '../models/api.types';
import { getErrorMessage } from '../utils/errorUtils';

/**
 * Retrieves a list of policies with optional filtering
 * 
 * @param search Optional search string to filter policies
 * @param limit Optional maximum number of policies to retrieve
 * @returns Promise resolving to an array of policy data
 */
export const getPolicies = async (search?: string, limit?: number): Promise<PolicyResponse[]> => {
  try {
    const params: Record<string, string | number> = {};
    
    if (search) {
      params.search = search;
    }
    
    if (limit) {
      params.limit = limit;
    }
    
    const response = await apiClient.get<PolicyResponse[]>('/policies', { params });
    return response.data;
  } catch (error) {
    console.error('Error fetching policies:', getErrorMessage(error));
    throw error;
  }
};

/**
 * Retrieves a specific policy by ID
 * 
 * @param policyId The ID of the policy to retrieve
 * @returns Promise resolving to the policy data
 */
export const getPolicy = async (policyId: number): Promise<PolicyResponse> => {
  try {
    const response = await apiClient.get<PolicyResponse>(`/policies/${policyId}`);
    return response.data;
  } catch (error) {
    console.error(`Error fetching policy ${policyId}:`, getErrorMessage(error));
    throw error;
  }
};

/**
 * Retrieves policy options for dropdown selection with optional filtering
 * 
 * @param search Optional search string to filter policy options
 * @param producerId Optional producer ID to filter policies by producer
 * @param limit Optional maximum number of options to retrieve
 * @returns Promise resolving to an array of dropdown options
 */
export const getPolicyOptions = async (
  search?: string,
  producerId?: number,
  limit?: number
): Promise<DropdownOption[]> => {
  try {
    const params: Record<string, string | number> = {};
    
    if (search) {
      params.search = search;
    }
    
    if (producerId) {
      params.producer_id = producerId;
    }
    
    if (limit) {
      params.limit = limit;
    }
    
    const response = await apiClient.get<PolicyResponse[]>('/policies/options', { params });
    
    // Map the policy responses to dropdown options
    return response.data.map(mapPolicyToDropdownOption);
  } catch (error) {
    console.error('Error fetching policy options:', getErrorMessage(error));
    throw error;
  }
};

/**
 * Retrieves policies associated with a specific producer
 * 
 * @param producerId The ID of the producer
 * @param search Optional search string to filter policies
 * @param limit Optional maximum number of policies to retrieve
 * @returns Promise resolving to an array of policy data
 */
export const getProducerPolicies = async (
  producerId: number,
  search?: string,
  limit?: number
): Promise<PolicyResponse[]> => {
  try {
    const params: Record<string, string | number> = {};
    
    if (search) {
      params.search = search;
    }
    
    if (limit) {
      params.limit = limit;
    }
    
    const response = await apiClient.get<PolicyResponse[]>(`/producers/${producerId}/policies`, { params });
    return response.data;
  } catch (error) {
    console.error(`Error fetching policies for producer ${producerId}:`, getErrorMessage(error));
    throw error;
  }
};

/**
 * Retrieves losses associated with a specific policy
 * 
 * @param policyId The ID of the policy
 * @returns Promise resolving to an array of loss data
 */
export const getPolicyLosses = async (policyId: number): Promise<LossResponse[]> => {
  try {
    const response = await apiClient.get<LossResponse[]>(`/policies/${policyId}/losses`);
    return response.data;
  } catch (error) {
    console.error(`Error fetching losses for policy ${policyId}:`, getErrorMessage(error));
    throw error;
  }
};

/**
 * Maps a policy response to a dropdown option format
 * 
 * @param policy Policy response object
 * @returns Formatted dropdown option
 */
export const mapPolicyToDropdownOption = (policy: PolicyResponse): DropdownOption => {
  return {
    id: policy.id,
    value: policy.id,
    label: `${policy.prefix}${policy.number}`,
    metadata: {
      ...policy,
      effectiveDate: policy.effective_date,
      expirationDate: policy.expiration_date
    }
  };
};
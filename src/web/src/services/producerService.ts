/**
 * Producer Service for the Documents View feature
 * 
 * This module provides functions for interacting with producer-related API endpoints.
 * It handles producer data retrieval, producer options for dropdown fields, and 
 * producer-related policies for the document metadata panel.
 */

import { apiClient } from '../lib/apiClient';
import { ProducerResponse, DropdownOption, ApiResponse } from '../models/api.types';
import { getErrorMessage } from '../utils/errorUtils';

/**
 * Retrieves a list of producers with optional filtering
 * @param search Optional search term to filter producers
 * @param limit Optional maximum number of results to return
 * @returns Promise resolving to an array of producer data
 */
export const getProducers = async (search?: string, limit?: number): Promise<ProducerResponse[]> => {
  try {
    // Construct query parameters with search and limit
    const params: Record<string, any> = {};
    if (search) params.search = search;
    if (limit) params.limit = limit;
    
    // Make GET request to /producers endpoint with query parameters
    const response = await apiClient.get<ProducerResponse[]>('/producers', { params });
    
    // Return the producer data from the response
    return response.data;
  } catch (error) {
    console.error('Error fetching producers:', error);
    throw new Error(getErrorMessage(error));
  }
};

/**
 * Retrieves a specific producer by ID
 * @param producerId ID of the producer to retrieve
 * @returns Promise resolving to the producer data
 */
export const getProducer = async (producerId: number): Promise<ProducerResponse> => {
  try {
    // Make GET request to /producers/{producerId} endpoint
    const response = await apiClient.get<ProducerResponse>(`/producers/${producerId}`);
    
    // Return the producer data from the response
    return response.data;
  } catch (error) {
    console.error(`Error fetching producer with ID ${producerId}:`, error);
    throw new Error(getErrorMessage(error));
  }
};

/**
 * Retrieves producer options for dropdown selection with optional filtering
 * @param search Optional search term to filter producers
 * @param limit Optional maximum number of results to return
 * @returns Promise resolving to an array of dropdown options
 */
export const getProducerOptions = async (search?: string, limit?: number): Promise<DropdownOption[]> => {
  try {
    // Construct query parameters with search and limit
    const params: Record<string, any> = {};
    if (search) params.search = search;
    if (limit) params.limit = limit;
    
    // Make GET request to /producers/options endpoint with query parameters
    const response = await apiClient.get<DropdownOption[]>('/producers/options', { params });
    
    // Return the dropdown options from the response
    return response.data;
  } catch (error) {
    console.error('Error fetching producer options:', error);
    throw new Error(getErrorMessage(error));
  }
};

/**
 * Retrieves the URL for navigating to the producer view
 * @param producerId ID of the producer to navigate to
 * @returns Promise resolving to the producer view URL
 */
export const getProducerUrl = async (producerId: number): Promise<string> => {
  try {
    // Make GET request to /producers/{producerId}/url endpoint
    const response = await apiClient.get<{ url: string }>(`/producers/${producerId}/url`);
    
    // Return the URL from the response
    return response.data.url;
  } catch (error) {
    console.error(`Error fetching producer URL for ID ${producerId}:`, error);
    throw new Error(getErrorMessage(error));
  }
};

/**
 * Maps a producer response to a dropdown option format
 * @param producer Producer data to map
 * @returns Formatted dropdown option
 */
export const mapProducerToDropdownOption = (producer: ProducerResponse): DropdownOption => {
  return {
    id: producer.id,
    value: producer.id,
    // Format label as producer number + producer name
    label: `${producer.number} - ${producer.name}`,
    // Add producer data as metadata
    metadata: {
      producerNumber: producer.number,
      producerName: producer.name
    }
  };
};
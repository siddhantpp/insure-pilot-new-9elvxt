/**
 * Service module for interacting with loss-related API endpoints in the Documents View feature
 * 
 * This service handles loss data retrieval for the Documents View feature,
 * particularly for populating the Loss Sequence dropdown field in the metadata panel
 * and retrieving loss details.
 */
import { apiClient } from '../lib/apiClient';
import { ApiResponse, LossResponse, DropdownOption } from '../models/api.types';

/**
 * Retrieves losses for a specific policy for the Loss Sequence dropdown
 * 
 * @param policyId The ID of the policy to retrieve losses for
 * @returns Promise resolving to an array of loss options formatted for dropdown
 */
export async function getLossesByPolicy(policyId: number): Promise<DropdownOption[]> {
    if (!policyId) {
        return [];
    }

    try {
        const response = await apiClient.get<LossResponse[]>(`/policies/${policyId}/losses`);
        if (!response.data) return [];
        return response.data.map(mapLossToDropdownOption);
    } catch (error) {
        console.error('Error fetching losses by policy:', error);
        return [];
    }
}

/**
 * Retrieves a specific loss by its ID
 * 
 * @param lossId The ID of the loss to retrieve
 * @returns Promise resolving to the loss data or null if not found
 */
export async function getLossById(lossId: number): Promise<LossResponse | null> {
    if (!lossId) {
        return null;
    }

    try {
        const response = await apiClient.get<LossResponse>(`/losses/${lossId}`);
        return response.data || null;
    } catch (error) {
        console.error('Error fetching loss by ID:', error);
        return null;
    }
}

/**
 * Retrieves the URL for navigating to a loss's detail view
 * 
 * @param lossId The ID of the loss to get the URL for
 * @returns Promise resolving to the loss detail URL or null if not available
 */
export async function getLossUrl(lossId: number): Promise<string | null> {
    if (!lossId) {
        return null;
    }

    try {
        const response = await apiClient.get<{ url: string }>(`/losses/${lossId}/url`);
        return response.data?.url || null;
    } catch (error) {
        console.error('Error fetching loss URL:', error);
        return null;
    }
}

/**
 * Maps a loss response to dropdown option format
 * 
 * @param loss The loss response to map
 * @returns Formatted dropdown option with id, label, and value properties
 */
function mapLossToDropdownOption(loss: LossResponse): DropdownOption {
    // Format the date for display
    const lossDate = new Date(loss.date);
    const formattedDate = lossDate.toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric'
    });

    // Create the label in the format "1 - Vehicle Accident (03/15/2023)"
    const label = `${loss.sequence} - ${loss.description} (${formattedDate})`;

    return {
        id: loss.id,
        label: label,
        value: loss.id
    };
}
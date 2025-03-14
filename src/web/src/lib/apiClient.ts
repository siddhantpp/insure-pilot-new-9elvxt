/**
 * API client for the Documents View feature
 * 
 * This module provides standardized methods for making HTTP requests to the
 * backend services, handling authentication, error processing, and response formatting.
 */

import axios, { AxiosInstance, AxiosRequestConfig, AxiosResponse, AxiosError } from 'axios'; // axios 1.x
import { ApiResponse, ApiError } from '../models/api.types';
import { formatErrorForLogging, getErrorMessage } from '../utils/errorUtils';

/**
 * Creates and configures an Axios instance for API requests
 */
const createApiClient = (): AxiosInstance => {
  // Create axios instance with base URL from environment
  const axiosInstance = axios.create({
    baseURL: process.env.REACT_APP_API_URL || '/api',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    timeout: 30000 // 30 seconds
  });

  // Add request interceptor for authentication token
  axiosInstance.interceptors.request.use(
    (config) => {
      const token = getAuthToken();
      if (token && config.headers) {
        config.headers.Authorization = `Bearer ${token}`;
      }
      return config;
    },
    (error) => {
      return Promise.reject(error);
    }
  );

  // Add response interceptor for standardizing responses
  axiosInstance.interceptors.response.use(
    (response: AxiosResponse) => {
      // Standardize successful responses
      const apiResponse: ApiResponse<any> = {
        data: response.data,
        status: response.status,
        message: response.data.message || 'Success'
      };
      return apiResponse;
    },
    (error: AxiosError) => {
      // Handle errors in the error interceptor
      return Promise.reject(handleApiError(error));
    }
  );

  return axiosInstance;
};

/**
 * Retrieves the authentication token from storage
 * @returns Authentication token or null if not found
 */
const getAuthToken = (): string | null => {
  try {
    return localStorage.getItem('auth_token');
  } catch (error) {
    // Handle error during localStorage access
    console.error('Error accessing localStorage:', error);
    return null;
  }
};

/**
 * Processes API errors and formats them into a standardized ApiError object
 * @param error The axios error to process
 * @returns Standardized API error object
 */
export const handleApiError = (error: AxiosError): ApiError => {
  // Log the error for debugging
  console.error(formatErrorForLogging(error));

  // Check if error has a response
  if (error.response) {
    // Server responded with an error status
    const { status } = error.response;
    const responseData = error.response.data as any;
    
    return {
      status: status,
      message: responseData.message || getErrorMessage(error),
      errors: responseData.errors || null
    };
  } else if (error.request) {
    // Request was made but no response received (network error)
    return {
      status: 0,
      message: 'Network error: Unable to connect to server',
      errors: null
    };
  } else {
    // Error in setting up the request
    return {
      status: 0,
      message: error.message || 'Unknown error occurred',
      errors: null
    };
  }
};

// Initialize the API client
const axiosInstance = createApiClient();

/**
 * Makes a GET request to the specified endpoint
 * @param url The endpoint URL
 * @param config Optional request configuration
 * @returns Promise resolving to the API response
 */
const get = <T>(url: string, config?: AxiosRequestConfig): Promise<ApiResponse<T>> => {
  return axiosInstance.get<T>(url, config);
};

/**
 * Makes a POST request to the specified endpoint
 * @param url The endpoint URL
 * @param data Optional data to send
 * @param config Optional request configuration
 * @returns Promise resolving to the API response
 */
const post = <T>(url: string, data?: any, config?: AxiosRequestConfig): Promise<ApiResponse<T>> => {
  return axiosInstance.post<T>(url, data, config);
};

/**
 * Makes a PUT request to the specified endpoint
 * @param url The endpoint URL
 * @param data Optional data to send
 * @param config Optional request configuration
 * @returns Promise resolving to the API response
 */
const put = <T>(url: string, data?: any, config?: AxiosRequestConfig): Promise<ApiResponse<T>> => {
  return axiosInstance.put<T>(url, data, config);
};

/**
 * Makes a DELETE request to the specified endpoint
 * @param url The endpoint URL
 * @param config Optional request configuration
 * @returns Promise resolving to the API response
 */
const delete_ = <T>(url: string, config?: AxiosRequestConfig): Promise<ApiResponse<T>> => {
  return axiosInstance.delete<T>(url, config);
};

// Export the API client with standardized request methods
export const apiClient = {
  get,
  post,
  put,
  delete: delete_
};
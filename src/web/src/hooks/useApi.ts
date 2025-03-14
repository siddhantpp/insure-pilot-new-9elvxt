import { useState, useCallback, useEffect, useRef } from 'react'; // React 18.x
import { apiClient, handleApiError } from '../lib/apiClient';
import { ApiResponse, ApiError } from '../models/api.types';
import { useDebounce } from './useDebounce';
import { getErrorMessage } from '../utils/errorUtils';
import { useAuth } from '../context/AuthContext';
import { AxiosRequestConfig } from 'axios'; // axios 1.x

/**
 * Options for configuring the useApi hook
 */
interface UseApiOptions {
  /** Duration in ms to keep cached responses (default: 5 minutes) */
  cacheTime?: number;
  /** Delay in ms for debounced operations (default: 300ms) */
  debounceDelay?: number;
  /** Whether to enable caching for GET requests (default: true) */
  enableCaching?: boolean;
}

/**
 * Type for the cache record storing API responses
 */
type CacheRecord = Record<string, { data: ApiResponse<any>; timestamp: number }>;

/**
 * Custom hook that provides a standardized interface for making API requests
 * with loading state, error handling, and response formatting.
 * 
 * This hook wraps the apiClient with React state management for the Documents View feature.
 * 
 * @param options Configuration options for the hook
 * @returns Object containing API methods, loading state, error state, and utility functions
 * 
 * @example
 * // Basic usage
 * function DocumentMetadataPanel({ documentId }) {
 *   const { get, loading, error } = useApi();
 *   
 *   useEffect(() => {
 *     if (documentId) {
 *       get(`/documents/${documentId}`);
 *     }
 *   }, [documentId, get]);
 *   
 *   if (loading) return <LoadingIndicator />;
 *   if (error) return <ErrorMessage error={error} />;
 *   
 *   return <DocumentForm />;
 * }
 */
export function useApi(options: UseApiOptions = {}) {
  // State for loading, error, and response
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<ApiError | null>(null);
  const [response, setResponse] = useState<ApiResponse<any> | null>(null);
  
  // Get auth context for authentication state
  const { state } = useAuth();
  
  // Set up request cache using ref to persist across renders
  const requestCache = useRef<CacheRecord>({});
  
  // Get options with defaults
  const debounceDelay = options.debounceDelay || 300;
  const cacheTime = options.cacheTime || 5 * 60 * 1000; // 5 minutes default
  const enableCaching = options.enableCaching !== false;
  
  // Debounced version of the response for triggering delayed effects
  const debouncedResponse = useDebounce(response, debounceDelay);
  
  /**
   * Core function to execute API requests with loading state and error handling
   */
  const executeRequest = useCallback(async <T>(
    requestFn: () => Promise<ApiResponse<T>>,
    cacheKey?: string
  ): Promise<ApiResponse<T> | null> => {
    // Check cache if cacheKey is provided and caching is enabled
    if (enableCaching && cacheKey) {
      const cachedData = requestCache.current[cacheKey];
      if (cachedData && Date.now() - cachedData.timestamp < cacheTime) {
        return cachedData.data as ApiResponse<T>;
      }
    }
    
    // Clear previous error and set loading state
    setError(null);
    setLoading(true);
    
    try {
      // Execute the request function
      const result = await requestFn();
      
      // Cache the result if cacheKey is provided and caching is enabled
      if (enableCaching && cacheKey) {
        requestCache.current[cacheKey] = {
          data: result,
          timestamp: Date.now()
        };
      }
      
      // Update state with the response
      setResponse(result);
      setLoading(false);
      return result;
    } catch (err) {
      // Process error using the handleApiError utility
      const apiError = handleApiError(err);
      
      // Enhance error message with user-friendly text if available
      const userMessage = getErrorMessage(err);
      if (apiError.message === 'Unknown error occurred' && userMessage) {
        apiError.message = userMessage;
      }
      
      // Update error state and clear loading
      setError(apiError);
      setLoading(false);
      return null;
    }
  }, [cacheTime, enableCaching]);
  
  /**
   * Makes a GET request to the specified endpoint
   * 
   * @param url The endpoint URL
   * @param config Optional Axios request configuration
   * @returns Promise resolving to the API response or null on error
   */
  const get = useCallback(<T>(
    url: string,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T> | null> => {
    const cacheKey = enableCaching ? `GET:${url}:${JSON.stringify(config || {})}` : undefined;
    return executeRequest<T>(
      () => apiClient.get<T>(url, config),
      cacheKey
    );
  }, [executeRequest, enableCaching]);
  
  /**
   * Makes a POST request to the specified endpoint
   * 
   * @param url The endpoint URL
   * @param data Data to send in the request body
   * @param config Optional Axios request configuration
   * @returns Promise resolving to the API response or null on error
   */
  const post = useCallback(<T>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T> | null> => {
    // POST requests are not cached by default
    return executeRequest<T>(
      () => apiClient.post<T>(url, data, config)
    );
  }, [executeRequest]);
  
  /**
   * Makes a PUT request to the specified endpoint
   * 
   * @param url The endpoint URL
   * @param data Data to send in the request body
   * @param config Optional Axios request configuration
   * @returns Promise resolving to the API response or null on error
   */
  const put = useCallback(<T>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T> | null> => {
    // PUT requests are not cached by default
    return executeRequest<T>(
      () => apiClient.put<T>(url, data, config)
    );
  }, [executeRequest]);
  
  /**
   * Makes a DELETE request to the specified endpoint
   * 
   * @param url The endpoint URL
   * @param config Optional Axios request configuration
   * @returns Promise resolving to the API response or null on error
   */
  const delete_ = useCallback(<T>(
    url: string,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T> | null> => {
    // DELETE requests are not cached by default
    return executeRequest<T>(
      () => apiClient.delete<T>(url, config)
    );
  }, [executeRequest]);
  
  /**
   * Clears the current error state
   */
  const clearError = useCallback(() => {
    setError(null);
  }, []);
  
  /**
   * Retrieves a cached response for the specified URL if available
   * 
   * @param url The endpoint URL to check in cache
   * @returns Cached API response or null if not found
   */
  const getCached = useCallback(<T>(url: string): ApiResponse<T> | null => {
    if (!enableCaching) return null;
    
    const cacheKey = `GET:${url}:{}`;
    const cachedData = requestCache.current[cacheKey];
    
    if (cachedData && Date.now() - cachedData.timestamp < cacheTime) {
      return cachedData.data as ApiResponse<T>;
    }
    
    return null;
  }, [cacheTime, enableCaching]);
  
  // Clean up expired cache entries periodically
  useEffect(() => {
    const cleanupInterval = setInterval(() => {
      const now = Date.now();
      Object.keys(requestCache.current).forEach(key => {
        const entry = requestCache.current[key];
        if (now - entry.timestamp > cacheTime) {
          delete requestCache.current[key];
        }
      });
    }, 60000); // Clean up every minute
    
    return () => {
      clearInterval(cleanupInterval);
    };
  }, [cacheTime]);
  
  // Return the hook interface
  return {
    get,
    post,
    put,
    delete: delete_,
    loading,
    error,
    clearError,
    getCached
  };
}
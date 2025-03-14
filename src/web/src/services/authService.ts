/**
 * Authentication service for the Documents View feature
 * 
 * This service handles user login, logout, token management, and session validation.
 * It communicates with the backend authentication endpoints and manages the storage
 * of authentication tokens.
 */

import { post, get } from '../lib/apiClient'; // axios 1.x
import { User, LoginCredentials, LoginResponse } from '../models/user.types';
import { ApiResponse } from '../models/api.types';
import { setLocalStorageItem, getLocalStorageItem, removeLocalStorageItem } from '../utils/storageUtils';

// Constants for storage keys
const AUTH_TOKEN_KEY = 'auth_token';
const USER_DATA_KEY = 'user_data';

/**
 * Authenticates a user with the backend and stores the authentication token
 * @param username The username for login
 * @param password The password for login
 * @param rememberMe Whether to extend the token lifespan
 * @returns Authentication response containing token and user data
 */
const login = async (
  username: string,
  password: string,
  rememberMe: boolean
): Promise<LoginResponse> => {
  // Create login credentials object
  const credentials: LoginCredentials = {
    username,
    password,
    rememberMe
  };

  try {
    // Make POST request to login endpoint
    const response = await post<LoginResponse>('/auth/login', credentials);
    
    // Store authentication token and user data
    const { token, user } = response.data;
    setToken(token);
    setUserInStorage(user);
    
    return response.data;
  } catch (error) {
    // Let the caller handle the error
    throw error;
  }
};

/**
 * Logs out the current user by invalidating the token on the server and removing it from storage
 * @returns Promise that resolves when logout is complete
 */
const logout = async (): Promise<void> => {
  try {
    // Make POST request to logout endpoint
    await post<void>('/auth/logout');
  } catch (error) {
    // Log the error but still proceed with local cleanup
    console.error('Error during logout:', error);
  } finally {
    // Clean up local storage regardless of API response
    removeToken();
    removeUserFromStorage();
  }
};

/**
 * Retrieves the current authenticated user data
 * @returns User data if authenticated, null otherwise
 */
const getCurrentUser = async (): Promise<User | null> => {
  // Check if we have a token
  const token = getToken();
  if (!token) {
    return null;
  }

  try {
    // Make GET request to user endpoint
    const response = await get<User>('/auth/user');
    const user = response.data;
    
    // Update stored user data
    setUserInStorage(user);
    
    return user;
  } catch (error) {
    // If there was an error, the token might be invalid
    removeToken();
    removeUserFromStorage();
    return null;
  }
};

/**
 * Validates the current authentication token with the server
 * @returns True if token is valid, false otherwise
 */
const validateToken = async (): Promise<boolean> => {
  // Check if we have a token
  const token = getToken();
  if (!token) {
    return false;
  }

  try {
    // Make GET request to validate endpoint
    await get<{ valid: boolean }>('/auth/validate');
    return true;
  } catch (error) {
    // If there was an error, the token is invalid
    removeToken();
    removeUserFromStorage();
    return false;
  }
};

/**
 * Refreshes the authentication token to extend the session
 * @returns True if token was refreshed successfully, false otherwise
 */
const refreshToken = async (): Promise<boolean> => {
  // Check if we have a token
  const token = getToken();
  if (!token) {
    return false;
  }

  try {
    // Make POST request to refresh endpoint
    const response = await post<{ token: string }>('/auth/refresh');
    
    // Update stored token
    setToken(response.data.token);
    
    return true;
  } catch (error) {
    // If there was an error, the token might be invalid
    removeToken();
    removeUserFromStorage();
    return false;
  }
};

/**
 * Retrieves the current authentication token from storage
 * @returns Authentication token if it exists, null otherwise
 */
const getToken = (): string | null => {
  return getLocalStorageItem<string>(AUTH_TOKEN_KEY);
};

/**
 * Stores the authentication token in localStorage
 * @param token The token to store
 * @returns True if token was stored successfully, false otherwise
 */
const setToken = (token: string): boolean => {
  return setLocalStorageItem(AUTH_TOKEN_KEY, token);
};

/**
 * Removes the authentication token from localStorage
 * @returns True if token was removed successfully, false otherwise
 */
const removeToken = (): boolean => {
  return removeLocalStorageItem(AUTH_TOKEN_KEY);
};

/**
 * Retrieves the user data from localStorage
 * @returns User data if it exists, null otherwise
 */
const getUserFromStorage = (): User | null => {
  return getLocalStorageItem<User>(USER_DATA_KEY);
};

/**
 * Stores the user data in localStorage
 * @param user The user data to store
 * @returns True if user data was stored successfully, false otherwise
 */
const setUserInStorage = (user: User): boolean => {
  return setLocalStorageItem(USER_DATA_KEY, user);
};

/**
 * Removes the user data from localStorage
 * @returns True if user data was removed successfully, false otherwise
 */
const removeUserFromStorage = (): boolean => {
  return removeLocalStorageItem(USER_DATA_KEY);
};

// Export the authentication service object
export const authService = {
  login,
  logout,
  getCurrentUser,
  validateToken,
  refreshToken,
  getToken
};
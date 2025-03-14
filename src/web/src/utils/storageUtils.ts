import { getErrorMessage } from './errorUtils';

/**
 * Stores a value in localStorage with error handling
 * @param key The key to store the value under
 * @param value The value to store
 * @returns True if storage was successful, false otherwise
 */
export function setLocalStorageItem(key: string, value: any): boolean {
  try {
    const serializedValue = JSON.stringify(value);
    localStorage.setItem(key, serializedValue);
    return true;
  } catch (error) {
    console.error(`Failed to store item in localStorage: ${getErrorMessage(error)}`);
    return false;
  }
}

/**
 * Retrieves a value from localStorage with error handling and optional type casting
 * @param key The key to retrieve
 * @param defaultValue The default value to return if the key is not found or an error occurs
 * @returns The retrieved value cast to type T, or defaultValue if not found
 */
export function getLocalStorageItem<T>(key: string, defaultValue: any = null): T | null {
  try {
    const item = localStorage.getItem(key);
    if (item === null) {
      return defaultValue;
    }
    return JSON.parse(item) as T;
  } catch (error) {
    console.error(`Failed to retrieve item from localStorage: ${getErrorMessage(error)}`);
    return defaultValue;
  }
}

/**
 * Removes an item from localStorage with error handling
 * @param key The key to remove
 * @returns True if removal was successful, false otherwise
 */
export function removeLocalStorageItem(key: string): boolean {
  try {
    localStorage.removeItem(key);
    return true;
  } catch (error) {
    console.error(`Failed to remove item from localStorage: ${getErrorMessage(error)}`);
    return false;
  }
}

/**
 * Stores a value in sessionStorage with error handling
 * @param key The key to store the value under
 * @param value The value to store
 * @returns True if storage was successful, false otherwise
 */
export function setSessionStorageItem(key: string, value: any): boolean {
  try {
    const serializedValue = JSON.stringify(value);
    sessionStorage.setItem(key, serializedValue);
    return true;
  } catch (error) {
    console.error(`Failed to store item in sessionStorage: ${getErrorMessage(error)}`);
    return false;
  }
}

/**
 * Retrieves a value from sessionStorage with error handling and optional type casting
 * @param key The key to retrieve
 * @param defaultValue The default value to return if the key is not found or an error occurs
 * @returns The retrieved value cast to type T, or defaultValue if not found
 */
export function getSessionStorageItem<T>(key: string, defaultValue: any = null): T | null {
  try {
    const item = sessionStorage.getItem(key);
    if (item === null) {
      return defaultValue;
    }
    return JSON.parse(item) as T;
  } catch (error) {
    console.error(`Failed to retrieve item from sessionStorage: ${getErrorMessage(error)}`);
    return defaultValue;
  }
}

/**
 * Removes an item from sessionStorage with error handling
 * @param key The key to remove
 * @returns True if removal was successful, false otherwise
 */
export function removeSessionStorageItem(key: string): boolean {
  try {
    sessionStorage.removeItem(key);
    return true;
  } catch (error) {
    console.error(`Failed to remove item from sessionStorage: ${getErrorMessage(error)}`);
    return false;
  }
}

/**
 * Clears all items from both localStorage and sessionStorage with error handling
 * @returns True if clearing was successful, false otherwise
 */
export function clearAllStorage(): boolean {
  try {
    localStorage.clear();
    sessionStorage.clear();
    return true;
  } catch (error) {
    console.error(`Failed to clear storage: ${getErrorMessage(error)}`);
    return false;
  }
}

/**
 * Checks if a specific storage type is available in the current browser environment
 * @param storageType The storage type to check ('localStorage' or 'sessionStorage')
 * @returns True if the storage type is available, false otherwise
 */
export function isStorageAvailable(storageType: string): boolean {
  try {
    const storage = window[storageType as keyof Window] as Storage;
    const testKey = '__storage_test__';
    storage.setItem(testKey, testKey);
    storage.removeItem(testKey);
    return true;
  } catch (error) {
    return false;
  }
}

/**
 * Calculates the current usage of a specific storage type in bytes
 * @param storageType The storage type to check ('localStorage' or 'sessionStorage')
 * @returns The number of bytes used by the storage type
 */
export function getStorageUsage(storageType: string): number {
  try {
    let usage = 0;
    const storage = window[storageType as keyof Window] as Storage;
    
    for (let i = 0; i < storage.length; i++) {
      const key = storage.key(i);
      if (key) {
        const value = storage.getItem(key) || '';
        // Calculate bytes (2 bytes per character in UTF-16)
        usage += (key.length + value.length) * 2;
      }
    }
    
    return usage;
  } catch (error) {
    console.error(`Failed to calculate storage usage: ${getErrorMessage(error)}`);
    return 0;
  }
}
import { useState, useEffect, useCallback, useRef } from 'react'; // React 18.x

/**
 * A hook that debounces a value, returning the latest value only after 
 * a specified delay has passed without changes.
 * 
 * Useful for preventing excessive re-renders when a value is changing rapidly,
 * such as during user typing in search fields or form inputs in the Documents View.
 * 
 * @template T - The type of the value being debounced
 * @param value - The value to debounce
 * @param delay - The delay in milliseconds
 * @returns The debounced value
 * 
 * @example
 * // Use in a type-ahead filter for Policy Number dropdown
 * const [policyFilter, setPolicyFilter] = useState('');
 * const debouncedFilter = useDebounce(policyFilter, 300);
 * 
 * // Only fetch filtered policies when debouncedFilter changes
 * useEffect(() => {
 *   if (debouncedFilter !== undefined) {
 *     fetchFilteredPolicies(debouncedFilter);
 *   }
 * }, [debouncedFilter]);
 */
export function useDebounce<T>(value: T, delay: number): T {
  // State to hold the debounced value
  const [debouncedValue, setDebouncedValue] = useState<T>(value);

  useEffect(() => {
    // Set up a timeout to update the debounced value after the delay
    const timer = setTimeout(() => {
      setDebouncedValue(value);
    }, delay);

    // Clear the timeout if the value or delay changes
    return () => {
      clearTimeout(timer);
    };
  }, [value, delay]);

  return debouncedValue;
}

/**
 * A hook that returns a debounced version of the provided callback function.
 * The callback will only be executed after the specified delay has passed
 * without the debounced function being called again.
 * 
 * Useful for optimizing event handlers in the Documents View that trigger 
 * expensive operations, such as metadata updates or search operations.
 * 
 * @template T - The type of the callback function
 * @param callback - The function to debounce
 * @param delay - The delay in milliseconds
 * @param dependencies - Dependencies array for memoization (similar to useCallback)
 * @returns The debounced callback function
 * 
 * @example
 * // Debounced metadata update handler
 * const handleMetadataChange = useDebouncedCallback(
 *   (fieldName, value) => {
 *     saveDocumentMetadata(documentId, fieldName, value);
 *   },
 *   500,
 *   [documentId, saveDocumentMetadata]
 * );
 * 
 * // Debounced dropdown filter handler
 * const handleFilterChange = useDebouncedCallback(
 *   (filterText) => {
 *     updateFilteredOptions(filterText);
 *   },
 *   300,
 *   [updateFilteredOptions]
 * );
 */
export function useDebouncedCallback<T extends (...args: any[]) => any>(
  callback: T,
  delay: number,
  dependencies: any[] = []
): (...args: Parameters<T>) => void {
  // Ref to store the timeout ID
  const timeoutRef = useRef<NodeJS.Timeout | null>(null);

  // Memoized function to clear any existing timeout
  const clearTimeoutRef = useCallback(() => {
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
      timeoutRef.current = null;
    }
  }, []);

  // Return a memoized debounced function
  return useCallback(
    (...args: Parameters<T>) => {
      // Clear any existing timeout
      clearTimeoutRef();
      
      // Set up a new timeout
      timeoutRef.current = setTimeout(() => {
        callback(...args);
      }, delay);
    },
    [callback, delay, clearTimeoutRef, ...dependencies]
  );
}
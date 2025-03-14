import { useEffect, useRef } from 'react'; // React v18.x

/**
 * A custom React hook that detects clicks outside of a specified element.
 * This hook is primarily used for UI components like dropdowns, modals, and menus 
 * that need to close when a user clicks outside of them.
 * 
 * @param ref - React ref object pointing to the element to monitor for outside clicks
 * @param callback - Function to call when a click outside the element is detected
 *                  (should be memoized with useCallback for optimal performance)
 * 
 * @example
 * const dropdownRef = useRef(null);
 * const handleClose = useCallback(() => setIsOpen(false), [setIsOpen]);
 * useOutsideClick(dropdownRef, handleClose);
 */
const useOutsideClick = (ref: React.RefObject<HTMLElement>, callback: () => void): void => {
  useEffect(() => {
    /**
     * Handler for the mousedown event
     * Checks if the click occurred outside the referenced element
     */
    const handleClickOutside = (event: MouseEvent) => {
      // If ref is null or click was inside the element, do nothing
      if (!ref.current || ref.current.contains(event.target as Node)) {
        return;
      }
      
      // Click was outside the element, execute the callback
      callback();
    };

    // Attach the event listener to the document
    document.addEventListener('mousedown', handleClickOutside);
    
    // Clean up function to remove the event listener
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [ref, callback]); // Re-run effect if ref or callback changes
};

export { useOutsideClick };
import { useEffect, useCallback, useRef } from 'react'; // react@18.x

/**
 * Interface defining the structure of keyboard shortcut configurations
 */
export interface ShortcutConfig {
  /** The key that triggers the shortcut (e.g., 'Escape', 's', 'p') */
  key: string;
  /** Whether the Ctrl key should be pressed (defaults to false if undefined) */
  ctrlKey?: boolean;
  /** Whether the Alt key should be pressed (defaults to false if undefined) */
  altKey?: boolean;
  /** Whether the Shift key should be pressed (defaults to false if undefined) */
  shiftKey?: boolean;
  /** The function to execute when the shortcut is triggered */
  callback: () => void;
}

/**
 * A custom hook that provides keyboard shortcut functionality for the Documents View feature.
 * It registers keyboard shortcuts and executes callbacks when triggered.
 * 
 * @param shortcutConfig - A shortcut configuration object or an array of configuration objects
 * @param options - Additional options for controlling the behavior of shortcuts
 */
export const useKeyboardShortcut = (
  shortcutConfig: ShortcutConfig | ShortcutConfig[],
  options: {
    enabled?: boolean;
    ignoreInputs?: boolean;
  } = {}
): void => {
  const { enabled = true, ignoreInputs = true } = options;
  
  // Create a ref to store the shortcut configuration
  const shortcutsRef = useRef<ShortcutConfig[]>(
    Array.isArray(shortcutConfig) ? shortcutConfig : [shortcutConfig]
  );

  // Update the ref when the shortcut configuration changes
  useEffect(() => {
    shortcutsRef.current = Array.isArray(shortcutConfig) 
      ? shortcutConfig 
      : [shortcutConfig];
  }, [shortcutConfig]);

  // Create a keyboard event handler function using useCallback
  const handleKeyDown = useCallback((event: KeyboardEvent): void => {
    // If shortcuts are disabled, do nothing
    if (!enabled) return;

    // If ignoreInputs is true, don't trigger shortcuts when focus is on input elements
    if (ignoreInputs) {
      const target = event.target as HTMLElement;
      const tagName = target.tagName.toLowerCase();
      if (
        tagName === 'input' || 
        tagName === 'textarea' || 
        target.isContentEditable
      ) {
        return;
      }
    }

    // Check if any registered shortcut matches the current key combination
    const matchedShortcut = shortcutsRef.current.find(shortcut => {
      // Key match (case insensitive)
      const keyMatch = event.key.toLowerCase() === shortcut.key.toLowerCase();
      
      // Modifier keys match - use shortcut.ModifierKey || false to default to false when undefined
      const ctrlMatch = event.ctrlKey === (shortcut.ctrlKey || false);
      const altMatch = event.altKey === (shortcut.altKey || false);
      const shiftMatch = event.shiftKey === (shortcut.shiftKey || false);

      return keyMatch && ctrlMatch && altMatch && shiftMatch;
    });

    // If a match is found, prevent default behavior and execute the callback
    if (matchedShortcut) {
      event.preventDefault();
      matchedShortcut.callback();
    }
  }, [enabled, ignoreInputs]);

  // Use useEffect to add the keyboard event listener to the document
  useEffect(() => {
    // Only add the event listener if shortcuts are enabled
    if (enabled) {
      document.addEventListener('keydown', handleKeyDown);
    }

    // Clean up the event listener when the component unmounts
    return () => {
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [handleKeyDown, enabled]);
};
import React from 'react'; // version 18.x - Core React library for type definitions

/**
 * Enum for screen reader announcement priority levels
 */
export enum AnnouncementPriority {
  POLITE = 'polite',
  ASSERTIVE = 'assertive',
}

/**
 * Interface for ARIA attribute options
 */
export interface AriaAttributeOptions {
  invalid?: boolean;
  required?: boolean;
  describedBy?: string;
  labelledBy?: string;
  expanded?: boolean;
  hasPopup?: boolean | string;
  controls?: string;
  live?: string;
  busy?: boolean;
}

/**
 * Sets ARIA attributes on an element based on provided options
 * @param element - The element to set attributes on
 * @param options - The ARIA attributes to set
 */
export const setAriaAttributes = (
  element: HTMLElement | null,
  options: AriaAttributeOptions
): void => {
  if (!element) return;

  if (options.invalid !== undefined) {
    element.setAttribute('aria-invalid', options.invalid.toString());
  }
  
  if (options.required !== undefined) {
    element.setAttribute('aria-required', options.required.toString());
  }
  
  if (options.describedBy) {
    element.setAttribute('aria-describedby', options.describedBy);
  }
  
  if (options.labelledBy) {
    element.setAttribute('aria-labelledby', options.labelledBy);
  }
  
  if (options.expanded !== undefined) {
    element.setAttribute('aria-expanded', options.expanded.toString());
  }
  
  if (options.hasPopup !== undefined) {
    element.setAttribute('aria-haspopup', options.hasPopup.toString());
  }
  
  if (options.controls) {
    element.setAttribute('aria-controls', options.controls);
  }
  
  if (options.live) {
    element.setAttribute('aria-live', options.live);
  }
  
  if (options.busy !== undefined) {
    element.setAttribute('aria-busy', options.busy.toString());
  }
};

/**
 * Creates a focus trap within a container element, preventing focus from leaving the container
 * @param container - The container element to trap focus within
 * @returns Cleanup function to remove the focus trap
 */
export const trapFocus = (container: HTMLElement): (() => void) => {
  // Find all focusable elements within the container
  const focusableElements = getFocusableElements(container);
  
  if (focusableElements.length === 0) return () => {};
  
  // Store the element that had focus before trapping
  const previousActiveElement = document.activeElement as HTMLElement;
  
  // Focus the first focusable element in the container
  setFocus(focusableElements[0]);
  
  // Handle Tab key navigation
  const handleKeyDown = (event: KeyboardEvent) => {
    // Only handle Tab key
    if (event.key !== 'Tab') return;
    
    // If there are no focusable elements, do nothing
    if (focusableElements.length === 0) return;
    
    const firstFocusableElement = focusableElements[0];
    const lastFocusableElement = focusableElements[focusableElements.length - 1];
    
    // Shift + Tab (backward navigation)
    if (event.shiftKey) {
      if (document.activeElement === firstFocusableElement) {
        // If we're at the first element, cycle to the last element
        event.preventDefault();
        setFocus(lastFocusableElement);
      }
    } 
    // Tab (forward navigation)
    else {
      if (document.activeElement === lastFocusableElement) {
        // If we're at the last element, cycle to the first element
        event.preventDefault();
        setFocus(firstFocusableElement);
      }
    }
  };
  
  // Add event listener
  container.addEventListener('keydown', handleKeyDown);
  
  // Return cleanup function
  return () => {
    container.removeEventListener('keydown', handleKeyDown);
    
    // Restore focus to the previously active element
    if (previousActiveElement && previousActiveElement.focus) {
      setFocus(previousActiveElement);
    }
  };
};

/**
 * Gets all focusable elements within a container element
 * @param container - The container element to search within
 * @returns Array of focusable elements
 */
export const getFocusableElements = (container: HTMLElement): HTMLElement[] => {
  // Define selector for focusable elements
  const focusableSelector = [
    'button',
    'a[href]',
    'input',
    'select',
    'textarea',
    '[tabindex]:not([tabindex="-1"])',
    'details',
    'audio[controls]',
    'video[controls]',
    '[contenteditable]:not([contenteditable="false"])',
  ].join(',');
  
  // Query all focusable elements
  const elements = Array.from(
    container.querySelectorAll<HTMLElement>(focusableSelector)
  );
  
  // Filter out elements that are not visible or are disabled
  return elements.filter(element => 
    !element.hasAttribute('disabled') && 
    element.getAttribute('tabindex') !== '-1' &&
    isElementVisible(element)
  );
};

/**
 * Sets focus on an element with proper handling for screen readers
 * @param element - The element to focus
 * @param options - Focus options
 * @returns True if focus was set successfully, false otherwise
 */
export const setFocus = (
  element: HTMLElement | null,
  options?: FocusOptions
): boolean => {
  if (!element) return false;
  
  try {
    element.focus(options);
    return document.activeElement === element;
  } catch (error) {
    console.error('Error setting focus:', error);
    return false;
  }
};

/**
 * Announces a message to screen readers using an ARIA live region
 * @param message - The message to announce
 * @param priority - The announcement priority (polite or assertive)
 */
export const announceToScreenReader = (
  message: string,
  priority: AnnouncementPriority = AnnouncementPriority.POLITE
): void => {
  // Get or create live region
  const liveRegion = createHiddenLiveRegion();
  
  // Set the appropriate aria-live attribute
  liveRegion.setAttribute('aria-live', priority);
  
  // Clear the region first (to ensure announcement if the same text is provided multiple times)
  liveRegion.textContent = '';
  
  // Use setTimeout to ensure the clearing has taken effect
  setTimeout(() => {
    liveRegion.textContent = message;
    
    // Clear the announcement after it's been read (typical screen reader behavior)
    setTimeout(() => {
      liveRegion.textContent = '';
    }, 7000); // Most screen readers finish announcing within this time
  }, 50);
};

/**
 * Creates a visually hidden live region for screen reader announcements
 * @returns The created live region element
 */
export const createHiddenLiveRegion = (): HTMLElement => {
  // Check if a live region already exists
  const existingLiveRegion = document.getElementById('screen-reader-announcement');
  if (existingLiveRegion) return existingLiveRegion as HTMLElement;
  
  // Create a new live region element
  const liveRegion = document.createElement('div');
  liveRegion.id = 'screen-reader-announcement';
  liveRegion.setAttribute('role', 'status');
  liveRegion.setAttribute('aria-live', 'polite');
  liveRegion.setAttribute('aria-atomic', 'true');
  
  // Visually hide the element but keep it accessible to screen readers
  Object.assign(liveRegion.style, {
    position: 'absolute',
    width: '1px',
    height: '1px',
    padding: '0',
    margin: '-1px',
    overflow: 'hidden',
    clip: 'rect(0, 0, 0, 0)',
    whiteSpace: 'nowrap',
    borderWidth: '0'
  });
  
  // Append to the body
  document.body.appendChild(liveRegion);
  
  return liveRegion;
};

/**
 * Checks if an element is visible in the DOM
 * @param element - The element to check
 * @returns True if the element is visible, false otherwise
 */
export const isElementVisible = (element: HTMLElement): boolean => {
  const style = window.getComputedStyle(element);
  
  return style.display !== 'none' &&
         style.visibility !== 'hidden' &&
         style.opacity !== '0' &&
         element.offsetWidth > 0 &&
         element.offsetHeight > 0;
};
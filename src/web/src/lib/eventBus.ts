/**
 * A simple event bus implementation for the Documents View feature.
 * Provides publish-subscribe functionality to enable decoupled communication
 * between components through events.
 * 
 * @version 1.0.0
 */

/**
 * Type definition for event listener function
 */
type Listener = (data?: any) => void;

/**
 * Map of event types to sets of listener functions
 */
const listeners: Map<string, Set<Listener>> = new Map();

/**
 * Enum of document-related event types used throughout the application
 */
export enum DocumentEventType {
  DOCUMENT_LOADED = 'DOCUMENT_LOADED',
  METADATA_UPDATED = 'METADATA_UPDATED',
  DOCUMENT_PROCESSED = 'DOCUMENT_PROCESSED',
  DOCUMENT_UNPROCESSED = 'DOCUMENT_UNPROCESSED',
  DOCUMENT_TRASHED = 'DOCUMENT_TRASHED',
  PANEL_CHANGED = 'PANEL_CHANGED',
  HISTORY_LOADED = 'HISTORY_LOADED'
}

/**
 * Subscribes a listener function to a specific event type
 * 
 * @param eventType - The type of event to listen for
 * @param listener - The callback function to execute when the event is emitted
 * @returns An unsubscribe function that can be called to remove the listener
 */
const on = (eventType: string, listener: Listener): () => void => {
  // Get or create a set of listeners for this event type
  if (!listeners.has(eventType)) {
    listeners.set(eventType, new Set());
  }
  
  // Add the listener to the set
  const eventListeners = listeners.get(eventType)!;
  eventListeners.add(listener);
  
  // Return an unsubscribe function
  return () => {
    off(eventType, listener);
  };
};

/**
 * Unsubscribes a listener function from a specific event type
 * 
 * @param eventType - The type of event to unsubscribe from
 * @param listener - The listener function to remove
 */
const off = (eventType: string, listener: Listener): void => {
  // Check if we have listeners for this event type
  if (!listeners.has(eventType)) {
    return;
  }
  
  // Remove the listener from the set
  const eventListeners = listeners.get(eventType)!;
  eventListeners.delete(listener);
  
  // If no listeners remain, remove the event type from the map
  if (eventListeners.size === 0) {
    listeners.delete(eventType);
  }
};

/**
 * Emits an event with optional data to all subscribed listeners
 * 
 * @param eventType - The type of event to emit
 * @param data - Optional data to pass to the listeners
 */
const emit = (eventType: string, data?: any): void => {
  // Check if we have listeners for this event type
  if (!listeners.has(eventType)) {
    return;
  }
  
  // Call each listener with the provided data
  const eventListeners = listeners.get(eventType)!;
  eventListeners.forEach(listener => {
    try {
      listener(data);
    } catch (error) {
      // Prevent event propagation failures by catching errors
      console.error(`Error in event listener for ${eventType}:`, error);
    }
  });
};

/**
 * Subscribes a listener function that will be called only once
 * for a specific event type
 * 
 * @param eventType - The type of event to listen for
 * @param listener - The callback function to execute when the event is emitted
 * @returns An unsubscribe function that can be called to remove the listener
 */
const once = (eventType: string, listener: Listener): () => void => {
  // Create a wrapper that will call the listener once and then unsubscribe
  const wrapper = (data?: any) => {
    // Unsubscribe first to prevent potential recursive calls
    unsubscribe();
    // Call the original listener
    listener(data);
  };
  
  // Subscribe the wrapper
  const unsubscribe = on(eventType, wrapper);
  
  // Return the unsubscribe function
  return unsubscribe;
};

/**
 * Removes all listeners for a specific event type or all event types
 * 
 * @param eventType - Optional event type to clear listeners for.
 *                   If not provided, all listeners for all events are removed.
 */
const clear = (eventType?: string): void => {
  if (eventType) {
    // Clear all listeners for the specified event type
    listeners.delete(eventType);
  } else {
    // Clear all listeners for all event types
    listeners.clear();
  }
};

/**
 * Event bus object that provides publish-subscribe functionality
 * for document-related events
 */
export const EventBus = {
  on,
  off,
  emit,
  once,
  clear
};
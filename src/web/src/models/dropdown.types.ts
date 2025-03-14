/**
 * TypeScript interfaces and types for dropdown components used in the Documents View feature.
 * This file defines the data structures for dropdown controls with type-ahead filtering, 
 * keyboard navigation, and dependent field support.
 */

import { DropdownOption } from './api.types';

/**
 * Interface for dropdown component props.
 * Provides all necessary props for a dropdown control with type-ahead filtering
 * and support for dependent fields.
 */
export interface DropdownProps {
  name: string;                                 // Field name for form identification
  label: string;                                // Display label for the field
  value: string | number | null;                // Current selected value
  options: DropdownOption[];                    // Available options for the dropdown
  onChange: (value: string | number | null) => void; // Callback when value changes
  onBlur: () => void;                           // Callback when field loses focus
  error: string | null;                         // Error message if validation fails
  disabled: boolean;                            // Whether the field is disabled
  placeholder: string;                          // Placeholder text when no value is selected
  dependsOn: string | null;                     // Field name that this dropdown depends on
  isLoading: boolean;                           // Whether options are currently loading
  isReadOnly: boolean;                          // Whether the field is in read-only mode
}

/**
 * Interface for dropdown component internal state.
 * Manages the UI state for dropdown interactions.
 */
export interface DropdownState {
  isOpen: boolean;                              // Whether the dropdown menu is open
  inputValue: string;                           // Current text in the input field
  filteredOptions: DropdownOption[];            // Options filtered by input text
  highlightedIndex: number;                     // Index of currently highlighted option
  isLoading: boolean;                           // Whether options are currently loading
  error: string | null;                         // Current error state
}

/**
 * Interface for options menu component props.
 * Used for the dropdown menu that displays available options.
 */
export interface OptionsMenuProps {
  options: DropdownOption[];                    // Options to display in the menu
  highlightedIndex: number;                     // Index of currently highlighted option
  onSelect: (option: DropdownOption) => void;   // Callback when an option is selected
  onHighlight: (index: number) => void;         // Callback when highlighted option changes
  isLoading: boolean;                           // Whether options are currently loading
}

/**
 * Interface for navigation menu component props.
 * Used for the contextual navigation menu in the Document View.
 */
export interface NavigationMenuProps {
  options: NavigationOption[];                  // Available navigation options
  onSelect: (option: NavigationOption) => void; // Callback when an option is selected
  isOpen: boolean;                              // Whether the menu is open
  onClose: () => void;                          // Callback to close the menu
}

/**
 * Interface for navigation menu option data.
 * Defines the structure for navigation options in the ellipsis menu.
 */
export interface NavigationOption {
  id: string;                                   // Unique identifier
  label: string;                                // Display text
  url: string;                                  // Target URL for navigation
  icon: string | null;                          // Optional icon identifier
}

/**
 * Type definition for dropdown filtering function.
 * Filters dropdown options based on input text.
 */
export type FilterFunction = (options: DropdownOption[], inputValue: string) => DropdownOption[];

/**
 * Type definition for keyboard event handler function.
 * Handles keyboard navigation within the dropdown.
 */
export type KeyboardEventHandler = (event: React.KeyboardEvent<HTMLElement>) => void;

/**
 * Type definition for dropdown change handler function.
 * Processes value changes from the dropdown.
 */
export type DropdownChangeHandler = (value: string | number | null) => void;

/**
 * Enum for dropdown field types in document metadata.
 * Defines constants for the different types of dropdown fields.
 */
export enum DropdownFieldType {
  POLICY_NUMBER = 'policyNumber',
  LOSS_SEQUENCE = 'lossSequence',
  CLAIMANT = 'claimant',
  DOCUMENT_DESCRIPTION = 'documentDescription',
  ASSIGNED_TO = 'assignedTo',
  PRODUCER_NUMBER = 'producerNumber'
}
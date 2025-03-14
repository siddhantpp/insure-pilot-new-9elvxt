import React, { useState, useEffect, useRef } from 'react'; // React v18.x
import classNames from 'classnames'; // 2.x

import { DropdownProps } from '../../models/dropdown.types';
import { DropdownOption } from '../../models/api.types';
import { useOutsideClick } from '../../hooks/useOutsideClick';
import { useDebounce } from '../../hooks/useDebounce';
import OptionsMenu from './OptionsMenu';
import ValidationError from '../form/ValidationError';
import { setAriaAttributes } from '../../utils/accessibilityUtils';

/**
 * Filters dropdown options based on input text
 * @param options - Available dropdown options
 * @param inputValue - Text to filter by
 * @returns Filtered options array
 */
const filterOptions = (options: DropdownOption[], inputValue: string): DropdownOption[] => {
  if (!inputValue) return options;
  
  const lowercaseInput = inputValue.toLowerCase();
  return options.filter(option => 
    option.label.toLowerCase().includes(lowercaseInput)
  );
};

/**
 * Finds an option in the options array by its value
 * @param options - Available dropdown options
 * @param value - Value to find
 * @returns The matching option or undefined if not found
 */
const getOptionByValue = (options: DropdownOption[], value: string | number | null): DropdownOption | undefined => {
  if (value === null) return undefined;
  return options.find(option => option.value === value);
};

/**
 * A reusable dropdown control component with type-ahead filtering and keyboard navigation.
 * Used for document metadata fields in the Documents View feature.
 */
const DropdownControl: React.FC<DropdownProps> = ({
  name,
  label,
  value,
  options,
  onChange,
  onBlur,
  error,
  disabled = false,
  placeholder = 'Select...',
  dependsOn = null,
  isLoading = false,
  isReadOnly = false,
}) => {
  // Component state
  const [isOpen, setIsOpen] = useState<boolean>(false);
  const [inputValue, setInputValue] = useState<string>('');
  const [filteredOptions, setFilteredOptions] = useState<DropdownOption[]>(options);
  const [highlightedIndex, setHighlightedIndex] = useState<number>(-1);

  // Refs for DOM elements
  const containerRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);
  const menuRef = useRef<HTMLDivElement>(null);

  // Close dropdown when clicking outside
  useOutsideClick(containerRef, () => {
    if (isOpen) {
      setIsOpen(false);
    }
  });

  // Debounce input value changes for filtering
  const debouncedInputValue = useDebounce(inputValue, 300);

  // Update filtered options when input value changes
  useEffect(() => {
    if (debouncedInputValue !== undefined) {
      const filtered = filterOptions(options, debouncedInputValue);
      setFilteredOptions(filtered);
      setHighlightedIndex(filtered.length > 0 ? 0 : -1);
    }
  }, [debouncedInputValue, options]);

  // Update input value when selected value changes
  useEffect(() => {
    const selectedOption = getOptionByValue(options, value);
    if (selectedOption) {
      setInputValue(selectedOption.label);
    } else {
      setInputValue('');
    }
  }, [value, options]);

  // Reset state when dependent field changes
  useEffect(() => {
    if (dependsOn) {
      setInputValue('');
      setFilteredOptions(options);
      setHighlightedIndex(-1);
    }
  }, [dependsOn, options]);

  /**
   * Handles input field changes
   * @param e - Change event from input field
   */
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newInputValue = e.target.value;
    setInputValue(newInputValue);
    setIsOpen(true);
  };

  /**
   * Handles option selection from dropdown
   * @param option - The selected dropdown option
   */
  const handleOptionSelect = (option: DropdownOption) => {
    if (option.disabled) return;
    
    onChange(option.value);
    setIsOpen(false);
    setInputValue(option.label);
    
    // Focus back on input after selection
    if (inputRef.current) {
      inputRef.current.focus();
    }
  };

  /**
   * Handles keyboard navigation and interaction
   * @param e - Keyboard event from input field
   */
  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    // Do nothing if disabled or readonly
    if (disabled || isReadOnly) return;

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        if (!isOpen) {
          setIsOpen(true);
        } else if (filteredOptions.length > 0) {
          // Move to next option or cycle back to first
          const nextIndex = highlightedIndex >= filteredOptions.length - 1
            ? 0
            : highlightedIndex + 1;
          setHighlightedIndex(nextIndex);
        }
        break;
        
      case 'ArrowUp':
        e.preventDefault();
        if (!isOpen) {
          setIsOpen(true);
        } else if (filteredOptions.length > 0) {
          // Move to previous option or cycle to last
          const prevIndex = highlightedIndex <= 0
            ? filteredOptions.length - 1
            : highlightedIndex - 1;
          setHighlightedIndex(prevIndex);
        }
        break;
        
      case 'Enter':
        e.preventDefault();
        if (isOpen && highlightedIndex >= 0 && filteredOptions.length > 0) {
          handleOptionSelect(filteredOptions[highlightedIndex]);
        } else if (!isOpen) {
          setIsOpen(true);
        }
        break;
        
      case 'Escape':
        e.preventDefault();
        setIsOpen(false);
        break;
        
      case 'Tab':
        if (isOpen) {
          setIsOpen(false);
        }
        break;
        
      default:
        break;
    }
  };

  /**
   * Handles input field focus
   */
  const handleFocus = () => {
    if (!disabled && !isReadOnly) {
      // Don't open dropdown immediately on focus, just prepare for interaction
      setHighlightedIndex(-1);
    }
  };

  /**
   * Handles input field blur
   */
  const handleBlur = () => {
    // Give time for option selection to process before closing
    setTimeout(() => {
      if (onBlur) {
        onBlur();
      }
      
      // If input value doesn't match any option, reset it to match selected value
      const selectedOption = getOptionByValue(options, value);
      if (selectedOption && selectedOption.label !== inputValue) {
        setInputValue(selectedOption.label);
      } else if (!selectedOption && inputValue) {
        setInputValue('');
      }
    }, 200);
  };

  /**
   * Handles option highlighting on mouse hover
   * @param index - Index of the option to highlight
   */
  const handleHighlight = (index: number) => {
    setHighlightedIndex(index);
  };

  /**
   * Gets the display value for the input field based on component state
   * @returns The display text for the input field
   */
  const getDisplayValue = () => {
    // If read-only, just display the selected option's label
    if (isReadOnly) {
      const selectedOption = getOptionByValue(options, value);
      return selectedOption ? selectedOption.label : '';
    }
    
    // Otherwise use the input value
    return inputValue;
  };

  // Determine CSS classes
  const containerClasses = classNames('dropdown-control', {
    'dropdown-control-open': isOpen,
    'dropdown-control-disabled': disabled,
    'dropdown-control-readonly': isReadOnly,
    'dropdown-control-error': !!error,
    'dropdown-control-loading': isLoading,
  });

  // Set up ARIA attributes
  const inputId = `${name}-input`;
  const optionsId = `${name}-options`;
  const errorId = `${name}-error`;

  // Set aria attributes for the input element
  useEffect(() => {
    if (inputRef.current) {
      setAriaAttributes(inputRef.current, {
        invalid: !!error,
        required: false, // Set to true if required
        describedBy: error ? errorId : undefined,
        expanded: isOpen,
        hasPopup: 'listbox',
        controls: isOpen ? optionsId : undefined,
      });
    }
  }, [error, isOpen, errorId, optionsId]);

  return (
    <div 
      className={containerClasses} 
      ref={containerRef}
      data-testid={`dropdown-${name}`}
    >
      <label 
        className="dropdown-label" 
        htmlFor={inputId}
      >
        {label}
      </label>
      
      <div className="dropdown-input-container">
        <input
          id={inputId}
          ref={inputRef}
          className="dropdown-input"
          type="text"
          value={getDisplayValue()}
          onChange={handleInputChange}
          onFocus={handleFocus}
          onBlur={handleBlur}
          onKeyDown={handleKeyDown}
          placeholder={placeholder}
          disabled={disabled}
          readOnly={isReadOnly}
          autoComplete="off"
          role="combobox"
          aria-autocomplete="list"
          data-testid={`dropdown-input-${name}`}
        />
        
        <div 
          className="dropdown-indicator"
          aria-hidden="true"
        >
          {isLoading ? (
            <span className="dropdown-loading-indicator" />
          ) : (
            <span className={`dropdown-arrow ${isOpen ? 'dropdown-arrow-up' : 'dropdown-arrow-down'}`} />
          )}
        </div>
      </div>
      
      {isOpen && !disabled && !isReadOnly && (
        <OptionsMenu
          options={filteredOptions}
          highlightedIndex={highlightedIndex}
          onSelect={handleOptionSelect}
          onHighlight={handleHighlight}
          isLoading={isLoading}
        />
      )}
      
      {error && (
        <ValidationError
          id={errorId}
          error={error}
        />
      )}
    </div>
  );
};

export default DropdownControl;
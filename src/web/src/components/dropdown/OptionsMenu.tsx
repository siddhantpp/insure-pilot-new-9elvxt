import React, { useEffect, useRef } from 'react'; // React 18.x
import classNames from 'classnames'; // 2.x

import { OptionsMenuProps } from '../../models/dropdown.types';
import { DropdownOption } from '../../models/api.types';
import LoadingIndicator from '../common/LoadingIndicator';

/**
 * Scrolls the highlighted option into view if it's outside the visible area
 * of the container. This ensures the highlighted option is always visible
 * during keyboard navigation.
 * 
 * @param container - The scrollable container element
 * @param option - The highlighted option element to scroll into view
 */
const scrollOptionIntoView = (container: HTMLElement | null, option: HTMLElement | null): void => {
  if (!container || !option) return;

  const containerRect = container.getBoundingClientRect();
  const optionRect = option.getBoundingClientRect();

  const isAbove = optionRect.top < containerRect.top;
  const isBelow = optionRect.bottom > containerRect.bottom;

  if (isAbove) {
    container.scrollTop -= containerRect.top - optionRect.top;
  } else if (isBelow) {
    container.scrollTop += optionRect.bottom - containerRect.bottom;
  }
};

/**
 * A component that renders a dropdown options menu with support for keyboard navigation,
 * option highlighting, and selection. This component is used by the DropdownControl
 * to display filtered options for document metadata fields.
 * 
 * Features:
 * - Displays a list of selectable options
 * - Supports keyboard navigation through options
 * - Shows loading indicator when options are being fetched
 * - Displays empty state message when no options are available
 * - Fully accessible with ARIA attributes for screen readers
 */
const OptionsMenu: React.FC<OptionsMenuProps> = ({
  options,
  highlightedIndex,
  onSelect,
  onHighlight,
  isLoading,
}) => {
  // Ref for the options container element for scrolling
  const containerRef = useRef<HTMLDivElement>(null);
  
  // Ref for the currently highlighted option element
  const highlightedOptionRef = useRef<HTMLDivElement>(null);

  // Scroll highlighted option into view when it changes
  useEffect(() => {
    scrollOptionIntoView(containerRef.current, highlightedOptionRef.current);
  }, [highlightedIndex]);

  /**
   * Handle option selection when an option is clicked
   * @param option - The selected dropdown option
   */
  const handleOptionClick = (option: DropdownOption) => {
    // Don't select disabled options
    if (option.disabled) return;
    onSelect(option);
  };

  /**
   * Handle option highlighting when mouse hovers over an option
   * @param index - The index of the option to highlight
   */
  const handleOptionMouseEnter = (index: number) => {
    // Don't highlight disabled options
    if (options[index]?.disabled) return;
    onHighlight(index);
  };

  return (
    <div
      className="options-menu"
      ref={containerRef}
      role="listbox"
      aria-label="Options"
      tabIndex={-1}
      data-testid="options-menu"
    >
      {isLoading ? (
        <div className="options-menu-loading">
          <LoadingIndicator size="small" message="Loading options..." />
        </div>
      ) : options.length === 0 ? (
        <div className="options-menu-empty" role="alert">
          No options available
        </div>
      ) : (
        options.map((option, index) => {
          const isHighlighted = index === highlightedIndex;
          const optionClasses = classNames('options-menu-item', {
            'options-menu-item-highlighted': isHighlighted,
            'options-menu-item-disabled': option.disabled,
          });

          return (
            <div
              key={option.id}
              ref={isHighlighted ? highlightedOptionRef : null}
              className={optionClasses}
              role="option"
              aria-selected={isHighlighted}
              aria-disabled={option.disabled}
              onClick={() => handleOptionClick(option)}
              onMouseEnter={() => handleOptionMouseEnter(index)}
              data-testid={`option-${option.id}`}
              data-value={option.value}
            >
              {option.label}
            </div>
          );
        })
      )}
    </div>
  );
};

export default OptionsMenu;
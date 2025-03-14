import React, { useState, useEffect, useRef } from 'react'; // v18.x
import classNames from 'classnames'; // 2.x
import EllipsisButton from '../buttons/EllipsisButton';
import { Document } from '../../models/document.types';
import { NavigationOption, getNavigationOptions, navigateTo } from '../../services/navigationService';
import { useOutsideClick } from '../../hooks/useOutsideClick';

/**
 * Props interface for the NavigationMenu component
 */
export interface NavigationMenuProps {
  /**
   * The document containing metadata for navigation options
   */
  document: Document;
  
  /**
   * Additional CSS class names to apply to the component
   */
  className?: string;
}

/**
 * A dropdown menu component that provides contextual navigation options
 * based on document metadata. This component displays a list of navigation
 * links to related records such as producer view, policy view, and claimant view
 * when triggered by the ellipsis button.
 */
const NavigationMenu: React.FC<NavigationMenuProps> = ({
  document,
  className
}) => {
  // State for tracking if the menu is open
  const [isOpen, setIsOpen] = useState<boolean>(false);
  
  // State for storing navigation options
  const [navigationOptions, setNavigationOptions] = useState<NavigationOption[]>([]);
  
  // State for tracking if options are loading
  const [isLoading, setIsLoading] = useState<boolean>(true);
  
  // Reference to the menu container for click outside detection
  const menuRef = useRef<HTMLDivElement>(null);
  
  // Handle clicks outside the menu to close it
  useOutsideClick(menuRef, () => {
    if (isOpen) {
      setIsOpen(false);
    }
  });
  
  // Generate navigation options when document changes
  useEffect(() => {
    const fetchNavigationOptions = async () => {
      if (!document) {
        setNavigationOptions([]);
        setIsLoading(false);
        return;
      }
      
      setIsLoading(true);
      try {
        const options = await getNavigationOptions(document);
        setNavigationOptions(options);
      } catch (error) {
        console.error('Error fetching navigation options:', error);
        setNavigationOptions([]);
      } finally {
        setIsLoading(false);
      }
    };
    
    fetchNavigationOptions();
  }, [document]);
  
  // Toggle menu open/closed state
  const toggleMenu = () => {
    setIsOpen(prevState => !prevState);
  };
  
  // Handle option click - navigate to selected option
  const handleOptionClick = async (option: NavigationOption) => {
    try {
      await navigateTo(option);
      setIsOpen(false);
    } catch (error) {
      console.error('Error navigating to:', option, error);
    }
  };
  
  // Combine CSS classes
  const containerClasses = classNames(
    'navigation-menu',
    className
  );
  
  return (
    <div className={containerClasses} ref={menuRef}>
      <EllipsisButton
        onClick={toggleMenu}
        isOpen={isOpen}
        ariaLabel="Navigation options"
      />
      
      {isOpen && (
        <div 
          className="navigation-menu__dropdown"
          role="menu"
          aria-orientation="vertical"
          aria-labelledby="navigation-menu-button"
        >
          {isLoading ? (
            <div className="navigation-menu__loading">
              Loading options...
            </div>
          ) : navigationOptions.length > 0 ? (
            navigationOptions.map((option) => (
              <button
                key={`${option.targetType}-${option.targetId}`}
                className="navigation-menu__option"
                role="menuitem"
                onClick={() => handleOptionClick(option)}
              >
                {option.label}
              </button>
            ))
          ) : (
            <div className="navigation-menu__empty">
              No navigation options available
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default NavigationMenu;
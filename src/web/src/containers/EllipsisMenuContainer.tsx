import React from 'react';
import NavigationMenu from '../components/dropdown/NavigationMenu';
import { useDocumentContext } from '../context/DocumentContext';
import { navigateTo, NavigationOption } from '../services/navigationService';

/**
 * Props interface for the EllipsisMenuContainer component
 */
export interface EllipsisMenuContainerProps {
  /**
   * Additional CSS class names to apply to the component
   */
  className?: string;
}

/**
 * A container component that manages the ellipsis menu for contextual navigation
 * in the Documents View feature. This container connects the NavigationMenu component
 * with document context and navigation services.
 */
const EllipsisMenuContainer: React.FC<EllipsisMenuContainerProps> = ({ 
  className,
  ...rest
}) => {
  // Get document state from context
  const { state } = useDocumentContext();
  const { document } = state;
  
  // Handle navigation option selection
  const handleNavigate = async (option: NavigationOption) => {
    try {
      await navigateTo(option);
    } catch (error) {
      console.error('Navigation error:', error);
    }
  };
  
  // Render the NavigationMenu component with document from context
  return (
    <NavigationMenu
      document={document}
      className={className}
      onNavigate={handleNavigate}
      {...rest}
    />
  );
};

export default EllipsisMenuContainer;
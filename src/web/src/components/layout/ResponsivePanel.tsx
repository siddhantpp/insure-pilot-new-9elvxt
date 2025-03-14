import React, { ReactNode, useEffect, useRef, useState } from 'react'; // react 18.x
import classNames from 'classnames'; // ^2.3.2
import { useDocumentContext } from '../../context/DocumentContext';
import { PanelType } from '../../models/document.types';

/**
 * Props interface for the ResponsivePanel component
 */
export interface ResponsivePanelProps {
  children: ReactNode;
  className?: string;
  panelType: PanelType;
  isActive?: boolean;
  header?: ReactNode;
  footer?: ReactNode;
  onFocus?: () => void;
  tabIndex?: number;
}

/**
 * Custom hook to track window dimensions for responsive behavior
 * @returns Current window width and height
 */
function useWindowSize() {
  // Default to desktop dimensions for SSR
  const [windowSize, setWindowSize] = useState({
    width: typeof window !== 'undefined' ? window.innerWidth : 1200,
    height: typeof window !== 'undefined' ? window.innerHeight : 800,
  });

  useEffect(() => {
    function handleResize() {
      setWindowSize({
        width: window.innerWidth,
        height: window.innerHeight,
      });
    }
    
    // Add event listener only in browser environment
    if (typeof window !== 'undefined') {
      // Set initial size
      handleResize();
      
      // Listen for window resize events
      window.addEventListener('resize', handleResize);
      return () => window.removeEventListener('resize', handleResize);
    }
  }, []);

  return windowSize;
}

/**
 * A component that renders a panel with responsive behavior based on screen size
 * Adapts its layout and visibility based on viewport dimensions and active state
 * 
 * @param props - Component props
 * @returns Rendered responsive panel component
 */
const ResponsivePanel: React.FC<ResponsivePanelProps> = ({
  children,
  className = '',
  panelType,
  isActive = true,
  header,
  footer,
  onFocus,
  tabIndex = 0,
}) => {
  // Reference to panel element for focus management
  const panelRef = useRef<HTMLDivElement>(null);
  
  // Get window dimensions for responsive behavior
  const { width } = useWindowSize();
  
  // Access document context to determine active panel
  const { state } = useDocumentContext();
  
  // Combine prop-based active state with context-based active state
  const isActivePanel = isActive && (state.activePanel === panelType);
  
  // Determine device type based on screen width
  const isMobile = width < 768;
  const isTablet = width >= 768 && width < 1200;
  const isDesktop = width >= 1200;
  
  // Track whether to use mobile view behavior
  const [isMobileView, setIsMobileView] = useState(isMobile);
  
  // Update mobile view state when window size changes
  useEffect(() => {
    setIsMobileView(isMobile);
  }, [isMobile]);
  
  // Handle focus management when panel becomes active
  useEffect(() => {
    if (isActivePanel && panelRef.current && onFocus) {
      // Set focus to panel and trigger onFocus callback
      panelRef.current.focus();
      onFocus();
    }
  }, [isActivePanel, onFocus]);
  
  // Determine panel visibility based on active state and screen size
  const isVisible = !isMobileView || (isMobileView && isActivePanel);
  
  // Determine CSS classes based on panel type, screen size, and state
  const panelClasses = classNames(
    'responsive-panel',
    `panel-type-${panelType.toLowerCase()}`,
    {
      'panel-active': isActivePanel,
      'panel-inactive': !isActivePanel,
      'panel-mobile': isMobileView,
      'panel-tablet': isTablet,
      'panel-desktop': isDesktop,
      'panel-hidden': !isVisible,
    },
    className
  );
  
  // Don't render if panel should be hidden on mobile
  if (isMobileView && !isActivePanel) {
    return null;
  }
  
  // Render panel with appropriate structure and content
  return (
    <div 
      ref={panelRef}
      className={panelClasses}
      role="region"
      aria-label={`${panelType === PanelType.METADATA ? 'Metadata' : 'History'} panel`}
      tabIndex={tabIndex}
      aria-hidden={!isVisible}
    >
      {header && (
        <div className="panel-header" role="heading" aria-level={2}>
          {header}
        </div>
      )}
      <div className="panel-content">
        {children}
      </div>
      {footer && (
        <div className="panel-footer">
          {footer}
        </div>
      )}
    </div>
  );
};

export default ResponsivePanel;
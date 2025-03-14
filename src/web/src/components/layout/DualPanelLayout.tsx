import React, { ReactNode, useEffect, useState, useRef } from 'react'; // react 18.x
import classNames from 'classnames'; // ^2.3.2
import ResponsivePanel from './ResponsivePanel';
import { useDocumentContext } from '../../context/DocumentContext';
import { PanelType } from '../../models/document.types';

/**
 * Props interface for the DualPanelLayout component
 */
export interface DualPanelLayoutProps {
  leftPanel: ReactNode;
  rightPanel: ReactNode;
  className?: string;
  initialFocus?: PanelType;
  onPanelChange?: (panel: PanelType) => void;
}

/**
 * Custom hook to track window dimensions for responsive behavior
 * @returns Current window dimensions and device type flags
 */
function useWindowSize() {
  const [windowSize, setWindowSize] = useState({
    width: typeof window !== 'undefined' ? window.innerWidth : 1200,
    height: typeof window !== 'undefined' ? window.innerHeight : 800,
    isMobile: false,
    isTablet: false
  });

  useEffect(() => {
    function handleResize() {
      const width = window.innerWidth;
      const height = window.innerHeight;
      
      setWindowSize({
        width,
        height,
        isMobile: width < 768,
        isTablet: width >= 768 && width < 1200
      });
    }
    
    if (typeof window !== 'undefined') {
      handleResize();
      window.addEventListener('resize', handleResize);
      return () => window.removeEventListener('resize', handleResize);
    }
  }, []);

  return windowSize;
}

/**
 * Handles focus management between panels
 * 
 * @param panelType Panel type to focus
 */
function handlePanelFocus(panelType: PanelType) {
  // This function is exported for external use
  // Implementation will set focus to the appropriate panel element
  // when called from outside this component
}

/**
 * A component that renders a dual-panel layout with document display on the left and metadata/history on the right
 * 
 * @param props Component props
 * @returns Rendered dual-panel layout component
 */
const DualPanelLayout: React.FC<DualPanelLayoutProps> = ({
  leftPanel,
  rightPanel,
  className = '',
  initialFocus = PanelType.METADATA,
  onPanelChange
}) => {
  // Access document context to determine active panel type
  const { state, setActivePanel } = useDocumentContext();
  
  // Get window dimensions for responsive behavior
  const { isMobile, isTablet } = useWindowSize();
  
  // Track which panel is visible in mobile view (document or metadata/history)
  const [mobileActiveSection, setMobileActiveSection] = useState<'document' | 'metadata'>('metadata');
  
  // Create refs for panel elements (for focus management)
  const leftPanelRef = useRef<HTMLDivElement>(null);
  const rightPanelRef = useRef<HTMLDivElement>(null);
  
  // Set initial focus when component mounts
  useEffect(() => {
    if (initialFocus && initialFocus !== state.activePanel) {
      setActivePanel(initialFocus);
    }
  }, [initialFocus, state.activePanel, setActivePanel]);
  
  // Notify parent of panel changes
  useEffect(() => {
    if (onPanelChange) {
      onPanelChange(state.activePanel);
    }
  }, [state.activePanel, onPanelChange]);
  
  // Handle focus management between panels
  const focusPanel = (section: 'document' | 'metadata') => {
    if (section === 'document') {
      leftPanelRef.current?.focus();
    } else {
      rightPanelRef.current?.focus();
    }
    
    if (isMobile) {
      setMobileActiveSection(section);
    }
  };
  
  // Determine layout classes based on screen size and active panel
  const layoutClasses = classNames(
    'dual-panel-layout',
    {
      'layout-mobile': isMobile,
      'layout-tablet': isTablet,
      'layout-desktop': !isMobile && !isTablet
    },
    className
  );
  
  // For mobile: create a tab-like interface to switch between panels
  const mobileTabs = isMobile && (
    <div className="mobile-tabs" role="tablist">
      <button 
        className={classNames('tab-button', { 'active': mobileActiveSection === 'document' })}
        onClick={() => focusPanel('document')}
        role="tab"
        aria-selected={mobileActiveSection === 'document'}
        aria-controls="document-panel"
        id="document-tab"
      >
        Document
      </button>
      <button 
        className={classNames('tab-button', { 'active': mobileActiveSection === 'metadata' })}
        onClick={() => focusPanel('metadata')}
        role="tab"
        aria-selected={mobileActiveSection === 'metadata'}
        aria-controls="metadata-panel"
        id="metadata-tab"
      >
        {state.activePanel === PanelType.METADATA ? 'Metadata' : 'History'}
      </button>
    </div>
  );
  
  return (
    <div className={layoutClasses} role="main">
      {mobileTabs}
      
      <div 
        ref={leftPanelRef}
        id="document-panel"
        className={classNames('panel-container left-panel', {
          'panel-hidden': isMobile && mobileActiveSection !== 'document'
        })}
        role={isMobile ? 'tabpanel' : undefined}
        aria-labelledby={isMobile ? 'document-tab' : undefined}
        tabIndex={0}
        aria-hidden={isMobile && mobileActiveSection !== 'document'}
      >
        {leftPanel}
      </div>
      
      <div 
        ref={rightPanelRef}
        id="metadata-panel"
        className={classNames('panel-container right-panel', {
          'panel-hidden': isMobile && mobileActiveSection !== 'metadata'
        })}
        role={isMobile ? 'tabpanel' : undefined}
        aria-labelledby={isMobile ? 'metadata-tab' : undefined}
        tabIndex={0}
        aria-hidden={isMobile && mobileActiveSection !== 'metadata'}
      >
        {rightPanel}
      </div>
    </div>
  );
};

export { handlePanelFocus };
export default DualPanelLayout;
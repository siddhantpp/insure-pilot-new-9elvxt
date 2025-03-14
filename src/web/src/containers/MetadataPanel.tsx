import React, { useState } from 'react'; // React v18.x
import classNames from 'classnames'; // 2.3.2

import MetadataField from '../components/metadata/MetadataField';
import ProcessButton from '../components/buttons/ProcessButton';
import TrashButton from '../components/buttons/TrashButton';
import EllipsisButton from '../components/buttons/EllipsisButton';
import SaveIndicator from '../components/metadata/SaveIndicator';
import NavigationMenu from '../components/dropdown/NavigationMenu';
import { useDocumentContext } from '../context/DocumentContext';
import { useMetadataForm } from '../hooks/useMetadataForm';
import { MetadataFieldName, METADATA_FIELD_CONFIGS } from '../models/metadata.types';
import { MetadataPanelProps } from '../models/document.types';

/**
 * Container component for document metadata management
 * 
 * This component displays and manages the right panel of the Documents View feature,
 * providing a comprehensive interface for viewing and editing document metadata fields,
 * processing documents, and accessing document history and contextual navigation options.
 * 
 * @param props - Component props
 * @returns Rendered metadata panel component
 */
const MetadataPanel: React.FC<MetadataPanelProps> = ({
  document,
  onMetadataChange,
  onProcessDocument,
  onTrashDocument,
  onViewHistory,
  isSaving,
  saveError,
}) => {
  // State for navigation menu visibility
  const [menuOpen, setMenuOpen] = useState(false);
  
  // Get document context for access to global document state
  const { state } = useDocumentContext();
  
  // Get metadata form state and actions from custom hook
  const {
    values,
    errors,
    options,
    isLoading,
    handleFieldChange,
    handleFieldBlur,
    isFieldDisabled,
    getFieldOptions
  } = useMetadataForm(
    document.metadata, 
    document.isProcessed
  );
  
  /**
   * Toggles the visibility of the navigation menu
   */
  const handleMenuToggle = () => {
    setMenuOpen(!menuOpen);
  };
  
  /**
   * Handles navigation to related records
   * @param url - URL to navigate to
   */
  const handleNavigate = (url: string) => {
    setMenuOpen(false);
    window.location.href = url;
  };
  
  // Sort metadata fields by order property for consistent display
  const sortedFieldConfigs = [...METADATA_FIELD_CONFIGS].sort((a, b) => a.order - b.order);
  
  return (
    <div className={classNames('metadata-panel', { 'is-processed': document.isProcessed })}>
      {/* Metadata fields section */}
      <div className="metadata-fields">
        {sortedFieldConfigs.map((fieldConfig) => (
          <MetadataField
            key={fieldConfig.name}
            fieldName={fieldConfig.name}
            label={fieldConfig.label}
            required={fieldConfig.required}
            className="metadata-field"
          />
        ))}
      </div>
      
      {/* Process button - uses context to access document state and processing actions */}
      <ProcessButton className="process-button" />
      
      {/* Document actions row */}
      <div className="document-actions">
        <button
          className="history-link"
          onClick={onViewHistory}
          aria-label="View document history"
          data-testid="document-history-link"
        >
          Document History
        </button>
        
        <div className="action-buttons-group">
          {/* Ellipsis button for navigation menu */}
          <EllipsisButton
            onClick={handleMenuToggle}
            isOpen={menuOpen}
            className="ellipsis-button"
            aria-label="Show navigation options"
          />
          
          {/* Trash button */}
          <TrashButton
            documentId={document.id}
            onTrashComplete={onTrashDocument}
            className="trash-button"
          />
        </div>
      </div>
      
      {/* Conditional rendering of navigation menu */}
      {menuOpen && (
        <NavigationMenu
          document={document}
          className="navigation-menu"
        />
      )}
      
      {/* Save indicator shows saving status and errors */}
      <SaveIndicator
        isSaving={isSaving}
        saveError={saveError}
        className="save-indicator"
      />
    </div>
  );
};

export default MetadataPanel;
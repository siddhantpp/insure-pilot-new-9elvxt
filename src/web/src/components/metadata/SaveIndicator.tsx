import React from 'react'; // React 18.x
import StatusIndicator from '../common/StatusIndicator';

/**
 * Props interface for the SaveIndicator component
 */
export interface SaveIndicatorProps {
  /**
   * Whether a save operation is currently in progress
   */
  isSaving?: boolean;
  
  /**
   * Error message when a save operation fails, null when no error
   */
  saveError?: string | null;
  
  /**
   * Additional CSS classes to apply to the component
   */
  className?: string;
  
  /**
   * Whether to show the "Saved" indicator when not saving and no errors
   * @default true
   */
  showSavedStatus?: boolean;
}

/**
 * A component that displays the current saving status of document metadata in the Documents View feature.
 * It provides visual feedback to users about whether their metadata changes are being saved,
 * have been successfully saved, or encountered an error during saving.
 * 
 * @example
 * // When saving is in progress
 * <SaveIndicator isSaving={true} />
 * 
 * @example
 * // When save completed successfully
 * <SaveIndicator isSaving={false} />
 * 
 * @example
 * // When save failed with an error
 * <SaveIndicator saveError="Failed to save metadata: Network error" />
 */
const SaveIndicator: React.FC<SaveIndicatorProps> = ({
  isSaving = false,
  saveError = null,
  className = '',
  showSavedStatus = true
}) => {
  // Don't show anything if no save is happening, no error, and savedStatus is not required
  if (!isSaving && !saveError && !showSavedStatus) {
    return null;
  }

  let status: 'saving' | 'error' | 'success';
  let message: string;

  // Determine the appropriate status and message based on the current state
  if (isSaving) {
    status = 'saving';
    message = 'Saving...';
  } else if (saveError) {
    status = 'error';
    message = saveError;
  } else if (showSavedStatus) {
    status = 'success';
    message = 'Saved';
  } else {
    // This case should never happen due to the early return above
    return null;
  }

  return (
    <StatusIndicator 
      status={status} 
      message={message} 
      className={className}
      data-testid="metadata-save-indicator"
    />
  );
};

export default SaveIndicator;
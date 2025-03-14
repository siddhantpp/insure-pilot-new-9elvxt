import React from 'react'; // React 18.x

/**
 * Valid status types for the StatusIndicator component
 */
type StatusType = 'success' | 'error' | 'warning' | 'info' | 'saving' | 'saved';

/**
 * Props for the StatusIndicator component
 */
interface StatusIndicatorProps {
  /**
   * The type of status to display. Controls the styling and icon.
   * @default 'info'
   */
  status?: StatusType;
  
  /**
   * The message text to display
   */
  message: string;
  
  /**
   * Additional CSS classes to apply to the component
   */
  className?: string;
}

/**
 * A component that displays status messages with appropriate styling based on the status type.
 * Used throughout the Documents View feature to provide visual feedback about operation results and system states.
 * 
 * @example
 * // Success status
 * <StatusIndicator status="success" message="Document saved successfully" />
 * 
 * @example
 * // Error status
 * <StatusIndicator status="error" message="Failed to save document" />
 * 
 * @example
 * // In-progress saving status
 * <StatusIndicator status="saving" message="Saving..." />
 */
const StatusIndicator: React.FC<StatusIndicatorProps> = ({
  status = 'info',
  message,
  className = '',
}) => {
  if (!message) {
    console.warn('StatusIndicator: No message provided');
    return null;
  }

  /**
   * Returns the appropriate icon element for the given status type
   */
  const getStatusIcon = (statusType: StatusType): JSX.Element => {
    switch (statusType) {
      case 'success':
      case 'saved':
        return <span className="status-indicator-icon" aria-hidden="true">✓</span>;
      case 'error':
        return <span className="status-indicator-icon" aria-hidden="true">✕</span>;
      case 'warning':
        return <span className="status-indicator-icon" aria-hidden="true">!</span>;
      case 'saving':
        return <span className="status-indicator-icon" aria-hidden="true">⟳</span>;
      case 'info':
      default:
        return <span className="status-indicator-icon" aria-hidden="true">ℹ</span>;
    }
  };

  /**
   * Returns the appropriate ARIA attributes for the given status type
   */
  const getAriaAttributes = (statusType: StatusType): React.AriaAttributes & { role?: string } => {
    switch (statusType) {
      case 'error':
        return { 'aria-live': 'assertive', role: 'alert' };
      case 'warning':
        return { 'aria-live': 'polite', role: 'status' };
      case 'success':
      case 'saved':
      case 'info':
      case 'saving':
      default:
        return { 'aria-live': 'polite', role: 'status' };
    }
  };

  // Construct CSS class names
  const baseClass = 'status-indicator';
  const statusClass = `${baseClass}-${status}`;
  const combinedClassName = `${baseClass} ${statusClass} ${className}`.trim();
  
  // Get appropriate ARIA attributes
  const ariaAttributes = getAriaAttributes(status);

  return (
    <div className={combinedClassName} {...ariaAttributes} data-testid={`status-indicator-${status}`}>
      {getStatusIcon(status)}
      <span className="status-indicator-message">{message}</span>
    </div>
  );
};

export default StatusIndicator;
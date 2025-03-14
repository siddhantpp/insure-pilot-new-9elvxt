import React from 'react'; // React 18.x

interface LoadingIndicatorProps {
  /**
   * Size variant of the loading spinner
   * @default 'medium'
   */
  size?: 'small' | 'medium' | 'large';
  
  /**
   * Message to display below the spinner
   * @default 'Loading...'
   */
  message?: string;
  
  /**
   * Whether the loading indicator should cover the full screen
   * @default false
   */
  fullScreen?: boolean;
  
  /**
   * Additional CSS classes to apply to the container
   */
  className?: string;
}

/**
 * A component that displays a loading spinner with optional text to indicate
 * that content is being loaded or an operation is in progress.
 * 
 * Used throughout the Documents View feature to provide visual feedback
 * during asynchronous operations like document loading and metadata updates.
 */
const LoadingIndicator: React.FC<LoadingIndicatorProps> = ({
  size = 'medium',
  message = 'Loading...',
  fullScreen = false,
  className = '',
}) => {
  // Determine the CSS classes based on props
  const containerClasses = [
    'loading-indicator',
    fullScreen ? 'loading-indicator-fullscreen' : '',
    className,
  ].filter(Boolean).join(' ');

  const spinnerClasses = [
    'loading-indicator-spinner',
    `loading-indicator-spinner-${size}`,
  ].join(' ');

  return (
    <div className={containerClasses} aria-live="polite" role="status">
      <div className={spinnerClasses} aria-hidden="true" />
      {message && <div className="loading-indicator-message">{message}</div>}
    </div>
  );
};

export default LoadingIndicator;
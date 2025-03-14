import React from 'react';
import ErrorMessage from './ErrorMessage';
import { formatErrorForLogging } from '../../utils/errorUtils';

/**
 * Props for the fallback component that will be rendered when an error occurs
 */
export interface FallbackProps {
  /**
   * The error that was caught
   */
  error: Error | null;
  
  /**
   * Function to reset the error state
   */
  resetError: () => void;
}

/**
 * Props for the ErrorBoundary component
 */
export interface ErrorBoundaryProps {
  /**
   * Custom component to render when an error occurs
   */
  FallbackComponent?: React.ComponentType<FallbackProps>;
  
  /**
   * Callback function called when an error is caught
   */
  onError?: (error: Error, errorInfo: React.ErrorInfo) => void;
}

/**
 * State for the ErrorBoundary component
 */
interface ErrorBoundaryState {
  /**
   * Whether an error has occurred
   */
  hasError: boolean;
  
  /**
   * The error that was caught, if any
   */
  error: Error | null;
}

/**
 * A React error boundary component that catches JavaScript errors in its child component tree,
 * logs those errors, and displays a fallback UI instead of crashing the entire application.
 */
class ErrorBoundary extends React.Component<
  React.PropsWithChildren<ErrorBoundaryProps>,
  ErrorBoundaryState
> {
  constructor(props: React.PropsWithChildren<ErrorBoundaryProps>) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
    };
  }

  /**
   * React lifecycle method that updates state when an error occurs
   */
  static getDerivedStateFromError(error: Error): ErrorBoundaryState {
    // Update state so the next render will show the fallback UI
    return {
      hasError: true,
      error,
    };
  }

  /**
   * React lifecycle method called after an error has been thrown by a descendant component
   */
  componentDidCatch(error: Error, errorInfo: React.ErrorInfo): void {
    // Format the error for logging
    const formattedError = formatErrorForLogging(error);
    
    // Log the error and component stack to the console
    console.error('Error caught by ErrorBoundary:', formattedError);
    console.error('Component stack:', errorInfo.componentStack);
    
    // Call the onError prop if provided
    this.props.onError?.(error, errorInfo);
  }

  /**
   * Resets the error state to allow retry
   */
  resetError = (): void => {
    this.setState({
      hasError: false,
      error: null,
    });
  };

  render(): React.ReactNode {
    const { hasError, error } = this.state;
    const { children, FallbackComponent } = this.props;

    if (hasError) {
      // If a custom fallback component is provided, use it
      if (FallbackComponent) {
        return <FallbackComponent error={error} resetError={this.resetError} />;
      }
      
      // Otherwise, use the default ErrorMessage component
      return (
        <div className="error-boundary" role="alert" aria-live="assertive">
          <ErrorMessage error={error} />
          <button 
            className="error-boundary-reset" 
            onClick={this.resetError}
            aria-label="Try again"
          >
            Try again
          </button>
        </div>
      );
    }

    // If there's no error, render the children
    return children;
  }
}

export default ErrorBoundary;
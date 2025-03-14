import React from 'react'; // React 18.x - Core React library for building user interfaces
import ReactDOM from 'react-dom/client'; // React DOM rendering library for web applications
import App from './App'; // Main application component that provides routing and context providers
import reportWebVitals from './reportWebVitals'; // Function to report performance metrics for monitoring
import styles from './styles/index.css'; // Global styles for the application

/**
 * Renders the React application to the DOM
 */
const renderApp = (): void => {
  // LD1: Get the root element from the DOM
  const rootElement = document.getElementById('root');

  // LD1: Check if rootElement exists before proceeding
  if (!rootElement) {
    console.error('Root element with ID "root" not found in the document.');
    return;
  }

  // LD1: Create a React root using ReactDOM.createRoot
  const root = ReactDOM.createRoot(rootElement);

  // LD1: Render the App component wrapped in React.StrictMode for development checks
  root.render(
    <React.StrictMode>
      <App />
    </React.StrictMode>
  );

  // LD1: Apply accessibility attributes to the root element
  rootElement.setAttribute('role', 'application');
  rootElement.setAttribute('aria-label', 'Documents View');
};

// LD1: Call renderApp function to render the application
renderApp();

// LD1: Report web vitals for performance monitoring
reportWebVitals((metric) => {
  // LD1: Log web vitals to console for debugging
  console.log(metric);

  // LD1: Send web vitals to a monitoring service (e.g., Google Analytics, New Relic)
  // LD1: Example: sendToAnalytics(metric);
});
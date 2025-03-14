import { ReportHandler, getCLS, getFID, getFCP, getLCP, getTTFB } from 'web-vitals'; // web-vitals ^3.3.1

/**
 * Reports web vitals metrics to a specified handler or logs them to console.
 * This function is used to measure and report performance metrics of the Documents View
 * feature to ensure it meets the performance requirements specified in the technical
 * specifications.
 * 
 * Collects the following Core Web Vitals metrics:
 * - CLS (Cumulative Layout Shift): Measures visual stability
 * - FID (First Input Delay): Measures interactivity
 * - FCP (First Contentful Paint): Measures perceived load speed
 * - LCP (Largest Contentful Paint): Measures loading performance
 * - TTFB (Time to First Byte): Measures time until the first byte of the page is received
 * 
 * These metrics are critical for meeting the performance SLAs defined in the technical specifications,
 * where document loading must complete within 3 seconds and UI interactions must respond within 100ms.
 * 
 * @param onPerfEntry - Optional callback function to handle performance metric reports
 */
const reportWebVitals = (onPerfEntry?: ReportHandler): void => {
  if (onPerfEntry && typeof onPerfEntry === 'function') {
    // Import the web-vitals library functions
    // Each function will measure its respective metric and report it via the callback
    getCLS(onPerfEntry);  // Cumulative Layout Shift - measures visual stability
    getFID(onPerfEntry);  // First Input Delay - measures interactivity
    getFCP(onPerfEntry);  // First Contentful Paint - measures perceived load speed
    getLCP(onPerfEntry);  // Largest Contentful Paint - measures loading performance
    getTTFB(onPerfEntry); // Time to First Byte - measures resource load time
  }
};

export default reportWebVitals;
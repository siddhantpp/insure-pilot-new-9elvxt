# Documents View Frontend

The frontend implementation of the Documents View feature for Insure Pilot, providing a dedicated, full-screen environment for users to review, process, and manage insurance-related documents.

## Overview

This React-based application implements the user interface for the Documents View feature, featuring a dual-panel layout with document display on the left and metadata management on the right. It integrates with Adobe Acrobat PDF Viewer for document rendering and provides a comprehensive set of document management capabilities.

## Key Features

- Full-screen lightbox interface with Adobe Acrobat PDF viewer integration
- Dual-panel layout with document display and metadata management
- Type-ahead filtering for dropdown fields with dynamic dependencies
- Document processing actions (mark as processed, trash document)
- Document history and audit trail viewing
- Contextual navigation to related records
- Comprehensive error handling and validation
- Accessibility compliance with WCAG 2.1 AA standards

## Technology Stack

- React 18.x with TypeScript
- Adobe Acrobat PDF Viewer SDK
- React Query for data fetching
- Axios for API communication
- React Router for navigation
- Jest and React Testing Library for testing

## Project Structure

```
src/
├── assets/         # Static assets (images, fonts)
├── components/     # Reusable UI components
│   ├── buttons/    # Button components
│   ├── common/     # Common utility components
│   ├── document/   # Document-specific components
│   ├── dropdown/   # Dropdown and selection components
│   ├── form/       # Form input components
│   ├── history/    # History display components
│   ├── layout/     # Layout components
│   ├── metadata/   # Metadata field components
│   └── pdf/        # PDF viewer components
├── containers/     # Container components that manage state
├── context/        # React context providers
├── hooks/          # Custom React hooks
├── lib/            # Utility libraries
├── models/         # TypeScript type definitions
├── pages/          # Page components
├── services/       # API service functions
├── styles/         # CSS styles
├── tests/          # Test files
├── utils/          # Utility functions
├── views/          # View components
├── App.tsx         # Main application component
└── index.tsx       # Application entry point
```

## Getting Started

### Prerequisites

- Node.js 16.x or higher
- npm 8.x or higher
- Access to backend API (running locally or in development environment)

### Installation

1. Clone the repository
   ```bash
   git clone https://github.com/your-org/insure-pilot.git
   cd insure-pilot/src/web
   ```

2. Install dependencies
   ```bash
   npm install
   ```

3. Configure environment variables
   ```bash
   cp .env.example .env
   # Edit .env with appropriate values
   ```

4. Start the development server
   ```bash
   npm start
   ```

The application will be available at http://localhost:3000.

## Available Scripts

- `npm start` - Starts the development server
- `npm run build` - Builds the app for production
- `npm test` - Runs the test suite
- `npm run test:coverage` - Runs tests with coverage report
- `npm run lint` - Lints the codebase
- `npm run lint:fix` - Fixes linting issues automatically
- `npm run format` - Formats code with Prettier
- `npm run typecheck` - Checks TypeScript types
- `npm run analyze` - Analyzes bundle size
- `npm run ci` - Runs the CI pipeline locally (lint, typecheck, test)

## Development Guidelines

### Code Style

This project follows strict coding standards enforced by ESLint and Prettier. All code should pass linting and formatting checks before being committed.

### Component Structure

- **Components** - Reusable UI elements with minimal state
- **Containers** - Components that manage state and coordinate child components
- **Hooks** - Custom hooks for shared logic
- **Context** - Global state management

### Testing

All components should have appropriate tests:

- Unit tests for utility functions and hooks
- Component tests for UI components
- Integration tests for complex workflows

Test files should be placed in the `tests` directory, mirroring the structure of the source code.

## Key Components

### DocumentViewerContainer

The main container component that coordinates the document viewing experience. It manages the overall state and interactions between child components.

### DocumentDisplay

Handles the rendering of PDF documents using the Adobe Acrobat PDF Viewer SDK in the left panel.

### MetadataPanel

Manages the display and editing of document metadata in the right panel, including field validation and saving.

### HistoryPanel

Displays the document history and audit trail in the right panel when selected.

### DropdownControl

Provides type-ahead filtering for dropdown fields with support for dependent field relationships.

## Adobe PDF Viewer Integration

This application integrates with the Adobe Acrobat PDF Viewer SDK for document rendering. The integration is handled through the `adobeViewer.ts` utility and the `PdfViewer` component.

The viewer provides the following capabilities:

- Document rendering with high fidelity
- Page navigation for multi-page documents
- Zoom and fit controls
- Document information display

Refer to the [Adobe DC View SDK documentation](https://www.adobe.io/apis/documentcloud/dcsdk/docs.html) for more details on the integration.

## API Integration

The application communicates with the backend API through service functions in the `services` directory. These functions use Axios for HTTP requests and React Query for data fetching and caching.

Key API endpoints used:

- `GET /api/documents/{id}` - Retrieve document details
- `PUT /api/documents/{id}/metadata` - Update document metadata
- `POST /api/documents/{id}/process` - Mark document as processed
- `POST /api/documents/{id}/trash` - Move document to trash
- `GET /api/documents/{id}/history` - Retrieve document history

## Accessibility

This application is designed to meet WCAG 2.1 AA accessibility standards. Key accessibility features include:

- Keyboard navigation for all interactive elements
- ARIA attributes for screen reader support
- Sufficient color contrast for text and UI elements
- Focus management for modal dialogs and dynamic content
- Error messages that are accessible to screen readers

Accessibility testing is performed using axe-core and manual testing with screen readers.

## Troubleshooting

### Common Issues

**PDF Viewer Not Loading**

Ensure that the Adobe PDF Viewer SDK is properly loaded and that the document URL is accessible.

**API Connection Errors**

Check that the backend API is running and that the proxy configuration in `package.json` is correct.

**Type Errors**

Run `npm run typecheck` to identify and fix TypeScript errors.

**Test Failures**

Ensure that all dependencies are installed and that the test environment is properly configured.

## Additional Resources

- [Main Project Documentation](../../README.md)
- [Development Guide](../../docs/development.md)
- [API Documentation](../../docs/api.md)
- [Architecture Documentation](../../docs/architecture.md)
- [React Documentation](https://reactjs.org/docs/getting-started.html)
- [TypeScript Documentation](https://www.typescriptlang.org/docs/)

## Contributing

Please refer to the [Development Guide](../../docs/development.md) for detailed information on the development workflow, coding standards, and contribution process.

## License

This project is licensed under the MIT License - see the [LICENSE](../../LICENSE) file for details.
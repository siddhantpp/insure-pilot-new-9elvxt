# Documents View for Insure Pilot

A dedicated, full-screen environment for users to review, process, and manage insurance-related documents.

## Overview

The Documents View feature provides a comprehensive solution for document management within Insure Pilot. It addresses the critical business need for efficient document handling within insurance operations, where document review and metadata management are frequent, high-volume activities.

Key features include:

- Full-screen lightbox interface with dual-panel layout
- Adobe Acrobat PDF viewer integration for document display
- Comprehensive metadata management with dynamic dropdown fields
- Document processing workflow (mark as processed, trash)
- Document history and audit trail
- Contextual navigation to related records
- Type-ahead filtering for efficient data entry
- Accessibility compliance with WCAG 2.1 AA standards

## System Requirements

### Backend Requirements

- PHP 8.2+
- Composer
- MariaDB 10.6+
- Redis 7.x

### Frontend Requirements

- Node.js 16.x+
- npm 8.x+

### Deployment Requirements

- Docker and Docker Compose (for containerized deployment)
- Kubernetes (for orchestrated deployment)
- LGTM Stack (Loki, Grafana, Tempo, Mimir) for monitoring

## Technology Stack

### Backend

- Laravel 10.x (PHP Framework)
- Laravel Sanctum (Authentication)
- Barryvdh/Laravel-Snappy // @barryvdh/laravel-snappy: ^1.0 (PDF generation)
- MariaDB 10.6+ (Database)
- Redis 7.x (Caching, Queues)

### Frontend

- React // react: ^18.2.0 (UI Library)
- TypeScript (Type Safety)
- Minimal UI Kit (Design System)
- Tailwind CSS // tailwindcss: ^3.3.1 (Utility CSS)
- Adobe Acrobat PDF Viewer // @adobe/dc-view-sdk: latest (Document Display)
- Axios // axios: ^1.3.6 (API Requests)
- React Query // @tanstack/react-query: ^4.29.5 (Data Fetching)

### Infrastructure

- Docker (Containerization)
- Kubernetes (Orchestration)
- NGINX (Web Server)
- LGTM Stack (Monitoring)

## Project Structure

```
├── docs/                  # Documentation
│   ├── architecture.md    # Architecture documentation
│   ├── deployment.md      # Deployment guide
│   ├── development.md     # Development guide
│   ├── api.md             # API documentation
│   ├── user-guide.md      # User guide
│   └── troubleshooting.md # Troubleshooting guide
├── src/                   # Source code
│   ├── backend/           # Laravel backend
│   │   ├── app/           # Application code
│   │   ├── config/        # Configuration files
│   │   ├── database/      # Migrations and seeders
│   │   ├── routes/        # API routes
│   │   └── tests/         # Backend tests
│   └── web/               # React frontend
│       ├── src/           # Frontend source code
│       ├── public/        # Static assets
│       └── tests/         # Frontend tests
├── infrastructure/        # Infrastructure configuration
│   ├── docker/            # Docker configuration
│   ├── kubernetes/        # Kubernetes manifests
│   ├── terraform/         # Infrastructure as code
│   ├── helm/              # Helm charts
│   ├── scripts/           # Deployment scripts
│   └── monitoring/        # Monitoring configuration
├── .github/               # GitHub workflows and templates
└── README.md              # This file
```

## Getting Started

### Quick Start

1. Clone the repository
   ```bash
   git clone https://github.com/your-org/insure-pilot.git
   cd insure-pilot
   ```

2. Start the development environment with Docker Compose
   ```bash
   docker-compose up -d
   ```

3. Access the application
   - Backend API: http://localhost:8000
   - Frontend: http://localhost:3000

### Manual Setup

#### Backend Setup

1. Navigate to the backend directory
   ```bash
   cd src/backend
   ```

2. Install dependencies
   ```bash
   composer install
   ```

3. Configure environment
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Set up the database
   ```bash
   php artisan migrate --seed
   ```

5. Start the development server
   ```bash
   php artisan serve
   ```

#### Frontend Setup

1. Navigate to the frontend directory
   ```bash
   cd src/web
   ```

2. Install dependencies
   ```bash
   npm install
   ```

3. Configure environment
   ```bash
   cp .env.example .env
   ```

4. Start the development server
   ```bash
   npm start
   ```

## Development

For detailed development guidelines, please refer to the [Development Guide](docs/development.md).

### Key Development Commands

#### Backend

- Run tests: `php artisan test`
- Run linting: `composer lint`
- Format code: `composer format`

#### Frontend

- Run tests: `npm test`
- Run linting: `npm run lint`
- Format code: `npm run format`
- Type checking: `npm run typecheck`

## Deployment

For detailed deployment instructions, please refer to the [Deployment Guide](docs/deployment.md).

### Quick Deployment

1. Build the Docker images
   ```bash
   docker build -f infrastructure/docker/backend.Dockerfile -t insurepilot-documents-backend .
   docker build -f infrastructure/docker/frontend.Dockerfile -t insurepilot-documents-frontend .
   ```

2. Deploy to Kubernetes
   ```bash
   kubectl apply -k infrastructure/kubernetes/overlays/prod
   ```

3. Deploy with Helm
   ```bash
   helm install documents-view infrastructure/helm/documents-view -f infrastructure/helm/documents-view/values-prod.yaml
   ```

## Documentation

Comprehensive documentation is available in the `docs/` directory:

- [Architecture Documentation](docs/architecture.md) - System design and component interactions
- [Deployment Guide](docs/deployment.md) - Deployment instructions and configuration
- [Development Guide](docs/development.md) - Development workflow and guidelines
- [API Documentation](docs/api.md) - API endpoints and usage
- [User Guide](docs/user-guide.md) - End-user documentation
- [Troubleshooting Guide](docs/troubleshooting.md) - Common issues and solutions

Component-specific documentation:

- [Backend Documentation](src/backend/README.md) - Backend-specific setup and development
- [Frontend Documentation](src/web/README.md) - Frontend-specific setup and development

## Testing

The Documents View feature includes comprehensive testing at multiple levels:

### Backend Testing

- Unit tests for individual components
- Feature tests for API endpoints
- Integration tests for service interactions

Run backend tests with:
```bash
cd src/backend
php artisan test
```

### Frontend Testing

- Unit tests for components and hooks
- Integration tests for user workflows
- End-to-end tests for complete features

Run frontend tests with:
```bash
cd src/web
npm test
```

### CI/CD Pipeline

All tests are automatically run in the CI/CD pipeline on pull requests and before deployment.

## Monitoring

The Documents View feature integrates with the LGTM stack for comprehensive monitoring:

- **Loki** - Log aggregation and analysis
- **Grafana** - Visualization and dashboards
- **Tempo** - Distributed tracing
- **Mimir** - Metrics storage and alerting

Predefined dashboards are available in `infrastructure/monitoring/grafana/dashboards/`.

## Contributing

Please refer to the [Development Guide](docs/development.md) for detailed information on the development workflow, coding standards, and contribution process.

1. Create a feature branch from `main`
2. Implement your changes with appropriate tests
3. Ensure all tests pass and code meets quality standards
4. Submit a pull request with a clear description of the changes
5. Address any feedback from code reviews

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
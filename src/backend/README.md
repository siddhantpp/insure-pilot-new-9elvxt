# Documents View Backend

Backend implementation for the Documents View feature of Insure Pilot, providing a dedicated, full-screen environment for users to review, process, and manage insurance-related documents.

## Requirements

- PHP 8.2+
- Composer
- MariaDB 10.6+
- Redis 7.x
- wkhtmltopdf (for PDF generation)
- Docker (optional, for containerized development)

## Installation

1. Clone the repository
2. Navigate to the backend directory: `cd src/backend`
3. Install dependencies: `composer install`
4. Copy the environment file: `cp .env.example .env`
5. Generate application key: `php artisan key:generate`
6. Configure your database connection in the `.env` file
7. Run migrations: `php artisan migrate`
8. Seed the database: `php artisan db:seed`

## Docker Setup

For containerized development:

1. Make sure Docker and Docker Compose are installed
2. Build the container: `docker build -t insurepilot-documents-backend .`
3. Run the container: `docker run -p 9000:9000 -v $(pwd):/var/www/html insurepilot-documents-backend`

Alternatively, use the provided Docker Compose configuration:

```bash
docker-compose up -d
```

## Environment Configuration

Configure the following environment variables in your `.env` file:

- `APP_NAME`: Application name (default: "Insure Pilot")
- `APP_ENV`: Environment (local, staging, production)
- `DB_CONNECTION`: Database connection (mariadb)
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: Database connection details
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`: Redis connection details
- `DOCUMENT_VIEWER_ENABLED`: Enable/disable document viewer feature
- `DOCUMENT_VIEWER_ADOBE_SDK_URL`: URL to Adobe PDF SDK
- `DOCUMENT_VIEWER_MAX_FILE_SIZE`: Maximum file size in MB
- `DOCUMENT_VIEWER_ALLOWED_TYPES`: Comma-separated list of allowed file types

See `.env.example` for all available configuration options.

## Architecture Overview

The backend follows Laravel's MVC architecture with the following components:

- **Models**: Located in `app/Models/`, representing database entities
- **Controllers**: Located in `app/Http/Controllers/Api/`, handling API requests
- **Services**: Located in `app/Services/`, containing business logic
- **Resources**: Located in `app/Http/Resources/`, for API response formatting
- **Requests**: Located in `app/Http/Requests/`, for request validation
- **Events & Listeners**: Located in `app/Events/` and `app/Listeners/`, for event-driven architecture
- **Jobs**: Located in `app/Jobs/`, for background processing
- **Policies**: Located in `app/Policies/`, for authorization

## API Endpoints

The API provides the following endpoint groups:

- `/api/documents`: Document management endpoints
- `/api/metadata`: Document metadata management endpoints
- `/api/policies`: Policy data endpoints
- `/api/losses`: Loss data endpoints
- `/api/claimants`: Claimant data endpoints
- `/api/producers`: Producer data endpoints

All endpoints require authentication using Laravel Sanctum. See the API documentation for detailed endpoint specifications.

## Authentication

The API uses Laravel Sanctum for token-based authentication. To authenticate:

1. Obtain a token via the authentication endpoint
2. Include the token in the `Authorization` header of subsequent requests:
   ```
   Authorization: Bearer {your-token}
   ```

## Testing

Run the test suite with PHPUnit:

```bash
php artisan test
```

Or with code coverage report:

```bash
composer test-coverage
```

The test suite includes:
- Unit tests: Testing individual components
- Feature tests: Testing API endpoints
- Integration tests: Testing component interactions

## Development Workflow

1. Create a feature branch from `main`
2. Implement your changes
3. Write tests for your implementation
4. Run the test suite to ensure all tests pass
5. Format your code: `composer format`
6. Submit a pull request

All pull requests must pass automated tests and code quality checks before being merged.

## Deployment

The application can be deployed using the provided Docker container or through traditional PHP deployment methods. For production deployment, ensure:

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Optimize the application: `php artisan optimize`
3. Cache configuration: `php artisan config:cache`
4. Cache routes: `php artisan route:cache`
5. Set up a proper web server (Nginx/Apache) with PHP-FPM

## Monitoring

The application integrates with the LGTM stack (Loki, Grafana, Tempo, Mimir) for monitoring. Configure the following environment variables:

- `LGTM_ENABLED`: Enable LGTM monitoring
- `GRAFANA_URL`: Grafana dashboard URL
- `LOKI_URL`: Loki log aggregation URL
- `TEMPO_URL`: Tempo distributed tracing URL
- `MIMIR_URL`: Mimir metrics URL
- `LGTM_API_KEY`: API key for LGTM stack

## License

Proprietary software. All rights reserved.
# Deployment Guide for Documents View

This guide provides comprehensive instructions for deploying, configuring, and maintaining the Documents View feature of Insure Pilot. It covers environment setup, deployment procedures, monitoring, and maintenance tasks required for successful operation in development, staging, and production environments.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Environment Setup](#environment-setup)
- [Deployment Process](#deployment-process)
- [Configuration Management](#configuration-management)
- [Monitoring and Observability](#monitoring-and-observability)
- [Scaling and Performance](#scaling-and-performance)
- [Backup and Disaster Recovery](#backup-and-disaster-recovery)
- [Security Considerations](#security-considerations)
- [Maintenance Procedures](#maintenance-procedures)
- [Appendices](#appendices)

## Prerequisites

Before deploying the Documents View feature, ensure you have the following prerequisites:

### Required Tools

- Docker v20.10.x or later
- Kubernetes CLI (kubectl) v1.26.x or later
- GitLab CLI (optional, for pipeline management)
- Helm v3.10.x or later
- AWS CLI v2.x (for cloud deployment)

### Access Permissions

- GitLab repository access (Developer role or higher)
- Kubernetes cluster access:
  - Development: Edit permissions
  - Staging: Edit permissions
  - Production: View permissions (deployments handled by authorized DevOps personnel)
- AWS console access (for cloud resources)
- Docker registry access

### Required Knowledge

- Basic understanding of Docker and Kubernetes
- Familiarity with GitLab CI/CD
- Knowledge of infrastructure monitoring concepts

## Environment Setup

### Development Environment

The development environment uses Docker Compose for local development and testing.

1. Clone the repository:

```bash
git clone https://gitlab.example.com/insurepilot/documents-view.git
cd documents-view
```

2. Create a `.env` file from the template:

```bash
cp .env.example .env
```

3. Update the following variables in `.env`:

```properties
APP_ENV=local
DB_HOST=mariadb
DB_DATABASE=documents
DB_USERNAME=documents_user
DB_PASSWORD=your_password_here
REDIS_HOST=redis
ADOBE_PDF_SDK_URL=https://documentcloud.adobe.com/view-sdk/main.js
ADOBE_PDF_SDK_CLIENT_ID=your_client_id_here
```

4. Start the development environment:

```bash
docker-compose up -d
```

5. Run database migrations:

```bash
docker-compose exec app php artisan migrate
```

6. Seed the database with test data:

```bash
docker-compose exec app php artisan db:seed --class=DocumentsViewSeeder
```

7. Access the application at `http://localhost:8080`

#### Development Environment Validation

Verify the development environment is working correctly:

```bash
# Check container status
docker-compose ps

# Verify API endpoints
curl http://localhost:8080/api/health

# Check logs
docker-compose logs -f app
```

### Staging Environment

The staging environment uses Kubernetes with a configuration matching production but with reduced resources.

1. Connect to the staging Kubernetes cluster:

```bash
# Using AWS EKS
aws eks update-kubeconfig --name insure-pilot-staging --region us-east-1

# OR using Rancher K3s
export KUBECONFIG=/path/to/staging-kubeconfig.yaml
```

2. Create the namespace if it doesn't exist:

```bash
kubectl create namespace documents-staging
```

3. Create required secrets:

```bash
kubectl create secret generic documents-db-credentials \
  --namespace=documents-staging \
  --from-literal=username=documents_user \
  --from-literal=password=staging_password_here

kubectl create secret generic documents-adobe-credentials \
  --namespace=documents-staging \
  --from-literal=client-id=your_staging_client_id_here \
  --from-literal=client-secret=your_staging_client_secret_here
```

4. Apply resource quotas:

```bash
kubectl apply -f kubernetes/staging/resource-quotas.yaml
```

5. Deploy using Helm:

```bash
helm upgrade --install documents-view ./helm/documents-view \
  --namespace documents-staging \
  --values helm/documents-view/values-staging.yaml
```

#### Staging Environment Validation

Verify the staging deployment:

```bash
# Check pod status
kubectl get pods -n documents-staging

# Verify services
kubectl get services -n documents-staging

# Check ingress
kubectl get ingress -n documents-staging

# Test the health endpoint
curl https://documents-staging.insurepilot.example.com/api/health
```

### Production Environment

Production deployment follows the same pattern as staging but with high availability configuration and increased resources.

1. Connect to the production Kubernetes cluster:

```bash
# Using AWS EKS
aws eks update-kubeconfig --name insure-pilot-production --region us-east-1

# OR using Rancher K3s
export KUBECONFIG=/path/to/production-kubeconfig.yaml
```

2. Create the namespace if it doesn't exist:

```bash
kubectl create namespace documents-prod
```

3. Create required secrets:

```bash
kubectl create secret generic documents-db-credentials \
  --namespace=documents-prod \
  --from-literal=username=documents_user \
  --from-literal=password=production_password_here

kubectl create secret generic documents-adobe-credentials \
  --namespace=documents-prod \
  --from-literal=client-id=your_production_client_id_here \
  --from-literal=client-secret=your_production_client_secret_here
```

4. Apply resource quotas and pod disruption budgets:

```bash
kubectl apply -f kubernetes/production/resource-quotas.yaml
kubectl apply -f kubernetes/production/pod-disruption-budget.yaml
```

5. Deploy using Helm with canary strategy (explained in the Deployment Process section):

```bash
helm upgrade --install documents-view ./helm/documents-view \
  --namespace documents-prod \
  --values helm/documents-view/values-production.yaml
```

## Deployment Process

### CI/CD Pipeline

The Documents View feature uses GitLab CI/CD for automated testing, building, and deployment. The pipeline is defined in `.gitlab-ci.yml`.

#### Pipeline Stages

1. **Build**: Compiles assets and builds Docker images
2. **Test**: Runs automated tests (unit, integration)
3. **Analyze**: Performs static code analysis and security scanning
4. **Publish**: Pushes Docker images to registry
5. **Deploy-Dev**: Deploys to development environment
6. **Deploy-Staging**: Deploys to staging environment
7. **Deploy-Prod**: Deploys to production environment

#### Pipeline Triggers

- Commits to any branch trigger build, test, and analyze stages
- Merges to `main` branch additionally trigger publish and deploy-dev stages
- Tags with format `vX.Y.Z` trigger staging deployment
- Manual approval required for production deployment

### Build Process

The build process compiles frontend assets and creates Docker images for deployment.

1. Frontend assets are built using webpack:

```yaml
build-frontend:
  stage: build
  image: node:16-alpine
  script:
    - npm ci
    - npm run build
  artifacts:
    paths:
      - public/build
```

2. Docker images are built with multi-stage builds for efficiency:

```yaml
build-docker:
  stage: build
  image: docker:20.10
  services:
    - docker:20.10-dind
  script:
    - docker build -t ${CI_REGISTRY_IMAGE}:${CI_COMMIT_SHA} .
    - docker tag ${CI_REGISTRY_IMAGE}:${CI_COMMIT_SHA} ${CI_REGISTRY_IMAGE}:latest
    - docker push ${CI_REGISTRY_IMAGE}:${CI_COMMIT_SHA}
    - docker push ${CI_REGISTRY_IMAGE}:latest
```

3. Security scanning is performed on the Docker image:

```yaml
scan-docker:
  stage: analyze
  image: aquasec/trivy
  script:
    - trivy image --severity HIGH,CRITICAL ${CI_REGISTRY_IMAGE}:${CI_COMMIT_SHA}
```

### Deployment to Development

The development deployment process automatically deploys the latest code from the `main` branch.

1. The GitLab CI/CD pipeline deploys to the development environment:

```yaml
deploy-dev:
  stage: deploy-dev
  image: bitnami/kubectl:1.26
  script:
    - kubectl config use-context ${KUBE_CONTEXT}
    - helm upgrade --install documents-view ./helm/documents-view \
        --namespace documents-dev \
        --values helm/documents-view/values-development.yaml \
        --set image.tag=${CI_COMMIT_SHA}
  environment:
    name: development
    url: https://documents-dev.insurepilot.example.com
  only:
    - main
```

2. Automated integration tests verify the deployment:

```yaml
test-dev-deployment:
  stage: deploy-dev
  image: node:16-alpine
  script:
    - npm ci
    - npm run test:e2e -- --baseUrl=https://documents-dev.insurepilot.example.com
  only:
    - main
```

### Deployment to Staging

Staging deployment uses a blue-green deployment strategy to ensure zero-downtime upgrades.

1. Create a new "green" deployment:

```yaml
deploy-staging-green:
  stage: deploy-staging
  image: bitnami/kubectl:1.26
  script:
    - kubectl config use-context ${KUBE_CONTEXT}
    - helm upgrade --install documents-view-green ./helm/documents-view \
        --namespace documents-staging \
        --values helm/documents-view/values-staging.yaml \
        --set image.tag=${CI_COMMIT_TAG} \
        --set service.name=documents-view-green
  environment:
    name: staging
    url: https://documents-staging.insurepilot.example.com
  only:
    - tags
```

2. Run automated tests against the green deployment:

```yaml
test-staging-green:
  stage: deploy-staging
  image: node:16-alpine
  script:
    - npm ci
    - npm run test:e2e -- --baseUrl=https://documents-staging-green.insurepilot.example.com
  only:
    - tags
```

3. Switch traffic to the green deployment:

```yaml
switch-staging-traffic:
  stage: deploy-staging
  image: bitnami/kubectl:1.26
  script:
    - kubectl config use-context ${KUBE_CONTEXT}
    - kubectl patch ingress documents-view -n documents-staging \
        --patch '{"spec":{"rules":[{"http":{"paths":[{"backend":{"service":{"name":"documents-view-green"}}}]}}]}}'
  only:
    - tags
  when: manual
```

4. After verification, remove the old "blue" deployment:

```yaml
cleanup-staging-blue:
  stage: deploy-staging
  image: bitnami/kubectl:1.26
  script:
    - kubectl config use-context ${KUBE_CONTEXT}
    - helm uninstall documents-view-blue -n documents-staging || true
  only:
    - tags
  when: manual
```

### Deployment to Production

Production deployment uses a canary deployment strategy, gradually shifting traffic to the new version.

1. Deploy canary version (10% of traffic):

```yaml
deploy-prod-canary:
  stage: deploy-prod
  image: bitnami/kubectl:1.26
  script:
    - kubectl config use-context ${KUBE_CONTEXT}
    - helm upgrade --install documents-view-canary ./helm/documents-view \
        --namespace documents-prod \
        --values helm/documents-view/values-production.yaml \
        --set image.tag=${CI_COMMIT_TAG} \
        --set service.name=documents-view-canary \
        --set canary.enabled=true \
        --set canary.weight=10
  environment:
    name: production-canary
    url: https://documents.insurepilot.example.com
  only:
    - tags
  when: manual
```

2. Monitor canary deployment for issues:

```yaml
monitor-prod-canary:
  stage: deploy-prod
  image: alpine:3.16
  script:
    - apk add --no-cache curl jq
    - for i in {1..10}; do
        ERROR_RATE=$(curl -s https://prometheus.insurepilot.example.com/api/v1/query?query=sum(rate(http_requests_total{service="documents-view-canary",status=~"5.."}[5m]))/sum(rate(http_requests_total{service="documents-view-canary"}[5m])) | jq '.data.result[0].value[1]');
        if (( $(echo "$ERROR_RATE > 0.01" | bc -l) )); then
          echo "Error rate too high: $ERROR_RATE";
          exit 1;
        fi;
        sleep 30;
      done
  only:
    - tags
```

3. Increase canary traffic to 50%:

```yaml
increase-canary-traffic:
  stage: deploy-prod
  image: bitnami/kubectl:1.26
  script:
    - kubectl config use-context ${KUBE_CONTEXT}
    - kubectl patch ingress documents-view-canary -n documents-prod \
        --patch '{"spec":{"canary":{"weight":50}}}'
  only:
    - tags
  when: manual
```

4. Complete deployment (100% traffic):

```yaml
deploy-prod-complete:
  stage: deploy-prod
  image: bitnami/kubectl:1.26
  script:
    - kubectl config use-context ${KUBE_CONTEXT}
    - helm upgrade --install documents-view ./helm/documents-view \
        --namespace documents-prod \
        --values helm/documents-view/values-production.yaml \
        --set image.tag=${CI_COMMIT_TAG}
    - kubectl delete ingress documents-view-canary -n documents-prod
    - helm uninstall documents-view-canary -n documents-prod
  environment:
    name: production
    url: https://documents.insurepilot.example.com
  only:
    - tags
  when: manual
```

## Configuration Management

### Environment Variables

The Documents View feature uses environment variables for configuration. These variables are managed differently in each environment:

#### Critical Configuration Variables

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `DOCUMENT_VIEWER_ENABLED` | Enable/disable feature | `true` | Yes |
| `DOCUMENT_VIEWER_ADOBE_SDK_URL` | URL to Adobe PDF SDK | `https://documentcloud.adobe.com/view-sdk/main.js` | Yes |
| `DOCUMENT_VIEWER_MAX_FILE_SIZE` | Maximum file size in MB | `50` | Yes |
| `DOCUMENT_VIEWER_ALLOWED_TYPES` | Allowed document types | `pdf,docx,xlsx,pptx` | Yes |
| `DOCUMENT_VIEWER_CACHE_TTL` | Cache time-to-live in seconds | `3600` | Yes |
| `DOCUMENT_VIEWER_AUDIT_ENABLED` | Enable detailed audit logging | `true` | Yes |
| `DB_HOST` | Database hostname | - | Yes |
| `DB_DATABASE` | Database name | - | Yes |
| `DB_USERNAME` | Database username | - | Yes |
| `DB_PASSWORD` | Database password | - | Yes |
| `REDIS_HOST` | Redis hostname | - | Yes |
| `ADOBE_PDF_SDK_CLIENT_ID` | Adobe SDK client ID | - | Yes |

### Kubernetes ConfigMaps and Secrets

In Kubernetes environments, configuration is managed using ConfigMaps and Secrets.

#### Creating ConfigMaps

```bash
kubectl create configmap documents-view-config \
  --namespace=documents-prod \
  --from-literal=DOCUMENT_VIEWER_ENABLED=true \
  --from-literal=DOCUMENT_VIEWER_ADOBE_SDK_URL=https://documentcloud.adobe.com/view-sdk/main.js \
  --from-literal=DOCUMENT_VIEWER_MAX_FILE_SIZE=50 \
  --from-literal=DOCUMENT_VIEWER_ALLOWED_TYPES=pdf,docx,xlsx,pptx \
  --from-literal=DOCUMENT_VIEWER_CACHE_TTL=3600 \
  --from-literal=DOCUMENT_VIEWER_AUDIT_ENABLED=true
```

#### Managing Secrets

```bash
kubectl create secret generic documents-db-credentials \
  --namespace=documents-prod \
  --from-literal=username=documents_user \
  --from-literal=password=your_secure_password_here

kubectl create secret generic documents-adobe-credentials \
  --namespace=documents-prod \
  --from-literal=client-id=your_client_id_here \
  --from-literal=client-secret=your_client_secret_here
```

#### Using ConfigMaps and Secrets in Deployments

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: documents-view
  namespace: documents-prod
spec:
  template:
    spec:
      containers:
      - name: documents-view
        image: ${CI_REGISTRY_IMAGE}:${CI_COMMIT_TAG}
        envFrom:
        - configMapRef:
            name: documents-view-config
        env:
        - name: DB_USERNAME
          valueFrom:
            secretKeyRef:
              name: documents-db-credentials
              key: username
        - name: DB_PASSWORD
          valueFrom:
            secretKeyRef:
              name: documents-db-credentials
              key: password
        - name: ADOBE_PDF_SDK_CLIENT_ID
          valueFrom:
            secretKeyRef:
              name: documents-adobe-credentials
              key: client-id
```

### Feature Flags

Feature flags are used for controlled rollout of functionality. They are managed in the application database and can be configured through the admin interface or API.

#### Core Feature Flags

| Flag | Description | Default |
|------|-------------|---------|
| `enable_document_viewer` | Master toggle for the entire feature | `true` |
| `enable_document_history` | Toggle for document history functionality | `true` |
| `enable_document_processing` | Toggle for document processing actions | `true` |

#### Progressive Rollout Flags

| Flag | Description | Default |
|------|-------------|---------|
| `document_viewer_user_group_{id}` | Enable feature for specific user groups | `false` |
| `document_viewer_beta_users` | Enable feature for beta testers | `true` |
| `document_viewer_percentage_rollout` | Enable for percentage of users | `100` |

#### Managing Feature Flags via API

```bash
# Enable a feature flag
curl -X POST https://documents.insurepilot.example.com/api/admin/feature-flags \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"name": "enable_document_history", "enabled": true}'

# Get feature flag status
curl https://documents.insurepilot.example.com/api/admin/feature-flags/enable_document_history \
  -H "Authorization: Bearer ${TOKEN}"
```

## Monitoring and Observability

### Metrics Collection

The Documents View feature uses Prometheus for metrics collection with Grafana for visualization.

#### Key Metrics

| Metric | Type | Description |
|--------|------|-------------|
| `document_view_count` | Counter | Number of document views |
| `document_processing_time` | Histogram | Time taken to process documents |
| `metadata_update_count` | Counter | Number of metadata updates |
| `api_request_duration_seconds` | Histogram | API endpoint response times |
| `document_load_time_seconds` | Histogram | Time to load documents |

#### Prometheus Configuration

```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'documents-view'
    kubernetes_sd_configs:
      - role: pod
    relabel_configs:
      - source_labels: [__meta_kubernetes_pod_label_app]
        action: keep
        regex: documents-view
      - source_labels: [__meta_kubernetes_pod_annotation_prometheus_io_scrape]
        action: keep
        regex: true
      - source_labels: [__meta_kubernetes_pod_annotation_prometheus_io_path]
        action: replace
        target_label: __metrics_path__
        regex: (.+)
      - source_labels: [__address__, __meta_kubernetes_pod_annotation_prometheus_io_port]
        action: replace
        regex: ([^:]+)(?::\d+)?;(\d+)
        replacement: $1:$2
        target_label: __address__
```

#### Adding Custom Metrics in Code

```php
// PHP example using Prometheus client
$counter = $registry->getOrRegisterCounter(
    'documents_view', 
    'document_view_count', 
    'Number of document views', 
    ['document_type']
);
$counter->increment(['policy']);

// Track document load time
$histogram = $registry->getOrRegisterHistogram(
    'documents_view',
    'document_load_time_seconds',
    'Time to load documents',
    ['document_type'],
    [0.1, 0.5, 1, 2, 5]
);
$histogram->observe($loadTime, ['policy']);
```

### Log Aggregation

Logs are aggregated using Loki and can be viewed through Grafana dashboards.

#### Log Levels

| Environment | Log Level | Description |
|-------------|-----------|-------------|
| Development | DEBUG | Verbose logging for development |
| Staging | INFO | Standard operational logs |
| Production | WARNING | Only important operational events |

#### Log Format

All logs use a structured JSON format for easier querying and analysis:

```json
{
  "timestamp": "2023-05-15T10:23:45.123Z",
  "level": "INFO",
  "component": "DocumentViewer",
  "message": "Document viewed successfully",
  "context": {
    "document_id": 12345,
    "user_id": 789,
    "document_type": "policy"
  }
}
```

#### Setting Up Promtail

Promtail is used to collect and forward logs to Loki:

```yaml
# promtail-config.yaml
clients:
  - url: http://loki:3100/loki/api/v1/push

scrape_configs:
  - job_name: kubernetes-pods
    kubernetes_sd_configs:
      - role: pod
    relabel_configs:
      - source_labels: [__meta_kubernetes_pod_label_app]
        action: keep
        regex: documents-view
      - source_labels: [__meta_kubernetes_pod_container_name]
        target_label: container
      - source_labels: [__meta_kubernetes_namespace]
        target_label: namespace
      - source_labels: [__meta_kubernetes_pod_name]
        target_label: pod
```

#### Useful Loki Queries

```
# Find all ERROR level logs for DocumentViewer
{app="documents-view", component="DocumentViewer"} |= "ERROR"

# Track document processing failures
{app="documents-view"} |= "Failed to process document"

# Count document views by type
sum by (document_type) (count_over_time({app="documents-view"} |= "Document viewed successfully"[1h]))
```

### Distributed Tracing

Distributed tracing is implemented using Tempo to track requests across services.

#### Setting Up Tracing

1. Add the OpenTelemetry PHP library:

```bash
composer require open-telemetry/opentelemetry-auto-laravel
```

2. Configure the exporter in your `.env` file:

```properties
OTEL_EXPORTER_OTLP_ENDPOINT=http://tempo:4318
OTEL_SERVICE_NAME=documents-view
OTEL_TRACES_SAMPLER=parentbased_always_on
```

3. Create a middleware to add trace context:

```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

class TraceMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $span = Span::getCurrent();
        $span->setAttributes([
            'http.method' => $request->method(),
            'http.url' => $request->fullUrl(),
            'http.route' => $request->route() ? $request->route()->getName() : '',
        ]);
        
        $response = $next($request);
        
        $span->setAttributes(['http.status_code' => $response->getStatusCode()]);
        
        if ($response->getStatusCode() >= 400) {
            $span->setStatus(StatusCode::ERROR);
        }
        
        return $response;
    }
}
```

4. Add custom spans for important operations:

```php
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;

// Custom span for document loading
$tracer = \OpenTelemetry\API\GlobalTracerProvider::get()->getTracer('documents-view');
$span = $tracer->spanBuilder('load_document')
    ->setSpanKind(SpanKind::INTERNAL)
    ->startSpan();

try {
    // Load document logic here
    $span->setAttributes([
        'document.id' => $documentId,
        'document.type' => $documentType
    ]);
} catch (\Exception $e) {
    $span->recordException($e);
    $span->setStatus(StatusCode::ERROR, $e->getMessage());
    throw $e;
} finally {
    $span->end();
}
```

### Alerting

Alerting is configured using Prometheus AlertManager to notify teams of issues.

#### Alert Rules

```yaml
# alerts.yaml
groups:
- name: documents-view
  rules:
  - alert: HighErrorRate
    expr: sum(rate(http_requests_total{job="documents-view",status=~"5.."}[5m])) / sum(rate(http_requests_total{job="documents-view"}[5m])) > 0.05
    for: 5m
    labels:
      severity: critical
      component: documents-view
    annotations:
      summary: "High error rate on Documents View"
      description: "Error rate is above 5% for 5 minutes ({{ $value | printf \"%.2f\" }})"

  - alert: SlowDocumentLoading
    expr: histogram_quantile(0.95, sum(rate(document_load_time_seconds_bucket{job="documents-view"}[5m])) by (le)) > 3
    for: 5m
    labels:
      severity: warning
      component: documents-view
    annotations:
      summary: "Slow document loading detected"
      description: "95th percentile of document load time is above 3 seconds ({{ $value | printf \"%.2f\" }}s)"

  - alert: HighMemoryUsage
    expr: container_memory_usage_bytes{container="documents-view"} / container_spec_memory_limit_bytes{container="documents-view"} > 0.85
    for: 15m
    labels:
      severity: warning
      component: documents-view
    annotations:
      summary: "High memory usage on Documents View"
      description: "Memory usage is above 85% for 15 minutes ({{ $value | printf \"%.2f\" }})"
```

#### Alert Notification Channels

Configure AlertManager to send notifications to appropriate channels:

```yaml
# alertmanager.yml
receivers:
- name: 'documents-team'
  slack_configs:
  - api_url: 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX'
    channel: '#documents-alerts'
    title: "{{ .GroupLabels.alertname }}"
    text: "{{ .CommonAnnotations.description }}"
  email_configs:
  - to: 'documents-team@insurepilot.example.com'
    send_resolved: true

- name: 'ops-team'
  pagerduty_configs:
  - service_key: '1234567890abcdef1234567890abcdef'
    send_resolved: true

route:
  group_by: ['alertname', 'component']
  group_wait: 30s
  group_interval: 5m
  repeat_interval: 4h
  receiver: 'documents-team'
  routes:
  - match:
      severity: critical
    receiver: 'ops-team'
```

## Scaling and Performance

### Horizontal Pod Autoscaling

The Documents View feature uses Kubernetes Horizontal Pod Autoscaler (HPA) to automatically scale based on load.

#### HPA Configuration

```yaml
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: documents-view
  namespace: documents-prod
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: documents-view
  minReplicas: 3
  maxReplicas: 10
  metrics:
  - type: Resource
    resource:
      name: cpu
      target:
        type: Utilization
        averageUtilization: 70
  - type: Resource
    resource:
      name: memory
      target:
        type: Utilization
        averageUtilization: 80
  behavior:
    scaleDown:
      stabilizationWindowSeconds: 300
```

#### Custom Metrics Autoscaling

For more advanced scaling, configure HPA with custom metrics:

```yaml
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: documents-view-custom
  namespace: documents-prod
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: documents-view
  minReplicas: 3
  maxReplicas: 15
  metrics:
  - type: Pods
    pods:
      metric:
        name: document_processing_queue_length
      target:
        type: AverageValue
        averageValue: 10
```

### Resource Allocation

Proper resource allocation is critical for optimal performance.

#### Resource Requirements by Environment

| Environment | Component | CPU Request | CPU Limit | Memory Request | Memory Limit |
|-------------|-----------|------------|-----------|----------------|-------------|
| Development | documents-view | 0.5 | 1 | 1Gi | 2Gi |
| Staging | documents-view | 1 | 2 | 2Gi | 4Gi |
| Production | documents-view | 2 | 4 | 4Gi | 8Gi |

#### Example Deployment Resource Configuration

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: documents-view
  namespace: documents-prod
spec:
  template:
    spec:
      containers:
      - name: documents-view
        image: ${CI_REGISTRY_IMAGE}:${CI_COMMIT_TAG}
        resources:
          requests:
            cpu: "2"
            memory: "4Gi"
          limits:
            cpu: "4"
            memory: "8Gi"
```

### Performance Benchmarks

Performance benchmarks ensure the system meets expected performance targets.

#### Key Performance Indicators

| Operation | Target Performance | Load Testing Validation |
|-----------|-------------------|-------------------------|
| Document Load Time | < 3 seconds | Test with various document sizes (1MB, 5MB, 10MB) |
| Metadata Update | < 1 second | Test with 10, 50, 100 concurrent updates |
| Search Operations | < 2 seconds | Test with 10,000+ documents in the system |

#### Load Testing Script Example

Using k6 for load testing:

```javascript
// document-load-test.js
import http from 'k6/http';
import { sleep, check } from 'k6';

export const options = {
  stages: [
    { duration: '1m', target: 50 },  // Ramp up to 50 users
    { duration: '3m', target: 50 },  // Stay at 50 users
    { duration: '1m', target: 0 },   // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p95<3000'], // 95% of requests must complete within 3s
    http_req_failed: ['rate<0.01'],  // Less than 1% of requests can fail
  },
};

export default function () {
  const documentIds = [1001, 1002, 1003, 1004, 1005]; // Sample document IDs
  const randomId = documentIds[Math.floor(Math.random() * documentIds.length)];
  
  const url = `https://documents.insurepilot.example.com/api/documents/${randomId}`;
  const params = {
    headers: {
      'Authorization': `Bearer ${__ENV.AUTH_TOKEN}`,
    },
  };
  
  const res = http.get(url, params);
  
  check(res, {
    'status is 200': (r) => r.status === 200,
    'response time < 3s': (r) => r.timings.duration < 3000,
  });
  
  sleep(1);
}
```

Run the load test:

```bash
k6 run -e AUTH_TOKEN=your_test_token document-load-test.js
```

## Backup and Disaster Recovery

### Backup Procedures

#### Database Backups

MariaDB backups are performed using both logical dumps and binary log backups:

1. Daily full backup:

```bash
# Run daily at 1:00 AM
0 1 * * * /usr/bin/mysqldump --single-transaction --quick --lock-tables=false --all-databases | gzip > /backup/db/daily/documents-$(date +\%Y\%m\%d).sql.gz
```

2. Hourly incremental backup (binary logs):

```bash
# Configure MariaDB for binary logging
# in my.cnf:
# log_bin = /var/log/mysql/mysql-bin.log
# expire_logs_days = 14
# binlog_format = ROW

# Script to copy binary logs hourly
0 * * * * /usr/bin/rsync -av /var/log/mysql/mysql-bin.* /backup/db/binlogs/
```

3. Backup to S3:

```bash
# Sync backup directory to S3 daily
0 3 * * * /usr/bin/aws s3 sync /backup/db/ s3://insurepilot-backups/documents/db/ --delete
```

#### Document File Backups

Document files are stored on NFS and backed up using AWS S3:

1. Daily incremental sync to S3:

```bash
0 2 * * * /usr/bin/aws s3 sync /data/documents/ s3://insurepilot-backups/documents/files/ --size-only
```

2. Weekly full backup:

```bash
0 4 * * 0 /usr/bin/tar -czf /backup/files/documents-$(date +\%Y\%m\%d).tar.gz /data/documents/ && /usr/bin/aws s3 cp /backup/files/documents-$(date +\%Y\%m\%d).tar.gz s3://insurepilot-backups/documents/files/weekly/
```

#### Configuration Backups

All configuration is stored in Git and automatically backed up:

```bash
# Backup Kubernetes configuration
0 5 * * * /usr/bin/kubectl get configmap,secret -n documents-prod -o yaml > /backup/config/documents-config-$(date +\%Y\%m\%d).yaml && /usr/bin/aws s3 cp /backup/config/documents-config-$(date +\%Y\%m\%d).yaml s3://insurepilot-backups/documents/config/
```

### Recovery Procedures

#### Database Recovery

1. Full restoration from daily backup:

```bash
# Download the latest backup from S3
aws s3 cp s3://insurepilot-backups/documents/db/daily/documents-YYYYMMDD.sql.gz /tmp/

# Restore the database
gunzip -c /tmp/documents-YYYYMMDD.sql.gz | mysql -h ${DB_HOST} -u ${DB_USER} -p${DB_PASSWORD}
```

2. Point-in-time recovery using binary logs:

```bash
# First restore from the last full backup
gunzip -c /tmp/documents-YYYYMMDD.sql.gz | mysql -h ${DB_HOST} -u ${DB_USER} -p${DB_PASSWORD}

# Download binary logs
aws s3 cp --recursive s3://insurepilot-backups/documents/db/binlogs/ /tmp/binlogs/

# Apply binary logs up to the desired point in time
mysqlbinlog --stop-datetime="2023-05-15 14:30:00" /tmp/binlogs/mysql-bin.* | mysql -h ${DB_HOST} -u ${DB_USER} -p${DB_PASSWORD}
```

#### Document Files Recovery

1. Restore files from S3:

```bash
# Restore all files
aws s3 sync s3://insurepilot-backups/documents/files/ /data/documents/

# Restore specific files
aws s3 sync s3://insurepilot-backups/documents/files/policies/ /data/documents/policies/
```

2. Restore from weekly full backup:

```bash
# Download weekly backup
aws s3 cp s3://insurepilot-backups/documents/files/weekly/documents-YYYYMMDD.tar.gz /tmp/

# Extract files
tar -xzf /tmp/documents-YYYYMMDD.tar.gz -C /
```

### Disaster Recovery Testing

Regular disaster recovery tests ensure that recovery procedures work when needed:

1. Monthly database recovery test:

```bash
# Create test database
mysql -h ${DB_HOST} -u ${DB_USER} -p${DB_PASSWORD} -e "CREATE DATABASE documents_recovery_test;"

# Restore backup to test database
gunzip -c /tmp/documents-YYYYMMDD.sql.gz | mysql -h ${DB_HOST} -u ${DB_USER} -p${DB_PASSWORD} documents_recovery_test

# Verify data integrity
mysql -h ${DB_HOST} -u ${DB_USER} -p${DB_PASSWORD} -e "SELECT COUNT(*) FROM documents_recovery_test.documents;"

# Clean up
mysql -h ${DB_HOST} -u ${DB_USER} -p${DB_PASSWORD} -e "DROP DATABASE documents_recovery_test;"
```

2. Quarterly full disaster recovery drill:

```bash
# Create a complete test environment
kubectl create namespace documents-dr-test

# Restore database, configuration, and sample files
# Deploy application
helm upgrade --install documents-view ./helm/documents-view \
  --namespace documents-dr-test \
  --values helm/documents-view/values-dr-test.yaml

# Verify application functionality
# Run automated tests against the recovered environment
npm run test:e2e -- --baseUrl=https://documents-dr-test.insurepilot.example.com

# Clean up
kubectl delete namespace documents-dr-test
```

## Security Considerations

### Network Security

#### Firewall Rules

Implement the following firewall rules to secure the Documents View feature:

1. Kubernetes Network Policies:

```yaml
apiVersion: networking.k8s.io/v1
kind: NetworkPolicy
metadata:
  name: documents-view-policy
  namespace: documents-prod
spec:
  podSelector:
    matchLabels:
      app: documents-view
  policyTypes:
  - Ingress
  - Egress
  ingress:
  - from:
    - namespaceSelector:
        matchLabels:
          name: ingress-nginx
    ports:
    - protocol: TCP
      port: 80
  egress:
  - to:
    - podSelector:
        matchLabels:
          app: mariadb
    ports:
    - protocol: TCP
      port: 3306
  - to:
    - podSelector:
        matchLabels:
          app: redis
    ports:
    - protocol: TCP
      port: 6379
  - to:
    - namespaceSelector:
        matchLabels:
          name: kube-system
      podSelector:
        matchLabels:
          k8s-app: kube-dns
    ports:
    - protocol: UDP
      port: 53
    - protocol: TCP
      port: 53
  - to:
    - ipBlock:
        cidr: 0.0.0.0/0
        except:
        - 10.0.0.0/8
        - 172.16.0.0/12
        - 192.168.0.0/16
    ports:
    - protocol: TCP
      port: 443
```

2. WAF Rules:

Configure Web Application Firewall rules to protect against common attacks:

```
# ModSecurity Rules for Documents View
SecRule REQUEST_URI "/api/documents/.*" \
  "id:1001,phase:1,deny,status:403,msg:'Invalid document ID format',chain"
SecRule ARGS:id "!@rx ^[0-9]+$"

SecRule REQUEST_HEADERS:Content-Type "application/json" \
  "id:1002,phase:1,deny,status:403,msg:'JSON injection attempt',chain"
SecRule REQUEST_BODY "@contains script"
```

#### TLS Configuration

Configure TLS for all connections:

1. NGINX Ingress TLS configuration:

```yaml
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: documents-view
  namespace: documents-prod
  annotations:
    nginx.ingress.kubernetes.io/ssl-redirect: "true"
    nginx.ingress.kubernetes.io/ssl-ciphers: "ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384"
    nginx.ingress.kubernetes.io/ssl-protocols: "TLSv1.2 TLSv1.3"
    cert-manager.io/cluster-issuer: "letsencrypt-prod"
spec:
  tls:
  - hosts:
    - documents.insurepilot.example.com
    secretName: documents-tls
  rules:
  - host: documents.insurepilot.example.com
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: documents-view
            port:
              name: http
```

2. Service-to-service communication:

Configure mutual TLS (mTLS) for service-to-service communication using a service mesh like Istio:

```yaml
apiVersion: networking.istio.io/v1beta1
kind: PeerAuthentication
metadata:
  name: documents-view-mtls
  namespace: documents-prod
spec:
  selector:
    matchLabels:
      app: documents-view
  mtls:
    mode: STRICT
```

### Container Security

#### Image Scanning

Scan container images for vulnerabilities using Trivy:

```bash
# In CI/CD pipeline
trivy image --severity HIGH,CRITICAL ${CI_REGISTRY_IMAGE}:${CI_COMMIT_SHA}

# Regular scheduled scans
0 6 * * * /usr/local/bin/trivy image --severity HIGH,CRITICAL ${CI_REGISTRY_IMAGE}:latest > /var/log/trivy-scan-$(date +\%Y\%m\%d).log
```

#### Security Context

Configure secure pod security context:

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: documents-view
  namespace: documents-prod
spec:
  template:
    spec:
      securityContext:
        runAsNonRoot: true
        runAsUser: 1000
        fsGroup: 2000
      containers:
      - name: documents-view
        securityContext:
          allowPrivilegeEscalation: false
          capabilities:
            drop:
            - ALL
          readOnlyRootFilesystem: true
        volumeMounts:
        - name: tmp-dir
          mountPath: /tmp
        - name: var-run
          mountPath: /var/run
      volumes:
      - name: tmp-dir
        emptyDir: {}
      - name: var-run
        emptyDir: {}
```

#### Runtime Security

Configure Falco for runtime security monitoring:

```yaml
# falco_rules.yaml
- rule: Documents API Token Theft
  desc: Detect potential API token theft from documents application
  condition: >
    proc.name="curl" and 
    container.name="documents-view" and 
    container.image.repository="insurepilot/documents-view" and
    (proc.args contains "Authorization:" or 
     fd.name startswith "/var/run/secrets")
  output: >
    Potential API token theft in documents-view container
    (user=%user.name container=%container.name
    process=%proc.name cmdline=%proc.cmdline)
  priority: WARNING
```

### Access Control

#### RBAC Configuration

Configure Kubernetes RBAC for service accounts:

```yaml
apiVersion: v1
kind: ServiceAccount
metadata:
  name: documents-view
  namespace: documents-prod
---
apiVersion: rbac.authorization.k8s.io/v1
kind: Role
metadata:
  name: documents-view-role
  namespace: documents-prod
rules:
- apiGroups: [""]
  resources: ["configmaps", "secrets"]
  verbs: ["get", "list"]
- apiGroups: [""]
  resources: ["pods"]
  verbs: ["get", "list"]
---
apiVersion: rbac.authorization.k8s.io/v1
kind: RoleBinding
metadata:
  name: documents-view-rolebinding
  namespace: documents-prod
roleRef:
  apiGroup: rbac.authorization.k8s.io
  kind: Role
  name: documents-view-role
subjects:
- kind: ServiceAccount
  name: documents-view
  namespace: documents-prod
```

#### Secret Management

Use HashiCorp Vault for advanced secret management:

1. Configure Vault integration:

```yaml
# In Kubernetes deployment
apiVersion: apps/v1
kind: Deployment
metadata:
  name: documents-view
  namespace: documents-prod
spec:
  template:
    metadata:
      annotations:
        vault.hashicorp.com/agent-inject: "true"
        vault.hashicorp.com/agent-inject-secret-db-credentials: "database/creds/documents-view"
        vault.hashicorp.com/agent-inject-template-db-credentials: |
          {{- with secret "database/creds/documents-view" -}}
          export DB_USERNAME="{{ .Data.username }}"
          export DB_PASSWORD="{{ .Data.password }}"
          {{- end -}}
        vault.hashicorp.com/role: "documents-view"
```

2. Access secrets in the application:

```bash
source /vault/secrets/db-credentials
java -jar /app/application.jar
```

## Maintenance Procedures

### Routine Maintenance

#### Security Patching

Apply security patches monthly or as needed for critical vulnerabilities:

1. Update the base image in your Dockerfile:

```Dockerfile
# Before
FROM php:8.2-fpm-alpine

# After (with updated patch version)
FROM php:8.2.7-fpm-alpine
```

2. Rebuild and deploy the updated image:

```bash
# In CI/CD pipeline or manually
docker build -t ${CI_REGISTRY_IMAGE}:${CI_COMMIT_SHA} .
docker push ${CI_REGISTRY_IMAGE}:${CI_COMMIT_SHA}

# Deploy the updated image
helm upgrade --install documents-view ./helm/documents-view \
  --namespace documents-prod \
  --values helm/documents-view/values-production.yaml \
  --set image.tag=${CI_COMMIT_SHA}
```

#### Database Optimization

Perform regular database maintenance to ensure optimal performance:

1. Weekly index optimization:

```sql
-- Analyze and optimize tables (schedule during low traffic periods)
ANALYZE TABLE documents;
ANALYZE TABLE map_document_action;
ANALYZE TABLE map_document_file;
```

2. Monthly cleanup of temporary data:

```sql
-- Delete old temporary data (adjust retention period as needed)
DELETE FROM map_document_action 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
AND document_id IN (
  SELECT id FROM document WHERE status_id = 3 -- Trashed status
);
```

### Major Upgrades

#### Kubernetes Version Upgrade

Upgrade Kubernetes version using a blue-green approach:

1. Create a new cluster with the updated Kubernetes version:

```bash
# Using EKS
aws eks create-cluster \
  --name insure-pilot-prod-new \
  --kubernetes-version 1.27 \
  --role-arn arn:aws:iam::123456789012:role/eks-cluster-role \
  --resources-vpc-config subnetIds=subnet-abcdef,subnet-123456,securityGroupIds=sg-abcdef
  
# Wait for the new cluster to be ready
aws eks wait cluster-active --name insure-pilot-prod-new
```

2. Deploy the Documents View feature to the new cluster:

```bash
# Configure kubectl for the new cluster
aws eks update-kubeconfig --name insure-pilot-prod-new --region us-east-1

# Create namespace
kubectl create namespace documents-prod

# Apply configurations
kubectl apply -f kubernetes/production/namespace-setup.yaml

# Deploy using Helm
helm upgrade --install documents-view ./helm/documents-view \
  --namespace documents-prod \
  --values helm/documents-view/values-production.yaml
```

3. Test the deployment on the new cluster:

```bash
# Run smoke tests
kubectl exec -it -n documents-prod deploy/documents-view -- /app/bin/run-smoke-tests.sh

# Run load tests
k6 run -e TARGET_URL=https://documents-new.insurepilot.example.com load-tests/document-view.js
```

4. Switch traffic to the new cluster:

```bash
# Update DNS records to point to the new cluster's load balancer
aws route53 change-resource-record-sets \
  --hosted-zone-id Z1234567890ABC \
  --change-batch file://dns-update.json
```

5. Decommission the old cluster after validation:

```bash
# After confirming everything works correctly (typically 1-2 weeks)
aws eks delete-cluster --name insure-pilot-prod-old
```

#### Database Version Upgrade

Upgrade the MariaDB version using replica promotion:

1. Create a replica with the new version:

```bash
# Set up a new MariaDB instance with the upgraded version
docker run -d --name mariadb-new \
  -e MYSQL_ROOT_PASSWORD=root_password \
  -e MYSQL_DATABASE=documents \
  -e MYSQL_USER=documents_user \
  -e MYSQL_PASSWORD=documents_password \
  mariadb:10.7
```

2. Configure replication from the old to the new instance:

```sql
-- On the new MariaDB instance
CHANGE MASTER TO
  MASTER_HOST='mariadb-old',
  MASTER_USER='replication_user',
  MASTER_PASSWORD='replication_password',
  MASTER_LOG_FILE='mysql-bin.000001',
  MASTER_LOG_POS=4;
START SLAVE;
```

3. Verify replication is working correctly:

```sql
-- On the new MariaDB instance
SHOW SLAVE STATUS\G
```

4. Prepare for the switchover:

```bash
# Deploy a maintenance page
kubectl apply -f kubernetes/maintenance/maintenance-page.yaml

# Scale down the application
kubectl scale deployment documents-view --replicas=0 -n documents-prod
```

5. Perform the switchover:

```sql
-- On the new MariaDB instance
STOP SLAVE;
RESET SLAVE ALL;
```

6. Update the application configuration to point to the new database:

```bash
kubectl create configmap documents-view-config-new \
  --namespace=documents-prod \
  --from-literal=DB_HOST=mariadb-new \
  --from-literal=DB_PORT=3306 \
  --from-literal=DB_DATABASE=documents \
  --dry-run=client -o yaml | kubectl apply -f -
```

7. Restart the application:

```bash
kubectl scale deployment documents-view --replicas=3 -n documents-prod
```

8. Verify the application is working correctly:

```bash
# Run smoke tests
kubectl exec -it -n documents-prod deploy/documents-view -- /app/bin/run-smoke-tests.sh

# Remove maintenance page
kubectl delete -f kubernetes/maintenance/maintenance-page.yaml
```

### Troubleshooting

#### Common Issues and Resolution Steps

1. Pod startup failures:

```bash
# Check pod status
kubectl get pods -n documents-prod

# Check detailed pod information
kubectl describe pod documents-view-78d8f9b7c8-abcde -n documents-prod

# Check logs
kubectl logs documents-view-78d8f9b7c8-abcde -n documents-prod
```

Resolution:
- For missing secrets or config issues: verify ConfigMaps and Secrets
- For resource issues: check resource allocation and node capacity
- For image pull issues: verify registry access and image availability

2. Database connection issues:

```bash
# Check database connectivity from within a pod
kubectl exec -it documents-view-78d8f9b7c8-abcde -n documents-prod -- mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD -e "SELECT 1;"

# Check database logs
kubectl logs -l app=mariadb -n database
```

Resolution:
- For authentication issues: verify credentials in Secrets
- For network issues: check Network Policies and firewall rules
- For database performance issues: check slow query logs and connection limits

3. High error rates or latency:

```bash
# Query error rate and latency metrics
curl -s http://prometheus:9090/api/v1/query?query=sum(rate(http_requests_total{job="documents-view",status=~"5.."}[5m]))/sum(rate(http_requests_total{job="documents-view"}[5m]))

# Check detailed error logs
kubectl exec -it documents-view-78d8f9b7c8-abcde -n documents-prod -- cat /var/log/app/error.log
```

Resolution:
- For code issues: deploy a previous known-good version
- For resource constraints: increase resource limits or scale horizontally
- For external dependencies: check connectivity to Adobe PDF SDK and other services

## Appendices

### Deployment Checklist

Use this checklist before and after each deployment:

#### Pre-Deployment Checks

- [ ] All CI/CD pipeline tests pass
- [ ] Security scan shows no HIGH or CRITICAL vulnerabilities
- [ ] Database migrations are backward compatible
- [ ] Staging environment tests pass
- [ ] Resource requirements have been reviewed
- [ ] Load testing results are acceptable
- [ ] Rollback plan is documented and tested
- [ ] Monitoring and alerting are configured for new metrics
- [ ] Release has been approved by product owner

#### Post-Deployment Checks

- [ ] All pods are running and healthy
- [ ] API endpoints return 200 OK responses
- [ ] Document viewing functionality works correctly
- [ ] Metadata editing functionality works correctly
- [ ] Error rates are within acceptable limits
- [ ] Response times are within performance SLAs
- [ ] Logs show no unexpected errors
- [ ] Monitoring dashboards show normal operations
- [ ] Database performance is normal

### Configuration Reference

#### Environment Variable Reference

| Variable | Description | Required | Default | Example |
|----------|-------------|----------|---------|---------|
| `DOCUMENT_VIEWER_ENABLED` | Enable/disable feature | Yes | `true` | `true` |
| `DOCUMENT_VIEWER_ADOBE_SDK_URL` | URL to Adobe PDF SDK | Yes | `https://documentcloud.adobe.com/view-sdk/main.js` | `https://documentcloud.adobe.com/view-sdk/main.js` |
| `DOCUMENT_VIEWER_MAX_FILE_SIZE` | Maximum file size in MB | Yes | `50` | `50` |
| `DOCUMENT_VIEWER_ALLOWED_TYPES` | Allowed document types | Yes | `pdf,docx,xlsx,pptx` | `pdf,docx,xlsx,pptx` |
| `DOCUMENT_VIEWER_CACHE_TTL` | Cache time-to-live in seconds | Yes | `3600` | `3600` |
| `DOCUMENT_VIEWER_AUDIT_ENABLED` | Enable detailed audit logging | Yes | `true` | `true` |
| `APP_ENV` | Application environment | Yes | - | `production` |
| `APP_DEBUG` | Enable debug mode | No | `false` | `false` |
| `DB_HOST` | Database hostname | Yes | - | `mariadb.database` |
| `DB_PORT` | Database port | No | `3306` | `3306` |
| `DB_DATABASE` | Database name | Yes | - | `documents` |
| `DB_USERNAME` | Database username | Yes | - | `documents_user` |
| `DB_PASSWORD` | Database password | Yes | - | `secure_password` |
| `REDIS_HOST` | Redis hostname | Yes | - | `redis.cache` |
| `REDIS_PORT` | Redis port | No | `6379` | `6379` |
| `REDIS_PASSWORD` | Redis password | No | - | `secure_password` |
| `ADOBE_PDF_SDK_CLIENT_ID` | Adobe SDK client ID | Yes | - | `your_client_id` |
| `ADOBE_PDF_SDK_CLIENT_SECRET` | Adobe SDK client secret | Yes | - | `your_client_secret` |
| `LOG_LEVEL` | Application log level | No | `warning` | `info` |

#### Helm Values Reference

```yaml
# Default values for documents-view
replicaCount: 3

image:
  repository: registry.example.com/insurepilot/documents-view
  tag: latest
  pullPolicy: IfNotPresent

nameOverride: ""
fullnameOverride: ""

serviceAccount:
  create: true
  annotations: {}
  name: ""

podAnnotations: {}

podSecurityContext:
  runAsNonRoot: true
  runAsUser: 1000
  fsGroup: 2000

securityContext:
  allowPrivilegeEscalation: false
  capabilities:
    drop:
    - ALL
  readOnlyRootFilesystem: true

service:
  type: ClusterIP
  port: 80

ingress:
  enabled: true
  className: nginx
  annotations:
    nginx.ingress.kubernetes.io/ssl-redirect: "true"
    cert-manager.io/cluster-issuer: "letsencrypt-prod"
  hosts:
    - host: documents.insurepilot.example.com
      paths:
        - path: /
          pathType: Prefix
  tls:
    - secretName: documents-tls
      hosts:
        - documents.insurepilot.example.com

resources:
  limits:
    cpu: 4
    memory: 8Gi
  requests:
    cpu: 2
    memory: 4Gi

autoscaling:
  enabled: true
  minReplicas: 3
  maxReplicas: 10
  targetCPUUtilizationPercentage: 70
  targetMemoryUtilizationPercentage: 80

nodeSelector: {}

tolerations: []

affinity:
  podAntiAffinity:
    preferredDuringSchedulingIgnoredDuringExecution:
    - weight: 100
      podAffinityTerm:
        labelSelector:
          matchExpressions:
          - key: app.kubernetes.io/name
            operator: In
            values:
            - documents-view
        topologyKey: "kubernetes.io/hostname"

env:
  DOCUMENT_VIEWER_ENABLED: "true"
  DOCUMENT_VIEWER_ADOBE_SDK_URL: "https://documentcloud.adobe.com/view-sdk/main.js"
  DOCUMENT_VIEWER_MAX_FILE_SIZE: "50"
  DOCUMENT_VIEWER_ALLOWED_TYPES: "pdf,docx,xlsx,pptx"
  DOCUMENT_VIEWER_CACHE_TTL: "3600"
  DOCUMENT_VIEWER_AUDIT_ENABLED: "true"
  APP_ENV: "production"
  APP_DEBUG: "false"
  LOG_LEVEL: "warning"

# External services configuration
externalServices:
  database:
    host: "mariadb.database"
    port: 3306
    database: "documents"
    existingSecret: "documents-db-credentials"
    secretKeys:
      username: "username"
      password: "password"
  redis:
    host: "redis.cache"
    port: 6379
    existingSecret: "documents-redis-credentials"
    secretKeys:
      password: "password"
  adobe:
    existingSecret: "documents-adobe-credentials"
    secretKeys:
      clientId: "client-id"
      clientSecret: "client-secret"
```

### Command Reference

#### Kubernetes Commands

```bash
# Get pod status
kubectl get pods -n documents-prod

# Get pod logs
kubectl logs -l app=documents-view -n documents-prod

# Describe a pod
kubectl describe pod documents-view-78d8f9b7c8-abcde -n documents-prod

# Execute a command in a pod
kubectl exec -it documents-view-78d8f9b7c8-abcde -n documents-prod -- /bin/sh

# Get service details
kubectl get services -n documents-prod

# Check ingress configuration
kubectl get ingress -n documents-prod

# View ConfigMaps
kubectl get configmap -n documents-prod

# View Secrets (without values)
kubectl get secrets -n documents-prod

# Check Horizontal Pod Autoscaler
kubectl get hpa -n documents-prod

# View resource usage
kubectl top pods -n documents-prod
```

#### Helm Commands

```bash
# Install or upgrade a release
helm upgrade --install documents-view ./helm/documents-view \
  --namespace documents-prod \
  --values helm/documents-view/values-production.yaml

# List releases
helm list -n documents-prod

# Get release history
helm history documents-view -n documents-prod

# Rollback to a previous release
helm rollback documents-view 1 -n documents-prod

# Uninstall a release
helm uninstall documents-view -n documents-prod

# Render templates for debugging
helm template documents-view ./helm/documents-view \
  --values helm/documents-view/values-production.yaml
```

#### Troubleshooting Commands

```bash
# Check DNS resolution
kubectl exec -it documents-view-78d8f9b7c8-abcde -n documents-prod -- nslookup mariadb.database

# Check network connectivity
kubectl exec -it documents-view-78d8f9b7c8-abcde -n documents-prod -- curl -v telnet://mariadb.database:3306

# Check API endpoints
kubectl exec -it documents-view-78d8f9b7c8-abcde -n documents-prod -- curl -v http://localhost/api/health

# View application logs
kubectl exec -it documents-view-78d8f9b7c8-abcde -n documents-prod -- tail -f /var/log/app/error.log

# Check resource usage
kubectl exec -it documents-view-78d8f9b7c8-abcde -n documents-prod -- top -b -n 1

# Check file permissions
kubectl exec -it documents-view-78d8f9b7c8-abcde -n documents-prod -- ls -la /data/documents/
```

## References

- [Kubernetes Documentation](https://kubernetes.io/docs/)
- [Docker Documentation](https://docs.docker.com/)
- [GitLab CI/CD Documentation](https://docs.gitlab.com/ee/ci/)
- [Prometheus Documentation](https://prometheus.io/docs/)
- [Grafana Documentation](https://grafana.com/docs/)
- [Helm Documentation](https://helm.sh/docs/)
- [MariaDB Documentation](https://mariadb.com/kb/en/documentation/)
- [Adobe PDF Embed API Documentation](https://developer.adobe.com/document-services/docs/overview/pdf-embed-api/)
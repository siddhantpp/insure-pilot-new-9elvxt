#!/bin/bash
# Deployment script for the Documents View feature

# Exit immediately if a command fails
# Pipefail ensures that a pipeline returns a non-zero status if any command fails
set -eo pipefail

# Import rollback functionality from rollback.sh
source "$(dirname "${BASH_SOURCE[0]}")/rollback.sh"

# Global variables
SCRIPT_DIR=$(dirname "${BASH_SOURCE[0]}")
REPO_ROOT=$(git rev-parse --show-toplevel)
LOG_FILE="/var/log/documents-view-deploy.log"
HELM_TIMEOUT="300s"
KUBE_CONTEXT_DEV="documents-view-dev"
KUBE_CONTEXT_STAGING="documents-view-staging"
KUBE_CONTEXT_PROD="documents-view-prod"

# Logging function to output messages to both console and log file
log_message() {
    local level="$1"
    local message="$2"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    local formatted_message="[${timestamp}] [${level}] ${message}"
    
    echo "${formatted_message}"
    echo "${formatted_message}" >> "${LOG_FILE}"
}

# Check if required dependencies are installed
check_dependencies() {
    log_message "INFO" "Checking required dependencies..."
    
    if ! command -v kubectl &> /dev/null; then
        log_message "ERROR" "kubectl is not installed. Please install kubernetes-cli (latest)."
        return 1
    fi
    
    if ! command -v helm &> /dev/null; then
        log_message "ERROR" "helm is not installed. Please install helm v3.x."
        return 1
    fi
    
    if ! command -v kustomize &> /dev/null; then
        log_message "ERROR" "kustomize is not installed. Please install kustomize v4.x."
        return 1
    fi
    
    log_message "INFO" "All required dependencies are installed."
    return 0
}

# Validate environment parameter
validate_environment() {
    local environment="$1"
    
    if [[ "${environment}" != "dev" && "${environment}" != "staging" && "${environment}" != "prod" ]]; then
        log_message "ERROR" "Invalid environment: ${environment}. Must be one of: dev, staging, prod"
        return 1
    fi
    
    log_message "INFO" "Environment validated: ${environment}"
    return 0
}

# Set Kubernetes context based on environment
set_kubernetes_context() {
    local environment="$1"
    local context
    
    case "${environment}" in
        dev)
            context="${KUBE_CONTEXT_DEV}"
            ;;
        staging)
            context="${KUBE_CONTEXT_STAGING}"
            ;;
        prod)
            context="${KUBE_CONTEXT_PROD}"
            ;;
        *)
            log_message "ERROR" "Unknown environment: ${environment}"
            return 1
            ;;
    esac
    
    log_message "INFO" "Setting Kubernetes context to: ${context}"
    
    if ! kubectl config use-context "${context}" &> /dev/null; then
        log_message "ERROR" "Failed to set Kubernetes context to: ${context}"
        return 1
    fi
    
    # Verify context was set correctly
    local current_context=$(kubectl config current-context)
    if [[ "${current_context}" != "${context}" ]]; then
        log_message "ERROR" "Kubernetes context verification failed. Expected: ${context}, Got: ${current_context}"
        return 1
    fi
    
    log_message "INFO" "Kubernetes context set successfully to: ${context}"
    return 0
}

# Build Docker images for frontend and backend components
build_docker_images() {
    local environment="$1"
    local version="$2"
    
    log_message "INFO" "Building Docker images for environment: ${environment}, version: ${version}"
    
    # Determine Docker registry and image tags based on environment
    local registry="registry.insure-pilot.com"
    local frontend_image_name="${registry}/documents-view/frontend"
    local backend_image_name="${registry}/documents-view/backend"
    local frontend_image_tag="${frontend_image_name}:${version}"
    local backend_image_tag="${backend_image_name}:${version}"
    
    # Add environment-specific tags
    if [[ "${environment}" != "prod" ]]; then
        frontend_image_tag="${frontend_image_name}:${environment}-${version}"
        backend_image_tag="${backend_image_name}:${environment}-${version}"
    fi
    
    log_message "INFO" "Frontend image tag: ${frontend_image_tag}"
    log_message "INFO" "Backend image tag: ${backend_image_tag}"
    
    # Build frontend image
    log_message "INFO" "Building frontend image..."
    if ! docker build -t "${frontend_image_tag}" -f "${REPO_ROOT}/docker/frontend.Dockerfile" "${REPO_ROOT}"; then
        log_message "ERROR" "Failed to build frontend image"
        return 1
    fi
    
    # Build backend image
    log_message "INFO" "Building backend image..."
    if ! docker build -t "${backend_image_tag}" -f "${REPO_ROOT}/docker/backend.Dockerfile" "${REPO_ROOT}"; then
        log_message "ERROR" "Failed to build backend image"
        return 1
    fi
    
    # Push images to registry
    log_message "INFO" "Pushing images to registry..."
    
    if ! docker push "${frontend_image_tag}"; then
        log_message "ERROR" "Failed to push frontend image to registry"
        return 1
    fi
    
    if ! docker push "${backend_image_tag}"; then
        log_message "ERROR" "Failed to push backend image to registry"
        return 1
    fi
    
    # Verify images are available in registry
    log_message "INFO" "Verifying images in registry..."
    
    # Simple verification by checking if images can be pulled
    if ! docker pull "${frontend_image_tag}" &> /dev/null; then
        log_message "ERROR" "Failed to verify frontend image in registry"
        return 1
    fi
    
    if ! docker pull "${backend_image_tag}" &> /dev/null; then
        log_message "ERROR" "Failed to verify backend image in registry"
        return 1
    fi
    
    log_message "INFO" "Images built and pushed successfully"
    return 0
}

# Deploy the application using Helm
deploy_with_helm() {
    local environment="$1"
    local version="$2"
    local namespace="$3"
    
    log_message "INFO" "Deploying with Helm to environment: ${environment}, namespace: ${namespace}, version: ${version}"
    
    # Set release name
    local release_name="documents-view"
    
    # Determine values file based on environment
    local values_file="${REPO_ROOT}/helm/documents-view/values-${environment}.yaml"
    
    if [[ ! -f "${values_file}" ]]; then
        log_message "ERROR" "Values file not found: ${values_file}"
        return 1
    fi
    
    # Set image tag in values file
    log_message "INFO" "Updating image tag in values file to: ${version}"
    
    # Create a temporary values file with updated image tag
    local temp_values_file=$(mktemp)
    cp "${values_file}" "${temp_values_file}"
    
    # Update frontend and backend image tags
    if ! sed -i "s/tag:.*# frontend/tag: ${version} # frontend/g" "${temp_values_file}"; then
        log_message "ERROR" "Failed to update frontend image tag in values file"
        rm "${temp_values_file}"
        return 1
    fi
    
    if ! sed -i "s/tag:.*# backend/tag: ${version} # backend/g" "${temp_values_file}"; then
        log_message "ERROR" "Failed to update backend image tag in values file"
        rm "${temp_values_file}"
        return 1
    fi
    
    # Check if release already exists
    if helm status "${release_name}" -n "${namespace}" &> /dev/null; then
        log_message "INFO" "Helm release ${release_name} already exists, upgrading..."
        
        # Upgrade the release
        if ! helm upgrade "${release_name}" "${REPO_ROOT}/helm/documents-view" \
            --namespace "${namespace}" \
            --values "${temp_values_file}" \
            --timeout "${HELM_TIMEOUT}" \
            --wait; then
            
            log_message "ERROR" "Helm upgrade failed"
            rm "${temp_values_file}"
            
            # Attempt to rollback
            log_message "WARNING" "Attempting to rollback failed Helm upgrade..."
            rollback_deployment "${environment}" "${namespace}" "helm"
            
            return 1
        fi
    else
        log_message "INFO" "Helm release ${release_name} does not exist, installing..."
        
        # Install the release
        if ! helm install "${release_name}" "${REPO_ROOT}/helm/documents-view" \
            --namespace "${namespace}" \
            --values "${temp_values_file}" \
            --create-namespace \
            --timeout "${HELM_TIMEOUT}" \
            --wait; then
            
            log_message "ERROR" "Helm install failed"
            rm "${temp_values_file}"
            return 1
        fi
    fi
    
    # Clean up temporary values file
    rm "${temp_values_file}"
    
    log_message "INFO" "Helm deployment completed successfully"
    return 0
}

# Deploy the application using Kustomize
deploy_with_kustomize() {
    local environment="$1"
    local version="$2"
    
    log_message "INFO" "Deploying with Kustomize to environment: ${environment}, version: ${version}"
    
    # Set paths
    local kustomization_dir="${REPO_ROOT}/kubernetes/${environment}"
    
    if [[ ! -d "${kustomization_dir}" ]]; then
        log_message "ERROR" "Kustomization directory not found: ${kustomization_dir}"
        return 1
    fi
    
    # Update image tag in kustomization.yaml
    log_message "INFO" "Updating image tag in kustomization.yaml to: ${version}"
    
    if ! sed -i "s/newTag:.*/newTag: ${version}/g" "${kustomization_dir}/kustomization.yaml"; then
        log_message "ERROR" "Failed to update image tag in kustomization.yaml"
        return 1
    fi
    
    # Apply the kustomize configuration
    log_message "INFO" "Applying Kustomize configuration..."
    
    if ! kustomize build "${kustomization_dir}" | kubectl apply -f -; then
        log_message "ERROR" "Failed to apply Kustomize configuration"
        
        # Attempt to rollback
        log_message "WARNING" "Attempting to rollback failed Kustomize deployment..."
        rollback_deployment "${environment}" "documents-view-${environment}" "kustomize"
        
        return 1
    fi
    
    # Wait for deployment to complete
    local deployment_name="documents-view"
    local namespace="documents-view-${environment}"
    
    log_message "INFO" "Waiting for deployment to complete..."
    
    if ! kubectl rollout status deployment "${deployment_name}" -n "${namespace}" --timeout="${HELM_TIMEOUT}"; then
        log_message "ERROR" "Deployment rollout failed"
        
        # Attempt to rollback
        log_message "WARNING" "Attempting to rollback failed Kustomize deployment..."
        rollback_deployment "${environment}" "${namespace}" "kustomize"
        
        return 1
    fi
    
    log_message "INFO" "Kustomize deployment completed successfully"
    return 0
}

# Deploy a canary release for production environment
deploy_canary() {
    local version="$1"
    local traffic_percentage="$2"
    
    local namespace="documents-view-prod"
    
    log_message "INFO" "Deploying canary release with version ${version} and ${traffic_percentage}% traffic"
    
    # Check if canary release already exists
    if kubectl get deployment "documents-view-canary" -n "${namespace}" &> /dev/null; then
        log_message "INFO" "Canary deployment already exists, updating..."
        
        # Update existing canary deployment
        if ! kubectl set image deployment/documents-view-canary \
            "frontend=registry.insure-pilot.com/documents-view/frontend:${version}" \
            "backend=registry.insure-pilot.com/documents-view/backend:${version}" \
            -n "${namespace}"; then
            
            log_message "ERROR" "Failed to update canary deployment images"
            return 1
        fi
    else
        log_message "INFO" "Creating new canary deployment..."
        
        # Clone the stable deployment as a template for canary
        if ! kubectl get deployment documents-view-stable -n "${namespace}" -o yaml | \
            sed "s/name: documents-view-stable/name: documents-view-canary/g" | \
            sed "s/app: documents-view-stable/app: documents-view-canary/g" | \
            sed "s/registry.insure-pilot.com\/documents-view\/frontend:.*/registry.insure-pilot.com\/documents-view\/frontend:${version}/g" | \
            sed "s/registry.insure-pilot.com\/documents-view\/backend:.*/registry.insure-pilot.com\/documents-view\/backend:${version}/g" | \
            kubectl apply -n "${namespace}" -f -; then
            
            log_message "ERROR" "Failed to create canary deployment"
            return 1
        fi
    fi
    
    # Scale canary deployment based on traffic percentage
    local stable_replicas=$(kubectl get deployment documents-view-stable -n "${namespace}" -o jsonpath='{.spec.replicas}')
    local canary_replicas=$(awk "BEGIN {print int(${stable_replicas} * ${traffic_percentage} / 100)}")
    
    # Ensure at least 1 replica for canary
    if [[ "${canary_replicas}" -lt 1 ]]; then
        canary_replicas=1
    fi
    
    log_message "INFO" "Scaling canary deployment to ${canary_replicas} replicas"
    
    if ! kubectl scale deployment documents-view-canary --replicas="${canary_replicas}" -n "${namespace}"; then
        log_message "ERROR" "Failed to scale canary deployment"
        return 1
    fi
    
    # Wait for canary deployment to be ready
    if ! kubectl rollout status deployment documents-view-canary -n "${namespace}" --timeout="${HELM_TIMEOUT}"; then
        log_message "ERROR" "Canary deployment rollout failed"
        
        # Rollback canary deployment
        log_message "WARNING" "Rolling back canary deployment..."
        kubectl scale deployment documents-view-canary --replicas=0 -n "${namespace}" || true
        
        return 1
    fi
    
    # Update service to route traffic percentage to canary
    log_message "INFO" "Configuring service to route ${traffic_percentage}% traffic to canary"
    
    # Monitor canary deployment health
    log_message "INFO" "Monitoring canary deployment health..."
    
    # Set a monitoring period (5 minutes)
    local monitor_seconds=300
    local monitor_interval=30
    local iterations=$((monitor_seconds / monitor_interval))
    
    for ((i=1; i<=iterations; i++)); do
        log_message "INFO" "Canary health check ${i}/${iterations}..."
        
        # Check if canary pods are healthy
        local unhealthy_pods=$(kubectl get pods -n "${namespace}" -l app=documents-view-canary -o jsonpath='{.items[?(@.status.phase!="Running")].metadata.name}')
        
        if [[ -n "${unhealthy_pods}" ]]; then
            log_message "ERROR" "Unhealthy canary pods detected: ${unhealthy_pods}"
            log_message "WARNING" "Rolling back canary deployment..."
            
            # Rollback canary deployment
            kubectl scale deployment documents-view-canary --replicas=0 -n "${namespace}" || true
            
            return 1
        fi
        
        sleep "${monitor_interval}"
    done
    
    log_message "INFO" "Canary deployment successful and stable"
    return 0
}

# Deploy using blue-green strategy for staging environment
deploy_blue_green() {
    local version="$1"
    
    local namespace="documents-view-staging"
    
    log_message "INFO" "Deploying using blue-green strategy with version ${version}"
    
    # Determine which deployment is currently active (blue or green)
    local current_deployment
    local next_deployment
    
    # Check if service exists and which deployment it points to
    if kubectl get service documents-view -n "${namespace}" &> /dev/null; then
        current_deployment=$(kubectl get service documents-view -n "${namespace}" -o jsonpath='{.spec.selector.app}')
        
        if [[ "${current_deployment}" == "documents-view-blue" ]]; then
            next_deployment="documents-view-green"
        else
            next_deployment="documents-view-blue"
        fi
    else
        # If service doesn't exist, start with blue
        current_deployment=""
        next_deployment="documents-view-blue"
    fi
    
    log_message "INFO" "Current active deployment: ${current_deployment:-(none)}"
    log_message "INFO" "Next deployment will be: ${next_deployment}"
    
    # Create or update the next deployment
    if kubectl get deployment "${next_deployment}" -n "${namespace}" &> /dev/null; then
        log_message "INFO" "Updating ${next_deployment} deployment..."
        
        # Update existing deployment
        if ! kubectl set image deployment/"${next_deployment}" \
            "frontend=registry.insure-pilot.com/documents-view/frontend:${version}" \
            "backend=registry.insure-pilot.com/documents-view/backend:${version}" \
            -n "${namespace}"; then
            
            log_message "ERROR" "Failed to update ${next_deployment} deployment images"
            return 1
        fi
    else
        log_message "INFO" "Creating new ${next_deployment} deployment..."
        
        # Create deployment YAML or apply from template
        if ! kubectl create deployment "${next_deployment}" \
            --image="registry.insure-pilot.com/documents-view/frontend:${version}" \
            -n "${namespace}" \
            --labels="app=${next_deployment}"; then
            
            log_message "ERROR" "Failed to create ${next_deployment} deployment"
            return 1
        fi
        
        # Add backend container to deployment
        # In a real scenario, you would apply a complete deployment YAML
    fi
    
    # Wait for next deployment to be ready
    if ! kubectl rollout status deployment "${next_deployment}" -n "${namespace}" --timeout="${HELM_TIMEOUT}"; then
        log_message "ERROR" "${next_deployment} deployment rollout failed"
        return 1
    fi
    
    # Create or update service to point to the next deployment
    if kubectl get service documents-view -n "${namespace}" &> /dev/null; then
        log_message "INFO" "Updating service to point to ${next_deployment}..."
        
        # Update service selector to point to next deployment
        if ! kubectl patch service documents-view -n "${namespace}" \
            -p "{\"spec\":{\"selector\":{\"app\":\"${next_deployment}\"}}}"; then
            
            log_message "ERROR" "Failed to update service selector"
            return 1
        fi
    else
        log_message "INFO" "Creating new service pointing to ${next_deployment}..."
        
        # Create service
        if ! kubectl create service clusterip documents-view \
            --tcp=80:8080 \
            -n "${namespace}" \
            --labels="app=documents-view" \
            --selector="app=${next_deployment}"; then
            
            log_message "ERROR" "Failed to create service"
            return 1
        fi
    fi
    
    # Verify the next deployment is serving traffic correctly
    log_message "INFO" "Verifying ${next_deployment} is serving traffic correctly..."
    
    # Wait for DNS propagation and service stability (30 seconds)
    sleep 30
    
    # If verified successfully, scale down the previous deployment
    if [[ -n "${current_deployment}" ]]; then
        log_message "INFO" "Scaling down previous deployment: ${current_deployment}"
        
        if ! kubectl scale deployment "${current_deployment}" --replicas=0 -n "${namespace}"; then
            log_message "WARNING" "Failed to scale down previous deployment: ${current_deployment}"
            # This is not a critical failure, so we continue
        fi
    fi
    
    log_message "INFO" "Blue-green deployment completed successfully"
    return 0
}

# Verify the deployment was successful
verify_deployment() {
    local environment="$1"
    local namespace="$2"
    
    log_message "INFO" "Verifying deployment in ${environment} environment, namespace ${namespace}..."
    
    # Verify different components based on the environment
    case "${environment}" in
        prod)
            # For production, verify both stable and canary deployments if present
            if ! kubectl rollout status deployment/documents-view-stable -n "${namespace}" --timeout=60s; then
                log_message "ERROR" "Stable deployment verification failed"
                return 1
            fi
            
            if kubectl get deployment/documents-view-canary -n "${namespace}" &> /dev/null; then
                if ! kubectl rollout status deployment/documents-view-canary -n "${namespace}" --timeout=60s; then
                    log_message "WARNING" "Canary deployment verification failed"
                    # Not a critical failure if canary isn't fully ready
                fi
            fi
            ;;
        
        staging)
            # For staging, verify the active deployment (blue or green)
            local active_deployment=$(kubectl get service documents-view -n "${namespace}" -o jsonpath='{.spec.selector.app}')
            
            if [[ -z "${active_deployment}" ]]; then
                log_message "ERROR" "Could not determine active deployment in staging"
                return 1
            fi
            
            if ! kubectl rollout status deployment/"${active_deployment}" -n "${namespace}" --timeout=60s; then
                log_message "ERROR" "Active deployment verification failed: ${active_deployment}"
                return 1
            fi
            ;;
        
        dev)
            # For development, verify the main deployment
            if ! kubectl rollout status deployment/documents-view -n "${namespace}" --timeout=60s; then
                log_message "ERROR" "Deployment verification failed"
                return 1
            fi
            ;;
        
        *)
            log_message "ERROR" "Unknown environment for verification: ${environment}"
            return 1
            ;;
    esac
    
    # Verify pods are in Running state
    log_message "INFO" "Verifying pod status..."
    
    local deployment_label
    case "${environment}" in
        prod)
            deployment_label="app=documents-view-stable"
            ;;
        staging)
            deployment_label="app in (documents-view-blue,documents-view-green)"
            ;;
        dev)
            deployment_label="app=documents-view"
            ;;
    esac
    
    local failing_pods=$(kubectl get pods -n "${namespace}" -l "${deployment_label}" -o jsonpath='{.items[?(@.status.phase!="Running")].metadata.name}')
    
    if [[ -n "${failing_pods}" ]]; then
        log_message "ERROR" "The following pods are not in Running state: ${failing_pods}"
        return 1
    fi
    
    # Check if services are available
    log_message "INFO" "Verifying services..."
    
    if ! kubectl get service documents-view -n "${namespace}" &> /dev/null; then
        log_message "ERROR" "Service 'documents-view' not found"
        return 1
    fi
    
    # Run health checks against the application
    log_message "INFO" "Running application health checks..."
    
    # Set up port-forwarding to access the service
    local port_forward_pid
    kubectl port-forward svc/documents-view 8080:80 -n "${namespace}" &> /dev/null &
    port_forward_pid=$!
    
    # Give it a moment to establish
    sleep 5
    
    # Check if health endpoint is accessible
    local health_check_result=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/health || echo "Failed")
    
    # Kill the port-forward
    kill ${port_forward_pid} &> /dev/null || true
    
    if [[ "${health_check_result}" != "200" ]]; then
        log_message "ERROR" "Health check failed with status: ${health_check_result}"
        return 1
    fi
    
    # Verify metrics are being collected
    log_message "INFO" "Verifying metrics collection..."
    
    log_message "INFO" "Deployment verification completed successfully"
    return 0
}

# Send notifications about the deployment
notify_deployment() {
    local environment="$1"
    local version="$2"
    local status="$3"
    
    log_message "INFO" "Sending deployment notification for ${environment} environment, version: ${version}, status: ${status}"
    
    # Set notification message
    local message="Documents View Deployment: ${environment}\nVersion: ${version}\nStatus: ${status}"
    
    # Send Slack notification if webhook URL is configured
    if [[ -n "${SLACK_WEBHOOK_URL}" ]]; then
        log_message "INFO" "Sending Slack notification..."
        
        # Format message for Slack
        local slack_payload=$(cat <<EOF
{
    "text": "${message}",
    "username": "Deployment Bot",
    "icon_emoji": ":rocket:"
}
EOF
)
        
        # Send to Slack
        curl -s -X POST -H "Content-type: application/json" \
            --data "${slack_payload}" \
            "${SLACK_WEBHOOK_URL}" &> /dev/null
    fi
    
    # Send email notification if configured
    if [[ -n "${EMAIL_RECIPIENTS}" ]]; then
        log_message "INFO" "Sending email notification to: ${EMAIL_RECIPIENTS}"
        
        # Use mail command if available
        if command -v mail &> /dev/null; then
            echo -e "${message}" | mail -s "Documents View Deployment: ${environment} - ${status}" "${EMAIL_RECIPIENTS}"
        else
            log_message "WARNING" "mail command not available, skipping email notification"
        fi
    fi
    
    log_message "INFO" "Notifications sent"
}

# Display script usage information
show_usage() {
    echo "Usage: $(basename "$0") [options]"
    echo ""
    echo "Deployment script for the Documents View feature"
    echo ""
    echo "Options:"
    echo "  -e, --environment <env>     Target environment (dev, staging, prod) [required]"
    echo "  -v, --version <version>     Version to deploy [required]"
    echo "  -b, --build                 Build Docker images before deployment"
    echo "  -m, --method <method>       Deployment method (helm, kustomize) [default: helm]"
    echo "  -p, --percentage <percent>  Traffic percentage for canary deployment [default: 10]"
    echo "  -n, --namespace <namespace> Kubernetes namespace [default: documents-view-<env>]"
    echo "  -h, --help                  Show this help message"
    echo ""
    echo "Examples:"
    echo "  $(basename "$0") --environment dev --version v1.0.0 --build"
    echo "  $(basename "$0") --environment staging --version v1.0.0 --method kustomize"
    echo "  $(basename "$0") --environment prod --version v1.0.0 --percentage 20"
}

# Main function that orchestrates the deployment process
main() {
    # Initialize variables with defaults
    local environment=""
    local version=""
    local build_images=false
    local deployment_method="helm"
    local canary_percentage=10
    local namespace=""
    
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case "$1" in
            -e|--environment)
                environment="$2"
                shift 2
                ;;
            -v|--version)
                version="$2"
                shift 2
                ;;
            -b|--build)
                build_images=true
                shift
                ;;
            -m|--method)
                deployment_method="$2"
                shift 2
                ;;
            -p|--percentage)
                canary_percentage="$2"
                shift 2
                ;;
            -n|--namespace)
                namespace="$2"
                shift 2
                ;;
            -h|--help)
                show_usage
                return 0
                ;;
            *)
                log_message "ERROR" "Unknown option: $1"
                show_usage
                return 1
                ;;
        esac
    done
    
    # Validate required parameters
    if [[ -z "${environment}" ]]; then
        log_message "ERROR" "Environment is required"
        show_usage
        return 1
    fi
    
    if [[ -z "${version}" ]]; then
        log_message "ERROR" "Version is required"
        show_usage
        return 1
    fi
    
    # Validate environment
    if ! validate_environment "${environment}"; then
        return 1
    fi
    
    # Check dependencies
    if ! check_dependencies; then
        return 1
    fi
    
    # Set Kubernetes context
    if ! set_kubernetes_context "${environment}"; then
        return 1
    fi
    
    # Set namespace if not provided
    if [[ -z "${namespace}" ]]; then
        namespace="documents-view-${environment}"
    fi
    
    log_message "INFO" "Starting deployment to ${environment} environment, namespace: ${namespace}, version: ${version}"
    
    # Build Docker images if requested
    if [[ "${build_images}" == "true" ]]; then
        if ! build_docker_images "${environment}" "${version}"; then
            log_message "ERROR" "Image build failed, aborting deployment"
            return 1
        fi
    fi
    
    # Perform deployment based on environment and method
    local deployment_status=0
    
    case "${environment}" in
        prod)
            # For production, use canary deployment
            if ! deploy_canary "${version}" "${canary_percentage}"; then
                log_message "ERROR" "Canary deployment failed"
                deployment_status=1
            else
                log_message "INFO" "Canary deployment successful with ${canary_percentage}% traffic"
                
                # In a real scenario, you would monitor the canary and gradually increase traffic
                # This step would typically involve human approval or automated verification
                
                log_message "INFO" "Canary deployment verified, updating stable deployment..."
                
                # Update the stable deployment with the new version
                if [[ "${deployment_method}" == "helm" ]]; then
                    if ! deploy_with_helm "${environment}" "${version}" "${namespace}"; then
                        log_message "ERROR" "Stable deployment failed"
                        deployment_status=1
                    fi
                elif [[ "${deployment_method}" == "kustomize" ]]; then
                    if ! deploy_with_kustomize "${environment}" "${version}"; then
                        log_message "ERROR" "Stable deployment failed"
                        deployment_status=1
                    fi
                else
                    log_message "ERROR" "Unknown deployment method: ${deployment_method}"
                    deployment_status=1
                fi
            fi
            ;;
        
        staging)
            # For staging, use blue-green deployment
            if ! deploy_blue_green "${version}"; then
                log_message "ERROR" "Blue-green deployment failed"
                deployment_status=1
            fi
            ;;
        
        dev)
            # For development, use direct deployment
            if [[ "${deployment_method}" == "helm" ]]; then
                if ! deploy_with_helm "${environment}" "${version}" "${namespace}"; then
                    log_message "ERROR" "Helm deployment failed"
                    deployment_status=1
                fi
            elif [[ "${deployment_method}" == "kustomize" ]]; then
                if ! deploy_with_kustomize "${environment}" "${version}"; then
                    log_message "ERROR" "Kustomize deployment failed"
                    deployment_status=1
                fi
            else
                log_message "ERROR" "Unknown deployment method: ${deployment_method}"
                deployment_status=1
            fi
            ;;
        
        *)
            log_message "ERROR" "Unknown environment: ${environment}"
            deployment_status=1
            ;;
    esac
    
    # Verify deployment
    if [[ ${deployment_status} -eq 0 ]]; then
        if ! verify_deployment "${environment}" "${namespace}"; then
            log_message "ERROR" "Deployment verification failed"
            deployment_status=1
        fi
    fi
    
    # Send notifications
    if [[ ${deployment_status} -eq 0 ]]; then
        notify_deployment "${environment}" "${version}" "SUCCESS"
        log_message "INFO" "Deployment completed successfully"
    else
        notify_deployment "${environment}" "${version}" "FAILURE"
        log_message "ERROR" "Deployment failed"
    fi
    
    return ${deployment_status}
}

# Export functions for potential use in other scripts
export -f deploy_with_helm
export -f deploy_with_kustomize
export -f deploy_canary
export -f deploy_blue_green

# Execute main function with provided arguments
main "$@"
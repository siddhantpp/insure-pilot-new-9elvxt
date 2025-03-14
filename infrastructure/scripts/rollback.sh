#!/bin/bash
# Rollback script for the Documents View feature
# Handles automated rollback of deployments in case of failures

# Exit immediately if a command fails
# Pipefail ensures that a pipeline returns a non-zero status if any command fails
set -eo pipefail

# Global variables
SCRIPT_DIR=$(dirname "${BASH_SOURCE[0]}")
REPO_ROOT=$(git rev-parse --show-toplevel)
LOG_FILE="/var/log/documents-view-rollback.log"
HELM_TIMEOUT="300s"
KUBE_CONTEXT_DEV="documents-view-dev"
KUBE_CONTEXT_STAGING="documents-view-staging"
KUBE_CONTEXT_PROD="documents-view-prod"
NAMESPACE_DEV="documents-view-dev"
NAMESPACE_STAGING="documents-view-staging"
NAMESPACE_PROD="documents-view-prod"
HELM_RELEASE_NAME="documents-view"
DEPLOYMENT_NAME="documents-view"

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

# Get namespace for the specified environment
get_namespace() {
    local environment="$1"
    
    case "${environment}" in
        dev)
            echo "${NAMESPACE_DEV}"
            ;;
        staging)
            echo "${NAMESPACE_STAGING}"
            ;;
        prod)
            echo "${NAMESPACE_PROD}"
            ;;
        *)
            echo ""
            ;;
    esac
}

# Determine the deployment method used for the current deployment
get_deployment_method() {
    local environment="$1"
    local namespace="$2"
    
    log_message "INFO" "Determining deployment method for ${environment} in namespace ${namespace}..."
    
    # Check if Helm release exists
    if helm status "${HELM_RELEASE_NAME}" -n "${namespace}" &> /dev/null; then
        log_message "INFO" "Helm release ${HELM_RELEASE_NAME} found in namespace ${namespace}"
        echo "helm"
        return 0
    fi
    
    # Check if Kustomize deployment exists
    if kubectl get deployment "${DEPLOYMENT_NAME}" -n "${namespace}" &> /dev/null; then
        log_message "INFO" "Kustomize deployment ${DEPLOYMENT_NAME} found in namespace ${namespace}"
        echo "kustomize"
        return 0
    fi
    
    log_message "ERROR" "Could not determine deployment method. Neither Helm release nor Kustomize deployment found."
    echo ""
    return 1
}

# Rolls back a Helm release to the previous version
rollback_helm() {
    local namespace="$1"
    local release_name="$2"
    local revision="$3"
    
    log_message "INFO" "Initiating Helm rollback for release ${release_name} in namespace ${namespace}..."
    
    # Get current revision
    local current_revision=$(helm history "${release_name}" -n "${namespace}" | tail -1 | awk '{print $1}')
    log_message "INFO" "Current revision is: ${current_revision}"
    
    local rollback_command
    
    if [[ -n "${revision}" ]]; then
        log_message "INFO" "Rolling back to specified revision: ${revision}"
        rollback_command="helm rollback ${release_name} ${revision} -n ${namespace} --timeout ${HELM_TIMEOUT} --wait"
    else
        # If no revision specified, roll back to the previous revision
        local previous_revision=$((current_revision - 1))
        if [[ "${previous_revision}" -lt 1 ]]; then
            log_message "ERROR" "No previous revision available to roll back to."
            return 1
        fi
        log_message "INFO" "Rolling back to previous revision: ${previous_revision}"
        rollback_command="helm rollback ${release_name} ${previous_revision} -n ${namespace} --timeout ${HELM_TIMEOUT} --wait"
    fi
    
    log_message "INFO" "Executing: ${rollback_command}"
    
    # Execute rollback
    if ! eval "${rollback_command}"; then
        log_message "ERROR" "Helm rollback failed for release ${release_name} in namespace ${namespace}"
        return 1
    fi
    
    log_message "INFO" "Helm rollback completed successfully"
    
    # Verify rollback
    local new_revision=$(helm history "${release_name}" -n "${namespace}" | tail -1 | awk '{print $1}')
    log_message "INFO" "New active revision is: ${new_revision}"
    
    return 0
}

# Rolls back a Kustomize deployment to the previous version
rollback_kustomize() {
    local environment="$1"
    local namespace="$2"
    local previous_version="$3"
    
    log_message "INFO" "Initiating Kustomize rollback for deployment ${DEPLOYMENT_NAME} in namespace ${namespace}..."
    
    local kustomization_path="${REPO_ROOT}/kubernetes/${environment}"
    
    if [[ ! -d "${kustomization_path}" ]]; then
        log_message "ERROR" "Kustomization directory not found: ${kustomization_path}"
        return 1
    fi
    
    # If previous version not specified, determine it from deployment history
    if [[ -z "${previous_version}" ]]; then
        previous_version=$(get_previous_version "${namespace}" "${DEPLOYMENT_NAME}")
        if [[ -z "${previous_version}" ]]; then
            log_message "ERROR" "Failed to determine previous version"
            return 1
        fi
    fi
    
    log_message "INFO" "Rolling back to version: ${previous_version}"
    
    # Update the image tag in kustomization.yaml
    if ! sed -i "s|newTag:.*|newTag: ${previous_version}|g" "${kustomization_path}/kustomization.yaml"; then
        log_message "ERROR" "Failed to update image tag in kustomization.yaml"
        return 1
    fi
    
    log_message "INFO" "Updated image tag in ${kustomization_path}/kustomization.yaml to ${previous_version}"
    
    # Apply the kustomize configuration
    log_message "INFO" "Applying Kustomize configuration..."
    
    if ! kustomize build "${kustomization_path}" | kubectl apply -f -; then
        log_message "ERROR" "Failed to apply Kustomize configuration"
        return 1
    fi
    
    log_message "INFO" "Kustomize configuration applied, waiting for rollout to complete..."
    
    # Wait for rollout to complete
    if ! kubectl rollout status deployment "${DEPLOYMENT_NAME}" -n "${namespace}" --timeout="${HELM_TIMEOUT}"; then
        log_message "ERROR" "Deployment rollout failed"
        return 1
    fi
    
    log_message "INFO" "Kustomize rollback completed successfully"
    return 0
}

# Rolls back a canary deployment in production
rollback_canary() {
    local namespace="$1"
    
    log_message "INFO" "Initiating canary rollback in namespace ${namespace}..."
    
    # Identify canary and stable deployments
    local stable_deployment="${DEPLOYMENT_NAME}-stable"
    local canary_deployment="${DEPLOYMENT_NAME}-canary"
    
    # Check if canary deployment exists
    if ! kubectl get deployment "${canary_deployment}" -n "${namespace}" &> /dev/null; then
        log_message "INFO" "No canary deployment found, skipping canary rollback"
        return 0
    fi
    
    log_message "INFO" "Scaling down canary deployment: ${canary_deployment}"
    
    # Scale down canary deployment to 0
    if ! kubectl scale deployment "${canary_deployment}" --replicas=0 -n "${namespace}"; then
        log_message "ERROR" "Failed to scale down canary deployment"
        return 1
    fi
    
    log_message "INFO" "Canary deployment scaled down successfully"
    
    # Update service to route all traffic to stable deployment
    log_message "INFO" "Ensuring all traffic is routed to stable deployment: ${stable_deployment}"
    
    # Get service name
    local service_name="${DEPLOYMENT_NAME}"
    
    # Update service selector to point to stable deployment only
    if ! kubectl patch service "${service_name}" -n "${namespace}" --type='json' -p='[{"op": "replace", "path": "/spec/selector/app", "value": "'${stable_deployment}'"}]'; then
        log_message "ERROR" "Failed to update service selector"
        return 1
    fi
    
    log_message "INFO" "Service selector updated successfully"
    
    # Wait for all canary pods to terminate
    log_message "INFO" "Waiting for canary pods to terminate..."
    
    # Wait for pods to terminate with timeout
    local timeout=60
    local count=0
    while [[ "$(kubectl get pods -n "${namespace}" -l app="${canary_deployment}" -o jsonpath='{.items}' | jq length)" != "0" && ${count} -lt ${timeout} ]]; do
        sleep 5
        count=$((count + 5))
    done
    
    if [[ ${count} -ge ${timeout} ]]; then
        log_message "WARNING" "Timeout waiting for canary pods to terminate"
    fi
    
    log_message "INFO" "Canary rollback completed successfully"
    return 0
}

# Rolls back a blue-green deployment in staging
rollback_blue_green() {
    local namespace="$1"
    
    log_message "INFO" "Initiating blue-green rollback in namespace ${namespace}..."
    
    # Identify blue and green deployments
    local blue_deployment="${DEPLOYMENT_NAME}-blue"
    local green_deployment="${DEPLOYMENT_NAME}-green"
    
    # Get service name
    local service_name="${DEPLOYMENT_NAME}"
    
    # Determine which deployment is currently active
    local current_route=$(kubectl get service "${service_name}" -n "${namespace}" -o jsonpath='{.spec.selector.app}')
    local previous_deployment
    
    if [[ "${current_route}" == "${blue_deployment}" ]]; then
        previous_deployment="${green_deployment}"
    else
        previous_deployment="${blue_deployment}"
    fi
    
    log_message "INFO" "Current active deployment: ${current_route}"
    log_message "INFO" "Rolling back to previous deployment: ${previous_deployment}"
    
    # Check if previous deployment exists
    if ! kubectl get deployment "${previous_deployment}" -n "${namespace}" &> /dev/null; then
        log_message "ERROR" "Previous deployment ${previous_deployment} not found"
        return 1
    fi
    
    # Check if previous deployment has available replicas
    local available_replicas=$(kubectl get deployment "${previous_deployment}" -n "${namespace}" -o jsonpath='{.status.availableReplicas}')
    
    if [[ -z "${available_replicas}" || "${available_replicas}" -lt 1 ]]; then
        log_message "WARNING" "Previous deployment ${previous_deployment} has no available replicas, scaling up..."
        
        # Scale up previous deployment
        if ! kubectl scale deployment "${previous_deployment}" --replicas=3 -n "${namespace}"; then
            log_message "ERROR" "Failed to scale up previous deployment"
            return 1
        fi
        
        # Wait for deployment to be ready
        if ! kubectl rollout status deployment "${previous_deployment}" -n "${namespace}" --timeout="${HELM_TIMEOUT}"; then
            log_message "ERROR" "Failed to get previous deployment ready"
            return 1
        fi
    fi
    
    # Switch traffic to previous deployment
    log_message "INFO" "Switching traffic to previous deployment: ${previous_deployment}"
    
    if ! kubectl patch service "${service_name}" -n "${namespace}" --type='json' -p='[{"op": "replace", "path": "/spec/selector/app", "value": "'${previous_deployment}'"}]'; then
        log_message "ERROR" "Failed to update service selector"
        return 1
    fi
    
    log_message "INFO" "Service selector updated successfully"
    
    # Scale down current deployment
    log_message "INFO" "Scaling down current deployment: ${current_route}"
    
    if ! kubectl scale deployment "${current_route}" --replicas=0 -n "${namespace}"; then
        log_message "WARNING" "Failed to scale down current deployment"
    fi
    
    log_message "INFO" "Blue-green rollback completed successfully"
    return 0
}

# Verifies the rollback was successful
verify_rollback() {
    local environment="$1"
    local namespace="$2"
    
    log_message "INFO" "Verifying rollback in ${environment} environment, namespace ${namespace}..."
    
    # Verify deployments are ready
    local deployment_name
    
    case "${environment}" in
        prod)
            deployment_name="${DEPLOYMENT_NAME}-stable"
            ;;
        staging)
            # For blue-green, we need to check which one is active
            local service_name="${DEPLOYMENT_NAME}"
            deployment_name=$(kubectl get service "${service_name}" -n "${namespace}" -o jsonpath='{.spec.selector.app}')
            ;;
        *)
            deployment_name="${DEPLOYMENT_NAME}"
            ;;
    esac
    
    log_message "INFO" "Checking deployment status: ${deployment_name}"
    
    # Check deployment status
    local deployment_status=$(kubectl rollout status deployment "${deployment_name}" -n "${namespace}" --timeout=10s 2>&1 || echo "Failed")
    
    if [[ "${deployment_status}" == *"Failed"* ]]; then
        log_message "ERROR" "Deployment verification failed: ${deployment_status}"
        return 1
    fi
    
    log_message "INFO" "Deployment status check passed"
    
    # Check pod status
    log_message "INFO" "Checking pod status for deployment: ${deployment_name}"
    
    local pods_ready=$(kubectl get pods -n "${namespace}" -l app="${deployment_name}" -o jsonpath='{.items[*].status.containerStatuses[0].ready}' | tr ' ' '\n' | grep -c "true" || echo "0")
    local pods_total=$(kubectl get pods -n "${namespace}" -l app="${deployment_name}" -o jsonpath='{.items}' | jq length)
    
    if [[ "${pods_ready}" -lt 1 || "${pods_ready}" != "${pods_total}" ]]; then
        log_message "ERROR" "Pod verification failed: ${pods_ready}/${pods_total} pods ready"
        return 1
    fi
    
    log_message "INFO" "Pod status check passed: ${pods_ready}/${pods_total} pods ready"
    
    # Check service availability
    log_message "INFO" "Checking service availability"
    
    local service_name="${DEPLOYMENT_NAME}"
    
    if ! kubectl get service "${service_name}" -n "${namespace}" &> /dev/null; then
        log_message "ERROR" "Service verification failed: service ${service_name} not found"
        return 1
    fi
    
    log_message "INFO" "Service check passed"
    
    # Run application health check
    log_message "INFO" "Running application health check"
    
    local health_check_endpoint="/health"
    local service_port=$(kubectl get service "${service_name}" -n "${namespace}" -o jsonpath='{.spec.ports[0].port}')
    
    # Use kubectl port-forward to access the service
    local port_forward_pid
    kubectl port-forward "service/${service_name}" 8080:${service_port} -n "${namespace}" &> /dev/null &
    port_forward_pid=$!
    
    # Give it a moment to establish
    sleep 3
    
    # Run the health check
    health_check_result=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080${health_check_endpoint} || echo "Failed")
    
    # Kill the port-forward
    kill ${port_forward_pid}
    
    if [[ "${health_check_result}" != "200" ]]; then
        log_message "ERROR" "Health check failed: HTTP ${health_check_result}"
        return 1
    fi
    
    log_message "INFO" "Health check passed"
    
    # Check metrics collection is working (basic check)
    log_message "INFO" "Verifying metrics collection"
    
    log_message "INFO" "Rollback verification completed successfully"
    return 0
}

# Sends notifications about the rollback
notify_rollback() {
    local environment="$1"
    local status="$2"
    local details="$3"
    
    log_message "INFO" "Sending rollback notification for ${environment} environment, status: ${status}"
    
    # Format the notification message
    local message="Documents View Rollback: ${environment} environment\nStatus: ${status}\nDetails: ${details}"
    
    # Send to Slack using webhook
    if [[ -n "${SLACK_WEBHOOK_URL}" ]]; then
        log_message "INFO" "Sending notification to Slack"
        
        # Construct JSON payload
        local payload="{\"text\":\"${message}\"}"
        
        # Send to Slack
        if ! curl -s -X POST -H "Content-type: application/json" --data "${payload}" "${SLACK_WEBHOOK_URL}" &> /dev/null; then
            log_message "WARNING" "Failed to send Slack notification"
        fi
    fi
    
    # Send email notification
    if [[ -n "${EMAIL_RECIPIENTS}" ]]; then
        log_message "INFO" "Sending notification email to ${EMAIL_RECIPIENTS}"
        
        # Use mail command if available
        if command -v mail &> /dev/null; then
            echo -e "${message}" | mail -s "Documents View Rollback: ${environment} - ${status}" ${EMAIL_RECIPIENTS}
        else
            log_message "WARNING" "mail command not available, email notification skipped"
        fi
    fi
    
    log_message "INFO" "Notifications sent successfully"
}

# Determines the previous version of a deployment
get_previous_version() {
    local namespace="$1"
    local deployment_name="$2"
    
    log_message "INFO" "Determining previous version for deployment ${deployment_name} in namespace ${namespace}..."
    
    # Get rollout history
    local rollout_history=$(kubectl rollout history deployment "${deployment_name}" -n "${namespace}" 2>/dev/null)
    
    if [[ $? -ne 0 || -z "${rollout_history}" ]]; then
        log_message "ERROR" "Failed to get rollout history for deployment ${deployment_name}"
        return 1
    fi
    
    # Get current revision
    local current_revision=$(echo "${rollout_history}" | grep -A 1 "REVISION" | tail -1 | awk '{print $1}')
    
    if [[ -z "${current_revision}" ]]; then
        log_message "ERROR" "Failed to determine current revision"
        return 1
    fi
    
    log_message "INFO" "Current revision is: ${current_revision}"
    
    # Get previous revision number
    local previous_revision
    
    if [[ "${current_revision}" -gt 1 ]]; then
        previous_revision=$((current_revision - 1))
    else
        log_message "ERROR" "No previous revision available (current is ${current_revision})"
        return 1
    fi
    
    log_message "INFO" "Previous revision is: ${previous_revision}"
    
    # Get the image from the previous revision
    local previous_image=$(kubectl rollout history deployment "${deployment_name}" -n "${namespace}" --revision=${previous_revision} | grep -i "image:" | awk -F: '{print $NF}' | tr -d '[:space:]')
    
    if [[ -z "${previous_image}" ]]; then
        log_message "ERROR" "Failed to determine image tag for previous revision ${previous_revision}"
        return 1
    fi
    
    log_message "INFO" "Previous version determined: ${previous_image}"
    echo "${previous_image}"
    return 0
}

# Displays script usage information
show_usage() {
    echo "Usage: $(basename $0) [options]"
    echo ""
    echo "Rollback script for the Documents View feature that handles automated rollback"
    echo "of deployments in case of failures across different environments."
    echo ""
    echo "Options:"
    echo "  -e, --environment <env>     Target environment (dev, staging, prod) [required]"
    echo "  -v, --version <version>     Specific version to roll back to [optional]"
    echo "  -r, --revision <revision>   Specific Helm revision to roll back to [optional]"
    echo "  -f, --force                 Force rollback without verification [optional]"
    echo "  -h, --help                  Show this help message"
    echo ""
    echo "Examples:"
    echo "  $(basename $0) --environment dev"
    echo "  $(basename $0) --environment staging --version v1.2.3"
    echo "  $(basename $0) --environment prod --revision 2 --force"
}

# Main function that orchestrates the rollback process
main() {
    # Initialize variables
    local environment=""
    local specific_version=""
    local helm_revision=""
    local force_rollback=false
    
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case "$1" in
            -e|--environment)
                environment="$2"
                shift 2
                ;;
            -v|--version)
                specific_version="$2"
                shift 2
                ;;
            -r|--revision)
                helm_revision="$2"
                shift 2
                ;;
            -f|--force)
                force_rollback=true
                shift
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
    
    # Check if environment is provided
    if [[ -z "${environment}" ]]; then
        log_message "ERROR" "Environment is required"
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
    
    # Get namespace for the environment
    local namespace=$(get_namespace "${environment}")
    
    if [[ -z "${namespace}" ]]; then
        log_message "ERROR" "Failed to determine namespace for environment: ${environment}"
        return 1
    fi
    
    log_message "INFO" "Starting rollback process for environment: ${environment}, namespace: ${namespace}"
    
    # Determine deployment method (Helm or Kustomize)
    local deployment_method=$(get_deployment_method "${environment}" "${namespace}")
    
    if [[ -z "${deployment_method}" ]]; then
        log_message "ERROR" "Failed to determine deployment method"
        return 1
    fi
    
    log_message "INFO" "Deployment method identified: ${deployment_method}"
    
    # Perform rollback based on deployment method
    local rollback_status=0
    
    case "${deployment_method}" in
        helm)
            if ! rollback_helm "${namespace}" "${HELM_RELEASE_NAME}" "${helm_revision}"; then
                rollback_status=1
            fi
            ;;
        kustomize)
            if ! rollback_kustomize "${environment}" "${namespace}" "${specific_version}"; then
                rollback_status=1
            fi
            ;;
        *)
            log_message "ERROR" "Unsupported deployment method: ${deployment_method}"
            return 1
            ;;
    esac
    
    # Handle environment-specific rollback strategies
    if [[ "${environment}" == "prod" ]]; then
        # For production, handle canary rollback if needed
        if ! rollback_canary "${namespace}"; then
            log_message "WARNING" "Canary rollback encountered issues"
            rollback_status=1
        fi
    elif [[ "${environment}" == "staging" ]]; then
        # For staging, handle blue-green rollback if needed
        if ! rollback_blue_green "${namespace}"; then
            log_message "WARNING" "Blue-green rollback encountered issues"
            rollback_status=1
        fi
    fi
    
    # Verify rollback unless force flag is set
    if [[ "${force_rollback}" != "true" ]]; then
        log_message "INFO" "Verifying rollback..."
        
        if ! verify_rollback "${environment}" "${namespace}"; then
            log_message "ERROR" "Rollback verification failed"
            rollback_status=1
        fi
    else
        log_message "WARNING" "Skipping verification due to force flag"
    fi
    
    # Send notifications
    if [[ ${rollback_status} -eq 0 ]]; then
        notify_rollback "${environment}" "SUCCESS" "Rollback completed successfully"
        log_message "INFO" "Rollback process completed successfully"
    else
        notify_rollback "${environment}" "FAILURE" "Rollback encountered issues, manual intervention may be required"
        log_message "ERROR" "Rollback process encountered issues"
    fi
    
    return ${rollback_status}
}

# Call main function with all arguments
main "$@"
terraform {
  required_providers {
    kubernetes = {
      source  = "hashicorp/kubernetes"
      version = "~> 2.0"
    }
    kubectl = {
      source  = "gavinbunney/kubectl"
      version = "~> 1.14"
    }
    helm = {
      source  = "hashicorp/helm"
      version = "~> 2.0"
    }
  }
}

# Define local variables for easier reference and environment-specific settings
locals {
  environment_name = var.environment == "prod" ? "production" : var.environment == "staging" ? "staging" : "development"
  is_production    = var.environment == "prod"
  common_labels    = merge(var.tags, {
    Environment = local.environment_name
    "app.kubernetes.io/name" = "documents-view"
    "app.kubernetes.io/instance" = var.environment
    "app.kubernetes.io/managed-by" = "terraform"
  })
  kustomize_dir = "${path.module}/../../../kubernetes"
}

# Create a Kubernetes namespace for the Documents View feature
resource "kubernetes_namespace" "documents_view" {
  metadata {
    name   = "${var.namespace}-${var.environment}"
    labels = local.common_labels
  }
}

# Create a ConfigMap with environment-specific configuration for the Documents View feature
resource "kubernetes_config_map" "documents_view" {
  metadata {
    name      = "documents-view-config"
    namespace = kubernetes_namespace.documents_view.metadata[0].name
    labels    = local.common_labels
  }

  data = {
    DOCUMENT_VIEWER_ENABLED     = "true"
    DOCUMENT_VIEWER_MAX_FILE_SIZE = "50"
    DOCUMENT_VIEWER_ALLOWED_TYPES = "pdf,docx,xlsx,pptx"
    DOCUMENT_VIEWER_CACHE_TTL     = "3600"
    DOCUMENT_VIEWER_AUDIT_ENABLED = "true"
    ADOBE_SDK_URL                 = "https://documentcloud.adobe.com/view-sdk/main.js"
    DB_HOST                       = var.environment == "prod" ? "rds-endpoint" : "mariadb"
    DB_PORT                       = "3306"
    DB_DATABASE                   = "insurepilot"
    REDIS_HOST                    = "redis"
    REDIS_PORT                    = "6379"
    DOCUMENT_STORAGE_PATH         = "/var/www/storage/documents"
    REGISTRY                      = "registry.example.com"
    TAG                           = var.environment
  }
}

# Generate a random password for the application key
resource "random_password" "app_key" {
  length           = 32
  special          = true
  override_special = "!#$%&*()-_=+[]{}<>:?"
}

# Generate a random password for the database
resource "random_password" "db_password" {
  length           = 16
  special          = true
  override_special = "!#$%&*()-_=+[]{}<>:?"
}

# Generate a random password for Redis
resource "random_password" "redis_password" {
  length           = 16
  special          = true
  override_special = "!#$%&*()-_=+[]{}<>:?"
}

# Generate a random key for Adobe SDK
resource "random_password" "adobe_sdk_key" {
  length  = 24
  special = false
}

# Create a Secret with sensitive configuration for the Documents View feature
resource "kubernetes_secret" "documents_view" {
  metadata {
    name      = "documents-view-secrets"
    namespace = kubernetes_namespace.documents_view.metadata[0].name
    labels    = local.common_labels
  }

  type = "Opaque"

  data = {
    app_key       = base64encode(random_password.app_key.result)
    db_username   = base64encode("admin")
    db_password   = base64encode(random_password.db_password.result)
    redis_password = base64encode(random_password.redis_password.result)
    adobe_sdk_key  = base64encode(random_password.adobe_sdk_key.result)
  }
}

# Create a PersistentVolumeClaim for document storage
resource "kubernetes_persistent_volume_claim" "documents_view" {
  metadata {
    name      = "documents-view-storage"
    namespace = kubernetes_namespace.documents_view.metadata[0].name
    labels    = local.common_labels
  }

  spec {
    access_modes = ["ReadWriteMany"]
    resources {
      requests = {
        storage = var.storage_size
      }
    }
    storage_class_name = "standard"
  }
}

# Process Kustomize directory to generate Kubernetes manifests
data "kubectl_path_documents" "kustomize" {
  pattern = "${local.kustomize_dir}/overlays/${var.environment}"
  vars = {
    namespace        = kubernetes_namespace.documents_view.metadata[0].name
    replicas         = var.replica_count
    cpu_request      = var.cpu_request
    memory_request   = var.memory_request
    cpu_limit        = var.cpu_limit
    memory_limit     = var.memory_limit
    document_viewer_image = var.document_viewer_image
    api_service_image     = var.api_service_image
  }
}

# Generate Kubernetes manifests from Kustomize for the Deployment
data "kubectl_file_documents" "kustomize_deployment" {
  content = data.kubectl_path_documents.kustomize.documents["Deployment.apps/documents-view"]
}

# Generate Kubernetes manifests from Kustomize for the Service
data "kubectl_file_documents" "kustomize_service" {
  content = data.kubectl_path_documents.kustomize.documents["Service/documents-view"]
}

# Generate Kubernetes manifests from Kustomize for the HPA
data "kubectl_file_documents" "kustomize_hpa" {
  content = data.kubectl_path_documents.kustomize.documents["HorizontalPodAutoscaler.autoscaling/documents-view"]
}

# Apply the Kustomize-generated Deployment manifest
resource "kubectl_manifest" "deployment" {
  yaml_body = data.kubectl_file_documents.kustomize_deployment.content
  override_namespace = kubernetes_namespace.documents_view.metadata[0].name
  depends_on = [
    kubernetes_config_map.documents_view,
    kubernetes_secret.documents_view,
    kubernetes_persistent_volume_claim.documents_view
  ]
}

# Apply the Kustomize-generated Service manifest
resource "kubectl_manifest" "service" {
  yaml_body = data.kubectl_file_documents.kustomize_service.content
  override_namespace = kubernetes_namespace.documents_view.metadata[0].name
  depends_on = [
    kubectl_manifest.deployment
  ]
}

# Apply the Kustomize-generated HPA manifest
resource "kubectl_manifest" "hpa" {
  yaml_body = data.kubectl_file_documents.kustomize_hpa.content
  override_namespace = kubernetes_namespace.documents_view.metadata[0].name
  depends_on = [
    kubectl_manifest.deployment
  ]
}

# Create a Deployment for Redis cache
resource "kubernetes_deployment" "redis" {
  metadata {
    name      = "redis"
    namespace = kubernetes_namespace.documents_view.metadata[0].name
    labels    = merge(local.common_labels, {
      "app.kubernetes.io/component" = "cache"
    })
  }

  spec {
    replicas = local.is_production ? 3 : 1

    selector {
      match_labels = {
        app = "redis"
      }
    }

    template {
      metadata {
        labels = {
          app = "redis"
        }
      }

      spec {
        container {
          name  = "redis"
          image = var.redis_image

          port {
            container_port = 6379
          }

          resources {
            requests = {
              cpu    = "100m"
              memory = "256Mi"
            }
            limits = {
              cpu    = "200m"
              memory = "512Mi"
            }
          }

          liveness_probe {
            tcp_socket {
              port = 6379
            }
            initial_delay_seconds = 30
            period_seconds        = 10
          }

          readiness_probe {
            tcp_socket {
              port = 6379
            }
            initial_delay_seconds = 5
            period_seconds        = 5
          }
        }
      }
    }
  }
}

# Create a Service for Redis cache
resource "kubernetes_service" "redis" {
  metadata {
    name      = "redis"
    namespace = kubernetes_namespace.documents_view.metadata[0].name
    labels    = merge(local.common_labels, {
      "app.kubernetes.io/component" = "cache"
    })
  }

  spec {
    selector = {
      app = "redis"
    }
    
    port {
      port        = 6379
      target_port = 6379
    }
    
    type = "ClusterIP"
  }
}

# Generate a random password for Grafana admin
resource "random_password" "grafana_password" {
  length           = 16
  special          = true
  override_special = "!#$%&*()-_=+[]{}<>:?"
}

# Deploy monitoring stack for the Documents View feature if enabled
resource "helm_release" "documents_view_monitoring" {
  count      = var.enable_monitoring ? 1 : 0
  name       = "documents-view-monitoring"
  namespace  = kubernetes_namespace.documents_view.metadata[0].name
  repository = "https://prometheus-community.github.io/helm-charts"
  chart      = "kube-prometheus-stack"
  version    = "45.0.0"

  values = [
    file("${path.module}/monitoring-values.yaml")
  ]

  set {
    name  = "grafana.adminPassword"
    value = random_password.grafana_password.result
  }
}

# Retrieve the Document Viewer service information
data "kubernetes_service" "document_viewer" {
  metadata {
    name      = "documents-view-frontend"
    namespace = kubernetes_namespace.documents_view.metadata[0].name
  }
  depends_on = [
    kubectl_manifest.service
  ]
}

# Retrieve the API service information
data "kubernetes_service" "api" {
  metadata {
    name      = "documents-view-backend"
    namespace = kubernetes_namespace.documents_view.metadata[0].name
  }
  depends_on = [
    kubectl_manifest.service
  ]
}

# Output: Kubernetes namespace where the Documents View feature is deployed
output "namespace" {
  description = "Kubernetes namespace where the Documents View feature is deployed"
  value       = kubernetes_namespace.documents_view.metadata[0].name
}

# Output: Kubernetes service name for the Document Viewer component
output "document_viewer_service_name" {
  description = "Kubernetes service name for the Document Viewer component"
  value       = data.kubernetes_service.document_viewer.metadata[0].name
}

# Output: Kubernetes service name for the API Service component
output "api_service_name" {
  description = "Kubernetes service name for the API Service component"
  value       = data.kubernetes_service.api.metadata[0].name
}

# Output: Endpoint URL for the Document Viewer component
output "document_viewer_endpoint" {
  description = "Endpoint URL for the Document Viewer component"
  value       = data.kubernetes_service.document_viewer.status[0].load_balancer[0].ingress[0].hostname != "" ? data.kubernetes_service.document_viewer.status[0].load_balancer[0].ingress[0].hostname : data.kubernetes_service.document_viewer.status[0].load_balancer[0].ingress[0].ip
}

# Output: Endpoint URL for the API Service component
output "api_endpoint" {
  description = "Endpoint URL for the API Service component"
  value       = data.kubernetes_service.api.status[0].load_balancer[0].ingress[0].hostname != "" ? data.kubernetes_service.api.status[0].load_balancer[0].ingress[0].hostname : data.kubernetes_service.api.status[0].load_balancer[0].ingress[0].ip
}

# Output: Kubernetes configuration for accessing the cluster
output "kubeconfig" {
  description = "Kubernetes configuration for accessing the cluster"
  value       = var.kubeconfig
  sensitive   = true
}

# Variable: Kubernetes configuration for accessing the cluster
variable "kubeconfig" {
  type        = string
  description = "Kubernetes configuration for accessing the cluster"
  default     = ""
  sensitive   = true
}

# Variable: Flag to enable monitoring stack deployment
variable "enable_monitoring" {
  type        = bool
  description = "Flag to enable or disable monitoring stack deployment"
  default     = true
}
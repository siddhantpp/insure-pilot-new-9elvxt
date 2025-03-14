# AWS Region Configuration
variable "aws_region" {
  type        = string
  description = "AWS region where cloud resources will be deployed"
  default     = "us-east-1"
}

variable "aws_profile" {
  type        = string
  description = "AWS profile to use for authentication"
  default     = "default"
}

# Environment Configuration
variable "environment" {
  type        = string
  description = "Deployment environment (dev, staging, prod)"
  default     = "dev"
  
  validation {
    condition     = contains(["dev", "staging", "prod"], var.environment)
    error_message = "Environment must be one of: dev, staging, prod."
  }
}

# S3 Storage Configuration
variable "document_storage_bucket_name" {
  type        = string
  description = "Base name for the S3 bucket used for document backup storage"
  default     = "insure-pilot-documents"
}

# RDS Configuration
variable "rds_instance_class" {
  type        = map(string)
  description = "RDS instance class for the database failover instance per environment"
  default     = {
    dev     = "db.t3.medium"
    staging = "db.t3.large"
    prod    = "db.m5.large"
  }
}

variable "rds_allocated_storage" {
  type        = map(number)
  description = "Allocated storage in GB for the RDS instance per environment"
  default     = {
    dev     = 50
    staging = 100
    prod    = 500
  }
}

# Direct Connect Configuration
variable "direct_connect_bandwidth" {
  type        = string
  description = "Bandwidth for the AWS Direct Connect connection"
  default     = "1Gbps"
}

# CloudFront Configuration
variable "cloudfront_price_class" {
  type        = string
  description = "Price class for CloudFront distribution"
  default     = "PriceClass_100"
}

# Kubernetes Configuration
variable "k8s_config_path" {
  type        = string
  description = "Path to the Kubernetes configuration file"
  default     = "~/.kube/config"
}

variable "k8s_config_context" {
  type        = string
  description = "Kubernetes context to use for deployment"
  default     = "default"
}

variable "k8s_namespace" {
  type        = string
  description = "Kubernetes namespace for the Documents View feature"
  default     = "documents-view"
}

# Deployment Resource Configuration
variable "replica_count" {
  type        = map(number)
  description = "Number of replicas for the Documents View deployment per environment"
  default     = {
    dev     = 2
    staging = 3
    prod    = 5
  }
}

variable "cpu_request" {
  type        = map(string)
  description = "CPU request for the Documents View containers per environment"
  default     = {
    dev     = "0.5"
    staging = "1"
    prod    = "2"
  }
}

variable "memory_request" {
  type        = map(string)
  description = "Memory request for the Documents View containers per environment"
  default     = {
    dev     = "1Gi"
    staging = "2Gi"
    prod    = "4Gi"
  }
}

variable "cpu_limit" {
  type        = map(string)
  description = "CPU limit for the Documents View containers per environment"
  default     = {
    dev     = "1"
    staging = "2"
    prod    = "4"
  }
}

variable "memory_limit" {
  type        = map(string)
  description = "Memory limit for the Documents View containers per environment"
  default     = {
    dev     = "2Gi"
    staging = "4Gi"
    prod    = "8Gi"
  }
}

variable "storage_size" {
  type        = map(string)
  description = "Storage size for document files per environment"
  default     = {
    dev     = "50Gi"
    staging = "100Gi"
    prod    = "500Gi"
  }
}

# Container Images
variable "document_viewer_image" {
  type        = string
  description = "Docker image for the Document Viewer component"
  default     = "insure-pilot/document-viewer:latest"
}

variable "api_service_image" {
  type        = string
  description = "Docker image for the API Service component"
  default     = "insure-pilot/api-service:latest"
}

variable "redis_image" {
  type        = string
  description = "Docker image for Redis cache"
  default     = "redis:7.0-alpine"
}

# Monitoring Configuration
variable "enable_monitoring" {
  type        = bool
  description = "Flag to enable or disable monitoring stack deployment"
  default     = true
}

# Resource Tagging
variable "tags" {
  type        = map(string)
  description = "Tags to apply to all resources"
  default     = {
    Project    = "InsurePilot"
    Feature    = "DocumentsView"
    ManagedBy  = "Terraform"
  }
}
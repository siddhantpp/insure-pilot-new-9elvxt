terraform {
  required_version = ">= 1.0.0"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 4.0"
    }
    kubernetes = {
      source  = "hashicorp/kubernetes"
      version = "~> 2.0"
    }
    helm = {
      source  = "hashicorp/helm"
      version = "~> 2.0"
    }
    kubectl = {
      source  = "gavinbunney/kubectl"
      version = "~> 1.14"
    }
    local = {
      source  = "hashicorp/local"
      version = "~> 2.0"
    }
    random = {
      source  = "hashicorp/random"
      version = "~> 3.0"
    }
    time = {
      source  = "hashicorp/time"
      version = "~> 0.7"
    }
  }

  backend "s3" {
    bucket         = "insure-pilot-terraform-state"
    key            = "documents-view/terraform.tfstate"
    region         = "us-east-1"
    encrypt        = true
    dynamodb_table = "terraform-locks"
  }
}

provider "aws" {
  region  = var.aws_region
  profile = var.aws_profile
  
  default_tags {
    tags = var.tags
  }
}

provider "kubernetes" {
  config_path    = var.k8s_config_path
  config_context = var.k8s_config_context
}

provider "helm" {
  kubernetes {
    config_path    = var.k8s_config_path
    config_context = var.k8s_config_context
  }
}

provider "kubectl" {
  config_path    = var.k8s_config_path
  config_context = var.k8s_config_context
}

locals {
  environment_name = var.environment == "prod" ? "production" : var.environment == "staging" ? "staging" : "development"
  is_production    = var.environment == "prod"
  common_tags      = merge(var.tags, { Environment = local.environment_name })
  kubeconfig_path  = pathexpand(var.k8s_config_path)
}

data "aws_caller_identity" "current" {}
data "aws_region" "current" {}

module "aws" {
  source = "./modules/aws"
  
  aws_region             = var.aws_region
  environment            = var.environment
  s3_bucket_name         = var.document_storage_bucket_name
  rds_instance_class     = var.rds_instance_class[var.environment]
  rds_allocated_storage  = var.rds_allocated_storage[var.environment]
  direct_connect_bandwidth = var.direct_connect_bandwidth
  cloudfront_price_class = var.cloudfront_price_class
  tags                   = var.tags
}

module "kubernetes" {
  source = "./modules/kubernetes"
  
  environment           = var.environment
  namespace             = var.k8s_namespace
  replica_count         = var.replica_count[var.environment]
  cpu_request           = var.cpu_request[var.environment]
  memory_request        = var.memory_request[var.environment]
  cpu_limit             = var.cpu_limit[var.environment]
  memory_limit          = var.memory_limit[var.environment]
  storage_size          = var.storage_size[var.environment]
  document_viewer_image = var.document_viewer_image
  api_service_image     = var.api_service_image
  redis_image           = var.redis_image
  tags                  = var.tags
}

resource "local_file" "kubeconfig" {
  content         = module.kubernetes.kubeconfig
  filename        = "${path.module}/kubeconfig"
  file_permission = "0600"
}

resource "time_sleep" "wait_for_aws" {
  depends_on      = [module.aws]
  create_duration = "30s"
}

output "document_storage_bucket_id" {
  description = "ID of the S3 bucket used for document backup storage"
  value       = module.aws.s3_bucket_id
}

output "document_storage_bucket_arn" {
  description = "ARN of the S3 bucket used for document backup storage"
  value       = module.aws.s3_bucket_arn
}

output "cloudfront_distribution_id" {
  description = "ID of the CloudFront distribution for content delivery"
  value       = module.aws.cloudfront_distribution_id
}

output "cloudfront_domain_name" {
  description = "Domain name of the CloudFront distribution for content delivery"
  value       = module.aws.cloudfront_domain_name
}

output "database_endpoint" {
  description = "Endpoint of the RDS database failover instance"
  value       = module.aws.rds_endpoint
}

output "direct_connect_id" {
  description = "ID of the AWS Direct Connect connection for on-premises to cloud connectivity"
  value       = module.aws.direct_connect_id
}

output "kubernetes_namespace" {
  description = "Kubernetes namespace where the Documents View feature is deployed"
  value       = module.kubernetes.namespace
}

output "document_viewer_service_name" {
  description = "Kubernetes service name for the Document Viewer component"
  value       = module.kubernetes.document_viewer_service_name
}

output "api_service_name" {
  description = "Kubernetes service name for the API Service component"
  value       = module.kubernetes.api_service_name
}

output "document_viewer_endpoint" {
  description = "Endpoint URL for the Document Viewer component"
  value       = module.kubernetes.document_viewer_endpoint
}

output "api_endpoint" {
  description = "Endpoint URL for the API Service component"
  value       = module.kubernetes.api_endpoint
}

output "kubeconfig_path" {
  description = "Path to the generated Kubernetes configuration file"
  value       = "${path.module}/kubeconfig"
}

output "environment" {
  description = "Current deployment environment (dev, staging, prod)"
  value       = var.environment
}

output "aws_account_id" {
  description = "AWS account ID where resources are deployed"
  value       = data.aws_caller_identity.current.account_id
}

output "aws_region_name" {
  description = "AWS region where resources are deployed"
  value       = data.aws_region.current.name
}

output "deployment_timestamp" {
  description = "Timestamp when the infrastructure was last deployed"
  value       = timestamp()
}
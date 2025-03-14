# ------------------------------------------------------
# Terraform Outputs for Documents View Feature
# 
# This file defines the output values that will be displayed
# after Terraform applies the configuration. These outputs
# provide important resource identifiers, endpoints, and
# configuration details that can be used by other systems
# or for reference after deployment.
# ------------------------------------------------------

# AWS Cloud Resources
output "document_storage_bucket_id" {
  value       = module.aws.s3_bucket_id
  description = "ID of the S3 bucket used for document backup storage"
}

output "document_storage_bucket_arn" {
  value       = module.aws.s3_bucket_arn
  description = "ARN of the S3 bucket used for document backup storage"
}

output "cloudfront_distribution_id" {
  value       = module.aws.cloudfront_distribution_id
  description = "ID of the CloudFront distribution for content delivery"
}

output "cloudfront_domain_name" {
  value       = module.aws.cloudfront_domain_name
  description = "Domain name of the CloudFront distribution for content delivery"
}

output "database_endpoint" {
  value       = module.aws.rds_endpoint
  description = "Endpoint of the RDS database failover instance"
}

output "direct_connect_id" {
  value       = module.aws.direct_connect_id
  description = "ID of the AWS Direct Connect connection for on-premises to cloud connectivity"
}

# Kubernetes Resources
output "kubernetes_namespace" {
  value       = module.kubernetes.namespace
  description = "Kubernetes namespace where the Documents View feature is deployed"
}

output "document_viewer_service_name" {
  value       = module.kubernetes.document_viewer_service_name
  description = "Kubernetes service name for the Document Viewer component"
}

output "api_service_name" {
  value       = module.kubernetes.api_service_name
  description = "Kubernetes service name for the API Service component"
}

output "document_viewer_endpoint" {
  value       = module.kubernetes.document_viewer_endpoint
  description = "Endpoint URL for the Document Viewer component"
}

output "api_endpoint" {
  value       = module.kubernetes.api_endpoint
  description = "Endpoint URL for the API Service component"
}

output "kubeconfig_path" {
  value       = "${path.module}/kubeconfig"
  description = "Path to the generated Kubernetes configuration file"
}

output "environment" {
  value       = var.environment
  description = "Current deployment environment (dev, staging, prod)"
}

# Additional metadata outputs
output "aws_account_id" {
  value       = data.aws_caller_identity.current.account_id
  description = "AWS account ID where resources are deployed"
}

output "aws_region_name" {
  value       = data.aws_region.current.name
  description = "AWS region where resources are deployed"
}

output "deployment_timestamp" {
  value       = timestamp()
  description = "Timestamp when the infrastructure was last deployed"
}

output "infrastructure_version" {
  value       = "1.0.0"
  description = "Version of the infrastructure deployment"
}

# Computed or derived outputs
output "hybrid_connectivity_status" {
  value       = module.aws.direct_connect_id != "" ? "connected" : "disconnected"
  description = "Status of the hybrid connectivity between on-premises and cloud environments"
}
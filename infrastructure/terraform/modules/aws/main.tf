# AWS Terraform module for Documents View feature
# This module provisions and configures AWS cloud resources for the hybrid infrastructure

terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws" # version ~> 4.0
      version = "~> 4.0"
    }
    random = {
      source  = "hashicorp/random" # version ~> 3.0
      version = "~> 3.0"
    }
  }
}

# Variables
variable "aws_region" {
  description = "AWS region where cloud resources will be deployed"
  type        = string
  default     = "us-east-1"
}

variable "environment" {
  description = "Deployment environment (dev, staging, prod)"
  type        = string
  default     = "dev"
}

variable "s3_bucket_name" {
  description = "Base name for the S3 bucket used for document backup storage"
  type        = string
  default     = "insure-pilot-documents"
}

variable "rds_instance_class" {
  description = "RDS instance class for the database failover instance"
  type        = string
  default     = "db.t3.medium"
}

variable "rds_allocated_storage" {
  description = "Allocated storage in GB for the RDS instance"
  type        = number
  default     = 50
}

variable "direct_connect_bandwidth" {
  description = "Bandwidth for the AWS Direct Connect connection"
  type        = string
  default     = "1Gbps"
}

variable "cloudfront_price_class" {
  description = "Price class for CloudFront distribution"
  type        = string
  default     = "PriceClass_100"
}

variable "tags" {
  description = "Tags to apply to all resources"
  type        = map(string)
  default = {
    Project   = "InsurePilot"
    Feature   = "DocumentsView"
    ManagedBy = "Terraform"
  }
}

# Locals
locals {
  bucket_name      = "${var.s3_bucket_name}-${var.environment}-${random_id.bucket_suffix.hex}"
  environment_name = var.environment == "prod" ? "production" : var.environment == "staging" ? "staging" : "development"
  is_production    = var.environment == "prod"
  common_tags      = merge(var.tags, { Environment = local.environment_name })
}

# Data sources
data "aws_vpc" "default" {
  default = true
}

data "aws_subnets" "private" {
  filter {
    name   = "vpc-id"
    values = [data.aws_vpc.default.id]
  }
  tags = {
    Tier = "Private"
  }
}

data "aws_iam_policy_document" "s3_policy" {
  statement {
    actions   = ["s3:GetObject"]
    resources = ["${aws_s3_bucket.documents.arn}/*"]
    principals {
      type        = "AWS"
      identifiers = [aws_cloudfront_origin_access_identity.documents.iam_arn]
    }
  }
}

# Random resources
resource "random_id" "bucket_suffix" {
  byte_length = 4
  keepers = {
    environment = var.environment
  }
}

resource "random_password" "db_password" {
  length           = 16
  special          = true
  override_special = "!#$%&*()-_=+[]{}<>:?"
}

# S3 Bucket for document storage
resource "aws_s3_bucket" "documents" {
  bucket = local.bucket_name
  tags   = local.common_tags
}

resource "aws_s3_bucket_versioning" "documents" {
  bucket = aws_s3_bucket.documents.id
  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "documents" {
  bucket = aws_s3_bucket.documents.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

resource "aws_s3_bucket_lifecycle_configuration" "documents" {
  bucket = aws_s3_bucket.documents.id

  rule {
    id     = "transition-to-standard-ia"
    status = "Enabled"
    
    transition {
      days          = 30
      storage_class = "STANDARD_IA"
    }
    
    filter {
      prefix = "documents/"
    }
  }

  rule {
    id     = "transition-to-glacier"
    status = "Enabled"
    
    transition {
      days          = 90
      storage_class = "GLACIER"
    }
    
    filter {
      prefix = "documents/"
    }
  }

  rule {
    id     = "expire-deleted-documents"
    status = "Enabled"
    
    expiration {
      days = 90
    }
    
    filter {
      prefix = "documents/deleted/"
    }
  }
}

resource "aws_s3_bucket_public_access_block" "documents" {
  bucket                  = aws_s3_bucket.documents.id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

# CloudFront for content delivery
resource "aws_cloudfront_origin_access_identity" "documents" {
  comment = "Access identity for Documents View S3 bucket"
}

resource "aws_s3_bucket_policy" "documents" {
  bucket = aws_s3_bucket.documents.id
  policy = data.aws_iam_policy_document.s3_policy.json
}

resource "aws_cloudfront_distribution" "documents" {
  enabled             = true
  is_ipv6_enabled     = true
  comment             = "Documents View static assets distribution"
  default_root_object = "index.html"
  price_class         = var.cloudfront_price_class

  origin {
    domain_name = aws_s3_bucket.documents.bucket_regional_domain_name
    origin_id   = "S3-${aws_s3_bucket.documents.id}"

    s3_origin_config {
      origin_access_identity = aws_cloudfront_origin_access_identity.documents.cloudfront_access_identity_path
    }
  }

  default_cache_behavior {
    allowed_methods  = ["GET", "HEAD", "OPTIONS"]
    cached_methods   = ["GET", "HEAD"]
    target_origin_id = "S3-${aws_s3_bucket.documents.id}"

    forwarded_values {
      query_string = false

      cookies {
        forward = "none"
      }
    }

    viewer_protocol_policy = "redirect-to-https"
    min_ttl                = 0
    default_ttl            = 3600
    max_ttl                = 86400
    compress               = true
  }

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }

  viewer_certificate {
    cloudfront_default_certificate = true
  }

  tags = local.common_tags
}

# RDS for database failover
resource "aws_db_subnet_group" "documents" {
  name       = "documents-view-${var.environment}"
  subnet_ids = data.aws_subnets.private.ids
  tags       = local.common_tags
}

resource "aws_security_group" "rds" {
  name        = "documents-view-rds-${var.environment}"
  description = "Security group for Documents View RDS instance"
  vpc_id      = data.aws_vpc.default.id

  ingress {
    from_port   = 3306
    to_port     = 3306
    protocol    = "tcp"
    cidr_blocks = [data.aws_vpc.default.cidr_block]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = local.common_tags
}

resource "aws_db_instance" "documents" {
  identifier              = "documents-view-${var.environment}"
  engine                  = "mariadb"
  engine_version          = "10.6"
  instance_class          = var.rds_instance_class
  allocated_storage       = var.rds_allocated_storage
  db_name                 = "insurepilot"
  username                = "admin"
  password                = random_password.db_password.result
  parameter_group_name    = "default.mariadb10.6"
  skip_final_snapshot     = !local.is_production
  final_snapshot_identifier = local.is_production ? "documents-view-${var.environment}-final" : null
  backup_retention_period = local.is_production ? 7 : 1
  multi_az                = local.is_production
  storage_encrypted       = true
  db_subnet_group_name    = aws_db_subnet_group.documents.name
  vpc_security_group_ids  = [aws_security_group.rds.id]
  tags                    = local.common_tags
}

# Direct Connect for hybrid connectivity
resource "aws_dx_connection" "documents" {
  name      = "documents-view-${var.environment}"
  bandwidth = var.direct_connect_bandwidth
  location  = "EqDC2"  # Example location - adjust for actual deployment
  tags      = local.common_tags
}

resource "aws_dx_private_virtual_interface" "documents" {
  connection_id    = aws_dx_connection.documents.id
  name             = "documents-view-vif-${var.environment}"
  vlan             = 100
  address_family   = "ipv4"
  bgp_asn          = 65000  # Example ASN - adjust for actual deployment
  amazon_address   = "169.254.0.1/30"
  customer_address = "169.254.0.2/30"
  tags             = local.common_tags
}

# Outputs
output "s3_bucket_id" {
  description = "ID of the S3 bucket used for document backup storage"
  value       = aws_s3_bucket.documents.id
}

output "s3_bucket_arn" {
  description = "ARN of the S3 bucket used for document backup storage"
  value       = aws_s3_bucket.documents.arn
}

output "cloudfront_distribution_id" {
  description = "ID of the CloudFront distribution for content delivery"
  value       = aws_cloudfront_distribution.documents.id
}

output "cloudfront_domain_name" {
  description = "Domain name of the CloudFront distribution for content delivery"
  value       = aws_cloudfront_distribution.documents.domain_name
}

output "rds_endpoint" {
  description = "Endpoint of the RDS database failover instance"
  value       = aws_db_instance.documents.endpoint
}

output "direct_connect_id" {
  description = "ID of the AWS Direct Connect connection for on-premises to cloud connectivity"
  value       = aws_dx_connection.documents.id
}
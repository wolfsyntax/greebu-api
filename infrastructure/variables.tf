variable "application_name" {
  description = "The name of the Application"
  type        = string
  default     = "greebu-backend-dev"
}

variable "aws_account_id" {
  description = "AWS account ID"
  type        = string
  default     = "675146749892"
}

variable "aws_region" {
  description = "The AWS region where resources will be created"
  type        = string
  default     = "ap-southeast-1"
}

variable "aws_ecs_task_definition_log_group_name" {
  type        = string
  description = "task definition log group name"
  default     = "/ecs/greebu-backend-dev-td"
}

variable "subnet_ids" {
  description = "A list of existing subnet IDs"
  type        = list(string)
  default     = ["subnet-0c644fa6b6869da04", "subnet-006ade2ee381a7469", "subnet-0b475883097abc0a4"]
}

variable "load_balancer_name" {
  description = "The name of the load balancer"
  type        = string
  default     = "greebu-backend-dev-lb"
}

variable "health_check_path" {
  description = "The path to use for health checks"
  type        = string
  default     = "/"
}

variable "health_check_timeout" {
  description = "The timeout for health checks (in seconds)"
  type        = number
  default     = 60
}

variable "vpc_id" {
  description = "The ID of the existing VPC"
  type        = string
  default     = "vpc-038265754209e98c3"
}

variable "is_fargate_setup" {
  description = "Identify if the AWS ECS is Fargate or ECS Launch Type"
  type        = bool
  default     = true
}


# VPC
data "aws_vpc" "this" {
  id = var.vpc_id
}

# Subnets
data "aws_subnet" "existing" {
  count = length(var.subnet_ids)
  id    = var.subnet_ids[count.index]
}

data "aws_ecs_task_definition" "latest" {
  task_definition = var.is_fargate_setup == 1 ? aws_ecs_task_definition.fargate_launch_type_task_definition.family : aws_ecs_task_definition.ec2_launch_type_task_definition.family
}
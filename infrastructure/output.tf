output "ecr_repository_url" {
  value = aws_ecr_repository.this.repository_url
}

output "ecr_repository_arn" {
  value = aws_ecr_repository.this.arn
}

output "aws_lb_listener" {
  value = aws_lb_listener.this.arn
}

output "aws_ecs_service" {
  value = aws_ecs_service.this
}

output "aws_ecs_cluster" {
  value = aws_ecs_cluster.this.arn
}

output "aws_lb_target_group" {
  value = aws_lb_target_group.this.arn
}

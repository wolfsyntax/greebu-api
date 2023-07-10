resource "aws_ecr_repository" "this" {
  name                 = var.application_name
  image_tag_mutability = "MUTABLE"

  image_scanning_configuration {
    scan_on_push = false
  }
}

resource "aws_cloudwatch_log_group" "application_log_group_name" {
  name              = var.aws_ecs_task_definition_log_group_name
  retention_in_days = 14
}

resource "aws_ecr_lifecycle_policy" "this" {
  repository = aws_ecr_repository.this.name

  policy = <<EOF
{
    "rules": [
        {
            "rulePriority": 1,
            "description": "remove more than 3 untagged images",
            "selection": {
                "tagStatus": "untagged",
                "countType": "imageCountMoreThan",
                "countNumber": 3
            },
            "action": {
                "type": "expire"
            }
        }
    ]
}
EOF
}

# Task Definition Fargate Launch Type
resource "aws_ecs_task_definition" "fargate_launch_type_task_definition" {
  family                   = "${var.application_name}-td"
  network_mode             = "awsvpc"
  requires_compatibilities = ["FARGATE"]
  cpu                      = "256"
  memory                   = "512"
  execution_role_arn       = "arn:aws:iam::${var.aws_account_id}:role/ecsTaskExecutionRole"

  container_definitions = jsonencode([
    {
      name   = var.application_name
      image  = "${var.aws_account_id}.dkr.ecr.${var.aws_region}.amazonaws.com/${var.application_name}:latest"
      cpu    = 256
      memory = 512
      portMappings = [
        {
          containerPort = 80
          hostPort      = 80
          protocol      = "tcp"
        }
      ]
      essential = true
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          awslogs-group         = aws_cloudwatch_log_group.application_log_group_name.name
          awslogs-region        = var.aws_region
          awslogs-stream-prefix = "ecs"
        }
      }
    }
  ])
}


# Task Definition EC2 Launch Type
resource "aws_ecs_task_definition" "ec2_launch_type_task_definition" {
  family                   = "${var.application_name}-td"
  network_mode             = "awsvpc"
  requires_compatibilities = ["EC2"]
  cpu                      = "512"
  memory                   = "1024"
  execution_role_arn       = "arn:aws:iam::${var.aws_account_id}:role/ecsTaskExecutionRole"

  container_definitions = jsonencode([
      {
        name   = var.application_name
        image  = "${var.aws_account_id}.dkr.ecr.${var.aws_region}.amazonaws.com/${var.application_name}:latest"
        cpu    = 512
        memory = 1024
        portMappings = [
          {
            containerPort = 80,
            hostPort      = 80,
            protocol      = "tcp"
          }
        ],
        essential = true,
        logConfiguration = {
          logDriver = "awslogs",
          options = {
            awslogs-group         = aws_cloudwatch_log_group.application_log_group_name.name,
            awslogs-region        = var.aws_region,
            awslogs-stream-prefix = "ecs"
          }
        },
        healthCheck = {
          command = [
            "CMD-SHELL",
            "wget http=//127.0.0.1${var.health_check_path} -q -O - > /dev/null 2>&1 || exit 1"
          ],
          interval    = 30,
          timeout     = 60,
          retries     = 10,
          startPeriod = 180
        }
      }
  ])

}


# ECS Cluster
resource "aws_ecs_cluster" "this" {
  name = "${var.application_name}-cluster"
}


# Load Balancer
resource "aws_lb" "this" {
  name               = var.load_balancer_name
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.ecs_service.id]
  subnets            = data.aws_subnet.existing.*.id

  tags = {
    Name = "${var.application_name}-lb"
  }
}

# Target Group
resource "aws_lb_target_group" "this" {
  name                 = "${var.application_name}-tg"
  port                 = 80
  protocol             = "HTTP"
  vpc_id               = data.aws_vpc.this.id
  target_type          = "ip"
  slow_start           = 30
  deregistration_delay = 60

  health_check {
    path                = var.health_check_path
    timeout             = var.health_check_timeout
    interval            = var.health_check_timeout * 2
    healthy_threshold   = 10
    unhealthy_threshold = 10
  }
}

# Listener
resource "aws_lb_listener" "this" {
  load_balancer_arn = aws_lb.this.arn
  port              = 80
  protocol          = "HTTP"

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.this.arn
  }
}

resource "aws_security_group" "ecs_service" {
  name        = "${var.application_name}-ecs-service-sg"
  description = "Security group for ECS Fargate service"
  vpc_id      = data.aws_vpc.this.id

  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 65535
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "${var.application_name}-ecs-service-sg"
  }
}



# ECS Service
resource "aws_ecs_service" "this" {
  name            = "${var.application_name}-service"
  cluster         = aws_ecs_cluster.this.id
  task_definition = data.aws_ecs_task_definition.latest.arn
  desired_count   = 1

  launch_type = var.is_fargate_setup ? "FARGATE" : "EC2"

  network_configuration {
    subnets          = data.aws_subnet.existing.*.id
    security_groups  = [aws_security_group.ecs_service.id]
    assign_public_ip = true
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.this.arn
    container_name   = var.application_name
    container_port   = 80
  }

  depends_on = [aws_lb_listener.this]
}


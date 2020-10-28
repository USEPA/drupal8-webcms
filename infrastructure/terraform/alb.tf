# Create the user-facing load balancer for the ECS cluster
resource "aws_lb" "frontend" {
  name               = "webcms-frontend-${local.env-suffix}"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.load_balancer.id]
  subnets            = aws_subnet.public.*.id

  access_logs {
    bucket  = aws_s3_bucket.elb_logs.bucket
    enabled = true
  }

  tags = merge(local.common-tags, {
    Name = "${local.name-prefix} Load Balancer"
  })
}

# Target group for Drupal container tasks
resource "aws_lb_target_group" "drupal_target_group" {
  name = "webcms-drupal-tg-${local.env-suffix}"

  port        = 80
  protocol    = "HTTP"
  target_type = "ip"
  vpc_id      = local.vpc-id

  load_balancing_algorithm_type = "least_outstanding_requests"

  # Have the load balancer target the PHP-FPM status port (:8080) instead of the Drupal
  # application. In an ideal world, we could hit / to determine if Drupal is still
  # healthy, but this causes so much load on the PHP-FPM pool that it can cause the
  # container to fail to respond in time, resulting in an unhealthy task - which in turn
  # puts more load on the other containers, which can cause them to become unhealthy.
  health_check {
    enabled  = true
    interval = 300
    timeout  = 60
    path     = "/ping"
    port     = 8080
    protocol = "HTTP"
  }
}

# Listener for HTTP requests. We unconditionally upgrade all HTTP requests to HTTPS because
# it's just good request hygiene.
resource "aws_lb_listener" "frontend_http" {
  load_balancer_arn = aws_lb.frontend.arn
  port              = 80
  protocol          = "HTTP"

  default_action {
    type = "redirect"

    redirect {
      port        = 443
      protocol    = "HTTPS"
      status_code = "HTTP_301"
    }
  }
}

# Send all HTTPS requests to Drupal
resource "aws_lb_listener" "frontend_https" {
  load_balancer_arn = aws_lb.frontend.arn
  port              = 443
  protocol          = "HTTPS"
  certificate_arn   = var.alb-certificate

  # The default action here is to reject traffic - this prevents requests bypassing
  # the configured domain name and trying to visit the autogenerated ALB address.
  default_action {
    type = "fixed-response"

    fixed_response {
      content_type = "text/plain"
      status_code  = 404
      message_body = "Not found"
    }
  }
}

# This is the rule that allows traffic through if it matches the configured domain
resource "aws_lb_listener_rule" "frontend_https" {
  listener_arn = aws_lb_listener.frontend_https.arn

  action {
    type = "forward"

    target_group_arn = aws_lb_target_group.drupal_target_group.arn
  }

  condition {
    host_header {
      values = concat([var.site-hostname], var.alb-hostnames)
    }
  }
}

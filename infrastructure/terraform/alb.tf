# Create the user-facing load balancer for the ECS cluster
resource "aws_lb" "frontend" {
  name               = "webcms-frontend"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.load_balancer.id]
  subnets            = aws_subnet.public.*.id

  tags = {
    Application = "WebCMS"
  }
}

# Target group for Drupal container tasks
resource "aws_lb_target_group" "drupal_target_group" {
  name = "webcms-drupal-tg"

  port        = 80
  protocol    = "HTTP"
  target_type = "ip"
  vpc_id      = aws_vpc.main.id
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
      # If the alb-hostname var isn't set, use the site hostname (this matters in cases
      # where we're behind a CDN and the ALB's domain name is different from the
      # public-facing domain name.)
      values = [var.alb-hostname == null ? var.site-hostname : var.alb-hostname]
    }
  }
}

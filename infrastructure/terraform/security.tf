resource "aws_security_group" "load_balancer" {
  name        = "webcms-alb-sg"
  description = "Security group for the WebCMS load balancers"

  vpc_id = aws_vpc.main.id

  # We allow port 80 in order to perform HTTP -> HTTPS redirection here instead of at the
  # app level.
  ingress {
    description = "Allow incoming HTTP traffic"

    protocol    = "tcp"
    from_port   = 80
    to_port     = 80
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    description = "Allow incoming HTTPS traffic"

    protocol    = "tcp"
    from_port   = 443
    to_port     = 443
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Application = "WebCMS"
    Name        = "WebCMS Load Balancer"
  }
}

# NB. This is only the security group for the EC2 instances in the cluster, _not_ the
# ECS tasks that will be running in containers. These servers only need enough permissions
# to communicate with the ECS API and a few other AWS services.
resource "aws_security_group" "server" {
  name        = "webcms-ec2-sg"
  description = "Security group for the WebCMS EC2 instances"

  vpc_id = aws_vpc.main.id

  egress {
    description = "Allow outgoing HTTP traffic"

    protocol    = "tcp"
    from_port   = 80
    to_port     = 80
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    description = "Allow outgoing HTTPS traffic"

    protocol    = "tcp"
    from_port   = 443
    to_port     = 443
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Application = "WebCMS"
    Name        = "WebCMS Cluster Server"
  }
}

resource "aws_security_group" "bastion" {
  name        = "webcms-bastion-sg"
  description = "Security group for SSH bastions"

  vpc_id = aws_vpc.main.id

  # We only allow inbound SSH connections from the IP ranges specified in bastion-ingress
  # This limits security risk of a public-facing bastion server since we assume that the
  # allowed IPs are associated with, e.g., a VPN or jump box.
  ingress {
    description = "Allow incoming SSH connections"

    protocol    = "tcp"
    from_port   = 22
    to_port     = 22
    cidr_blocks = var.bastion-ingress
  }

  # This rule allows accessing an ECS instance from the bastion server
  egress {
    description = "Allow outgoing SSH connections"

    protocol        = "tcp"
    from_port       = 22
    to_port         = 22
    security_groups = [aws_security_group.server.id]
  }

  # For administration purposes, we allow access to the RDS instance from the SSH bastion
  # to allow users to run queries against RDS.
  egress {
    description = "Allow outgoing MySQL connections"

    protocol        = "tcp"
    from_port       = 3306
    to_port         = 3306
    security_groups = [aws_security_group.server.id]
  }

  tags = {
    Application = "WebCMS"
    Name        = "WebCMS Bastion"
  }
}

# This server->bastion rule is separate to avoid loops in the Terraform dependency graph
resource "aws_security_group_rule" "server_bastion_ingress" {
  description = "Allow SSH connections to EC2 servers from bastion hosts"

  type              = "ingress"
  protocol          = "tcp"
  from_port         = 22
  to_port           = 22
  security_group_id = aws_security_group.server.id

  source_security_group_id = aws_security_group.bastion.id
}

resource "aws_security_group" "database" {
  name        = "webcms-database-sg"
  description = "Security group for the RDS database"

  vpc_id = aws_vpc.main.id

  tags = {
    Application = "WebCMS"
    Name        = "WebCMS RDS"
  }
}

# Because Drupal tasks are run in the AWSVPC networking mode, we are able to assign
# custom security groups to the container - this enables us to grant database access
# to Drupal while denying it at the EC2 instance level.
resource "aws_security_group" "drupal_task" {
  name        = "webcms-drupal-sg"
  description = "Security group for the WebCMS Drupal container tasks"

  vpc_id = aws_vpc.main.id

  egress {
    description = "Allow outgoing HTTP traffic"

    protocol    = "tcp"
    from_port   = 80
    to_port     = 80
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    description = "Allow outgoing HTTPS traffic"

    protocol    = "tcp"
    from_port   = 443
    to_port     = 443
    cidr_blocks = ["0.0.0.0/0"]
  }

  # The corresponding ingress rule for RDS is at the end of this file. We use this here
  # because for some reason, the egress list attached to this resource is somewhat
  # brittle, so we can't create a separate aws_security_group_rule like we normally do.
  egress {
    description = "Allow outgoing connections to RDS"

    protocol        = "tcp"
    from_port       = 3306
    to_port         = 3306
    security_groups = [aws_security_group.database.id]
  }

  tags = {
    Application = "WebCMS"
    Name        = "WebCMS Drupal Containers"
  }
}

# Rule: egress from load balancers to Drupal
resource "aws_security_group_rule" "lb_drupal_egress" {
  description = "Allow outgoing connections from ALBs to Drupal tasks"

  security_group_id = aws_security_group.load_balancer.id

  type                     = "egress"
  protocol                 = "tcp"
  from_port                = 80
  to_port                  = 80
  source_security_group_id = aws_security_group.drupal_task.id
}

# Rule: ingress to Drupal from load balancers
# This is the reverse of the above rule
resource "aws_security_group_rule" "drupal_lb_ingress" {
  description = "Allow incoming connections from ALBs to Drupal tasks"

  security_group_id = aws_security_group.drupal_task.id

  type                     = "ingress"
  protocol                 = "tcp"
  from_port                = 80
  to_port                  = 80
  source_security_group_id = aws_security_group.load_balancer.id
}

# Rule: ingress to RDS from Drupal
resource "aws_security_group_rule" "db_task_ingress" {
  description = "Allow incoming connections from Drupal tasks to RDS"

  security_group_id = aws_security_group.database.id

  type                     = "ingress"
  protocol                 = "tcp"
  from_port                = 3306
  to_port                  = 3306
  source_security_group_id = aws_security_group.drupal_task.id
}

# Rule: ingress to RDS from bastion server
resource "aws_security_group_rule" "db_bastion_ingress" {
  description = "Allow incoming connections from the SSH bastion to RDS"

  security_group_id = aws_security_group.database.id

  type                     = "ingress"
  protocol                 = "tcp"
  from_port                = 3306
  to_port                  = 3306
  source_security_group_id = aws_security_group.bastion.id
}

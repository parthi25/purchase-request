-- Create status_permissions table
-- This table defines which roles can change to which statuses
CREATE TABLE IF NOT EXISTS `status_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(50) NOT NULL COMMENT 'User role (admin, buyer, B_Head, PO_Team, PO_Team_Member)',
  `status_id` INT(11) NOT NULL COMMENT 'Status ID that this role can change to',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Whether this permission is active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_status` (`role`, `status_id`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status_id`),
  FOREIGN KEY (`status_id`) REFERENCES `status`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create status_flow table
-- This table defines the flow/transitions between statuses
CREATE TABLE IF NOT EXISTS `status_flow` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `from_status_id` INT(11) NOT NULL COMMENT 'Current status ID',
  `to_status_id` INT(11) NOT NULL COMMENT 'Next status ID',
  `role` VARCHAR(50) NOT NULL COMMENT 'Role that can make this transition',
  `requires_proforma` TINYINT(1) DEFAULT 0 COMMENT 'Whether proforma is required for this transition',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Whether this flow is active',
  `priority` INT(11) DEFAULT 0 COMMENT 'Priority order for multiple flows',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_flow` (`from_status_id`, `to_status_id`, `role`),
  KEY `idx_from_status` (`from_status_id`),
  KEY `idx_to_status` (`to_status_id`),
  KEY `idx_role` (`role`),
  FOREIGN KEY (`from_status_id`) REFERENCES `status`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`to_status_id`) REFERENCES `status`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default permissions based on current hardcoded values
-- Admin can change to status 1
INSERT INTO `status_permissions` (`role`, `status_id`, `is_active`) VALUES
('admin', 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1;

-- Buyer can change to statuses 3, 4, 5
INSERT INTO `status_permissions` (`role`, `status_id`, `is_active`) VALUES
('buyer', 3, 1),
('buyer', 4, 1),
('buyer', 5, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1;

-- B_Head can change to statuses 2, 6, 8
INSERT INTO `status_permissions` (`role`, `status_id`, `is_active`) VALUES
('B_Head', 2, 1),
('B_Head', 6, 1),
('B_Head', 8, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1;

-- PO_Team can change to status 9
INSERT INTO `status_permissions` (`role`, `status_id`, `is_active`) VALUES
('PO_Team', 9, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1;

-- PO_Team_Member can change to status 7
INSERT INTO `status_permissions` (`role`, `status_id`, `is_active`) VALUES
('PO_Team_Member', 7, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1;

-- Insert default status flows
-- From status 1, B_Head can go to status 2 (Forwarded to Buyer)
INSERT INTO `status_flow` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(1, 2, 'B_Head', 0, 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1;

-- From status 1, B_Head can go to status 6 (Forwarded to PO Team) if proforma exists
INSERT INTO `status_flow` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(1, 6, 'B_Head', 1, 1, 2)
ON DUPLICATE KEY UPDATE `is_active` = 1;

-- From status 2, buyer can go to status 3 (Awaiting PO)
INSERT INTO `status_flow` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(2, 3, 'buyer', 0, 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1;

-- From status 3, buyer can go to status 4 (Received Proforma PO)
INSERT INTO `status_flow` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(3, 4, 'buyer', 0, 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1;

-- From status 4, buyer can go to status 5 (Forwarded to Buyer Head)
INSERT INTO `status_flow` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(4, 5, 'buyer', 0, 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1;

-- From status 5, B_Head can go to status 6 (Forwarded to PO Team)
INSERT INTO `status_flow` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(5, 6, 'B_Head', 0, 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1;

-- From status 6, PO_Team can go to status 9 (Forwarded to PO Members)
INSERT INTO `status_flow` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(6, 9, 'PO_Team', 0, 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1;

-- From status 9, PO_Team_Member can go to status 7 (PO Generated)
INSERT INTO `status_flow` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(9, 7, 'PO_Team_Member', 0, 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1;

-- Rejection flows (any status can be rejected by B_Head)
INSERT INTO `status_flow` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(1, 8, 'B_Head', 0, 1, 0),
(2, 8, 'B_Head', 0, 1, 0),
(3, 8, 'B_Head', 0, 1, 0),
(4, 8, 'B_Head', 0, 1, 0),
(5, 8, 'B_Head', 0, 1, 0)
ON DUPLICATE KEY UPDATE `is_active` = 1;


-- Create pr_permissions table
-- This table defines which roles can create and edit PRs
CREATE TABLE IF NOT EXISTS `pr_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(50) NOT NULL COMMENT 'User role (admin, buyer, B_Head, etc.)',
  `can_create` TINYINT(1) DEFAULT 0 COMMENT 'Whether this role can create PRs',
  `can_edit` TINYINT(1) DEFAULT 0 COMMENT 'Whether this role can edit PRs',
  `can_edit_status` INT(11) DEFAULT NULL COMMENT 'PR can only be edited when status equals this value (NULL = any status)',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Whether this permission is active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role` (`role`),
  KEY `idx_role` (`role`),
  KEY `idx_can_create` (`can_create`),
  KEY `idx_can_edit` (`can_edit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default permissions
-- Admin can create and edit PRs (edit only when status is 1)
INSERT INTO `pr_permissions` (`role`, `can_create`, `can_edit`, `can_edit_status`, `is_active`) VALUES
('admin', 1, 1, 1, 1)
ON DUPLICATE KEY UPDATE `can_create` = 1, `can_edit` = 1, `can_edit_status` = 1, `is_active` = 1;

-- Buyer can create and edit PRs (edit only when status is 1)
INSERT INTO `pr_permissions` (`role`, `can_create`, `can_edit`, `can_edit_status`, `is_active`) VALUES
('buyer', 1, 1, 1, 1)
ON DUPLICATE KEY UPDATE `can_create` = 1, `can_edit` = 1, `can_edit_status` = 1, `is_active` = 1;

-- B_Head can create and edit PRs (edit only when status is 1)
INSERT INTO `pr_permissions` (`role`, `can_create`, `can_edit`, `can_edit_status`, `is_active`) VALUES
('B_Head', 1, 1, 1, 1)
ON DUPLICATE KEY UPDATE `can_create` = 1, `can_edit` = 1, `can_edit_status` = 1, `is_active` = 1;


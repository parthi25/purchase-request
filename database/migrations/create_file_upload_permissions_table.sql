-- Create file_upload_permissions table
-- This table defines which roles can upload/delete files for specific file types and statuses
CREATE TABLE IF NOT EXISTS `file_upload_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(50) NOT NULL COMMENT 'User role (admin, buyer, B_Head, PO_Team, PO_Team_Member)',
  `file_type` VARCHAR(20) NOT NULL COMMENT 'File type: proforma, po, product',
  `status_id` INT(11) DEFAULT NULL COMMENT 'Status ID that allows upload (NULL = any status)',
  `can_upload` TINYINT(1) DEFAULT 0 COMMENT 'Whether this role can upload files',
  `can_delete` TINYINT(1) DEFAULT 0 COMMENT 'Whether this role can delete files',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Whether this permission is active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_file_status` (`role`, `file_type`, `status_id`),
  KEY `idx_role` (`role`),
  KEY `idx_file_type` (`file_type`),
  KEY `idx_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default permissions based on current hardcoded logic

-- Proforma file permissions
-- B_Head can upload/delete proforma files when status is 1 or 5
-- buyer can upload/delete proforma files when status is 1, 2, or 3
INSERT INTO `file_upload_permissions` (`role`, `file_type`, `status_id`, `can_upload`, `can_delete`, `is_active`) VALUES
('B_Head', 'proforma', 1, 1, 1, 1),
('B_Head', 'proforma', 5, 1, 1, 1),
('buyer', 'proforma', 1, 1, 1, 1),
('buyer', 'proforma', 2, 1, 1, 1),
('buyer', 'proforma', 3, 1, 1, 1)
ON DUPLICATE KEY UPDATE `can_upload` = 1, `can_delete` = 1, `is_active` = 1, `updated_at` = NOW();

-- PO file permissions
-- PO_Team and PO_Team_Member can upload/delete PO files when status is 7
INSERT INTO `file_upload_permissions` (`role`, `file_type`, `status_id`, `can_upload`, `can_delete`, `is_active`) VALUES
('PO_Team', 'po', 7, 1, 1, 1),
('PO_Team_Member', 'po', 7, 1, 1, 1)
ON DUPLICATE KEY UPDATE `can_upload` = 1, `can_delete` = 1, `is_active` = 1, `updated_at` = NOW();

-- Product file permissions
-- B_Head, buyer, and admin can upload/delete product files when status is 1, 2, 3, 4, or 5
INSERT INTO `file_upload_permissions` (`role`, `file_type`, `status_id`, `can_upload`, `can_delete`, `is_active`) VALUES
('B_Head', 'product', 1, 1, 1, 1),
('B_Head', 'product', 2, 1, 1, 1),
('B_Head', 'product', 3, 1, 1, 1),
('B_Head', 'product', 4, 1, 1, 1),
('B_Head', 'product', 5, 1, 1, 1),
('buyer', 'product', 1, 1, 1, 1),
('buyer', 'product', 2, 1, 1, 1),
('buyer', 'product', 3, 1, 1, 1),
('buyer', 'product', 4, 1, 1, 1),
('buyer', 'product', 5, 1, 1, 1),
('admin', 'product', 1, 1, 1, 1),
('admin', 'product', 2, 1, 1, 1),
('admin', 'product', 3, 1, 1, 1),
('admin', 'product', 4, 1, 1, 1),
('admin', 'product', 5, 1, 1, 1)
ON DUPLICATE KEY UPDATE `can_upload` = 1, `can_delete` = 1, `is_active` = 1, `updated_at` = NOW();


-- ============================================
-- Master Data Deployment Script
-- ============================================
-- This script contains all master data needed for the PR system
-- Run this after creating the database schema
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================
-- 1. PR STATUSES MASTER DATA
-- ============================================
-- Insert all status values that the system uses

INSERT INTO `pr_statuses` (`id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Open', NOW(), NOW()),
(2, 'Forwarded to Buyer', NOW(), NOW()),
(3, 'Agent/Supplier contacted and Awaiting PO details', NOW(), NOW()),
(4, 'Received Proforma PO', NOW(), NOW()),
(5, 'Forwarded to Buyer Head', NOW(), NOW()),
(6, 'Forwarded to PO Team', NOW(), NOW()),
(7, 'PO generated', NOW(), NOW()),
(8, 'Rejected', NOW(), NOW()),
(9, 'Forwarded to PO Members', NOW(), NOW())
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `updated_at` = NOW();

-- ============================================
-- 2. ROLE STATUS PERMISSIONS
-- ============================================
-- Defines which roles can change to which statuses

-- Create table if not exists (with updated table name)
CREATE TABLE IF NOT EXISTS `role_status_permissions` (
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
  CONSTRAINT `fk_role_status_permissions_status` FOREIGN KEY (`status_id`) REFERENCES `pr_statuses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin can change to status 1
INSERT INTO `role_status_permissions` (`role`, `status_id`, `is_active`) VALUES
('admin', 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- Buyer can change to statuses 3, 4, 5
INSERT INTO `role_status_permissions` (`role`, `status_id`, `is_active`) VALUES
('buyer', 3, 1),
('buyer', 4, 1),
('buyer', 5, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- B_Head can change to statuses 2, 6, 8
INSERT INTO `role_status_permissions` (`role`, `status_id`, `is_active`) VALUES
('B_Head', 2, 1),
('B_Head', 6, 1),
('B_Head', 8, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- PO_Team can change to status 9
INSERT INTO `role_status_permissions` (`role`, `status_id`, `is_active`) VALUES
('PO_Team', 9, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- PO_Team_Member can change to status 7
INSERT INTO `role_status_permissions` (`role`, `status_id`, `is_active`) VALUES
('PO_Team_Member', 7, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- ============================================
-- 3. STATUS TRANSITIONS (Status Flow)
-- ============================================
-- Defines the flow/transitions between statuses

-- Create table if not exists (with updated table name)
CREATE TABLE IF NOT EXISTS `status_transitions` (
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
  CONSTRAINT `fk_status_transitions_from` FOREIGN KEY (`from_status_id`) REFERENCES `pr_statuses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_status_transitions_to` FOREIGN KEY (`to_status_id`) REFERENCES `pr_statuses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- From status 1, B_Head can go to status 2 (Forwarded to Buyer)
INSERT INTO `status_transitions` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(1, 2, 'B_Head', 0, 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- From status 1, B_Head can go to status 6 (Forwarded to PO Team) if proforma exists
INSERT INTO `status_transitions` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(1, 6, 'B_Head', 1, 1, 2)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- From status 2, buyer can go to status 3 (Awaiting PO)
INSERT INTO `status_transitions` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(2, 3, 'buyer', 0, 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- From status 3, buyer can go to status 4 (Received Proforma PO)
INSERT INTO `status_transitions` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(3, 4, 'buyer', 0, 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- From status 4, buyer can go to status 5 (Forwarded to Buyer Head)
INSERT INTO `status_transitions` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(4, 5, 'buyer', 0, 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- From status 5, B_Head can go to status 6 (Forwarded to PO Team)
INSERT INTO `status_transitions` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(5, 6, 'B_Head', 0, 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- From status 6, PO_Team can go to status 9 (Forwarded to PO Members)
INSERT INTO `status_transitions` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(6, 9, 'PO_Team', 0, 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- From status 9, PO_Team_Member can go to status 7 (PO Generated)
INSERT INTO `status_transitions` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(9, 7, 'PO_Team_Member', 0, 1, 1)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- Rejection flows (any status can be rejected by B_Head)
INSERT INTO `status_transitions` (`from_status_id`, `to_status_id`, `role`, `requires_proforma`, `is_active`, `priority`) VALUES
(1, 8, 'B_Head', 0, 1, 0),
(2, 8, 'B_Head', 0, 1, 0),
(3, 8, 'B_Head', 0, 1, 0),
(4, 8, 'B_Head', 0, 1, 0),
(5, 8, 'B_Head', 0, 1, 0)
ON DUPLICATE KEY UPDATE `is_active` = 1, `updated_at` = NOW();

-- ============================================
-- 4. ROLE PR PERMISSIONS
-- ============================================
-- Defines which roles can create and edit PRs

-- Create table if not exists (with updated table name)
CREATE TABLE IF NOT EXISTS `role_pr_permissions` (
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

-- Admin can create and edit PRs (edit only when status is 1)
INSERT INTO `role_pr_permissions` (`role`, `can_create`, `can_edit`, `can_edit_status`, `is_active`) VALUES
('admin', 1, 1, 1, 1)
ON DUPLICATE KEY UPDATE `can_create` = 1, `can_edit` = 1, `can_edit_status` = 1, `is_active` = 1, `updated_at` = NOW();

-- Buyer can create and edit PRs (edit only when status is 1)
INSERT INTO `role_pr_permissions` (`role`, `can_create`, `can_edit`, `can_edit_status`, `is_active`) VALUES
('buyer', 1, 1, 1, 1)
ON DUPLICATE KEY UPDATE `can_create` = 1, `can_edit` = 1, `can_edit_status` = 1, `is_active` = 1, `updated_at` = NOW();

-- B_Head can create and edit PRs (edit only when status is 1)
INSERT INTO `role_pr_permissions` (`role`, `can_create`, `can_edit`, `can_edit_status`, `is_active`) VALUES
('B_Head', 1, 1, 1, 1)
ON DUPLICATE KEY UPDATE `can_create` = 1, `can_edit` = 1, `can_edit_status` = 1, `is_active` = 1, `updated_at` = NOW();

-- ============================================
-- 5. STATUS MODAL FIELDS
-- ============================================
-- Defines which input fields should be shown in the status modal for each status

-- Create table if not exists
CREATE TABLE IF NOT EXISTS `status_modal_fields` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `status_id` INT(11) NOT NULL,
    `field_name` VARCHAR(50) NOT NULL COMMENT 'Field identifier: buyer, po_head, po_team, qty, file_upload, remark',
    `is_required` TINYINT(1) DEFAULT 0 COMMENT 'Whether the field is required',
    `field_order` INT(11) DEFAULT 0 COMMENT 'Order in which fields should appear',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_status_field` (`status_id`, `field_name`),
    KEY `idx_status_id` (`status_id`),
    CONSTRAINT `fk_modal_field_status` FOREIGN KEY (`status_id`) REFERENCES `pr_statuses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Status 2: Forwarded to Buyer
INSERT INTO `status_modal_fields` (`status_id`, `field_name`, `is_required`, `field_order`) VALUES
(2, 'buyer', 1, 1),
(2, 'remark', 0, 2)
ON DUPLICATE KEY UPDATE `is_required` = VALUES(`is_required`), `field_order` = VALUES(`field_order`), `updated_at` = NOW();

-- Status 4: Received Proforma PO
INSERT INTO `status_modal_fields` (`status_id`, `field_name`, `is_required`, `field_order`) VALUES
(4, 'qty', 0, 1),
(4, 'file_upload', 0, 2)
ON DUPLICATE KEY UPDATE `is_required` = VALUES(`is_required`), `field_order` = VALUES(`field_order`), `updated_at` = NOW();

-- Status 5: Forwarded to Buyer Head
INSERT INTO `status_modal_fields` (`status_id`, `field_name`, `is_required`, `field_order`) VALUES
(5, 'remark', 0, 1)
ON DUPLICATE KEY UPDATE `is_required` = VALUES(`is_required`), `field_order` = VALUES(`field_order`), `updated_at` = NOW();

-- Status 6: Forwarded to PO Head
INSERT INTO `status_modal_fields` (`status_id`, `field_name`, `is_required`, `field_order`) VALUES
(6, 'po_head', 0, 1),
(6, 'buyer', 0, 2),
(6, 'remark', 0, 3)
ON DUPLICATE KEY UPDATE `is_required` = VALUES(`is_required`), `field_order` = VALUES(`field_order`), `updated_at` = NOW();

-- Status 8: Rejected
INSERT INTO `status_modal_fields` (`status_id`, `field_name`, `is_required`, `field_order`) VALUES
(8, 'remark', 0, 1)
ON DUPLICATE KEY UPDATE `is_required` = VALUES(`is_required`), `field_order` = VALUES(`field_order`), `updated_at` = NOW();

-- Status 9: Forwarded to PO Team
INSERT INTO `status_modal_fields` (`status_id`, `field_name`, `is_required`, `field_order`) VALUES
(9, 'po_team', 1, 1),
(9, 'remark', 0, 2)
ON DUPLICATE KEY UPDATE `is_required` = VALUES(`is_required`), `field_order` = VALUES(`field_order`), `updated_at` = NOW();

-- ============================================
-- 6. PURCHASE TYPES (Optional - can be added later)
-- ============================================
-- Create table if not exists
CREATE TABLE IF NOT EXISTS `purchase_types` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. CATEGORIES (Optional - can be added later)
-- ============================================
-- Create table if not exists
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `maincat` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_maincat` (`maincat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================
-- Deployment Complete!
-- ============================================
-- Master data has been deployed successfully.
-- You can now:
-- 1. Create users with roles (admin, buyer, B_Head, PO_Team, PO_Team_Member)
-- 2. Map categories to buyer heads using buyer_head_categories table
-- 3. Map buyers to buyer heads using buyers_info table
-- 4. Start using the system!
-- ============================================


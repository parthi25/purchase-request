-- ============================================
-- Master Data Insertion Script
-- ============================================
-- This script ensures all master data is present:
-- 1. Default roles in roles table
-- 2. Menu items for super_admin
-- 3. Initial page settings for all roles
-- ============================================

-- Ensure roles table has all default roles
INSERT INTO `roles` (`role_code`, `role_name`, `description`, `is_active`, `display_order`) VALUES
('admin', 'Admin', 'Administrator role with full access', 1, 1),
('buyer', 'Buyer', 'Buyer role for creating and managing purchase requests', 1, 2),
('B_Head', 'Buyer Head', 'Buyer Head role for approving and managing buyer requests', 1, 3),
('PO_Head', 'PO Head', 'Purchase Order Head role for managing PO operations', 1, 4),
('PO_Team_Member', 'PO Team Member', 'Purchase Order Team Member role', 1, 5),
('PO_Team', 'PO Team', 'Purchase Order Team role', 1, 6),
('super_admin', 'Super Admin', 'Super Administrator with system-wide access', 1, 7),
('master', 'Master', 'Master role with complete system control', 1, 8)
ON DUPLICATE KEY UPDATE `role_name` = VALUES(`role_name`), `description` = VALUES(`description`), `is_active` = VALUES(`is_active`), `display_order` = VALUES(`display_order`), `updated_at` = NOW();

-- Ensure role_menu_settings has menu items for super_admin (Role Management and Role Initial Settings)
INSERT INTO `role_menu_settings` (`role`, `menu_item_label`, `menu_item_url`, `menu_item_icon`, `menu_order`, `is_visible`, `menu_group`) VALUES
('super_admin', 'Role Management', 'role-management.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>', 17, 1, 'master_management'),
('super_admin', 'Role Initial Settings', 'role-initial-settings.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>', 18, 1, 'master_management')
ON DUPLICATE KEY UPDATE `menu_item_label` = VALUES(`menu_item_label`), `menu_item_icon` = VALUES(`menu_item_icon`), `menu_order` = VALUES(`menu_order`), `is_visible` = VALUES(`is_visible`), `menu_group` = VALUES(`menu_group`), `updated_at` = NOW();

-- Ensure role_initial_settings has default settings
INSERT INTO `role_initial_settings` (`role`, `initial_page_url`, `initial_status_filter`) VALUES
('admin', 'admin.php', 1),
('buyer', 'buyer.php', 2),
('B_Head', 'buyer-head.php', 1),
('PO_Head', 'po-head.php', 1),
('PO_Team_Member', 'po-member.php', 9),
('super_admin', 'admin.php', 1),
('master', 'admin.php', 1)
ON DUPLICATE KEY UPDATE `initial_page_url` = VALUES(`initial_page_url`), `initial_status_filter` = VALUES(`initial_status_filter`), `updated_at` = NOW();


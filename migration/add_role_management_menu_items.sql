-- Add Role Management and Role Initial Settings menu items for super_admin
INSERT INTO `role_menu_settings` (`role`, `menu_item_label`, `menu_item_url`, `menu_item_icon`, `menu_order`, `is_visible`, `menu_group`) VALUES
('super_admin', 'Role Management', 'role-management.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>', 17, 1, 'master_management'),
('super_admin', 'Role Initial Settings', 'role-initial-settings.php', '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>', 18, 1, 'master_management')
ON DUPLICATE KEY UPDATE `menu_item_label` = VALUES(`menu_item_label`), `menu_item_icon` = VALUES(`menu_item_icon`), `menu_order` = VALUES(`menu_order`), `is_visible` = VALUES(`is_visible`), `menu_group` = VALUES(`menu_group`), `updated_at` = NOW();

-- ============================================
-- Note: After running this migration, you should also run:
-- migration/alter_users_role_to_id.sql
-- to convert users.role from ENUM to role_id foreign key
-- ============================================


-- Add Buyer Category Mapping menu item to super_admin and master roles
INSERT INTO role_menu_settings (role, menu_item_label, menu_item_url, menu_item_icon, menu_order, menu_group, is_visible, is_active)
SELECT 'super_admin', 'Buyer Category Mapping', 'pages/buyer-category-mapping.php', 
       '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>',
       15, 'master_management', 1, 1
WHERE NOT EXISTS (
    SELECT 1 FROM role_menu_settings 
    WHERE role = 'super_admin' AND menu_item_url = 'pages/buyer-category-mapping.php'
);

INSERT INTO role_menu_settings (role, menu_item_label, menu_item_url, menu_item_icon, menu_order, menu_group, is_visible, is_active)
SELECT 'master', 'Buyer Category Mapping', 'pages/buyer-category-mapping.php', 
       '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>',
       15, 'master_management', 1, 1
WHERE NOT EXISTS (
    SELECT 1 FROM role_menu_settings 
    WHERE role = 'master' AND menu_item_url = 'pages/buyer-category-mapping.php'
);


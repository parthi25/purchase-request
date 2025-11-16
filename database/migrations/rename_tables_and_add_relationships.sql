-- ============================================
-- Database Table Renaming and Relationship Migration
-- ============================================
-- This migration renames tables to follow better naming conventions
-- and adds proper foreign key relationships

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 1. RENAME MAIN TABLES
-- ============================================

-- po_tracking → purchase_requests (Main PR table)
RENAME TABLE `po_tracking` TO `purchase_requests`;

-- cat → categories
RENAME TABLE `cat` TO `categories`;

-- purchase_master → purchase_types
RENAME TABLE `purchase_master` TO `purchase_types`;

-- status → pr_statuses
RENAME TABLE `status` TO `pr_statuses`;

-- new_supplier → supplier_requests
RENAME TABLE `new_supplier` TO `supplier_requests`;

-- po_team_member → pr_assignments
RENAME TABLE `po_team_member` TO `pr_assignments`;

-- po_ → po_documents (PO files)
RENAME TABLE `po_` TO `po_documents`;

-- po_order → pr_attachments (Product images/files)
RENAME TABLE `po_order` TO `pr_attachments`;

-- ============================================
-- 2. RENAME PERMISSION TABLES
-- ============================================

-- status_permissions → role_status_permissions
RENAME TABLE `status_permissions` TO `role_status_permissions`;

-- status_flow → status_transitions
RENAME TABLE `status_flow` TO `status_transitions`;

-- pr_permissions → role_pr_permissions
RENAME TABLE `pr_permissions` TO `role_pr_permissions`;

-- ============================================
-- 3. ADD FOREIGN KEY RELATIONSHIPS
-- ============================================
-- Note: These will fail if foreign keys already exist or columns don't exist
-- That's okay - you can run them individually if needed

-- purchase_requests foreign keys (add one at a time to avoid issues)
ALTER TABLE `purchase_requests`
    ADD CONSTRAINT `fk_pr_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `purchase_requests`
    ADD CONSTRAINT `fk_pr_b_head` FOREIGN KEY (`b_head`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `purchase_requests`
    ADD CONSTRAINT `fk_pr_buyer` FOREIGN KEY (`buyer`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `purchase_requests`
    ADD CONSTRAINT `fk_pr_po_team` FOREIGN KEY (`po_team`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `purchase_requests`
    ADD CONSTRAINT `fk_pr_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `purchase_requests`
    ADD CONSTRAINT `fk_pr_new_supplier` FOREIGN KEY (`new_supplier`) REFERENCES `supplier_requests`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `purchase_requests`
    ADD CONSTRAINT `fk_pr_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `purchase_requests`
    ADD CONSTRAINT `fk_pr_purchase_type` FOREIGN KEY (`purch_id`) REFERENCES `purchase_types`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `purchase_requests`
    ADD CONSTRAINT `fk_pr_status` FOREIGN KEY (`po_status`) REFERENCES `pr_statuses`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- pr_assignments foreign keys
ALTER TABLE `pr_assignments`
    ADD CONSTRAINT `fk_assignment_pr` FOREIGN KEY (`ord_id`) REFERENCES `purchase_requests`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_assignment_member` FOREIGN KEY (`po_team_member`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- po_documents foreign keys
ALTER TABLE `po_documents`
    ADD CONSTRAINT `fk_po_doc_pr` FOREIGN KEY (`ord_id`) REFERENCES `purchase_requests`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- pr_attachments foreign keys
ALTER TABLE `pr_attachments`
    ADD CONSTRAINT `fk_attachment_pr` FOREIGN KEY (`ord_id`) REFERENCES `purchase_requests`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- supplier_requests foreign keys
ALTER TABLE `supplier_requests`
    ADD CONSTRAINT `fk_supplier_req_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- role_status_permissions foreign keys
ALTER TABLE `role_status_permissions`
    ADD CONSTRAINT `fk_role_status_perm_status` FOREIGN KEY (`status_id`) REFERENCES `pr_statuses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- status_transitions foreign keys
ALTER TABLE `status_transitions`
    ADD CONSTRAINT `fk_transition_from_status` FOREIGN KEY (`from_status_id`) REFERENCES `pr_statuses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_transition_to_status` FOREIGN KEY (`to_status_id`) REFERENCES `pr_statuses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- role_pr_permissions foreign keys
ALTER TABLE `role_pr_permissions`
    ADD CONSTRAINT `fk_pr_perm_status` FOREIGN KEY (`can_edit_status`) REFERENCES `pr_statuses`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- 4. ADD INDEXES FOR BETTER PERFORMANCE
-- ============================================

-- Add indexes to purchase_requests for common queries
ALTER TABLE `purchase_requests`
    ADD INDEX `idx_po_status` (`po_status`),
    ADD INDEX `idx_created_by` (`created_by`),
    ADD INDEX `idx_b_head` (`b_head`),
    ADD INDEX `idx_created_at` (`created_at`);

-- Add indexes to pr_assignments
ALTER TABLE `pr_assignments`
    ADD INDEX `idx_ord_id` (`ord_id`),
    ADD INDEX `idx_po_team_member` (`po_team_member`);

-- Add indexes to po_documents
ALTER TABLE `po_documents`
    ADD INDEX `idx_ord_id` (`ord_id`);

-- Add indexes to pr_attachments
ALTER TABLE `pr_attachments`
    ADD INDEX `idx_ord_id` (`ord_id`);

-- ============================================
-- MIGRATION COMPLETE
-- ============================================

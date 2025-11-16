-- ============================================
-- Status Modal Fields Configuration Table
-- ============================================
-- This table stores which input fields should be shown
-- in the status modal for each status

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

-- ============================================
-- Insert Default Field Configurations
-- ============================================

-- Status 2: Forwarded to Buyer
INSERT INTO `status_modal_fields` (`status_id`, `field_name`, `is_required`, `field_order`) VALUES
(2, 'buyer', 1, 1),
(2, 'remark', 0, 2);

-- Status 3: Awaiting PO (no fields)

-- Status 4: Received Proforma PO
INSERT INTO `status_modal_fields` (`status_id`, `field_name`, `is_required`, `field_order`) VALUES
(4, 'qty', 0, 1),
(4, 'file_upload', 0, 2);

-- Status 5: Forwarded to Buyer Head
INSERT INTO `status_modal_fields` (`status_id`, `field_name`, `is_required`, `field_order`) VALUES
(5, 'remark', 0, 1);

-- Status 6: Forwarded to PO Head
INSERT INTO `status_modal_fields` (`status_id`, `field_name`, `is_required`, `field_order`) VALUES
(6, 'po_head', 0, 1),
(6, 'buyer', 0, 2),
(6, 'remark', 0, 3);

-- Status 7: PO Generated (no fields)

-- Status 8: Rejected
INSERT INTO `status_modal_fields` (`status_id`, `field_name`, `is_required`, `field_order`) VALUES
(8, 'remark', 0, 1);

-- Status 9: Forwarded to PO Team
INSERT INTO `status_modal_fields` (`status_id`, `field_name`, `is_required`, `field_order`) VALUES
(9, 'po_team', 1, 1),
(9, 'remark', 0, 2);


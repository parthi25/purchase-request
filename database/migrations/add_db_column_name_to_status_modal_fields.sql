-- ============================================
-- Add db_column_name column to status_modal_fields
-- This column stores which database column should be used
-- to store the value for remark fields
-- ============================================

-- Add the new column
ALTER TABLE `status_modal_fields` 
ADD COLUMN `db_column_name` VARCHAR(50) NULL DEFAULT NULL 
COMMENT 'Database column name where this field value should be stored (e.g., b_remark, to_bh_rm, po_team_rm, rrm). Only used for remark fields.' 
AFTER `field_order`;

-- Update existing remark fields with their corresponding column names
UPDATE `status_modal_fields` 
SET `db_column_name` = 'b_remark' 
WHERE `status_id` = 2 AND `field_name` = 'remark';

UPDATE `status_modal_fields` 
SET `db_column_name` = 'to_bh_rm' 
WHERE `status_id` = 5 AND `field_name` = 'remark';

UPDATE `status_modal_fields` 
SET `db_column_name` = 'po_team_rm' 
WHERE `status_id` = 6 AND `field_name` = 'remark';

UPDATE `status_modal_fields` 
SET `db_column_name` = 'rrm' 
WHERE `status_id` = 8 AND `field_name` = 'remark';

UPDATE `status_modal_fields` 
SET `db_column_name` = 'rrm' 
WHERE `status_id` = 9 AND `field_name` = 'remark';


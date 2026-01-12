-- Migration: Add GST, PAN, Mobile, Email fields to supplier_requests table
-- Date: 2024-01-XX
-- Description: Adds gst_no, pan_no, mobile, email columns to supplier_requests table

-- Check if columns already exist before adding
SET @gst_no_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'supplier_requests' 
    AND COLUMN_NAME = 'gst_no'
);

SET @pan_no_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'supplier_requests' 
    AND COLUMN_NAME = 'pan_no'
);

SET @mobile_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'supplier_requests' 
    AND COLUMN_NAME = 'mobile'
);

SET @email_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'supplier_requests' 
    AND COLUMN_NAME = 'email'
);

-- Add gst_no column if it doesn't exist
SET @sql_gst = IF(
    @gst_no_exists = 0,
    'ALTER TABLE `supplier_requests` ADD COLUMN `gst_no` VARCHAR(50) NULL AFTER `city`',
    'SELECT "Column gst_no already exists" AS message'
);
PREPARE stmt FROM @sql_gst;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add pan_no column if it doesn't exist
SET @sql_pan = IF(
    @pan_no_exists = 0,
    'ALTER TABLE `supplier_requests` ADD COLUMN `pan_no` VARCHAR(20) NULL AFTER `gst_no`',
    'SELECT "Column pan_no already exists" AS message'
);
PREPARE stmt FROM @sql_pan;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add mobile column if it doesn't exist
SET @sql_mobile = IF(
    @mobile_exists = 0,
    'ALTER TABLE `supplier_requests` ADD COLUMN `mobile` VARCHAR(20) NULL AFTER `pan_no`',
    'SELECT "Column mobile already exists" AS message'
);
PREPARE stmt FROM @sql_mobile;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add email column if it doesn't exist
SET @sql_email = IF(
    @email_exists = 0,
    'ALTER TABLE `supplier_requests` ADD COLUMN `email` VARCHAR(255) NULL AFTER `mobile`',
    'SELECT "Column email already exists" AS message'
);
PREPARE stmt FROM @sql_email;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
SELECT COUNT(*) INTO @supplier_code_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'jcrc_ch'
AND TABLE_NAME = 'supplier_requests'
AND COLUMN_NAME = 'supplier_code';
SET @sql_supplier_code = IF(
    @supplier_code_exists = 0,
    'ALTER TABLE supplier_requests ADD COLUMN supplier_code VARCHAR(50) NULL AFTER email',
    'SELECT "supplier_code already exists"'
);

PREPARE stmt FROM @sql_supplier_code;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
-- ============================================
-- Add item details columns to proforma table
-- ============================================
-- This migration adds two optional columns:
-- 1. item_details_url - URL for item details upload
-- 2. item_info - Text field to store item code, name, price (new/old item info)
-- ============================================

-- Check if columns exist before adding (MySQL 5.7+ compatible)
SET @dbname = DATABASE();
SET @tablename = 'proforma';
SET @columnname1 = 'item_details_url';
SET @columnname2 = 'item_info';

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname1)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname1, '` VARCHAR(500) NULL DEFAULT NULL COMMENT ''URL for item details upload file'' AFTER `filename`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname2)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN `', @columnname2, '` TEXT NULL DEFAULT NULL COMMENT ''Item information: new/old item code, name, price stored as comment'' AFTER `item_details_url`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;


-- ============================================
-- Add item details columns to proforma table
-- ============================================
-- This migration adds two optional columns:
-- 1. item_details_url - URL for item details upload
-- 2. item_info - Text field to store item code, name, price (new/old item info)
-- ============================================
-- Note: This is a simpler version that will show an error if columns already exist
--       but the error can be safely ignored
-- ============================================

-- Add item_details_url column
ALTER TABLE `proforma` 
ADD COLUMN `item_details_url` VARCHAR(500) NULL DEFAULT NULL COMMENT 'URL for item details upload file' AFTER `filename`;

-- Add item_info column  
ALTER TABLE `proforma` 
ADD COLUMN `item_info` TEXT NULL DEFAULT NULL COMMENT 'Item information: new/old item code, name, price stored as comment' AFTER `item_details_url`;


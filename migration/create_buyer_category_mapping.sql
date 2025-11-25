-- ============================================
-- Create Buyer Category Mapping Table
-- ============================================
-- This table maps buyers directly to categories
-- This migration is idempotent and can be run multiple times safely
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Check if table exists
SET @table_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'buyer_category_mapping'
);

-- Create table only if it doesn't exist
SET @create_table_sql = IF(
    @table_exists = 0,
    'CREATE TABLE `buyer_category_mapping` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `buyer_id` INT(11) NOT NULL COMMENT ''User ID of the buyer'',
      `category_id` INT(11) NOT NULL COMMENT ''Category ID'',
      `is_active` TINYINT(1) DEFAULT 1 COMMENT ''Whether this mapping is active'',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_buyer_category` (`buyer_id`, `category_id`),
      KEY `idx_buyer_id` (`buyer_id`),
      KEY `idx_category_id` (`category_id`),
      KEY `idx_is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
    'SELECT 1'
);
PREPARE stmt FROM @create_table_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add missing columns if table exists but columns are missing
SET @has_created_at = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'buyer_category_mapping' 
    AND COLUMN_NAME = 'created_at'
);

SET @has_updated_at = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'buyer_category_mapping' 
    AND COLUMN_NAME = 'updated_at'
);

-- Add created_at if missing
SET @add_created_at_sql = IF(
    @table_exists > 0 AND @has_created_at = 0,
    'ALTER TABLE `buyer_category_mapping` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'SELECT 1'
);
PREPARE stmt FROM @add_created_at_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add updated_at if missing
SET @add_updated_at_sql = IF(
    @table_exists > 0 AND @has_updated_at = 0,
    'ALTER TABLE `buyer_category_mapping` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT 1'
);
PREPARE stmt FROM @add_updated_at_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraints separately (only if referenced tables exist)
-- Check if users table exists and has id column
SET @users_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users'
);

SET @users_id_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'id'
);

-- Check if categories table exists and has id column
SET @categories_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'categories'
);

SET @categories_id_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'categories' 
    AND COLUMN_NAME = 'id'
);

-- Add foreign key for buyer_id if users table exists and constraint doesn't exist
SET @fk_buyer_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'buyer_category_mapping' 
    AND CONSTRAINT_NAME = 'fk_bcm_buyer'
);

SET @fk_buyer_sql = IF(
    @table_exists > 0 AND @users_exists > 0 AND @users_id_exists > 0 AND @fk_buyer_exists = 0,
    'ALTER TABLE `buyer_category_mapping` 
     ADD CONSTRAINT `fk_bcm_buyer` 
     FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`) 
     ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_buyer_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key for category_id if categories table exists and constraint doesn't exist
SET @fk_category_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'buyer_category_mapping' 
    AND CONSTRAINT_NAME = 'fk_bcm_category'
);

SET @fk_category_sql = IF(
    @table_exists > 0 AND @categories_exists > 0 AND @categories_id_exists > 0 AND @fk_category_exists = 0,
    'ALTER TABLE `buyer_category_mapping` 
     ADD CONSTRAINT `fk_bcm_category` 
     FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) 
     ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @fk_category_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes if they don't exist (for existing tables)
SET @idx_buyer_id_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'buyer_category_mapping' 
    AND INDEX_NAME = 'idx_buyer_id'
);

SET @idx_category_id_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'buyer_category_mapping' 
    AND INDEX_NAME = 'idx_category_id'
);

SET @idx_is_active_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'buyer_category_mapping' 
    AND INDEX_NAME = 'idx_is_active'
);

SET @unique_buyer_category_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'buyer_category_mapping' 
    AND INDEX_NAME = 'unique_buyer_category'
);

-- Add missing indexes
SET @add_idx_buyer_id_sql = IF(
    @table_exists > 0 AND @idx_buyer_id_exists = 0,
    'ALTER TABLE `buyer_category_mapping` ADD INDEX `idx_buyer_id` (`buyer_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @add_idx_buyer_id_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_idx_category_id_sql = IF(
    @table_exists > 0 AND @idx_category_id_exists = 0,
    'ALTER TABLE `buyer_category_mapping` ADD INDEX `idx_category_id` (`category_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @add_idx_category_id_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_idx_is_active_sql = IF(
    @table_exists > 0 AND @idx_is_active_exists = 0,
    'ALTER TABLE `buyer_category_mapping` ADD INDEX `idx_is_active` (`is_active`)',
    'SELECT 1'
);
PREPARE stmt FROM @add_idx_is_active_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_unique_buyer_category_sql = IF(
    @table_exists > 0 AND @unique_buyer_category_exists = 0,
    'ALTER TABLE `buyer_category_mapping` ADD UNIQUE KEY `unique_buyer_category` (`buyer_id`, `category_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @add_unique_buyer_category_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Migration Complete!
-- ============================================
-- This migration is idempotent and can be run multiple times safely.
-- It will:
-- 1. Create the table if it doesn't exist
-- 2. Add missing columns if table exists but columns are missing
-- 3. Add foreign keys only if referenced tables exist and constraints don't exist
-- 4. Add indexes only if they don't exist
-- ============================================


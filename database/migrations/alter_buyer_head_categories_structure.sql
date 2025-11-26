-- ============================================
-- Alter buyer_head_categories table structure
-- ============================================
-- This migration:
-- 1. Adds cat_id column (INT, foreign key to categories.id)
-- 2. Migrates existing data from cat (name) to cat_id (ID)
-- 3. Removes Name column
-- 4. Removes cat column
-- 5. Adds foreign key constraint
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Step 1: Add cat_id column
ALTER TABLE `buyer_head_categories` 
ADD COLUMN `cat_id` INT(11) NULL AFTER `user_id`;

-- Step 2: Migrate existing data from cat (name) to cat_id (ID)
UPDATE `buyer_head_categories` bhc
INNER JOIN `categories` c ON c.maincat = bhc.cat
SET bhc.cat_id = c.id
WHERE bhc.cat IS NOT NULL AND bhc.cat != '';

-- Step 3: Make cat_id NOT NULL after migration
ALTER TABLE `buyer_head_categories` 
MODIFY COLUMN `cat_id` INT(11) NOT NULL;

-- Step 4: Remove Name column
ALTER TABLE `buyer_head_categories` 
DROP COLUMN `Name`;

-- Step 5: Remove cat column
ALTER TABLE `buyer_head_categories` 
DROP COLUMN `cat`;

-- Step 6: Add foreign key constraint
ALTER TABLE `buyer_head_categories`
ADD CONSTRAINT `fk_bhc_category` 
FOREIGN KEY (`cat_id`) REFERENCES `categories`(`id`) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Step 7: Add index on cat_id for performance
ALTER TABLE `buyer_head_categories`
ADD INDEX `idx_cat_id` (`cat_id`);

-- Step 8: Add unique constraint on user_id and cat_id to prevent duplicates
ALTER TABLE `buyer_head_categories`
ADD UNIQUE KEY `unique_user_category` (`user_id`, `cat_id`);

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Migration Complete!
-- ============================================
-- The table structure has been updated:
-- - Removed: Name column, cat column
-- - Added: cat_id column (INT, foreign key to categories.id)
-- - Added: Foreign key constraint and indexes
-- Please update all code references to use cat_id instead of cat
-- ============================================



-- ============================================
-- Rename catbasbh table to buyer_head_categories
-- ============================================
-- This migration renames the catbasbh table to buyer_head_categories
-- for better naming consistency and clarity
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Rename catbasbh table to buyer_head_categories
SET @rename_sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'catbasbh') > 0,
    'RENAME TABLE `catbasbh` TO `buyer_head_categories`',
    'SELECT 1'
);
PREPARE stmt FROM @rename_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Migration Complete!
-- ============================================
-- The table catbasbh has been renamed to buyer_head_categories
-- Please update all code references from catbasbh to buyer_head_categories
-- ============================================


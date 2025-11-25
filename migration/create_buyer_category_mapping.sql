-- ============================================
-- Create Buyer Category Mapping Table
-- ============================================
-- This table maps buyers directly to categories
-- ============================================

CREATE TABLE IF NOT EXISTS `buyer_category_mapping` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` INT(11) NOT NULL COMMENT 'User ID of the buyer',
  `category_id` INT(11) NOT NULL COMMENT 'Category ID',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Whether this mapping is active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_buyer_category` (`buyer_id`, `category_id`),
  KEY `idx_buyer_id` (`buyer_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_bcm_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bcm_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


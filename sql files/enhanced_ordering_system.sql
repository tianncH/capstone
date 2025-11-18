-- Enhanced Ordering System Database Schema
-- This file contains all the tables needed for the QR code-based ordering system

-- Tables for restaurant management
CREATE TABLE `tables` (
  `table_id` int(11) NOT NULL AUTO_INCREMENT,
  `table_number` varchar(10) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 4,
  `location` varchar(100) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`table_id`),
  UNIQUE KEY `table_number` (`table_number`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Enhanced orders table for table-specific orders
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `table_id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `order_type` enum('dine_in','takeout','delivery') NOT NULL DEFAULT 'dine_in',
  `status` enum('pending','confirmed','preparing','ready','served','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','partial','refunded') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','card','digital_wallet','split') DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `special_instructions` text DEFAULT NULL,
  `is_billed_out` tinyint(1) NOT NULL DEFAULT 0,
  `billed_out_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `table_id` (`table_id`),
  KEY `status` (`status`),
  KEY `payment_status` (`payment_status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`table_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Order items table
CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `special_instructions` text DEFAULT NULL,
  `status` enum('pending','preparing','ready','served') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `menu_item_id` (`menu_item_id`),
  KEY `status` (`status`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`menu_item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Order add-ons table
CREATE TABLE `order_addons` (
  `order_addon_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_item_id` int(11) NOT NULL,
  `addon_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`order_addon_id`),
  KEY `order_item_id` (`order_item_id`),
  KEY `addon_id` (`addon_id`),
  CONSTRAINT `order_addons_ibfk_1` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`order_item_id`) ON DELETE CASCADE,
  CONSTRAINT `order_addons_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `addons` (`addon_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Payment transactions table
CREATE TABLE `payment_transactions` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `transaction_type` enum('payment','refund','partial_payment') NOT NULL DEFAULT 'payment',
  `payment_method` enum('cash','card','digital_wallet','bank_transfer') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `gateway_response` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  KEY `order_id` (`order_id`),
  KEY `status` (`status`),
  KEY `processed_by` (`processed_by`),
  CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `payment_transactions_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `admin_users` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Split payments table for group dining
CREATE TABLE `split_payments` (
  `split_payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `split_number` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','digital_wallet') DEFAULT NULL,
  `status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`split_payment_id`),
  KEY `order_id` (`order_id`),
  KEY `status` (`status`),
  CONSTRAINT `split_payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Order notifications table
CREATE TABLE `order_notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `notification_type` enum('order_placed','order_confirmed','order_preparing','order_ready','order_served','payment_received','bill_out') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `order_id` (`order_id`),
  KEY `notification_type` (`notification_type`),
  KEY `is_read` (`is_read`),
  CONSTRAINT `order_notifications_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Customer preferences table for personalized experience
CREATE TABLE `customer_preferences` (
  `preference_id` int(11) NOT NULL AUTO_INCREMENT,
  `table_id` int(11) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `favorite_items` text DEFAULT NULL COMMENT 'JSON array of favorite menu items',
  `dietary_restrictions` text DEFAULT NULL COMMENT 'JSON array of dietary restrictions',
  `order_frequency` int(11) NOT NULL DEFAULT 0,
  `last_visit` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`preference_id`),
  KEY `table_id` (`table_id`),
  KEY `customer_phone` (`customer_phone`),
  CONSTRAINT `customer_preferences_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`table_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample tables
INSERT INTO `tables` (`table_number`, `table_name`, `capacity`, `location`, `is_active`) VALUES
('T001', 'Table 1', 4, 'Main Dining Area', 1),
('T002', 'Table 2', 2, 'Main Dining Area', 1),
('T003', 'Table 3', 6, 'Main Dining Area', 1),
('T004', 'Table 4', 4, 'Window Side', 1),
('T005', 'Table 5', 8, 'Private Section', 1),
('T006', 'Table 6', 2, 'Bar Area', 1),
('T007', 'Table 7', 4, 'Outdoor Patio', 1),
('T008', 'Table 8', 6, 'Outdoor Patio', 1);

-- Update existing orders table if it exists (add new columns)
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `table_id` int(11) DEFAULT NULL AFTER `order_id`,
ADD COLUMN IF NOT EXISTS `order_number` varchar(20) DEFAULT NULL AFTER `table_id`,
ADD COLUMN IF NOT EXISTS `customer_name` varchar(100) DEFAULT NULL AFTER `order_number`,
ADD COLUMN IF NOT EXISTS `customer_phone` varchar(20) DEFAULT NULL AFTER `customer_name`,
ADD COLUMN IF NOT EXISTS `order_type` enum('dine_in','takeout','delivery') NOT NULL DEFAULT 'dine_in' AFTER `customer_phone`,
ADD COLUMN IF NOT EXISTS `payment_method` enum('cash','card','digital_wallet','split') DEFAULT NULL AFTER `payment_status`,
ADD COLUMN IF NOT EXISTS `is_billed_out` tinyint(1) NOT NULL DEFAULT 0 AFTER `special_instructions`,
ADD COLUMN IF NOT EXISTS `billed_out_at` timestamp NULL DEFAULT NULL AFTER `is_billed_out`;

-- Add foreign key constraint for table_id if it doesn't exist
-- ALTER TABLE `orders` ADD CONSTRAINT `orders_ibfk_table` FOREIGN KEY (`table_id`) REFERENCES `tables` (`table_id`) ON DELETE SET NULL;

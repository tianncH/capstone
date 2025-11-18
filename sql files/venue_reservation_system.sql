-- Venue Reservation System Database Schema
-- This file contains all the tables needed for the venue reservation system

-- Venues table - stores venue spaces
CREATE TABLE `venues` (
  `venue_id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `max_capacity` int(11) NOT NULL DEFAULT 1,
  `min_party_size` int(11) NOT NULL DEFAULT 1,
  `opening_time` time NOT NULL DEFAULT '10:00:00',
  `closing_time` time NOT NULL DEFAULT '22:00:00',
  `time_slot_interval` int(11) NOT NULL DEFAULT 30 COMMENT 'Time slot interval in minutes',
  `buffer_time` int(11) NOT NULL DEFAULT 15 COMMENT 'Buffer time between reservations in minutes',
  `special_requirements` text DEFAULT NULL,
  `features` text DEFAULT NULL COMMENT 'JSON array of features like outdoor, private, etc.',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`venue_id`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Reservations table - stores all reservations
CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `reservation_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `party_size` int(11) NOT NULL DEFAULT 1,
  `reservation_type` enum('party','business','couple','family','event','other') NOT NULL DEFAULT 'other',
  `special_requests` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed','no_show') NOT NULL DEFAULT 'pending',
  `confirmation_code` varchar(20) DEFAULT NULL,
  `deposit_amount` decimal(10,2) DEFAULT NULL,
  `deposit_paid` tinyint(1) NOT NULL DEFAULT 0,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('pending','partial','paid','refunded') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'Admin who created the reservation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`reservation_id`),
  KEY `venue_id` (`venue_id`),
  KEY `reservation_date` (`reservation_date`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`),
  KEY `confirmation_code` (`confirmation_code`),
  CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`) ON DELETE CASCADE,
  CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Venue restrictions table - stores blackout dates and restrictions
CREATE TABLE `venue_restrictions` (
  `restriction_id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_id` int(11) NOT NULL,
  `restriction_type` enum('blackout','maintenance','special_event','holiday','custom') NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `recurring` enum('none','daily','weekly','monthly','yearly') NOT NULL DEFAULT 'none',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`restriction_id`),
  KEY `venue_id` (`venue_id`),
  KEY `start_date` (`start_date`),
  KEY `restriction_type` (`restriction_type`),
  CONSTRAINT `venue_restrictions_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Waitlist table - stores customers waiting for available slots
CREATE TABLE `reservation_waitlist` (
  `waitlist_id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `preferred_date` date NOT NULL,
  `preferred_start_time` time NOT NULL,
  `preferred_end_time` time NOT NULL,
  `party_size` int(11) NOT NULL DEFAULT 1,
  `special_requests` text DEFAULT NULL,
  `status` enum('active','notified','booked','cancelled') NOT NULL DEFAULT 'active',
  `priority` int(11) NOT NULL DEFAULT 0 COMMENT 'Higher number = higher priority',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`waitlist_id`),
  KEY `venue_id` (`venue_id`),
  KEY `preferred_date` (`preferred_date`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  CONSTRAINT `reservation_waitlist_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Communication logs table - stores all communication with customers
CREATE TABLE `reservation_communications` (
  `communication_id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) DEFAULT NULL,
  `waitlist_id` int(11) DEFAULT NULL,
  `communication_type` enum('email','sms','phone','in_person') NOT NULL,
  `direction` enum('inbound','outbound') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`communication_id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `waitlist_id` (`waitlist_id`),
  KEY `admin_id` (`admin_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `reservation_communications_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE,
  CONSTRAINT `reservation_communications_ibfk_2` FOREIGN KEY (`waitlist_id`) REFERENCES `reservation_waitlist` (`waitlist_id`) ON DELETE CASCADE,
  CONSTRAINT `reservation_communications_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample venues
INSERT INTO `venues` (`venue_name`, `description`, `max_capacity`, `min_party_size`, `opening_time`, `closing_time`, `time_slot_interval`, `buffer_time`, `special_requirements`, `features`) VALUES
('Main Dining Room', 'Our elegant main dining area with comfortable seating and ambient lighting', 50, 2, '10:00:00', '22:00:00', 30, 15, 'Standard dining setup', '["indoor", "air_conditioned", "wifi"]'),
('Outdoor Patio', 'Beautiful outdoor seating with garden views and fresh air', 30, 2, '10:00:00', '21:00:00', 30, 15, 'Weather dependent - may need to move indoors', '["outdoor", "garden_view", "covered"]'),
('Private Room', 'Intimate private dining room perfect for special occasions', 20, 4, '10:00:00', '22:00:00', 60, 30, 'Requires advance booking and special menu setup', '["private", "sound_system", "projector"]'),
('Bar Area', 'Casual bar seating with high-top tables and bar stools', 25, 1, '11:00:00', '23:00:00', 30, 15, 'Bar service available', '["bar_seating", "tv", "casual"]');

-- Insert sample restrictions (holidays)
INSERT INTO `venue_restrictions` (`venue_id`, `restriction_type`, `title`, `description`, `start_date`, `end_date`, `recurring`) VALUES
(1, 'holiday', 'New Year\'s Day', 'Restaurant closed for New Year\'s Day', '2024-01-01', '2024-01-01', 'yearly'),
(2, 'holiday', 'New Year\'s Day', 'Restaurant closed for New Year\'s Day', '2024-01-01', '2024-01-01', 'yearly'),
(3, 'holiday', 'New Year\'s Day', 'Restaurant closed for New Year\'s Day', '2024-01-01', '2024-01-01', 'yearly'),
(4, 'holiday', 'New Year\'s Day', 'Restaurant closed for New Year\'s Day', '2024-01-01', '2024-01-01', 'yearly');

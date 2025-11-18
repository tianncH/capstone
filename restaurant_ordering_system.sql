-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 05, 2025 at 11:51 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `restaurant_ordering_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','manager','staff') NOT NULL DEFAULT 'staff',
  `last_login` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`admin_id`, `username`, `password`, `full_name`, `email`, `role`, `last_login`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$yvzpO3fMdZ5ge.SuiAlDXed2Uj3CVlnrqWVzwnujxnSge7c8k/Xu6', 'System Administrator', 'admin@example.com', 'admin', '2025-10-05 16:06:00', 1, '2025-05-17 01:34:54', '2025-10-05 08:06:00');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_sales`
--

CREATE TABLE `daily_sales` (
  `daily_sales_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `total_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `table_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `food_quality_rating` int(1) NOT NULL,
  `food_quality_comments` text DEFAULT NULL,
  `service_quality_rating` int(1) NOT NULL,
  `service_quality_comments` text DEFAULT NULL,
  `venue_quality_rating` int(1) NOT NULL,
  `venue_quality_comments` text DEFAULT NULL,
  `reservation_experience` enum('not_applicable','did_not_use','used_system') NOT NULL DEFAULT 'not_applicable',
  `reservation_comments` text DEFAULT NULL,
  `overall_rating` decimal(2,1) GENERATED ALWAYS AS ((`food_quality_rating` + `service_quality_rating` + `venue_quality_rating`) / 3) STORED,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `admin_notes` text DEFAULT NULL,
  `status` enum('pending','reviewed','responded','archived') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `order_id`, `table_id`, `customer_name`, `customer_email`, `customer_phone`, `food_quality_rating`, `food_quality_comments`, `service_quality_rating`, `service_quality_comments`, `venue_quality_rating`, `venue_quality_comments`, `reservation_experience`, `reservation_comments`, `is_anonymous`, `is_public`, `admin_notes`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 'Ivan', 'icbilbao.chmsu@gnail.com', '09660653087', 5, 'asd', 5, 'asd', 5, 'asd', 'not_applicable', NULL, 0, 1, NULL, 'pending', '2025-10-05 08:45:39', '2025-10-05 08:45:39');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_analytics`
--

CREATE TABLE `feedback_analytics` (
  `analytics_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `total_feedback` int(11) NOT NULL DEFAULT 0,
  `avg_food_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `avg_service_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `avg_venue_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `avg_overall_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `positive_feedback_count` int(11) NOT NULL DEFAULT 0,
  `negative_feedback_count` int(11) NOT NULL DEFAULT 0,
  `reservation_usage_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_categories`
--

CREATE TABLE `feedback_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_categories`
--

INSERT INTO `feedback_categories` (`category_id`, `name`, `description`, `is_active`, `display_order`) VALUES
(1, 'Food Quality', 'Feedback related to food taste, presentation, and temperature', 1, 1),
(2, 'Service Quality', 'Feedback related to staff service, friendliness, and efficiency', 1, 2),
(3, 'Venue Quality', 'Feedback related to ambiance, cleanliness, and environment', 1, 3),
(4, 'Reservation Experience', 'Feedback related to reservation process and system', 1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `feedback_responses`
--

CREATE TABLE `feedback_responses` (
  `response_id` int(11) NOT NULL,
  `feedback_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `response_text` text NOT NULL,
  `response_type` enum('acknowledgment','follow_up','resolution') NOT NULL DEFAULT 'acknowledgment',
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_addons`
--

CREATE TABLE `item_addons` (
  `addon_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_options`
--

CREATE TABLE `item_options` (
  `option_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_popularity`
--

CREATE TABLE `item_popularity` (
  `popularity_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `order_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_variations`
--

CREATE TABLE `item_variations` (
  `variation_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price_adjustment` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `item_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` text DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monthly_sales`
--

CREATE TABLE `monthly_sales` (
  `monthly_sales_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `total_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `queue_number` varchar(20) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `status_id` int(11) NOT NULL DEFAULT 1,
  `total_amount` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `variation_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_item_addons`
--

CREATE TABLE `order_item_addons` (
  `order_item_addon_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `addon_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_statuses`
--

CREATE TABLE `order_statuses` (
  `status_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_statuses`
--

INSERT INTO `order_statuses` (`status_id`, `name`, `description`) VALUES
(1, 'pending', 'Order placed but not paid'),
(2, 'paid', 'Order paid and waiting to be prepared'),
(3, 'preparing', 'Order is being prepared in the kitchen'),
(4, 'ready', 'Order is ready for pickup'),
(5, 'completed', 'Order has been picked up and completed'),
(6, 'cancelled', 'Order has been cancelled');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `history_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `venue_id`, `customer_name`, `customer_email`, `customer_phone`, `reservation_date`, `start_time`, `end_time`, `party_size`, `reservation_type`, `special_requests`, `status`, `confirmation_code`, `deposit_amount`, `deposit_paid`, `total_amount`, `payment_status`, `admin_notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 4, 'Christian Louise Hondrade', 'test@gmail.com', '09208902312', '2025-10-05', '11:30:00', '00:00:12', 25, 'party', 'sad', 'confirmed', '05FAAC48', NULL, 0, NULL, 'pending', NULL, NULL, '2025-10-05 09:23:14', '2025-10-05 09:36:19');

-- --------------------------------------------------------

--
-- Table structure for table `reservation_communications`
--

CREATE TABLE `reservation_communications` (
  `communication_id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `waitlist_id` int(11) DEFAULT NULL,
  `communication_type` enum('email','sms','phone','in_person') NOT NULL,
  `direction` enum('inbound','outbound') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_waitlist`
--

CREATE TABLE `reservation_waitlist` (
  `waitlist_id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `table_id` int(11) NOT NULL,
  `table_number` varchar(10) NOT NULL,
  `qr_code_url` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `venues`
--

CREATE TABLE `venues` (
  `venue_id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venues`
--

INSERT INTO `venues` (`venue_id`, `venue_name`, `description`, `max_capacity`, `min_party_size`, `opening_time`, `closing_time`, `time_slot_interval`, `buffer_time`, `special_requirements`, `features`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Main Dining Room', 'Our elegant main dining area with comfortable seating and ambient lighting', 50, 2, '10:00:00', '22:00:00', 30, 15, 'Standard dining setup', '[\"indoor\", \"air_conditioned\", \"wifi\"]', 1, '2025-10-05 09:08:25', '2025-10-05 09:08:25'),
(2, 'Outdoor Patio', 'Beautiful outdoor seating with garden views and fresh air', 30, 2, '10:00:00', '21:00:00', 30, 15, 'Weather dependent - may need to move indoors', '[\"outdoor\", \"garden_view\", \"covered\"]', 1, '2025-10-05 09:08:25', '2025-10-05 09:08:25'),
(3, 'Private Room', 'Intimate private dining room perfect for special occasions', 20, 4, '10:00:00', '22:00:00', 60, 30, 'Requires advance booking and special menu setup', '[\"private\", \"sound_system\", \"projector\"]', 1, '2025-10-05 09:08:25', '2025-10-05 09:08:25'),
(4, 'Bar Area', 'Casual bar seating with high-top tables and bar stools', 25, 1, '11:00:00', '23:00:00', 30, 15, 'Bar service available', '[\"bar_seating\", \"tv\", \"casual\"]', 1, '2025-10-05 09:08:25', '2025-10-05 09:08:25');

-- --------------------------------------------------------

--
-- Table structure for table `venue_restrictions`
--

CREATE TABLE `venue_restrictions` (
  `restriction_id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venue_restrictions`
--

INSERT INTO `venue_restrictions` (`restriction_id`, `venue_id`, `restriction_type`, `title`, `description`, `start_date`, `end_date`, `start_time`, `end_time`, `recurring`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'holiday', 'New Year\'s Day', 'Restaurant closed for New Year\'s Day', '2024-01-01', '2024-01-01', NULL, NULL, 'yearly', 1, '2025-10-05 09:08:25', '2025-10-05 09:08:25'),
(2, 2, 'holiday', 'New Year\'s Day', 'Restaurant closed for New Year\'s Day', '2024-01-01', '2024-01-01', NULL, NULL, 'yearly', 1, '2025-10-05 09:08:25', '2025-10-05 09:08:25'),
(3, 3, 'holiday', 'New Year\'s Day', 'Restaurant closed for New Year\'s Day', '2024-01-01', '2024-01-01', NULL, NULL, 'yearly', 1, '2025-10-05 09:08:25', '2025-10-05 09:08:25'),
(4, 4, 'holiday', 'New Year\'s Day', 'Restaurant closed for New Year\'s Day', '2024-01-01', '2024-01-01', NULL, NULL, 'yearly', 1, '2025-10-05 09:08:25', '2025-10-05 09:08:25');

-- --------------------------------------------------------

--
-- Table structure for table `yearly_sales`
--

CREATE TABLE `yearly_sales` (
  `yearly_sales_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `total_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `daily_sales`
--
ALTER TABLE `daily_sales`
  ADD PRIMARY KEY (`daily_sales_id`),
  ADD UNIQUE KEY `date` (`date`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `table_id` (`table_id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `overall_rating` (`overall_rating`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `feedback_analytics`
--
ALTER TABLE `feedback_analytics`
  ADD PRIMARY KEY (`analytics_id`),
  ADD UNIQUE KEY `date` (`date`);

--
-- Indexes for table `feedback_categories`
--
ALTER TABLE `feedback_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `feedback_responses`
--
ALTER TABLE `feedback_responses`
  ADD PRIMARY KEY (`response_id`),
  ADD KEY `feedback_id` (`feedback_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `item_addons`
--
ALTER TABLE `item_addons`
  ADD PRIMARY KEY (`addon_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `item_options`
--
ALTER TABLE `item_options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `item_popularity`
--
ALTER TABLE `item_popularity`
  ADD PRIMARY KEY (`popularity_id`),
  ADD UNIQUE KEY `item_date` (`item_id`,`date`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `item_variations`
--
ALTER TABLE `item_variations`
  ADD PRIMARY KEY (`variation_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `monthly_sales`
--
ALTER TABLE `monthly_sales`
  ADD PRIMARY KEY (`monthly_sales_id`),
  ADD UNIQUE KEY `year_month` (`year`,`month`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `queue_number` (`queue_number`),
  ADD KEY `table_id` (`table_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `variation_id` (`variation_id`);

--
-- Indexes for table `order_item_addons`
--
ALTER TABLE `order_item_addons`
  ADD PRIMARY KEY (`order_item_addon_id`),
  ADD KEY `order_item_id` (`order_item_id`),
  ADD KEY `addon_id` (`addon_id`);

--
-- Indexes for table `order_statuses`
--
ALTER TABLE `order_statuses`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `status_id` (`status_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `venue_id` (`venue_id`),
  ADD KEY `reservation_date` (`reservation_date`),
  ADD KEY `status` (`status`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `confirmation_code` (`confirmation_code`);

--
-- Indexes for table `reservation_communications`
--
ALTER TABLE `reservation_communications`
  ADD PRIMARY KEY (`communication_id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `waitlist_id` (`waitlist_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `reservation_waitlist`
--
ALTER TABLE `reservation_waitlist`
  ADD PRIMARY KEY (`waitlist_id`),
  ADD KEY `venue_id` (`venue_id`),
  ADD KEY `preferred_date` (`preferred_date`),
  ADD KEY `status` (`status`),
  ADD KEY `priority` (`priority`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`table_id`),
  ADD UNIQUE KEY `table_number` (`table_number`);

--
-- Indexes for table `venues`
--
ALTER TABLE `venues`
  ADD PRIMARY KEY (`venue_id`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `venue_restrictions`
--
ALTER TABLE `venue_restrictions`
  ADD PRIMARY KEY (`restriction_id`),
  ADD KEY `venue_id` (`venue_id`),
  ADD KEY `start_date` (`start_date`),
  ADD KEY `restriction_type` (`restriction_type`);

--
-- Indexes for table `yearly_sales`
--
ALTER TABLE `yearly_sales`
  ADD PRIMARY KEY (`yearly_sales_id`),
  ADD UNIQUE KEY `year` (`year`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_sales`
--
ALTER TABLE `daily_sales`
  MODIFY `daily_sales_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback_analytics`
--
ALTER TABLE `feedback_analytics`
  MODIFY `analytics_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback_categories`
--
ALTER TABLE `feedback_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `feedback_responses`
--
ALTER TABLE `feedback_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_addons`
--
ALTER TABLE `item_addons`
  MODIFY `addon_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_options`
--
ALTER TABLE `item_options`
  MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_popularity`
--
ALTER TABLE `item_popularity`
  MODIFY `popularity_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_variations`
--
ALTER TABLE `item_variations`
  MODIFY `variation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monthly_sales`
--
ALTER TABLE `monthly_sales`
  MODIFY `monthly_sales_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_item_addons`
--
ALTER TABLE `order_item_addons`
  MODIFY `order_item_addon_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_statuses`
--
ALTER TABLE `order_statuses`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reservation_communications`
--
ALTER TABLE `reservation_communications`
  MODIFY `communication_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservation_waitlist`
--
ALTER TABLE `reservation_waitlist`
  MODIFY `waitlist_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `table_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `venues`
--
ALTER TABLE `venues`
  MODIFY `venue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `venue_restrictions`
--
ALTER TABLE `venue_restrictions`
  MODIFY `restriction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `yearly_sales`
--
ALTER TABLE `yearly_sales`
  MODIFY `yearly_sales_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `item_addons`
--
ALTER TABLE `item_addons`
  ADD CONSTRAINT `item_addons_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `item_options`
--
ALTER TABLE `item_options`
  ADD CONSTRAINT `item_options_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `item_popularity`
--
ALTER TABLE `item_popularity`
  ADD CONSTRAINT `item_popularity_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `item_variations`
--
ALTER TABLE `item_variations`
  ADD CONSTRAINT `item_variations_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`table_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `order_statuses` (`status_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`item_id`),
  ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`variation_id`) REFERENCES `item_variations` (`variation_id`);

--
-- Constraints for table `order_item_addons`
--
ALTER TABLE `order_item_addons`
  ADD CONSTRAINT `order_item_addons_ibfk_1` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`order_item_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_item_addons_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `item_addons` (`addon_id`);

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `order_statuses` (`status_id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `reservation_communications`
--
ALTER TABLE `reservation_communications`
  ADD CONSTRAINT `reservation_communications_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservation_communications_ibfk_2` FOREIGN KEY (`waitlist_id`) REFERENCES `reservation_waitlist` (`waitlist_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservation_communications_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `reservation_waitlist`
--
ALTER TABLE `reservation_waitlist`
  ADD CONSTRAINT `reservation_waitlist_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`) ON DELETE CASCADE;

--
-- Constraints for table `venue_restrictions`
--
ALTER TABLE `venue_restrictions`
  ADD CONSTRAINT `venue_restrictions_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

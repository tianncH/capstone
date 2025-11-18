-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2025 at 02:45 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

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
(1, 'admin', '$2y$10$FYHvV2aFfmkxwmeAV/nT7uUTaSX5nSmb4k00Lp93gYiN73D3.IJmK', 'System Administrator', 'admin@example.com', 'admin', '2025-05-25 11:38:55', 1, '2025-05-17 01:34:54', '2025-05-25 03:38:55');

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

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `display_order`, `is_active`) VALUES
(1, 'Silog Meals', 'Classic Filipino breakfast meals with garlic rice and a fried egg.', 0, 1),
(2, 'Regular Meals', 'Hearty rice meals perfect for any time of day.', 1, 1),
(3, 'Silog Meals', 'Tasty rice-based options and side add-ons.', 2, 1),
(4, 'Extras', 'Simple additions to enhance your meal.', 3, 1),
(5, 'Snacks', 'Light meals and bites for quick cravings.', 4, 1),
(6, 'Shakes & Drinks', 'Cold and sweet refreshments to beat the heat.', 5, 1),
(7, 'Coffee & Choco', 'Hot or iced beverages for caffeine and chocolate lovers.', 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `counter_users`
--

CREATE TABLE `counter_users` (
  `counter_user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `counter_users`
--

INSERT INTO `counter_users` (`counter_user_id`, `username`, `password`, `full_name`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'counter', '$2y$10$xE4B5pHpakOSfvmUqsULPuHjDw4yHNWM3He1OUlnhf6c4BL7yJrEi', 'Counter Staff', 1, '2025-05-25 11:41:43', '2025-05-22 05:05:41');

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

--
-- Dumping data for table `daily_sales`
--

INSERT INTO `daily_sales` (`daily_sales_id`, `date`, `total_orders`, `total_sales`, `created_at`, `updated_at`) VALUES
(1, '2025-05-21', 3, 615.00, '2025-05-21 09:49:42', '2025-05-21 09:50:27'),
(2, '2025-05-22', 2, 246.00, '2025-05-22 05:13:26', '2025-05-22 05:13:32'),
(3, '2025-05-24', 1, 123.00, '2025-05-24 12:11:44', '2025-05-24 12:11:44'),
(4, '2025-05-25', 10, 1230.00, '2025-05-25 03:14:50', '2025-05-25 03:37:16'),
(5, '2024-08-16', 3, 160.00, '2025-05-25 09:07:32', '2025-05-25 09:07:32'),
(6, '2024-08-17', 3, 150.00, '2025-05-25 09:07:32', '2025-05-25 09:07:32'),
(7, '2024-08-18', 4, 210.00, '2025-05-25 09:07:32', '2025-05-25 09:07:32'),
(8, '2024-01-01', 3, 70.00, '2024-01-01 00:03:00', '2025-05-25 09:37:30'),
(9, '2024-01-19', 1, 40.00, '2024-01-19 12:33:00', '2025-05-25 09:37:30'),
(10, '2024-02-09', 1, 60.00, '2024-02-09 04:34:00', '2025-05-25 09:37:30'),
(11, '2024-02-10', 1, 80.00, '2024-02-10 10:34:00', '2025-05-25 09:37:30'),
(12, '2024-02-27', 1, 15.00, '2024-02-27 09:39:00', '2025-05-25 09:37:30'),
(13, '2024-02-28', 1, 40.00, '2024-02-28 05:49:00', '2025-05-25 09:37:30'),
(14, '2024-03-15', 1, 90.00, '2024-03-15 10:42:00', '2025-05-25 09:37:30'),
(15, '2024-03-31', 1, 40.00, '2024-03-31 06:52:00', '2025-05-25 09:37:30'),
(16, '2024-05-04', 1, 70.00, '2024-05-04 11:49:00', '2025-05-25 09:37:30'),
(17, '2024-05-08', 1, 140.00, '2024-05-08 00:40:00', '2025-05-25 09:37:30'),
(18, '2024-05-15', 1, 70.00, '2024-05-15 12:23:00', '2025-05-25 09:37:30'),
(19, '2024-05-21', 1, 100.00, '2024-05-21 11:48:00', '2025-05-25 09:37:30'),
(20, '2024-05-26', 1, 195.00, '2024-05-26 04:37:00', '2025-05-25 09:37:30'),
(21, '2024-06-01', 1, 80.00, '2024-06-01 00:03:00', '2025-05-25 09:37:30'),
(22, '2024-06-03', 1, 40.00, '2024-06-03 07:16:00', '2025-05-25 09:37:30'),
(23, '2024-06-16', 1, 80.00, '2024-06-16 10:00:00', '2025-05-25 09:37:30'),
(24, '2024-07-01', 1, 20.00, '2024-07-01 01:25:00', '2025-05-25 09:37:30'),
(25, '2024-07-10', 1, 40.00, '2024-07-10 11:05:00', '2025-05-25 09:37:30'),
(26, '2024-07-11', 1, 70.00, '2024-07-11 04:35:00', '2025-05-25 09:37:30'),
(27, '2024-08-05', 1, 40.00, '2024-08-05 06:25:00', '2025-05-25 09:37:30'),
(28, '2024-08-08', 1, 40.00, '2024-08-08 11:46:00', '2025-05-25 09:37:30'),
(29, '2024-09-11', 1, 80.00, '2024-09-11 07:32:00', '2025-05-25 09:37:30'),
(30, '2024-09-16', 1, 75.00, '2024-09-16 10:35:00', '2025-05-25 09:37:30'),
(31, '2024-09-21', 1, 45.00, '2024-09-21 05:07:00', '2025-05-25 09:37:30'),
(32, '2024-10-25', 1, 40.00, '2024-10-25 12:43:00', '2025-05-25 09:37:30'),
(33, '2024-10-31', 1, 60.00, '2024-10-31 07:19:00', '2025-05-25 09:37:30'),
(34, '2024-11-13', 1, 40.00, '2024-11-13 05:30:00', '2025-05-25 09:37:30'),
(35, '2024-11-16', 1, 80.00, '2024-11-16 04:19:00', '2025-05-25 09:37:30'),
(36, '2024-11-19', 1, 50.00, '2024-11-19 05:28:00', '2025-05-25 09:37:30'),
(37, '2024-12-10', 1, 40.00, '2024-12-10 07:00:00', '2025-05-25 09:37:30'),
(38, '2024-12-22', 2, 70.00, '2024-12-22 03:35:00', '2025-05-25 09:37:30'),
(39, '2024-12-25', 1, 120.00, '2024-12-25 05:40:00', '2025-05-25 09:37:30'),
(40, '2024-12-27', 1, 65.00, '2024-12-27 06:11:00', '2025-05-25 09:37:30'),
(71, '2025-03-31', 1, 160.00, '2025-03-31 09:17:00', '2025-05-25 09:37:30');

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

--
-- Dumping data for table `item_popularity`
--

INSERT INTO `item_popularity` (`popularity_id`, `item_id`, `date`, `order_count`, `created_at`, `updated_at`) VALUES
(83, 47, '2024-05-15', 1, '2024-05-15 12:23:00', '2025-05-25 09:37:30'),
(84, 48, '2024-02-09', 3, '2024-02-09 04:34:00', '2025-05-25 09:37:30'),
(85, 48, '2024-07-01', 1, '2024-07-01 01:25:00', '2025-05-25 09:37:30'),
(86, 48, '2024-07-10', 2, '2024-07-10 11:05:00', '2025-05-25 09:37:30'),
(87, 48, '2024-12-22', 1, '2024-12-22 04:14:00', '2025-05-25 09:37:30'),
(88, 49, '2024-12-22', 2, '2024-12-22 03:35:00', '2025-05-25 09:37:30'),
(89, 50, '2024-05-26', 3, '2024-05-26 04:37:00', '2025-05-25 09:37:30'),
(90, 51, '2024-06-03', 1, '2024-06-03 07:16:00', '2025-05-25 09:37:30'),
(91, 51, '2024-12-10', 1, '2024-12-10 07:00:00', '2025-05-25 09:37:30'),
(92, 52, '2024-06-16', 2, '2024-06-16 10:00:00', '2025-05-25 09:37:30'),
(93, 53, '2024-01-01', 3, '2024-01-01 04:12:00', '2025-05-25 09:37:30'),
(94, 53, '2024-11-19', 5, '2024-11-19 05:28:00', '2025-05-25 09:37:30'),
(95, 54, '2024-09-21', 3, '2024-09-21 05:07:00', '2025-05-25 09:37:30'),
(96, 55, '2024-09-16', 1, '2024-09-16 10:35:00', '2025-05-25 09:37:30'),
(97, 56, '2024-03-15', 2, '2024-03-15 10:42:00', '2025-05-25 09:37:30'),
(98, 57, '2024-02-27', 1, '2024-02-27 09:39:00', '2025-05-25 09:37:30'),
(99, 58, '2024-01-01', 1, '2024-01-01 00:03:00', '2025-05-25 09:37:30'),
(100, 58, '2024-01-19', 1, '2024-01-19 12:33:00', '2025-05-25 09:37:30'),
(101, 58, '2024-06-01', 2, '2024-06-01 00:03:00', '2025-05-25 09:37:30'),
(102, 58, '2024-10-25', 1, '2024-10-25 12:43:00', '2025-05-25 09:37:30'),
(103, 58, '2024-12-25', 3, '2024-12-25 05:40:00', '2025-05-25 09:37:30'),
(104, 59, '2024-03-31', 1, '2024-03-31 06:52:00', '2025-05-25 09:37:30'),
(105, 60, '2024-09-11', 2, '2024-09-11 07:32:00', '2025-05-25 09:37:30'),
(106, 60, '2025-03-31', 4, '2025-03-31 09:17:00', '2025-05-25 09:37:30'),
(107, 61, '2024-08-05', 4, '2024-08-05 06:25:00', '2025-05-25 09:37:30'),
(108, 62, '2024-05-21', 1, '2024-05-21 11:48:00', '2025-05-25 09:37:30'),
(109, 63, '2024-02-28', 1, '2024-02-28 05:49:00', '2025-05-25 09:37:30'),
(110, 63, '2024-08-08', 1, '2024-08-08 11:46:00', '2025-05-25 09:37:30'),
(111, 63, '2024-11-16', 2, '2024-11-16 04:19:00', '2025-05-25 09:37:30'),
(112, 64, '2024-05-08', 2, '2024-05-08 00:40:00', '2025-05-25 09:37:30'),
(113, 64, '2024-07-11', 1, '2024-07-11 04:35:00', '2025-05-25 09:37:30'),
(114, 65, '2024-05-04', 1, '2024-05-04 11:49:00', '2025-05-25 09:37:30'),
(115, 66, '2024-10-31', 2, '2024-10-31 07:19:00', '2025-05-25 09:37:30'),
(116, 67, '2024-02-10', 2, '2024-02-10 10:34:00', '2025-05-25 09:37:30'),
(117, 68, '2024-12-27', 1, '2024-12-27 06:11:00', '2025-05-25 09:37:30'),
(118, 69, '2024-11-13', 1, '2024-11-13 05:30:00', '2025-05-25 09:37:30');

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

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`item_id`, `category_id`, `name`, `description`, `price`, `image_url`, `is_available`, `display_order`, `created_at`, `updated_at`) VALUES
(47, 2, 'Beefy Mushroom', 'Creamy beef and mushroom stew over rice.', 70.00, '/capstone/uploads/menu_items/1748172227_ecc27a9f6b1b53494ab82e52e30a8f9d.avif', 1, 3, '2024-05-15 12:23:00', '2025-05-25 11:23:47'),
(48, 7, 'Brewed Coffee', 'Straight-up black coffee for a quick boost.', 20.00, '/capstone/uploads/menu_items/1748175344_pexels-sikunovruslan-20444220.jpg', 1, 0, '2024-02-09 04:34:00', '2025-05-25 12:15:44'),
(49, 7, 'Café Latte', 'Smooth espresso with steamed milk.', 25.00, '/capstone/uploads/menu_items/1748175359_1000_F_75894966_grMyKtLrU2dDQzMNKxtkN9PfDxg5iQpE.jpg', 1, 1, '2024-12-22 03:35:00', '2025-05-25 12:15:59'),
(50, 5, 'Cheese Burger', 'Beef patty burger with melted cheese and toppings.', 65.00, '/capstone/uploads/menu_items/1748174551_pexels-griffinw-2657960.jpg', 1, 0, '2024-05-26 04:37:00', '2025-05-25 12:02:31'),
(51, 6, 'Chocolate Shake', 'Rich chocolate-flavored cold drink.', 40.00, '/capstone/uploads/menu_items/1748175089_ChocolateMilkshake-Featured-300x300.jpg', 1, 1, '2024-06-03 07:16:00', '2025-05-25 12:11:29'),
(52, 6, 'Cookies and Cream Shake', 'Creamy blend with crushed cookies.', 40.00, '/capstone/uploads/menu_items/1748175128_istockphoto-1388569821-612x612.jpg', 1, 3, '2024-06-16 10:00:00', '2025-05-25 12:12:08'),
(53, 3, 'Dynamite', 'Cheese-stuffed chili peppers wrapped in spring roll wrapper.', 10.00, '/capstone/uploads/menu_items/1748172692_Dynamit-Okra-scaled.jpg', 1, 3, '2024-01-01 04:12:00', '2025-05-25 11:31:32'),
(54, 4, 'Extra Egg', 'One fried egg, perfect as a topping.', 15.00, '/capstone/uploads/menu_items/1748173877_two-eggs-fried-plate_908985-51554.jpg', 1, 0, '2024-09-21 05:07:00', '2025-05-25 11:51:17'),
(55, 2, 'Hungarian', 'Spicy Hungarian sausage paired with rice.', 75.00, '/capstone/uploads/menu_items/1748172214_0002892_hungarian-sausages.jpeg', 1, 2, '2024-09-16 10:35:00', '2025-05-25 11:23:34'),
(56, 7, 'Iced Americano', 'Espresso served over ice with water.', 45.00, '/capstone/uploads/menu_items/1748175376_istockphoto-1392510439-612x612.jpg', 1, 2, '2024-03-15 10:42:00', '2025-05-25 12:16:16'),
(57, 4, 'Java Rice', 'Yellow rice with mild spices and savory seasoning.', 15.00, '/capstone/uploads/menu_items/1748173894_20533007_united_steak_extra_java_rice.webp', 1, 2, '2024-02-27 09:39:00', '2025-05-25 11:51:34'),
(58, 3, 'Kimchi Rice', 'Spicy Korean-style fermented cabbage fried with rice.', 40.00, '/capstone/uploads/menu_items/1748172659_istockphoto-174760556-612x612.jpg', 1, 0, '2024-01-01 00:03:00', '2025-05-25 11:30:59'),
(59, 6, 'Mais Con Yelo', 'Shaved ice dessert with corn and sweet milk.', 40.00, '/capstone/uploads/menu_items/1748175140_Mais-con-Yelo-Feature.jpg', 1, 4, '2024-03-31 06:52:00', '2025-05-25 12:12:20'),
(60, 2, 'Pares', 'Sweet soy-braised beef with rice and broth.', 40.00, '/capstone/uploads/menu_items/1748172251_Beef-Pares-1-scaled.jpeg', 1, 4, '2024-09-11 07:32:00', '2025-05-25 11:24:11'),
(61, 4, 'Plain Rice', 'Basic steamed white rice.', 10.00, '/capstone/uploads/menu_items/1748173885_Plain_Rice_Cup_503x503.webp', 1, 1, '2024-08-05 06:25:00', '2025-05-25 11:51:25'),
(62, 2, 'Pork Tonkatsu', 'Breaded and deep-fried pork cutlet with dipping sauce.', 100.00, '/capstone/uploads/menu_items/1748172198_ND-tonkatsu-hbtg-threeByTwoMediumAt2X.jpg', 1, 1, '2024-05-21 11:48:00', '2025-05-25 11:23:18'),
(63, 6, 'Rocky Road Shake', 'Chocolate shake with marshmallows and nuts.', 40.00, '/capstone/uploads/menu_items/1748175102_cookies-and-cream-milkshake-9-683x1024.jpg', 1, 2, '2024-02-28 05:49:00', '2025-05-25 12:11:42'),
(64, 1, 'Sisig', 'Chopped pork with spices and calamansi, served sizzling with egg.', 70.00, '/capstone/uploads/menu_items/1748170579_pexels-mark-john-hilario-264144764-30355484.jpg', 1, 0, '2024-05-08 00:40:00', '2025-05-25 10:56:19'),
(65, 1, 'Spam', 'Fried slices of savory Spam.', 70.00, '/capstone/uploads/menu_items/1748171830_Spamsilog-1024x1280.jpg', 1, 2, '2024-05-04 11:49:00', '2025-05-25 11:17:10'),
(66, 3, 'Spam / Half Hungarian', 'Your choice of Spam slices or half Hungarian sausage.', 30.00, '/capstone/uploads/menu_items/1748172680_a7d398d5e86014bb03dfeb2a371c10ce.jpg', 1, 2, '2024-10-31 07:19:00', '2025-05-25 11:31:20'),
(67, 6, 'Strawberry Shake', 'Refreshing fruity strawberry shake.', 40.00, '/capstone/uploads/menu_items/1748175076_istockphoto-184129563-612x612.jpg', 1, 0, '2024-02-10 10:34:00', '2025-05-25 12:11:16'),
(68, 1, 'Tocino', 'Sweet cured pork with a sticky glaze.', 65.00, '/capstone/uploads/menu_items/1748171638_RB-Pork-Tocino-czgq-videoSixteenByNineJumbo1600-v2.jpg', 1, 1, '2024-12-27 06:11:00', '2025-05-25 11:13:58'),
(69, 3, 'Veggie Rice', 'Rice stir-fried with assorted vegetables.', 40.00, '/capstone/uploads/menu_items/1748172666_best-fried-rice.jpg', 1, 1, '2024-11-13 05:30:00', '2025-05-25 11:31:06'),
(92, 1, 'Bistek', 'Beef steak marinated in soy sauce and calamansi, sautéed with onions.', 75.00, '/capstone/uploads/menu_items/1748171838_b5318405e36335e00e82b10ffcf7b439fff513af-2500x1600.jpg', 1, 3, '2025-05-25 10:42:45', '2025-05-25 11:17:18'),
(93, 1, 'Longganisa', 'Sweet and garlicky Filipino-style pork sausage.', 70.00, '/capstone/uploads/menu_items/1748171845_homemade-skinless-longganisa-640.jpg', 1, 4, '2025-05-25 10:42:45', '2025-05-25 11:17:25'),
(94, 2, 'Chicken Ala King', 'Creamy chicken with bell peppers and vegetables.', 70.00, '/capstone/uploads/menu_items/1748172258_chicken-ala-king-scaled.jpg', 1, 5, '2025-05-25 10:42:45', '2025-05-25 11:24:18'),
(95, 3, 'Siomai / Lumpia', 'Steamed pork dumplings or crispy spring rolls.', 25.00, '/capstone/uploads/menu_items/1748172703_pexels-benidiktus-hermanto-2827671-5347054.jpg', 1, 4, '2025-05-25 10:42:45', '2025-05-25 11:31:43'),
(96, 4, 'Garlic Rice', 'Fried rice infused with garlic flavor.', 15.00, '/capstone/uploads/menu_items/1748173865_Filipino-Sinangag-Garlic-Fried-Rice-min.jpg', 1, 3, '2025-05-25 10:42:45', '2025-05-25 11:52:19'),
(97, 5, 'Fries Platter', 'Generous portion of crispy golden fries.', 50.00, '/capstone/uploads/menu_items/1748174570_istockphoto-1443993866-612x612.jpg', 1, 1, '2025-05-25 10:42:45', '2025-05-25 12:02:50'),
(98, 5, 'Nachos Fries', 'French fries topped with nacho cheese and sauces.', 85.00, '/capstone/uploads/menu_items/1748174581_Nachos Fries.JPG', 1, 2, '2025-05-25 10:42:45', '2025-05-25 12:03:01'),
(99, 5, 'Nachos', 'Corn chips with meat, cheese, and salsa toppings.', 60.00, '/capstone/uploads/menu_items/1748174590_cover-6.jpg', 1, 3, '2025-05-25 10:42:45', '2025-05-25 12:03:10'),
(100, 5, 'Chips Nachos (Mr. Chips / Chippy)', 'Pre-packaged chips used as base for nacho toppings.', 35.00, '/capstone/uploads/menu_items/1748174638_474484258_1146144806866520_6308930645400727786_n.jpg', 1, 4, '2025-05-25 10:42:45', '2025-05-25 12:03:58'),
(101, 6, 'Coke / Mt. Dew / Pepsi', 'Cold soft drink choices.', 20.00, '/capstone/uploads/menu_items/1748175152_37133469-sabah-malaysia-january-13-2015-bottle-of-coca-cola-pepsi-and-mountain-dew-drink-isolated-on.jpg', 1, 5, '2025-05-25 10:42:45', '2025-05-25 12:12:32'),
(102, 7, 'Milo', 'Classic choco-malt drink, hot or iced.', 25.00, '/capstone/uploads/menu_items/1748175482_Banana Smoothie Image smaller.jpg', 1, 3, '2025-05-25 10:42:45', '2025-05-25 12:18:02'),
(103, 7, 'Dark Choco', 'Deep, rich hot chocolate.', 30.00, '/capstone/uploads/menu_items/1748175427_pieces-dark-chocolate-fall-into-600nw-2446312189.webp', 1, 4, '2025-05-25 10:42:45', '2025-05-25 12:17:07'),
(104, 7, 'Iced Spanish Latte', 'Sweet and creamy iced latte with milk and espresso.', 60.00, '/capstone/uploads/menu_items/1748175435_Spanish-Iced-Latte.jpg', 1, 5, '2025-05-25 10:42:45', '2025-05-25 12:17:15');

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

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `queue_number`, `table_id`, `status_id`, `total_amount`, `notes`, `created_at`, `updated_at`) VALUES
(1, '20250521-536', NULL, 5, 369.00, '', '2025-05-21 09:27:36', '2025-05-21 09:37:30'),
(2, '20250521-220', NULL, 5, 123.00, 'asd', '2025-05-21 09:50:22', '2025-05-21 09:56:23'),
(3, '20250522-397', NULL, 5, 123.00, '', '2025-05-22 05:13:26', '2025-05-22 05:13:58'),
(4, '20250524-635', NULL, 1, 123.00, '', '2025-05-24 12:11:44', '2025-05-24 12:11:44'),
(5, '20250525-056', NULL, 2, 123.00, '', '2025-05-25 03:14:50', '2025-05-25 03:37:16'),
(6, '20250525-499', NULL, 2, 123.00, '', '2025-05-25 03:25:51', '2025-05-25 03:37:16'),
(7, '20250525-018', NULL, 2, 123.00, '', '2025-05-25 03:27:18', '2025-05-25 03:37:15'),
(8, '20250525-914', NULL, 2, 123.00, '', '2025-05-25 03:30:39', '2025-05-25 03:37:15'),
(9, '20250525-661', NULL, 2, 123.00, '', '2025-05-25 03:35:19', '2025-05-25 03:37:13'),
(88, '20240101-O001', NULL, 5, 40.00, 'Original historical data with quantities', '2024-01-01 00:03:00', '2024-01-01 00:03:00'),
(89, '20240101-O002', NULL, 5, 20.00, 'Original historical data with quantities', '2024-01-01 04:12:00', '2024-01-01 04:12:00'),
(90, '20240101-O003', NULL, 5, 10.00, 'Original historical data with quantities', '2024-01-01 06:05:00', '2024-01-01 06:05:00'),
(91, '20240119-O004', NULL, 5, 40.00, 'Original historical data with quantities', '2024-01-19 12:33:00', '2024-01-19 12:33:00'),
(92, '20240209-O005', NULL, 5, 60.00, 'Original historical data with quantities', '2024-02-09 04:34:00', '2024-02-09 04:34:00'),
(93, '20240210-O006', NULL, 5, 80.00, 'Original historical data with quantities', '2024-02-10 10:34:00', '2024-02-10 10:34:00'),
(94, '20240227-O007', NULL, 5, 15.00, 'Original historical data with quantities', '2024-02-27 09:39:00', '2024-02-27 09:39:00'),
(95, '20240228-O008', NULL, 5, 40.00, 'Original historical data with quantities', '2024-02-28 05:49:00', '2024-02-28 05:49:00'),
(96, '20240315-O009', NULL, 5, 90.00, 'Original historical data with quantities', '2024-03-15 10:42:00', '2024-03-15 10:42:00'),
(97, '20240331-O010', NULL, 5, 40.00, 'Original historical data with quantities', '2024-03-31 06:52:00', '2024-03-31 06:52:00'),
(98, '20240504-O011', NULL, 5, 70.00, 'Original historical data with quantities', '2024-05-04 11:49:00', '2024-05-04 11:49:00'),
(99, '20240508-O012', NULL, 5, 140.00, 'Original historical data with quantities', '2024-05-08 00:40:00', '2024-05-08 00:40:00'),
(100, '20240515-O013', NULL, 5, 70.00, 'Original historical data with quantities', '2024-05-15 12:23:00', '2024-05-15 12:23:00'),
(101, '20240521-O014', NULL, 5, 100.00, 'Original historical data with quantities', '2024-05-21 11:48:00', '2024-05-21 11:48:00'),
(102, '20240526-O015', NULL, 5, 195.00, 'Original historical data with quantities', '2024-05-26 04:37:00', '2024-05-26 04:37:00'),
(103, '20240601-O016', NULL, 5, 80.00, 'Original historical data with quantities', '2024-06-01 00:03:00', '2024-06-01 00:03:00'),
(104, '20240603-O017', NULL, 5, 40.00, 'Original historical data with quantities', '2024-06-03 07:16:00', '2024-06-03 07:16:00'),
(105, '20240616-O018', NULL, 5, 80.00, 'Original historical data with quantities', '2024-06-16 10:00:00', '2024-06-16 10:00:00'),
(106, '20240701-O019', NULL, 5, 20.00, 'Original historical data with quantities', '2024-07-01 01:25:00', '2024-07-01 01:25:00'),
(107, '20240710-O020', NULL, 5, 40.00, 'Original historical data with quantities', '2024-07-10 11:05:00', '2024-07-10 11:05:00'),
(108, '20240711-O021', NULL, 5, 70.00, 'Original historical data with quantities', '2024-07-11 04:35:00', '2024-07-11 04:35:00'),
(109, '20240805-O022', NULL, 5, 40.00, 'Original historical data with quantities', '2024-08-05 06:25:00', '2024-08-05 06:25:00'),
(110, '20240808-O023', NULL, 5, 40.00, 'Original historical data with quantities', '2024-08-08 11:46:00', '2024-08-08 11:46:00'),
(111, '20240911-O024', NULL, 5, 80.00, 'Original historical data with quantities', '2024-09-11 07:32:00', '2024-09-11 07:32:00'),
(112, '20240916-O025', NULL, 5, 75.00, 'Original historical data with quantities', '2024-09-16 10:35:00', '2024-09-16 10:35:00'),
(113, '20240921-O026', NULL, 5, 45.00, 'Original historical data with quantities', '2024-09-21 05:07:00', '2024-09-21 05:07:00'),
(114, '20241025-O027', NULL, 5, 40.00, 'Original historical data with quantities', '2024-10-25 12:43:00', '2024-10-25 12:43:00'),
(115, '20241031-O028', NULL, 5, 60.00, 'Original historical data with quantities', '2024-10-31 07:19:00', '2024-10-31 07:19:00'),
(116, '20241113-O029', NULL, 5, 40.00, 'Original historical data with quantities', '2024-11-13 05:30:00', '2024-11-13 05:30:00'),
(117, '20241116-O030', NULL, 5, 80.00, 'Original historical data with quantities', '2024-11-16 04:19:00', '2024-11-16 04:19:00'),
(118, '20241119-O031', NULL, 5, 50.00, 'Original historical data with quantities', '2024-11-19 05:28:00', '2024-11-19 05:28:00'),
(119, '20241210-O032', NULL, 5, 40.00, 'Original historical data with quantities', '2024-12-10 07:00:00', '2024-12-10 07:00:00'),
(120, '20241222-O033', NULL, 5, 50.00, 'Original historical data with quantities', '2024-12-22 03:35:00', '2024-12-22 03:35:00'),
(121, '20241222-O034', NULL, 5, 20.00, 'Original historical data with quantities', '2024-12-22 04:14:00', '2024-12-22 04:14:00'),
(122, '20241225-O035', NULL, 5, 120.00, 'Original historical data with quantities', '2024-12-25 05:40:00', '2024-12-25 05:40:00'),
(123, '20241227-O036', NULL, 5, 65.00, 'Original historical data with quantities', '2024-12-27 06:11:00', '2024-12-27 06:11:00'),
(124, '20250331-O037', NULL, 5, 160.00, 'Original historical data with quantities', '2025-03-31 09:17:00', '2025-03-31 09:17:00');

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

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `item_id`, `variation_id`, `quantity`, `unit_price`, `subtotal`, `notes`, `created_at`) VALUES
(90, 88, 58, NULL, 1, 40.00, 40.00, 'Original order item with quantity', '2024-01-01 00:03:00'),
(91, 89, 53, NULL, 2, 10.00, 20.00, 'Original order item with quantity', '2024-01-01 04:12:00'),
(92, 90, 53, NULL, 1, 10.00, 10.00, 'Original order item with quantity', '2024-01-01 06:05:00'),
(93, 91, 58, NULL, 1, 40.00, 40.00, 'Original order item with quantity', '2024-01-19 12:33:00'),
(94, 92, 48, NULL, 3, 20.00, 60.00, 'Original order item with quantity', '2024-02-09 04:34:00'),
(95, 93, 67, NULL, 2, 40.00, 80.00, 'Original order item with quantity', '2024-02-10 10:34:00'),
(96, 94, 57, NULL, 1, 15.00, 15.00, 'Original order item with quantity', '2024-02-27 09:39:00'),
(97, 95, 63, NULL, 1, 40.00, 40.00, 'Original order item with quantity', '2024-02-28 05:49:00'),
(98, 96, 56, NULL, 2, 45.00, 90.00, 'Original order item with quantity', '2024-03-15 10:42:00'),
(99, 97, 59, NULL, 1, 40.00, 40.00, 'Original order item with quantity', '2024-03-31 06:52:00'),
(100, 98, 65, NULL, 1, 70.00, 70.00, 'Original order item with quantity', '2024-05-04 11:49:00'),
(101, 99, 64, NULL, 2, 70.00, 140.00, 'Original order item with quantity', '2024-05-08 00:40:00'),
(102, 100, 47, NULL, 1, 70.00, 70.00, 'Original order item with quantity', '2024-05-15 12:23:00'),
(103, 101, 62, NULL, 1, 100.00, 100.00, 'Original order item with quantity', '2024-05-21 11:48:00'),
(104, 102, 50, NULL, 3, 65.00, 195.00, 'Original order item with quantity', '2024-05-26 04:37:00'),
(105, 103, 58, NULL, 2, 40.00, 80.00, 'Original order item with quantity', '2024-06-01 00:03:00'),
(106, 104, 51, NULL, 1, 40.00, 40.00, 'Original order item with quantity', '2024-06-03 07:16:00'),
(107, 105, 52, NULL, 2, 40.00, 80.00, 'Original order item with quantity', '2024-06-16 10:00:00'),
(108, 106, 48, NULL, 1, 20.00, 20.00, 'Original order item with quantity', '2024-07-01 01:25:00'),
(109, 107, 48, NULL, 2, 20.00, 40.00, 'Original order item with quantity', '2024-07-10 11:05:00'),
(110, 108, 64, NULL, 1, 70.00, 70.00, 'Original order item with quantity', '2024-07-11 04:35:00'),
(111, 109, 61, NULL, 4, 10.00, 40.00, 'Original order item with quantity', '2024-08-05 06:25:00'),
(112, 110, 63, NULL, 1, 40.00, 40.00, 'Original order item with quantity', '2024-08-08 11:46:00'),
(113, 111, 60, NULL, 2, 40.00, 80.00, 'Original order item with quantity', '2024-09-11 07:32:00'),
(114, 112, 55, NULL, 1, 75.00, 75.00, 'Original order item with quantity', '2024-09-16 10:35:00'),
(115, 113, 54, NULL, 3, 15.00, 45.00, 'Original order item with quantity', '2024-09-21 05:07:00'),
(116, 114, 58, NULL, 1, 40.00, 40.00, 'Original order item with quantity', '2024-10-25 12:43:00'),
(117, 115, 66, NULL, 2, 30.00, 60.00, 'Original order item with quantity', '2024-10-31 07:19:00'),
(118, 116, 69, NULL, 1, 40.00, 40.00, 'Original order item with quantity', '2024-11-13 05:30:00'),
(119, 117, 63, NULL, 2, 40.00, 80.00, 'Original order item with quantity', '2024-11-16 04:19:00'),
(120, 118, 53, NULL, 5, 10.00, 50.00, 'Original order item with quantity', '2024-11-19 05:28:00'),
(121, 119, 51, NULL, 1, 40.00, 40.00, 'Original order item with quantity', '2024-12-10 07:00:00'),
(122, 120, 49, NULL, 2, 25.00, 50.00, 'Original order item with quantity', '2024-12-22 03:35:00'),
(123, 121, 48, NULL, 1, 20.00, 20.00, 'Original order item with quantity', '2024-12-22 04:14:00'),
(124, 122, 58, NULL, 3, 40.00, 120.00, 'Original order item with quantity', '2024-12-25 05:40:00'),
(125, 123, 68, NULL, 1, 65.00, 65.00, 'Original order item with quantity', '2024-12-27 06:11:00'),
(126, 124, 60, NULL, 4, 40.00, 160.00, 'Original order item with quantity', '2025-03-31 09:17:00');

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

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`history_id`, `order_id`, `status_id`, `notes`, `created_at`) VALUES
(1, 1, 1, 'Order placed', '2025-05-21 09:27:36'),
(2, 1, 2, 'Order marked as paid by counter', '2025-05-21 09:27:47'),
(3, 1, 3, 'Order preparation started', '2025-05-21 09:35:56'),
(4, 1, 4, 'Order ready for pickup', '2025-05-21 09:37:22'),
(5, 1, 5, 'Order marked as completed by counter', '2025-05-21 09:37:30'),
(6, 2, 1, 'Order placed', '2025-05-21 09:50:22'),
(7, 2, 2, 'Order marked as paid by counter', '2025-05-21 09:50:27'),
(8, 2, 3, 'Order preparation started', '2025-05-21 09:51:15'),
(9, 2, 4, 'Order ready for pickup', '2025-05-21 09:51:25'),
(10, 2, 5, 'Order marked as completed by counter', '2025-05-21 09:56:23'),
(11, 3, 1, 'Order placed', '2025-05-22 05:13:26'),
(12, 3, 2, 'Order marked as paid by counter', '2025-05-22 05:13:32'),
(13, 3, 3, 'Order preparation started', '2025-05-22 05:13:47'),
(14, 3, 4, 'Order ready for pickup', '2025-05-22 05:13:49'),
(15, 3, 5, 'Order marked as completed by counter', '2025-05-22 05:13:58'),
(16, 4, 1, 'Order placed', '2025-05-24 12:11:44'),
(17, 5, 1, 'Order placed', '2025-05-25 03:14:50'),
(18, 6, 1, 'Order placed', '2025-05-25 03:25:51'),
(19, 7, 1, 'Order placed', '2025-05-25 03:27:18'),
(20, 8, 1, 'Order placed', '2025-05-25 03:30:39'),
(21, 9, 1, 'Order placed', '2025-05-25 03:35:19'),
(22, 9, 2, 'Order marked as paid by counter', '2025-05-25 03:37:13'),
(23, 8, 2, 'Order marked as paid by counter', '2025-05-25 03:37:15'),
(24, 7, 2, 'Order marked as paid by counter', '2025-05-25 03:37:15'),
(25, 6, 2, 'Order marked as paid by counter', '2025-05-25 03:37:16'),
(26, 5, 2, 'Order marked as paid by counter', '2025-05-25 03:37:16'),
(105, 88, 5, 'Original historical order completed', '2024-01-01 00:03:00'),
(106, 89, 5, 'Original historical order completed', '2024-01-01 04:12:00'),
(107, 90, 5, 'Original historical order completed', '2024-01-01 06:05:00'),
(108, 91, 5, 'Original historical order completed', '2024-01-19 12:33:00'),
(109, 92, 5, 'Original historical order completed', '2024-02-09 04:34:00'),
(110, 93, 5, 'Original historical order completed', '2024-02-10 10:34:00'),
(111, 94, 5, 'Original historical order completed', '2024-02-27 09:39:00'),
(112, 95, 5, 'Original historical order completed', '2024-02-28 05:49:00'),
(113, 96, 5, 'Original historical order completed', '2024-03-15 10:42:00'),
(114, 97, 5, 'Original historical order completed', '2024-03-31 06:52:00'),
(115, 98, 5, 'Original historical order completed', '2024-05-04 11:49:00'),
(116, 99, 5, 'Original historical order completed', '2024-05-08 00:40:00'),
(117, 100, 5, 'Original historical order completed', '2024-05-15 12:23:00'),
(118, 101, 5, 'Original historical order completed', '2024-05-21 11:48:00'),
(119, 102, 5, 'Original historical order completed', '2024-05-26 04:37:00'),
(120, 103, 5, 'Original historical order completed', '2024-06-01 00:03:00'),
(121, 104, 5, 'Original historical order completed', '2024-06-03 07:16:00'),
(122, 105, 5, 'Original historical order completed', '2024-06-16 10:00:00'),
(123, 106, 5, 'Original historical order completed', '2024-07-01 01:25:00'),
(124, 107, 5, 'Original historical order completed', '2024-07-10 11:05:00'),
(125, 108, 5, 'Original historical order completed', '2024-07-11 04:35:00'),
(126, 109, 5, 'Original historical order completed', '2024-08-05 06:25:00'),
(127, 110, 5, 'Original historical order completed', '2024-08-08 11:46:00'),
(128, 111, 5, 'Original historical order completed', '2024-09-11 07:32:00'),
(129, 112, 5, 'Original historical order completed', '2024-09-16 10:35:00'),
(130, 113, 5, 'Original historical order completed', '2024-09-21 05:07:00'),
(131, 114, 5, 'Original historical order completed', '2024-10-25 12:43:00'),
(132, 115, 5, 'Original historical order completed', '2024-10-31 07:19:00'),
(133, 116, 5, 'Original historical order completed', '2024-11-13 05:30:00'),
(134, 117, 5, 'Original historical order completed', '2024-11-16 04:19:00'),
(135, 118, 5, 'Original historical order completed', '2024-11-19 05:28:00'),
(136, 119, 5, 'Original historical order completed', '2024-12-10 07:00:00'),
(137, 120, 5, 'Original historical order completed', '2024-12-22 03:35:00'),
(138, 121, 5, 'Original historical order completed', '2024-12-22 04:14:00'),
(139, 122, 5, 'Original historical order completed', '2024-12-25 05:40:00'),
(140, 123, 5, 'Original historical order completed', '2024-12-27 06:11:00'),
(141, 124, 5, 'Original historical order completed', '2025-03-31 09:17:00');

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
-- Indexes for table `counter_users`
--
ALTER TABLE `counter_users`
  ADD PRIMARY KEY (`counter_user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `daily_sales`
--
ALTER TABLE `daily_sales`
  ADD PRIMARY KEY (`daily_sales_id`),
  ADD UNIQUE KEY `date` (`date`);

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
  ADD UNIQUE KEY `unique_menu_item_name` (`name`),
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
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`table_id`),
  ADD UNIQUE KEY `table_number` (`table_number`);

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
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `counter_users`
--
ALTER TABLE `counter_users`
  MODIFY `counter_user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `daily_sales`
--
ALTER TABLE `daily_sales`
  MODIFY `daily_sales_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

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
  MODIFY `popularity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- AUTO_INCREMENT for table `item_variations`
--
ALTER TABLE `item_variations`
  MODIFY `variation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `monthly_sales`
--
ALTER TABLE `monthly_sales`
  MODIFY `monthly_sales_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

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
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `table_id` int(11) NOT NULL AUTO_INCREMENT;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

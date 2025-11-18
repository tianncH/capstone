-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 28, 2025 at 06:04 AM
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
(1, 'admin', '$2y$10$FYHvV2aFfmkxwmeAV/nT7uUTaSX5nSmb4k00Lp93gYiN73D3.IJmK', 'System Administrator', 'admin@example.com', 'admin', '2025-05-27 12:53:59', 1, '2025-05-17 01:34:54', '2025-05-27 04:53:59');

-- --------------------------------------------------------

--
-- Table structure for table `cash_float`
--

CREATE TABLE `cash_float` (
  `float_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `opening_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `closing_amount` decimal(10,2) DEFAULT NULL,
  `set_by_admin_id` int(11) NOT NULL,
  `closed_by_admin_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','closed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cash_float`
--

INSERT INTO `cash_float` (`float_id`, `date`, `opening_amount`, `closing_amount`, `set_by_admin_id`, `closed_by_admin_id`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, '2025-05-25', 2000.00, NULL, 1, NULL, 'dont steal ever!', 'active', '2025-05-25 13:23:01', '2025-05-25 13:32:02');

-- --------------------------------------------------------

--
-- Table structure for table `cash_transactions`
--

CREATE TABLE `cash_transactions` (
  `transaction_id` int(11) NOT NULL,
  `float_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `transaction_type` enum('payment','change','adjustment','opening','closing') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `customer_payment` decimal(10,2) DEFAULT NULL,
  `change_given` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `counter_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'counter', '$2y$10$xE4B5pHpakOSfvmUqsULPuHjDw4yHNWM3He1OUlnhf6c4BL7yJrEi', 'Counter Staff', 1, '2025-05-28 10:37:03', '2025-05-22 05:05:41');

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
(1, '2019-02-08', 15, 1955.00, '2019-02-08 00:01:00', '2025-05-27 12:58:09'),
(2, '2020-03-15', 3, 375.00, '2020-03-15 02:30:00', '2025-05-27 12:58:09'),
(3, '2020-05-22', 2, 215.00, '2020-05-22 01:15:00', '2025-05-27 12:58:09'),
(4, '2020-07-10', 1, 150.00, '2020-07-10 08:45:00', '2025-05-27 12:58:09'),
(5, '2020-09-18', 2, 175.00, '2020-09-18 00:30:00', '2025-05-27 12:58:09'),
(6, '2020-11-25', 1, 120.00, '2020-11-25 02:00:00', '2025-05-27 12:58:09'),
(7, '2020-12-31', 1, 90.00, '2020-12-31 11:30:00', '2025-05-27 12:58:09'),
(8, '2021-01-15', 1, 140.00, '2021-01-15 03:30:00', '2025-05-27 12:58:09'),
(9, '2021-02-14', 1, 160.00, '2021-02-14 10:45:00', '2025-05-27 12:58:09'),
(10, '2021-03-20', 1, 210.00, '2021-03-20 04:15:00', '2025-05-27 12:58:09'),
(11, '2021-04-25', 1, 80.00, '2021-04-25 06:30:00', '2025-05-27 12:58:09'),
(12, '2021-05-30', 1, 60.00, '2021-05-30 08:00:00', '2025-05-27 12:58:09'),
(13, '2021-06-15', 1, 195.00, '2021-06-15 02:45:00', '2025-05-27 12:58:09'),
(14, '2021-07-20', 1, 75.00, '2021-07-20 05:30:00', '2025-05-27 12:58:09'),
(15, '2021-08-25', 1, 80.00, '2021-08-25 07:15:00', '2025-05-27 12:58:09'),
(16, '2021-09-30', 1, 70.00, '2021-09-30 09:45:00', '2025-05-27 12:58:09'),
(17, '2021-10-31', 1, 170.00, '2021-10-31 11:00:00', '2025-05-27 12:58:09'),
(18, '2022-01-10', 1, 260.00, '2022-01-10 01:30:00', '2025-05-27 12:58:09'),
(19, '2022-02-20', 1, 140.00, '2022-02-20 03:15:00', '2025-05-27 12:58:09'),
(20, '2022-03-25', 1, 120.00, '2022-03-25 05:45:00', '2025-05-27 12:58:09'),
(21, '2022-04-30', 1, 120.00, '2022-04-30 07:30:00', '2025-05-27 12:58:09'),
(22, '2022-05-15', 1, 140.00, '2022-05-15 09:00:00', '2025-05-27 12:58:09'),
(23, '2022-06-20', 1, 80.00, '2022-06-20 04:30:00', '2025-05-27 12:58:09'),
(24, '2022-07-25', 1, 120.00, '2022-07-25 06:15:00', '2025-05-27 12:58:09'),
(25, '2022-08-30', 1, 100.00, '2022-08-30 08:45:00', '2025-05-27 12:58:09'),
(26, '2022-09-15', 1, 75.00, '2022-09-15 10:30:00', '2025-05-27 12:58:09'),
(27, '2022-10-20', 1, 200.00, '2022-10-20 02:15:00', '2025-05-27 12:58:09'),
(28, '2023-01-15', 1, 210.00, '2023-01-15 02:30:00', '2025-05-27 12:58:09'),
(29, '2023-02-20', 1, 200.00, '2023-02-20 04:45:00', '2025-05-27 12:58:09'),
(30, '2023-03-25', 1, 160.00, '2023-03-25 06:15:00', '2025-05-27 12:58:09'),
(31, '2023-04-30', 1, 140.00, '2023-04-30 08:30:00', '2025-05-27 12:58:09'),
(32, '2023-05-15', 1, 135.00, '2023-05-15 03:00:00', '2025-05-27 12:58:09'),
(33, '2023-06-20', 1, 100.00, '2023-06-20 05:30:00', '2025-05-27 12:58:09'),
(34, '2023-07-25', 1, 160.00, '2023-07-25 07:45:00', '2025-05-27 12:58:09'),
(35, '2023-08-30', 1, 120.00, '2023-08-30 09:15:00', '2025-05-27 12:58:09'),
(36, '2023-09-15', 1, 75.00, '2023-09-15 04:00:00', '2025-05-27 12:58:09'),
(37, '2023-10-20', 1, 90.00, '2023-10-20 06:30:00', '2025-05-27 12:58:09'),
(38, '2024-01-10', 1, 325.00, '2024-01-10 01:15:00', '2025-05-27 12:58:09'),
(39, '2024-02-15', 1, 140.00, '2024-02-15 03:30:00', '2025-05-27 12:58:09'),
(40, '2024-03-20', 1, 140.00, '2024-03-20 05:45:00', '2025-05-27 12:58:09'),
(41, '2024-04-25', 1, 195.00, '2024-04-25 07:00:00', '2025-05-27 12:58:09'),
(42, '2024-05-30', 1, 140.00, '2024-05-30 08:30:00', '2025-05-27 12:58:09'),
(43, '2024-06-15', 1, 160.00, '2024-06-15 04:15:00', '2025-05-27 12:58:09'),
(44, '2024-07-20', 1, 75.00, '2024-07-20 06:45:00', '2025-05-27 12:58:09'),
(45, '2024-08-25', 1, 125.00, '2024-08-25 08:00:00', '2025-05-27 12:58:09'),
(46, '2024-09-30', 1, 120.00, '2024-09-30 09:30:00', '2025-05-27 12:58:09'),
(47, '2024-10-15', 1, 120.00, '2024-10-15 03:45:00', '2025-05-27 12:58:09'),
(48, '2024-11-20', 1, 280.00, '2024-11-20 05:15:00', '2025-05-27 12:58:09'),
(49, '2024-12-25', 1, 90.00, '2024-12-25 10:00:00', '2025-05-27 12:58:09'),
(50, '2025-01-05', 1, 210.00, '2025-01-05 02:30:00', '2025-05-27 12:58:09'),
(51, '2025-01-10', 1, 100.00, '2025-01-10 04:15:00', '2025-05-27 12:58:09'),
(52, '2025-01-15', 1, 80.00, '2025-01-15 06:45:00', '2025-05-27 12:58:09'),
(53, '2025-01-20', 1, 260.00, '2025-01-20 08:30:00', '2025-05-27 12:58:09'),
(54, '2025-01-25', 1, 75.00, '2025-01-25 03:00:00', '2025-05-27 12:58:09'),
(55, '2025-02-01', 1, 140.00, '2025-02-01 05:30:00', '2025-05-27 12:58:09'),
(56, '2025-02-05', 1, 200.00, '2025-02-05 07:15:00', '2025-05-27 12:58:09'),
(57, '2025-02-10', 1, 90.00, '2025-02-10 09:00:00', '2025-05-27 12:58:09'),
(58, '2025-02-15', 1, 195.00, '2025-02-15 04:45:00', '2025-05-27 12:58:09'),
(59, '2025-02-20', 1, 160.00, '2025-02-20 06:30:00', '2025-05-27 12:58:09'),
(60, '2025-03-01', 1, 140.00, '2025-03-01 02:15:00', '2025-05-27 12:58:09'),
(61, '2025-03-05', 1, 75.00, '2025-03-05 04:00:00', '2025-05-27 12:58:09'),
(62, '2025-03-10', 1, 120.00, '2025-03-10 06:15:00', '2025-05-27 12:58:09'),
(63, '2025-03-15', 1, 100.00, '2025-03-15 08:45:00', '2025-05-27 12:58:09'),
(64, '2025-03-20', 1, 120.00, '2025-03-20 03:30:00', '2025-05-27 12:58:09'),
(65, '2025-04-01', 1, 75.00, '2025-04-01 05:15:00', '2025-05-27 12:58:09'),
(66, '2025-04-05', 1, 140.00, '2025-04-05 07:30:00', '2025-05-27 12:58:09'),
(67, '2025-04-10', 1, 100.00, '2025-04-10 09:15:00', '2025-05-27 12:58:09'),
(68, '2025-04-15', 1, 120.00, '2025-04-15 04:30:00', '2025-05-27 12:58:09'),
(69, '2025-04-20', 1, 105.00, '2025-04-20 06:45:00', '2025-05-27 12:58:09'),
(70, '2025-05-01', 1, 210.00, '2025-05-01 02:45:00', '2025-05-27 12:58:09'),
(71, '2025-05-05', 1, 130.00, '2025-05-05 04:30:00', '2025-05-27 12:58:09'),
(72, '2025-05-10', 1, 280.00, '2025-05-10 06:15:00', '2025-05-27 12:58:09'),
(73, '2025-05-15', 1, 100.00, '2025-05-15 08:00:00', '2025-05-27 12:58:09'),
(74, '2025-05-20', 1, 200.00, '2025-05-20 03:15:00', '2025-05-27 12:58:09'),
(75, '2025-05-25', 1, 120.00, '2025-05-25 05:45:00', '2025-05-27 12:58:09'),
(128, '2025-05-27', 11, 2390.00, '2025-05-27 13:07:32', '2025-05-27 15:13:17');

-- --------------------------------------------------------

--
-- Table structure for table `discount_presets`
--

CREATE TABLE `discount_presets` (
  `preset_id` int(11) NOT NULL,
  `preset_key` varchar(50) NOT NULL,
  `preset_name` varchar(100) NOT NULL,
  `default_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `icon_class` varchar(50) DEFAULT 'bi-percent',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discount_presets`
--

INSERT INTO `discount_presets` (`preset_id`, `preset_key`, `preset_name`, `default_percentage`, `icon_class`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'senior', 'Senior Citizen', 20.00, 'bi-person-badge', 1, '2025-05-25 13:43:55', '2025-05-25 13:43:55'),
(2, 'pwd', 'PWD', 20.00, 'bi-universal-access', 1, '2025-05-25 13:43:55', '2025-05-25 13:43:55'),
(3, 'employee', 'Employee', 15.00, 'bi-person-workspace', 1, '2025-05-25 13:43:55', '2025-05-25 13:43:55');

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
(1, 48, '2019-02-08', 5, '2019-02-08 10:37:00', '2025-05-27 12:58:09'),
(2, 49, '2019-02-08', 1, '2019-02-08 04:43:00', '2025-05-27 12:58:09'),
(3, 50, '2019-02-08', 4, '2019-02-08 09:16:00', '2025-05-27 12:58:09'),
(4, 51, '2019-02-08', 5, '2019-02-08 09:55:00', '2025-05-27 12:58:09'),
(5, 53, '2019-02-08', 1, '2019-02-08 08:39:00', '2025-05-27 12:58:09'),
(6, 54, '2019-02-08', 2, '2019-02-08 09:36:00', '2025-05-27 12:58:09'),
(7, 56, '2019-02-08', 2, '2019-02-08 06:10:00', '2025-05-27 12:58:09'),
(8, 57, '2019-02-08', 2, '2019-02-08 04:23:00', '2025-05-27 12:58:09'),
(9, 59, '2019-02-08', 4, '2019-02-08 07:05:00', '2025-05-27 12:58:09'),
(10, 60, '2019-02-08', 5, '2019-02-08 02:44:00', '2025-05-27 12:58:09'),
(11, 63, '2019-02-08', 2, '2019-02-08 04:36:00', '2025-05-27 12:58:09'),
(12, 64, '2019-02-08', 9, '2019-02-08 00:01:00', '2025-05-27 12:58:09'),
(13, 65, '2019-02-08', 2, '2019-02-08 00:09:00', '2025-05-27 12:58:09'),
(14, 62, '2020-03-15', 1, '2020-03-15 03:45:00', '2025-05-27 12:58:09'),
(15, 67, '2020-03-15', 2, '2020-03-15 06:20:00', '2025-05-27 12:58:09'),
(16, 68, '2020-03-15', 3, '2020-03-15 02:30:00', '2025-05-27 12:58:09'),
(17, 55, '2020-05-22', 1, '2020-05-22 04:30:00', '2025-05-27 12:58:09'),
(18, 93, '2020-05-22', 2, '2020-05-22 01:15:00', '2025-05-27 12:58:09'),
(19, 97, '2020-07-10', 3, '2020-07-10 08:45:00', '2025-05-27 12:58:09'),
(20, 92, '2020-09-18', 1, '2020-09-18 00:30:00', '2025-05-27 12:58:09'),
(21, 102, '2020-09-18', 4, '2020-09-18 05:15:00', '2025-05-27 12:58:09'),
(22, 99, '2020-11-25', 2, '2020-11-25 02:00:00', '2025-05-27 12:58:09'),
(23, 103, '2020-12-31', 3, '2020-12-31 11:30:00', '2025-05-27 12:58:09'),
(24, 65, '2021-01-15', 2, '2021-01-15 03:30:00', '2025-05-27 12:58:09'),
(25, 51, '2021-02-14', 4, '2021-02-14 10:45:00', '2025-05-27 12:58:09'),
(26, 64, '2021-03-20', 3, '2021-03-20 04:15:00', '2025-05-27 12:58:09'),
(27, 60, '2021-04-25', 2, '2021-04-25 06:30:00', '2025-05-27 12:58:09'),
(28, 104, '2021-05-30', 1, '2021-05-30 08:00:00', '2025-05-27 12:58:09'),
(29, 50, '2021-06-15', 3, '2021-06-15 02:45:00', '2025-05-27 12:58:09'),
(30, 96, '2021-07-20', 5, '2021-07-20 05:30:00', '2025-05-27 12:58:09'),
(31, 52, '2021-08-25', 2, '2021-08-25 07:15:00', '2025-05-27 12:58:09'),
(32, 94, '2021-09-30', 1, '2021-09-30 09:45:00', '2025-05-27 12:58:09'),
(33, 98, '2021-10-31', 2, '2021-10-31 11:00:00', '2025-05-27 12:58:09'),
(34, 68, '2022-01-10', 4, '2022-01-10 01:30:00', '2025-05-27 12:58:09'),
(35, 47, '2022-02-20', 2, '2022-02-20 03:15:00', '2025-05-27 12:58:09'),
(36, 48, '2022-03-25', 6, '2022-03-25 05:45:00', '2025-05-27 12:58:09'),
(37, 67, '2022-04-30', 3, '2022-04-30 07:30:00', '2025-05-27 12:58:09'),
(38, 93, '2022-05-15', 2, '2022-05-15 09:00:00', '2025-05-27 12:58:09'),
(39, 61, '2022-06-20', 8, '2022-06-20 04:30:00', '2025-05-27 12:58:09'),
(40, 69, '2022-07-25', 3, '2022-07-25 06:15:00', '2025-05-27 12:58:09'),
(41, 49, '2022-08-30', 4, '2022-08-30 08:45:00', '2025-05-27 12:58:09'),
(42, 55, '2022-09-15', 1, '2022-09-15 10:30:00', '2025-05-27 12:58:09'),
(43, 59, '2022-10-20', 5, '2022-10-20 02:15:00', '2025-05-27 12:58:09'),
(44, 64, '2023-01-15', 3, '2023-01-15 02:30:00', '2025-05-27 12:58:09'),
(45, 62, '2023-02-20', 2, '2023-02-20 04:45:00', '2025-05-27 12:58:09'),
(46, 51, '2023-03-25', 4, '2023-03-25 06:15:00', '2025-05-27 12:58:09'),
(47, 65, '2023-04-30', 2, '2023-04-30 08:30:00', '2025-05-27 12:58:09'),
(48, 56, '2023-05-15', 3, '2023-05-15 03:00:00', '2025-05-27 12:58:09'),
(49, 97, '2023-06-20', 2, '2023-06-20 05:30:00', '2025-05-27 12:58:09'),
(50, 60, '2023-07-25', 4, '2023-07-25 07:45:00', '2025-05-27 12:58:09'),
(51, 63, '2023-08-30', 3, '2023-08-30 09:15:00', '2025-05-27 12:58:09'),
(52, 92, '2023-09-15', 1, '2023-09-15 04:00:00', '2025-05-27 12:58:09'),
(53, 54, '2023-10-20', 6, '2023-10-20 06:30:00', '2025-05-27 12:58:09'),
(54, 68, '2024-01-10', 5, '2024-01-10 01:15:00', '2025-05-27 12:58:09'),
(55, 94, '2024-02-15', 2, '2024-02-15 03:30:00', '2025-05-27 12:58:09'),
(56, 48, '2024-03-20', 7, '2024-03-20 05:45:00', '2025-05-27 12:58:09'),
(57, 50, '2024-04-25', 3, '2024-04-25 07:00:00', '2025-05-27 12:58:09'),
(58, 93, '2024-05-30', 2, '2024-05-30 08:30:00', '2025-05-27 12:58:09'),
(59, 67, '2024-06-15', 4, '2024-06-15 04:15:00', '2025-05-27 12:58:09'),
(60, 55, '2024-07-20', 1, '2024-07-20 06:45:00', '2025-05-27 12:58:09'),
(61, 102, '2024-08-25', 5, '2024-08-25 08:00:00', '2025-05-27 12:58:09'),
(62, 99, '2024-09-30', 2, '2024-09-30 09:30:00', '2025-05-27 12:58:09'),
(63, 96, '2024-10-15', 8, '2024-10-15 03:45:00', '2025-05-27 12:58:09'),
(64, 64, '2024-11-20', 4, '2024-11-20 05:15:00', '2025-05-27 12:58:09'),
(65, 103, '2024-12-25', 3, '2024-12-25 10:00:00', '2025-05-27 12:58:09'),
(66, 65, '2025-01-05', 3, '2025-01-05 02:30:00', '2025-05-27 12:58:09'),
(67, 62, '2025-01-10', 1, '2025-01-10 04:15:00', '2025-05-27 12:58:09'),
(68, 51, '2025-01-15', 2, '2025-01-15 06:45:00', '2025-05-27 12:58:09'),
(69, 68, '2025-01-20', 4, '2025-01-20 08:30:00', '2025-05-27 12:58:09'),
(70, 49, '2025-01-25', 3, '2025-01-25 03:00:00', '2025-05-27 12:58:09'),
(71, 64, '2025-02-01', 2, '2025-02-01 05:30:00', '2025-05-27 12:58:09'),
(72, 60, '2025-02-05', 5, '2025-02-05 07:15:00', '2025-05-27 12:58:09'),
(73, 56, '2025-02-10', 2, '2025-02-10 09:00:00', '2025-05-27 12:58:09'),
(74, 50, '2025-02-15', 3, '2025-02-15 04:45:00', '2025-05-27 12:58:09'),
(75, 67, '2025-02-20', 4, '2025-02-20 06:30:00', '2025-05-27 12:58:09'),
(76, 93, '2025-03-01', 2, '2025-03-01 02:15:00', '2025-05-27 12:58:09'),
(77, 55, '2025-03-05', 1, '2025-03-05 04:00:00', '2025-05-27 12:58:09'),
(78, 48, '2025-03-10', 6, '2025-03-10 06:15:00', '2025-05-27 12:58:09'),
(79, 97, '2025-03-15', 2, '2025-03-15 08:45:00', '2025-05-27 12:58:09'),
(80, 63, '2025-03-20', 3, '2025-03-20 03:30:00', '2025-05-27 12:58:09'),
(81, 92, '2025-04-01', 1, '2025-04-01 05:15:00', '2025-05-27 12:58:09'),
(82, 94, '2025-04-05', 2, '2025-04-05 07:30:00', '2025-05-27 12:58:09'),
(83, 102, '2025-04-10', 4, '2025-04-10 09:15:00', '2025-05-27 12:58:09'),
(84, 99, '2025-04-15', 2, '2025-04-15 04:30:00', '2025-05-27 12:58:09'),
(85, 54, '2025-04-20', 7, '2025-04-20 06:45:00', '2025-05-27 12:58:09'),
(86, 65, '2025-05-01', 3, '2025-05-01 02:45:00', '2025-05-27 12:58:09'),
(87, 68, '2025-05-05', 2, '2025-05-05 04:30:00', '2025-05-27 12:58:09'),
(88, 64, '2025-05-10', 4, '2025-05-10 06:15:00', '2025-05-27 12:58:09'),
(89, 62, '2025-05-15', 1, '2025-05-15 08:00:00', '2025-05-27 12:58:09'),
(90, 51, '2025-05-20', 5, '2025-05-20 03:15:00', '2025-05-27 12:58:09'),
(91, 104, '2025-05-25', 2, '2025-05-25 05:45:00', '2025-05-27 12:58:09'),
(128, 100, '2025-05-27', 1, '2025-05-27 13:07:32', '2025-05-27 13:07:32'),
(129, 64, '2025-05-27', 2, '2025-05-27 14:57:50', '2025-05-27 15:05:58'),
(130, 59, '2025-05-27', 2, '2025-05-27 15:12:32', '2025-05-27 15:13:13');

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
  `is_best_seller` tinyint(1) NOT NULL DEFAULT 0,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`item_id`, `category_id`, `name`, `description`, `price`, `image_url`, `is_available`, `is_best_seller`, `display_order`, `created_at`, `updated_at`) VALUES
(47, 2, 'Beefy Mushroom', 'Creamy beef and mushroom stew over rice.', 70.00, '/capstone/uploads/menu_items/1748172227_ecc27a9f6b1b53494ab82e52e30a8f9d.avif', 1, 0, 3, '2024-05-15 12:23:00', '2025-05-25 11:23:47'),
(48, 7, 'Brewed Coffee', 'Straight-up black coffee for a quick boost.', 20.00, '/capstone/uploads/menu_items/1748175344_pexels-sikunovruslan-20444220.jpg', 1, 0, 0, '2024-02-09 04:34:00', '2025-05-25 12:15:44'),
(49, 7, 'Café Latte', 'Smooth espresso with steamed milk.', 25.00, '/capstone/uploads/menu_items/1748175359_1000_F_75894966_grMyKtLrU2dDQzMNKxtkN9PfDxg5iQpE.jpg', 1, 0, 1, '2024-12-22 03:35:00', '2025-05-25 12:15:59'),
(50, 5, 'Cheese Burger', 'Beef patty burger with melted cheese and toppings.', 65.00, '/capstone/uploads/menu_items/1748174551_pexels-griffinw-2657960.jpg', 1, 0, 0, '2024-05-26 04:37:00', '2025-05-25 12:02:31'),
(51, 6, 'Chocolate Shake', 'Rich chocolate-flavored cold drink.', 40.00, '/capstone/uploads/menu_items/1748175089_ChocolateMilkshake-Featured-300x300.jpg', 1, 0, 1, '2024-06-03 07:16:00', '2025-05-25 12:11:29'),
(52, 6, 'Cookies and Cream Shake', 'Creamy blend with crushed cookies.', 40.00, '/capstone/uploads/menu_items/1748175128_istockphoto-1388569821-612x612.jpg', 1, 0, 3, '2024-06-16 10:00:00', '2025-05-25 12:12:08'),
(53, NULL, 'Dynamite', 'Cheese-stuffed chili peppers wrapped in spring roll wrapper.', 10.00, '/capstone/uploads/menu_items/1748172692_Dynamit-Okra-scaled.jpg', 1, 0, 3, '2024-01-01 04:12:00', '2025-05-25 11:31:32'),
(54, 4, 'Extra Egg', 'One fried egg, perfect as a topping.', 15.00, '/capstone/uploads/menu_items/1748173877_two-eggs-fried-plate_908985-51554.jpg', 1, 0, 0, '2024-09-21 05:07:00', '2025-05-25 11:51:17'),
(55, 2, 'Hungarian', 'Spicy Hungarian sausage paired with rice.', 75.00, '/capstone/uploads/menu_items/1748172214_0002892_hungarian-sausages.jpeg', 1, 0, 2, '2024-09-16 10:35:00', '2025-05-25 11:23:34'),
(56, 7, 'Iced Americano', 'Espresso served over ice with water.', 45.00, '/capstone/uploads/menu_items/1748175376_istockphoto-1392510439-612x612.jpg', 1, 0, 2, '2024-03-15 10:42:00', '2025-05-25 12:16:16'),
(57, 4, 'Java Rice', 'Yellow rice with mild spices and savory seasoning.', 15.00, '/capstone/uploads/menu_items/1748173894_20533007_united_steak_extra_java_rice.webp', 1, 0, 2, '2024-02-27 09:39:00', '2025-05-25 11:51:34'),
(58, NULL, 'Kimchi Rice', 'Spicy Korean-style fermented cabbage fried with rice.', 40.00, '/capstone/uploads/menu_items/1748172659_istockphoto-174760556-612x612.jpg', 1, 0, 0, '2024-01-01 00:03:00', '2025-05-25 11:30:59'),
(59, 6, 'Mais Con Yelo', 'Shaved ice dessert with corn and sweet milk.', 40.00, '/capstone/uploads/menu_items/1748175140_Mais-con-Yelo-Feature.jpg', 1, 1, 4, '2024-03-31 06:52:00', '2025-05-28 03:11:02'),
(60, 2, 'Pares', 'Sweet soy-braised beef with rice and broth.', 40.00, '/capstone/uploads/menu_items/1748172251_Beef-Pares-1-scaled.jpeg', 1, 0, 4, '2024-09-11 07:32:00', '2025-05-25 11:24:11'),
(61, 4, 'Plain Rice', 'Basic steamed white rice.', 10.00, '/capstone/uploads/menu_items/1748173885_Plain_Rice_Cup_503x503.webp', 1, 0, 1, '2024-08-05 06:25:00', '2025-05-25 11:51:25'),
(62, 2, 'Pork Tonkatsu', 'Breaded and deep-fried pork cutlet with dipping sauce.', 100.00, '/capstone/uploads/menu_items/1748172198_ND-tonkatsu-hbtg-threeByTwoMediumAt2X.jpg', 1, 0, 1, '2024-05-21 11:48:00', '2025-05-25 11:23:18'),
(63, 6, 'Rocky Road Shake', 'Chocolate shake with marshmallows and nuts.', 40.00, '/capstone/uploads/menu_items/1748175102_cookies-and-cream-milkshake-9-683x1024.jpg', 1, 0, 2, '2024-02-28 05:49:00', '2025-05-25 12:11:42'),
(64, 1, 'Sisig', 'Chopped pork with spices and calamansi, served sizzling with egg.', 70.00, '/capstone/uploads/menu_items/1748170579_pexels-mark-john-hilario-264144764-30355484.jpg', 1, 1, 0, '2024-05-08 00:40:00', '2025-05-27 14:57:41'),
(65, 1, 'Spam', 'Fried slices of savory Spam.', 70.00, '/capstone/uploads/menu_items/1748171830_Spamsilog-1024x1280.jpg', 1, 0, 2, '2024-05-04 11:49:00', '2025-05-25 11:17:10'),
(66, NULL, 'Spam / Half Hungarian', 'Your choice of Spam slices or half Hungarian sausage.', 30.00, '/capstone/uploads/menu_items/1748172680_a7d398d5e86014bb03dfeb2a371c10ce.jpg', 1, 0, 2, '2024-10-31 07:19:00', '2025-05-25 11:31:20'),
(67, 6, 'Strawberry Shake', 'Refreshing fruity strawberry shake.', 40.00, '/capstone/uploads/menu_items/1748175076_istockphoto-184129563-612x612.jpg', 1, 0, 0, '2024-02-10 10:34:00', '2025-05-25 12:11:16'),
(68, 1, 'Tocino', 'Sweet cured pork with a sticky glaze.', 65.00, '/capstone/uploads/menu_items/1748171638_RB-Pork-Tocino-czgq-videoSixteenByNineJumbo1600-v2.jpg', 1, 0, 1, '2024-12-27 06:11:00', '2025-05-25 11:13:58'),
(69, NULL, 'Veggie Rice', 'Rice stir-fried with assorted vegetables.', 40.00, '/capstone/uploads/menu_items/1748172666_best-fried-rice.jpg', 1, 0, 1, '2024-11-13 05:30:00', '2025-05-25 11:31:06'),
(92, 1, 'Bistek', 'Beef steak marinated in soy sauce and calamansi, sautéed with onions.', 75.00, '/capstone/uploads/menu_items/1748171838_b5318405e36335e00e82b10ffcf7b439fff513af-2500x1600.jpg', 1, 0, 3, '2025-05-25 10:42:45', '2025-05-25 11:17:18'),
(93, 1, 'Longganisa', 'Sweet and garlicky Filipino-style pork sausage.', 70.00, '/capstone/uploads/menu_items/1748171845_homemade-skinless-longganisa-640.jpg', 1, 0, 4, '2025-05-25 10:42:45', '2025-05-25 11:17:25'),
(94, 2, 'Chicken Ala King', 'Creamy chicken with bell peppers and vegetables.', 70.00, '/capstone/uploads/menu_items/1748172258_chicken-ala-king-scaled.jpg', 1, 0, 5, '2025-05-25 10:42:45', '2025-05-25 11:24:18'),
(95, NULL, 'Siomai / Lumpia', 'Steamed pork dumplings or crispy spring rolls.', 25.00, '/capstone/uploads/menu_items/1748172703_pexels-benidiktus-hermanto-2827671-5347054.jpg', 1, 0, 4, '2025-05-25 10:42:45', '2025-05-25 11:31:43'),
(96, 4, 'Garlic Rice', 'Fried rice infused with garlic flavor.', 15.00, '/capstone/uploads/menu_items/1748173865_Filipino-Sinangag-Garlic-Fried-Rice-min.jpg', 1, 0, 3, '2025-05-25 10:42:45', '2025-05-25 11:52:19'),
(97, 5, 'Fries Platter', 'Generous portion of crispy golden fries.', 50.00, '/capstone/uploads/menu_items/1748174570_istockphoto-1443993866-612x612.jpg', 1, 0, 1, '2025-05-25 10:42:45', '2025-05-25 12:02:50'),
(98, 5, 'Nachos Fries', 'French fries topped with nacho cheese and sauces.', 85.00, '/capstone/uploads/menu_items/1748174581_Nachos Fries.JPG', 1, 0, 2, '2025-05-25 10:42:45', '2025-05-25 12:03:01'),
(99, 5, 'Nachos', 'Corn chips with meat, cheese, and salsa toppings.', 60.00, '/capstone/uploads/menu_items/1748174590_cover-6.jpg', 1, 0, 3, '2025-05-25 10:42:45', '2025-05-25 12:03:10'),
(100, 5, 'Chips Nachos (Mr. Chips / Chippy)', 'Pre-packaged chips used as base for nacho toppings.', 35.00, '/capstone/uploads/menu_items/1748174638_474484258_1146144806866520_6308930645400727786_n.jpg', 1, 0, 4, '2025-05-25 10:42:45', '2025-05-25 12:03:58'),
(101, 6, 'Coke / Mt. Dew / Pepsi', 'Cold soft drink choices.', 20.00, '/capstone/uploads/menu_items/1748175152_37133469-sabah-malaysia-january-13-2015-bottle-of-coca-cola-pepsi-and-mountain-dew-drink-isolated-on.jpg', 1, 0, 5, '2025-05-25 10:42:45', '2025-05-25 12:12:32'),
(102, 7, 'Milo', 'Classic choco-malt drink, hot or iced.', 25.00, '/capstone/uploads/menu_items/1748175482_Banana Smoothie Image smaller.jpg', 1, 0, 3, '2025-05-25 10:42:45', '2025-05-25 12:18:02'),
(103, 7, 'Dark Choco', 'Deep, rich hot chocolate.', 30.00, '/capstone/uploads/menu_items/1748175427_pieces-dark-chocolate-fall-into-600nw-2446312189.webp', 1, 0, 4, '2025-05-25 10:42:45', '2025-05-25 12:17:07'),
(104, 7, 'Iced Spanish Latte', 'Sweet and creamy iced latte with milk and espresso.', 60.00, '/capstone/uploads/menu_items/1748175435_Spanish-Iced-Latte.jpg', 1, 0, 5, '2025-05-25 10:42:45', '2025-05-25 12:17:15');

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

--
-- Dumping data for table `monthly_sales`
--

INSERT INTO `monthly_sales` (`monthly_sales_id`, `year`, `month`, `total_orders`, `total_sales`, `created_at`, `updated_at`) VALUES
(1, 2019, 2, 15, 1955.00, '2019-02-08 00:01:00', '2025-05-27 12:58:09'),
(2, 2020, 3, 3, 375.00, '2020-03-15 02:30:00', '2025-05-27 12:58:09'),
(3, 2020, 5, 2, 215.00, '2020-05-22 01:15:00', '2025-05-27 12:58:09'),
(4, 2020, 7, 1, 150.00, '2020-07-10 08:45:00', '2025-05-27 12:58:09'),
(5, 2020, 9, 2, 175.00, '2020-09-18 00:30:00', '2025-05-27 12:58:09'),
(6, 2020, 11, 1, 120.00, '2020-11-25 02:00:00', '2025-05-27 12:58:09'),
(7, 2020, 12, 1, 90.00, '2020-12-31 11:30:00', '2025-05-27 12:58:09'),
(8, 2021, 1, 1, 140.00, '2021-01-15 03:30:00', '2025-05-27 12:58:09'),
(9, 2021, 2, 1, 160.00, '2021-02-14 10:45:00', '2025-05-27 12:58:09'),
(10, 2021, 3, 1, 210.00, '2021-03-20 04:15:00', '2025-05-27 12:58:09'),
(11, 2021, 4, 1, 80.00, '2021-04-25 06:30:00', '2025-05-27 12:58:09'),
(12, 2021, 5, 1, 60.00, '2021-05-30 08:00:00', '2025-05-27 12:58:09'),
(13, 2021, 6, 1, 195.00, '2021-06-15 02:45:00', '2025-05-27 12:58:09'),
(14, 2021, 7, 1, 75.00, '2021-07-20 05:30:00', '2025-05-27 12:58:09'),
(15, 2021, 8, 1, 80.00, '2021-08-25 07:15:00', '2025-05-27 12:58:09'),
(16, 2021, 9, 1, 70.00, '2021-09-30 09:45:00', '2025-05-27 12:58:09'),
(17, 2021, 10, 1, 170.00, '2021-10-31 11:00:00', '2025-05-27 12:58:09'),
(18, 2022, 1, 1, 260.00, '2022-01-10 01:30:00', '2025-05-27 12:58:09'),
(19, 2022, 2, 1, 140.00, '2022-02-20 03:15:00', '2025-05-27 12:58:09'),
(20, 2022, 3, 1, 120.00, '2022-03-25 05:45:00', '2025-05-27 12:58:09'),
(21, 2022, 4, 1, 120.00, '2022-04-30 07:30:00', '2025-05-27 12:58:09'),
(22, 2022, 5, 1, 140.00, '2022-05-15 09:00:00', '2025-05-27 12:58:09'),
(23, 2022, 6, 1, 80.00, '2022-06-20 04:30:00', '2025-05-27 12:58:09'),
(24, 2022, 7, 1, 120.00, '2022-07-25 06:15:00', '2025-05-27 12:58:09'),
(25, 2022, 8, 1, 100.00, '2022-08-30 08:45:00', '2025-05-27 12:58:09'),
(26, 2022, 9, 1, 75.00, '2022-09-15 10:30:00', '2025-05-27 12:58:09'),
(27, 2022, 10, 1, 200.00, '2022-10-20 02:15:00', '2025-05-27 12:58:09'),
(28, 2023, 1, 1, 210.00, '2023-01-15 02:30:00', '2025-05-27 12:58:09'),
(29, 2023, 2, 1, 200.00, '2023-02-20 04:45:00', '2025-05-27 12:58:09'),
(30, 2023, 3, 1, 160.00, '2023-03-25 06:15:00', '2025-05-27 12:58:09'),
(31, 2023, 4, 1, 140.00, '2023-04-30 08:30:00', '2025-05-27 12:58:09'),
(32, 2023, 5, 1, 135.00, '2023-05-15 03:00:00', '2025-05-27 12:58:09'),
(33, 2023, 6, 1, 100.00, '2023-06-20 05:30:00', '2025-05-27 12:58:09'),
(34, 2023, 7, 1, 160.00, '2023-07-25 07:45:00', '2025-05-27 12:58:09'),
(35, 2023, 8, 1, 120.00, '2023-08-30 09:15:00', '2025-05-27 12:58:09'),
(36, 2023, 9, 1, 75.00, '2023-09-15 04:00:00', '2025-05-27 12:58:09'),
(37, 2023, 10, 1, 90.00, '2023-10-20 06:30:00', '2025-05-27 12:58:09'),
(38, 2024, 1, 1, 325.00, '2024-01-10 01:15:00', '2025-05-27 12:58:09'),
(39, 2024, 2, 1, 140.00, '2024-02-15 03:30:00', '2025-05-27 12:58:09'),
(40, 2024, 3, 1, 140.00, '2024-03-20 05:45:00', '2025-05-27 12:58:09'),
(41, 2024, 4, 1, 195.00, '2024-04-25 07:00:00', '2025-05-27 12:58:09'),
(42, 2024, 5, 1, 140.00, '2024-05-30 08:30:00', '2025-05-27 12:58:09'),
(43, 2024, 6, 1, 160.00, '2024-06-15 04:15:00', '2025-05-27 12:58:09'),
(44, 2024, 7, 1, 75.00, '2024-07-20 06:45:00', '2025-05-27 12:58:09'),
(45, 2024, 8, 1, 125.00, '2024-08-25 08:00:00', '2025-05-27 12:58:09'),
(46, 2024, 9, 1, 120.00, '2024-09-30 09:30:00', '2025-05-27 12:58:09'),
(47, 2024, 10, 1, 120.00, '2024-10-15 03:45:00', '2025-05-27 12:58:09'),
(48, 2024, 11, 1, 280.00, '2024-11-20 05:15:00', '2025-05-27 12:58:09'),
(49, 2024, 12, 1, 90.00, '2024-12-25 10:00:00', '2025-05-27 12:58:09'),
(50, 2025, 1, 5, 725.00, '2025-01-05 02:30:00', '2025-05-27 12:58:09'),
(51, 2025, 2, 5, 785.00, '2025-02-01 05:30:00', '2025-05-27 12:58:09'),
(52, 2025, 3, 5, 555.00, '2025-03-01 02:15:00', '2025-05-27 12:58:09'),
(53, 2025, 4, 5, 540.00, '2025-04-01 05:15:00', '2025-05-27 12:58:09'),
(54, 2025, 5, 6, 1040.00, '2025-05-01 02:45:00', '2025-05-27 12:58:09');

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
  `discount_type` varchar(50) DEFAULT 'none',
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `original_amount` decimal(10,2) DEFAULT NULL,
  `discount_notes` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `queue_number`, `table_id`, `status_id`, `total_amount`, `discount_type`, `discount_percentage`, `discount_amount`, `original_amount`, `discount_notes`, `notes`, `created_at`, `updated_at`) VALUES
(1, '20190208-001', NULL, 5, 280.00, 'none', 0.00, 0.00, 280.00, NULL, 'Imported from CSV - 1 items', '2019-02-08 00:01:00', '2019-02-08 00:01:00'),
(2, '20190208-002', NULL, 5, 140.00, 'none', 0.00, 0.00, 140.00, NULL, 'Imported from CSV - 1 items', '2019-02-08 00:09:00', '2019-02-08 00:09:00'),
(3, '20190208-003', NULL, 5, 200.00, 'none', 0.00, 0.00, 200.00, NULL, 'Imported from CSV - 1 items', '2019-02-08 02:44:00', '2019-02-08 02:44:00'),
(4, '20190208-004', NULL, 5, 30.00, 'none', 0.00, 0.00, 30.00, NULL, 'Imported from CSV - 1 items', '2019-02-08 04:23:00', '2019-02-08 04:23:00'),
(5, '20190208-005', NULL, 5, 80.00, 'none', 0.00, 0.00, 80.00, NULL, 'Imported from CSV - 1 items', '2019-02-08 04:36:00', '2019-02-08 04:36:00'),
(6, '20190208-006', NULL, 5, 25.00, 'none', 0.00, 0.00, 25.00, NULL, 'Imported from CSV - 1 items', '2019-02-08 04:43:00', '2019-02-08 04:43:00'),
(7, '20190208-007', NULL, 5, 90.00, 'none', 0.00, 0.00, 90.00, NULL, 'Imported from CSV - 1 items', '2019-02-08 06:10:00', '2019-02-08 06:10:00'),
(8, '20190208-008', NULL, 5, 40.00, 'none', 0.00, 0.00, 40.00, NULL, 'Imported from CSV - 1 items', '2019-02-08 07:05:00', '2019-02-08 07:05:00'),
(9, '20190208-009', NULL, 5, 10.00, 'none', 0.00, 0.00, 10.00, NULL, 'Imported from CSV - 1 items', '2019-02-08 08:39:00', '2019-02-08 08:39:00'),
(10, '20190208-010', NULL, 5, 120.00, 'none', 0.00, 0.00, 120.00, NULL, 'Imported from CSV - 1 items', '2019-02-08 09:15:00', '2019-02-08 09:15:00'),
(11, '20190208-011', NULL, 5, 260.00, 'none', 0.00, 0.00, 260.00, NULL, 'Imported from CSV - 1 items', '2019-02-08 09:16:00', '2019-02-08 09:16:00'),
(12, '20190208-012', NULL, 5, 30.00, 'none', 0.00, 0.00, 30.00, NULL, 'Imported from CSV - 1 items', '2019-02-08 09:36:00', '2019-02-08 09:36:00'),
(13, '20190208-013', NULL, 5, 350.00, 'none', 0.00, 0.00, 350.00, NULL, 'Imported from CSV - 1 items', '2019-02-08 09:48:00', '2019-02-08 09:48:00'),
(14, '20190208-014', NULL, 5, 200.00, 'none', 0.00, 0.00, 200.00, NULL, 'Imported from CSV - 1 items', '2019-02-08 09:55:00', '2019-02-08 09:55:00'),
(15, '20190208-015', NULL, 5, 100.00, 'none', 0.00, 0.00, 100.00, NULL, 'Imported from CSV - 1 items', '2019-02-08 10:37:00', '2019-02-08 10:37:00'),
(16, '20200315-016', NULL, 5, 195.00, 'none', 0.00, 0.00, 195.00, NULL, 'Imported from CSV - 1 items', '2020-03-15 02:30:00', '2020-03-15 02:30:00'),
(17, '20200315-017', NULL, 5, 100.00, 'none', 0.00, 0.00, 100.00, NULL, 'Imported from CSV - 1 items', '2020-03-15 03:45:00', '2020-03-15 03:45:00'),
(18, '20200315-018', NULL, 5, 80.00, 'none', 0.00, 0.00, 80.00, NULL, 'Imported from CSV - 1 items', '2020-03-15 06:20:00', '2020-03-15 06:20:00'),
(19, '20200522-019', NULL, 5, 140.00, 'none', 0.00, 0.00, 140.00, NULL, 'Imported from CSV - 1 items', '2020-05-22 01:15:00', '2020-05-22 01:15:00'),
(20, '20200522-020', NULL, 5, 75.00, 'none', 0.00, 0.00, 75.00, NULL, 'Imported from CSV - 1 items', '2020-05-22 04:30:00', '2020-05-22 04:30:00'),
(21, '20200710-021', NULL, 5, 150.00, 'none', 0.00, 0.00, 150.00, NULL, 'Imported from CSV - 1 items', '2020-07-10 08:45:00', '2020-07-10 08:45:00'),
(22, '20200918-022', NULL, 5, 75.00, 'none', 0.00, 0.00, 75.00, NULL, 'Imported from CSV - 1 items', '2020-09-18 00:30:00', '2020-09-18 00:30:00'),
(23, '20200918-023', NULL, 5, 100.00, 'none', 0.00, 0.00, 100.00, NULL, 'Imported from CSV - 1 items', '2020-09-18 05:15:00', '2020-09-18 05:15:00'),
(24, '20201125-024', NULL, 5, 120.00, 'none', 0.00, 0.00, 120.00, NULL, 'Imported from CSV - 1 items', '2020-11-25 02:00:00', '2020-11-25 02:00:00'),
(25, '20201231-025', NULL, 5, 90.00, 'none', 0.00, 0.00, 90.00, NULL, 'Imported from CSV - 1 items', '2020-12-31 11:30:00', '2020-12-31 11:30:00'),
(26, '20210115-026', NULL, 5, 140.00, 'none', 0.00, 0.00, 140.00, NULL, 'Imported from CSV - 1 items', '2021-01-15 03:30:00', '2021-01-15 03:30:00'),
(27, '20210214-027', NULL, 5, 160.00, 'none', 0.00, 0.00, 160.00, NULL, 'Imported from CSV - 1 items', '2021-02-14 10:45:00', '2021-02-14 10:45:00'),
(28, '20210320-028', NULL, 5, 210.00, 'none', 0.00, 0.00, 210.00, NULL, 'Imported from CSV - 1 items', '2021-03-20 04:15:00', '2021-03-20 04:15:00'),
(29, '20210425-029', NULL, 5, 80.00, 'none', 0.00, 0.00, 80.00, NULL, 'Imported from CSV - 1 items', '2021-04-25 06:30:00', '2021-04-25 06:30:00'),
(30, '20210530-030', NULL, 5, 60.00, 'none', 0.00, 0.00, 60.00, NULL, 'Imported from CSV - 1 items', '2021-05-30 08:00:00', '2021-05-30 08:00:00'),
(31, '20210615-031', NULL, 5, 195.00, 'none', 0.00, 0.00, 195.00, NULL, 'Imported from CSV - 1 items', '2021-06-15 02:45:00', '2021-06-15 02:45:00'),
(32, '20210720-032', NULL, 5, 75.00, 'none', 0.00, 0.00, 75.00, NULL, 'Imported from CSV - 1 items', '2021-07-20 05:30:00', '2021-07-20 05:30:00'),
(33, '20210825-033', NULL, 5, 80.00, 'none', 0.00, 0.00, 80.00, NULL, 'Imported from CSV - 1 items', '2021-08-25 07:15:00', '2021-08-25 07:15:00'),
(34, '20210930-034', NULL, 5, 70.00, 'none', 0.00, 0.00, 70.00, NULL, 'Imported from CSV - 1 items', '2021-09-30 09:45:00', '2021-09-30 09:45:00'),
(35, '20211031-035', NULL, 5, 170.00, 'none', 0.00, 0.00, 170.00, NULL, 'Imported from CSV - 1 items', '2021-10-31 11:00:00', '2021-10-31 11:00:00'),
(36, '20220110-036', NULL, 5, 260.00, 'none', 0.00, 0.00, 260.00, NULL, 'Imported from CSV - 1 items', '2022-01-10 01:30:00', '2022-01-10 01:30:00'),
(37, '20220220-037', NULL, 5, 140.00, 'none', 0.00, 0.00, 140.00, NULL, 'Imported from CSV - 1 items', '2022-02-20 03:15:00', '2022-02-20 03:15:00'),
(38, '20220325-038', NULL, 5, 120.00, 'none', 0.00, 0.00, 120.00, NULL, 'Imported from CSV - 1 items', '2022-03-25 05:45:00', '2022-03-25 05:45:00'),
(39, '20220430-039', NULL, 5, 120.00, 'none', 0.00, 0.00, 120.00, NULL, 'Imported from CSV - 1 items', '2022-04-30 07:30:00', '2022-04-30 07:30:00'),
(40, '20220515-040', NULL, 5, 140.00, 'none', 0.00, 0.00, 140.00, NULL, 'Imported from CSV - 1 items', '2022-05-15 09:00:00', '2022-05-15 09:00:00'),
(41, '20220620-041', NULL, 5, 80.00, 'none', 0.00, 0.00, 80.00, NULL, 'Imported from CSV - 1 items', '2022-06-20 04:30:00', '2022-06-20 04:30:00'),
(42, '20220725-042', NULL, 5, 120.00, 'none', 0.00, 0.00, 120.00, NULL, 'Imported from CSV - 1 items', '2022-07-25 06:15:00', '2022-07-25 06:15:00'),
(43, '20220830-043', NULL, 5, 100.00, 'none', 0.00, 0.00, 100.00, NULL, 'Imported from CSV - 1 items', '2022-08-30 08:45:00', '2022-08-30 08:45:00'),
(44, '20220915-044', NULL, 5, 75.00, 'none', 0.00, 0.00, 75.00, NULL, 'Imported from CSV - 1 items', '2022-09-15 10:30:00', '2022-09-15 10:30:00'),
(45, '20221020-045', NULL, 5, 200.00, 'none', 0.00, 0.00, 200.00, NULL, 'Imported from CSV - 1 items', '2022-10-20 02:15:00', '2022-10-20 02:15:00'),
(46, '20230115-046', NULL, 5, 210.00, 'none', 0.00, 0.00, 210.00, NULL, 'Imported from CSV - 1 items', '2023-01-15 02:30:00', '2023-01-15 02:30:00'),
(47, '20230220-047', NULL, 5, 200.00, 'none', 0.00, 0.00, 200.00, NULL, 'Imported from CSV - 1 items', '2023-02-20 04:45:00', '2023-02-20 04:45:00'),
(48, '20230325-048', NULL, 5, 160.00, 'none', 0.00, 0.00, 160.00, NULL, 'Imported from CSV - 1 items', '2023-03-25 06:15:00', '2023-03-25 06:15:00'),
(49, '20230430-049', NULL, 5, 140.00, 'none', 0.00, 0.00, 140.00, NULL, 'Imported from CSV - 1 items', '2023-04-30 08:30:00', '2023-04-30 08:30:00'),
(50, '20230515-050', NULL, 5, 135.00, 'none', 0.00, 0.00, 135.00, NULL, 'Imported from CSV - 1 items', '2023-05-15 03:00:00', '2023-05-15 03:00:00'),
(51, '20230620-051', NULL, 5, 100.00, 'none', 0.00, 0.00, 100.00, NULL, 'Imported from CSV - 1 items', '2023-06-20 05:30:00', '2023-06-20 05:30:00'),
(52, '20230725-052', NULL, 5, 160.00, 'none', 0.00, 0.00, 160.00, NULL, 'Imported from CSV - 1 items', '2023-07-25 07:45:00', '2023-07-25 07:45:00'),
(53, '20230830-053', NULL, 5, 120.00, 'none', 0.00, 0.00, 120.00, NULL, 'Imported from CSV - 1 items', '2023-08-30 09:15:00', '2023-08-30 09:15:00'),
(54, '20230915-054', NULL, 5, 75.00, 'none', 0.00, 0.00, 75.00, NULL, 'Imported from CSV - 1 items', '2023-09-15 04:00:00', '2023-09-15 04:00:00'),
(55, '20231020-055', NULL, 5, 90.00, 'none', 0.00, 0.00, 90.00, NULL, 'Imported from CSV - 1 items', '2023-10-20 06:30:00', '2023-10-20 06:30:00'),
(56, '20240110-056', NULL, 5, 325.00, 'none', 0.00, 0.00, 325.00, NULL, 'Imported from CSV - 1 items', '2024-01-10 01:15:00', '2024-01-10 01:15:00'),
(57, '20240215-057', NULL, 5, 140.00, 'none', 0.00, 0.00, 140.00, NULL, 'Imported from CSV - 1 items', '2024-02-15 03:30:00', '2024-02-15 03:30:00'),
(58, '20240320-058', NULL, 5, 140.00, 'none', 0.00, 0.00, 140.00, NULL, 'Imported from CSV - 1 items', '2024-03-20 05:45:00', '2024-03-20 05:45:00'),
(59, '20240425-059', NULL, 5, 195.00, 'none', 0.00, 0.00, 195.00, NULL, 'Imported from CSV - 1 items', '2024-04-25 07:00:00', '2024-04-25 07:00:00'),
(60, '20240530-060', NULL, 5, 140.00, 'none', 0.00, 0.00, 140.00, NULL, 'Imported from CSV - 1 items', '2024-05-30 08:30:00', '2024-05-30 08:30:00'),
(61, '20240615-061', NULL, 5, 160.00, 'none', 0.00, 0.00, 160.00, NULL, 'Imported from CSV - 1 items', '2024-06-15 04:15:00', '2024-06-15 04:15:00'),
(62, '20240720-062', NULL, 5, 75.00, 'none', 0.00, 0.00, 75.00, NULL, 'Imported from CSV - 1 items', '2024-07-20 06:45:00', '2024-07-20 06:45:00'),
(63, '20240825-063', NULL, 5, 125.00, 'none', 0.00, 0.00, 125.00, NULL, 'Imported from CSV - 1 items', '2024-08-25 08:00:00', '2024-08-25 08:00:00'),
(64, '20240930-064', NULL, 5, 120.00, 'none', 0.00, 0.00, 120.00, NULL, 'Imported from CSV - 1 items', '2024-09-30 09:30:00', '2024-09-30 09:30:00'),
(65, '20241015-065', NULL, 5, 120.00, 'none', 0.00, 0.00, 120.00, NULL, 'Imported from CSV - 1 items', '2024-10-15 03:45:00', '2024-10-15 03:45:00'),
(66, '20241120-066', NULL, 5, 280.00, 'none', 0.00, 0.00, 280.00, NULL, 'Imported from CSV - 1 items', '2024-11-20 05:15:00', '2024-11-20 05:15:00'),
(67, '20241225-067', NULL, 5, 90.00, 'none', 0.00, 0.00, 90.00, NULL, 'Imported from CSV - 1 items', '2024-12-25 10:00:00', '2024-12-25 10:00:00'),
(68, '20250105-068', NULL, 5, 210.00, 'none', 0.00, 0.00, 210.00, NULL, 'Imported from CSV - 1 items', '2025-01-05 02:30:00', '2025-01-05 02:30:00'),
(69, '20250110-069', NULL, 5, 100.00, 'none', 0.00, 0.00, 100.00, NULL, 'Imported from CSV - 1 items', '2025-01-10 04:15:00', '2025-01-10 04:15:00'),
(70, '20250115-070', NULL, 5, 80.00, 'none', 0.00, 0.00, 80.00, NULL, 'Imported from CSV - 1 items', '2025-01-15 06:45:00', '2025-01-15 06:45:00'),
(71, '20250120-071', NULL, 5, 260.00, 'none', 0.00, 0.00, 260.00, NULL, 'Imported from CSV - 1 items', '2025-01-20 08:30:00', '2025-01-20 08:30:00'),
(72, '20250125-072', NULL, 5, 75.00, 'none', 0.00, 0.00, 75.00, NULL, 'Imported from CSV - 1 items', '2025-01-25 03:00:00', '2025-01-25 03:00:00'),
(73, '20250201-073', NULL, 5, 140.00, 'none', 0.00, 0.00, 140.00, NULL, 'Imported from CSV - 1 items', '2025-02-01 05:30:00', '2025-02-01 05:30:00'),
(74, '20250205-074', NULL, 5, 200.00, 'none', 0.00, 0.00, 200.00, NULL, 'Imported from CSV - 1 items', '2025-02-05 07:15:00', '2025-02-05 07:15:00'),
(75, '20250210-075', NULL, 5, 90.00, 'none', 0.00, 0.00, 90.00, NULL, 'Imported from CSV - 1 items', '2025-02-10 09:00:00', '2025-02-10 09:00:00'),
(76, '20250215-076', NULL, 5, 195.00, 'none', 0.00, 0.00, 195.00, NULL, 'Imported from CSV - 1 items', '2025-02-15 04:45:00', '2025-02-15 04:45:00'),
(77, '20250220-077', NULL, 5, 160.00, 'none', 0.00, 0.00, 160.00, NULL, 'Imported from CSV - 1 items', '2025-02-20 06:30:00', '2025-02-20 06:30:00'),
(78, '20250301-078', NULL, 5, 140.00, 'none', 0.00, 0.00, 140.00, NULL, 'Imported from CSV - 1 items', '2025-03-01 02:15:00', '2025-03-01 02:15:00'),
(79, '20250305-079', NULL, 5, 75.00, 'none', 0.00, 0.00, 75.00, NULL, 'Imported from CSV - 1 items', '2025-03-05 04:00:00', '2025-03-05 04:00:00'),
(80, '20250310-080', NULL, 5, 120.00, 'none', 0.00, 0.00, 120.00, NULL, 'Imported from CSV - 1 items', '2025-03-10 06:15:00', '2025-03-10 06:15:00'),
(81, '20250315-081', NULL, 5, 100.00, 'none', 0.00, 0.00, 100.00, NULL, 'Imported from CSV - 1 items', '2025-03-15 08:45:00', '2025-03-15 08:45:00'),
(82, '20250320-082', NULL, 5, 120.00, 'none', 0.00, 0.00, 120.00, NULL, 'Imported from CSV - 1 items', '2025-03-20 03:30:00', '2025-03-20 03:30:00'),
(83, '20250401-083', NULL, 5, 75.00, 'none', 0.00, 0.00, 75.00, NULL, 'Imported from CSV - 1 items', '2025-04-01 05:15:00', '2025-04-01 05:15:00'),
(84, '20250405-084', NULL, 5, 140.00, 'none', 0.00, 0.00, 140.00, NULL, 'Imported from CSV - 1 items', '2025-04-05 07:30:00', '2025-04-05 07:30:00'),
(85, '20250410-085', NULL, 5, 100.00, 'none', 0.00, 0.00, 100.00, NULL, 'Imported from CSV - 1 items', '2025-04-10 09:15:00', '2025-04-10 09:15:00'),
(86, '20250415-086', NULL, 5, 120.00, 'none', 0.00, 0.00, 120.00, NULL, 'Imported from CSV - 1 items', '2025-04-15 04:30:00', '2025-04-15 04:30:00'),
(87, '20250420-087', NULL, 5, 105.00, 'none', 0.00, 0.00, 105.00, NULL, 'Imported from CSV - 1 items', '2025-04-20 06:45:00', '2025-04-20 06:45:00'),
(88, '20250501-088', NULL, 5, 210.00, 'none', 0.00, 0.00, 210.00, NULL, 'Imported from CSV - 1 items', '2025-05-01 02:45:00', '2025-05-01 02:45:00'),
(89, '20250505-089', NULL, 5, 130.00, 'none', 0.00, 0.00, 130.00, NULL, 'Imported from CSV - 1 items', '2025-05-05 04:30:00', '2025-05-05 04:30:00'),
(90, '20250510-090', NULL, 5, 280.00, 'none', 0.00, 0.00, 280.00, NULL, 'Imported from CSV - 1 items', '2025-05-10 06:15:00', '2025-05-10 06:15:00'),
(91, '20250515-091', NULL, 5, 100.00, 'none', 0.00, 0.00, 100.00, NULL, 'Imported from CSV - 1 items', '2025-05-15 08:00:00', '2025-05-15 08:00:00'),
(92, '20250520-092', NULL, 5, 200.00, 'none', 0.00, 0.00, 200.00, NULL, 'Imported from CSV - 1 items', '2025-05-20 03:15:00', '2025-05-20 03:15:00'),
(93, '20250525-093', NULL, 5, 120.00, 'none', 0.00, 0.00, 120.00, NULL, 'Imported from CSV - 1 items', '2025-05-25 05:45:00', '2025-05-25 05:45:00'),
(128, '20250527-179', NULL, 5, 420.00, 'none', 0.00, 0.00, NULL, NULL, ' [Auto-flagged: Statistical outlier]', '2025-05-27 13:07:32', '2025-05-27 13:08:11'),
(129, '20250527-753', NULL, 5, 70.00, 'none', 0.00, 0.00, NULL, NULL, '', '2025-05-27 14:57:50', '2025-05-27 14:58:17'),
(130, '20250527-243', NULL, 5, 70.00, 'none', 0.00, 0.00, NULL, NULL, '', '2025-05-27 15:05:58', '2025-05-27 15:12:49'),
(131, '20250527-514', NULL, 5, 360.00, 'none', 0.00, 0.00, NULL, NULL, '', '2025-05-27 15:12:32', '2025-05-27 15:12:46'),
(132, '20250527-303', NULL, 5, 240.00, 'none', 0.00, 0.00, NULL, NULL, '', '2025-05-27 15:13:13', '2025-05-27 15:13:27');

-- --------------------------------------------------------

--
-- Table structure for table `order_discounts`
--

CREATE TABLE `order_discounts` (
  `discount_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `discount_type` enum('percentage','fixed','custom') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `discount_name` varchar(100) DEFAULT NULL,
  `applied_by` int(11) DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
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

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `item_id`, `variation_id`, `quantity`, `unit_price`, `subtotal`, `notes`, `created_at`) VALUES
(1, 1, 64, NULL, 4, 70.00, 280.00, 'Imported from CSV', '2019-02-08 00:01:00'),
(2, 2, 65, NULL, 2, 70.00, 140.00, 'Imported from CSV', '2019-02-08 00:09:00'),
(3, 3, 60, NULL, 5, 40.00, 200.00, 'Imported from CSV', '2019-02-08 02:44:00'),
(4, 4, 57, NULL, 2, 15.00, 30.00, 'Imported from CSV', '2019-02-08 04:23:00'),
(5, 5, 63, NULL, 2, 40.00, 80.00, 'Imported from CSV', '2019-02-08 04:36:00'),
(6, 6, 49, NULL, 1, 25.00, 25.00, 'Imported from CSV', '2019-02-08 04:43:00'),
(7, 7, 56, NULL, 2, 45.00, 90.00, 'Imported from CSV', '2019-02-08 06:10:00'),
(8, 8, 59, NULL, 1, 40.00, 40.00, 'Imported from CSV', '2019-02-08 07:05:00'),
(9, 9, 53, NULL, 1, 10.00, 10.00, 'Imported from CSV', '2019-02-08 08:39:00'),
(10, 10, 59, NULL, 3, 40.00, 120.00, 'Imported from CSV', '2019-02-08 09:15:00'),
(11, 11, 50, NULL, 4, 65.00, 260.00, 'Imported from CSV', '2019-02-08 09:16:00'),
(12, 12, 54, NULL, 2, 15.00, 30.00, 'Imported from CSV', '2019-02-08 09:36:00'),
(13, 13, 64, NULL, 5, 70.00, 350.00, 'Imported from CSV', '2019-02-08 09:48:00'),
(14, 14, 51, NULL, 5, 40.00, 200.00, 'Imported from CSV', '2019-02-08 09:55:00'),
(15, 15, 48, NULL, 5, 20.00, 100.00, 'Imported from CSV', '2019-02-08 10:37:00'),
(16, 16, 68, NULL, 3, 65.00, 195.00, 'Imported from CSV', '2020-03-15 02:30:00'),
(17, 17, 62, NULL, 1, 100.00, 100.00, 'Imported from CSV', '2020-03-15 03:45:00'),
(18, 18, 67, NULL, 2, 40.00, 80.00, 'Imported from CSV', '2020-03-15 06:20:00'),
(19, 19, 93, NULL, 2, 70.00, 140.00, 'Imported from CSV', '2020-05-22 01:15:00'),
(20, 20, 55, NULL, 1, 75.00, 75.00, 'Imported from CSV', '2020-05-22 04:30:00'),
(21, 21, 97, NULL, 3, 50.00, 150.00, 'Imported from CSV', '2020-07-10 08:45:00'),
(22, 22, 92, NULL, 1, 75.00, 75.00, 'Imported from CSV', '2020-09-18 00:30:00'),
(23, 23, 102, NULL, 4, 25.00, 100.00, 'Imported from CSV', '2020-09-18 05:15:00'),
(24, 24, 99, NULL, 2, 60.00, 120.00, 'Imported from CSV', '2020-11-25 02:00:00'),
(25, 25, 103, NULL, 3, 30.00, 90.00, 'Imported from CSV', '2020-12-31 11:30:00'),
(26, 26, 65, NULL, 2, 70.00, 140.00, 'Imported from CSV', '2021-01-15 03:30:00'),
(27, 27, 51, NULL, 4, 40.00, 160.00, 'Imported from CSV', '2021-02-14 10:45:00'),
(28, 28, 64, NULL, 3, 70.00, 210.00, 'Imported from CSV', '2021-03-20 04:15:00'),
(29, 29, 60, NULL, 2, 40.00, 80.00, 'Imported from CSV', '2021-04-25 06:30:00'),
(30, 30, 104, NULL, 1, 60.00, 60.00, 'Imported from CSV', '2021-05-30 08:00:00'),
(31, 31, 50, NULL, 3, 65.00, 195.00, 'Imported from CSV', '2021-06-15 02:45:00'),
(32, 32, 96, NULL, 5, 15.00, 75.00, 'Imported from CSV', '2021-07-20 05:30:00'),
(33, 33, 52, NULL, 2, 40.00, 80.00, 'Imported from CSV', '2021-08-25 07:15:00'),
(34, 34, 94, NULL, 1, 70.00, 70.00, 'Imported from CSV', '2021-09-30 09:45:00'),
(35, 35, 98, NULL, 2, 85.00, 170.00, 'Imported from CSV', '2021-10-31 11:00:00'),
(36, 36, 68, NULL, 4, 65.00, 260.00, 'Imported from CSV', '2022-01-10 01:30:00'),
(37, 37, 47, NULL, 2, 70.00, 140.00, 'Imported from CSV', '2022-02-20 03:15:00'),
(38, 38, 48, NULL, 6, 20.00, 120.00, 'Imported from CSV', '2022-03-25 05:45:00'),
(39, 39, 67, NULL, 3, 40.00, 120.00, 'Imported from CSV', '2022-04-30 07:30:00'),
(40, 40, 93, NULL, 2, 70.00, 140.00, 'Imported from CSV', '2022-05-15 09:00:00'),
(41, 41, 61, NULL, 8, 10.00, 80.00, 'Imported from CSV', '2022-06-20 04:30:00'),
(42, 42, 69, NULL, 3, 40.00, 120.00, 'Imported from CSV', '2022-07-25 06:15:00'),
(43, 43, 49, NULL, 4, 25.00, 100.00, 'Imported from CSV', '2022-08-30 08:45:00'),
(44, 44, 55, NULL, 1, 75.00, 75.00, 'Imported from CSV', '2022-09-15 10:30:00'),
(45, 45, 59, NULL, 5, 40.00, 200.00, 'Imported from CSV', '2022-10-20 02:15:00'),
(46, 46, 64, NULL, 3, 70.00, 210.00, 'Imported from CSV', '2023-01-15 02:30:00'),
(47, 47, 62, NULL, 2, 100.00, 200.00, 'Imported from CSV', '2023-02-20 04:45:00'),
(48, 48, 51, NULL, 4, 40.00, 160.00, 'Imported from CSV', '2023-03-25 06:15:00'),
(49, 49, 65, NULL, 2, 70.00, 140.00, 'Imported from CSV', '2023-04-30 08:30:00'),
(50, 50, 56, NULL, 3, 45.00, 135.00, 'Imported from CSV', '2023-05-15 03:00:00'),
(51, 51, 97, NULL, 2, 50.00, 100.00, 'Imported from CSV', '2023-06-20 05:30:00'),
(52, 52, 60, NULL, 4, 40.00, 160.00, 'Imported from CSV', '2023-07-25 07:45:00'),
(53, 53, 63, NULL, 3, 40.00, 120.00, 'Imported from CSV', '2023-08-30 09:15:00'),
(54, 54, 92, NULL, 1, 75.00, 75.00, 'Imported from CSV', '2023-09-15 04:00:00'),
(55, 55, 54, NULL, 6, 15.00, 90.00, 'Imported from CSV', '2023-10-20 06:30:00'),
(56, 56, 68, NULL, 5, 65.00, 325.00, 'Imported from CSV', '2024-01-10 01:15:00'),
(57, 57, 94, NULL, 2, 70.00, 140.00, 'Imported from CSV', '2024-02-15 03:30:00'),
(58, 58, 48, NULL, 7, 20.00, 140.00, 'Imported from CSV', '2024-03-20 05:45:00'),
(59, 59, 50, NULL, 3, 65.00, 195.00, 'Imported from CSV', '2024-04-25 07:00:00'),
(60, 60, 93, NULL, 2, 70.00, 140.00, 'Imported from CSV', '2024-05-30 08:30:00'),
(61, 61, 67, NULL, 4, 40.00, 160.00, 'Imported from CSV', '2024-06-15 04:15:00'),
(62, 62, 55, NULL, 1, 75.00, 75.00, 'Imported from CSV', '2024-07-20 06:45:00'),
(63, 63, 102, NULL, 5, 25.00, 125.00, 'Imported from CSV', '2024-08-25 08:00:00'),
(64, 64, 99, NULL, 2, 60.00, 120.00, 'Imported from CSV', '2024-09-30 09:30:00'),
(65, 65, 96, NULL, 8, 15.00, 120.00, 'Imported from CSV', '2024-10-15 03:45:00'),
(66, 66, 64, NULL, 4, 70.00, 280.00, 'Imported from CSV', '2024-11-20 05:15:00'),
(67, 67, 103, NULL, 3, 30.00, 90.00, 'Imported from CSV', '2024-12-25 10:00:00'),
(68, 68, 65, NULL, 3, 70.00, 210.00, 'Imported from CSV', '2025-01-05 02:30:00'),
(69, 69, 62, NULL, 1, 100.00, 100.00, 'Imported from CSV', '2025-01-10 04:15:00'),
(70, 70, 51, NULL, 2, 40.00, 80.00, 'Imported from CSV', '2025-01-15 06:45:00'),
(71, 71, 68, NULL, 4, 65.00, 260.00, 'Imported from CSV', '2025-01-20 08:30:00'),
(72, 72, 49, NULL, 3, 25.00, 75.00, 'Imported from CSV', '2025-01-25 03:00:00'),
(73, 73, 64, NULL, 2, 70.00, 140.00, 'Imported from CSV', '2025-02-01 05:30:00'),
(74, 74, 60, NULL, 5, 40.00, 200.00, 'Imported from CSV', '2025-02-05 07:15:00'),
(75, 75, 56, NULL, 2, 45.00, 90.00, 'Imported from CSV', '2025-02-10 09:00:00'),
(76, 76, 50, NULL, 3, 65.00, 195.00, 'Imported from CSV', '2025-02-15 04:45:00'),
(77, 77, 67, NULL, 4, 40.00, 160.00, 'Imported from CSV', '2025-02-20 06:30:00'),
(78, 78, 93, NULL, 2, 70.00, 140.00, 'Imported from CSV', '2025-03-01 02:15:00'),
(79, 79, 55, NULL, 1, 75.00, 75.00, 'Imported from CSV', '2025-03-05 04:00:00'),
(80, 80, 48, NULL, 6, 20.00, 120.00, 'Imported from CSV', '2025-03-10 06:15:00'),
(81, 81, 97, NULL, 2, 50.00, 100.00, 'Imported from CSV', '2025-03-15 08:45:00'),
(82, 82, 63, NULL, 3, 40.00, 120.00, 'Imported from CSV', '2025-03-20 03:30:00'),
(83, 83, 92, NULL, 1, 75.00, 75.00, 'Imported from CSV', '2025-04-01 05:15:00'),
(84, 84, 94, NULL, 2, 70.00, 140.00, 'Imported from CSV', '2025-04-05 07:30:00'),
(85, 85, 102, NULL, 4, 25.00, 100.00, 'Imported from CSV', '2025-04-10 09:15:00'),
(86, 86, 99, NULL, 2, 60.00, 120.00, 'Imported from CSV', '2025-04-15 04:30:00'),
(87, 87, 54, NULL, 7, 15.00, 105.00, 'Imported from CSV', '2025-04-20 06:45:00'),
(88, 88, 65, NULL, 3, 70.00, 210.00, 'Imported from CSV', '2025-05-01 02:45:00'),
(89, 89, 68, NULL, 2, 65.00, 130.00, 'Imported from CSV', '2025-05-05 04:30:00'),
(90, 90, 64, NULL, 4, 70.00, 280.00, 'Imported from CSV', '2025-05-10 06:15:00'),
(91, 91, 62, NULL, 1, 100.00, 100.00, 'Imported from CSV', '2025-05-15 08:00:00'),
(92, 92, 51, NULL, 5, 40.00, 200.00, 'Imported from CSV', '2025-05-20 03:15:00'),
(93, 93, 104, NULL, 2, 60.00, 120.00, 'Imported from CSV', '2025-05-25 05:45:00'),
(128, 128, 100, NULL, 12, 35.00, 420.00, NULL, '2025-05-27 13:07:32'),
(129, 129, 64, NULL, 1, 70.00, 70.00, NULL, '2025-05-27 14:57:50'),
(130, 130, 64, NULL, 1, 70.00, 70.00, NULL, '2025-05-27 15:05:58'),
(131, 131, 59, NULL, 9, 40.00, 360.00, NULL, '2025-05-27 15:12:32'),
(132, 132, 59, NULL, 6, 40.00, 240.00, NULL, '2025-05-27 15:13:13');

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
(1, 1, 5, 'Imported order - automatically completed', '2019-02-08 00:01:00'),
(2, 2, 5, 'Imported order - automatically completed', '2019-02-08 00:09:00'),
(3, 3, 5, 'Imported order - automatically completed', '2019-02-08 02:44:00'),
(4, 4, 5, 'Imported order - automatically completed', '2019-02-08 04:23:00'),
(5, 5, 5, 'Imported order - automatically completed', '2019-02-08 04:36:00'),
(6, 6, 5, 'Imported order - automatically completed', '2019-02-08 04:43:00'),
(7, 7, 5, 'Imported order - automatically completed', '2019-02-08 06:10:00'),
(8, 8, 5, 'Imported order - automatically completed', '2019-02-08 07:05:00'),
(9, 9, 5, 'Imported order - automatically completed', '2019-02-08 08:39:00'),
(10, 10, 5, 'Imported order - automatically completed', '2019-02-08 09:15:00'),
(11, 11, 5, 'Imported order - automatically completed', '2019-02-08 09:16:00'),
(12, 12, 5, 'Imported order - automatically completed', '2019-02-08 09:36:00'),
(13, 13, 5, 'Imported order - automatically completed', '2019-02-08 09:48:00'),
(14, 14, 5, 'Imported order - automatically completed', '2019-02-08 09:55:00'),
(15, 15, 5, 'Imported order - automatically completed', '2019-02-08 10:37:00'),
(16, 16, 5, 'Imported order - automatically completed', '2020-03-15 02:30:00'),
(17, 17, 5, 'Imported order - automatically completed', '2020-03-15 03:45:00'),
(18, 18, 5, 'Imported order - automatically completed', '2020-03-15 06:20:00'),
(19, 19, 5, 'Imported order - automatically completed', '2020-05-22 01:15:00'),
(20, 20, 5, 'Imported order - automatically completed', '2020-05-22 04:30:00'),
(21, 21, 5, 'Imported order - automatically completed', '2020-07-10 08:45:00'),
(22, 22, 5, 'Imported order - automatically completed', '2020-09-18 00:30:00'),
(23, 23, 5, 'Imported order - automatically completed', '2020-09-18 05:15:00'),
(24, 24, 5, 'Imported order - automatically completed', '2020-11-25 02:00:00'),
(25, 25, 5, 'Imported order - automatically completed', '2020-12-31 11:30:00'),
(26, 26, 5, 'Imported order - automatically completed', '2021-01-15 03:30:00'),
(27, 27, 5, 'Imported order - automatically completed', '2021-02-14 10:45:00'),
(28, 28, 5, 'Imported order - automatically completed', '2021-03-20 04:15:00'),
(29, 29, 5, 'Imported order - automatically completed', '2021-04-25 06:30:00'),
(30, 30, 5, 'Imported order - automatically completed', '2021-05-30 08:00:00'),
(31, 31, 5, 'Imported order - automatically completed', '2021-06-15 02:45:00'),
(32, 32, 5, 'Imported order - automatically completed', '2021-07-20 05:30:00'),
(33, 33, 5, 'Imported order - automatically completed', '2021-08-25 07:15:00'),
(34, 34, 5, 'Imported order - automatically completed', '2021-09-30 09:45:00'),
(35, 35, 5, 'Imported order - automatically completed', '2021-10-31 11:00:00'),
(36, 36, 5, 'Imported order - automatically completed', '2022-01-10 01:30:00'),
(37, 37, 5, 'Imported order - automatically completed', '2022-02-20 03:15:00'),
(38, 38, 5, 'Imported order - automatically completed', '2022-03-25 05:45:00'),
(39, 39, 5, 'Imported order - automatically completed', '2022-04-30 07:30:00'),
(40, 40, 5, 'Imported order - automatically completed', '2022-05-15 09:00:00'),
(41, 41, 5, 'Imported order - automatically completed', '2022-06-20 04:30:00'),
(42, 42, 5, 'Imported order - automatically completed', '2022-07-25 06:15:00'),
(43, 43, 5, 'Imported order - automatically completed', '2022-08-30 08:45:00'),
(44, 44, 5, 'Imported order - automatically completed', '2022-09-15 10:30:00'),
(45, 45, 5, 'Imported order - automatically completed', '2022-10-20 02:15:00'),
(46, 46, 5, 'Imported order - automatically completed', '2023-01-15 02:30:00'),
(47, 47, 5, 'Imported order - automatically completed', '2023-02-20 04:45:00'),
(48, 48, 5, 'Imported order - automatically completed', '2023-03-25 06:15:00'),
(49, 49, 5, 'Imported order - automatically completed', '2023-04-30 08:30:00'),
(50, 50, 5, 'Imported order - automatically completed', '2023-05-15 03:00:00'),
(51, 51, 5, 'Imported order - automatically completed', '2023-06-20 05:30:00'),
(52, 52, 5, 'Imported order - automatically completed', '2023-07-25 07:45:00'),
(53, 53, 5, 'Imported order - automatically completed', '2023-08-30 09:15:00'),
(54, 54, 5, 'Imported order - automatically completed', '2023-09-15 04:00:00'),
(55, 55, 5, 'Imported order - automatically completed', '2023-10-20 06:30:00'),
(56, 56, 5, 'Imported order - automatically completed', '2024-01-10 01:15:00'),
(57, 57, 5, 'Imported order - automatically completed', '2024-02-15 03:30:00'),
(58, 58, 5, 'Imported order - automatically completed', '2024-03-20 05:45:00'),
(59, 59, 5, 'Imported order - automatically completed', '2024-04-25 07:00:00'),
(60, 60, 5, 'Imported order - automatically completed', '2024-05-30 08:30:00'),
(61, 61, 5, 'Imported order - automatically completed', '2024-06-15 04:15:00'),
(62, 62, 5, 'Imported order - automatically completed', '2024-07-20 06:45:00'),
(63, 63, 5, 'Imported order - automatically completed', '2024-08-25 08:00:00'),
(64, 64, 5, 'Imported order - automatically completed', '2024-09-30 09:30:00'),
(65, 65, 5, 'Imported order - automatically completed', '2024-10-15 03:45:00'),
(66, 66, 5, 'Imported order - automatically completed', '2024-11-20 05:15:00'),
(67, 67, 5, 'Imported order - automatically completed', '2024-12-25 10:00:00'),
(68, 68, 5, 'Imported order - automatically completed', '2025-01-05 02:30:00'),
(69, 69, 5, 'Imported order - automatically completed', '2025-01-10 04:15:00'),
(70, 70, 5, 'Imported order - automatically completed', '2025-01-15 06:45:00'),
(71, 71, 5, 'Imported order - automatically completed', '2025-01-20 08:30:00'),
(72, 72, 5, 'Imported order - automatically completed', '2025-01-25 03:00:00'),
(73, 73, 5, 'Imported order - automatically completed', '2025-02-01 05:30:00'),
(74, 74, 5, 'Imported order - automatically completed', '2025-02-05 07:15:00'),
(75, 75, 5, 'Imported order - automatically completed', '2025-02-10 09:00:00'),
(76, 76, 5, 'Imported order - automatically completed', '2025-02-15 04:45:00'),
(77, 77, 5, 'Imported order - automatically completed', '2025-02-20 06:30:00'),
(78, 78, 5, 'Imported order - automatically completed', '2025-03-01 02:15:00'),
(79, 79, 5, 'Imported order - automatically completed', '2025-03-05 04:00:00'),
(80, 80, 5, 'Imported order - automatically completed', '2025-03-10 06:15:00'),
(81, 81, 5, 'Imported order - automatically completed', '2025-03-15 08:45:00'),
(82, 82, 5, 'Imported order - automatically completed', '2025-03-20 03:30:00'),
(83, 83, 5, 'Imported order - automatically completed', '2025-04-01 05:15:00'),
(84, 84, 5, 'Imported order - automatically completed', '2025-04-05 07:30:00'),
(85, 85, 5, 'Imported order - automatically completed', '2025-04-10 09:15:00'),
(86, 86, 5, 'Imported order - automatically completed', '2025-04-15 04:30:00'),
(87, 87, 5, 'Imported order - automatically completed', '2025-04-20 06:45:00'),
(88, 88, 5, 'Imported order - automatically completed', '2025-05-01 02:45:00'),
(89, 89, 5, 'Imported order - automatically completed', '2025-05-05 04:30:00'),
(90, 90, 5, 'Imported order - automatically completed', '2025-05-10 06:15:00'),
(91, 91, 5, 'Imported order - automatically completed', '2025-05-15 08:00:00'),
(92, 92, 5, 'Imported order - automatically completed', '2025-05-20 03:15:00'),
(93, 93, 5, 'Imported order - automatically completed', '2025-05-25 05:45:00'),
(128, 128, 1, 'Order placed', '2025-05-27 13:07:32'),
(129, 128, 2, 'Order marked as paid by counter', '2025-05-27 13:07:39'),
(130, 128, 3, 'Order preparation started', '2025-05-27 13:07:46'),
(131, 128, 4, 'Order ready for pickup', '2025-05-27 13:07:47'),
(132, 128, 5, 'Order marked as completed by counter', '2025-05-27 13:07:54'),
(133, 129, 1, 'Order placed', '2025-05-27 14:57:50'),
(134, 129, 2, 'Order marked as paid by counter', '2025-05-27 14:58:00'),
(135, 129, 3, 'Order preparation started', '2025-05-27 14:58:11'),
(136, 129, 4, 'Order ready for pickup', '2025-05-27 14:58:12'),
(137, 129, 5, 'Order marked as completed by counter', '2025-05-27 14:58:17'),
(138, 130, 1, 'Order placed', '2025-05-27 15:05:58'),
(139, 130, 2, 'Order marked as paid by counter', '2025-05-27 15:06:02'),
(140, 130, 2, 'Order marked as paid by counter', '2025-05-27 15:06:26'),
(141, 131, 1, 'Order placed', '2025-05-27 15:12:32'),
(142, 131, 2, 'Order marked as paid by counter', '2025-05-27 15:12:36'),
(143, 130, 3, 'Order preparation started', '2025-05-27 15:12:40'),
(144, 131, 3, 'Order preparation started', '2025-05-27 15:12:40'),
(145, 131, 4, 'Order ready for pickup', '2025-05-27 15:12:42'),
(146, 130, 4, 'Order ready for pickup', '2025-05-27 15:12:43'),
(147, 131, 5, 'Order marked as completed by counter', '2025-05-27 15:12:46'),
(148, 130, 5, 'Order marked as completed by counter', '2025-05-27 15:12:49'),
(149, 132, 1, 'Order placed', '2025-05-27 15:13:13'),
(150, 132, 2, 'Order marked as paid by counter', '2025-05-27 15:13:17'),
(151, 132, 3, 'Order preparation started', '2025-05-27 15:13:21'),
(152, 132, 4, 'Order ready for pickup', '2025-05-27 15:13:22'),
(153, 132, 5, 'Order marked as completed by counter', '2025-05-27 15:13:27');

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
-- Dumping data for table `yearly_sales`
--

INSERT INTO `yearly_sales` (`yearly_sales_id`, `year`, `total_orders`, `total_sales`, `created_at`, `updated_at`) VALUES
(1, 2019, 15, 1955.00, '2019-02-08 00:01:00', '2025-05-27 12:58:09'),
(2, 2020, 10, 1125.00, '2020-03-15 02:30:00', '2025-05-27 12:58:09'),
(3, 2021, 10, 1240.00, '2021-01-15 03:30:00', '2025-05-27 12:58:09'),
(4, 2022, 10, 1355.00, '2022-01-10 01:30:00', '2025-05-27 12:58:09'),
(5, 2023, 10, 1390.00, '2023-01-15 02:30:00', '2025-05-27 12:58:09'),
(6, 2024, 12, 1910.00, '2024-01-10 01:15:00', '2025-05-27 12:58:09'),
(7, 2025, 26, 3645.00, '2025-01-05 02:30:00', '2025-05-27 12:58:09');

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
-- Indexes for table `cash_float`
--
ALTER TABLE `cash_float`
  ADD PRIMARY KEY (`float_id`),
  ADD UNIQUE KEY `unique_date_active` (`date`,`status`),
  ADD KEY `date` (`date`),
  ADD KEY `set_by_admin_id` (`set_by_admin_id`),
  ADD KEY `closed_by_admin_id` (`closed_by_admin_id`),
  ADD KEY `idx_cash_float_date_status` (`date`,`status`);

--
-- Indexes for table `cash_transactions`
--
ALTER TABLE `cash_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `float_id` (`float_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `counter_user_id` (`counter_user_id`),
  ADD KEY `transaction_type` (`transaction_type`);

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
-- Indexes for table `discount_presets`
--
ALTER TABLE `discount_presets`
  ADD PRIMARY KEY (`preset_id`),
  ADD UNIQUE KEY `preset_key` (`preset_key`);

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
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_menu_items_best_seller` (`is_best_seller`);

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
-- Indexes for table `order_discounts`
--
ALTER TABLE `order_discounts`
  ADD PRIMARY KEY (`discount_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `applied_by` (`applied_by`);

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
-- AUTO_INCREMENT for table `cash_float`
--
ALTER TABLE `cash_float`
  MODIFY `float_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cash_transactions`
--
ALTER TABLE `cash_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `counter_users`
--
ALTER TABLE `counter_users`
  MODIFY `counter_user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `daily_sales`
--
ALTER TABLE `daily_sales`
  MODIFY `daily_sales_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `discount_presets`
--
ALTER TABLE `discount_presets`
  MODIFY `preset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  MODIFY `popularity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

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
  MODIFY `monthly_sales_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `order_discounts`
--
ALTER TABLE `order_discounts`
  MODIFY `discount_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

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
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `table_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `yearly_sales`
--
ALTER TABLE `yearly_sales`
  MODIFY `yearly_sales_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cash_float`
--
ALTER TABLE `cash_float`
  ADD CONSTRAINT `cash_float_ibfk_1` FOREIGN KEY (`set_by_admin_id`) REFERENCES `admin_users` (`admin_id`),
  ADD CONSTRAINT `cash_float_ibfk_2` FOREIGN KEY (`closed_by_admin_id`) REFERENCES `admin_users` (`admin_id`);

--
-- Constraints for table `cash_transactions`
--
ALTER TABLE `cash_transactions`
  ADD CONSTRAINT `cash_transactions_ibfk_1` FOREIGN KEY (`float_id`) REFERENCES `cash_float` (`float_id`),
  ADD CONSTRAINT `cash_transactions_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `cash_transactions_ibfk_3` FOREIGN KEY (`counter_user_id`) REFERENCES `counter_users` (`counter_user_id`);

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

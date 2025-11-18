-- Feedback System Database Structure
-- This file contains the SQL statements to create the feedback system tables

USE `restaurant_ordering_system`;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `table_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `food_quality_rating` int(1) NOT NULL CHECK (`food_quality_rating` >= 1 AND `food_quality_rating` <= 5),
  `food_quality_comments` text DEFAULT NULL,
  `service_quality_rating` int(1) NOT NULL CHECK (`service_quality_rating` >= 1 AND `service_quality_rating` <= 5),
  `service_quality_comments` text DEFAULT NULL,
  `venue_quality_rating` int(1) NOT NULL CHECK (`venue_quality_rating` >= 1 AND `venue_quality_rating` <= 5),
  `venue_quality_comments` text DEFAULT NULL,
  `reservation_experience` enum('not_applicable', 'did_not_use', 'used_system') NOT NULL DEFAULT 'not_applicable',
  `reservation_comments` text DEFAULT NULL,
  `overall_rating` decimal(2,1) GENERATED ALWAYS AS ((`food_quality_rating` + `service_quality_rating` + `venue_quality_rating`) / 3) STORED,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `admin_notes` text DEFAULT NULL,
  `status` enum('pending', 'reviewed', 'responded', 'archived') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`feedback_id`),
  KEY `order_id` (`order_id`),
  KEY `table_id` (`table_id`),
  KEY `created_at` (`created_at`),
  KEY `overall_rating` (`overall_rating`),
  KEY `status` (`status`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL,
  CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`table_id`) REFERENCES `tables` (`table_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_categories`
--

CREATE TABLE `feedback_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`category_id`)
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
  `response_id` int(11) NOT NULL AUTO_INCREMENT,
  `feedback_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `response_text` text NOT NULL,
  `response_type` enum('acknowledgment', 'follow_up', 'resolution') NOT NULL DEFAULT 'acknowledgment',
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`response_id`),
  KEY `feedback_id` (`feedback_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `feedback_responses_ibfk_1` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`feedback_id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_responses_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_analytics`
--

CREATE TABLE `feedback_analytics` (
  `analytics_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`analytics_id`),
  UNIQUE KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Sample data for testing (optional)
--

-- Sample feedback entries for testing
INSERT INTO `feedback` (`order_id`, `table_id`, `customer_name`, `customer_email`, `food_quality_rating`, `food_quality_comments`, `service_quality_rating`, `service_quality_comments`, `venue_quality_rating`, `venue_quality_comments`, `reservation_experience`, `reservation_comments`, `is_anonymous`, `status`) VALUES
(NULL, 1, 'John Doe', 'john.doe@email.com', 5, 'Excellent taste and presentation!', 4, 'Staff was very friendly and attentive.', 5, 'Clean and comfortable environment.', 'did_not_use', NULL, 0, 'reviewed'),
(NULL, 2, 'Jane Smith', 'jane.smith@email.com', 4, 'Good food, could be warmer.', 5, 'Outstanding service!', 4, 'Nice ambiance, could use better lighting.', 'not_applicable', NULL, 0, 'pending'),
(NULL, 3, NULL, NULL, 3, 'Average food quality.', 3, 'Service was okay.', 2, 'Venue needs cleaning.', 'used_system', 'Reservation system worked well.', 1, 'pending');

-- --------------------------------------------------------

--
-- Indexes for better performance
--

-- Additional indexes for common queries
CREATE INDEX `idx_feedback_ratings` ON `feedback` (`food_quality_rating`, `service_quality_rating`, `venue_quality_rating`);
CREATE INDEX `idx_feedback_date_status` ON `feedback` (`created_at`, `status`);
CREATE INDEX `idx_feedback_overall_rating` ON `feedback` (`overall_rating` DESC);

-- --------------------------------------------------------

--
-- Views for common queries
--

-- View for feedback summary
CREATE VIEW `feedback_summary` AS
SELECT 
    DATE(created_at) as feedback_date,
    COUNT(*) as total_feedback,
    AVG(food_quality_rating) as avg_food_rating,
    AVG(service_quality_rating) as avg_service_rating,
    AVG(venue_quality_rating) as avg_venue_rating,
    AVG(overall_rating) as avg_overall_rating,
    COUNT(CASE WHEN overall_rating >= 4.0 THEN 1 END) as positive_feedback,
    COUNT(CASE WHEN overall_rating < 3.0 THEN 1 END) as negative_feedback,
    COUNT(CASE WHEN reservation_experience = 'used_system' THEN 1 END) as reservation_usage
FROM feedback 
GROUP BY DATE(created_at)
ORDER BY feedback_date DESC;

-- View for recent feedback with details
CREATE VIEW `recent_feedback` AS
SELECT 
    f.feedback_id,
    f.customer_name,
    f.customer_email,
    f.food_quality_rating,
    f.service_quality_rating,
    f.venue_quality_rating,
    f.overall_rating,
    f.reservation_experience,
    f.status,
    f.created_at,
    t.table_number,
    o.queue_number
FROM feedback f
LEFT JOIN tables t ON f.table_id = t.table_id
LEFT JOIN orders o ON f.order_id = o.order_id
ORDER BY f.created_at DESC;

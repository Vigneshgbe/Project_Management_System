-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2026 at 09:35 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `projects_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `details`, `created_at`) VALUES
(1, 3, 'login', 'user', 3, '', '2026-04-21 08:42:18'),
(2, 3, 'login', 'user', 3, '', '2026-04-22 20:48:35'),
(3, 3, 'generated leads', 'Hotel in Lalgudi', 0, '0 leads found', '2026-04-22 21:30:50'),
(4, 3, 'generated leads', 'Restorants in Lalgudi', 0, '0 leads found', '2026-04-22 21:31:26'),
(5, 3, 'login', 'user', 3, '', '2026-04-23 09:21:27'),
(6, 3, 'login', 'user', 3, '', '2026-04-24 07:42:58'),
(7, 3, 'generated payslip', 'Vignesh G', 1, '', '2026-04-24 09:26:34'),
(8, 3, 'login', 'user', 3, '', '2026-04-24 12:59:07'),
(9, 3, 'generated leads', 'restaurants in Trichy', 0, '5 leads', '2026-04-24 14:04:16'),
(10, 3, 'chatbot settings saved', '', 0, '', '2026-04-24 14:45:42'),
(11, 3, 'chatbot message', '', 0, 'session 1', '2026-04-24 14:47:47'),
(12, 3, 'generated leads', 'Graphics Design in Batticola', 0, '5 leads', '2026-04-24 15:18:43'),
(13, 3, 'bulk import leads', '', 0, '5 leads', '2026-04-24 15:19:01'),
(14, 3, 'generated leads', 'Web Development in Trichy', 0, '1 leads', '2026-04-24 15:45:13'),
(15, 3, 'login', 'user', 3, '', '2026-04-25 10:56:20'),
(16, 3, 'generated leads', 'Fitness center in Trichy', 0, '1 leads', '2026-04-25 11:04:35'),
(17, 3, 'generated leads', 'Supermarket chain in Trichy', 0, '1 leads', '2026-04-25 11:06:06'),
(18, 3, 'generated leads', 'Event hall in Colombo', 0, '1 leads', '2026-04-25 11:41:36'),
(19, 3, 'generated leads', 'Restaurant in Colombo', 0, '1 leads', '2026-04-25 11:43:42'),
(20, 3, 'generated leads', 'Restaurant in Batticaloa', 0, '1 leads', '2026-04-25 11:45:18'),
(21, 3, 'generated leads', 'Restaurant in Lalgudi', 0, '5 leads', '2026-04-25 12:33:38'),
(28, 3, 'generated leads', 'Educational institutes in Chennai', 0, '3 leads', '2026-04-26 10:23:23'),
(29, 3, 'login', 'user', 3, '', '2026-04-26 12:24:37'),
(30, 3, 'generated leads', 'Institutes in Colombo', 0, '0 leads', '2026-04-26 12:41:45'),
(31, 3, 'generated leads', 'Institutes in Colombo', 0, '0 leads', '2026-04-26 12:43:16'),
(32, 3, 'generated leads', 'Startups in Colombo', 0, '0 leads', '2026-04-26 12:44:25'),
(33, 3, 'generated leads', 'Retail in Colombo', 0, '0 leads', '2026-04-26 12:44:42'),
(34, 3, 'generated leads', 'Small Businesses in Colombo', 0, '1 leads', '2026-04-26 12:45:03'),
(35, 3, 'generated leads', '(Pvt) Ltd in Colombo', 0, '0 leads', '2026-04-26 12:47:37'),
(36, 3, 'generated leads', 'Private Limited Company in Colombo', 0, '0 leads', '2026-04-26 12:48:21'),
(37, 3, 'generated leads', 'Small Businesses in Colombo', 0, '1 leads', '2026-04-26 12:48:30'),
(38, 3, 'generated leads', 'Small Businesses in Colombo', 0, '0 leads', '2026-04-26 12:49:34'),
(39, 3, 'login', 'user', 3, '', '2026-04-26 14:57:27'),
(40, 3, 'generated leads', 'IT Services in Tiruchirappalli, India', 0, '0 leads', '2026-04-26 15:55:27'),
(41, 3, 'generated leads', 'resort in Tiruchirappalli, India', 0, '1 leads', '2026-04-26 15:56:11'),
(44, 3, 'generated leads', 'Logistics company in Switzerland', 0, '1 leads', '2026-04-27 10:41:34'),
(45, 3, 'generated leads', 'Logistics company in Tiruchirappalli, India', 0, '0 leads', '2026-04-27 10:44:13'),
(46, 3, 'generated leads', 'Logistics company in Tamil Nadu, India', 0, '0 leads', '2026-04-27 10:44:49'),
(47, 3, 'generated leads', 'Logistics company in Puducherry, India', 0, '0 leads', '2026-04-27 10:45:18'),
(48, 3, 'generated leads', 'Logistics company in Pondicherry, India', 0, '0 leads', '2026-04-27 10:45:29'),
(49, 3, 'generated leads', 'company in Pondicherry, India', 0, '0 leads', '2026-04-27 10:46:24'),
(50, 3, 'generated leads', 'Software company in Tamil Nadu, India', 0, '0 leads', '2026-04-27 10:47:04'),
(51, 3, 'social draft', 'First Post Creation', 1, '', '2026-04-27 11:36:35'),
(52, 3, 'social published', 'First Post Creation', 0, '', '2026-04-27 12:17:14'),
(53, 3, 'created user', 'Member', 4, '', '2026-04-27 12:21:54'),
(54, 3, 'logout', 'user', 3, '', '2026-04-27 12:22:22'),
(55, 2, 'login', 'user', 2, '', '2026-04-27 12:22:31'),
(56, 2, 'generated leads', 'Private Limited in Singapore, Singapore', 0, '2 leads', '2026-04-27 13:05:20'),
(57, 2, 'generated leads', 'Wedding photographer in Tiruchirappalli, India', 0, '0 leads', '2026-04-27 13:16:11'),
(58, 2, 'generated leads', 'Wedding photographer in Trichy, India', 0, '1 leads', '2026-04-27 13:17:15'),
(59, 2, 'logout', 'user', 2, '', '2026-04-27 13:44:53'),
(60, 3, 'login', 'user', 3, '', '2026-04-27 13:45:01'),
(61, 3, 'generated leads', 'Private Limited in Europe, Romania', 0, '5 leads', '2026-04-27 13:48:20'),
(62, 3, 'generated leads', 'Private Limited in Europe, Romania', 0, '0 leads', '2026-04-27 13:50:02'),
(63, 3, 'login', 'user', 3, '', '2026-04-28 09:35:20'),
(64, 3, 'generated leads', 'Institute in Colombo, Sri Lanka', 0, '0 leads', '2026-04-28 09:41:03'),
(65, 3, 'generated leads', 'academy in Colombo, Sri Lanka', 0, '3 leads', '2026-04-28 09:42:00'),
(66, 3, 'updated task', 'Get Quotes from Client Blog', 1, '', '2026-04-28 11:06:00');

-- --------------------------------------------------------

--
-- Table structure for table `calendar_attendees`
--

CREATE TABLE `calendar_attendees` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rsvp` enum('pending','accepted','declined') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL,
  `title` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` enum('meeting','appointment','deadline','milestone','reminder','other') DEFAULT 'other',
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime DEFAULT NULL,
  `all_day` tinyint(1) DEFAULT 0,
  `location` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#f97316',
  `project_id` int(11) DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `recur` enum('none','daily','weekly','monthly') DEFAULT 'none',
  `created_by` int(11) NOT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_messages`
--

CREATE TABLE `chatbot_messages` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `role` enum('user','assistant') NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_messages`
--

INSERT INTO `chatbot_messages` (`id`, `session_id`, `role`, `content`, `created_at`) VALUES
(1, 1, 'user', 'What is Padak Pvt Ltd', '2026-04-24 09:17:47'),
(2, 1, 'assistant', 'Padak Pvt Ltd is the company that develops and provides the **Padak Internal Customer Relationship Management (CRM) system**.\n\nIn short, we are a software company specializing in CRM solutions designed to help businesses manage and analyze customer interactions and data throughout the customer lifecycle, with the goal of improving business relationships with customers, assisting in customer retention, and driving sales growth.', '2026-04-24 09:17:47');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_sessions`
--

CREATE TABLE `chatbot_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL DEFAULT 'Conversation',
  `msg_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_sessions`
--

INSERT INTO `chatbot_sessions` (`id`, `user_id`, `title`, `msg_count`, `created_at`, `updated_at`) VALUES
(1, 3, 'What is Padak Pvt Ltd', 2, '2026-04-24 09:17:43', '2026-04-24 09:17:47');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_settings`
--

CREATE TABLE `chatbot_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_val` text NOT NULL DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_settings`
--

INSERT INTO `chatbot_settings` (`id`, `setting_key`, `setting_val`, `updated_at`) VALUES
(1, 'gemini_api_key', 'AIzaSyD6TJRAl7BSxLFKEHtAFKnR6N-7Sf6pL0M', '2026-04-24 09:15:42'),
(2, 'daily_limit', '200', '2026-04-24 09:15:42');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_usage`
--

CREATE TABLE `chatbot_usage` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `msg_count` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_usage`
--

INSERT INTO `chatbot_usage` (`id`, `user_id`, `session_id`, `msg_count`, `created_at`) VALUES
(1, 3, 1, 1, '2026-04-24 09:17:47');

-- --------------------------------------------------------

--
-- Table structure for table `chat_channels`
--

CREATE TABLE `chat_channels` (
  `id` int(11) NOT NULL,
  `type` enum('direct','project','general','thread') DEFAULT 'general',
  `name` varchar(150) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_channels`
--

INSERT INTO `chat_channels` (`id`, `type`, `name`, `project_id`, `created_by`, `created_at`) VALUES
(1, 'general', 'general', NULL, 3, '2026-04-19 11:01:53'),
(2, 'direct', NULL, NULL, 3, '2026-04-19 21:57:52'),
(3, 'direct', NULL, NULL, 3, '2026-04-19 22:06:50');

-- --------------------------------------------------------

--
-- Table structure for table `chat_members`
--

CREATE TABLE `chat_members` (
  `id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_read` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_members`
--

INSERT INTO `chat_members` (`id`, `channel_id`, `user_id`, `last_read`) VALUES
(1, 1, 1, NULL),
(2, 1, 2, '2026-04-19 22:24:39'),
(3, 1, 3, '2026-04-28 10:43:42'),
(22, 2, 3, '2026-04-19 22:23:59'),
(23, 2, 1, NULL),
(37, 3, 3, '2026-04-19 22:34:08'),
(38, 3, 2, '2026-04-19 22:25:26');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `body` text NOT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `edited` tinyint(1) DEFAULT 0,
  `deleted` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `channel_id`, `user_id`, `parent_id`, `body`, `file_url`, `file_name`, `file_size`, `edited`, `deleted`, `created_at`, `updated_at`) VALUES
(2, 3, 3, NULL, 'Testing Chat Messages..!', NULL, NULL, NULL, 0, 0, '2026-04-19 22:07:37', '2026-04-19 22:07:37'),
(3, 3, 3, NULL, 'Sending Second Test message', NULL, NULL, NULL, 0, 0, '2026-04-19 22:15:17', '2026-04-19 22:15:17'),
(4, 3, 3, NULL, 'Sending Thirst Test Msg..', NULL, NULL, NULL, 0, 0, '2026-04-19 22:16:15', '2026-04-19 22:16:15'),
(5, 3, 2, 3, 'Worked at Fourth Time', NULL, NULL, NULL, 0, 0, '2026-04-19 22:25:10', '2026-04-19 22:25:10');

-- --------------------------------------------------------

--
-- Table structure for table `chat_reactions`
--

CREATE TABLE `chat_reactions` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `emoji` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_reactions`
--

INSERT INTO `chat_reactions` (`id`, `message_id`, `user_id`, `emoji`) VALUES
(1, 2, 2, '👍'),
(2, 4, 2, '👍');

-- --------------------------------------------------------

--
-- Table structure for table `client_messages`
--

CREATE TABLE `client_messages` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `sender_type` enum('client','staff') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `subject` varchar(250) DEFAULT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_portal`
--

CREATE TABLE `client_portal` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `token` varchar(64) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_portal`
--

INSERT INTO `client_portal` (`id`, `contact_id`, `email`, `password`, `status`, `last_login`, `token`, `token_expiry`, `created_at`) VALUES
(1, 1, 'padak.service@gmail.com', '$2y$10$e6.vvXVy7iMPSF63fF3AReH8Lg6sNrRGEDvWq.NwyR.TkLa5bYLei', 'active', '2026-04-28 10:10:14', NULL, NULL, '2026-04-19 23:10:40');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `company` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `type` enum('client','lead','partner','vendor') DEFAULT 'lead',
  `status` enum('active','inactive','prospect') DEFAULT 'prospect',
  `notes` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `company`, `email`, `phone`, `address`, `type`, `status`, `notes`, `assigned_to`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Padak', 'Slice of Life', 'padak.service@gmail.com', '+41 798235584', 'Testing Address', 'client', 'active', '', 1, 1, '2026-04-19 08:33:12', '2026-04-19 08:33:12');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `title` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_size` bigint(20) DEFAULT 0,
  `file_type` varchar(100) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT 'General',
  `access` enum('all','admin','manager') DEFAULT 'all',
  `uploaded_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `title`, `description`, `filename`, `original_name`, `file_size`, `file_type`, `project_id`, `contact_id`, `task_id`, `lead_id`, `category`, `access`, `uploaded_by`, `created_at`) VALUES
(1, 'Agreement Hypernova', 'This is my resume file', 'doc_69e445c51158a4.84392903.pdf', 'VigneshG_Software_Engineer_Resume.pdf', 54995, '0', 1, NULL, NULL, NULL, 'Development', 'manager', 1, '2026-04-19 08:32:29'),
(2, 'CRM AGREEMENT', 'Canada crm project agreement', 'doc_69e50c0bb80be1.07252161.pdf', 'CRM SOFTWARE DEVELOPMENT AGREEMENT.pdf', 155717, 'application/pdf', 2, NULL, NULL, NULL, 'Agreement', 'all', 3, '2026-04-19 22:38:27');

-- --------------------------------------------------------

--
-- Table structure for table `email_log`
--

CREATE TABLE `email_log` (
  `id` int(11) NOT NULL,
  `direction` enum('out','in') DEFAULT 'out',
  `subject` varchar(500) NOT NULL,
  `from_email` varchar(150) NOT NULL,
  `from_name` varchar(150) DEFAULT NULL,
  `to_email` text NOT NULL,
  `cc_email` text DEFAULT NULL,
  `bcc_email` text DEFAULT NULL,
  `body_html` mediumtext DEFAULT NULL,
  `body_text` text DEFAULT NULL,
  `status` enum('queued','sent','failed','draft') DEFAULT 'queued',
  `error_msg` text DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL,
  `message_id` varchar(255) DEFAULT NULL,
  `tracking_token` varchar(64) DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `opened_count` int(11) DEFAULT 0,
  `sent_by` int(11) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_log`
--

INSERT INTO `email_log` (`id`, `direction`, `subject`, `from_email`, `from_name`, `to_email`, `cc_email`, `bcc_email`, `body_html`, `body_text`, `status`, `error_msg`, `contact_id`, `lead_id`, `invoice_id`, `project_id`, `task_id`, `message_id`, `tracking_token`, `opened_at`, `opened_count`, `sent_by`, `sent_at`, `created_at`) VALUES
(4, 'out', 'Appointment Letter', 'noreply@thepadak.com', 'Internal CRM', '[\"vignesh0000g@gmail.com\"]', '[]', '[]', '<p>Testing Fourth Try</p>', 'Testing Fourth Try', 'failed', 'SMTP error: Path cannot be empty', NULL, NULL, NULL, NULL, NULL, '<crm_69e5be0a7775f3.90728277@localhost>', '631c5698265aa1290790fe18d27e3029', NULL, 0, 3, NULL, '2026-04-20 11:17:54'),
(7, 'out', 'Appointment Letter', 'noreply@thepadak.com', 'Internal CRM', '[\"vignesh0000g@gmail.com\"]', '[]', '[]', '<p>Testing Fourth Try</p>', 'Testing Fourth Try', 'sent', '', NULL, NULL, NULL, NULL, NULL, '<crm_69e5c2676c5843.77953877@localhost>', '6b5a4dc24af7d5c23ecaf1e71e79a23b', NULL, 0, 3, '2026-04-20 11:36:35', '2026-04-20 11:36:35');

-- --------------------------------------------------------

--
-- Table structure for table `email_settings`
--

CREATE TABLE `email_settings` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `from_name` varchar(100) NOT NULL,
  `from_email` varchar(150) NOT NULL,
  `host` varchar(150) DEFAULT NULL,
  `port` smallint(6) DEFAULT 587,
  `encryption` enum('tls','ssl','none') DEFAULT 'tls',
  `username` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_settings`
--

INSERT INTO `email_settings` (`id`, `name`, `from_name`, `from_email`, `host`, `port`, `encryption`, `username`, `password`, `is_default`, `is_active`, `created_at`) VALUES
(1, 'Gmail', 'Internal CRM', 'noreply@thepadak.com', 'smtp.gmail.com', 465, 'ssl', 'padak.service@gmail.com', 'bbgmcwabhburnisq', 1, 1, '2026-04-20 10:45:51');

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(80) DEFAULT 'General',
  `subject` varchar(500) NOT NULL,
  `body_html` mediumtext NOT NULL,
  `variables` text DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`id`, `name`, `category`, `subject`, `body_html`, `variables`, `is_system`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Task Assigned', 'Notifications', 'New Task Assigned: {{task}}', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px\">\r\n<h2 style=\"color:#f97316;margin-bottom:4px\">📋 New Task Assigned</h2>\r\n<p style=\"color:#666;margin-bottom:20px\">Hi {{name}},</p>\r\n<p>You have been assigned a new task:</p>\r\n<div style=\"background:#f5f5f5;border-left:4px solid #f97316;padding:14px 18px;margin:16px 0;border-radius:0 8px 8px 0\">\r\n  <strong style=\"font-size:16px\">{{task}}</strong>\r\n  <p style=\"margin:6px 0 0;color:#555\">Project: {{project}}</p>\r\n  <p style=\"margin:4px 0 0;color:#e44\">Due: {{due_date}}</p>\r\n</div>\r\n<a href=\"{{link}}\" style=\"display:inline-block;background:#f97316;color:#fff;padding:10px 22px;border-radius:6px;text-decoration:none;font-weight:700;margin-top:10px\">View Task →</a>\r\n<p style=\"margin-top:24px;color:#999;font-size:12px\">Padak CRM · This is an automated notification</p>\r\n</div>', '[\"name\",\"task\",\"project\",\"due_date\",\"link\"]', 1, NULL, '2026-04-20 10:04:34', '2026-04-20 10:04:34'),
(2, 'Task Due Reminder', 'Notifications', 'Task Due {{due_date}}: {{task}}', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px\">\r\n<h2 style=\"color:#ef4444;margin-bottom:4px\">⏰ Task Due Soon</h2>\r\n<p style=\"color:#666;margin-bottom:20px\">Hi {{name}},</p>\r\n<p>A task assigned to you is due <strong>{{due_date}}</strong>:</p>\r\n<div style=\"background:#fff3f3;border-left:4px solid #ef4444;padding:14px 18px;margin:16px 0;border-radius:0 8px 8px 0\">\r\n  <strong style=\"font-size:16px\">{{task}}</strong>\r\n  <p style=\"margin:6px 0 0;color:#555\">Project: {{project}}</p>\r\n</div>\r\n<a href=\"{{link}}\" style=\"display:inline-block;background:#ef4444;color:#fff;padding:10px 22px;border-radius:6px;text-decoration:none;font-weight:700;margin-top:10px\">View Task →</a>\r\n<p style=\"margin-top:24px;color:#999;font-size:12px\">Padak CRM · This is an automated notification</p>\r\n</div>', '[\"name\",\"task\",\"project\",\"due_date\",\"link\"]', 1, NULL, '2026-04-20 10:04:34', '2026-04-20 10:04:34'),
(3, 'Invoice Sent', 'Billing', 'Invoice {{invoice_no}} — {{amount}} Due {{due_date}}', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px\">\r\n<h2 style=\"color:#f97316;margin-bottom:4px\">🧾 Invoice from Padak</h2>\r\n<p style=\"color:#666;margin-bottom:20px\">Hi {{name}},</p>\r\n<p>Please find your invoice details below:</p>\r\n<div style=\"background:#fff8f0;border:1px solid #fed7aa;padding:16px 20px;margin:16px 0;border-radius:8px\">\r\n  <div style=\"display:flex;justify-content:space-between;margin-bottom:8px\">\r\n    <span style=\"color:#666\">Invoice No.</span><strong>{{invoice_no}}</strong>\r\n  </div>\r\n  <div style=\"display:flex;justify-content:space-between;margin-bottom:8px\">\r\n    <span style=\"color:#666\">Amount</span><strong style=\"color:#f97316;font-size:18px\">{{amount}}</strong>\r\n  </div>\r\n  <div style=\"display:flex;justify-content:space-between\">\r\n    <span style=\"color:#666\">Due Date</span><strong style=\"color:#ef4444\">{{due_date}}</strong>\r\n  </div>\r\n</div>\r\n<a href=\"{{link}}\" style=\"display:inline-block;background:#f97316;color:#fff;padding:10px 22px;border-radius:6px;text-decoration:none;font-weight:700;margin-top:10px\">View Invoice →</a>\r\n<p style=\"margin-top:24px;color:#999;font-size:12px\">Padak (Pvt) Ltd · Batticaloa, Sri Lanka · +94 710815522</p>\r\n</div>', '[\"name\",\"invoice_no\",\"amount\",\"due_date\",\"link\"]', 1, NULL, '2026-04-20 10:04:34', '2026-04-20 10:04:34'),
(4, 'Lead Follow-Up', 'Sales', 'Following up — {{company}}', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px\">\r\n<p>Hi {{name}},</p>\r\n<p>I wanted to follow up on our recent conversation regarding <strong>{{service_interest}}</strong> for {{company}}.</p>\r\n<p>I would love to discuss how Padak can help you achieve your goals. Please let me know if you have any questions or would like to schedule a call.</p>\r\n<p>Looking forward to hearing from you.</p>\r\n<p style=\"margin-top:24px\">Best Regards,<br><strong>Padak Team</strong><br>+94 710815522 · thepadak.com</p>\r\n</div>', '[\"name\",\"company\",\"service_interest\"]', 1, NULL, '2026-04-20 10:04:34', '2026-04-20 10:04:34'),
(5, 'Project Update', 'Projects', 'Project Update: {{project}}', '<div style=\"font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px\">\r\n<h2 style=\"color:#6366f1;margin-bottom:4px\">📁 Project Update</h2>\r\n<p>Hi {{name}},</p>\r\n<p>Here is the latest update for your project <strong>{{project}}</strong>:</p>\r\n<div style=\"background:#f5f5f5;padding:14px 18px;border-radius:8px;margin:16px 0\">\r\n  <p>{{body}}</p>\r\n</div>\r\n<p>If you have any questions, please don\'t hesitate to reach out.</p>\r\n<p style=\"margin-top:24px\">Best Regards,<br><strong>Padak Team</strong></p>\r\n</div>', '[\"name\",\"project\",\"body\"]', 1, NULL, '2026-04-20 10:04:34', '2026-04-20 10:04:34');

-- --------------------------------------------------------

--
-- Table structure for table `expense_entries`
--

CREATE TABLE `expense_entries` (
  `id` int(11) NOT NULL,
  `month_id` int(11) NOT NULL,
  `category` enum('Office & Rent','Software & Tools','Marketing','Legal & Registration','Company Branding','Miscellaneous','Internet & WiFi','Employee Salary','Daily Expenses','Other') DEFAULT 'Other',
  `description` varchar(255) DEFAULT NULL,
  `own_spend` decimal(12,2) DEFAULT 0.00,
  `office_spend` decimal(12,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'INR',
  `purchase_date` date DEFAULT NULL,
  `expire_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_entries`
--

INSERT INTO `expense_entries` (`id`, `month_id`, `category`, `description`, `own_spend`, `office_spend`, `currency`, `purchase_date`, `expire_date`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Office & Rent', 'Rented space cost', 0.00, 10000.00, 'LKR', '2026-03-31', '2026-04-30', 'This is LKR Price', 3, '2026-04-18 13:49:09', '2026-04-19 08:34:18'),
(2, 1, 'Software & Tools', 'Claude, Canva, AI Bots', 0.00, 0.00, 'INR', NULL, NULL, NULL, 3, '2026-04-18 13:49:09', '2026-04-18 13:49:09'),
(3, 1, 'Marketing', 'Campaigns, Managing', 0.00, 0.00, 'INR', NULL, NULL, NULL, 3, '2026-04-18 13:49:09', '2026-04-18 13:49:09'),
(4, 1, 'Legal & Registration', 'Office register Docs', 0.00, 0.00, 'INR', NULL, NULL, NULL, 3, '2026-04-18 13:49:09', '2026-04-18 13:49:09'),
(5, 1, 'Company Branding', 'Own Brand Website', 0.00, 0.00, 'INR', NULL, NULL, NULL, 3, '2026-04-18 13:49:09', '2026-04-18 13:49:09'),
(6, 1, 'Miscellaneous', 'In case of something', 0.00, 0.00, 'INR', NULL, NULL, NULL, 3, '2026-04-18 13:49:09', '2026-04-18 13:49:09'),
(7, 1, 'Internet & WiFi', 'Internet usage', 702.80, 0.00, 'INR', '2026-04-01', '2026-04-30', '', 3, '2026-04-18 13:49:09', '2026-04-19 08:35:07'),
(8, 1, 'Employee Salary', 'Salary to Workers', 0.00, 0.00, 'INR', NULL, NULL, NULL, 3, '2026-04-18 13:49:09', '2026-04-18 13:49:09'),
(9, 1, 'Daily Expenses', 'Tea, Snacks, Gifts', 0.00, 0.00, 'INR', NULL, NULL, NULL, 3, '2026-04-18 13:49:09', '2026-04-18 13:49:09'),
(10, 1, 'Other', '', 0.00, 0.00, 'INR', NULL, NULL, NULL, 3, '2026-04-18 13:49:09', '2026-04-18 13:49:09');

-- --------------------------------------------------------

--
-- Table structure for table `expense_months`
--

CREATE TABLE `expense_months` (
  `id` int(11) NOT NULL,
  `month_year` varchar(7) NOT NULL,
  `month_label` varchar(30) NOT NULL,
  `revenue` decimal(12,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_months`
--

INSERT INTO `expense_months` (`id`, `month_year`, `month_label`, `revenue`, `notes`, `created_by`, `created_at`) VALUES
(1, '2026-01', 'January 2026', 42150.00, '', 3, '2026-04-18 13:49:09');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(30) NOT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `title` varchar(250) NOT NULL,
  `status` enum('draft','sent','viewed','partial','paid','overdue','cancelled') DEFAULT 'draft',
  `currency` varchar(10) DEFAULT 'LKR',
  `issue_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(14,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(14,2) DEFAULT 0.00,
  `discount` decimal(14,2) DEFAULT 0.00,
  `total` decimal(14,2) DEFAULT 0.00,
  `amount_paid` decimal(14,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT 0,
  `recur_interval` enum('monthly','quarterly','yearly') DEFAULT NULL,
  `recur_next` date DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `viewed_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `reminder_sent` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_no`, `contact_id`, `project_id`, `title`, `status`, `currency`, `issue_date`, `due_date`, `subtotal`, `tax_rate`, `tax_amount`, `discount`, `total`, `amount_paid`, `notes`, `terms`, `is_recurring`, `recur_interval`, `recur_next`, `sent_at`, `viewed_at`, `paid_at`, `reminder_sent`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'INV-2026-0001', 1, 1, 'Premium Blog', 'draft', 'USD', '2026-04-19', '2026-04-30', 55000.00, 3.00, 1650.00, 1650.00, 55000.00, 0.00, '', 'Payment within 30 Days', 0, '', NULL, NULL, NULL, NULL, NULL, 3, '2026-04-19 23:07:26', '2026-04-19 23:07:26');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_counter`
--

CREATE TABLE `invoice_counter` (
  `id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `seq` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_counter`
--

INSERT INTO `invoice_counter` (`id`, `year`, `seq`) VALUES
(1, '2026', 1);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `description` varchar(500) NOT NULL,
  `quantity` decimal(10,2) DEFAULT 1.00,
  `unit_price` decimal(14,2) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `description`, `quantity`, `unit_price`, `amount`, `sort_order`) VALUES
(1, 1, 'Frontend', 1.00, 25000.00, 25000.00, 0),
(2, 1, 'Backend', 1.00, 25000.00, 25000.00, 1),
(3, 1, 'Database', 1.00, 5000.00, 5000.00, 2);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_payments`
--

CREATE TABLE `invoice_payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `method` enum('bank_transfer','cash','card','cheque','online','other') DEFAULT 'bank_transfer',
  `reference` varchar(150) DEFAULT NULL,
  `paid_at` date NOT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `company` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `source` enum('website','referral','social','cold_outreach','event','other') DEFAULT 'other',
  `service_interest` varchar(200) DEFAULT NULL,
  `budget_est` decimal(12,2) DEFAULT NULL,
  `budget_currency` varchar(10) DEFAULT 'LKR',
  `stage` enum('new','contacted','qualified','proposal','negotiation','won','lost') DEFAULT 'new',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `expected_close` date DEFAULT NULL,
  `last_contact` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `loss_reason` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `name`, `company`, `email`, `phone`, `source`, `service_interest`, `budget_est`, `budget_currency`, `stage`, `priority`, `expected_close`, `last_contact`, `notes`, `loss_reason`, `assigned_to`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Padak', 'Slice of Life', 'padak.service@gmail.com', '+41 798235584', 'referral', 'Website', 600.00, 'USD', 'won', 'medium', '2026-04-29', '2026-04-17', 'Testing Notes', '', 3, 1, '2026-04-19 08:47:28', '2026-04-19 10:15:25');

-- --------------------------------------------------------

--
-- Table structure for table `lead_activities`
--

CREATE TABLE `lead_activities` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('call','email','meeting','note','proposal','follow_up') DEFAULT 'note',
  `description` text NOT NULL,
  `activity_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_gen_results`
--

CREATE TABLE `lead_gen_results` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `place_id` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `owner_name` varchar(200) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `has_website` tinyint(1) DEFAULT 0,
  `api_calls` int(11) DEFAULT 0,
  `rating` decimal(2,1) DEFAULT NULL,
  `ratings_total` int(11) DEFAULT 0,
  `price_level` tinyint(4) DEFAULT NULL,
  `opportunity_score` int(11) DEFAULT 0,
  `search_mode` varchar(20) DEFAULT 'all',
  `location` varchar(200) DEFAULT NULL,
  `industry` varchar(200) DEFAULT NULL,
  `imported` tinyint(1) DEFAULT 0,
  `lead_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `website_found_by_crawler` tinyint(1) DEFAULT 0 COMMENT 'Set to 1 when website was found by deep-search, not Google Places'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_gen_results`
--

INSERT INTO `lead_gen_results` (`id`, `user_id`, `place_id`, `name`, `owner_name`, `phone`, `email`, `address`, `website`, `has_website`, `api_calls`, `rating`, `ratings_total`, `price_level`, `opportunity_score`, `search_mode`, `location`, `industry`, `imported`, `lead_id`, `created_at`, `website_found_by_crawler`) VALUES
(1, 3, 'ChIJN27CuZD1qjsRU4ioFrxAES4', 'Thamboora Restaurant Trichy', NULL, '097333 96000', NULL, 'C.27, North East Extension Fort Station Road 5th Cross Road East, Thillai Nagar Main Rd, Thillai Nagar East, North East Extension, Tennur, Tiruchirappalli, Tamil Nadu 620018, India', 'https://www.thamboora.com/', 1, 2, 4.4, 0, NULL, 15, 'all', 'Trichy', 'restaurants', 0, NULL, '2026-04-24 14:04:14', 1),
(2, 3, 'ChIJTyxvXw_1qjsRGxE6k0KdlBQ', 'Appathaa Samayall Trichy', NULL, '099528 43695', NULL, 'Bus Stand, 6, Voc Rd, near Roshan Mahal, Central, Cantonment, Tiruchirappalli, Tamil Nadu 620001, India', 'http://appathaasamayall.com/', 1, 3, 4.6, 0, NULL, 15, 'all', 'Trichy', 'restaurants', 0, NULL, '2026-04-24 14:04:15', 1),
(3, 3, 'ChIJ6-XzG431qjsRsU2m7IO-YAc', 'THE ROY Fine Dine Restaurant', NULL, '099653 93169', NULL, '10, 11th Cross E Rd, Ramachandrapuram, Tennur, Tiruchirappalli, Tamil Nadu 620017, India', '', 0, 4, 4.4, 0, NULL, 65, 'all', 'Trichy', 'restaurants', 0, NULL, '2026-04-24 14:04:15', 0),
(4, 3, 'ChIJ6d5gcSz1qjsRHjcHUj7-sQQ', 'Shri Sangeethas (W.B. Road, Trichy) Veg. Restaurant | Sweets | Savouries', NULL, '099655 91028', NULL, 'Hotel Deepam Complex, 148, W Blvd Rd, Tharanallur, Tiruchirappalli, Tamil Nadu 620002, India', 'http://shrisangeethas.in/', 1, 5, 4.8, 0, NULL, 15, 'all', 'Trichy', 'restaurants', 0, NULL, '2026-04-24 14:04:15', 1),
(5, 3, 'ChIJmQpnMAD1qjsRzOSAi-nlO_g', 'TOVO Trichy', NULL, '087609 06213', NULL, '5, Karur Bypass Rd, Annamalai Nagar, Killa Chinthamani, Tiruchirappalli, Tamil Nadu 620018, India', 'http://www.tovo.co.in/', 1, 6, 4.8, 0, NULL, 15, 'all', 'Trichy', 'restaurants', 0, NULL, '2026-04-24 14:04:16', 1),
(6, 3, 'ChIJd2ooluTT-joRvHGP-bePwvI', 'DR Designs - Batticaloa', NULL, '075 289 1299', NULL, 'No-57 Nalliah road, Batticaloa 30000, Sri Lanka', 'https://g.co/kgs/teZnhmp', 0, 2, 5.0, 0, NULL, 15, 'all', 'Batticola', 'Graphics Design', 1, 2, '2026-04-24 15:18:41', 0),
(7, 3, 'ChIJT42XkZvT-joRHWiQ62RsKsg', 'G Design Studio', NULL, '070 353 2956', NULL, '49 Selvanayagam Rd, Batticaloa 30000, Sri Lanka', 'https://www.facebook.com/gdsign.lk', 0, 3, 5.0, 0, NULL, 65, 'all', 'Batticola', 'Graphics Design', 1, 3, '2026-04-24 15:18:42', 0),
(8, 3, 'ChIJudogCaDS-joRVr1LduOrdhE', 'Shalom Digital Printing', NULL, '', NULL, 'Batticaloa, Sri Lanka', '', 0, 4, 4.5, 0, NULL, 60, 'all', 'Batticola', 'Graphics Design', 1, 4, '2026-04-24 15:18:42', 0),
(9, 3, 'ChIJA5WLZp_N-joRIoauqMK__0Q', 'Montana', NULL, '078 492 5533', NULL, '440 Trincomalee Hwy, Batticaloa 30000, Sri Lanka', '', 0, 5, 5.0, 0, NULL, 65, 'all', 'Batticola', 'Graphics Design', 1, 5, '2026-04-24 15:18:43', 0),
(10, 3, 'ChIJL2RC4BzT-joRqxJ5VhZItNU', 'AJ Shan grops of company', NULL, '075 252 1528', NULL, '513, 40 Poompukar 4th Cross St, Batticaloa 30000, Sri Lanka', '', 0, 6, 5.0, 0, NULL, 65, 'all', 'Batticola', 'Graphics Design', 1, 6, '2026-04-24 15:18:43', 0),
(11, 3, 'ChIJL1-XXaH1qjsRWBuD0QYDJWU', 'Exciteon, Tree of Technology Trichy', NULL, '0431 454 0010', NULL, 'No 110A, 9th Cross Rd E, Thillai Nagar East, West Thillai Nagar, Tennur, Tiruchirappalli, Tamil Nadu 620018, India', 'http://www.exciteon.com/', 1, 2, 4.6, 0, NULL, 15, 'all', 'Trichy', 'Web Development', 0, NULL, '2026-04-24 15:45:13', 1),
(12, 3, 'ChIJHyBOU6b1qjsRMj33d0EM-70', 'Mr & Mrs. Muscle’s Gym', '', '097513 45134', '', '13D / 2 sasthri road, 11th Cross E Rd, near sarathambal temple, Tennur, Tiruchirappalli, Tamil Nadu 620017, India', '', 0, 2, 4.9, 0, NULL, 65, 'all', 'Trichy', 'Fitness center', 0, NULL, '2026-04-25 11:04:35', 0),
(13, 3, 'ChIJ4wD_8hD1qjsRi3Kx23c160Y', 'DMart Trichy', '', '022 3340 0500', '', 'Mahalakshmi Nagar, Tiruchirappalli, Tamil Nadu 620008, India', 'http://www.dmartindia.com/', 1, 2, 4.2, 10539, NULL, 35, 'all', 'Trichy', 'Supermarket chain', 0, NULL, '2026-04-25 11:06:06', 1),
(14, 3, 'ChIJzdfUHihZ4joRbWIpR1LR_qs', 'Grand Zenith - Banquets & Events', '', '077 060 9944', 'info@grandzenith.lk', '26 New Pradeepa Mawatha Rd, Colombo 01000, Sri Lanka', 'https://www.grandzenith.lk/', 1, 2, 4.9, 573, NULL, 35, 'all', 'Colombo', 'Event hall', 0, NULL, '2026-04-25 11:41:36', 1),
(15, 3, 'ChIJCbyzLGBZ4joRdPY4QTkZdGc', 'The Gallery Café', '', '0112 582 162', '', '2 Alfred House Rd, Colombo 00300, Sri Lanka', 'https://www.paradiseroad.lk/', 1, 2, 4.4, 2887, 3, 50, 'all', 'Colombo', 'Restaurant', 0, NULL, '2026-04-25 11:43:42', 0),
(16, 3, 'ChIJu1HDyTbN-joRmIkDsNGTk8o', 'Six Flav Kitchen', '', '075 333 3983', '', 'Lloyd\'s Ave, Batticaloa 30000, Sri Lanka', '', 0, 2, 3.9, 366, NULL, 75, 'all', 'Batticaloa', 'Restaurant', 0, NULL, '2026-04-25 11:45:18', 0),
(17, 3, 'ChIJ-zBxOdnxqjsRyk_c_RzCrIQ', 'Al-Arab Family Restaurant', '', '082706 66608', '', 'DEVI Mahal opp, M.M Complex, Poovalur Rd, Lalgudi, Tamil Nadu 621601, India', '', 0, 2, 4.1, 95, NULL, 77, 'all', 'Lalgudi', 'Restaurant', 0, NULL, '2026-04-25 12:33:37', 0),
(18, 3, 'ChIJRR2dbe3xqjsRt1zSlb6NRZM', 'Sri Sathyamoorthy Bhavan', '', '0431 254 1724', '', 'Trichy Main Rd, Paramasivapuram, Lalgudi, Tamil Nadu 621601, India', '', 0, 3, 4.2, 389, 2, 93, 'all', 'Lalgudi', 'Restaurant', 0, NULL, '2026-04-25 12:33:37', 0),
(19, 3, 'ChIJWcN7IaPxqjsROWRZGAbzhUQ', 'Muniyandi Vilas Hotel', '', '090957 29187', '', 'VRH9+V9G, Poovalur Rd, Paramasivapuram, Lalgudi, Tamil Nadu 621601, India', '', 0, 4, 3.9, 261, NULL, 75, 'all', 'Lalgudi', 'Restaurant', 0, NULL, '2026-04-25 12:33:38', 0),
(20, 3, 'ChIJu4pOLLjxqjsR3DISyvh0Mqc', 'Hotel ARCHANAS', '', '', '', 'VRH8+637, Trichy Main Rd, Siruthaiyur, Paramasivapuram, Lalgudi, Tamil Nadu 621601, India', '', 0, 5, 3.8, 280, 2, 78, 'all', 'Lalgudi', 'Restaurant', 0, NULL, '2026-04-25 12:33:38', 0),
(21, 3, 'ChIJIb0n9LjxqjsR6xRfFtAzqdM', 'KARAIKUDI MESS', '', '', '', 'VRH8+637, Trichy Main Rd, Siruthaiyur, Paramasivapuram, Lalgudi, Tamil Nadu 621601, India', '', 0, 6, 4.4, 19, NULL, 66, 'all', 'Lalgudi', 'Restaurant', 0, NULL, '2026-04-25 12:33:38', 0),
(22, 3, 'ChIJk_5bh1dlUjoR5SL_ZNJuUa4', 'Success Mantra Educational Institutions', '', '072006 00029', '', '1st Floor, No.67/135, Avadhanam Papier Road, near KAT Motors, opposite Aavin Shop, Choolai, Chennai, Tamil Nadu 600007, India', '', 0, 2, 4.8, 29, NULL, 71, 'all', 'Chennai', 'Educational institutes', 0, NULL, '2026-04-26 10:23:10', 0),
(23, 3, 'ChIJYWH9171lUjoRPAdRB9vmSMg', 'Global Tech Computer Education Institute Purasawalkam in Chennai, Tally, Python, Java, UI / UX, Data Science, DTP, SAP', '', '098898 43343', 'admin@globalteceducation.com', '14, Tana St, opp. AAVIN BOOTH, Purasaiwakkam, Chennai, Tamil Nadu 600007, India', 'https://www.globalteceducation.com/', 1, 3, 4.9, 331, NULL, 35, 'all', 'Chennai', 'Educational institutes', 0, NULL, '2026-04-26 10:23:21', 1),
(24, 3, 'ChIJv4QqUo5lUjoRhSGnL6jCPlM', 'Sri Saraswathy Educational Institution', '', '096000 90990', 'srisaraswathyeduinst@gmail.com', 'NEW No. 4/1, Old 26, 1, Middle St, Sri Nagar Colony, Sri Nagar, Kolathur, Chennai, Tamil Nadu 600099, India', 'http://www.srisaraswathyeducationalinstitution.in/', 1, 4, 4.8, 20, NULL, 21, 'all', 'Chennai', 'Educational institutes', 0, NULL, '2026-04-26 10:23:23', 1),
(25, 3, 'ChIJocHqAu1lUjoRFdlE3A4FVTM', 'SVCT Educational Institute', '', '090878 61477', '', '77/2 MH Road, M.H Road, near Railway Station, Bunder Garden, Perambur, Chennai, Tamil Nadu 600011, India', '', 0, 5, 5.0, 1, NULL, 65, 'all', 'Chennai', 'Educational institutes', 0, NULL, '2026-04-26 10:23:23', 0),
(26, 3, 'ChIJ8wlY40xnUjoRsrygTgo1LRc', 'Chennai Institute For Higher Studies', '', '', '', '188, Netaji Subash Chandra Bose St, Abdul Kalam Nagar, Parrys, Manapakkam, Chennai, Tamil Nadu 600078, India', '', 0, 6, 5.0, 3, NULL, 60, 'all', 'Chennai', 'Educational institutes', 0, NULL, '2026-04-26 10:23:23', 0),
(27, 3, 'ChIJMUwSWB5Z4joRyF102wkQAM0', 'Universal Institute Colombo (UIC)', '', '077 782 1746', 'support@universalinstitutecolombo.com', 'Level 35, World Trade Center, West Tower, Bank of Ceylon Mawatha, Colombo 00100, Sri Lanka', 'https://www.universalinstitutecolombo.com/%20%20https://universalinstitutecolombo.lk/', 1, 2, 5.0, 120, NULL, 35, 'all', 'Colombo', 'Institutes', 0, NULL, '2026-04-26 12:41:29', 1),
(28, 3, 'ChIJG_KLfN5b4joRGgXfOLvPRME', 'Imperial Institute of Higher Education (IIHE)', '', '077 477 8773', 'info@iihe.lk', '5 Geethanjalee Pl, Colombo 00300, Sri Lanka', 'https://www.iihe.lk/', 1, 3, 4.0, 128, NULL, 35, 'all', 'Colombo', 'Institutes', 0, NULL, '2026-04-26 12:41:35', 1),
(29, 3, 'ChIJaeoMEl1a4joRcgsNvPmd2RU', 'Royal Institute Campus', '', '077 768 3100', '', '92 Sunethradevi Rd, Nugegoda 10250, Sri Lanka', 'https://ric.lk/', 1, 4, 4.3, 152, NULL, 35, 'all', 'Colombo', 'Institutes', 0, NULL, '2026-04-26 12:41:39', 1),
(30, 3, 'ChIJp39kU8Rb4joRw2e91Zm_IIw', 'CIIHE', '', '076 844 1221', 'info@ciihe.com', '279 1, 1 Galle Rd, Colombo 00400, Sri Lanka', 'https://www.ciihe.com/', 1, 5, 3.4, 57, NULL, 17, 'all', 'Colombo', 'Institutes', 0, NULL, '2026-04-26 12:41:43', 1),
(31, 3, 'ChIJxXFnGttW4joRNnWaq0TjYyw', 'Sri Lanka Institute of Information Technology', '', '0117 544 801', 'info@sliit.lk', 'SLIIT Malabe Campus, New Kandy Rd, Malabe 10115, Sri Lanka', 'https://www.sliit.lk/', 1, 6, 4.6, 1805, NULL, 35, 'all', 'Colombo', 'Institutes', 0, NULL, '2026-04-26 12:41:45', 1),
(32, 3, 'ChIJMUwSWB5Z4joRyF102wkQAM0', 'Universal Institute Colombo (UIC)', '', '077 782 1746', 'support@universalinstitutecolombo.com', 'Level 35, World Trade Center, West Tower, Bank of Ceylon Mawatha, Colombo 00100, Sri Lanka', 'https://www.universalinstitutecolombo.com/%20%20https://universalinstitutecolombo.lk/', 1, 2, 5.0, 120, NULL, 35, 'all', 'Colombo', 'Institutes', 0, NULL, '2026-04-26 12:43:16', 1),
(33, 3, 'ChIJKYqCmWlZ4joRX5HFkGpk0eM', 'Hatch Works', '', '0117 652 500', 'joinus@hatch.lk', '14 Sir Baron Jayathilake Mawatha, Colombo 00100, Sri Lanka', 'http://hatch.lk/', 1, 2, 4.6, 679, NULL, 35, 'all', 'Colombo', 'Startups', 0, NULL, '2026-04-26 12:44:25', 1),
(34, 3, 'ChIJTXbn3s9b4joRxbASabb7l98', 'One Galle Face Mall', '', '0117 869 888', 'info@onegalleface.com', '1A Centre Road, Colombo 00200, Sri Lanka', 'https://onegalleface.com/', 1, 2, 4.6, 26047, NULL, 35, 'all', 'Colombo', 'Retail', 0, NULL, '2026-04-26 12:44:42', 1),
(35, 3, 'ChIJcyONU6tZ4joRsoUPZcoC2uI', 'Sana Commerce (Pvt) Ltd', '', '0115 115 588', '', '3rd floor, Alnitak Building, Dr Danister De Silva Mawatha, Colombo 9 00900, Sri Lanka', '', 0, 2, 4.7, 110, NULL, 85, 'all', 'Colombo', 'Small Businesses', 0, NULL, '2026-04-26 12:45:03', 0),
(36, 3, 'ChIJTcgpTzdZ4joRhohcPhd5fyk', 'IMPEX LANKA (PVT) LTD', '', '077 728 5589', 'info@impexlanka.lk', 'IMPEX LANKA PVT LTD, Colombo 01000, Sri Lanka', 'http://impexlanka.lk/', 1, 2, 5.0, 10, NULL, 21, 'all', 'Colombo', '(Pvt) Ltd', 0, NULL, '2026-04-26 12:47:37', 1),
(37, 3, 'ChIJxXU3dxBZ4joRnUItMGUbo_E', 'CodeGen International (Pvt) Ltd', '', '0112 024 400', '', 'Trace Expert City, Bay 1-5 Maradana Rd, Colombo 01000, Sri Lanka', 'http://www.codegen.co.uk/', 1, 2, 4.7, 476, NULL, 35, 'all', 'Colombo', 'Private Limited Company', 0, NULL, '2026-04-26 12:48:21', 1),
(38, 3, 'ChIJcyONU6tZ4joRsoUPZcoC2uI', 'Sana Commerce (Pvt) Ltd', '', '0115 115 588', '', '3rd floor, Alnitak Building, Dr Danister De Silva Mawatha, Colombo 9 00900, Sri Lanka', '', 0, 2, 4.7, 110, NULL, 85, 'all', 'Colombo', 'Small Businesses', 0, NULL, '2026-04-26 12:48:30', 0),
(39, 3, 'ChIJS_wMwRNZ4joRN4s78SzYXdI', 'Sumudu BPO (Pvt) Ltd', '', '0117 533 633', 'info@sumudubpo.com', 'No: 1043, 1/2 Maradana Rd, Colombo 00800, Sri Lanka', 'https://sumudubpo.lk/', 1, 2, 4.9, 34, NULL, 27, 'all', 'Colombo', 'Small Businesses', 0, NULL, '2026-04-26 12:48:47', 1),
(40, 3, 'ChIJp4dVVVtZ4joRPw1gnRhGqis', 'Aqcellor - Entrepreneurship Program | Best Startup Ideas | Tech Mentor', '', '076 784 8752', '', '11/2,Sumner Place, South, Colombo 00800, Sri Lanka', 'https://aqcellor.com/community-signup/', 1, 3, NULL, 0, NULL, 5, 'all', 'Colombo', 'Small Businesses', 0, NULL, '2026-04-26 12:48:48', 1),
(41, 3, 'ChIJkTZ08oJZ4joRpU_dpCoieWQ', 'Small Enterprises Development Division, colombo', '', '0112 695 388', '', 'Plywood Corporation, 420 Bauddhaloka Mawatha, Colombo 00700, Sri Lanka', 'http://www.sed.gov.lk/', 1, 4, 4.6, 5, NULL, 15, 'all', 'Colombo', 'Small Businesses', 0, NULL, '2026-04-26 12:49:34', 1),
(42, 3, 'ChIJF5go1ab1qjsRPFhS8S387ps', 'esoft IT Solutions.', '', '080724 20182', '', 'C, II-Floor, Land Mark: Lakshmi Complex, Bus Stop, 145/74, Salai Rd, Thillai Nagar East, Thillai Nagar, Tiruchirappalli, Tamil Nadu 620018, India', 'http://e-soft.in/', 1, 2, 4.9, 1002, NULL, 35, 'all', 'Tiruchirappalli, India', 'IT Services', 0, NULL, '2026-04-26 15:55:01', 1),
(43, 3, 'ChIJKaJbMo_1qjsRHzepslRlme0', 'Trichy IT Services', '', '096001 14466', '', 'New no: 12, old No: 14 7th main road, Vayalur Rd, Srinivase Nagar North, Srinivasa Nagar North, Tiruchirappalli, Tamil Nadu 620017, India', 'https://www.uniqtechnologies.co.in/', 1, 3, 3.1, 16, NULL, 11, 'all', 'Tiruchirappalli, India', 'IT Services', 0, NULL, '2026-04-26 15:55:14', 1),
(44, 3, 'ChIJ4f___5BPqjsRR7uGj1sP5oE', 'Adssan IT', '', '097891 08542', 'sales@adssan.com', 'Om complex, 3/6, Sankaran Pillai Road, Melachinthamani, Tiruchirappalli, Tamil Nadu 620002, India', 'https://adssan.com/', 1, 4, 4.9, 114, NULL, 35, 'all', 'Tiruchirappalli, India', 'IT Services', 0, NULL, '2026-04-26 15:55:16', 1),
(45, 3, 'ChIJQ3rMYRr1qjsRjk4b9gj9-VY', 'Fantasy Solution | Software & Hardware Solutions | Best Project Center In Trichy', '', '090430 95535', 'info@fantasysolution.in', '16, Samnath Plazza, Third Floor, Madurai Main Rd, Melapudur, Sangillyandapuram, Tiruchirappalli, Tamil Nadu 620001, India', 'http://www.fantasysolution.in/', 1, 5, 4.7, 1128, NULL, 35, 'all', 'Tiruchirappalli, India', 'IT Services', 0, NULL, '2026-04-26 15:55:18', 1),
(46, 3, 'ChIJ2SWc8qL1qjsRJbfVLJTfqUU', 'Capgemini Technology Services', '', '0431 660 6100', '', '26, Muthiah Towers, 2, Williams Rd, near to central bus stand & femina shopping mall, Cantonment, Tiruchirappalli, Tamil Nadu 620001, India', 'http://www.capgemini.com/', 1, 6, 4.5, 81, NULL, 27, 'all', 'Tiruchirappalli, India', 'IT Services', 0, NULL, '2026-04-26 15:55:27', 1),
(47, 3, 'ChIJVWwUPBT1qjsRBv9nLC5SJfQ', 'Breeze Residency', '', '097900 32444', 'reservations@breezeresidency.com', '3/14, McDonalds Rd, near Central Bus Stand, Melapudur, Cantonment, Tiruchirappalli, Tamil Nadu 620001, India', 'http://www.breezeresidency.com/', 1, 2, 4.0, 7044, NULL, 35, 'all', 'Tiruchirappalli, India', 'resort', 0, NULL, '2026-04-26 15:56:01', 1),
(48, 3, 'ChIJuflo7zr1qjsRLLGIUM9-zxI', 'Grand Gardenia', '', '095856 44000', 'reservations@grandgardenia.com', 'Junction, 22-25, Chennai - Theni Hwy, Mannarpuram, Sangillyandapuram, Tiruchirappalli, Tamil Nadu 620020, India', 'http://www.grandgardenia.com/', 1, 3, 4.1, 2706, NULL, 35, 'all', 'Tiruchirappalli, India', 'resort', 0, NULL, '2026-04-26 15:56:05', 1),
(49, 3, 'ChIJYX_vvqT1qjsRhrJjSn7Ivv8', 'HOTEL OXINA LYGON', '', '0431 401 3555', 'reservation@hoteloxinalygon.com', 'A-6, Salai Rd, North East Extension, Tennur, Tiruchirappalli, Tamil Nadu 620018, India', 'https://www.hoteloxinalygon.com/', 1, 4, 4.2, 2337, NULL, 35, 'all', 'Tiruchirappalli, India', 'resort', 0, NULL, '2026-04-26 15:56:08', 1),
(50, 3, 'ChIJR8fhdW31qjsRdNwwaw4R2j4', 'Sangam Hotel', '', '0431 424 4555', 'bookings@sangamhotels.com', 'Collectorate\'s Office Road, Near, Major Saravanan Rd, near Raja Colony, SBI Officers Colony, Raja Colony, Tiruchirappalli, Tamil Nadu 620001, India', 'http://sangamhotels.com/', 1, 5, 4.3, 4967, NULL, 35, 'all', 'Tiruchirappalli, India', 'resort', 0, NULL, '2026-04-26 15:56:11', 1),
(51, 3, 'ChIJ1-0XFz9ZqjsRu8cXWP78b0E', 'Solai Resort', '', '086081 44444', '', 'Tiruchirapalli - Salem Main Rd, Thudaiyur, Tiruchirappalli, Tamil Nadu 621213, India', '', 0, 6, 4.2, 1360, NULL, 85, 'all', 'Tiruchirappalli, India', 'resort', 0, NULL, '2026-04-26 15:56:11', 0),
(52, 3, 'ChIJp31-79Qhm0cR0HIQrXDpuRA', 'HE-Log Hightec Express Logistic GmbH', '', '071 535 38 32', 'info@he-log.com', 'Friedberg 231, 9427 Wolfhalden, Switzerland', 'http://www.he-log.com/', 0, 2, 4.8, 81, NULL, 77, 'all', 'Switzerland', 'Logistics company', 0, NULL, '2026-04-27 10:41:31', 0),
(53, 3, 'ChIJ4cOnbJqAmkcRp-i_sxeyaQg', 'JCL Logistics Switzerland AG', '', '052 645 30 30', 'office@jcl-logistics.com', 'Stammlerbühlstrasse 9, 8240 Thayngen, Switzerland', 'http://www.jcl-logistics.com/', 1, 3, 4.2, 65, NULL, 27, 'all', 'Switzerland', 'Logistics company', 0, NULL, '2026-04-27 10:41:34', 1),
(54, 3, 'ChIJ6-8Nnqz1qjsRCCoPzzCy6kU', 'SRD Logistics Pvt Ltd', '', '094425 46491', '', '74, Boologanathan Swamy Kovil Street, EB Rd, Tharanallur, Tiruchirappalli, Tamil Nadu 620008, India', 'http://www.srdlogistics.com/', 1, 2, 4.6, 213, NULL, 35, 'all', 'Tiruchirappalli, India', 'Logistics company', 0, NULL, '2026-04-27 10:44:08', 1),
(55, 3, 'ChIJJ5yIptiKqjsRyUBX2GHstDs', 'Blessing Cargo Trichy - Vegetables Exporter, Fruits Exporter, Flowers Exporter in Tamilnadu', '', '097876 12229', 'aircargotrz@gmail.com', 'No 56, Blessing Cargo Complex, Airport, Pasumai Nagar, Tiruchirappalli, Tamil Nadu 620007, India', 'http://www.blessingcargo.in/', 1, 3, 4.9, 85, NULL, 27, 'all', 'Tiruchirappalli, India', 'Logistics company', 0, NULL, '2026-04-27 10:44:13', 1),
(56, 3, 'ChIJ9_O9KaZZqDsREtXZXRFijyc', 'SARVAM LOGISTICS INDIA PVT LTD', '', '097515 00070', 'sales@sarvamlogistics.com', 'B 1,II Floor, TST Complex, 742, Avinashi Rd, Opp. Anna Statue, Gopalapuram, Coimbatore, Tamil Nadu 641018, India', 'https://www.sarvamlogistics.com/', 1, 2, 4.8, 65, NULL, 27, 'all', 'Tamil Nadu, India', 'Logistics company', 0, NULL, '2026-04-27 10:44:40', 1),
(57, 3, 'ChIJfVZOLzrPADsRXVGFBtJg7Yk', 'SRD Logistics Pvt Ltd', '', '095855 48678', '', 'No 27/B, Melakkal Rd, behind MK Complex, Kochadai, Madurai, Achampattu, Tamil Nadu 625016, India', 'http://www.srdlogistics.com/', 1, 3, 4.2, 199, NULL, 35, 'all', 'Tamil Nadu, India', 'Logistics company', 0, NULL, '2026-04-27 10:44:49', 1),
(58, 3, 'ChIJTfha1NdjUzoRn4BYnOvcfJo', 'Mighty Logistics', '', '099944 70115', 'logisticskarthik@gmail.com', '14, Savarirayalu St, MG Road Area, Puducherry, 605001, India', 'http://mighty-logistics.com/contact.php', 1, 2, 5.0, 135, NULL, 35, 'all', 'Puducherry, India', 'Logistics company', 0, NULL, '2026-04-27 10:45:12', 1),
(59, 3, 'ChIJXeEe0iFhUzoRbzl29GVpNOw', 'Inland World Logistics Pvt Ltd.', '', '0413 227 2858', 'customer.support@iwlpl.in', '3 & 4, Poothurai Rd, near Truck Terminal, Guru Nagar, Indra Nagar, Priyadarshini Nagar, Puducherry, 605009, India', 'http://inlandworldlogistics.com/', 1, 3, 4.8, 4, NULL, 15, 'all', 'Puducherry, India', 'Logistics company', 0, NULL, '2026-04-27 10:45:18', 1),
(60, 3, 'ChIJTfha1NdjUzoRn4BYnOvcfJo', 'Mighty Logistics', '', '099944 70115', 'logisticskarthik@gmail.com', '14, Savarirayalu St, MG Road Area, Puducherry, 605001, India', 'http://mighty-logistics.com/contact.php', 1, 2, 5.0, 135, NULL, 35, 'all', 'Pondicherry, India', 'Logistics company', 0, NULL, '2026-04-27 10:45:25', 1),
(61, 3, 'ChIJXeEe0iFhUzoRbzl29GVpNOw', 'Inland World Logistics Pvt Ltd.', '', '0413 227 2858', 'customer.support@iwlpl.in', '3 & 4, Poothurai Rd, near Truck Terminal, Guru Nagar, Indra Nagar, Priyadarshini Nagar, Puducherry, 605009, India', 'http://inlandworldlogistics.com/', 1, 3, 4.8, 4, NULL, 15, 'all', 'Pondicherry, India', 'Logistics company', 0, NULL, '2026-04-27 10:45:29', 1),
(62, 3, 'ChIJAY7bL11nUzoRgWAWIj7Dj6U', 'Eaton Power Quality Pvt Ltd', '', '0413 267 2005', '', '2, EVR Street, Sedarpet Indl. Estate, Sedarapet, Puducherry 605111, India', 'http://www.eaton.com/', 1, 2, 4.2, 368, NULL, 35, 'all', 'Pondicherry, India', 'company', 0, NULL, '2026-04-27 10:46:21', 1),
(63, 3, 'ChIJj1k09dthUzoRrTgXNwRcgS0', 'AAHA Solutions', '', '095515 65200', 'info@aahasolutions.com', '27, 3rd Cross Rd, Sithankudi, Brindavan Colony, Puducherry, 605013, India', 'http://www.aahasolutions.com/', 1, 3, 4.6, 721, NULL, 35, 'all', 'Pondicherry, India', 'company', 0, NULL, '2026-04-27 10:46:24', 1),
(64, 3, 'ChIJddwVjK7FADsRpqxk-ABaMO0', 'Chella Software Private Limited', '', '095009 80413', 'business@chelsoft.com', 'Plot No.6, ELCOT - SEZ, Ilandhaikulam, Pandi Kovil Ring Rd, Madurai, Tamil Nadu 625020, India', 'http://www.chelsoft.com/', 1, 2, 4.8, 32, NULL, 27, 'all', 'Tamil Nadu, India', 'Software company', 0, NULL, '2026-04-27 10:47:02', 1),
(65, 3, 'ChIJ5TnUwBjTADsRMKc5uyDVNYI', 'Notasco Technologies India Pvt Ltd', '', '090427 72367', '', '2nd Floor, Srinivasa Nagar, 409/3, Madurai Rd, behind Trends, Tirumangalam, Madurai, Tamil Nadu 625706, India', 'https://notasco.com/', 1, 3, 5.0, 278, NULL, 35, 'all', 'Tamil Nadu, India', 'Software company', 0, NULL, '2026-04-27 10:47:04', 1),
(66, 2, 'ChIJtwMicp3FADsRAwlEyYgxeqE', 'National Transport Company', '', '0452 233 3392', '', '89, Panthadi 1st St, Panthadi Area, Madurai Main, Panthadi, Madurai, Tamil Nadu 625001, India', 'https://nationaltransport.in/', 1, 2, 4.8, 6, NULL, 15, 'all', 'Tamil Nadu, India', 'Transport company', 0, NULL, '2026-04-27 13:03:25', 1),
(67, 2, 'ChIJVVVVJYYX2jER3DITgxJtiBI', 'Trinax Private Limited', '', '6694 8700', 'info@trinaxgroup.com', '68 Kallang Pudding Rd, #01-01 SYH Logistics Building, Singapore 349327', 'http://www.trinax.sg/', 1, 2, 4.9, 96, NULL, 27, 'all', 'Singapore, Singapore', 'Private Limited', 0, NULL, '2026-04-27 13:05:01', 1),
(68, 2, 'ChIJq6qqi5UW2jER1NPQe0FIk7A', 'Summit Company (Singapore) Private Limited', '', '6336 8891', '', '3 Ang Mo Kio Street 62, #06-28 Link@AMK, Singapore 569139', '', 0, 3, 5.0, 1, NULL, 65, 'all', 'Singapore, Singapore', 'Private Limited', 0, NULL, '2026-04-27 13:05:03', 0),
(69, 2, 'ChIJt8EsMA0Z2jER5I78UOHyqS4', 'Zegal Singapore Private Limited', '', '6589 8923', 'sales@zegal.com', '1 Wallich St, #14-01 Guoco Tower, Singapore 078881', 'http://www.dragonlaw.io/', 1, 4, 5.0, 1, NULL, 15, 'all', 'Singapore, Singapore', 'Private Limited', 0, NULL, '2026-04-27 13:05:07', 1),
(71, 2, 'ChIJqWvLXYQG2jERD-F-rJBBD8A', 'Abbott Manufacturing Singapore Private Limited', '', '6500 8500', '', '26 Tuas South Ave 10, Singapore 637437', 'https://www.abbott.com.sg/', 1, 6, 3.7, 25, NULL, 11, 'all', 'Singapore, Singapore', 'Private Limited', 0, NULL, '2026-04-27 13:05:20', 1),
(72, 2, 'ChIJjzhMVhD1qjsRt_iQahl6BKw', 'Where Photos | Best wedding photography in Trichy | Best baby photography in Trichy | kids and maternity photographers Trichy', '', '080729 07090', '', 'No.A, 41, Anna Nagar, Tennur, Tiruchirappalli, Tamil Nadu 620017, India', 'http://www.wherephotos.com/', 1, 2, 4.9, 448, NULL, 35, 'all', 'Tiruchirappalli, India', 'Wedding photographer', 0, NULL, '2026-04-27 13:15:53', 1),
(73, 2, 'ChIJB2PbwGP1qjsRjs39rknlOyU', 'Selva Wedding Photography', '', '097888 12364', 'selvastudiotry@gmail.com', 'SF3, B Block, Sri Raghavendra Apartment, Old Palpannai, Mahalakshmi Nagar, Tiruchirappalli, Tamil Nadu 620008, India', 'http://selvaweddingphotography.in/', 1, 3, 4.9, 611, NULL, 35, 'all', 'Tiruchirappalli, India', 'Wedding photographer', 0, NULL, '2026-04-27 13:15:54', 1),
(74, 2, 'ChIJMWuLLy30qjsRukIcepLsJyE', 'Chandru Clicks Photography Wedding photography in Trichy | best wedding photography in trichy', '', '099656 43351', '', 'Michael\'s Ice Cream & Cool Drinks, kaveri medicals Canara Bank Atm, 34/77, Sannathi St, near me, Srirangam, Thiruvanaikoil, Tiruchirappalli, Tamil Nadu 620005, India', 'http://www.chandruclicks.com/', 1, 4, 4.9, 172, NULL, 35, 'all', 'Tiruchirappalli, India', 'Wedding photographer', 0, NULL, '2026-04-27 13:15:58', 1),
(75, 2, 'ChIJ189tM0X1qjsRYeWlmoI9eZY', 'Jeno Photography', '', '099523 97023', 'jenophotography.trichy@gmail.com', 'No. 24, 1st floor Gandhi nagar 3rd main road, Trichy-Dindigul Rd, Jaya Nagar Extension, IOB Nagar, Karumandapam, Tiruchirappalli, Tamil Nadu 620001, India', 'http://www.jenophotography.com/', 1, 5, 4.9, 93, NULL, 27, 'all', 'Tiruchirappalli, India', 'Wedding photographer', 0, NULL, '2026-04-27 13:16:02', 1),
(76, 2, 'ChIJuwy42jP1qjsRRgVJoHwKbfY', 'Yazhi Photography | Best candid wedding photographer in trichy | baby & kids photography in trichy', '', '073735 29291', '', '4, 343, 2nd St, Sakthi Nagar, Pappakurichi Kattur, Tiruchirappalli, Tamil Nadu 620019, India', 'https://www.yazhiphotography.com/', 1, 6, 4.9, 67, NULL, 27, 'all', 'Tiruchirappalli, India', 'Wedding photographer', 0, NULL, '2026-04-27 13:16:11', 1),
(77, 2, 'ChIJjzhMVhD1qjsRt_iQahl6BKw', 'Where Photos | Best wedding photography in Trichy | Best baby photography in Trichy | kids and maternity photographers Trichy', '', '080729 07090', '', 'No.A, 41, Anna Nagar, Tennur, Tiruchirappalli, Tamil Nadu 620017, India', 'http://www.wherephotos.com/', 1, 2, 4.9, 448, NULL, 35, 'all', 'Trichy, India', 'Wedding photographer', 0, NULL, '2026-04-27 13:16:31', 1),
(78, 2, 'ChIJB2PbwGP1qjsRjs39rknlOyU', 'Selva Wedding Photography', '', '097888 12364', 'selvastudiotry@gmail.com', 'SF3, B Block, Sri Raghavendra Apartment, Old Palpannai, Mahalakshmi Nagar, Tiruchirappalli, Tamil Nadu 620008, India', 'http://selvaweddingphotography.in/', 1, 3, 4.9, 611, NULL, 35, 'all', 'Trichy, India', 'Wedding photographer', 0, NULL, '2026-04-27 13:16:32', 1),
(79, 2, 'ChIJMWuLLy30qjsRukIcepLsJyE', 'Chandru Clicks Photography Wedding photography in Trichy | best wedding photography in trichy', '', '099656 43351', '', 'Michael\'s Ice Cream & Cool Drinks, kaveri medicals Canara Bank Atm, 34/77, Sannathi St, near me, Srirangam, Thiruvanaikoil, Tiruchirappalli, Tamil Nadu 620005, India', 'http://www.chandruclicks.com/', 1, 4, 4.9, 172, NULL, 35, 'all', 'Trichy, India', 'Wedding photographer', 0, NULL, '2026-04-27 13:16:35', 1),
(80, 2, 'ChIJuwy42jP1qjsRRgVJoHwKbfY', 'Yazhi Photography | Best candid wedding photographer in trichy | baby & kids photography in trichy', '', '073735 29291', '', '4, 343, 2nd St, Sakthi Nagar, Pappakurichi Kattur, Tiruchirappalli, Tamil Nadu 620019, India', 'https://www.yazhiphotography.com/', 1, 5, 4.9, 67, NULL, 27, 'all', 'Trichy, India', 'Wedding photographer', 0, NULL, '2026-04-27 13:16:44', 1),
(81, 2, 'ChIJidcNPJn1qjsREfUN9Oba6Zg', 'Bee studio wedding photography in trichy - Best wedding photography in Trichy | Best baby photography in Trichy', '', '075399 90008', '', 'NO.99/4, St.Pauls Complex, 1st floor, near Head Postoffice, Bharathiyar Salai, Sangillyandapuram, Tiruchirappalli, Tamil Nadu 620001, India', 'https://www.instagram.com/bee_studio_trichy?igsh=MXNvOXNsYTkxbHRjbg==', 0, 6, 4.9, 351, NULL, 85, 'all', 'Trichy, India', 'Wedding photographer', 0, NULL, '2026-04-27 13:17:15', 0),
(82, 3, 'ChIJZefKFeP-sUARmNtGsCbrXvI', 'Realworld Systems - Romania', '', '021 330 5577', 'info@realworld-systems.com', 'Strada Anastasie Panu 50, 031166 București, Romania', 'http://ro.realworld-systems.com/', 1, 2, 4.9, 16, NULL, 21, 'all', 'Europe, Romania', 'Private Limited', 0, NULL, '2026-04-27 13:48:08', 1),
(83, 3, 'ChIJz7p97K_4sUARh2pnzKIAnBY', 'Phi Partners International Limited - Bucharest Branch', '', '021 780 9380', 'info@phipartners.com', 'Euro Tower Building, 11 Strada Dinu Vintila, Bucharest, Romania', 'http://www.phipartners.com/', 1, 3, 4.5, 11, NULL, 21, 'all', 'Europe, Romania', 'Private Limited', 0, NULL, '2026-04-27 13:48:12', 1),
(84, 3, 'ChIJp_hw4KdNRkcRuhQEEncSaEA', 'European Technologies & Services Romania', '', '0737 513 773', 'director@etsro.ro', 'Strada Garii, 417050 Biharia, Romania', 'http://www.etsro.ro/', 1, 4, 4.0, 2, NULL, 15, 'all', 'Europe, Romania', 'Private Limited', 0, NULL, '2026-04-27 13:48:17', 1),
(85, 3, 'ChIJgTxFBnr_sUARajoyoMc59fA', 'NXP Semiconductors Romania', '', '021 305 2400', '', 'Bulevardul Iuliu Maniu 6L, 061103 București, Romania', 'http://www.nxp.com/webapp/sps/site/overview.jsp?code=ROMANIA_HOME_ROMANIAN', 1, 5, 4.6, 93, NULL, 27, 'all', 'Europe, Romania', 'Private Limited', 0, NULL, '2026-04-27 13:48:18', 1),
(86, 3, 'ChIJ8fiwacz-sUARkOsOditM9fY', 'M247 Europe', '', '031 080 0700', 'support@m247global.com', 'Șoseaua Fabrica de Glucoză 11B, etaj 1, 020331 București, Romania', 'http://www.m247global.com/', 1, 6, 4.6, 205, NULL, 35, 'all', 'Europe, Romania', 'Private Limited', 0, NULL, '2026-04-27 13:48:20', 1),
(87, 3, 'ChIJZefKFeP-sUARmNtGsCbrXvI', 'Realworld Systems - Romania', '', '021 330 5577', 'info@realworld-systems.com', 'Strada Anastasie Panu 50, 031166 București, Romania', 'http://ro.realworld-systems.com/', 1, 2, 4.9, 16, NULL, 21, 'all', 'Europe, Romania', 'Private Limited', 0, NULL, '2026-04-27 13:49:44', 1),
(88, 3, 'ChIJz7p97K_4sUARh2pnzKIAnBY', 'Phi Partners International Limited - Bucharest Branch', '', '021 780 9380', 'info@phipartners.com', 'Euro Tower Building, 11 Strada Dinu Vintila, Bucharest, Romania', 'http://www.phipartners.com/', 1, 3, 4.5, 11, NULL, 21, 'all', 'Europe, Romania', 'Private Limited', 0, NULL, '2026-04-27 13:49:54', 1),
(89, 3, 'ChIJgTxFBnr_sUARajoyoMc59fA', 'NXP Semiconductors Romania', '', '021 305 2400', '', 'Bulevardul Iuliu Maniu 6L, 061103 București, Romania', 'http://www.nxp.com/webapp/sps/site/overview.jsp?code=ROMANIA_HOME_ROMANIAN', 1, 4, 4.6, 93, NULL, 27, 'all', 'Europe, Romania', 'Private Limited', 0, NULL, '2026-04-27 13:49:55', 1),
(90, 3, 'ChIJp_hw4KdNRkcRuhQEEncSaEA', 'European Technologies & Services Romania', '', '0737 513 773', 'director@etsro.ro', 'Strada Garii, 417050 Biharia, Romania', 'http://www.etsro.ro/', 1, 5, 4.0, 2, NULL, 15, 'all', 'Europe, Romania', 'Private Limited', 0, NULL, '2026-04-27 13:50:00', 1),
(91, 3, 'ChIJ8fiwacz-sUARkOsOditM9fY', 'M247 Europe', '', '031 080 0700', 'support@m247global.com', 'Șoseaua Fabrica de Glucoză 11B, etaj 1, 020331 București, Romania', 'http://www.m247global.com/', 1, 6, 4.6, 205, NULL, 35, 'all', 'Europe, Romania', 'Private Limited', 0, NULL, '2026-04-27 13:50:02', 1),
(92, 3, 'ChIJMUwSWB5Z4joRyF102wkQAM0', 'Universal Institute Colombo (UIC)', '', '077 782 1746', 'support@universalinstitutecolombo.com', 'Level 35, World Trade Center, West Tower, Bank of Ceylon Mawatha, Colombo 00100, Sri Lanka', 'https://www.universalinstitutecolombo.com/%20%20https://universalinstitutecolombo.lk/', 1, 2, 5.0, 120, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Institute', 0, NULL, '2026-04-28 09:40:34', 0),
(93, 3, 'ChIJaeoMEl1a4joRcgsNvPmd2RU', 'Royal Institute Campus', '', '077 768 3100', '', '92 Sunethradevi Rd, Nugegoda 10250, Sri Lanka', 'https://ric.lk/', 1, 3, 4.3, 153, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Institute', 0, NULL, '2026-04-28 09:40:38', 0),
(94, 3, 'ChIJrwi5Cg1Z4joRthTZdhTm0-E', 'FLITS Vocational Training Institute', '', '076 140 4525', 'marketing@flits.lk', '90/3, 10 Ven. S. Mahinda Mawatha, Colombo 01000, Sri Lanka', 'https://flits.lk/ec-2/', 1, 4, 4.3, 177, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Institute', 0, NULL, '2026-04-28 09:40:52', 0),
(95, 3, 'ChIJC8lrWE9Z4joR8BxPqGgDShk', 'PPIM – Colombo Campus Sri Lanka - ACCA Platinum Tuition Provider & AAT Tuition Courses Sri Lanka | ACCA & AAT Classes', '', '', 'hello@ppim.edu.lk', '248 1, 1 Galle - Colombo Rd, Colombo 00400, Sri Lanka', 'https://www.ppim.edu.lk/', 1, 5, 4.9, 248, NULL, 30, 'all', 'Colombo, Sri Lanka', 'Institute', 0, NULL, '2026-04-28 09:40:59', 0),
(96, 3, 'ChIJIUvdd69b4joRmyg987AUpBc', 'AIC Campus', '', '077 133 5511', 'gishan@gmail.com', '154 Havelock Rd, Colombo 00500, Sri Lanka', 'https://www.aicedu.lk/', 1, 6, 4.5, 113, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Institute', 0, NULL, '2026-04-28 09:41:03', 0),
(97, 3, 'ChIJx4IEQRbx4joR98FsMKNKrjQ', 'Lanka Academy', '', '077 024 4992', 'hello@lankaacademy.lk', '47 Ananda Coomaraswamy Mawatha, Colombo 00300, Sri Lanka', 'https://www.lankaacademy.lk/', 1, 2, 4.9, 69, NULL, 27, 'all', 'Colombo, Sri Lanka', 'academy', 0, NULL, '2026-04-28 09:41:43', 1),
(98, 3, 'ChIJldOQcDlZ4joRqnYrDcNPECY', 'Royal Academy', '', '077 244 2939', '', '297, 15 Baseline Rd, Colombo 00900, Sri Lanka', 'https://instagram.com/englishwithrishh', 0, 3, 5.0, 53, NULL, 77, 'all', 'Colombo, Sri Lanka', 'academy', 0, NULL, '2026-04-28 09:41:48', 0),
(99, 3, 'ChIJk5lYAPNZ4joRu_qRW5XwMRw', 'AS Learning Academy', '', '072 244 5534', '', '97/A Mohideen Masjid Rd, Colombo 01000, Sri Lanka', '', 0, 4, 4.8, 32, NULL, 77, 'all', 'Colombo, Sri Lanka', 'academy', 0, NULL, '2026-04-28 09:41:51', 0),
(100, 3, 'ChIJ2XkrK41Z4joRdGrEtprD70k', 'Colombo Leadership Academy', '', '072 086 5000', 'riaz@clacoaching.com', '26/1A Park Ln, Sri Jayawardenepura Kotte 11222, Sri Lanka', 'http://www.clacoaching.com/', 0, 5, 4.4, 13, NULL, 71, 'all', 'Colombo, Sri Lanka', 'academy', 0, NULL, '2026-04-28 09:41:54', 0),
(101, 3, 'ChIJx8KUI3dZ4joRU2Ekj_njORQ', 'IAF - International Academy of Fashion (Pvt) Ltd', '', '077 760 4029', 'info@iafedu.com', '3 Coniston Pl, Colombo 00700, Sri Lanka', 'https://iafedu.com/', 1, 6, 4.6, 41, NULL, 27, 'all', 'Colombo, Sri Lanka', 'academy', 0, NULL, '2026-04-28 09:42:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `lead_gen_settings`
--

CREATE TABLE `lead_gen_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_val` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_gen_settings`
--

INSERT INTO `lead_gen_settings` (`id`, `setting_key`, `setting_val`, `updated_by`, `updated_at`) VALUES
(1, 'monthly_quota', '4000', 3, '2026-04-25 14:58:04'),
(2, 'google_api_key', 'AIzaSyAFrA2kYwwgQU_PegAMFuE8Kvnp5vf9wAs', 3, '2026-04-24 14:03:14'),
(16, 'monthly_budget_usd', '180', 3, '2026-04-27 13:01:27'),
(17, 'cost_per_textsearch', '0.032', NULL, '2026-04-24 13:59:01'),
(18, 'cost_per_details', '0.003', NULL, '2026-04-24 13:59:01'),
(27, 'quota_role_admin', '1000', NULL, '2026-04-27 12:58:23'),
(28, 'quota_role_manager', '500', NULL, '2026-04-27 12:58:34'),
(29, 'quota_role_member', '100', NULL, '2026-04-27 12:58:43');

-- --------------------------------------------------------

--
-- Table structure for table `lead_gen_usage`
--

CREATE TABLE `lead_gen_usage` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `location` varchar(200) NOT NULL,
  `industry` varchar(200) NOT NULL,
  `result_count` int(11) DEFAULT 0,
  `api_calls_used` int(11) DEFAULT 0,
  `estimated_cost` decimal(8,4) DEFAULT 0.0000,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lead_gen_usage`
--

INSERT INTO `lead_gen_usage` (`id`, `user_id`, `location`, `industry`, `result_count`, `api_calls_used`, `estimated_cost`, `created_at`) VALUES
(1, 3, 'Lalgudi', 'Hotel', 0, 0, 0.0000, '2026-04-22 21:30:50'),
(2, 3, 'Lalgudi', 'Restorants', 0, 0, 0.0000, '2026-04-22 21:31:26'),
(3, 3, 'Trichy', 'restaurants', 5, 6, 0.0470, '2026-04-24 14:04:16'),
(4, 3, 'Batticola', 'Graphics Design', 5, 6, 0.0470, '2026-04-24 15:18:43'),
(5, 3, 'Trichy', 'Web Development', 1, 2, 0.0350, '2026-04-24 15:45:13'),
(6, 3, 'Trichy', 'Fitness center', 1, 2, 0.0350, '2026-04-25 11:04:35'),
(7, 3, 'Trichy', 'Supermarket chain', 1, 2, 0.0350, '2026-04-25 11:06:06'),
(8, 3, 'Colombo', 'Event hall', 1, 2, 0.0350, '2026-04-25 11:41:36'),
(9, 3, 'Colombo', 'Restaurant', 1, 2, 0.0350, '2026-04-25 11:43:42'),
(10, 3, 'Batticaloa', 'Restaurant', 1, 2, 0.0350, '2026-04-25 11:45:18'),
(11, 3, 'Lalgudi', 'Restaurant', 5, 6, 0.0470, '2026-04-25 12:33:38'),
(12, 3, 'Chennai', 'Educational institutes', 3, 6, 0.0410, '2026-04-26 10:23:23'),
(13, 3, 'Colombo', 'Institutes', 0, 6, 0.0320, '2026-04-26 12:41:45'),
(14, 3, 'Colombo', 'Institutes', 0, 2, 0.0320, '2026-04-26 12:43:16'),
(15, 3, 'Colombo', 'Startups', 0, 2, 0.0320, '2026-04-26 12:44:25'),
(16, 3, 'Colombo', 'Retail', 0, 2, 0.0320, '2026-04-26 12:44:42'),
(17, 3, 'Colombo', 'Small Businesses', 1, 2, 0.0350, '2026-04-26 12:45:03'),
(18, 3, 'Colombo', '(Pvt) Ltd', 0, 2, 0.0320, '2026-04-26 12:47:37'),
(19, 3, 'Colombo', 'Private Limited Company', 0, 2, 0.0320, '2026-04-26 12:48:21'),
(20, 3, 'Colombo', 'Small Businesses', 1, 2, 0.0350, '2026-04-26 12:48:30'),
(21, 3, 'Colombo', 'Small Businesses', 0, 4, 0.0320, '2026-04-26 12:49:34'),
(22, 3, 'Tiruchirappalli, India', 'IT Services', 0, 6, 0.0320, '2026-04-26 15:55:27'),
(23, 3, 'Tiruchirappalli, India', 'resort', 1, 6, 0.0350, '2026-04-26 15:56:11'),
(24, 3, 'Switzerland', 'Logistics company', 1, 3, 0.0350, '2026-04-27 10:41:34'),
(25, 3, 'Tiruchirappalli, India', 'Logistics company', 0, 3, 0.0320, '2026-04-27 10:44:13'),
(26, 3, 'Tamil Nadu, India', 'Logistics company', 0, 3, 0.0320, '2026-04-27 10:44:49'),
(27, 3, 'Puducherry, India', 'Logistics company', 0, 3, 0.0320, '2026-04-27 10:45:18'),
(28, 3, 'Pondicherry, India', 'Logistics company', 0, 3, 0.0320, '2026-04-27 10:45:29'),
(29, 3, 'Pondicherry, India', 'company', 0, 3, 0.0320, '2026-04-27 10:46:24'),
(30, 3, 'Tamil Nadu, India', 'Software company', 0, 3, 0.0320, '2026-04-27 10:47:04'),
(31, 2, 'Singapore, Singapore', 'Private Limited', 2, 6, 0.0380, '2026-04-27 13:05:20'),
(32, 2, 'Tiruchirappalli, India', 'Wedding photographer', 0, 6, 0.0320, '2026-04-27 13:16:11'),
(33, 2, 'Trichy, India', 'Wedding photographer', 1, 6, 0.0350, '2026-04-27 13:17:15'),
(34, 3, 'Europe, Romania', 'Private Limited', 5, 6, 0.0470, '2026-04-27 13:48:20'),
(35, 3, 'Europe, Romania', 'Private Limited', 0, 6, 0.0320, '2026-04-27 13:50:02'),
(36, 3, 'Colombo, Sri Lanka', 'Institute', 0, 6, 0.0320, '2026-04-28 09:41:03'),
(37, 3, 'Colombo, Sri Lanka', 'academy', 3, 6, 0.0410, '2026-04-28 09:42:00');

-- --------------------------------------------------------

--
-- Table structure for table `month_revenue_entries`
--

CREATE TABLE `month_revenue_entries` (
  `id` int(11) NOT NULL,
  `month_id` int(11) NOT NULL,
  `project_name` varchar(200) NOT NULL DEFAULT '',
  `client_name` varchar(200) NOT NULL DEFAULT '',
  `payment_type` enum('advance','milestone','final','other') NOT NULL DEFAULT 'advance',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'INR',
  `payment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `month_revenue_entries`
--

INSERT INTO `month_revenue_entries` (`id`, `month_id`, `project_name`, `client_name`, `payment_type`, `amount`, `currency`, `payment_date`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 'Premium Blog', 'Arthy Paranitharan', 'advance', 40000.00, 'LKR', '2026-04-26', '', 3, '2026-04-27 11:39:18'),
(2, 1, 'Middle', 'Arthy Paranitharan', 'milestone', 2000.00, 'INR', '2026-04-27', '', 3, '2026-04-27 11:43:09'),
(3, 1, 'CRM Canada Client', 'Deepan', 'advance', 150.00, 'EUR', '2026-04-20', '', 3, '2026-04-27 11:44:05');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `entity_type` enum('task','project','lead','document','message','invoice','comment') DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `title` varchar(250) NOT NULL,
  `body` text DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `email_sent` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

CREATE TABLE `payslips` (
  `id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `employee_name` varchar(200) NOT NULL,
  `employee_email` varchar(200) DEFAULT NULL,
  `employee_phone` varchar(50) DEFAULT NULL,
  `designation` varchar(150) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `employee_id_no` varchar(50) DEFAULT NULL,
  `pay_period` varchar(50) NOT NULL,
  `pay_date` date DEFAULT NULL,
  `basic_salary` decimal(12,2) DEFAULT 0.00,
  `allowances` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowances`)),
  `deductions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deductions`)),
  `gross_salary` decimal(12,2) DEFAULT 0.00,
  `total_deductions` decimal(12,2) DEFAULT 0.00,
  `net_salary` decimal(12,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'LKR',
  `bank_name` varchar(150) DEFAULT NULL,
  `account_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('draft','issued') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payslips`
--

INSERT INTO `payslips` (`id`, `template_id`, `employee_id`, `employee_name`, `employee_email`, `employee_phone`, `designation`, `department`, `employee_id_no`, `pay_period`, `pay_date`, `basic_salary`, `allowances`, `deductions`, `gross_salary`, `total_deductions`, `net_salary`, `currency`, `bank_name`, `account_no`, `notes`, `status`, `created_by`, `created_at`) VALUES
(1, 1, 3, 'Vignesh G', 'vignesh.g@thepadak.com', '+91 8525822546', 'Software Developer', 'Development', 'EMP-01', 'April 2026', '2026-04-25', 30000.00, '[]', '[]', 30000.00, 0.00, 30000.00, 'INR', 'HDFC Bank', '', '', 'issued', 3, '2026-04-24 09:26:34');

-- --------------------------------------------------------

--
-- Table structure for table `payslip_templates`
--

CREATE TABLE `payslip_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `company_address` text DEFAULT NULL,
  `company_logo` varchar(500) DEFAULT NULL,
  `footer_note` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payslip_templates`
--

INSERT INTO `payslip_templates` (`id`, `name`, `company_name`, `company_address`, `company_logo`, `footer_note`, `is_default`, `created_by`, `created_at`) VALUES
(1, 'Testing Template', 'Padak', 'Nothing', 'uploads/documents/logo_69eae5c6505fe.png', 'This is a computer-generated payslip and requires no signature.', 0, 3, '2026-04-24 09:08:46');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `status` enum('planning','active','on_hold','completed','cancelled') DEFAULT 'planning',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `budget` decimal(12,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'LKR',
  `progress` int(11) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `title`, `description`, `contact_id`, `status`, `priority`, `start_date`, `due_date`, `budget`, `currency`, `progress`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Premium Blog', 'Subscription based premium blog website', 1, 'active', 'high', '2026-03-25', '2026-04-30', 600.00, 'USD', 11, 1, '2026-04-19 08:29:15', '2026-04-20 09:34:33'),
(2, 'Insurance CRM', 'Canada client insurance case management', NULL, 'on_hold', 'low', '2026-03-16', '2026-05-18', 1500.00, 'USD', 5, 3, '2026-04-19 22:26:52', '2026-04-19 22:26:58');

-- --------------------------------------------------------

--
-- Table structure for table `project_members`
--

CREATE TABLE `project_members` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT 'member',
  `joined_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_members`
--

INSERT INTO `project_members` (`id`, `project_id`, `user_id`, `role`, `joined_at`) VALUES
(7, 1, 1, 'member', '2026-04-19 08:59:34'),
(8, 1, 2, 'member', '2026-04-19 08:59:34'),
(9, 1, 3, 'member', '2026-04-19 08:59:34'),
(10, 2, 1, 'member', '2026-04-19 22:26:52'),
(11, 2, 2, 'member', '2026-04-19 22:26:52'),
(12, 2, 3, 'member', '2026-04-19 22:26:52');

-- --------------------------------------------------------

--
-- Table structure for table `recent_searches`
--

CREATE TABLE `recent_searches` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `query` varchar(250) NOT NULL,
  `result_count` int(11) DEFAULT 0,
  `searched_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rich_docs`
--

CREATE TABLE `rich_docs` (
  `id` int(11) NOT NULL,
  `title` varchar(250) NOT NULL,
  `content` longtext DEFAULT NULL,
  `category` varchar(100) DEFAULT 'General',
  `project_id` int(11) DEFAULT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rich_docs`
--

INSERT INTO `rich_docs` (`id`, `title`, `content`, `category`, `project_id`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'Testing Rich Docs', '<p><em><strong>Testing Docs</strong></em></p>', 'General', 1, '', 1, 1, '2026-04-19 09:22:26', '2026-04-19 09:22:29');

-- --------------------------------------------------------

--
-- Table structure for table `saved_searches`
--

CREATE TABLE `saved_searches` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `label` varchar(150) NOT NULL,
  `query` varchar(250) DEFAULT NULL,
  `filters` text DEFAULT NULL,
  `is_pinned` tinyint(1) DEFAULT 0,
  `use_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `social_accounts`
--

CREATE TABLE `social_accounts` (
  `id` int(11) NOT NULL,
  `platform` enum('facebook','instagram','twitter','linkedin','youtube','tiktok','other') NOT NULL,
  `name` varchar(200) NOT NULL,
  `handle` varchar(200) DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `followers` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `acc_password` varchar(500) DEFAULT NULL,
  `acc_email` varchar(300) DEFAULT NULL,
  `acc_role` enum('admin','editor','viewer') NOT NULL DEFAULT 'admin',
  `acc_2fa` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `social_accounts`
--

INSERT INTO `social_accounts` (`id`, `platform`, `name`, `handle`, `url`, `followers`, `notes`, `is_active`, `created_by`, `created_at`, `acc_password`, `acc_email`, `acc_role`, `acc_2fa`) VALUES
(1, 'instagram', 'Padak (Pvt) Ltd', '@thepadak', 'https://www.instagram.com/thepadak', 3, '', 1, 3, '2026-04-27 11:30:36', NULL, NULL, 'admin', NULL),
(2, 'facebook', 'Padak (Pvt) Ltd', 'PadakOfficial', 'https://www.facebook.com/profile.php?id=61577970876846', 4, '', 1, 3, '2026-04-27 11:32:05', NULL, NULL, 'admin', NULL),
(3, 'twitter', 'Padak (Pvt) Ltd', '@padak_official', 'https://x.com/padak_official', 1, '', 1, 3, '2026-04-27 11:32:45', NULL, NULL, 'admin', NULL),
(4, 'youtube', 'Padak (Pvt) Ltd', '@thepadak', 'https://www.youtube.com/@thepadak', 7, '', 1, 3, '2026-04-27 11:33:33', NULL, NULL, 'admin', NULL),
(5, 'linkedin', 'Padak (Pvt) Ltd', 'padak-official', 'https://linkedin.com/company/padak-official/', 6, '', 1, 3, '2026-04-27 11:34:56', NULL, NULL, 'admin', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `social_posts`
--

CREATE TABLE `social_posts` (
  `id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `title` varchar(300) NOT NULL,
  `content` text DEFAULT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `status` enum('idea','draft','scheduled','published','cancelled') DEFAULT 'idea',
  `post_type` enum('post','story','reel','video','article') DEFAULT 'post',
  `tags` varchar(500) DEFAULT NULL,
  `caption_notes` text DEFAULT NULL,
  `platform_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`platform_data`)),
  `likes` int(11) DEFAULT 0,
  `comments` int(11) DEFAULT 0,
  `reach` int(11) DEFAULT 0,
  `project_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `social_posts`
--

INSERT INTO `social_posts` (`id`, `account_id`, `title`, `content`, `media_url`, `platform`, `scheduled_at`, `published_at`, `status`, `post_type`, `tags`, `caption_notes`, `platform_data`, `likes`, `comments`, `reach`, `project_id`, `created_by`, `assigned_to`, `approved_by`, `approved_at`, `created_at`) VALUES
(1, 1, 'First Post Creation', 'Try to create and post First post in our all social media pages', NULL, 'instagram', '2026-05-01 12:36:00', '2026-04-27 12:17:14', 'published', 'post', '#firstpost', '', NULL, 0, 0, 0, NULL, 3, NULL, NULL, NULL, '2026-04-27 11:36:35');

-- --------------------------------------------------------

--
-- Table structure for table `social_templates`
--

CREATE TABLE `social_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `type` enum('caption','hashtag','cta') DEFAULT 'caption',
  `platform` varchar(50) DEFAULT NULL,
  `content` text NOT NULL,
  `usage_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `social_templates`
--

INSERT INTO `social_templates` (`id`, `name`, `type`, `platform`, `content`, `usage_count`, `created_by`, `created_at`) VALUES
(1, 'Engagement CTA', 'cta', NULL, '💬 What do you think? Drop a comment below!\n❤️ Like if this helped you!\n🔔 Follow for more updates.', 0, NULL, '2026-04-24 13:19:25'),
(2, 'General Hashtags', 'hashtag', NULL, '#business #growth #marketing #digital #success #entrepreneur #startup', 0, NULL, '2026-04-24 13:19:25'),
(3, 'LinkedIn Opener', 'caption', 'linkedin', 'Excited to share something with our network...\n\n[Your main content here]\n\nWhat has been your experience with this? I\'d love to hear your thoughts in the comments. 👇', 0, NULL, '2026-04-24 13:19:25'),
(4, 'Instagram Caption', 'caption', 'instagram', '✨ [Hook line here]\n\n[2-3 sentences of value]\n\n[Call to action]\n\n.\n.\n.\n#padak', 0, NULL, '2026-04-24 13:19:25'),
(5, 'Facebook Post', 'caption', 'facebook', 'Hey everyone! 👋\n\n[Your content here]\n\nShare this with someone who needs to see it! 🙌', 0, NULL, '2026-04-24 13:19:25');

-- --------------------------------------------------------

--
-- Table structure for table `software_purchases`
--

CREATE TABLE `software_purchases` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `paid_to` varchar(150) NOT NULL,
  `date_purchase` date DEFAULT NULL,
  `date_expire` date DEFAULT NULL,
  `usage_limit` varchar(50) DEFAULT NULL,
  `paid_amount` decimal(12,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'INR',
  `payment_method` varchar(100) DEFAULT NULL,
  `paid_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `software_purchases`
--

INSERT INTO `software_purchases` (`id`, `invoice_number`, `paid_to`, `date_purchase`, `date_expire`, `usage_limit`, `paid_amount`, `currency`, `payment_method`, `paid_by`, `notes`, `created_by`, `created_at`) VALUES
(1, 'OWJUD92123', 'Canva', '2026-04-01', '2026-04-30', '1 Week', 199.00, 'INR', '4584', 3, '0', 1, '2026-04-19 08:36:32');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `paid_to` varchar(150) NOT NULL,
  `date_of_issue` date DEFAULT NULL,
  `date_of_end` date DEFAULT NULL,
  `paid_amount` decimal(12,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'USD',
  `payment_method` varchar(100) DEFAULT NULL,
  `paid_by` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT 'Software & Tools',
  `notes` text DEFAULT NULL,
  `auto_renew` tinyint(1) DEFAULT 0,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `invoice_number`, `paid_to`, `date_of_issue`, `date_of_end`, `paid_amount`, `currency`, `payment_method`, `paid_by`, `category`, `notes`, `auto_renew`, `status`, `created_by`, `created_at`) VALUES
(1, 'OWJUD921', 'Anthropic', '2026-04-02', '2026-04-14', 200.00, 'USD', '4584', 3, 'Software & Tools', '0', 0, 'active', 3, '2026-04-18 13:50:25');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(250) NOT NULL,
  `description` text DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('todo','in_progress','review','done') DEFAULT 'todo',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `due_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `label` varchar(80) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `project_id`, `assigned_to`, `created_by`, `status`, `priority`, `due_date`, `start_date`, `label`, `sort_order`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 'Get Quotes from Client Blog', 'Collect Quotes from Client for frontend', 1, 1, 1, 'todo', 'medium', '2026-04-30', '2026-04-27', 'Business Development', 0, NULL, '2026-04-19 08:30:39', '2026-04-28 11:06:00');

-- --------------------------------------------------------

--
-- Table structure for table `task_comments`
--

CREATE TABLE `task_comments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_comments`
--

INSERT INTO `task_comments` (`id`, `task_id`, `user_id`, `comment`, `created_at`) VALUES
(1, 1, 3, 'Do Task', '2026-04-28 10:59:53');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','member') DEFAULT 'member',
  `avatar` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `avatar`, `phone`, `department`, `status`, `last_login`, `created_at`) VALUES
(1, 'Manager', 'manager@thepadak.com', '$2y$10$3.5QhvfumMj.22FYneOAnOu1ZsF6yg0iIqKausdogECfEacsX3x12', 'manager', NULL, '', 'Management', 'active', '2026-04-19 08:27:01', '2026-04-14 09:10:22'),
(2, 'Thiki', 'thiki@thepadak.com', '$2y$10$coRWR1BmT/jdTrfuK/RDleG6GTSbp791Llgm509no1dhXJYnl0Poq', 'admin', 'avatar_2_1777272800.png', '+41 798235584', 'Leadership', 'active', '2026-04-27 12:22:31', '2026-04-14 09:10:22'),
(3, 'Vignesh', 'vignesh@thepadak.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'avatar_3_1776997592.png', '', 'Development', 'active', '2026-04-28 09:35:19', '2026-04-14 09:10:22'),
(4, 'Member', 'member@thepadak.com', '$2y$10$31V1PALKq2KcgLKxek.mCOtEhjPSegnveTuOnQIXkCz2vHPolbzHG', 'member', NULL, '+41 798235584', 'Marketing', 'active', NULL, '2026-04-27 12:21:54');

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_messages`
--

CREATE TABLE `whatsapp_messages` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `direction` enum('out','in') DEFAULT 'out',
  `phone` varchar(50) NOT NULL,
  `contact_name` varchar(200) DEFAULT NULL,
  `body` text NOT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `twilio_sid` varchar(100) DEFAULT NULL,
  `status` enum('queued','sent','delivered','read','failed','received') DEFAULT 'queued',
  `error_msg` text DEFAULT NULL,
  `sent_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_settings`
--

CREATE TABLE `whatsapp_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_val` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `whatsapp_settings`
--

INSERT INTO `whatsapp_settings` (`id`, `setting_key`, `setting_val`, `updated_by`, `updated_at`) VALUES
(1, 'twilio_sid', '', NULL, '2026-04-24 08:44:51'),
(2, 'twilio_token', '', NULL, '2026-04-24 08:44:51'),
(3, 'twilio_from', 'whatsapp:+14155238886', NULL, '2026-04-24 08:44:51'),
(4, 'sandbox_word', '', NULL, '2026-04-24 08:44:51');

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_templates`
--

CREATE TABLE `whatsapp_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category` enum('greeting','follow_up','invoice','proposal','support','reminder','custom') DEFAULT 'custom',
  `message` text NOT NULL,
  `variables` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `whatsapp_templates`
--

INSERT INTO `whatsapp_templates` (`id`, `name`, `category`, `message`, `variables`, `created_by`, `created_at`) VALUES
(1, 'Introduction', 'greeting', 'Hi {{name}}, I am contacting you from Padak. We specialize in {{service}}. Would you be open to a quick call to explore how we can help your business?', NULL, NULL, '2026-04-24 08:44:51'),
(2, 'Follow Up', 'follow_up', 'Hi {{name}}, following up on our previous conversation about {{topic}}. Please let me know if you have any questions!', NULL, NULL, '2026-04-24 08:44:51'),
(3, 'Invoice Reminder', 'invoice', 'Hi {{name}}, this is a reminder that Invoice #{{invoice_no}} for {{amount}} is due on {{due_date}}. Please let us know if you need help.', NULL, NULL, '2026-04-24 08:44:51'),
(4, 'Proposal Sent', 'proposal', 'Hi {{name}}, I have sent a proposal for {{project}}. Please review it and let us know your thoughts!', NULL, NULL, '2026-04-24 08:44:51'),
(5, 'Meeting Reminder', 'reminder', 'Hi {{name}}, reminder about our meeting on {{date}} at {{time}}. Please confirm your availability. Thank you!', NULL, NULL, '2026-04-24 08:44:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `calendar_attendees`
--
ALTER TABLE `calendar_attendees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_att` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`);

--
-- Indexes for table `chatbot_sessions`
--
ALTER TABLE `chatbot_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_updated` (`user_id`,`updated_at`);

--
-- Indexes for table `chatbot_settings`
--
ALTER TABLE `chatbot_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `chatbot_usage`
--
ALTER TABLE `chatbot_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`user_id`,`created_at`);

--
-- Indexes for table `chat_channels`
--
ALTER TABLE `chat_channels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `chat_members`
--
ALTER TABLE `chat_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_cm` (`channel_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `channel_id` (`channel_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `chat_reactions`
--
ALTER TABLE `chat_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_react` (`message_id`,`user_id`,`emoji`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `client_messages`
--
ALTER TABLE `client_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `client_portal`
--
ALTER TABLE `client_portal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contact_id` (`contact_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);
ALTER TABLE `contacts` ADD FULLTEXT KEY `idx_contact_search` (`name`,`company`,`email`,`notes`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);
ALTER TABLE `documents` ADD FULLTEXT KEY `idx_doc_search` (`title`,`description`,`original_name`);

--
-- Indexes for table `email_log`
--
ALTER TABLE `email_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `sent_by` (`sent_by`);

--
-- Indexes for table `email_settings`
--
ALTER TABLE `email_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `expense_entries`
--
ALTER TABLE `expense_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `month_id` (`month_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `expense_months`
--
ALTER TABLE `expense_months`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_month` (`month_year`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `invoice_counter`
--
ALTER TABLE `invoice_counter`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year` (`year`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);
ALTER TABLE `leads` ADD FULLTEXT KEY `idx_lead_search` (`name`,`company`,`service_interest`,`notes`);

--
-- Indexes for table `lead_activities`
--
ALTER TABLE `lead_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `lead_gen_results`
--
ALTER TABLE `lead_gen_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `lead_gen_settings`
--
ALTER TABLE `lead_gen_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `lead_gen_usage`
--
ALTER TABLE `lead_gen_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `month_revenue_entries`
--
ALTER TABLE `month_revenue_entries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `payslip_templates`
--
ALTER TABLE `payslip_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `created_by` (`created_by`);
ALTER TABLE `projects` ADD FULLTEXT KEY `idx_project_search` (`title`,`description`);

--
-- Indexes for table `project_members`
--
ALTER TABLE `project_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_member` (`project_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `recent_searches`
--
ALTER TABLE `recent_searches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rich_docs`
--
ALTER TABLE `rich_docs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `saved_searches`
--
ALTER TABLE `saved_searches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `social_accounts`
--
ALTER TABLE `social_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `social_posts`
--
ALTER TABLE `social_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `social_templates`
--
ALTER TABLE `social_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `software_purchases`
--
ALTER TABLE `software_purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paid_by` (`paid_by`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paid_by` (`paid_by`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_label` (`label`),
  ADD KEY `idx_start_date` (`start_date`);
ALTER TABLE `tasks` ADD FULLTEXT KEY `idx_task_search` (`title`,`description`);

--
-- Indexes for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `whatsapp_messages`
--
ALTER TABLE `whatsapp_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_id` (`contact_id`),
  ADD KEY `lead_id` (`lead_id`),
  ADD KEY `sent_by` (`sent_by`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `whatsapp_settings`
--
ALTER TABLE `whatsapp_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `whatsapp_templates`
--
ALTER TABLE `whatsapp_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `calendar_attendees`
--
ALTER TABLE `calendar_attendees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `chatbot_sessions`
--
ALTER TABLE `chatbot_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `chatbot_settings`
--
ALTER TABLE `chatbot_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `chatbot_usage`
--
ALTER TABLE `chatbot_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `chat_channels`
--
ALTER TABLE `chat_channels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `chat_members`
--
ALTER TABLE `chat_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `chat_reactions`
--
ALTER TABLE `chat_reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `client_messages`
--
ALTER TABLE `client_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client_portal`
--
ALTER TABLE `client_portal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `email_log`
--
ALTER TABLE `email_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `email_settings`
--
ALTER TABLE `email_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `expense_entries`
--
ALTER TABLE `expense_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `expense_months`
--
ALTER TABLE `expense_months`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoice_counter`
--
ALTER TABLE `invoice_counter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lead_activities`
--
ALTER TABLE `lead_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_gen_results`
--
ALTER TABLE `lead_gen_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `lead_gen_settings`
--
ALTER TABLE `lead_gen_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `lead_gen_usage`
--
ALTER TABLE `lead_gen_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `month_revenue_entries`
--
ALTER TABLE `month_revenue_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payslip_templates`
--
ALTER TABLE `payslip_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `project_members`
--
ALTER TABLE `project_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `recent_searches`
--
ALTER TABLE `recent_searches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `rich_docs`
--
ALTER TABLE `rich_docs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `saved_searches`
--
ALTER TABLE `saved_searches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `social_accounts`
--
ALTER TABLE `social_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `social_posts`
--
ALTER TABLE `social_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `social_templates`
--
ALTER TABLE `social_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `software_purchases`
--
ALTER TABLE `software_purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `whatsapp_messages`
--
ALTER TABLE `whatsapp_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `whatsapp_settings`
--
ALTER TABLE `whatsapp_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `whatsapp_templates`
--
ALTER TABLE `whatsapp_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `calendar_attendees`
--
ALTER TABLE `calendar_attendees`
  ADD CONSTRAINT `calendar_attendees_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calendar_attendees_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD CONSTRAINT `calendar_events_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `calendar_events_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `calendar_events_ibfk_3` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `calendar_events_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  ADD CONSTRAINT `chatbot_messages_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `chatbot_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_channels`
--
ALTER TABLE `chat_channels`
  ADD CONSTRAINT `chat_channels_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_channels_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `chat_members`
--
ALTER TABLE `chat_members`
  ADD CONSTRAINT `chat_members_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `chat_channels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`channel_id`) REFERENCES `chat_channels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `chat_messages_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `chat_messages` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chat_reactions`
--
ALTER TABLE `chat_reactions`
  ADD CONSTRAINT `chat_reactions_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_messages`
--
ALTER TABLE `client_messages`
  ADD CONSTRAINT `client_messages_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_messages_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `client_portal`
--
ALTER TABLE `client_portal`
  ADD CONSTRAINT `client_portal_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contacts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `email_log`
--
ALTER TABLE `email_log`
  ADD CONSTRAINT `email_log_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `email_log_ibfk_2` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD CONSTRAINT `email_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expense_entries`
--
ALTER TABLE `expense_entries`
  ADD CONSTRAINT `expense_entries_ibfk_1` FOREIGN KEY (`month_id`) REFERENCES `expense_months` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expense_entries_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `expense_months`
--
ALTER TABLE `expense_months`
  ADD CONSTRAINT `expense_months_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  ADD CONSTRAINT `invoice_payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_payments_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `leads`
--
ALTER TABLE `leads`
  ADD CONSTRAINT `leads_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leads_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `lead_activities`
--
ALTER TABLE `lead_activities`
  ADD CONSTRAINT `lead_activities_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lead_activities_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `lead_gen_results`
--
ALTER TABLE `lead_gen_results`
  ADD CONSTRAINT `lead_gen_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lead_gen_settings`
--
ALTER TABLE `lead_gen_settings`
  ADD CONSTRAINT `lead_gen_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lead_gen_usage`
--
ALTER TABLE `lead_gen_usage`
  ADD CONSTRAINT `lead_gen_usage_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payslips`
--
ALTER TABLE `payslips`
  ADD CONSTRAINT `payslips_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `payslip_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payslips_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payslips_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payslip_templates`
--
ALTER TABLE `payslip_templates`
  ADD CONSTRAINT `payslip_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `project_members`
--
ALTER TABLE `project_members`
  ADD CONSTRAINT `project_members_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recent_searches`
--
ALTER TABLE `recent_searches`
  ADD CONSTRAINT `recent_searches_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rich_docs`
--
ALTER TABLE `rich_docs`
  ADD CONSTRAINT `rich_docs_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `rich_docs_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `rich_docs_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `saved_searches`
--
ALTER TABLE `saved_searches`
  ADD CONSTRAINT `saved_searches_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `social_accounts`
--
ALTER TABLE `social_accounts`
  ADD CONSTRAINT `social_accounts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `social_posts`
--
ALTER TABLE `social_posts`
  ADD CONSTRAINT `social_posts_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `social_accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `social_posts_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `social_posts_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `social_templates`
--
ALTER TABLE `social_templates`
  ADD CONSTRAINT `social_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `software_purchases`
--
ALTER TABLE `software_purchases`
  ADD CONSTRAINT `software_purchases_ibfk_1` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `software_purchases_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `whatsapp_messages`
--
ALTER TABLE `whatsapp_messages`
  ADD CONSTRAINT `whatsapp_messages_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `whatsapp_messages_ibfk_2` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `whatsapp_messages_ibfk_3` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `whatsapp_settings`
--
ALTER TABLE `whatsapp_settings`
  ADD CONSTRAINT `whatsapp_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `whatsapp_templates`
--
ALTER TABLE `whatsapp_templates`
  ADD CONSTRAINT `whatsapp_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

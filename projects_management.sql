-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 08, 2026 at 06:03 AM
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
(3, 'direct', NULL, NULL, 3, '2026-04-19 22:00:00');

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
(1, 1, 2, '2026-04-19 22:24:39'),
(2, 1, 3, '2026-05-06 09:19:27');

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
(1, '2026-01', 'January 2026', 6150.00, '', 3, '2026-04-18 13:49:09');

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
(101, 3, 'ChIJx8KUI3dZ4joRU2Ekj_njORQ', 'IAF - International Academy of Fashion (Pvt) Ltd', '', '077 760 4029', 'info@iafedu.com', '3 Coniston Pl, Colombo 00700, Sri Lanka', 'https://iafedu.com/', 1, 6, 4.6, 41, NULL, 27, 'all', 'Colombo, Sri Lanka', 'academy', 0, NULL, '2026-04-28 09:42:00', 0),
(102, 3, 'ChIJtRHWqmnN-joR9YOWjXCe2lQ', 'Hotel East Lagoon', '', '0652 229 222', '', 'Munai Lane, Sinna Uppodai Road, Uppodai Lake Rd, Batticaloa 30000, Sri Lanka', 'http://hoteleastlagoon.com/', 1, 2, 4.1, 1148, NULL, 35, 'all', 'Batticaloa, Sri Lanka', 'Hotel', 0, NULL, '2026-04-28 14:44:29', 0),
(103, 3, 'ChIJN_jboCnN-joRp-EryLQpfdU', 'Sea View Resort', '', '077 435 7610', 'info@seaviewresort.net', 'Navalady Rd, Kallady, Sri Lanka', 'https://www.seaviewresort.net/', 1, 3, 4.8, 161, NULL, 35, 'all', 'Batticaloa, Sri Lanka', 'Hotel', 0, NULL, '2026-04-28 14:44:35', 1),
(104, 3, 'ChIJy8yWLSnN-joR4UitCD8y8J8', 'Beni Beach Resort', '', '077 614 3389', '', '18/50, 8th Cross, Thiruchenthur Road, Kallady, Batticaloa 30000, Sri Lanka', '', 0, 4, 4.2, 149, NULL, 85, 'all', 'Batticaloa, Sri Lanka', 'Hotel', 0, NULL, '2026-04-28 14:44:36', 0),
(105, 3, 'ChIJORBs2kDN-joRiOLOA3ktu6E', 'Riviera Resort', '', '0652 222 164', 'bookings@riviera-online.com', 'New Dutch Bar Road, Kallady 30000, Sri Lanka', 'http://www.riviera-online.com/', 1, 5, 4.1, 1154, NULL, 35, 'all', 'Batticaloa, Sri Lanka', 'Hotel', 0, NULL, '2026-04-28 14:44:40', 0),
(106, 3, 'ChIJYWhTDQDN-joR2WQX0MGwwW8', 'Batti lagoon hotel', '', '077 545 2060', '', '53 New Road, Batticaloa, Sri Lanka', 'https://www.facebook.com/profile.php?id=61579771361310', 0, 6, 4.8, 24, NULL, 71, 'all', 'Batticaloa, Sri Lanka', 'Hotel', 0, NULL, '2026-04-28 14:44:43', 0),
(107, 3, 'ChIJjenSThzN-joRlnhw-ZRqKf4', 'Sri Construction Circuit bungalow', '', '077 777 5869', '', '18 St Michael\'s St, Batticaloa 30000, Sri Lanka', '', 0, 7, 4.8, 35, NULL, 77, 'all', 'Batticaloa, Sri Lanka', 'Hotel', 0, NULL, '2026-04-28 14:44:44', 0),
(108, 3, 'ChIJQe2aOQDN-joR6nq9YVs3agk', 'Inpan\'s Beach Resort', '', '077 902 6947', '', 'Navalady, Kallady, PMXX+MC7, Batticaloa, Sri Lanka', '', 0, 8, 4.6, 38, NULL, 77, 'all', 'Batticaloa, Sri Lanka', 'Hotel', 0, NULL, '2026-04-28 14:44:46', 0),
(109, 3, 'ChIJHVo03DjN-joRjbnZMaZ0ocg', 'Juda Holiday Villa', '', '077 232 6433', '', 'PP98+W6J, Navalady Rd, Kallady, Sri Lanka', 'https://juda.xtadia.com/', 1, 9, 4.5, 42, NULL, 27, 'all', 'Batticaloa, Sri Lanka', 'Hotel', 0, NULL, '2026-04-28 14:44:47', 0),
(110, 3, 'ChIJN6qPF3HN-joR-mNXtQrlsR4', 'Naval Beach Rest Inn (Naval Beach Villa)', '', '077 469 2121', '', '31, 07 School Rd, Batticaloa, Sri Lanka', 'http://www.housetrip.com/', 1, 10, 4.5, 169, NULL, 35, 'all', 'Batticaloa, Sri Lanka', 'Hotel', 0, NULL, '2026-04-28 14:44:50', 0),
(111, 3, 'ChIJMWMfXADN-joRn8jT8VIiJpQ', 'Dutch beach rest inn', '', '077 113 0305', '', 'PPG6+CQG, Dutch Bar, Sri Lanka', '', 0, 11, 4.7, 24, NULL, 71, 'all', 'Batticaloa, Sri Lanka', 'Hotel', 0, NULL, '2026-04-28 14:44:51', 0),
(112, 3, 'ChIJE2y_qUFZ4joRopMPAP5GxbQ', 'The HR Consortium (Private) Ltd', '', '0112 335 244', 'info@thrconsortium.com', '16, 3 Rotunda Gardens, Colombo 00300, Sri Lanka', 'http://www.thrconsortium.com/', 1, 2, 4.5, 38, NULL, 27, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-28 16:13:16', 0),
(113, 3, 'ChIJU6xNXBxZ4joRGddhvJWkUeg', 'Manpower Sri Lanka Recruitment Agencies', '', '077 786 1474', '', '12A Ridgeway Pl, Colombo 00400, Sri Lanka', 'http://www.manpowersrilanka.com/', 1, 3, 4.2, 223, NULL, 35, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-28 16:13:23', 0),
(114, 3, 'ChIJz9Yn2WBZ4joRjProCJdXAWE', 'HR Consultants Sri Lanka', '', '077 733 7512', 'info@manpowersrilanka.com', '12A Ridgeway Pl, Colombo 00400, Sri Lanka', 'http://www.manpowersrilanka.lk/', 1, 4, NULL, 0, NULL, 5, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-28 16:13:30', 0),
(115, 3, 'ChIJDYTCoRlZ4joR6BnO4fpxepM', 'Prominent HR Consultancy', '', '075 528 2867', '', '6A Deanston Pl, Colombo 00300, Sri Lanka', '', 0, 5, 5.0, 1, NULL, 65, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-28 16:13:31', 0),
(116, 3, 'ChIJW9eEZgXapAkRU1a7ve6jg_s', 'Formix', '', '071 798 5045', '', '14 Sir Baron Jayathilake Mawatha, Colombo 00100, Sri Lanka', 'https://formix.live/', 1, 6, 5.0, 13, NULL, 21, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-28 16:13:40', 0),
(117, 3, 'ChIJ5zCgVKP1qjsRuJ5wCwRPCd0', 'Anjappar Chettinadu Restaurant - Home Delivery & Outdoor Catering', '', '097874 70115', '', '73/18-2, near Vasan Eye Care, Annamalai Nagar, Tiruchirappalli, Tamil Nadu 620018, India', 'http://anjapparchettinadrestaurant.com/', 1, 2, 3.9, 1125, 2, 33, 'all', 'Trichy, India', 'Food delivery', 0, NULL, '2026-04-28 16:15:45', 0),
(118, 3, 'ChIJv1fKHqv1qjsRHIIhljU4e7I', 'Aasife Biriyani Trichy', '', '091500 41778', '', 'Old No 21, New, 42, Shastri Rd, Thillai Nagar, Tiruchirappalli, Tamil Nadu 620018, India', 'https://aasifebiriyani.com/store-locator/trichy/aasife-biriyani-thillai-nagar-trichy', 1, 3, 4.2, 2915, 2, 43, 'all', 'Trichy, India', 'Food delivery', 0, NULL, '2026-04-28 16:15:45', 0),
(119, 3, 'ChIJ7blt0KD1qjsRW91ys02Exug', 'Lunch Box Thillai Nagar', '', '073046 78007', '', '1st Floor, 75 E/3, Salai Rd, Thillai Nagar, Tiruchirappalli, Tamil Nadu 620018, India', '', 0, 4, 3.5, 33, NULL, 67, 'all', 'Trichy, India', 'Food delivery', 0, NULL, '2026-04-28 16:15:47', 0),
(120, 3, 'ChIJ6d5gcSz1qjsRHjcHUj7-sQQ', 'Shri Sangeethas (W.B. Road, Trichy) Veg. Restaurant | Sweets | Savouries', '', '099655 91028', '', 'Hotel Deepam Complex, 148, W Blvd Rd, Tharanallur, Tiruchirappalli, Tamil Nadu 620002, India', 'http://shrisangeethas.in/', 1, 5, 4.8, 3787, NULL, 35, 'all', 'Trichy, India', 'Food delivery', 0, NULL, '2026-04-28 16:15:50', 0),
(121, 3, 'ChIJN27CuZD1qjsRU4ioFrxAES4', 'Thamboora Restaurant Trichy', '', '097333 96000', '', 'C.27, North East Extension Fort Station Road 5th Cross Road East, Thillai Nagar Main Rd, Thillai Nagar East, North East Extension, Tennur, Tiruchirappalli, Tamil Nadu 620018, India', 'https://www.thamboora.com/', 1, 6, 4.4, 1783, NULL, 35, 'all', 'Trichy, India', 'Food delivery', 0, NULL, '2026-04-28 16:15:52', 0),
(122, 3, 'ChIJKcq2lK71qjsRg33Rq-tby8s', 'J B Textiles', '', '094874 86154', '', '96-42/13, Singarathope, Singarathope, Tharanallur, Tiruchirappalli, Tamil Nadu 620008, India', '', 0, 2, 4.9, 40, NULL, 77, 'all', 'Tiruchirappalli, India', 'Textile company', 0, NULL, '2026-04-28 16:16:43', 0),
(123, 3, 'ChIJeeQXG6z1qjsR2kMD4pbUaZ8', 'Sri Venkateshwara Textiles', '', '0431 270 1668', '', '129/2, Old No. 78, Big Bazaar St, Singarathope, Devathanam, Tiruchirappalli, Tamil Nadu 620008, India', '', 0, 3, 4.9, 21, NULL, 71, 'all', 'Tiruchirappalli, India', 'Textile company', 0, NULL, '2026-04-28 16:16:47', 0),
(124, 3, 'ChIJd9OEGhX1qjsRjnkrj7ZvSuA', 'CO-OPTEX, POTHIGAI, TRICHY', '', '0431 246 1191', '', 'Ashby Hotel Complex, 5, Rockins Rd, near Central Bus Stand, Melapudur, Sangillyandapuram, Tiruchirappalli, Tamil Nadu 620001, India', 'http://www.cooptex.com/', 0, 4, 4.2, 244, NULL, 85, 'all', 'Tiruchirappalli, India', 'Textile company', 0, NULL, '2026-04-28 16:16:48', 0),
(125, 3, 'ChIJfwldYwT1qjsRIqiKBDWp7O0', 'Tafra Clothing Trichy Readymade showroom, Kids Clothes, Men’s Wear, Ladies Wear', '', '096555 44131', 'tafra4755@gmail.com', '6, Parupukkara St, Palakarai, Sangillyandapuram, Tiruchirappalli, Tamil Nadu 620008, India', 'https://tafraclothing.nowfloats.com/', 1, 5, 4.8, 1026, NULL, 35, 'all', 'Tiruchirappalli, India', 'Textile company', 0, NULL, '2026-04-28 16:16:49', 0),
(126, 3, 'ChIJXxrc2q31qjsRhtGlSsPqJBw', 'TRICHY SARATHAS', '', '0431 270 2077', '', 'Trichy Saratha\'s, 45, NSB Rd, Singarathope, Theppakulam, Tiruchirappalli, Tamil Nadu 620002, India', 'https://www.trichysarathas.com/', 1, 6, 4.3, 17377, NULL, 35, 'all', 'Tiruchirappalli, India', 'Textile company', 0, NULL, '2026-04-28 16:16:55', 1),
(127, 3, 'ChIJPWpQiuHN-joRC9bAVBCXpfo', 'RoaBaa Guesthouse', '', '077 410 7755', '', '501/8 Trincomalee Hwy, Batticaloa 30000, Sri Lanka', '', 0, 2, 4.7, 152, NULL, 85, 'all', 'Batticaloa, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 08:45:18', 0),
(128, 3, 'ChIJt_mX2qDS-joRD0nbAfmWEPY', 'City Lagoon Guest House', '', '077 008 0051', '', '# 92/10 Poombukar Street, Batticaloa 30000, Sri Lanka', '', 0, 3, 4.2, 119, NULL, 85, 'all', 'Batticaloa, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 08:45:20', 0),
(129, 3, 'ChIJZwM371HN-joRT0QacGGWZfI', 'Batticaloa Rest House', '', '0652 227 881', '', 'PP62+9GP, Batticaloa, Sri Lanka', '', 0, 4, 3.9, 150, NULL, 75, 'all', 'Batticaloa, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 08:45:22', 0),
(130, 3, 'ChIJjenSThzN-joRlnhw-ZRqKf4', 'Sri Construction Circuit bungalow', '', '077 777 5869', '', '18 St Michael\'s St, Batticaloa 30000, Sri Lanka', '', 0, 5, 4.8, 35, NULL, 77, 'all', 'Batticaloa, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 08:45:23', 0),
(131, 3, 'ChIJGZTCoHDN-joRXLBn6plpzFs', 'East Gate 8-9', '', '077 711 8775', '', '46, 9 Hospital Rd, Batticaloa 30000, Sri Lanka', '', 0, 6, 4.5, 15, NULL, 71, 'all', 'Batticaloa, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 08:45:24', 0),
(132, 3, 'ChIJ5ZRLl1zN-joRzpUVbtcL15E', 'Learn & Study Tuition Center', '', '077 615 2535', '', 'PPC2+H6C, Batticaloa, Sri Lanka', '', 0, 2, 5.0, 7, NULL, 65, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 08:45:40', 0),
(133, 3, 'ChIJI_rLhDFTzwwRLlquUjcccms', 'SPM Academy Batticaloa Branch', '', '074 016 3188', 'academy@spm.indust', '26/4A Boundary Rd S, Batticaloa 30000, Sri Lanka', 'https://academy.spm.industries/', 1, 3, 5.0, 17, NULL, 21, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 08:45:42', 0),
(134, 3, 'ChIJ2VV1vGXN-joRWrzQ8bfxS8Q', 'Study and Learning Centre. (Church of Ceylon, Eastern Deanery - Dioceses of Colombo)', '', '', '', 'PPM2+85G, Sakarias Rd, Batticaloa, Sri Lanka', '', 0, 4, 5.0, 1, NULL, 60, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 08:45:42', 0),
(135, 3, 'ChIJY_HU6F7N-joR_1mINYxql2c', 'GAMA ABACUS BATTICALOA', '', '077 579 9033', 'info@ynotinfo.com', 'Room No: 5, No 10 MPCS, Railway Station Road, batticaloa 03000, Sri Lanka', 'http://www.gamaabacus.com/', 1, 5, NULL, 0, NULL, 5, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 08:45:47', 0),
(136, 3, 'ChIJTehPrpfN-joRxk6tQKmb-yY', 'HBS College Batticaloa', '', '0652 059 199', '', 'PP92+63C, Old Rest House Road, Batticaloa, Sri Lanka', '', 0, 6, 3.8, 6, NULL, 55, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 08:45:48', 0),
(137, 3, 'ChIJZ7_LCFvN-joRHVgCWjkDWTk', 'New Batticaloa Medicals', '', '0652 224 400', '', 'PPC2+3G7, Bar Rd, Batticaloa, Sri Lanka', '', 0, 2, 4.1, 17, NULL, 71, 'all', 'Batticaloa, Sri Lanka', 'Pharmacy', 0, NULL, '2026-04-30 08:46:06', 0),
(138, 3, 'ChIJX5CMJPrN-joRHWETVnjtAWg', 'TIP TOP Health Care', '', '0652 222 221', '', 'Central Road, Batticaloa, Sri Lanka', 'https://tiptophealthcare.com/', 1, 3, 4.7, 6, NULL, 15, 'all', 'Batticaloa, Sri Lanka', 'Pharmacy', 0, NULL, '2026-04-30 08:46:22', 1),
(139, 3, 'ChIJWwM7VEPN-joRVatooaxksf8', 'Akshaya Pharmacy', '', '0652 223 366', '', '14 Baily Rd, Batticaloa, Sri Lanka', '', 0, 4, 3.6, 10, NULL, 61, 'all', 'Batticaloa, Sri Lanka', 'Pharmacy', 0, NULL, '2026-04-30 08:46:23', 0),
(140, 3, 'ChIJX_hLuFDN-joRcrSQHk9Gpd4', 'New V Care Pharmacy', '', '077 935 6595', '', '7 Munai St, Batticaloa, Sri Lanka', '', 0, 5, 4.2, 6, NULL, 65, 'all', 'Batticaloa, Sri Lanka', 'Pharmacy', 0, NULL, '2026-04-30 08:46:25', 0),
(141, 3, 'ChIJUwiKf1vT-joRib6ghiH1E5U', 'SK Siranjeevi pharmacy', '', '0652 224 990', '', '570 Trincomalee Hwy, Batticaloa, Sri Lanka', '', 0, 6, 4.8, 5, NULL, 65, 'all', 'Batticaloa, Sri Lanka', 'Pharmacy', 0, NULL, '2026-04-30 08:46:27', 0),
(142, 3, 'ChIJvzc8YqrS-joRMJsdBg10SlE', 'Teaching Hospital, Batticaloa போதனா வைத்தியசாலை, மட்டக்களப்பு', '', '0652 222 261', '', 'PM5R+669, Hospital Ln, Batticaloa 30000, Sri Lanka', 'http://www.thbatti.health.gov.lk/', 1, 2, 4.0, 130, NULL, 35, 'all', 'Batticaloa, Sri Lanka', 'clinic', 0, NULL, '2026-04-30 08:46:45', 0),
(143, 3, 'ChIJK2CCO1vN-joRD6tUKmdl7qA', 'New Pioneer Hospital Batticaloa', '', '0652 223 642', 'info@nph.lk', '91 Pioneer Rd, Batticaloa, Sri Lanka', 'http://newpioneerhospital.com/', 1, 3, 3.4, 91, NULL, 17, 'all', 'Batticaloa, Sri Lanka', 'clinic', 0, NULL, '2026-04-30 08:46:56', 0),
(144, 3, 'ChIJbQaIZUPN-joRm05eZINJt6M', 'GV Hospital', '', '0652 223 076', 'dr.zahidafzal@gmail.com', 'PPC3+JHF, New Kalmunai Rd, Batticaloa 30000, Sri Lanka', 'https://www.gvhospital.com/', 1, 4, 3.1, 118, NULL, 25, 'all', 'Batticaloa, Sri Lanka', 'clinic', 0, NULL, '2026-04-30 08:47:04', 1),
(145, 3, 'ChIJWUhfHADT-joR4GXIOIHBJJU', 'Aarokiya Medical Clinic & Laboratories ( ஆரோக்கியா)', '', '076 610 1066', 'support@labtech.lk', 'PMQJ+568, Batticaloa, Sri Lanka', 'http://www.labtech.lk/', 1, 5, 5.0, 2, NULL, 15, 'all', 'Batticaloa, Sri Lanka', 'clinic', 0, NULL, '2026-04-30 08:47:07', 0),
(146, 3, 'ChIJL54G81XN-joRzU-PqFfSJSM', 'Batticaloa Chest Clinic', '', '0652 222 261', '', 'PM5R+CMV, Mathew\'s Rd, Batticaloa, Sri Lanka', '', 0, 6, 4.5, 4, NULL, 65, 'all', 'Batticaloa, Sri Lanka', 'clinic', 0, NULL, '2026-04-30 08:47:09', 0),
(147, 3, 'ChIJu1HDyTbN-joRmIkDsNGTk8o', 'Six Flav Kitchen', '', '075 333 3983', '', 'Lloyd\'s Ave, Batticaloa 30000, Sri Lanka', '', 0, 2, 3.9, 369, NULL, 75, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:47:38', 0),
(148, 3, 'ChIJycjVaQDN-joRHw-OE3xcEcs', 'Soull Kitchen', '', '0652 054 235', '', 'Saravana Rd, Batticaloa, Sri Lanka', 'https://m.facebook.com/Soullkitchen.lk/', 0, 3, 4.8, 98, NULL, 77, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:47:40', 0),
(149, 3, 'ChIJ3RPnYADN-joRCPuMuEKtRRw', 'Aahaa restaurant', '', '070 522 6740', '', 'Trincomalee Hwy, Batticaloa, Sri Lanka', 'https://aahaarestaurant.com/', 1, 4, 3.9, 207, NULL, 25, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:47:47', 1),
(150, 3, 'ChIJR1Ejp5bS-joRDtGzFE4K8VI', 'Kiri Bhojan Restaurant', '', '0652 055 551', '', 'PMJF+2WP, Trinco Rd, Batticaloa, Sri Lanka', '', 0, 5, 3.9, 532, 2, 83, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:47:49', 0),
(151, 3, 'ChIJBbTV3kbN-joRC2-SZKRKrqE', 'Riviera Crab Cabana', '', '0652 222 164', 'bookings@riviera-online.com', 'New Dutch Bar Road, Kallady, Batticaloa 30000, Sri Lanka', 'http://www.riviera-online.com/dining/', 1, 6, 3.7, 81, NULL, 17, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:47:53', 0),
(152, 3, 'ChIJud9IfQDN-joRH5ofAnQBLEE', 'Aqua Fort Restaurant', '', '076 882 2229', '', 'PP52+JGR, Fort Rd, Batticaloa, Sri Lanka', 'https://web.facebook.com/p/Aqua-Fort-Restaurant-61561071273539/?_rdc=1&_rdr', 0, 7, 4.0, 110, NULL, 85, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:47:56', 0),
(153, 3, 'ChIJzRTfnVbT-joR1N8Vr-teJU4', 'Gama Gama Indian and Chinese Cuisine', '', '077 668 0733', '', 'PMHG+WWR Junction, Batticaloa, Sri Lanka', 'https://www.facebook.com/GamaGamaBiryani/', 0, 8, 4.0, 98, NULL, 77, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:47:59', 0),
(154, 3, 'ChIJPSaJo8oz5ToRvVLslIycoM4', 'Odai Family Restaurant', '', '077 424 3740', '', 'MP7H+53Q Kankeyanodai, 30150, Sri Lanka', '', 0, 9, 4.1, 162, NULL, 85, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:48:00', 0),
(155, 3, 'ChIJebXeo1jN-joR1EaUC6HCfPE', 'Sri Kishna cafe', '', '0652 228 900', '', '61A Kannaki Amman Kovil Rd, Batticaloa 30000, Sri Lanka', '', 0, 10, 4.2, 517, 1, 85, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:48:02', 0),
(156, 3, 'ChIJHYmz_VDN-joRMMSi5184PpE', 'Hajiyar Hotel', '', '0652 225 639', '', 'PM7X+4H6, Batticaloa, Sri Lanka', '', 0, 11, 3.8, 678, 2, 83, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:48:03', 0),
(157, 3, 'ChIJT4i24lvN-joRDs9RQngehYg', 'Sunshine', '', '0654 927 927', '', '136 Trinco Rd, Batticaloa 30000, Sri Lanka', 'http://www.tomato.lk/', 1, 12, 3.6, 877, 2, 33, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:48:04', 0),
(158, 3, 'ChIJ8bqKqlrN-joRw8XvQQxCLNQ', 'Cafe Chill', '', '077 777 9598', '', 'Pioneer Rd, Batticaloa 30000, Sri Lanka', 'http://www.facebook.com/pages/Cafe-Chill/215055691855290', 0, 13, 4.3, 157, 1, 85, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:48:06', 0),
(159, 3, 'ChIJsZS4lJTN-joRL01Y9aB6L5E', 'Fort Park ARK Restaurant', '', '070 680 9477', '', 'PP52+R8V, Batticaloa, Sri Lanka', '', 0, 14, 4.4, 28, NULL, 71, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:48:08', 0),
(160, 3, 'ChIJn96PVADN-joRjH1x43Rie08', 'China town', '', '0654 677 448', '', '39 Covington\'s Rd, Batticaloa 30410, Sri Lanka', '', 0, 15, 4.6, 27, NULL, 71, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:48:13', 0),
(161, 3, 'ChIJi-rVQiHT-joRH4TBgLde4aQ', 'Shaan Food court', '', '075 736 5784', '', '337 Trinco Rd, Batticaloa, Sri Lanka', 'https://www.facebook.com/profile.php?id=100093648773852&mibextid=LQQJ4d', 0, 16, 4.5, 66, NULL, 77, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:48:16', 0);
INSERT INTO `lead_gen_results` (`id`, `user_id`, `place_id`, `name`, `owner_name`, `phone`, `email`, `address`, `website`, `has_website`, `api_calls`, `rating`, `ratings_total`, `price_level`, `opportunity_score`, `search_mode`, `location`, `industry`, `imported`, `lead_id`, `created_at`, `website_found_by_crawler`) VALUES
(162, 3, 'ChIJS2snaQDT-joR0FWDMQdu-RA', 'CAFE CRUSH', '', '077 733 3049', '', 'no. 568 Trincomalee Hwy, Batticaloa, Sri Lanka', '', 0, 17, 4.5, 8, NULL, 65, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:48:20', 0),
(163, 3, 'ChIJtW5Q9x_T-joR4EbTxBPy3Cw', 'Kalappu Restaurant', '', '071 915 6038', '', 'PMGP+Q8F, Slaughter House Lake Rd, Batticaloa, Sri Lanka', 'https://www.facebook.com/mrblackneibarotta?mibextid=LQQJ4d', 0, 18, 3.6, 136, NULL, 75, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:48:24', 0),
(164, 3, 'ChIJ9WbfaPTN-joRqX_iG_fp50o', 'Traditional Restaurant', '', '077 803 8056', '', 'PM7X+QVQ, Lady Manning Dr, Batticaloa, Sri Lanka', '', 0, 19, 4.8, 4, NULL, 65, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:48:29', 0),
(165, 3, 'ChIJTV5MVwDT-joRJwl7_Z-ojRY', 'Cikolata', '', '0652 226 691', '', '442 Trincomalee Hwy, Batticaloa 30000, Sri Lanka', 'https://cikolata.lk/', 1, 20, NULL, 83, NULL, 17, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:48:31', 0),
(166, 3, 'ChIJb076T7rN-joR2uM2UwPkMd8', 'Max1 Magic', '', '076 422 2433', '', 'No 75 Central Road, Batticaloa 30000, Sri Lanka', 'https://www.facebook.com/max1magic', 0, 21, 4.3, 154, NULL, 85, 'all', 'Batticaloa, Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 08:48:34', 0),
(167, 3, 'ChIJbU5NVv3N-joRsimCnwV_9nc', 'ALEX Driving school', '', '077 878 7844', '', '63 B27, Batticaloa, Sri Lanka', '', 0, 2, 4.7, 6, NULL, 65, 'all', 'Batticaloa, Sri Lanka', 'Driving school', 0, NULL, '2026-04-30 08:49:42', 0),
(168, 3, 'ChIJnfUzD_TV-joRvDBoXGKAfPE', 'Gopi Driving School Batticaloa', '', '077 937 7399', '', '09 Market road, Batticaloa, Sri Lanka', '', 0, 3, 4.0, 1, NULL, 65, 'all', 'Batticaloa, Sri Lanka', 'Driving school', 0, NULL, '2026-04-30 08:49:42', 0),
(169, 3, 'ChIJceFF6i7N-joRb3AQ4tLIsCs', 'City driving school', '', '075 644 7880', '', 'PPC2+VJP, Batticaloa, Sri Lanka', 'https://www.citydrivingschool.com/', 1, 4, 5.0, 2, NULL, 15, 'all', 'Batticaloa, Sri Lanka', 'Driving school', 0, NULL, '2026-04-30 08:49:45', 1),
(170, 3, 'ChIJkVAUfNLN-joRpkJhjQDurwQ', 'Nadheera learners', '', '075 750 0030', '', 'Lady meaning drive market road, Batticaloa 30000, Sri Lanka', '', 0, 5, NULL, 0, NULL, 55, 'all', 'Batticaloa, Sri Lanka', 'Driving school', 0, NULL, '2026-04-30 08:49:46', 0),
(171, 3, 'ChIJzbnerV3N-joRo8pvqz-l_DA', 'Iyangaran Learners', '', '077 797 4329', '', 'PMFX+FGC, B27, Batticaloa, Sri Lanka', '', 0, 6, 3.7, 3, NULL, 55, 'all', 'Batticaloa, Sri Lanka', 'Driving school', 0, NULL, '2026-04-30 08:49:47', 0),
(172, 3, 'ChIJPTTzWaYz5ToRsAotWavSHxY', 'AHNA driving school', '', '077 948 9700', '', 'Main street, Batticaloa, Sri Lanka', '', 0, 7, NULL, 0, NULL, 55, 'all', 'Batticaloa, Sri Lanka', 'Driving school', 0, NULL, '2026-04-30 08:49:47', 0),
(173, 3, 'ChIJ0YSHsjnT-joRP0tIFpMJdpE', 'Pushpa driving school', '', '075 675 5722', '', 'Market, road, Batticaloa, Sri Lanka', '', 0, 8, 1.0, 1, NULL, 55, 'all', 'Batticaloa, Sri Lanka', 'Driving school', 0, NULL, '2026-04-30 08:49:48', 0),
(174, 3, 'ChIJ0R4qIgDT-joRZ9LTGaCg5Js', 'Alex driving school', '', '', '', 'QM96+6X, Batticaloa, Sri Lanka', '', 0, 9, NULL, 0, NULL, 50, 'all', 'Batticaloa, Sri Lanka', 'Driving school', 0, NULL, '2026-04-30 08:49:53', 0),
(175, 3, 'ChIJwdMZJZzT-joRPKu0s0_6A8M', 'Vikneswara lernece', '', '', '', 'PMJF+9VX, Batticaloa, Sri Lanka', '', 0, 10, NULL, 0, NULL, 50, 'all', 'Batticaloa, Sri Lanka', 'Driving school', 0, NULL, '2026-04-30 08:49:54', 0),
(176, 3, 'ChIJvVR2dHln4zoRxnQBOMzyJ1A', 'Kandy Fortress', '', '077 038 6426', '', '103, 46 Dharmaraja Mawatha, Kandy, Sri Lanka', '', 0, 2, 4.8, 77, NULL, 77, 'all', 'Kandy, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 08:51:02', 0),
(177, 3, 'ChIJE2y_qUFZ4joRopMPAP5GxbQ', 'The HR Consortium (Private) Ltd', '', '0112 335 244', 'info@thrconsortium.com', '16, 3 Rotunda Gardens, Colombo 00300, Sri Lanka', 'http://www.thrconsortium.com/', 1, 2, 4.5, 38, NULL, 27, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:51:35', 0),
(178, 3, 'ChIJDYTCoRlZ4joR6BnO4fpxepM', 'Prominent HR Consultancy', '', '075 528 2867', '', '6A Deanston Pl, Colombo 00300, Sri Lanka', '', 0, 3, 5.0, 1, NULL, 65, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:51:37', 0),
(179, 3, 'ChIJz9Yn2WBZ4joRjProCJdXAWE', 'HR Consultants Sri Lanka', '', '077 733 7512', 'info@manpowersrilanka.com', '12A Ridgeway Pl, Colombo 00400, Sri Lanka', 'http://www.manpowersrilanka.lk/', 1, 4, NULL, 0, NULL, 5, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:51:44', 0),
(180, 3, 'ChIJW9eEZgXapAkRU1a7ve6jg_s', 'Formix', '', '071 798 5045', '', '14 Sir Baron Jayathilake Mawatha, Colombo 00100, Sri Lanka', 'https://formix.live/', 1, 5, 5.0, 13, NULL, 21, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:51:54', 0),
(181, 3, 'ChIJUQhnquRY4joRbKEOeWxGgrg', 'Arabian Global Consulting(pvt) Ltd', '', '0112 424 740', '', '255-1/1 Sri Saddarma Mawatha, Colombo 01000, Sri Lanka', 'https://arabian-global.com/', 1, 6, 4.7, 29, NULL, 21, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:51:57', 0),
(182, 3, 'ChIJU6xNXBxZ4joRGddhvJWkUeg', 'Manpower Sri Lanka Recruitment Agencies', '', '077 786 1474', '', '12A Ridgeway Pl, Colombo 00400, Sri Lanka', 'http://www.manpowersrilanka.com/', 1, 7, 4.2, 223, NULL, 35, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:52:03', 0),
(183, 3, 'ChIJhU4IYpZZ4joRGvAWinSvuWY', 'JIT Resourcing & Consultancy Services', '', '0112 574 083', 'tirantha@jithpl.com', '370, 3rd Floor, Galle Rd, Colombo 00300, Sri Lanka', 'https://www.jitrcs.com/', 1, 8, 5.0, 31, NULL, 27, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:52:11', 0),
(184, 3, 'ChIJKaqsH4tb4joRR750_39W-hY', 'Central HR Solutions', '', '077 743 1596', 'hello@centralhrsolutions.com', '8 Boteju Rd, Colombo 00500, Sri Lanka', 'http://www.centralhrsolutions.com/', 1, 9, 5.0, 3, NULL, 15, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:52:12', 0),
(185, 3, 'ChIJa1VOzMRb4joRyUTKrAZ_VcM', 'Al Karrim Lanka Consultants (Pvt) Ltd', '', '0112 501 675', '', 'no 10, 1 R. A. De Mel Mawatha, Colombo 00500, Sri Lanka', '', 0, 10, 4.3, 99, NULL, 77, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:52:13', 0),
(186, 3, 'ChIJTTE0K9tb4joR0GCF3iTHHH8', 'CAREER 141', '', '075 359 5495', '', '35\'1 Stubbs Pl, Colombo 00500, Sri Lanka', 'http://www.career141.com/', 1, 11, 4.6, 154, NULL, 35, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:52:14', 0),
(187, 3, 'ChIJz9RK4w9Z4joRDP9S86zlWho', 'YONA HR', '', '0114 294 888', '', '7/13, 1/1 Pinthaliya Rd, Dehiwala-Mount Lavinia 10350, Sri Lanka', 'http://www.yonahr.com/', 1, 12, 4.5, 13, NULL, 21, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:52:18', 0),
(188, 3, 'ChIJ3e3mP9ZZ4joRPoiLaMAEDC8', 'Recruitment Consultants PVT LTD', '', '077 989 6568', '', 'WV6J+V78, Colombo, Sri Lanka', '', 0, 13, 4.3, 30, NULL, 77, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:52:20', 0),
(189, 3, 'ChIJTakpxXRb4joRTCv9_sUAwG0', 'Lucky HR Solution (Pvt) Ltd', '', '077 423 9191', 'info@luckyhrgroup.com', '186, 1, 2nd Floor, 2 Galle Rd, Colombo 00400, Sri Lanka', 'https://www.luckyhrgroup.com/', 1, 14, 4.3, 36, NULL, 27, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:52:23', 0),
(190, 3, 'ChIJSX4erbpb4joRtLjPAvfCP3s', 'Direct Lines (Pvt) Ltd.', '', '0112 582 879', '', '379 Galle Rd, Colombo 00600, Sri Lanka', 'https://directlines.lk/', 1, 15, 4.3, 65, NULL, 27, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:52:29', 0),
(191, 3, 'ChIJceDk54ZZ4joRw7rp2MTBX2k', 'ITS Grandeur Pvt Ltd', '', '077 706 6116', 'hello@itsgrandeur.lk', 'Regus Se Saya Building, 234/ 4 Sri Jayawardenepura Mawatha, Sri Jayawardenepura Kotte 10107, Sri Lanka', 'https://www.itsgrandeur.lk/', 1, 16, 5.0, 48, NULL, 27, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:52:29', 0),
(192, 3, 'ChIJi6oXjWRb4joRlbVEGCM0-ZI', 'Eastern Charisma Group', '', '077 786 1474', 'info@easterncharisma.com', '12A Ridgeway Pl, Colombo 00400, Sri Lanka', 'http://www.easterncharisma.com/', 1, 17, 4.8, 11, NULL, 21, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:52:34', 0),
(193, 3, 'ChIJ-____2Za4joRL6o9VNsfQfM', 'The Headmasters Lanka (Pvt) Limited', '', '076 138 1000', 'info@headmastershr.com', '3rd floor no, 126b High Level Rd, Nugegoda 10250, Sri Lanka', 'http://www.headmastershr.com/', 1, 18, 4.1, 61, NULL, 27, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:52:40', 0),
(194, 3, 'ChIJ1a273CVa4joRzcj2-CH7Eqc', 'Kent Ridge (Pvt) Ltd', '', '0112 805 155', 'hr@kentridge.lk', '18 Swarna Pl, Sri Jayawardenepura Kotte 10107, Sri Lanka', 'https://kentridge.lk/', 1, 19, 4.7, 6, NULL, 15, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:52:44', 0),
(195, 3, 'ChIJL_zFuf1Y4joRHx3btTA3pEQ', 'AlQareem Agency (Pvt) Ltd', '', '0112 472 021', 'alqareemagency@outlook.com', '143 Grandpass Rd, Colombo 01400, Sri Lanka', 'https://alqareemagency.com/', 1, 20, 4.6, 63, NULL, 27, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:52:56', 1),
(196, 3, 'ChIJj4gJMSRZ4joRyC1KqinHAbE', 'TGL Jobs Human Resource Agency (Trans Gulf)', '', '0114 426 222', '', '90 Chatham St, Colombo 00100, Sri Lanka', 'https://www.tgljobs.com/', 1, 21, 4.2, 259, NULL, 35, 'all', 'Colombo, Sri Lanka', 'HR consultancy', 0, NULL, '2026-04-30 08:52:56', 0),
(197, 3, 'ChIJQZqGal1Z4joRiusKkCGF9k4', 'Mister T - Real Estate Agency', '', '077 325 2566', 'info@mistert.lk', '8/6 1st Ln, Sri Jayawardenepura Kotte 11222, Sri Lanka', 'http://mistert.lk/', 1, 2, 4.8, 358, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:53:21', 0),
(198, 3, 'ChIJ8_jSGGFZ4joRethj_dVWe8w', 'Elegant Real Estate', '', '077 711 5773', 'info@elegantrealestate.lk', '237 Vauxhall St, Colombo 01000, Sri Lanka', 'https://elegantrealestate.lk/', 1, 3, 4.9, 71, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:53:25', 0),
(199, 3, 'ChIJPd1lztNZ4joRaQMl3PshWCg', '73 Avenue Realtors (Pvt) Ltd | Century 21', '', '076 360 7373', 'info@73avenuerealtors.com', '75/7 Ward Pl, Colombo 00700, Sri Lanka', 'https://73avenuerealtors.com/', 1, 4, 4.7, 85, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:53:26', 0),
(200, 3, 'ChIJI_gG-Uxa4joRUGAFimFU1HU', 'Lanka Property Web', '', '076 716 7167', '', '1, 01 Bagatalle Rd, Colombo 00300, Sri Lanka', 'https://www.lankapropertyweb.com/', 1, 5, 4.2, 232, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:53:30', 0),
(201, 3, 'ChIJhcWFJtdZ4joRrsJoXEgW0J0', 'ZOOM REAL ESTATE', '', '077 450 7000', '', '11, 1 Malalasekara Pl, Colombo 00700, Sri Lanka', '', 0, 6, 5.0, 24, NULL, 71, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:53:37', 0),
(202, 3, 'ChIJSw2aEZVZ4joRyzMLCDKLR7g', 'Bimsara Real Estate', '', '0117 778 777', '', 'Safetynet (Private) Limited, 199/58, Obesekara Crescent, Rajagiriya Rd, Sri Jayawardenepura Kotte 10107, Sri Lanka', 'https://www.bimsara.com/', 1, 7, 4.9, 185, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:53:39', 0),
(203, 3, 'ChIJf5klZzpa4joRN8CxfN1Ulwk', 'Colombo Realtors', '', '077 991 4407', 'info@colomborealtors.lk', '2nd Floor, No.275, Lotus Building, Nawala Road, Nawala 10100, Sri Lanka', 'https://www.colomborealtors.lk/', 1, 8, 4.8, 23, NULL, 21, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:53:42', 0),
(204, 3, 'ChIJ61-kF2dZ4joRm7gc2FG8v28', 'Pan Global Properties (Pvt) Ltd', '', '0115 300 100', 'info@panglobalproperty.com', '351, 1 R. A. De Mel Mawatha, Colombo 00300, Sri Lanka', 'https://panglobalproperty.lk/', 1, 9, 4.7, 51, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:53:47', 0),
(205, 3, 'ChIJqfQVSxNZ4joRW6r0iSIFBWE', 'Acquest (Pvt) Ltd', '', '0114 010 203', 'info@acquest.lk', 'Acquest (Pvt) Ltd, Level 16, Access Tower II, 278 Union Pl, Colombo 00200, Sri Lanka', 'http://www.acquest.lk/', 1, 10, 3.8, 43, NULL, 17, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:53:53', 0),
(206, 3, 'ChIJ0S3MfrxZ4joRkbVQNHjAlcE', 'Shuqak Properties Pvt Ltd', '', '077 474 8130', 'info@shuqakproperties.com', '160, Justice Akbar Mawatha, Union Pl, Colombo 02000, Sri Lanka', 'https://shuqakproperties.com/', 1, 11, 4.9, 76, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:53:53', 0),
(207, 3, 'ChIJY6yqJPwLEU0RmvwMRBbJ39s', 'Emil Realtors (Pvt) Ltd', '', '077 916 2684', 'info@emilrealtors.com', '160, 05 Vijaya Kumarathunga Mawatha, Colombo 00500, Sri Lanka', 'https://emilrealtors.com/', 1, 12, 4.9, 59, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:53:58', 0),
(208, 3, 'ChIJ3URw08Vb4joRm3X3wUTnjjU', 'AKARA Apartments PVT LTD.', '', '077 374 4156', 'info@akaraapartments.lk', '20/8 Fairfield Gardens, Colombo 00800, Sri Lanka', 'http://www.akaraapartments.lk/', 1, 13, 4.4, 158, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:53:59', 0),
(209, 3, 'ChIJ68Fh8Elb4joRwBUEFiDTrts', 'Exclusive Realtors', '', '077 639 6655', 'interested@domainmarket.com', '18/187 Muhandiram E D Dabare Mawatha, Evergreen Park Rd, Colombo 00500, Sri Lanka', 'https://exclusiverealtors.com/', 1, 14, NULL, 7, NULL, 5, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:54:11', 1),
(210, 3, 'ChIJ8Rl3PzxZ4joR9LGxxCIH6ms', 'Mohan Morais Real Estate (Pvt) Ltd', '', '077 225 1057', 'mohan@mmre.lk', '5 Charles Pl, Colombo 00300, Sri Lanka', 'https://www.mmre.lk/', 1, 15, 5.0, 6, NULL, 15, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:54:13', 0),
(211, 3, 'ChIJMxF7fLtb4joRY-_K_mSvd8A', 'AARCO Real Estate Agents Sri Lanka', '', '077 558 4476', 'info@aarcorealestate.lk', 'Real Estate Agents Hub, 12A Ridgeway Pl, කොළඹ 00400, Sri Lanka', 'http://www.aarcorealestate.lk/', 1, 16, NULL, 25, NULL, 11, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:54:19', 0),
(212, 3, 'ChIJf8qoUUNb4joRz2BEvlt4Y1w', 'Realtors Lanka', '', '', '', 'Colombo 3, Colombo 00300, Sri Lanka', '', 0, 17, 4.7, 15, NULL, 66, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:54:30', 0),
(213, 3, 'ChIJQZqGal1Z4joRiusKkCGF9k4', 'Mister T - Real Estate Agency', '', '077 325 2566', 'info@mistert.lk', '8/6 1st Ln, Sri Jayawardenepura Kotte 11222, Sri Lanka', 'http://mistert.lk/', 1, 2, 4.8, 358, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:55:25', 0),
(214, 3, 'ChIJ8_jSGGFZ4joRethj_dVWe8w', 'Elegant Real Estate', '', '077 711 5773', 'info@elegantrealestate.lk', '237 Vauxhall St, Colombo 01000, Sri Lanka', 'https://elegantrealestate.lk/', 1, 3, 4.9, 71, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:55:27', 0),
(215, 3, 'ChIJPd1lztNZ4joRaQMl3PshWCg', '73 Avenue Realtors (Pvt) Ltd | Century 21', '', '076 360 7373', 'info@73avenuerealtors.com', '75/7 Ward Pl, Colombo 00700, Sri Lanka', 'https://73avenuerealtors.com/', 1, 4, 4.7, 85, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:55:28', 0),
(216, 3, 'ChIJI_gG-Uxa4joRUGAFimFU1HU', 'Lanka Property Web', '', '076 716 7167', '', '1, 01 Bagatalle Rd, Colombo 00300, Sri Lanka', 'https://www.lankapropertyweb.com/', 1, 5, 4.2, 232, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:55:33', 0),
(217, 3, 'ChIJhcWFJtdZ4joRrsJoXEgW0J0', 'ZOOM REAL ESTATE', '', '077 450 7000', '', '11, 1 Malalasekara Pl, Colombo 00700, Sri Lanka', '', 0, 6, 5.0, 24, NULL, 71, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:55:37', 0),
(218, 3, 'ChIJSw2aEZVZ4joRyzMLCDKLR7g', 'Bimsara Real Estate', '', '0117 778 777', '', 'Safetynet (Private) Limited, 199/58, Obesekara Crescent, Rajagiriya Rd, Sri Jayawardenepura Kotte 10107, Sri Lanka', 'https://www.bimsara.com/', 1, 7, 4.9, 185, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:55:39', 0),
(219, 3, 'ChIJf5klZzpa4joRN8CxfN1Ulwk', 'Colombo Realtors', '', '077 991 4407', 'info@colomborealtors.lk', '2nd Floor, No.275, Lotus Building, Nawala Road, Nawala 10100, Sri Lanka', 'https://www.colomborealtors.lk/', 1, 8, NULL, 23, NULL, 11, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:55:42', 0),
(220, 3, 'ChIJ61-kF2dZ4joRm7gc2FG8v28', 'Pan Global Properties (Pvt) Ltd', '', '0115 300 100', 'info@panglobalproperty.com', '351, 1 R. A. De Mel Mawatha, Colombo 00300, Sri Lanka', 'https://panglobalproperty.lk/', 1, 9, 4.7, 51, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:55:42', 0),
(221, 3, 'ChIJqfQVSxNZ4joRW6r0iSIFBWE', 'Acquest (Pvt) Ltd', '', '0114 010 203', 'info@acquest.lk', 'Acquest (Pvt) Ltd, Level 16, Access Tower II, 278 Union Pl, Colombo 00200, Sri Lanka', 'http://www.acquest.lk/', 1, 10, 3.8, 43, NULL, 17, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:55:46', 0),
(222, 3, 'ChIJ0S3MfrxZ4joRkbVQNHjAlcE', 'Shuqak Properties Pvt Ltd', '', '077 474 8130', 'info@shuqakproperties.com', '160, Justice Akbar Mawatha, Union Pl, Colombo 02000, Sri Lanka', 'https://shuqakproperties.com/', 1, 11, 4.9, 76, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:55:46', 0),
(223, 3, 'ChIJY6yqJPwLEU0RmvwMRBbJ39s', 'Emil Realtors (Pvt) Ltd', '', '077 916 2684', 'info@emilrealtors.com', '160, 05 Vijaya Kumarathunga Mawatha, Colombo 00500, Sri Lanka', 'https://emilrealtors.com/', 1, 12, 4.9, 59, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:55:57', 0),
(224, 3, 'ChIJ3URw08Vb4joRm3X3wUTnjjU', 'AKARA Apartments PVT LTD.', '', '077 374 4156', 'info@akaraapartments.lk', '20/8 Fairfield Gardens, Colombo 00800, Sri Lanka', 'http://www.akaraapartments.lk/', 1, 13, 4.4, 158, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:55:57', 0),
(225, 3, 'ChIJ68Fh8Elb4joRwBUEFiDTrts', 'Exclusive Realtors', '', '077 639 6655', 'interested@domainmarket.com', '18/187 Muhandiram E D Dabare Mawatha, Evergreen Park Rd, Colombo 00500, Sri Lanka', 'https://exclusiverealtors.com/', 1, 14, 4.9, 7, NULL, 15, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:56:10', 1),
(226, 3, 'ChIJ8Rl3PzxZ4joR9LGxxCIH6ms', 'Mohan Morais Real Estate (Pvt) Ltd', '', '077 225 1057', 'mohan@mmre.lk', '5 Charles Pl, Colombo 00300, Sri Lanka', 'https://www.mmre.lk/', 1, 15, 5.0, 6, NULL, 15, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:56:10', 0),
(227, 3, 'ChIJMxF7fLtb4joRY-_K_mSvd8A', 'AARCO Real Estate Agents Sri Lanka', '', '077 558 4476', 'info@aarcorealestate.lk', 'Real Estate Agents Hub, 12A Ridgeway Pl, කොළඹ 00400, Sri Lanka', 'http://www.aarcorealestate.lk/', 1, 16, 3.4, 25, NULL, 11, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:56:13', 0),
(228, 3, 'ChIJf8qoUUNb4joRz2BEvlt4Y1w', 'Realtors Lanka', '', '', '', 'Colombo 3, Colombo 00300, Sri Lanka', '', 0, 17, 4.7, 15, NULL, 66, 'all', 'Colombo, Sri Lanka', 'Real estate agency', 0, NULL, '2026-04-30 08:56:21', 0),
(229, 3, 'ChIJ0SMpAG1b4joRdb5Hw5BqCIk', 'Prime Medicare Colombo', '', '0114 242 030', 'support@primemedicareltd.com', '19 St Alban\'s Pl, Colombo 00400, Sri Lanka', 'https://primemedicare.lk/', 1, 2, 5.0, 12, NULL, 21, 'all', 'Colombo, Sri Lanka', 'Private clinic', 0, NULL, '2026-04-30 08:57:52', 0),
(230, 3, 'ChIJ88xPDgdZ4joR2jidVO8aFsA', 'Vida Clinic & Ambulance Service', '', '0112 576 576', 'info@vida.lk', '23 Police Park Ave, Colombo 00500, Sri Lanka', 'http://www.vida.lk/', 1, 3, 5.0, 216, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Private clinic', 0, NULL, '2026-04-30 08:58:04', 0),
(231, 3, 'ChIJxY8NJg1Z4joRvg8JRRD7LXI', 'Asiri Central Hospital - Central Hospital Limited', '', '0114 665 500', '', '114 Norris Canal Rd, Colombo 01000, Sri Lanka', 'http://www.asirihealth.com/', 1, 4, 3.7, 1443, NULL, 25, 'all', 'Colombo, Sri Lanka', 'Private clinic', 0, NULL, '2026-04-30 08:58:12', 0),
(232, 3, 'ChIJuRjblGBZ4joRkZJSlMrl_7A', 'Durdans Hospital', '', '0112 140 000', '', '3 Alfred Pl, Colombo 00300, Sri Lanka', 'http://www.durdans.com/', 1, 5, 4.2, 4160, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Private clinic', 0, NULL, '2026-04-30 08:58:20', 0),
(233, 3, 'ChIJY9jvBwFZ4joRT_378-tKv1Y', 'Viv Health private limited', '', '076 097 4720', '', '82 Barnes Pl, Colombo 00700, Sri Lanka', 'https://rnhealth.lovable.app/', 1, 6, 4.8, 8, NULL, 15, 'all', 'Colombo, Sri Lanka', 'Private clinic', 0, NULL, '2026-04-30 08:58:21', 0),
(234, 3, 'ChIJ0SMpAG1b4joRdb5Hw5BqCIk', 'Prime Medicare Colombo', '', '0114 242 030', 'support@primemedicareltd.com', '19 St Alban\'s Pl, Colombo 00400, Sri Lanka', 'https://primemedicare.lk/', 1, 2, 5.0, 12, NULL, 21, 'all', 'Colombo, Sri Lanka', 'Private clinic', 0, NULL, '2026-04-30 08:58:31', 0),
(235, 3, 'ChIJ88xPDgdZ4joR2jidVO8aFsA', 'Vida Clinic & Ambulance Service', '', '0112 576 576', 'info@vida.lk', '23 Police Park Ave, Colombo 00500, Sri Lanka', 'http://www.vida.lk/', 1, 3, 5.0, 216, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Private clinic', 0, NULL, '2026-04-30 08:58:40', 0),
(236, 3, 'ChIJxY8NJg1Z4joRvg8JRRD7LXI', 'Asiri Central Hospital - Central Hospital Limited', '', '0114 665 500', '', '114 Norris Canal Rd, Colombo 01000, Sri Lanka', 'http://www.asirihealth.com/', 1, 4, 3.7, 1443, NULL, 25, 'all', 'Colombo, Sri Lanka', 'Private clinic', 0, NULL, '2026-04-30 08:58:48', 0),
(237, 3, 'ChIJuRjblGBZ4joRkZJSlMrl_7A', 'Durdans Hospital', '', '0112 140 000', '', '3 Alfred Pl, Colombo 00300, Sri Lanka', 'http://www.durdans.com/', 1, 5, 4.2, 4160, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Private clinic', 0, NULL, '2026-04-30 08:58:55', 0),
(238, 3, 'ChIJY9jvBwFZ4joRT_378-tKv1Y', 'Viv Health private limited', '', '076 097 4720', '', '82 Barnes Pl, Colombo 00700, Sri Lanka', 'https://rnhealth.lovable.app/', 1, 6, 4.8, 8, NULL, 15, 'all', 'Colombo, Sri Lanka', 'Private clinic', 0, NULL, '2026-04-30 08:58:57', 0),
(239, 3, 'ChIJH6tPCw1Z4joRL0tsamQwS4M', 'Platinum Logistics Colombo Pvt Ltd', '', '0112 302 220', '', 'No 217 1, 5 Dr NM Perera Mawatha Rd, Colombo 00800, Sri Lanka', 'http://www.platinumlogisticscmb.com/', 1, 2, 4.9, 148, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 08:59:06', 0),
(240, 3, 'ChIJPcNcQwNZ4joRoqabXA3V8yA', 'MSK Logistics (Pvt) Ltd', '', '44875673', 'uksales@msklogistics.com', '78 Lotus Rd, Colombo 00100, Sri Lanka', 'https://www.msklogistics.com/', 1, 3, 5.0, 0, NULL, 15, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 08:59:21', 1),
(241, 3, 'ChIJbwhcl21Z4joRd67SGyd5eTQ', 'Eagle Logistics Colombo (Pvt) Ltd', '', '0112 577 892', 'info@eaglelogisticscmb.com', 'No. 281-1, 1 R. A. De Mel Mawatha, Colombo 00300, Sri Lanka', 'http://www.eaglelogisticscmb.com/', 1, 4, 4.3, 70, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 08:59:22', 0),
(242, 3, 'ChIJmXfkqG1b4joRpFoyH2g61lk', 'Colombo Logistics World (Pvt) Ltd', '', '0112 662 050', 'info@cmblog.lk', 'No. 63/1 Ward Pl, Colombo 00700, Sri Lanka', 'https://www.colombologistics.com/', 1, 5, 4.5, 38, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 08:59:27', 0),
(243, 3, 'ChIJEZH6BUJZ4joRj48dke0FNCo', 'Lanka Shipping & Logistics (Pvt) Ltd', '', '0114 681 700', 'info@lankaship.lk', 'Lanka Shipping Tower, 40 Hudson Rd, Colombo 00300, Sri Lanka', 'http://www.lankaship.lk/', 1, 6, 4.1, 57, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 08:59:29', 0),
(244, 3, 'ChIJvR-GJ3JZ4joRlZn7LN5atqM', 'D. L. & F. De Saram Law Firm', '', '0112 015 200', 'info@desaram.com', 'No. 47, C.W.W. Kannangara Mawatha, Alexandra Pl, Colombo 00700, Sri Lanka', 'https://www.desaram.com/?utm_source=google&utm_medium=organic&utm_campaign=my_business', 1, 2, 4.5, 133, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Law firm', 0, NULL, '2026-04-30 08:59:41', 0),
(245, 3, 'ChIJ-6-ktwxZ4joRQyZEU1iYOKU', 'F J & G de Saram', '', '0114 718 200', '', '216 De Saram Pl, Colombo 01000, Sri Lanka', 'https://www.fjgdesaram.com/', 1, 3, 4.2, 95, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Law firm', 0, NULL, '2026-04-30 08:59:41', 0),
(246, 3, 'ChIJS4pKADVZ4joRPGePaQQK-Q0', 'CB LAW CHAMBERS', '', '077 227 0261', 'cblawchambers1@gmail.com', '30, 24A Longdon Pl, Colombo 00700, Sri Lanka', 'https://cblawchambers.com/', 1, 4, 5.0, 57, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Law firm', 0, NULL, '2026-04-30 08:59:44', 0),
(247, 3, 'ChIJvT8ImWdZ4joRXCavipy1WfI', 'Neelakandan & Neelakandan (formerly Murugesu & Neelakandan)', '', '0112 371 100', 'mail@neelakandan.lk', 'Kandiah Neelakandan Building, Level 5, 2 Deal Pl, Colombo 00300, Sri Lanka', 'http://www.neelakandan.lk/', 1, 5, 4.6, 25, NULL, 21, 'all', 'Colombo, Sri Lanka', 'Law firm', 0, NULL, '2026-04-30 08:59:45', 0),
(248, 3, 'ChIJvzBInmlZ4joRwDRfZvWL4DY', 'Chambers Colombo', '', '0112 434 493', 'lawyers@chamberscolombo.com', '65C Citi Bank Building, Srimath Anagarika Dharmapala Mawatha, Colombo 00700, Sri Lanka', 'http://www.chamberscolombo.com/', 1, 6, 4.2, 57, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Law firm', 0, NULL, '2026-04-30 08:59:48', 0),
(249, 3, 'ChIJbdDtEw1p4zoRwXHvlBGsgEI', 'Lawyer\'s Office Complex, Kandy', '', '0812 388 835', '', '7JC5+QRC, Kandy, Sri Lanka', '', 0, 2, 4.1, 92, NULL, 77, 'all', 'Kandy, Sri Lanka', 'Law firm', 0, NULL, '2026-04-30 09:00:01', 0),
(250, 3, 'ChIJFSEhdwBn4zoR-fbTdRr8t1E', 'Dilanji Athapaththu Attorney-at-Law', '', '077 879 6820', '', '7JPM+HQ3, Kandy, Sri Lanka', '', 0, 3, 5.0, 14, NULL, 71, 'all', 'Kandy, Sri Lanka', 'Law firm', 0, NULL, '2026-04-30 09:00:03', 0),
(251, 3, 'ChIJEUWj7-Rp4zoR_NUs9qokjE4', 'Shashikala Weerasekar - Attorney-at-Law and Nottary Public Kandy', '', '070 511 2959', '', 'No. 6/3/2 Rathnapala Road, Katugastota 20140, Sri Lanka', '', 0, 4, 5.0, 13, NULL, 71, 'all', 'Kandy, Sri Lanka', 'Law firm', 0, NULL, '2026-04-30 09:00:05', 0),
(252, 3, 'ChIJtUIKN2lp4zoR287WjOigeyY', 'Lawyer Anuththara Vitharana', '', '077 406 5799', '', '751 William Gopallawa Mawatha, Kandy 20000, Sri Lanka', '', 0, 5, 5.0, 13, NULL, 71, 'all', 'Kandy, Sri Lanka', 'Law firm', 0, NULL, '2026-04-30 09:00:06', 0),
(253, 3, 'ChIJl2kJLS5m4zoRxKt-_P-ZfwQ', 'Viyana Boutique Hotel', '', '0812 220 070', '', '56 Sangaraja Mawatha, Kandy 20000, Sri Lanka', 'http://www.viyanaboutique.com/', 1, 2, 4.3, 374, NULL, 35, 'all', 'Kandy, Sri Lanka', 'Boutique hotel', 0, NULL, '2026-04-30 09:00:41', 0),
(254, 3, 'ChIJJzKrlTpm4zoR66nRzDeUHJ4', 'Hotel Yo', '', '', '', '1, 5 Mahamaya Mawatha, Kandy 20000, Sri Lanka', '', 0, 3, 4.3, 221, NULL, 80, 'all', 'Kandy, Sri Lanka', 'Boutique hotel', 0, NULL, '2026-04-30 09:00:54', 0),
(255, 3, 'ChIJk6FZoGJn4zoRBG55d5zaRoM', 'Theva Residency', '', '0817 388 296', '', '11/B5/10-1, 6th lane, off upper tank road 2,Hanthana, Sri Lanka', 'https://www.theva.lk/?utm_source=google&utm_medium=organic&utm_campaign=GBP', 1, 4, 4.7, 371, NULL, 35, 'all', 'Kandy, Sri Lanka', 'Boutique hotel', 0, NULL, '2026-04-30 09:00:55', 0),
(256, 3, 'ChIJtaEuT4Zo4zoRyTIOrSYzFPE', 'Kings Pavilion Kandy', '', '0812 236 400', 'info@kingspavilion.com', '4/22 Galkanda Road, Aniwatta, Kandy 20000, Sri Lanka', 'http://www.kingspavilion.com/?utm_source=google&utm_medium=organic&utm_campaign=gbp', 1, 5, 4.7, 252, NULL, 35, 'all', 'Kandy, Sri Lanka', 'Boutique hotel', 0, NULL, '2026-04-30 09:00:56', 0),
(257, 3, 'ChIJ7alLiQlo4zoRRcRZV_0Fv78', 'Clove Villa', '', '0812 212 999', '', '48 P B A Weerakoon Mawatha, Kandy 20000, Sri Lanka', 'http://www.clovevilla.com/', 1, 6, 4.6, 102, NULL, 35, 'all', 'Kandy, Sri Lanka', 'Boutique hotel', 0, NULL, '2026-04-30 09:00:58', 0),
(258, 3, 'ChIJvVR2dHln4zoRxnQBOMzyJ1A', 'Kandy Fortress', '', '077 038 6426', '', '103, 46 Dharmaraja Mawatha, Kandy, Sri Lanka', '', 0, 2, 4.8, 77, NULL, 77, 'all', 'Kandy, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 09:01:20', 0),
(259, 3, 'ChIJibn3gNln4zoR_Vnl5JCbqeA', 'Lake classical guest house', '', '0812 232 315', '', '12 Sangaraja Mawatha, Kandy 20000, Sri Lanka', 'http://booking.com/', 0, 3, 4.8, 93, NULL, 77, 'all', 'Kandy, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 09:01:26', 0),
(260, 3, 'ChIJ2X3FOQBp4zoRGH18GDGiaPo', 'PPIM - Kandy Campus Sri Lanka - ACCA Platinum Tuition Provider & AAT Tuition Centre Sri Lanka | ACCA & AAT Classes & Courses', '', '077 739 5839', 'hello@ppim.edu.lk', '643 Peradeniya Rd, Kandy 20000, Sri Lanka', 'https://www.ppim.edu.lk/', 1, 2, 5.0, 56, NULL, 27, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:01:40', 0),
(261, 3, 'ChIJB-i5HgFp4zoRR7pUXTNKXFk', 'Ciencia', '', '077 352 4174', 'info@ciencia.com', '911/2 Peradeniya Rd, Kandy 20000, Sri Lanka', 'https://ciencia.com/', 1, 3, 5.0, 8, NULL, 15, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:01:42', 1),
(262, 3, 'ChIJmbpQvWpp4zoROSz2uxE0atg', 'No. Zero Knowledge Centre', '', '0812 222 601', '', 'No. 1/3 Bahirawakanda Patu Mawatha, Kandy 20000, Sri Lanka', 'https://nozero.lk/', 1, 4, 4.3, 231, NULL, 35, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:01:45', 0),
(263, 3, 'ChIJOzJKSNFp4zoRmznkSvLQs2I', 'Astro Edu Co.', '', '074 078 7873', '', '504/A/1/1 Peradeniya Rd, Kandy 20000, Sri Lanka', '', 0, 5, 5.0, 5, NULL, 65, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:01:46', 0),
(264, 3, 'ChIJZbfV4Nlp4zoRw4fIPdz4y60', 'A-Star Education', '', '077 799 7527', '', '17A George E De Silva Mawatha, Kandy 20000, Sri Lanka', '', 0, 6, 4.6, 70, NULL, 77, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:01:51', 0),
(265, 3, 'ChIJncpMGYJo4zoRmhEDEhWFgLk', 'Yowun Educational Institute', '', '0812 226 960', '', '93 Peradeniya Rd, Kandy 20000, Sri Lanka', '', 0, 7, 4.8, 12, NULL, 71, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:01:53', 0),
(266, 3, 'ChIJTRVwljln4zoRZeWsKz3TTPg', 'NexGen Education', '', '077 797 3835', '', '337, 1 Katugastota Rd, Kandy 20000, Sri Lanka', 'https://nexgeneducation.com/', 1, 8, 4.9, 45, NULL, 27, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:01:55', 1),
(267, 3, 'ChIJLaQ_CdFp4zoRh7Jm3W00zOE', 'Grman Exam Kandy Branch | ÖSD Exam Center in Sri Lanka', '', '077 329 8779', '', '286 A Katugastota Rd, Kandy 20800, Sri Lanka', 'https://www.grmanexams.lk/', 1, 9, 4.9, 38, NULL, 27, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:01:56', 0),
(268, 3, 'ChIJCfs1RINo4zoR0S6jUQOYH6s', 'Kandy language center', '', '077 348 0440', 'applocations@klcedu.lk', '321 1/1 Peradeniya Rd, Kandy 20000, Sri Lanka', 'https://klcedu.lk/', 1, 10, 4.5, 79, NULL, 27, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:02:04', 0),
(269, 3, 'ChIJRyE9A4Zn4zoRMcAocI-EPXc', 'A+ Tutors Lanka', '', '076 059 0259', 'aplustutorslanka@gmail.com', '2GL, Kandy 20000, Sri Lanka', 'https://prorath.wixsite.com/aplustutor', 1, 11, 5.0, 1, NULL, 15, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:02:07', 0),
(270, 3, 'ChIJs3uCKhBn4zoRW_qPvh3B3oI', 'URANUS ACADEMY', '', '070 461 8618', '', 'No.241, 241 A9, Kandy 20000, Sri Lanka', '', 0, 12, 5.0, 5, NULL, 65, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:02:09', 0),
(271, 3, 'ChIJAQOdupRp4zoRh45wYtLSyk0', 'GT Education', '', '075 236 7342', '', '239 Peradeniya Rd, Kandy 20000, Sri Lanka', 'https://gtedugroup.com/', 1, 13, 5.0, 8, NULL, 15, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:02:10', 0),
(272, 3, 'ChIJP2aM_IFp4zoR4eUfj9ZZPuQ', 'DRESDEN ACADEMY- FOR LANGUAGES/SCIENCE', '', '077 277 5564', '', 'near Kandy Convent/before Ceypetco Shed, 120 C Peradeniya Rd, Kandy 20000, Sri Lanka', '', 0, 14, 4.5, 17, NULL, 71, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:02:12', 0),
(273, 3, 'ChIJMVVN_Gxn4zoR44UFXrGhmEk', 'Farade Education Kandy / Farade Institute Kandy (Farade Educational Services (Pvt) Ltd)', '', '071 090 2750', 'guest@domain.com', '242 DS Senanayake Veediya, Kandy 20000, Sri Lanka', 'http://www.faradeeducation.com/', 1, 15, 4.0, 27, NULL, 21, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:02:19', 0),
(274, 3, 'ChIJsfTUKipm4zoRTE0vEhoPbHU', 'American Corner, Kandy', '', '0812 203 944', '', 'D. S. Senanayake Memorial Public Library, Kandy 20000, Sri Lanka', '', 0, 16, 4.7, 70, NULL, 77, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:02:20', 0),
(275, 3, 'ChIJbbN9X75p4zoRugJMMGr9eCE', 'Knowladge Win Higher Education Institute', '', '076 168 5030', '', 'Peradeniya Rd, Kandy 20000, Sri Lanka', '', 0, 17, 5.0, 1, NULL, 65, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:02:22', 0),
(276, 3, 'ChIJA-GiSklp4zoRtk_ctDwk1AY', 'The Hope Centre for Child Development', '', '', '', '933/4 Peradeniya Rd, Kandy 20000, Sri Lanka', 'https://www.facebook.com/groups/611175812649385/?ref=share', 0, 18, 4.7, 38, NULL, 72, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:02:24', 0),
(277, 3, 'ChIJg809apto4zoRn26lPb-X5T0', 'IMS - Higher Education Center', '', '077 780 6246', '', 'Kandy - Colombo Rd, Kandy, Sri Lanka', 'http://imsedu.lk/', 1, 19, 4.2, 140, NULL, 35, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:02:26', 0),
(278, 3, 'ChIJTT0-pR5p4zoR0v2VCn8BISM', 'IWIN Education Center', '', '070 370 7707', '', '1st floor, 239a Peradeniya Rd, Kandy 20000, Sri Lanka', 'https://iwin.edu.lk/', 1, 20, 5.0, 2, NULL, 15, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:02:27', 0),
(279, 3, 'ChIJf1FBbwto4zoRtN7QhwViP6o', 'FIRST FRIENDS CAMPUS', '', '077 723 0033', 'info@firstfriends.lk', '21 Srimath Kudarathwatta Mawatha, Kandy, Sri Lanka', 'http://firstfriends.lk/', 1, 21, NULL, 147, NULL, 25, 'all', 'Kandy, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:02:30', 0),
(280, 3, 'ChIJXytmvqZp4zoRM9IcwIcJ030', 'Nirwaan Ayurvedic Spa Kandy', '', '077 789 2486', 'nirwaanretreat@gmail.com', 'Shrimath Kuda, rathwaththa mawatha, Kandy 20000, Sri Lanka', 'http://nirwaan.com/', 1, 2, 4.8, 222, NULL, 35, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:02:47', 0),
(281, 3, 'ChIJvyiFvOJo4zoRXIQCyTk6sUw', 'Green Chaya Spa Ayurvedic Treatment Center (අයුර්වේද ප්‍රථිකාර පමණි)', '', '077 116 5404', 'info@greenchayaspa.com', '2 / A, 2 Hantana Housing Scheme Road, Kandy 20000, Sri Lanka', 'https://greenchayaspa.com/', 1, 3, 4.4, 686, NULL, 35, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:02:52', 0),
(282, 3, 'ChIJqw0wo2pn4zoRnrmdt3q_mRU', 'LAAMA GRAND WELLNESS KANDY', '', '070 532 1311', 'info@laamawellness.com', 'Infront of Toothrelic Temple, 5 Temple St, Kandy 20000, Sri Lanka', 'https://laamawellness.com/', 1, 4, 4.8, 485, NULL, 35, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:02:56', 0),
(283, 3, 'ChIJz81OJgBp4zoRMMShnchT854', 'Ayurveda Wedagedara Pvt Ltd', '', '0812 226 790', '', '76B Deveni Rajasinghe Mawatha, Kandy 20000, Sri Lanka', '', 0, 5, 3.3, 154, NULL, 75, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:02:58', 0),
(284, 3, 'ChIJifN5NN5p4zoRcxX3aUZZTEQ', 'Kandy Relaxation Massage Spa', '', '078 817 7708', 'info@ceylonayurvedicrelaxation.com', '35 Deveni Rajasinghe Mawatha, Kandy 20005, Sri Lanka', 'https://ceylonayurvedicrelaxation.com/', 1, 6, 4.6, 9, NULL, 15, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:00', 0),
(285, 3, 'ChIJP_1BYcxp4zoRV4xGfdHMMaE', 'Suwa Asapuwa Spa- Foreign bookings only', '', '075 086 9869', 'info@suwaasapuwa.com', 'Saranankara Mawatha, Kandy 20000, Sri Lanka', 'http://www.suwaasapuwa.com/', 1, 7, 4.1, 135, NULL, 35, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:03', 0),
(286, 3, 'ChIJ3x0UFHto4zoRmH1Maut91gI', 'Senkadagala Suwa Arana Ayurvedic Spa', '', '076 841 5305', '', '142 Srimath Kudarathwatta Mawatha, Kandy 20000, Sri Lanka', '', 0, 8, 3.2, 103, NULL, 75, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:05', 0),
(287, 3, 'ChIJmW9RfUNp4zoRWmau2i3tx-A', 'Suwaya Treatment Center', '', '071 642 0558', '', '68 14/3 Polwaththa Rd, Katugastota 20800, Sri Lanka', 'https://business.facebook.com/Suwaya-Treatment-Center-104367458383939/?ref=page_internal', 0, 9, 4.9, 146, NULL, 85, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:07', 0),
(288, 3, 'ChIJDyvzQQBn4zoRkUwObf1PFY0', 'Mango Ayurveda Center', '', '0812 235 135', '', '32/1/1 Saranankara Rd, Kandy 20000, Sri Lanka', 'http://kandylakesideayurvedaspa.com/', 1, 10, 4.7, 14, NULL, 21, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:17', 0),
(289, 3, 'ChIJqaJHMQBp4zoRtTTnFC0GLwo', 'Earthbound Ayurvedic Treatment & Spa', '', '072 818 0900', '', '27, 98 Sri Amarawansa Mawatha, Kandy, Sri Lanka', '', 0, 11, 5.0, 28, NULL, 71, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:18', 0),
(290, 3, 'ChIJj7GN2ABn4zoRuEwV4q8--3o', 'Ayurvedic Spa By Sethroo', '', '078 800 8100', '', '306, D S Senanayaka vidiya, Kandy 20000, Sri Lanka', '', 0, 12, 4.1, 35, NULL, 77, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:20', 0),
(291, 3, 'ChIJ0RToewBp4zoREkF1E0rkESE', 'WEDA ARANA KANDY', '', '076 841 5305', '', '160 Srimath Kudarathwatta Mawatha, Kandy 20000, Sri Lanka', 'https://www.facebook.com/share/17eCLk1Lv6/?mibextid=wwXIfr', 0, 13, 4.6, 65, NULL, 77, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:23', 0),
(292, 3, 'ChIJq6pq4Ctm4zoRhFM4qWGmtv8', 'Spa Ceylon Kandy City Center Boutique', '', '0812 121 828', '', '2nd Level, Kandy City Centre, 5 Sri Dalada Veediya, Kandy 20000, Sri Lanka', 'http://www.spaceylon.com/', 1, 14, 4.3, 151, NULL, 35, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:23', 0),
(293, 3, 'ChIJ5V0luYtn4zoRLDQAEAzCjEc', 'MASSAGE THERAPIST AYUR', '', '076 459 0589', '', 'Kandy 20000, Sri Lanka', 'https://wa.me/94752685177?text=Hello%20I%20need%20a%20massage%20appointment', 1, 15, 5.0, 7, NULL, 15, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:27', 0),
(294, 3, 'ChIJ2Xbs99dp4zoRxwGzigL6cmM', 'Spa Ceylon Peradeniya Boutique', '', '0812 200 768', 'online@spaceylon.com', '673 Peradeniya Rd, Kandy 20000, Sri Lanka', 'https://lk.spaceylon.com/', 1, 16, 4.3, 57, NULL, 27, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:28', 0),
(295, 3, 'ChIJEy9FITxn4zoRztHweWAjwlY', 'Sanhida Spa Kandy', '', '072 907 0775', '', 'No. 103, 1/1 Hewaheta Rd, Kandy 20000, Sri Lanka', '', 0, 17, 4.0, 12, NULL, 71, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:29', 0),
(296, 3, 'ChIJ387o6i9m4zoRZiAheaL_mnY', 'Olive Ayurvedic Center kandy....', '', '0813 834 555', '', '43 Saranankara Rd, Kandy 20000, Sri Lanka', '', 0, 18, 3.0, 5, NULL, 55, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:30', 0),
(297, 3, 'ChIJ2eL-MbFn4zoRgb5_I7jdS5g', 'acupuncture/ayurveda/homeopathy/massage clinic.hcc kandy', '', '071 442 6084', '', 'No:39 Vidyartha Mawatha, Kandy 20000, Sri Lanka', 'http://www.hcckandy.com/', 1, 19, 4.9, 42, NULL, 27, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:30', 0),
(298, 3, 'ChIJu7nJxQ1n4zoRwT8UK1xMMMg', 'Kandy Herbal Centre (Pvt) Ltd.', '', '0812 200 800', '', 'Room no.14,Hotel suisse, Swiss, 30 Sangaraja Mawatha, Kandy 20000, Sri Lanka', 'https://www.facebook.com/kandyherbalcentre/?modal=admin_todo_tour', 0, 20, 3.5, 11, NULL, 61, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:33', 0),
(299, 3, 'ChIJuxkOtHtn4zoRW-tR65u2uaU', 'Laama Wellness Academy', '', '070 542 1311', 'info@laamawellness.com', '4th floor, LAAMA GRAND WELLNESS KANDY, 05 Temple St, Kandy 20000, Sri Lanka', 'https://laamawellness.com/academy/', 1, 21, 5.0, 15, NULL, 21, 'all', 'Kandy, Sri Lanka', 'ayurveda spa', 0, NULL, '2026-04-30 09:03:37', 0),
(300, 3, 'ChIJ69wncJpo4zoRiB1JaYyThgM', 'Gagana Lanka (Pvt) Ltd', '', '0812 222 026', 'info@gaganalankatravels.com', '307 Peradeniya Rd, Kandy 20000, Sri Lanka', 'http://www.gaganalanka.lk/', 1, 2, 5.0, 51, NULL, 27, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:03:48', 0),
(301, 3, 'ChIJpd-AZ8dn4zoR50HbCMFHbIY', 'Vacation Sri Lanka By Marcus Vacations (PVT) LTD - Local Tour Operator - Travel Agent and Taxi Services Company in Sri Lanka', '', '075 642 4224', 'info@vacationsrilanka.com', 'No 338,Udagamaa,Ampitiya, Kandy 20000, Sri Lanka', 'http://www.vacationsrilanka.com/', 1, 3, 4.8, 209, NULL, 35, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:03:55', 0),
(302, 3, 'ChIJC6d2_RNn4zoRemRGUvdZd8s', 'Lanka Ceylon Tours', '', '071 382 6635', 'info@lankaceylontours.com', 'Sangaraja Mawatha, Kandy 20000, Sri Lanka', 'https://www.lankaceylontours.com/', 1, 4, 5.0, 74, NULL, 27, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:04:02', 0),
(303, 3, 'ChIJi0-gx-tp4zoRSzJj_u1xyJc', 'Shan Travel Guide', '', '077 653 4993', 'shantravelguide@gmail.com', '11 Yatinuwara St, Kandy 20000, Sri Lanka', 'http://www.shantravelguide.com/', 1, 5, 4.9, 265, NULL, 35, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:04:05', 0),
(304, 3, 'ChIJwbLcAxJo4zoREDM9ILwEtJo', 'LavinGo Travels', '', '077 229 4697', 'info@lavingotravels.com', 'No 07, 2nd Floor, DS Senanayake Veediya, Kandy 20000, Sri Lanka', 'https://www.lavingotravels.com/', 1, 6, 4.8, 76, NULL, 27, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:04:08', 0),
(305, 3, 'ChIJB0tUaNZn4zoRs3d8VSUJMCo', 'Sri Lanka Tours by Aaliya Tours ( Travel Sri Lanka)', '', '072 420 2115', '%20info@aaliyatours.com', '41 Katugastota Rd, Kandy 20000, Sri Lanka', 'http://www.aaliyatours.com/', 1, 7, 4.9, 115, NULL, 35, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:04:12', 0),
(306, 3, 'ChIJRcJA16Np4zoRopjAkv-_eyE', 'Tour Operators Sri Lanka - Breath Sri Lanka Tours', '', '071 635 2769', 'hello@breathsrilankatours.com', '69, 12A Ampitiya Rd, Kandy 20000, Sri Lanka', 'http://www.breathsrilankatours.com/', 1, 8, 5.0, 240, NULL, 35, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:04:17', 0),
(307, 3, 'ChIJe_Edw7xn4zoR2r6_25HgGsM', 'AARA Travel & Tours Pvt ltd', '', '077 330 6535', 'info@aaratravel.com', '28 E L Senanayake Veediya, Kandy 20000, Sri Lanka', 'https://aaratravel.com/', 1, 9, 4.8, 20, NULL, 21, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:04:19', 0),
(308, 3, 'ChIJ99E3Jbto4zoRxLbaxuX5CUI', 'Sri Lanka Viajes Eden', '', '', 'info@srilankaviajeseden.es', 'Stone House Suites, No 38 Nittawela Rd, Kandy 20000, Sri Lanka', 'https://srilankaviajeseden.es/', 1, 10, 5.0, 411, NULL, 30, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:04:21', 0),
(309, 3, 'ChIJ7weULNtn4zoRV0idSGhyR1Y', 'THE KANDY TRAVELS & LEISURE (PVT) LTD', '', '0812 221 177', '', '70 DS Senanayake Veediya, Kandy 20000, Sri Lanka', 'https://thekandytravels.com/', 1, 11, 4.5, 35, NULL, 27, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:04:27', 0),
(310, 3, 'ChIJ27pBHXlo4zoRvDxiPi8o3tk', 'Blue Haven Tours & Travels (Pvt) Ltd', '', '077 781 1880', 'bluehavtravels@gmail.com', '30/2 Poorna Ln, Kandy 20000, Sri Lanka', 'http://www.bluehaventours.com/', 1, 12, 4.7, 48, NULL, 27, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:04:31', 0),
(311, 3, 'ChIJC5_Vb0dn4zoRS2QodfncakA', 'Sunsmart Travels (Pvt) Ltd', '', '077 277 3777', 'info@sunsmarttravels.com', '25 DS Senanayake Veediya, Kandy 20000, Sri Lanka', 'http://sunsmarttravels.com/', 1, 13, 4.8, 17, NULL, 21, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:04:32', 0),
(312, 3, 'ChIJ4SGuU_Fn4zoRY5W6U3h86A4', 'Fly A World - Consultants and Tourism (PVT) LTD', '', '077 681 0201', 'info@flyworld.co', '27/2-1, HSBC Building, 2nd Floor, Cross St, Kandy 20000, Sri Lanka', 'https://flyaworld.co/', 1, 14, 4.9, 37, NULL, 27, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:04:35', 0),
(313, 3, 'ChIJ7Y7sRhln4zoRE87tyKffnCM', 'The Traveller Global Private Limited - Kandy Branch', '', '076 640 0008', '', 'No 22 Kotugodella Street, Kandy 20000, Sri Lanka', 'https://travellerglobal.com/', 1, 15, 4.8, 31, NULL, 27, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:04:37', 0),
(314, 3, 'ChIJGQmG5kBn4zoRJVBhkSDVdnI', 'BOC Travels - Kandy', '', '0812 205 131', 'info@boctravels.com', '17 Temple St, Kandy, Sri Lanka', 'https://www.boctravels.com/', 1, 16, 4.7, 9, NULL, 15, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:04:40', 0),
(315, 3, 'ChIJJ2IjjCJp4zoRonJtKT-6mqI', 'Thara Lanka Tours', '', '077 956 6195', '', '67, 42 Nagasthenna Rd, Kandy, Sri Lanka', 'https://tharalankatours.com/', 1, 17, 4.9, 90, NULL, 27, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:04:55', 1),
(316, 3, 'ChIJie5M1-Np4zoRyIMJo6LNGHw', 'Tours by Harsha - Travel in Sri Lanka. NELUM LANKA TOURS', '', '077 227 5457', '', '17/2B Devana, Deveni Rajasinghe Mawatha, Kandy 20000, Sri Lanka', 'https://www.facebook.com/people/UpCountry-Tours-Sri-Lanka/100005679676504/', 0, 18, 5.0, 36, NULL, 77, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:04:57', 0),
(317, 3, 'ChIJa1zZbfZn4zoRiygX0PZfzaM', 'Aruna Tours Sri Lanka', '', '077 584 3827', 'abkeerthi76@gmail.com', '222/4 A9, Kandy 20000, Sri Lanka', 'https://arunatourssl.com/', 1, 19, 5.0, 76, NULL, 27, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:05:05', 0),
(318, 3, 'ChIJN9BNZitm4zoRqE8rbM0BowU', 'Travel Star International (Pvt) Ltd', '', '071 475 6226', 'info@travelstar.lk', '68 Raja Veediya, Kandy 20000, Sri Lanka', 'http://www.travelstar.lk/', 1, 20, 4.6, 19, NULL, 21, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:05:08', 0),
(319, 3, 'ChIJKbUsOjhp4zoRmoZtnbaaZsI', 'Travel Sri Lanka Vibes', '', '078 639 9238', '', '20,Sri Dharmapala, Lane, Kandy 20000, Sri Lanka', 'https://travelsrilankavibes.com/', 1, 21, 5.0, 24, NULL, 21, 'all', 'Kandy, Sri Lanka', 'travel agent', 0, NULL, '2026-04-30 09:05:17', 0),
(320, 3, 'ChIJ5ZRLl1zN-joRzpUVbtcL15E', 'Learn & Study Tuition Center', '', '077 615 2535', '', 'PPC2+H6C, Batticaloa, Sri Lanka', '', 0, 2, 5.0, 7, NULL, 65, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:06:18', 0),
(321, 3, 'ChIJI_rLhDFTzwwRLlquUjcccms', 'SPM Academy Batticaloa Branch', '', '074 016 3188', 'academy@spm.indust', '26/4A Boundary Rd S, Batticaloa 30000, Sri Lanka', 'https://academy.spm.industries/', 1, 3, 5.0, 17, NULL, 21, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:06:21', 0),
(322, 3, 'ChIJ2VV1vGXN-joRWrzQ8bfxS8Q', 'Study and Learning Centre. (Church of Ceylon, Eastern Deanery - Dioceses of Colombo)', '', '', '', 'PPM2+85G, Sakarias Rd, Batticaloa, Sri Lanka', '', 0, 4, 5.0, 1, NULL, 60, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:06:22', 0),
(323, 3, 'ChIJY_HU6F7N-joR_1mINYxql2c', 'GAMA ABACUS BATTICALOA', '', '077 579 9033', 'info@ynotinfo.com', 'Room No: 5, No 10 MPCS, Railway Station Road, batticaloa 03000, Sri Lanka', 'http://www.gamaabacus.com/', 1, 5, NULL, 0, NULL, 5, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:06:25', 0),
(324, 3, 'ChIJTehPrpfN-joRxk6tQKmb-yY', 'HBS College Batticaloa', '', '0652 059 199', '', 'PP92+63C, Old Rest House Road, Batticaloa, Sri Lanka', '', 0, 6, 3.8, 6, NULL, 55, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:06:27', 0),
(325, 3, 'ChIJE8N9RVvN-joRXuhjBXpqxU4', 'Headway School of Languages', '', '0654 924 042', 'contact@headway.lk', 'Batticaloa 30000, Sri Lanka', 'http://headway.lk/', 1, 7, 4.4, 47, NULL, 27, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:06:30', 0),
(326, 3, 'ChIJY0JSKQDN-joRR7yeG0UDETM', 'SBL Institute', '', '074 410 6368', 'info@sbl.instit', 'Boundary Rd, Batticaloa, Sri Lanka', 'http://www.sbl.institute/', 1, 8, 5.0, 4, NULL, 15, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:06:33', 0),
(327, 3, 'ChIJ70vWmETN-joRackAS0KpBRM', 'Task Education', '', '077 228 3355', 'info@taskeducationwork.com', 'St Sebastian St, Batticaloa, Sri Lanka', 'http://lms.taskeducationwork.com/course/index.php?categoryid=13', 1, 9, 5.0, 2, NULL, 15, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:06:38', 0),
(328, 3, 'ChIJp57iWlvN-joRo6M__JtNm4I', 'ESOFT Metro Campus Batticaloa', '', '0657 572 572', 'info@esoft.lk', '43 Baily Rd, Batticaloa, Sri Lanka', 'http://www.esoft.lk/', 1, 10, 4.4, 55, NULL, 27, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:06:40', 0),
(329, 3, 'ChIJCwjqtAzN-joRlp4y_feBtLg', 'ATN Campus Batticaloa', '', '0114 848 489', '', '65/2 Central Road, Batticaloa 30000, Sri Lanka', '', 0, 11, 4.8, 28, NULL, 71, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:06:41', 0),
(330, 3, 'ChIJr-l8QlvN-joRtmESIV7CNrY', 'INFORMATION TECHNOLOGY AND DISTANCE LEARNING HUB', '', '0652 227 707', '', 'Bar Rd, Batticaloa, Sri Lanka', '', 0, 12, 4.4, 8, NULL, 65, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:06:43', 0),
(331, 3, 'ChIJj3bdmerT-joRKppvht9y5nk', 'Batticaloa Lagoon Environmental Learning Centre', '', '0652 229 144', '', 'QM4M+4V8, Batticaloa, Sri Lanka', '', 0, 13, 3.0, 2, NULL, 55, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:06:46', 0);
INSERT INTO `lead_gen_results` (`id`, `user_id`, `place_id`, `name`, `owner_name`, `phone`, `email`, `address`, `website`, `has_website`, `api_calls`, `rating`, `ratings_total`, `price_level`, `opportunity_score`, `search_mode`, `location`, `industry`, `imported`, `lead_id`, `created_at`, `website_found_by_crawler`) VALUES
(332, 3, 'ChIJFRU0M57N-joR-dvqSFLYFqc', 'DreamSpace Academy', '', '0652 226 525', '', '175 New Kalmunai Road, Kallady 30000, Sri Lanka', 'http://www.dreamspace.academy/', 1, 14, 4.8, 21, NULL, 21, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:06:51', 0),
(333, 3, 'ChIJuxA28__T-joRFgpoec8ng1k', 'Sagewyn Academy', '', '077 714 8926', '', '601 Trincomalee Hwy, Batticaloa 30000, Sri Lanka', 'https://sagewyn-academy.com/', 1, 15, NULL, 0, NULL, 5, 'all', 'Batticaloa, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:06:51', 0),
(334, 3, 'ChIJG6q84txb4joRjOBe_wd9lMA', 'SWA London AS/AL and SAT classes (Colombo - Sri Lanka)', '', '077 730 4755', '', '7 kothalawala place, Colombo 00094, Sri Lanka', '', 0, 2, 5.0, 70, NULL, 77, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:06', 0),
(335, 3, 'ChIJC8lrWE9Z4joR8BxPqGgDShk', 'PPIM – Colombo Campus Sri Lanka - ACCA Platinum Tuition Provider & AAT Tuition Courses Sri Lanka | ACCA & AAT Classes', '', '', 'hello@ppim.edu.lk', '248 1, 1 Galle - Colombo Rd, Colombo 00400, Sri Lanka', 'https://www.ppim.edu.lk/', 1, 3, 4.9, 248, NULL, 30, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:09', 0),
(336, 3, 'ChIJ3wGTBBtb4joR_iVAcPRFyG4', 'Arul sir\'s Maths Tuition Centre (London A/L Edexcel)', '', '', '', 'VVF8+CXC, Hampden Terrace, Colombo, Sri Lanka', '', 0, 4, 5.0, 3, NULL, 60, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:11', 0),
(337, 3, 'ChIJnzltqdZZ4joRwdP92CZADdc', 'Z Tuition Centre', '', '077 299 6375', 'ztuitioncentre@gmail.com', '123, 2/1 Kumaradasa Pl, Colombo 10600, Sri Lanka', 'http://www.ztuitioncentre.wordpress.com/', 1, 5, 4.0, 1, NULL, 15, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:12', 0),
(338, 3, 'ChIJo05glPTx4joREWUO0LHBU4c', 'Tutor Educational Institute', '', '077 024 4992', 'info@tutor.lk', '47 Ananda Coomaraswamy Mawatha, Colombo 00300, Sri Lanka', 'http://www.tutor.lk/', 1, 6, 4.6, 31, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:16', 0),
(339, 3, 'ChIJMUwSWB5Z4joRyF102wkQAM0', 'Universal Institute Colombo (UIC)', '', '077 782 1746', 'support@universalinstitutecolombo.com', 'Level 35, World Trade Center, West Tower, Bank of Ceylon Mawatha, Colombo 00100, Sri Lanka', 'https://www.universalinstitutecolombo.com/%20%20https://universalinstitutecolombo.lk/', 1, 7, 5.0, 120, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:23', 0),
(340, 3, 'ChIJV8KL039Z4joRVUTc381nAEQ', 'Distance Learning Centre Ltd', '', '0112 554 946', 'info@dlcsrilanka.org', '28 Prof. G. P. Malalasekara Mawatha, Colombo 00700, Sri Lanka', 'http://www.dlcsrilanka.org', 1, 8, 4.7, 38, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:26', 0),
(341, 3, 'ChIJhcHTBM5Z4joR_s4sV82XIU4', 'Upgate Education', '', '076 185 9898', 'hello@deepskyblue-shark-464975.hostingersite.com', 'No 17 Deal Place, 3, Colombo 00300, Sri Lanka', 'http://www.upgateeducation.com/', 1, 9, 4.9, 76, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:27', 0),
(342, 3, 'ChIJdVTJ-NBb4joRpIH3RfJLGYA', 'Kumon Maths & English Classes:Best Kids Learning Centre in Achievers', '', '077 984 4804', '', 'No. 18, 16 B Chitra Ln, Colombo 00500, Sri Lanka', 'http://in.kumonglobal.com/', 1, 10, 4.5, 18, NULL, 21, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:30', 0),
(343, 3, 'ChIJhSOvY8hZ4joRYNSbYWY-Sbk', 'Smart Educational Center', '', '072 924 4326', '', '145 Veluwana road Dematagoda, Colombo 00900, Sri Lanka', 'https://smartcenter.lk/', 1, 11, 5.0, 23, NULL, 21, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:38', 0),
(344, 3, 'ChIJk5lYAPNZ4joRu_qRW5XwMRw', 'AS Learning Academy', '', '072 244 5534', '', '97/A Mohideen Masjid Rd, Colombo 01000, Sri Lanka', '', 0, 12, 4.8, 32, NULL, 77, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:40', 0),
(345, 3, 'ChIJQdWUcSpZ4joRWp0OGFKWZmI', 'ESOFT Premier Learning Centre', '', '0117 677 888', '', '1a Galle Face Center Rd, Colombo 00200, Sri Lanka', 'http://premier.esoft.lk/', 1, 13, 3.3, 8, NULL, 5, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:41', 0),
(346, 3, 'ChIJx1gCTVJZ4joRgojStwOylfU', 'English Class @ Colombo 10', '', '076 698 5747', '', '244 Temple Rd, Colombo 01000, Sri Lanka', '', 0, 14, 5.0, 19, NULL, 71, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:42', 0),
(347, 3, 'ChIJXzKExUJZ4joR_T1pmUU6lds', 'MSU Colombo Learning Centre', '', '0112 576 644', '', 'No: 300 Galle Rd, Colombo 00300, Sri Lanka', 'http://msi.edu.lk/', 1, 15, 4.6, 22, NULL, 21, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:48', 0),
(348, 3, 'ChIJKeJqHoZZ4joR78V8BbcRqRs', 'PLA Group Academy', '', '076 036 2718', '', 'Sri Kathiresan St, Colombo 01300, Sri Lanka', 'https://youtube.com/@studywithplagroup229', 0, 16, 4.8, 27, NULL, 71, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:49', 0),
(349, 3, 'ChIJrZLtDcRZ4joRbiBIyOpJOkY', 'Spinnaker International Learning Centre', '', '0117 024 834', '', '113 Kynsey Rd, Colombo 00700, Sri Lanka', '', 0, 17, 3.8, 11, NULL, 61, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:51', 0),
(350, 3, 'ChIJI-lt_mdZ4joRdpq56MJzoyU', 'ANC Campus', '', '077 744 9955', 'info@ancedu.com', '308, 310 R. A. De Mel Mawatha, Colombo 00300, Sri Lanka', 'https://ancedu.com/', 1, 18, 3.9, 343, NULL, 25, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:07:53', 0),
(351, 3, 'ChIJx5k4EBlZ4joRi0_3o5ChMP0', 'Innovatus Campus', '', '076 644 6799', 'info@innovatuscampus.com', 'No.14 Sir Baron Jayatilaka Mawatha, Colombo 00100, Sri Lanka', 'http://www.innovatuscampus.com/', 1, 19, 4.5, 24, NULL, 21, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:00', 0),
(352, 3, 'ChIJDQIOtzdb4joReHL3RhkE3pI', 'Nanaska', '', '077 499 7338', '', '464/1/1 Galle Rd, Colombo 00300, Sri Lanka', 'https://nanaska.com/', 1, 20, 4.9, 48, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:01', 0),
(353, 3, 'ChIJmf_K7vxZ4joRiMEH3VvyOdk', 'Colombo English Studio (Private) Limited', '', '074 349 7397', '', 'NO.4 Pentreve Garden, Colombo 00300, Sri Lanka', '', 0, 21, 4.8, 18, NULL, 71, 'all', 'Colombo, Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:03', 0),
(354, 3, 'ChIJxz5oEXBV_joRCH6axviuDzc', 'institute of software skills development - ISSD.LK', '', '071 554 0123', 'info@issd.lk', 'fist floor, 183 Adiyapatham Rd, Jaffna 40000, Sri Lanka', 'https://issd.lk/', 1, 2, 5.0, 62, NULL, 27, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:14', 0),
(355, 3, 'ChIJf-8gpPhT_joRXSZ8QVCjF1w', 'Physics Tuition Centre', '', '078 790 2300', '', 'Villoondi Road, Jaffna 40000, Sri Lanka', 'https://sites.google.com/view/k7physics/home', 1, 3, 5.0, 1, NULL, 15, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:15', 0),
(356, 3, 'ChIJ_xK1KQCKKowRUC4_y0mVvZg', 'Physics Domain Institute Jaffna', '', '076 603 4895', '', '395, 1 Power House Rd, Jaffna 40000, Sri Lanka', '', 0, 4, 5.0, 12, NULL, 71, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:17', 0),
(357, 3, 'ChIJJYuL7oNV_joRwjWmwWbSDQo', 'EDUS Online Tuition', '', '0114 477 488', '', '95, K.K.S Road, Kokkuvil Junction, Jaffna 40000, Sri Lanka', 'https://www.edustutor.com/', 1, 5, 4.5, 77, NULL, 27, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:25', 0),
(358, 3, 'ChIJO38OLQRU_joRYkOnIv7ivIA', 'New Science World', '', '077 336 4055', '', 'Brown Road, Jaffna, Sri Lanka', '', 0, 6, 4.3, 94, NULL, 77, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:29', 0),
(359, 3, 'ChIJK36j9HRV_joRRsMShQSy96o', 'Manie tuition center', '', '', '', 'M2Q8+M7F, Sabapathy Lane, Jaffna, Sri Lanka', '', 0, 7, 5.0, 1, NULL, 60, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:30', 0),
(360, 3, 'ChIJ_Yk9UvBV_joR_svZuwgYKTw', 'Kids Coaching Center', '', '0212 213 640', '', '52 Wyman\'s Rd, Jaffna, Sri Lanka', '', 0, 8, 5.0, 4, NULL, 65, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:31', 0),
(361, 3, 'ChIJKasrRkhV_joRMu23uh4QRn8', 'Brainwave Academy', '', '077 256 6745', '', '70, 16 Arasady Lane, Jaffna 40000, Sri Lanka', 'https://brainwaveacademy.net/', 1, 9, 4.3, 3, NULL, 15, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:35', 1),
(362, 3, 'ChIJZ21AzaZW_joRRF-8E5DpD_M', 'Knowledge Universe', '', '077 730 2882', '', 'No 23, St Peters Lane, Off Hospital Rd, Jaffna 40000, Sri Lanka', 'https://edu.kugroup.info/', 1, 10, 4.7, 58, NULL, 27, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:36', 0),
(363, 3, 'ChIJySoQ38ZV_joRq7ARDAGBENg', 'பொருளியற் கல்லூரி', '', '077 395 6158', '', '313 Navalar Rd, Jaffna, Sri Lanka', '', 0, 11, 5.0, 2, NULL, 65, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:37', 0),
(364, 3, 'ChIJj_NPkQBV_joRLHmvvwYY-kQ', 'Gnanam Education Trust (GET)', '', '076 232 5603', 'hylin.ratnam@geteducation.lk', '150/ 8 Sivarajah Ave, Jaffna, Sri Lanka', 'http://www.geteducation.lk/', 1, 12, 4.9, 58, NULL, 27, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:40', 0),
(365, 3, 'ChIJd5ru7RtU_joRcbrzNU5kRH0', 'E-CITY COLLEGE OF ENGLISH & IT SKILLS DEVELOPMENT', '', '077 061 5242', '', 'No-320/3, Pointpedro Road, Anaipanthy, Jaffna., Pointpedro Road, AB20, Jaffna, Sri Lanka', 'http://www.ecitycollege.lk/', 1, 13, 4.9, 217, NULL, 35, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:41', 0),
(366, 3, 'ChIJgZeTZwRU_joRW1uvIjABXoE', 'Science Corner', '', '077 611 6724', '', 'Tower, Road, Jaffna, Sri Lanka', '', 0, 14, 5.0, 5, NULL, 65, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:47', 0),
(367, 3, 'ChIJff9n2w5U_joRk_N3y_2ebZE', 'Yarl Institute of Technology (Yarl IT)', '', '077 786 1677', 'info@yarlit.lk', '83 Kannathiddy Rd, Jaffna 40000, Sri Lanka', 'https://yarlit.lk/', 1, 15, 4.9, 75, NULL, 27, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:49', 0),
(368, 3, 'ChIJleMY2prrCiYRBdSVuckfU9M', 'Saho Creative Academy', '', '070 585 9390', 'sahocreativeacademy@gmail.com', 'No.37, 1st Floor, LIC Building, Vembady Street, Jaffna 40000, Sri Lanka', 'https://www.sca.lk/', 1, 16, 4.8, 4, NULL, 15, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:53', 0),
(369, 3, 'ChIJoyOx1oRV_joRXNXk6G_qfCY', 'Edissan Academy-Jaffna', '', '077 773 7926', '', '539A Kasthuriyar Rd, Jaffna, Sri Lanka', '', 0, 17, 4.4, 9, NULL, 65, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:55', 0),
(370, 3, 'ChIJ58jwBqhV_joR4Q4HzLUDEmY', 'BCLL British College Of Language Learning', '', '077 706 6000', '', 'Sirambiady, Jaffna, Sri Lanka', '', 0, 18, 5.0, 20, NULL, 71, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:08:57', 0),
(371, 3, 'ChIJc5BNQwFU_joRIrC5iV76Dhs', 'DMI Computer Education Jaffna', '', '077 717 3517', '', '113 Kannathiddy Rd, Jaffna, Sri Lanka', 'http://dmi.lk/', 1, 19, 4.8, 231, NULL, 35, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:09:03', 0),
(372, 3, 'ChIJ8TIl7x1U_joR8extwhfqa6k', 'Uki Technology School - Jaffna Center', '', '077 569 4587', 'jaffna@uki.life', '4th floor, 218 Stanley Rd, Jaffna 40000, Sri Lanka', 'http://www.uki.life/', 1, 20, 5.0, 12, NULL, 21, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:09:05', 0),
(373, 3, 'ChIJl0qWUxtU_joRKQFEFM4yNMw', 'EP Academy Jaffna (IELTS,PTE,CIMA)', '', '077 628 4845', 'info@epacademy.lk', '354 Clock Tower Rd, Jaffna, Sri Lanka', 'http://www.epacademy.lk/', 1, 21, 4.7, 29, NULL, 21, 'all', 'Jaffna , Sri Lanka', 'Tuition center', 0, NULL, '2026-04-30 09:09:06', 0),
(374, 3, 'ChIJNyfyFdFX_joRlig8GFIrjs8', 'Jaffna Authentic Cuisine / JAC Dining', '', '077 726 5597', '', '812 Hospital St, Jaffna, Sri Lanka', '', 0, 2, 4.2, 1090, 2, 93, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:09:25', 0),
(375, 3, 'ChIJNzKSWXFX_joRqBAazZUTwAU', 'Salem RR Biriyani Jaffna', '', '0212 226 262', '', '603 Hospital Rd, Jaffna 40000, Sri Lanka', '', 0, 3, 4.9, 6050, NULL, 85, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:09:27', 0),
(376, 3, 'ChIJWUI1Hm5X_joRMCqAGsP9M7s', 'MOONLIGHT RESTAURANT', '', '0212 214 000', '', '817 Hospital Rd, Jaffna 40000, Sri Lanka', '', 0, 4, 4.5, 462, NULL, 85, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:09:31', 0),
(377, 3, 'ChIJo7ZLFtBV_joRwULEh3h5aaI', 'Vanni Inn Restaurant - Jaffna branch', '', '077 652 5074', 'vanniinn@hotmail.com', '204 Brown Road, Jaffna, Sri Lanka', 'http://vanniinn.lk/', 1, 5, 4.2, 984, 2, 43, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:09:37', 0),
(378, 3, 'ChIJwyF_-fBV_joRaHj2pSNh6C8', 'Hotel New Selva', '', '077 699 5009', '', '1048 Jaffna-Kankesanturai Rd, Jaffna, Sri Lanka', '', 0, 6, 4.3, 427, 2, 93, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:09:38', 0),
(379, 3, 'ChIJG1chPgBU_joRwahoScB5N9A', 'மலாயன் கபே Malayan Cafe - Jaffna மணியன் கபே', '', '0212 222 373', '', 'M296+33W, C Ponnampalam Rd, Jaffna, Sri Lanka', '', 0, 7, 4.0, 1930, 2, 93, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:09:40', 0),
(380, 3, 'ChIJucSxtWBV_joRFyMudsHUcGM', 'Chinese Dragon Cafe - Jaffna', '', '0117 808 080', '', '229 Jaffna-Point Pedro Rd, Jaffna 40000, Sri Lanka', 'https://www.chinesedragoncafe.lk/', 1, 8, 4.8, 766, NULL, 35, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:09:42', 0),
(381, 3, 'ChIJRSEPiJ5X_joRzeOJepoqhEM', 'Vedan Restaurant', '', '071 984 4854', '', '42 Ilanthaikulam Road, Jaffna 40000, Sri Lanka', '', 0, 9, 4.5, 104, NULL, 85, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:09:44', 0),
(382, 3, 'ChIJb9wYzRFV_joRYYYvcotRxMM', 'A Plus Biriyani Jaffna', '', '077 721 4321', '', 'near by sub post office, 687 Hospital St, Jaffna 40000, Sri Lanka', 'http://www.aplusbiriyani.com/', 1, 10, 4.7, 960, NULL, 35, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:09:56', 0),
(383, 3, 'ChIJV9N-T6NV_joRoGvrVDzY_vM', 'Lavin\'s Vegetarian Family Restaurant', '', '076 330 3030', '', '43 Adiyapatham Rd, Jaffna, Sri Lanka', '', 0, 11, 4.4, 1158, NULL, 85, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:09:58', 0),
(384, 3, 'ChIJL9gbPwBV_joRlhb-Yz1oN6Q', 'Singai Restaurant', '', '077 214 1214', '', '214 Stanley Rd, Jaffna 40000, Sri Lanka', '', 0, 12, 4.2, 164, NULL, 85, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:10:00', 0),
(385, 3, 'ChIJgyitFuJV_joROkJEbrnPDOU', 'The Tiki Jaffna Private Home Dining', '', '076 123 1976', '', '4, 1, 6 Campus Ln, Jaffna, Sri Lanka', '', 0, 13, 4.8, 45, NULL, 77, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:10:02', 0),
(386, 3, 'ChIJF5AGXABX_joRWf_MgVg_w_c', 'MOJU Restaurant', '', '0212 227 573', '', 'No: Main Street, Jaffna, Sri Lanka', '', 0, 14, 4.4, 287, NULL, 85, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:10:04', 0),
(387, 3, 'ChIJt26cjgJU_joRifQpFxyf1a4', 'Akshathai', '', '0212 219 946', '', 'Stanley Rd, Jaffna 40000, Sri Lanka', '', 0, 15, 4.0, 1300, 2, 93, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:10:06', 0),
(388, 3, 'ChIJ-3ZHDSNV_joRLlzS3mb481A', 'Amudham Restaurant', '', '076 137 7144', 'sales@thethinnai.com', '86 Palali Rd, Jaffna 40000, Sri Lanka', 'https://thethinnai.com/dining/', 1, 16, 4.6, 18, NULL, 21, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:10:07', 0),
(389, 3, 'ChIJOeVx5EpV_joR3C8T0KSmYVc', 'Zoco Jaffna', '', '075 575 7545', 'hello@zoco.lk', 'Science Hall Road (Opposite of People\'s Bank, Kannathiddy Junction, Jaffna 40000, Sri Lanka', 'https://zoco.lk/', 1, 17, 4.8, 745, NULL, 35, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:10:09', 0),
(390, 3, 'ChIJzYDYKwBV_joReUPreaDDF3U', 'Delizz Restaurant', '', '077 990 0280', '', '87 Palali Rd, Jaffna, Sri Lanka', '', 0, 18, 4.2, 300, NULL, 85, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:10:11', 0),
(391, 3, 'ChIJx8i5BQpV_joRbiozomxdPcU', 'Crepe Runner - Jaffna Nolimit', '', '076 997 7387', 'info@creperunner.lk', '65 Jaffna-Point Pedro Rd, Jaffna 40000, Sri Lanka', 'https://www.creperunner.lk/', 1, 19, 5.0, 128, NULL, 35, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:10:14', 0),
(392, 3, 'ChIJLQ7rphVV_joRZPitCvAKwXg', 'Adchaya Pathra family restaurant', '', '077 273 6333', '', '63,sir Ramanathan road, Ramanathan Rd, Jaffna 40000, Sri Lanka', 'https://adchayapathra.com/', 1, 20, 4.3, 220, NULL, 35, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:10:15', 0),
(393, 3, 'ChIJozCa8f1X_joRnVZ4rWOrKgM', 'Shakthy Jaffna Kitchen', '', '077 724 0510', '', 'Main Street, Jaffna, Sri Lanka', '', 0, 21, 4.7, 246, NULL, 85, 'all', 'Jaffna , Sri Lanka', 'Restaurant', 0, NULL, '2026-04-30 09:10:17', 0),
(394, 3, 'ChIJNyfyFdFX_joRlig8GFIrjs8', 'Jaffna Authentic Cuisine / JAC Dining', '', '077 726 5597', '', '812 Hospital St, Jaffna, Sri Lanka', '', 0, 2, 4.2, 1090, 2, 93, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:10:31', 0),
(395, 3, 'ChIJo7ZLFtBV_joRwULEh3h5aaI', 'Vanni Inn Restaurant - Jaffna branch', '', '077 652 5074', 'vanniinn@hotmail.com', '204 Brown Road, Jaffna, Sri Lanka', 'http://vanniinn.lk/', 1, 3, 4.2, 984, 2, 43, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:10:34', 0),
(396, 3, 'ChIJwyF_-fBV_joRaHj2pSNh6C8', 'Hotel New Selva', '', '077 699 5009', '', '1048 Jaffna-Kankesanturai Rd, Jaffna, Sri Lanka', '', 0, 4, 4.3, 427, 2, 93, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:10:35', 0),
(397, 3, 'ChIJgyitFuJV_joROkJEbrnPDOU', 'The Tiki Jaffna Private Home Dining', '', '076 123 1976', '', '4, 1, 6 Campus Ln, Jaffna, Sri Lanka', '', 0, 5, 4.8, 45, NULL, 77, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:10:36', 0),
(398, 3, 'ChIJucSxtWBV_joRFyMudsHUcGM', 'Chinese Dragon Cafe - Jaffna', '', '0117 808 080', '', '229 Jaffna-Point Pedro Rd, Jaffna 40000, Sri Lanka', 'https://www.chinesedragoncafe.lk/', 1, 6, 4.8, 766, NULL, 35, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:10:38', 0),
(399, 3, 'ChIJNzKSWXFX_joRqBAazZUTwAU', 'Salem RR Biriyani Jaffna', '', '0212 226 262', '', '603 Hospital Rd, Jaffna 40000, Sri Lanka', '', 0, 7, 4.9, 6050, NULL, 85, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:10:39', 0),
(400, 3, 'ChIJ-3ZHDSNV_joRLlzS3mb481A', 'Amudham Restaurant', '', '076 137 7144', 'sales@thethinnai.com', '86 Palali Rd, Jaffna 40000, Sri Lanka', 'https://thethinnai.com/dining/', 1, 8, 4.6, 18, NULL, 21, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:10:40', 0),
(401, 3, 'ChIJl_CF4sNV_joRc1y-8XXvGpc', 'Nizhal Cafe and Restaurant', '', '076 529 3070', '', '581 Jaffna-Kankesanturai Rd, Jaffna 00040, Sri Lanka', 'https://everpurehitec.com/nizhal', 1, 9, 4.0, 336, NULL, 35, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:10:40', 0),
(402, 3, 'ChIJZUadJpZV_joR8iF8a_zxRp4', 'Seafood restaurant', '', '0212 053 999', '', '132, palali road, Kondavil - Irupalai Rd, Jaffna 40000, Sri Lanka', 'https://seafoodrestaurant.com/', 1, 10, 4.3, 6, NULL, 15, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:10:48', 1),
(403, 3, 'ChIJRSEPiJ5X_joRzeOJepoqhEM', 'Vedan Restaurant', '', '071 984 4854', '', '42 Ilanthaikulam Road, Jaffna 40000, Sri Lanka', '', 0, 11, 4.5, 104, NULL, 85, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:10:49', 0),
(404, 3, 'ChIJQTOGGnxV_joRt-3cZZYgrvQ', 'Aruvi Bistro', '', '077 665 5208', '', '236 Navalar Rd, Jaffna, Sri Lanka', 'https://aruvibistro.com/', 1, 12, 4.7, 108, NULL, 35, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:10:57', 1),
(405, 3, 'ChIJLQ7rphVV_joRZPitCvAKwXg', 'Adchaya Pathra family restaurant', '', '077 273 6333', '', '63,sir Ramanathan road, Ramanathan Rd, Jaffna 40000, Sri Lanka', 'https://adchayapathra.com/', 1, 13, 4.3, 220, NULL, 35, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:10:58', 0),
(406, 3, 'ChIJWUI1Hm5X_joRMCqAGsP9M7s', 'MOONLIGHT RESTAURANT', '', '0212 214 000', '', '817 Hospital Rd, Jaffna 40000, Sri Lanka', '', 0, 14, 4.5, 462, NULL, 85, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:11:04', 0),
(407, 3, 'ChIJHdN-ffBT_joRH8R4iEH-k74', 'ANNAI SEA FOOD Pvt Ltd (Local sale)', '', '0212 228 721', 'ajoshan@annai.lk', 'Beach Rd, Jaffna 40000, Sri Lanka', 'http://annai.lk/', 1, 15, 4.7, 13, NULL, 21, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:11:07', 0),
(408, 3, 'ChIJL9gbPwBV_joRlhb-Yz1oN6Q', 'Singai Restaurant', '', '077 214 1214', '', '214 Stanley Rd, Jaffna 40000, Sri Lanka', '', 0, 16, 4.2, 164, NULL, 85, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:11:07', 0),
(409, 3, 'ChIJPcNXVmdX_joRZ-3Xy4m_5F0', 'Peninsula', '', '0114 709 400', 'resv.jaffna@jetwinghotels.com', 'Cargills Square, 420 Hospital Rd, Jaffna 40000, Sri Lanka', 'https://www.jetwinghotels.com/jetwingjaffna/dining/main-restaurant/#gref', 1, 17, 4.3, 7, NULL, 15, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:11:08', 0),
(410, 3, 'ChIJ48u7knZX_joRLJZDsTmQBmQ', 'The Jaffna Grill', '', '070 332 3222', '', 'M287+4GV, Sangarapillai Rd, Manipay, Sri Lanka', '', 0, 18, 4.3, 201, NULL, 85, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:11:09', 0),
(411, 3, 'ChIJYzbfAgBU_joRCTD5RFmnjvw', 'Paradise Restaurant - Jaffna', '', '076 675 7787', '', '120 Jaffna-Kankesanturai Rd, Jaffna, Sri Lanka', '', 0, 19, 3.4, 132, NULL, 75, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:11:11', 0),
(412, 3, 'ChIJozCa8f1X_joRnVZ4rWOrKgM', 'Shakthy Jaffna Kitchen', '', '077 724 0510', '', 'Main Street, Jaffna, Sri Lanka', '', 0, 20, 4.7, 246, NULL, 85, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:11:12', 0),
(413, 3, 'ChIJE6F9DBtU_joRK4CwZnjgTBE', 'Yarl Pardy Residency', '', '0212 226 868', '', '51 Amman Rd, Jaffna 40000, Sri Lanka', '', 0, 21, 4.0, 187, NULL, 85, 'all', 'Jaffna , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:11:13', 0),
(414, 3, 'ChIJodYNZ6Zz4ToRtQn0fEMJ8zc', 'Bastille Fort Galle', '', '0912 242 935', '', 'No:22 Pedlar St, Galle 80000, Sri Lanka', '', 0, 2, 4.6, 1115, NULL, 85, 'all', 'Galle , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:11:24', 0),
(415, 3, 'ChIJV-0xfHNz4ToRJlse7Z8AqGg', 'The Fisherman\'s Dish', '', '', '', 'Galle Rd, Galle 80000, Sri Lanka', 'https://www.instagram.com/fishermans.dish?igsh=MTlldnl1cjJ1MDZxcQ%3D%3D&utm_source=qr', 0, 3, 4.8, 294, NULL, 80, 'all', 'Galle , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:11:26', 0),
(416, 3, 'ChIJ-1jJTqZz4ToRN8Sbynkney4', 'Elita Restaurant', '', '077 242 3442', '', '34 Middle St, Galle 80000, Sri Lanka', 'https://m.facebook.com/elita.restaurant/', 0, 4, 4.3, 966, 2, 93, 'all', 'Galle , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:11:28', 0),
(417, 3, 'ChIJ1dPY5Zhz4ToR_HOt0mvVZhQ', 'The Arch Restaurant - Galle Fort', '', '0912 238 053', '', '01 A Church Cross St, Galle 80000, Sri Lanka', 'http://www.thearch.lk/', 1, 5, 4.7, 433, NULL, 35, 'all', 'Galle , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:11:36', 0),
(418, 3, 'ChIJW9kkIaRz4ToRW_HsUcYW7Ms', 'The Bungalow Galle Fort - Restaurant & Cocktail Bar.', '', '', '', '3 Church Cross St, Galle 80212, Sri Lanka', 'https://www.instagram.com/thebungalowgallefort/', 0, 6, 4.8, 2317, NULL, 80, 'all', 'Galle , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:11:38', 0),
(419, 3, 'ChIJy9b-v6Zz4ToRugWnWhLsGj8', 'Pedlar\'s Inn Cafe and Restaurant', '', '0912 225 333', '', '92 Pedlar\'s Street, Galle 80000, Sri Lanka', 'http://pedlarsinncafe.com/', 1, 7, 4.5, 3181, 2, 43, 'all', 'Galle , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:11:39', 0),
(420, 3, 'ChIJnZxnso514ToRhVn9U4j7_6c', 'Luuma Beach', '', '074 012 4010', 'luumabeachrestaurant@gmail.com', '515/A Galle - Colombo Rd, Galle 80000, Sri Lanka', 'https://luumabeachsrilanka.com/', 1, 8, 4.9, 588, NULL, 35, 'all', 'Galle , Sri Lanka', 'seafood restaurant', 0, NULL, '2026-04-30 09:11:46', 0),
(421, 3, 'ChIJQcsyFq7u4joRq2MuWVuD1o4', 'Negombo Diving Centre', '', '077 020 0192', 'info@negombodivingcentre.com', '285/6 Lewis Pl, Negombo 11500, Sri Lanka', 'http://www.negombodivingcentre.com/', 1, 2, 4.5, 65, NULL, 27, 'all', 'Negombo, Sri Lanka', 'dive center', 0, NULL, '2026-04-30 09:13:58', 0),
(422, 3, 'ChIJ-8L-PUXp4joRihs-S5FNpkY', 'Colombo Divers Negombo', '', '', '', 'Topaz Beach Hotel, 21 Porutota Rd, Negombo 11500, Sri Lanka', 'http://www.colombodivers.lk/', 1, 3, 4.7, 204, NULL, 30, 'all', 'Negombo, Sri Lanka', 'dive center', 0, NULL, '2026-04-30 09:14:03', 0),
(423, 3, 'ChIJhWfGXkXp4joRjyWAvrDUytk', 'Sri Lanka Diving Tours, Negombo, PADI 5 Star IDC Center', '', '077 764 8459', 'hotmail.comsashaanfernando@yahoo.com', '158 Porutota Rd, Negombo 11540, Sri Lanka', 'http://www.srilanka-divingtours.com/', 1, 4, 4.9, 122, NULL, 35, 'all', 'Negombo, Sri Lanka', 'dive center', 0, NULL, '2026-04-30 09:14:08', 0),
(424, 3, 'ChIJe8FvFADp4joRim29lgm5mTI', 'Tony surf', '', '077 764 6177', '', '13 Porutota Rd, Negombo 11500, Sri Lanka', '', 0, 2, 4.4, 7, NULL, 65, 'all', 'Negombo, Sri Lanka', 'surf school', 0, NULL, '2026-04-30 09:14:22', 0),
(425, 3, 'ChIJ1X2WcQDp4joRk8qk6Hi3vUk', 'Christy surf school', '', '077 764 6177', '', 'No: 27 Porutota Rd, Negombo 11500, Sri Lanka', '', 0, 3, 3.2, 6, NULL, 55, 'all', 'Negombo, Sri Lanka', 'surf school', 0, NULL, '2026-04-30 09:14:24', 0),
(426, 3, 'ChIJ8T3AnVsV4ToR7E40Lk095b8', 'The Surfer Surf Camps Sri Lanka - Your Best Surf camp in Sri Lanka Weligama - Surf and Yoga Camp Weligama', '', '077 392 6614', '', '5.969630, 80.446641 Beach access road, Weligama 81700, Sri Lanka', 'http://www.thesurferweligama.com/', 1, 4, 4.9, 1605, NULL, 35, 'all', 'Negombo, Sri Lanka', 'surf school', 0, NULL, '2026-04-30 09:14:24', 0),
(427, 3, 'ChIJZUBWeDw_4ToRhIYJCJvzPqs', 'Chuty‘s Surf School Mirissa Beach', '', '071 151 2458', '', 'Sunanda Road, Mirissa 81740, Sri Lanka', 'http://www.chutys-surf.com/', 1, 5, 5.0, 897, NULL, 35, 'all', 'Negombo, Sri Lanka', 'surf school', 0, NULL, '2026-04-30 09:14:28', 0),
(428, 3, 'ChIJLWnEqTAV4ToR6SwJA39Jy8g', 'Layback | Surf Camp in Sri Lanka 〰 Surf Camps + Surf & Yoga Retreats in Weligama', '', '076 630 0126', 'hello@layback.lk', '247 Main Street, Weligama 81700, Sri Lanka', 'https://www.layback.lk/', 1, 6, 4.9, 419, NULL, 35, 'all', 'Negombo, Sri Lanka', 'surf school', 0, NULL, '2026-04-30 09:14:30', 0),
(429, 3, 'ChIJg_BEx0sT4ToRR_iqrxSK7b8', 'Lapoint Surf Camp', '', '018-800 81 25', 'info@lapointcamps.com', 'Lapoint Surfcamp Sri Lanka Kabalana Kataluwa, Ahangama Ahangama LK, Po 80650, Sri Lanka', 'http://www.lapointcamps.com/surfcamp/sri-lanka/', 1, 7, 4.7, 393, NULL, 35, 'all', 'Negombo, Sri Lanka', 'surf school', 0, NULL, '2026-04-30 09:14:33', 0),
(430, 3, 'ChIJnfCMMUYV4ToRvAdWCJDcDSQ', 'Lucky\'s Surf School', '', '076 606 9177', '', 'Weligama By Pass Rd, Weligama 81700, Sri Lanka', 'https://www.luckyssurfweligama.com/', 1, 8, 4.9, 1743, NULL, 35, 'all', 'Negombo, Sri Lanka', 'surf school', 0, NULL, '2026-04-30 09:14:42', 0),
(431, 3, 'ChIJsQ2wdKQV4ToRNJ5XPmFZTGM', 'Elsewhere Surf Camps in Weligama, Sri Lanka', '', '074 177 5858', 'reception@elsewheresurfcamps.com', '291 Weligama By Pass Rd, Weligama 81700, Sri Lanka', 'http://www.elsewheresurfcamps.com/srilanka', 1, 9, 4.8, 499, NULL, 35, 'all', 'Negombo, Sri Lanka', 'surf school', 0, NULL, '2026-04-30 09:14:45', 0),
(432, 3, 'ChIJz-uUd4AV4ToRO5F0DPScR88', 'Kima Surf Camp Sri Lanka', '', '0811-3944-332', 'info@kimasurf.com', '637 Matara Rd, Weligama 81700, Sri Lanka', 'https://kimasurfsrilanka.com/', 1, 10, 4.9, 615, NULL, 35, 'all', 'Negombo, Sri Lanka', 'surf school', 0, NULL, '2026-04-30 09:14:46', 0),
(433, 3, 'ChIJqYUo_ChL4ToRRUCfkapV_fo', 'Tangalle Surf School', '', '071 678 9932', 'mgmkumara@gmail.com', 'Ceylon sea hotel, Pagngnawasa Mawatha, තංගල්ල 82200, Sri Lanka', 'http://www.tangallesurfschool.com/', 1, 11, 5.0, 147, NULL, 35, 'all', 'Negombo, Sri Lanka', 'surf school', 0, NULL, '2026-04-30 09:14:57', 0),
(434, 3, 'ChIJd0HRNADp4joR3dZPd_WbxY8', 'Sea sand', '', '', '', '6RQR+RJH, Negombo, Sri Lanka', 'https://www.seasand.com/', 1, 12, NULL, 0, NULL, 0, 'all', 'Negombo, Sri Lanka', 'surf school', 0, NULL, '2026-04-30 09:15:02', 1),
(435, 3, 'ChIJPzBm9L7v4joREMCgwFRltpc', 'River sky tours pvt Ltd', '', '076 785 5855', '', '67 Alles Rd, Negombo 11500, Sri Lanka', 'https://riverskytours.lk/', 1, 2, 5.0, 170, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:15:15', 0),
(436, 3, 'ChIJaayCOrPu4joRY_49R4Rqtgk', 'Jeromwin Tours', '', '077 770 7449', 'info@jeromwintours.lk', '17 St Joseph Mawatha, Negombo 11500, Sri Lanka', 'http://www.jeromwintours.com/', 1, 3, 4.9, 113, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:15:18', 0),
(437, 3, 'ChIJOdrdjE_p4joRWxTqnqRdmPg', 'DS Tours', '', '077 654 4095', 'dstourssrilanka@gmail.com', '98/1, Palagathure West, Kochchikade, Negombo, Sri Lanka', 'https://www.dstoursrilanka.com/', 1, 4, NULL, 160, NULL, 25, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:15:21', 0),
(438, 3, 'ChIJ8UdPlkTp4joR3c_lwMauvSU', 'Darshana Tours - Private Driver Sri Lanka', '', '077 640 0538', 'info@darshanatours.com', 'No 49/B Palagature West Negombo, Negombo 11540, Sri Lanka', 'http://www.darshanatours.com/', 1, 5, 5.0, 204, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:15:24', 0),
(439, 3, 'ChIJ73mEMrDo4joRVSuu1eugDtw', 'Sri Shannon Tours- Private driver , Sri Lanka.', '', '077 754 3824', 'lambertneel@yahoo.com', 'No.22 Duwana Lane, මීගමුව 11500, Sri Lanka', 'http://www.srishannontours.com/', 1, 6, NULL, 161, NULL, 25, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:15:27', 0),
(440, 3, 'ChIJEU-lRqrv4joR6QjebdBI1mU', 'Real Lanka Holidays (Taxies & Negombo Cooking Classes & Water Sport Provider)', '', '077 710 8200', 'premilreallankaholiday@gmail.com', 'No 699/2, Thonwood Estate, Katunayake, Negombo 11450, Sri Lanka', 'http://www.rlholidays.com/', 1, 7, 4.8, 725, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:15:28', 0),
(441, 3, 'ChIJA1vGnE7p4joRvpFi_x8h7cw', 'Chamila Tours - Private Driver Sri Lanka', '', '077 617 1581', 'info@chamilatours.com', 'No.48 St Joseph Mawatha, Negombo 11500, Sri Lanka', 'http://chamilatours.com/', 1, 8, 5.0, 215, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:15:34', 0),
(442, 3, 'ChIJp8Wfo_Xu4joRB8WVswe8HvU', 'Ayubowan Tours And Travels (PVT) Limited in Negombo', '', '077 789 9533', 'info@ayubowantours.com', 'No. 15, Ranomoto Shopping Complex, Colombo Road,, Negombo 11500, Sri Lanka', 'https://ayubowan.travel/', 1, 9, 4.3, 131, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:15:36', 0),
(443, 3, 'ChIJHScwWpnv4joR-1Fq3l3buyo', 'Taprobane Tours and Travels', '', '070 399 5771', 'taprobanetours@sltnet.lk', '1 St Anthoney\'s Rd, Negombo 11500, Sri Lanka', 'http://www.taprobanetoursandtravel.com/', 1, 10, 5.0, 32, NULL, 27, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:15:39', 0),
(444, 3, 'ChIJuyh9n0Tp4joRzKofeX7_38w', 'Sri Lanka Brothers Tour - Private Driver Sri Lanka', '', '077 415 6266', '', '63/2 Beach Road Palangature, Negombo, Sri Lanka', 'http://slbrotherstour.com/', 1, 11, 5.0, 207, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:15:45', 0),
(445, 3, 'ChIJI3OtuY7u4joRoGJ6e4DA36M', '360 TOURS LANKA', '', '077 930 1088', 'info@360tourslanka.com', 'No. 286/2/A(121), 60 Feet Road Daluwakotuwa, Negombo 11500, Sri Lanka', 'https://www.360tourslanka.com/', 1, 12, 4.8, 149, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:15:49', 0),
(446, 3, 'ChIJvURTSRPp4joRZf7YUKU4bk4', 'PL Tours & Travels', '', '077 783 2253', '', '114 Plagathura west, Negombo 11500, Sri Lanka', 'https://pltoursandtravel.com/', 1, 13, 5.0, 18, NULL, 21, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:15:55', 0),
(447, 3, 'ChIJoxBOHTTv4joRetCYo6Qm91M', 'Croos Tours (Tuk Tuk Rental Negombo Sri Lanka)', '', '076 664 4471', 'info@croostours.com', '8c Rathna Mawatha, Negombo 11500, Sri Lanka', 'http://croostours.com/', 1, 14, 5.0, 576, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:15:58', 0),
(448, 3, 'ChIJKaoa-LDu4joRmlEEsbS-bIk', 'DM Tours negombo Srilanka', '', '', 'info@dmtoursnegombo.com', '27, 15 Cemetery Rd, Negombo, Sri Lanka', 'http://www.dmtoursnegombo.com/', 1, 15, 4.8, 37, NULL, 22, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:16:01', 0),
(449, 3, 'ChIJ8b-hXY3u4joRcIZQVAEpQQs', '2nd Chance Travels (Pvt) Ltd', '', '077 338 8970', 'info@2ndchancetravels.com', '95 Negombo - Colombo Main Rd, Negombo, Sri Lanka', 'https://2ndchance.travel/', 1, 16, 4.6, 183, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:16:03', 0),
(450, 3, 'ChIJv_paoUrp4joRQqhVKLNIOl8', 'Ceylon Adventure Tours', '', '077 717 3007', 'info@ceylonadventuretours.com', '117 Lewis Pl, Negombo 11500, Sri Lanka', 'http://ceylonadventuretours.com/', 1, 17, 4.7, 134, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:16:06', 0),
(451, 3, 'ChIJwcK-paHv4joRH_alOvS5csU', 'Season Travels Global (PVT) Ltd', '', '077 724 8777', 'info@seasontravels.com', '51/1 Galison Mawatha, Negombo 11500, Sri Lanka', 'http://www.seasontravels.com/', 1, 18, 4.7, 62, NULL, 27, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:16:08', 0),
(452, 3, 'ChIJCT4wPEzp4joReGOoXrqIWsM', 'Navoda Tours', '', '077 986 2192', 'yourmail@gmail.com', '72 St Joseph Mawatha, Negombo 11500, Sri Lanka', 'http://www.navodatours.com/', 1, 19, 5.0, 133, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:16:09', 0),
(453, 3, 'ChIJq-xs2ZXu4joRGOF7m12WgRI', 'Airwing Tours', '', '0312 238 377', 'travel@airwingtours.com', '68 Colombo Rd, Negombo, Sri Lanka', 'http://www.airwingtours.com/', 1, 20, 4.3, 71, NULL, 27, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:16:12', 0),
(454, 3, 'ChIJ5Y8NiZrv4joR_H3HLjBf2qc', 'Blooming Lanka Tours (Pvt) Ltd', '', '077 381 0704', 'info@bloominglanka.com', '463, 17 Colombo Road, Negombo 11500, Sri Lanka', 'http://www.bloominglanka.com/', 1, 21, 5.0, 28, NULL, 21, 'all', 'Negombo, Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:16:14', 0),
(455, 3, 'ChIJHYGSJZm9-zoRc1ly33TQWfs', 'Trincomalee Beach', '', '', '', '410/1, Nugasewana Mawatha,5th Mile Post, Kandy Rd, Trincomalee, Sri Lanka', '', 0, 2, 4.7, 482, NULL, 80, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:16:26', 0),
(456, 3, 'ChIJRYR6buKj-zoRevYhQdcWnWg', 'Trincomalee Bay', '', '', '', 'Trincomalee Bay, Sri Lanka', '', 0, 3, 4.0, 9, NULL, 60, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:16:29', 0),
(457, 3, 'ChIJBSmeQqO8-zoRgIv5TuUv-ig', 'Fort Frederick', '', '', '', 'H6GV+W86, Trincomalee, Sri Lanka', '', 0, 4, 4.4, 2631, NULL, 80, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:16:31', 0),
(458, 3, 'ChIJFzqGS-q8-zoRYVYSdYUYKRU', 'Trincomalee Railway Station', '', '', '', 'Trincomalee, Sri Lanka', 'http://www.railway.gov.lk/', 1, 5, 4.2, 154, NULL, 30, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:16:32', 0),
(459, 3, 'ChIJKZE_cE-8-zoR1rF4o6Ud6NA', 'Trinco Blu by Cinnamon', '', '0262 222 307', '', '8.619344, J699+75H Sampalthivu Post Uppuveli, 81.218409 Sarvodaya Rd, Trincomalee 83408, Sri Lanka', 'https://www.cinnamonhotels.com/trinco-blu-by-cinnamon?utm_source=google&utm_medium=organic&utm_campaign=gbp', 1, 6, 4.5, 4214, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:16:39', 0),
(460, 3, 'ChIJO_eya5zu4joRPm8YCOkmFqU', 'Negombo', '', '', '', 'Negombo, Sri Lanka', '', 0, 7, NULL, 0, NULL, 50, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:16:43', 0),
(461, 3, 'ChIJzQQJrLG8-zoROd89-Boqek4', 'Pigeon Island Diving Centre Trincomalee Sri Lanka', '', '077 511 1948', 'info@pigeonislanddiving.com', '330 Dockyard Rd, Trincomalee 31000, Sri Lanka', 'https://www.pigeonislanddiving.com/', 0, 8, 4.9, 475, NULL, 85, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:16:52', 0),
(462, 3, 'ChIJBaPo6E68-zoR7PH1yg2kktE', 'Amaranthé Bay Resort & Spa', '', '0262 050 200', 'reservations@amaranthebay.com', '101/17, Alles Garden Road, Uppuveli, Trincomalee, Sri Lanka', 'http://amaranthebay.com/', 1, 9, 4.5, 975, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:16:57', 0),
(463, 3, 'ChIJl9QCupi8-zoRndY8bAGzLXI', 'Lovers Leap', '', '', '', 'H6MW+965, Trincomalee, Sri Lanka', 'https://www.loversleap.com/', 1, 10, 4.4, 153, NULL, 30, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:17:02', 1),
(464, 3, 'ChIJfTu2q3-9-zoRbS-oBqe2JP0', 'Trincomalee fishing & charters', '', '075 271 5887', '', '319 Court Rd, Trincomalee 31000, Sri Lanka', 'https://www.facebook.com/TrincomaleeAnglerSACHIN?mibextid=ZbWKwL', 0, 11, 5.0, 95, NULL, 77, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:17:04', 0),
(465, 3, 'ChIJJ1oZHUej-zoRv2w7ZDGAvoU', 'Coral Cove Beach', '', '', '', 'Coral Cove Beach, Trincomalee, Sri Lanka', '', 0, 12, 4.2, 74, NULL, 72, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:17:06', 0),
(466, 3, 'ChIJB9Lyody8-zoReZNmWk5iprs', 'Trinco Lagoon', '', '0262 227 447', '', '182, Lower road, Orr\'s hill, 164 Lower Rd, Trincomalee, Sri Lanka', 'http://trincolagoon.com/', 1, 13, 4.3, 228, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:17:09', 0),
(467, 3, 'ChIJ9zNLVwCj-zoR1N8F0LBbTeY', 'Trincomalee Bay', '', '', '', 'H659+XM2, Trincomalee, Sri Lanka', '', 0, 14, NULL, 0, NULL, 50, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:17:10', 0),
(468, 3, 'ChIJrcL1lbej-zoR8Gknkxp1sKU', 'ත්‍රිකුණාමලය තෙල් ටැංකි සංකීර්ණය | Trincomalee Oil Tank Farm', '', '', '', 'H55Q+2J4, Trincomalee, Sri Lanka', '', 0, 15, 4.6, 9, NULL, 60, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:17:12', 0),
(469, 3, 'ChIJozQ1Bx29-zoR7J83dPkbSWA', 'SRI LANKA DIVING TOURS Trincomalee PADI 5 Star IDC Center', '', '077 876 2085', 'hotmail.comsashaanfernando@yahoo.com', 'Alles garden, Trincomalee 20000, Sri Lanka', 'http://www.srilanka-divingtours.com/', 1, 16, 4.9, 190, NULL, 35, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:17:15', 0),
(470, 3, 'ChIJyc1XCU68-zoRzlEk9ig2zc8', 'Trincomalee War Cemetery', '', '', '', '300 Nilaveli Rd, Trincomalee, Sri Lanka', '', 0, 17, 4.6, 96, NULL, 72, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:17:16', 0),
(471, 3, 'ChIJjyv3H07p4joRUNbCrtBDFnA', 'Negombo Beach', '', '', '', 'Negombo Beach, Negombo, Sri Lanka', '', 0, 18, 4.2, 960, NULL, 80, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:17:19', 0),
(472, 3, 'ChIJC31qTQi9-zoR6EC-QHy_IqU', 'Beach trinco', '', '', '', 'J6MG+262, Unnamed Road, Trincomalee, Sri Lanka', '', 0, 19, 4.6, 11, NULL, 66, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:17:21', 0),
(473, 3, 'ChIJj-IMZ7K8-zoRnsHGFV1r2mA', 'District General Hospital Trincomalee | ත්‍රිකුණාමලය දිස්ත්‍රික් මහ රෝහල | திருக்கோணமலை மாவட்ட பொது வைத்தியசாலை', '', '0262 222 261', '', 'H67Q+WQW, Hospital Ln, Trincomalee, Sri Lanka', '', 0, 20, 3.7, 94, NULL, 67, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:17:23', 0),
(474, 3, 'ChIJjV6P7bC8-zoRCTaicUxY1G4', 'Maritime And Naval History Museum', '', '', '', 'Lavender Ln, Trincomalee, Sri Lanka', '', 0, 21, 4.4, 661, NULL, 80, 'all', 'Negombo, Sri Lanka', 'Trincomalee', 0, NULL, '2026-04-30 09:17:25', 0),
(475, 3, 'ChIJU0A7HpS9-zoRokYYH5lWJD8', 'LTR Holidays (Lanka Travel Routes)', '', '077 277 4674', '', 'Central Road, Orr\'s Hill, Trincomalee 31000, Sri Lanka', 'https://www.facebook.com/LankaTravelRoutes', 0, 2, 5.0, 51, NULL, 77, 'all', 'Trincomalee , Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:18:09', 0),
(476, 3, 'ChIJLwOhfgC9-zoRIAF70KTQea4', 'Trinco M&M Snorkeling Tours', '', '077 539 0609', '', 'allesgarden, 47 Beach road, Trincomalee, Sri Lanka', '', 0, 3, 4.9, 233, NULL, 85, 'all', 'Trincomalee , Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:18:11', 0),
(477, 3, 'ChIJ2QERZxK9-zoR0v9TTDre2kw', 'Whale Snorkeling Tours Trincomalee', '', '077 487 0503', 'info@bluewhalesnorkelingtourstrincomalee.com', '30 Beach road, Alesgarden 31000, Sri Lanka', 'http://www.bluewhalesnorkelingtourstrincomalee.com/', 1, 4, 4.9, 190, NULL, 35, 'all', 'Trincomalee , Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:18:16', 0),
(478, 3, 'ChIJAZbnXT-9-zoREskWY4Zj99A', 'Believe-it tours & rentals', '', '077 432 3187', '', 'No : 25 velankanni street, Trincomalee, Sri Lanka', '', 0, 5, 5.0, 95, NULL, 77, 'all', 'Trincomalee , Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:18:17', 0),
(479, 3, 'ChIJEyUjT8K9-zoRiOY9SFGRUP0', 'trinco water sport tours', '', '075 830 9152', '', '15,beach road, alles garden, Trincomalee 31000, Sri Lanka', 'https://www.trincowatersporttours.com/', 1, 6, 4.9, 124, NULL, 35, 'all', 'Trincomalee , Sri Lanka', 'Tour operator', 0, NULL, '2026-04-30 09:18:18', 1),
(480, 3, 'ChIJVfchKWm9-zoR8254w1Iek8g', 'Trinco Town Family Guest House', '', '077 724 4394', '', '19B 4th Lane, Vihara Rd, Trincomalee 31000, Sri Lanka', '', 0, 2, 4.7, 17, NULL, 71, 'all', 'Trincomalee, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 09:18:54', 0),
(481, 3, 'ChIJhdbmjSO9-zoRbefQ2J3jU1k', 'Laila Guest House Trincomalee', '', '071 447 1533', '', 'No,8/1Tattkai Lane, 3rd Mile Post, Trincomalee 31000, Sri Lanka', '', 0, 3, 4.5, 19, NULL, 71, 'all', 'Trincomalee, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 09:18:55', 0),
(482, 3, 'ChIJD3WmJ8a9-zoRNWuIpdGYOWI', 'Breeze guest House', '', '', '', 'No.30 Beach road,alesgarden, Trincomalee 31000, Sri Lanka', '', 0, 4, 4.8, 57, NULL, 72, 'all', 'Trincomalee, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 09:18:59', 0),
(483, 3, 'ChIJfRf6G0K9-zoR6J24LAPQP3U', 'Kaweesha Guest House, Trincomalee', '', '071 726 1788', '', 'Trincomalee 35000, Sri Lanka', '', 0, 5, 4.5, 49, NULL, 77, 'all', 'Trincomalee, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 09:19:00', 0),
(484, 3, 'ChIJVyQfRae9-zoRzwdAOGfl5eo', 'TRINCO VISTARA GUEST HOUSE', '', '075 655 5123', '', '49/1 4th lane , kannakipuram, Trincomalee 31000, Sri Lanka', '', 0, 6, 5.0, 26, NULL, 71, 'all', 'Trincomalee, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 09:19:02', 0),
(485, 3, 'ChIJ2_fxHPu8-zoRixEA2AJLR0c', 'Amila Guest House', '', '0262 227 649', '', 'Abeyapura, 44 Lenin Mawatha, Trincomalee, Sri Lanka', '', 0, 7, 4.0, 82, NULL, 77, 'all', 'Trincomalee, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 09:19:03', 0),
(486, 3, 'ChIJI3zoJlC8-zoRH8cPfgeVdiw', 'SNP star guest house', '', '077 811 3008', '', '16, beach road, Ales garden, Trincomalee 31000, Sri Lanka', '', 0, 8, 4.7, 64, NULL, 77, 'all', 'Trincomalee, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 09:19:06', 0),
(487, 3, 'ChIJ8ZVyE1C8-zoR-Q4VzcztxXY', 'Natraj Guest House', '', '077 045 6446', '', 'No 36 Allasgarden Beach road, Trincomalee, Sri Lanka', 'https://www.natrajguesthouse.com/home', 1, 9, 4.3, 138, NULL, 35, 'all', 'Trincomalee, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 09:19:07', 0),
(488, 3, 'ChIJC_YMEwC9-zoRt4LPTpeTLAY', 'Backpacker GuestHouse Trincomalee', '', '070 487 2294', '', '19, 12 Vihara Rd, Trincomalee 31000, Sri Lanka', '', 0, 10, 4.6, 9, NULL, 65, 'all', 'Trincomalee, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 09:19:09', 0),
(489, 3, 'ChIJacnrBgC9-zoRt_aUeI0l3OQ', 'Neem Tree House Trincomalee', '', '071 918 9529', 'user@domain.com', '77 Main St, Trincomalee 31000, Sri Lanka', 'http://www.neemtreehouse.com/', 1, 11, 4.5, 19, NULL, 21, 'all', 'Trincomalee, Sri Lanka', 'Guest house', 0, NULL, '2026-04-30 09:19:10', 0),
(490, 3, 'ChIJ7ZLVmWdZ4joRHhIWGwsekf0', 'Base Hair and Nail Studio', '', '077 716 4499', 'basebythuurya@gmail.com', 'Shop number 202, 2nd floor, Colombo City Center, 137 Sir James Pieris Mawatha, Colombo 2, Sri Lanka', 'https://www.base-by-thuurya.com/', 1, 2, 4.8, 807, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:11', 0),
(491, 3, 'ChIJYdqZkVtZ4joRT0-er7N-9t4', 'Dee’s Hair, Beauty & Bridal Salon', '', '077 660 7607', '', '146a Professor Nandadasa Kodagoda Rd, Colombo 00700, Sri Lanka', 'https://www.instagram.com/dees_hairbeauty_salons', 0, 3, 4.9, 2720, NULL, 85, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:13', 0),
(492, 3, 'ChIJk1a_1c1b4joRHvCLFQUr9Bg', 'Noeline\'s', '', '077 210 2210', 'info@noelines.com', '78a Stratford Ave, Colombo 00600, Sri Lanka', 'http://www.noelines.com/', 1, 4, 4.8, 5530, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:14', 0),
(493, 3, 'ChIJoenko3lb4joR0aQCEOUuhLw', 'Cloudnine Beauty salon Wellawatte', '', '077 780 3082', '', '67,W.A.Silva mawatha, Galle Rd, Colombo 00600, Sri Lanka', '', 0, 5, 4.8, 571, NULL, 85, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:15', 0),
(494, 3, 'ChIJISg2x7lb4joRFdR8FlCHQaU', 'Megaa Beauty Solution World', '', '0112 364 229', '', '00600, No: 4/1 Fussel\'s Ln, Colombo 00600, Sri Lanka', '', 0, 6, 4.8, 827, NULL, 85, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:17', 0),
(495, 3, 'ChIJPdPF8lRb4joRWrjjLDnybY4', 'Hair me by Anushka', '', '077 455 5410', '', '185, 10 Havelock Rd, Colombo 00500, Sri Lanka', 'https://hairmebyanushka.com/', 1, 7, 4.9, 317, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:18', 0),
(496, 3, 'ChIJy17U79FZ4joRVkoEWIb2HV0', 'Toni&Guy', '', '0112 102 015', 'info@toniandguysl.com', '65b R.G. Senanayake Mawatha, Colombo 00700, Sri Lanka', 'http://toniandguysl.com/', 1, 8, 4.6, 473, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:19', 0),
(497, 3, 'ChIJk1S0lk9Z4joRYY0gR6WfSv0', 'Don Hair & Beauty Salon', '', '077 043 5971', '', '7 th floor Amari hotel Colombo, amari, 254 Galle Rd, Colombo 00300, Sri Lanka', 'https://www.facebook.com/Budagoda/', 0, 9, 4.7, 129, NULL, 85, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:21', 0),
(498, 3, 'ChIJ__D2tkxZ4joRxFRUCQAXINA', 'Rumours', '', '077 340 2076', 'info@rumours.lk', '467 Union Pl, Colombo 00200, Sri Lanka', 'http://www.rumours.lk/', 1, 10, 4.7, 119, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:23', 0),
(499, 3, 'ChIJ_6rl8U5Z4joRfGjVwaYOfTI', 'Naturals Unisex Salon Kotahena', '', '076 650 0100', 'info@naturals.lk', '298 George R. De Silva Mawatha, Colombo 01300, Sri Lanka', 'https://www.naturals.lk/branches/kotahena', 1, 11, 4.7, 850, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:30', 0),
(500, 3, 'ChIJuZv-ajpZ4joRpn0nXl_E7tE', 'Studio Zee | Colombo 07', '', '076 106 3011', 'sviduranga41@gmail.com', '20A Guildford Cres, Colombo 00700, Sri Lanka', 'https://studiozee.lk/', 1, 12, 4.3, 308, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:31', 0),
(501, 3, 'ChIJJTEOMHJZ4joR2kloYmkQ9zs', 'Kess', '', '0112 676 855', '', '1st floor, Park St, mews 00200, Sri Lanka', 'https://kess.com/', 1, 13, 4.5, 182, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:39', 1),
(502, 3, 'ChIJ3bpD70BZ4joRsMwvT7rBF6s', 'Chagall Colombo Sri Lanka', '', '077 735 3177', '', '33 Park St, Colombo 00200, Sri Lanka', '', 0, 14, 4.3, 249, NULL, 85, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:41', 0),
(503, 3, 'ChIJozrJ7M5b4joRT2mrHn0KLUA', 'Scissors Beauty and Hair Salon', '', '0114 308 967', '', '300 Havelock Rd, Colombo 00500, Sri Lanka', '', 0, 15, 4.1, 978, NULL, 85, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:42', 0),
(504, 3, 'ChIJM3pjO3FQ4joR0v6j9ylLkEg', 'Salon Zero', '', '071 314 4544', 'salonzeropvtltd@gmail.com', '240/1/1 B120, Nugegoda 11222, Sri Lanka', 'https://salonzero.lk/', 1, 16, 4.7, 1585, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:43', 0),
(505, 3, 'ChIJjyNo6nZZ4joR2ihldXl9a1E', 'Mosh - Aesthetic Lounge', '', '0112 690 549', '', '49/1 Ward Pl, Colombo 00700, Sri Lanka', 'http://www.mosh.lk/', 1, 17, 4.2, 457, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:46', 0),
(506, 3, 'ChIJSyKkc8Rb4joRp6cWbudcwS8', 'Salon Anoma', '', '0112 596 862', '', 'Bambalapitiya Flats, 268 Galle Rd, Colombo 00400, Sri Lanka', 'https://www.facebook.com/profile.php?id=100063667976016&mibextid=LQQJ4d', 0, 18, 4.3, 433, NULL, 85, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:48', 0),
(507, 3, 'ChIJ08iZadpb4joRClC_DDLMrpA', 'Salon Top To Toe', '', '0114 883 186', '', '68 Vajira Rd, Colombo 00400, Sri Lanka', '', 0, 19, 4.4, 90, NULL, 77, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:50', 0);
INSERT INTO `lead_gen_results` (`id`, `user_id`, `place_id`, `name`, `owner_name`, `phone`, `email`, `address`, `website`, `has_website`, `api_calls`, `rating`, `ratings_total`, `price_level`, `opportunity_score`, `search_mode`, `location`, `industry`, `imported`, `lead_id`, `created_at`, `website_found_by_crawler`) VALUES
(508, 3, 'ChIJccCG2etZ4joRN67GVkHvGbc', 'Onella Salon & Academy', '', '077 376 7422', '', '113/2 Dutugemunu St, Colombo 10250, Sri Lanka', 'https://www.onellasalon.lk/', 1, 20, 4.7, 165, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:52', 0),
(509, 3, 'ChIJQZdxEF1Z4joR9wwFPFKdOj0', 'The Beauty Quest', '', '077 719 5978', 'support@beautyquest.com', 'Regency Wing, Galle Face Hotel, 2 Galle Rd, Colombo 00300, Sri Lanka', 'https://www.beautyquest.com/', 1, 21, 4.2, 227, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Beauty salon', 0, NULL, '2026-04-30 09:20:57', 1),
(510, 3, 'ChIJvb8tYOJY4joRhGHL939BY6g', 'ASIAN HARDWARE (PTE) LTD', '', '077 224 5245', 'sales@asianhardwarepteltd.com', '23 Maha Vidyalaya Mawatha, Colombo 01300, Sri Lanka', 'http://asianhardware.com/', 1, 2, 4.1, 1673, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:13', 0),
(511, 3, 'ChIJq4DzoR1Z4joRnwpRoP1-Mcs', 'Serendib Hardware Stores', '', '077 011 9282', '', '231 Old Moor St, Colombo, Sri Lanka', 'https://www.serendibhardware.com/', 1, 3, 4.7, 38, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:14', 0),
(512, 3, 'ChIJzTtaVwJZ4joR3C--n608FrE', 'St. Anthony\'s Hardware (Pvt) Ltd', '', '0115 225 200', '', '524 Sri Sangaraja Mawatha, Colombo 01000, Sri Lanka', 'http://www.stanthonys.lk/', 1, 4, 4.2, 199, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:19', 0),
(513, 3, 'ChIJzW1uIpdZ4joR0dtqXtX7I4A', 'MultI Tools & Hardware', '', '0112 334 023', '', '139 Justice Akbar Mawatha, Colombo 00200, Sri Lanka', 'https://mttoolss.com/', 1, 5, 4.6, 14, NULL, 21, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:19', 0),
(514, 3, 'ChIJfZB6rAJZ4joRXBPi5W08n0E', 'Moon Hardware Centre', '', '0112 345 873', '', '375A&B, Old Moor St, Colombo 01200, Sri Lanka', '', 0, 6, 4.4, 16, NULL, 71, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:20', 0),
(515, 3, 'ChIJT5Sghcpb4joRXKNlElf4dgA', 'Pamankada Hardware', '', '0112 502 582', '', '86 B Pamankada Rd, Colombo 00500, Sri Lanka', 'http://www.pamankadagroup.lk/', 1, 7, 4.0, 105, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:22', 0),
(516, 3, 'ChIJxY-YluJY4joRUavmtcMI18o', 'Modern Hardware Centre', '', '0112 435 468', 'kumar@modernhardware.lk', '43 Abdul Jabbar Mawatha, Colombo 00130, Sri Lanka', 'http://modernhardware.lk/', 1, 8, 4.3, 38, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:24', 0),
(517, 3, 'ChIJvVxKnAJZ4joRxSFM3Y8-GpU', 'Colonial Engineering (Pvt) Ltd.', '', '0112 472 639', 'coloneng@sltnet.lk', '138 Sri Sumanatissa Mawatha, Colombo 01000, Sri Lanka', 'http://www.colonialeng.com/', 0, 9, 4.2, 198, NULL, 85, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:26', 0),
(518, 3, 'ChIJUXNb-nBZ4joRkZfLFqnYxr0', 'Melban Hardware Pvt Ltd', '', '0112 330 331', '', '105 M. J. M. Lafeer Mawatha, Colombo 01200, Sri Lanka', 'https://www.facebook.com/melbnhardware', 0, 10, 4.8, 59, NULL, 77, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:29', 0),
(519, 3, 'ChIJtUcVUh1Z4joR2kV7ypwvSb8', 'Allied Trading International (Pvt) Ltd', '', '0112 325 719', 'alliedint@eureka.lk', '301 Old Moor St, Colombo, Sri Lanka', 'http://www.allied.lk/', 1, 11, 4.3, 100, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:32', 0),
(520, 3, 'ChIJ1SGnm_5Z4joRyJWvGTskGOc', 'Home Hardware Pvt Ltd', '', '075 618 1109', '', '246, 12 M. J. M. Lafeer Mawatha, Colombo 01200, Sri Lanka', '', 0, 12, 4.9, 21, NULL, 71, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:35', 0),
(521, 3, 'ChIJcV_PYYpZ4joRgUzEftqHjo8', 'M. M. Noorbhoy & Co (Pvt) Ltd', '', '077 382 8900', 'info@stagheaddesigns.com', '351 Nawala Rd, Sri Jayawardenepura Kotte 11222, Sri Lanka', 'https://noorbhoy.com/', 1, 13, 4.8, 3199, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:36', 0),
(522, 3, 'ChIJxa1VeARZ4joR085xPUVRaKk', 'A.C.Paul & Company Ltd.', '', '0114 691 100', 'info@acpaul.lk', '324-326 Sri Sangaraja Mawatha, Mawathaa 01000, Sri Lanka', 'https://acpauls.com/', 1, 14, 4.2, 956, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:38', 0),
(523, 3, 'ChIJvSqMDuJZ4joRcAAnrFB7L1I', 'Sena Hardware Stores', '', '072 559 8941', '', '529 Elvitigala Mawatha, Colombo 00500, Sri Lanka', '', 0, 15, 4.4, 25, NULL, 71, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:40', 0),
(524, 3, 'ChIJgxbpzWdZ4joRHfHffgOXX6Q', 'Kollupitiya Trade Centre', '', '0112 573 527', '', '109 St Anthony\'s Mawatha, Colombo 00300, Sri Lanka', '', 0, 16, 3.8, 73, NULL, 67, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:41', 0),
(525, 3, 'ChIJG8Ito99b4joR-o53B_Du2w0', 'Tokyo Hardware & Electrical​s', '', '0112 581 543', '', '702 B, 8th Lane, Galle Rd, Colombo 00300, Sri Lanka', 'https://www.daraz.lk/shop/tokyoh-electricals-hardware?spm=a2a0e.store_hp.top.share&dsource=share&laz_share_info=6844812_100_200_327212_6714891_null&laz_token=fe5ce45058c616effbf590753721bc1b', 1, 17, NULL, 40, NULL, 17, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:45', 0),
(526, 3, 'ChIJwUcSxx1Z4joRaxM4mbDNTv8', 'Kamsons Trading Company (Pvt) Ltd - Showroom', '', '0112 332 834', 'info@kamsonsgroup.lk', '235 Old Moor St, Colombo, Sri Lanka', 'http://www.kamsonsgroup.lk/', 1, 18, 4.7, 14, NULL, 21, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:47', 0),
(527, 3, 'ChIJrWuxEVtQ4joR9GmBOvsF34E', 'B N S Hardware Store', '', '077 712 1084', 'info@bnshardware.lk', 'No:393/3 Horana Rd, Pannipitiya 10230, Sri Lanka', 'http://bnshardware.lk/', 1, 19, 4.1, 126, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:49', 0),
(528, 3, 'ChIJ93zIaP1Y4joRlc3TnAyUzIs', 'Lucky Hardware', '', '0112 440 683', '', 'No.109, Sumanathissa Mawatha, Armour St, Colombo 01200, Sri Lanka', 'https://www.facebook.com/luckyhardwarecolombo/', 0, 20, 4.6, 53, NULL, 77, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:51', 0),
(529, 3, 'ChIJW5Q31bJZ4joROxIJrPd7MRw', 'National Hardwares', '', '0112 333 024', '', '415 Old Moor St, Colombo 01000, Sri Lanka', '', 0, 21, 4.8, 12, NULL, 71, 'all', 'Colombo, Sri Lanka', 'Hardware store', 0, NULL, '2026-04-30 09:21:54', 0),
(530, 3, 'ChIJUeiIT0xa4joRXdsuzOK_ZEY', 'Olanka Travels', '', '077 330 6306', 'hello@outlook.com', '87 Dutugemunu St, Dehiwala-Mount Lavinia, Sri Lanka', 'https://www.olankatravels.com/', 1, 2, 4.5, 1192, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:03', 0),
(531, 3, 'ChIJCzOeoCZZ4joRl2O8IRpxSQ8', 'Acorn Travels (Pvt) Ltd', '', '0114 704 704', 'inquiries.travels@acorn.lk', 'Hemas Building, 36 Sir Razik Fareed Mawatha, Colombo 00100, Sri Lanka', 'https://www.acorntravels.lk/', 1, 3, 4.5, 644, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:05', 0),
(532, 3, 'ChIJKW5uVmdZ4joRg5d2kk8e340', 'Classic Travel', '', '0117 773 300', 'info@classictravel.lk', '379/4 Galle Rd, Colombo 00300, Sri Lanka', 'http://www.classictravel.lk/', 1, 4, 4.4, 791, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:06', 0),
(533, 3, 'ChIJq5xkM8Nb4joRab8rFItqw3I', 'Arabiers Holidays Sri Lanka', '', '077 776 7672', '', '8 Flower Rd, Colombo 00700, Sri Lanka', 'https://www.arabiers.lk/', 1, 5, 5.0, 363, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:07', 0),
(534, 3, 'ChIJuzb7kbRZ4joRQUjBIbnnlfw', 'HAR Travels', '', '077 976 0470', 'info@hartravels.com', '182/6/2 Nazra complex, Prince Street, Colombo 01100, Sri Lanka', 'http://hartravels.com/', 1, 6, 4.9, 252, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:14', 0),
(535, 3, 'ChIJcXFczSVZ4joRRsgc6LxZcYM', 'Haim Travel - Sri Lanka', '', '0117 777 007', '', 'Metro Homes Residence, No: 40-1/2 Malay St, Colombo 00200, Sri Lanka', 'https://www.haimtravel.com/', 1, 7, 4.9, 526, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:19', 0),
(536, 3, 'ChIJa8n6iflZ4joRc5BmNe36kdM', 'Halo Holidays LK', '', '0112 106 390', 'contact@haloholidays.lk', 'Deal Pl - A, Colombo 00300, Sri Lanka', 'https://www.haloholidays.lk/', 1, 8, 4.8, 202, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:20', 0),
(537, 3, 'ChIJqWCpgd5b4joRsP4l6j69wO4', 'Detroves Travels (Pvt) Ltd', '', '0114 373 239', 'your@email.com', 'No. 31/1 Ridgeway Pl, Colombo 00400, Sri Lanka', 'http://www.detrovestravels.com/', 1, 9, 5.0, 183, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:23', 0),
(538, 3, 'ChIJpUKIbEpP4joRKRtgS1vnd-U', 'Lanka Travels', '', '076 358 2526', 'info@lankatravels.lk', '2nd floor, 28 Muhandiram\'s Rd, Colombo 00300, Sri Lanka', 'https://lankatravels.lk/', 1, 10, 4.9, 203, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:26', 0),
(539, 3, 'ChIJ81VbT2lZ4joRFGUCnkvvzXE', 'Mackinnons Travels', '', '0117 991 000', '', '186 Vauxhall St, Colombo 00200, Sri Lanka', 'https://www.mackinnonstravels.com/?utm_source=organic&utm_medium=gbp&utm_campaign=mtl', 1, 11, 4.5, 333, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:30', 0),
(540, 3, 'ChIJkVESg11Z4joR03QDnQd_Mcg', 'Travelco Holidays', '', '077 700 9226', 'inquiry@travelco.lk', '333 2/2 Galle Rd, Colombo 00300, Sri Lanka', 'https://travelco.lk/', 1, 12, 4.5, 587, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:30', 0),
(541, 3, 'ChIJYeF7MhRZ4joRot4Spx0obIE', 'Navigeto Travels', '', '075 331 0101', 'info@navigeto.lk', '420, 2/1 Elvitigala Mawatha, Colombo 10500, Sri Lanka', 'https://www.navigeto.lk/', 1, 13, 4.9, 195, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:39', 0),
(542, 3, 'ChIJF60PiP9Z4joRjZR5K63aAJY', 'Travel with sampath (PVT)LTD', '', '077 209 7422', 'travelwithsampaths@gmail.com', '412 Nawala Rd, Colombo, Sri Lanka', 'https://travelwithsampath.com/', 1, 14, 5.0, 282, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:42', 0),
(543, 3, 'ChIJYcoMhHFZ4joRROIcHe2xS_I', 'Travelco Holidays - Corporate Office', '', '0117 699 729', 'inquiry@travelco.lk', '118C Barnes Pl, Colombo 00700, Sri Lanka', 'https://travelco.lk/', 1, 15, 4.9, 152, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:43', 0),
(544, 3, 'ChIJw8KuMSRZ4joRlapT0Kv5daw', 'tgl.travel', '', '077 337 4074', 'info@tgl.travel', '90 Chatham St, Colombo 00100, Sri Lanka', 'https://tgl.travel/', 1, 16, 4.6, 134, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:44', 0),
(545, 3, 'ChIJtapq4gpZ4joR_LmODWNuXhk', '2nd Chance Travels (Pvt) Ltd', '', '0112 552 055', 'info@2ndchancetravels.com', '25 Edward Ln, Colombo 00300, Sri Lanka', 'https://2ndchance.travel/', 1, 17, 4.7, 365, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:46', 0),
(546, 3, 'ChIJo26EAGFZ4joR9v0YtYYc_dU', 'Gabo Travels', '', '0114 524 700', 'info@gabos.com', '11 Bagatalle Rd, Colombo 00300, Sri Lanka', 'http://www.gabos.com/', 1, 18, 4.3, 291, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Travel agency', 0, NULL, '2026-04-30 09:22:48', 0),
(547, 3, 'ChIJ9_ZJitIVK4gRq1IJliuuI38', 'Fortinos Brampton Quarry Edge', '', '(905) 453-3600', '', '60 Quarry Edge Dr, Brampton, ON L6V 4K2, Canada', 'https://www.fortinos.ca/store-locator/details/5548?utm_source=G&utm_medium=LPM&utm_campaign=Loblaws', 1, 2, 4.4, 4009, NULL, 35, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:24:59', 0),
(548, 3, 'ChIJAQAAkOY9K4gR6OWTK05A8F4', 'Metro', '', '(905) 793-4828', '', '25 Peel Centre Dr, Brampton, ON L6T 3R5, Canada', 'https://www.metro.ca/en/find-a-grocery/322?utm_source=G&utm_medium=local&utm_campaign=google-local', 1, 3, 4.2, 3149, NULL, 35, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:01', 0),
(549, 3, 'ChIJr2hiJdE_K4gR8AAGxLnhw3M', 'Adil\'s NOFRILLS Brampton', '', '(866) 987-6453', '', '85 Steeles Ave W, Brampton, ON L6Y 0B5, Canada', 'https://www.nofrills.ca/store-locator/details/7518?utm_source=G&utm_medium=LPM&utm_campaign=Loblaws', 1, 4, 4.2, 8177, NULL, 35, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:03', 0),
(550, 3, 'ChIJbYA4XAE-K4gR5XVRTUuFeFE', 'Oceans Fresh Food Market • Brampton Store', '', '(905) 455-6166', '', '150 West Dr #104, Brampton, ON L6T 4P9, Canada', 'http://oceansfood.ca/', 1, 5, 3.9, 3105, NULL, 25, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:06', 0),
(551, 3, 'ChIJX-9jrcU_K4gR5c5JR_upu7M', 'Longo\'s Brampton', '', '(905) 455-3135', '', '7700 Hurontario St, Brampton, ON L6Y 4M3, Canada', 'http://www.longos.com/', 1, 6, 4.4, 1205, NULL, 35, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:07', 0),
(552, 3, 'ChIJmRdNK-Y9K4gREnc8sD-CG3U', 'FreshCo Bramalea City Centre', '', '(905) 793-4867', '', '12 Team Canada Dr, Brampton, ON L6T 0C9, Canada', 'https://freshco.com/stores/freshco-bramalea-city-centr/?utm_source=G&utm_medium=lpm&utm_campaign=Sobeys', 1, 7, 4.1, 2543, NULL, 35, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:09', 0),
(553, 3, 'ChIJozrCirgVK4gRNHDJdlHGA7M', 'Farzin\'s NOFRILLS Brampton', '', '(866) 987-6453', '', '345 Main St N, Brampton, ON L6X 1N6, Canada', 'https://www.nofrills.ca/store-locator/details/3673?utm_source=G&utm_medium=LPM&utm_campaign=Loblaws', 1, 8, 4.0, 3826, NULL, 35, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:12', 0),
(554, 3, 'ChIJmfXIO2MUK4gRfDhm47m0jJs', 'Fortinos Brampton Worthington', '', '(905) 495-8108', '', '35 Worthington Ave, Brampton, ON L7A 2Y7, Canada', 'https://www.fortinos.ca/store-locator/details/7529?utm_source=G&utm_medium=LPM&utm_campaign=Loblaws', 1, 9, 4.4, 4951, NULL, 35, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:15', 0),
(555, 3, 'ChIJoQvNe4MVK4gRgp5exCtxV5E', 'Metro', '', '(905) 459-6212', '', '156 Main St S, Brampton, ON L6W 2E1, Canada', 'https://www.metro.ca/en/find-a-grocery/311?utm_source=G&utm_medium=local&utm_campaign=google-local', 1, 10, 4.1, 1162, NULL, 35, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:16', 0),
(556, 3, 'ChIJe_iCVwI-K4gRiKe0CQ9bWxw', 'Sarangan\'s NOFRILLS Brampton', '', '(866) 987-6453', '', '295 Queen St E, Brampton, ON L6W 3R1, Canada', 'https://www.nofrills.ca/store-locator/details/7502?utm_source=G&utm_medium=LPM&utm_campaign=Loblaws', 1, 11, 4.1, 3649, NULL, 35, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:20', 0),
(557, 3, 'ChIJbUd7zskXK4gRGeXsGwzgaWM', 'Chalo FreshCo Bramalea & Sandalwood', '', '(905) 793-0558', '', '10615 Bramalea Rd, Brampton, ON L6R 3P4, Canada', '', 0, 12, 4.0, 4113, NULL, 85, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:22', 0),
(558, 3, 'ChIJIboVnlk7K4gRwdzQKvv3BhU', 'Healthy Planet - Brampton', '', '(905) 457-6565', '', '150 West Dr Unit 18, Brampton, ON L6T 4P9, Canada', 'https://www.healthyplanetcanada.com/storelocator/brampton/', 1, 13, 4.6, 1577, NULL, 35, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:22', 0),
(559, 3, 'ChIJZeZTgEQUK4gR7kXVaO3KmLI', 'Ample Food Market • Brampton Store', '', '(905) 455-3575', 'amplefood888@gmail.com', '235 Fletchers Creek Blvd, Brampton, ON L6X 0Y7, Canada', 'http://www.amplefood.ca/', 1, 14, 3.7, 2417, NULL, 25, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:25', 0),
(560, 3, 'ChIJycnMnOgVK4gR9fpYywlIRI4', 'Food Basics', '', '(905) 451-7842', '', '227 Vodden St E, Brampton, ON L6V 3C9, Canada', 'http://www.foodbasics.ca/?utm_source=G&utm_medium=local&utm_campaign=google-local', 1, 15, 4.0, 1432, NULL, 35, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:26', 0),
(561, 3, 'ChIJIVTHc3MUK4gReCtx62EAnrc', 'FreshCo Chinguacousy & Sandalwood', '', '(905) 495-4951', '', '10651 Chinguacousy Rd, Brampton, ON L7A 0N5, Canada', 'https://www.freshco.com/stores/freshco-chinguacousy-sand?utm_source=G&utm_medium=lpm&utm_campaign=Sobeys', 1, 16, 4.1, 2569, NULL, 35, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:27', 0),
(562, 3, 'ChIJZ__vDG8UK4gRqdez4wMOC0E', 'Indian Frootland', '', '(905) 846-5800', '', '15 Fandor Way, Brampton, ON L7A 4S2, Canada', 'https://indianfrootland.com/', 1, 17, 3.7, 2027, NULL, 25, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:27', 0),
(563, 3, 'ChIJJVCVpUEWK4gRQLDNv816NOM', 'Metro', '', '(905) 789-6161', '', '20 Great Lakes Dr, Brampton, ON L6R 2K7, Canada', 'https://www.metro.ca/en/find-a-grocery/222?utm_source=G&utm_medium=local&utm_campaign=google-local', 1, 18, 4.2, 2361, NULL, 35, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:28', 0),
(564, 3, 'ChIJn14UCT4VK4gRrxHgxZI55Ak', 'Indian Frootland', '', '(905) 456-9900', '', '45 Dusk Dr, Brampton, ON L6Y 5Z6, Canada', 'https://indianfrootland.com/', 1, 19, 3.8, 2735, NULL, 25, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:28', 0),
(565, 3, 'ChIJ8f74k4cWK4gRREvmSo2gucI', 'India Bazaar', '', '(905) 840-1116', 'indiabazaarbrampton@gmail.com', '10405 Kennedy Rd N, Brampton, ON L6Z 3X6, Canada', 'https://indiabazaar.ca/', 1, 20, 3.9, 2554, NULL, 25, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:29', 0),
(566, 3, 'ChIJj1ZaZsI9K4gR0-eDpRGxqKk', 'Rabba Fine Foods', '', '(905) 790-1685', 'sm@rabba.com', '17 Kings Cross Rd, Brampton, ON L6T 3V5, Canada', 'http://www.rabba.com/', 1, 21, 3.7, 471, NULL, 25, 'all', 'Brampton, Canada', 'Grocery store', 0, NULL, '2026-04-30 09:25:34', 0),
(567, 3, 'ChIJDasOwLc1K4gRVZT2MbdnfZY', 'Blair\'s Catering', '', '(905) 793-4405', 'info@blairscatering.com', '71 Rosedale Ave W #4, Brampton, ON L6X 1K4, Canada', 'http://www.blairscatering.com/', 0, 2, 5.0, 35, NULL, 77, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:26:09', 0),
(568, 3, 'ChIJh-ZQrUA-K4gRxrFjq6RIKIM', 'Catering By Gregory\'s', '', '(905) 454-8738', 'catering@gregorys.ca', '20 Bram Ct #4, Brampton, ON L6W 3R6, Canada', 'http://www.gregorys.ca/', 1, 3, 4.8, 105, NULL, 35, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:26:17', 0),
(569, 3, 'ChIJuaQYXiEUK4gRjvhSb51uESQ', 'TK\'s Catering', '', '(905) 846-1982', 'tkscatering@tkscatering.com', '27 Fisherman Dr Unit 2, Brampton, ON L7A 1E2, Canada', 'http://www.tkscatering.com/', 0, 4, 4.8, 70, NULL, 77, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:26:24', 0),
(570, 3, 'ChIJcYs9d6MVK4gRQRwPuaQK8v4', 'Feast Your Eyes Inc.', '', '', 'info@feastyoureyes.ca', '23 McMurchy Ave N, Brampton, ON L6X 1X4, Canada', 'http://feastathome.ca/', 1, 5, 4.8, 64, NULL, 22, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:26:26', 0),
(571, 3, 'ChIJccZmjHg_K4gR9pmEbTR5-mc', 'NS Catering', '', '(416) 770-0892', '', '2080 Steeles Ave E Unit #18, Brampton, ON L6T 1A7, Canada', '', 0, 6, 5.0, 140, NULL, 85, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:26:31', 0),
(572, 3, 'ChIJf-Ba0SIVK4gRRQs7HGwO4zQ', 'AB Foods and Catering - Tiffin Service Brampton | Event Catering', '', '(905) 497-3455', '', '105 Kennedy Rd S Unit # 1, Brampton, ON L6W 3G2, Canada', 'https://order.online/store/-23916024/?pickup=true&hideModal=true&utm_source=gfo', 1, 7, 3.9, 535, NULL, 25, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:26:34', 0),
(573, 3, 'ChIJJTwoAmYVK4gRq4l2_Om1Arg', 'Anmol Zaiqa', '', '(905) 495-7186', '', '15 Lathbury St, Brampton, ON L7A 0R6, Canada', 'https://www.facebook.com/anmolzaiqa', 0, 8, 4.9, 75, NULL, 77, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:26:37', 0),
(574, 3, 'ChIJuToZdrs_K4gRJSEFQJQRGxM', 'Galaxy Catering', '', '(647) 390-2584', 'info@mysite.com', '200 Advance Blvd, Brampton, ON L6T 4V4, Canada', 'https://galaxycatering.ca/', 1, 9, 5.0, 17, NULL, 21, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:26:39', 0),
(575, 3, 'ChIJh-9u0Q4-K4gRrxRi77lmdgY', 'Shriji Catering And Takeout', '', '(905) 451-4050', 'dipen@shrijicatering.ca', '71 West Dr unit 34, Brampton, ON L6T 3T6, Canada', 'http://www.shrijicatering.ca/', 1, 10, 4.2, 2160, 1, 35, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:26:41', 0),
(576, 3, 'ChIJdakMgqMVK4gRBzRMH0ZEk4Y', 'Pig Roast Catering', '', '(416) 938-4853', 'paula@feastyoureyes.ca', '23 McMurchy Ave N, Brampton, ON L6X 1X4, Canada', 'http://pigroastcatering.ca/', 1, 11, 4.3, 11, NULL, 21, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:26:46', 0),
(577, 3, 'ChIJmw2izH0WK4gRAbemZQcvwTE', 'Patto sweets & catering south 105 kennedy # 8', '', '(647) 938-7316', 'harindersinghh@yahoo.in', '105 Kennedy Rd S, Brampton, ON L6W 3G2, Canada', 'http://pattocateringandsweets.com/', 1, 12, 4.7, 83, NULL, 27, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:26:46', 0),
(578, 3, 'ChIJw6bxp55BK4gRYa80Pw_oOSo', 'Healthy and Homemade(Tiffin service/Catering Indian food)', '', '(647) 607-3961', 'healthihomemade@gmail.com', '4654 Drakestone Crescent, Mississauga, ON L5R 1K6, Canada', 'http://www.healthihomemade.com/', 1, 13, 4.8, 351, NULL, 35, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:26:50', 0),
(579, 3, 'ChIJJUsUELYVK4gR8qRTHVePyYo', 'Tiffin Pros - Veg. Tiffin Service Brampton | Catering', '', '(416) 301-2187', 'tiffinprosfood@gmail.com', '965 Bovaird Dr W #13, Brampton, ON L6X 0G3, Canada', 'http://tiffinpros.ca/', 1, 14, 4.4, 325, NULL, 35, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:26:52', 0),
(580, 3, 'ChIJnfja3c0_K4gRKH5A79w6yDk', 'Food Depot Restaurant & Catering Services inc', '', '(905) 796-1745', '', '55 Rutherford Rd S, Brampton, ON L6W 3J4, Canada', '', 0, 15, 4.3, 129, NULL, 85, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:26:54', 0),
(581, 3, 'ChIJ28jWrrsVK4gRDOhfitw6BAQ', 'JDS CATERING INC.', '', '(289) 201-2236', 'service@jdscatering.ca', '85 Rosedale Ave W, Brampton, ON L6X 4H5, Canada', 'http://www.jdscatering.ca/', 1, 16, 4.6, 9, NULL, 15, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:26:57', 0),
(582, 3, 'ChIJwU7c70Q_K4gRDg95KRpnj6s', 'Singh Tiffin & Catering', '', '(905) 414-4100', '', '118 Orenda Rd unit Number 1, Brampton, ON L6W 3W6, Canada', 'https://singhtiffin.ca/', 1, 17, 3.7, 59, NULL, 17, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:27:02', 0),
(583, 3, 'ChIJ5firyng-K4gRMLBl-cGBOWE', 'Blairs Catering Incorporated', '', '(416) 575-7606', 'info@blairscatering.com', '45 Avondale Blvd, Brampton, ON L6T 1H1, Canada', 'http://www.blairscatering.com/', 0, 18, 5.0, 2, NULL, 65, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:27:03', 0),
(584, 3, 'ChIJ9Qyp3NMVK4gRldqymhbSGL4', '7 Spice Bistro – Authentic Indian Restaurant | Hakka Cuisine | Catering service | Food Truck Catering Brampton', '', '(905) 456-7878', 'info@7spicebistro.com', '30 Gillingham Dr, Brampton, ON L6X 4P8, Canada', 'https://7spicebistro.com/', 1, 19, 4.6, 3116, NULL, 35, 'all', 'Brampton, Canada', 'Catering service', 0, NULL, '2026-04-30 09:27:06', 0),
(585, 3, 'ChIJH6tPCw1Z4joRL0tsamQwS4M', 'Platinum Logistics Colombo Pvt Ltd', '', '0112 302 220', '', 'No 217 1, 5 Dr NM Perera Mawatha Rd, Colombo 00800, Sri Lanka', 'http://www.platinumlogisticscmb.com/', 1, 2, 4.9, 148, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:27:20', 0),
(586, 3, 'ChIJPcNcQwNZ4joRoqabXA3V8yA', 'MSK Logistics (Pvt) Ltd', '', '077 866 5589', 'info@msklog.com', '78 Lotus Rd, Colombo 00100, Sri Lanka', 'https://msklog.com/', 0, 3, 5.0, 802, NULL, 85, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:27:21', 0),
(587, 3, 'ChIJbwhcl21Z4joRd67SGyd5eTQ', 'Eagle Logistics Colombo (Pvt) Ltd', '', '0112 577 892', 'info@eaglelogisticscmb.com', 'No. 281-1, 1 R. A. De Mel Mawatha, Colombo 00300, Sri Lanka', 'http://www.eaglelogisticscmb.com/', 1, 4, 4.3, 70, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:27:23', 0),
(588, 3, 'ChIJmXfkqG1b4joRpFoyH2g61lk', 'Colombo Logistics World (Pvt) Ltd', '', '0112 662 050', 'info@cmblog.lk', 'No. 63/1 Ward Pl, Colombo 00700, Sri Lanka', 'https://www.colombologistics.com/', 1, 5, 4.5, 38, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:27:26', 0),
(589, 3, 'ChIJEZH6BUJZ4joRj48dke0FNCo', 'Lanka Shipping & Logistics (Pvt) Ltd', '', '0114 681 700', 'info@lankaship.lk', 'Lanka Shipping Tower, 40 Hudson Rd, Colombo 00300, Sri Lanka', 'http://www.lankaship.lk/', 1, 6, 4.1, 57, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:27:29', 0),
(590, 3, 'ChIJCwFgBW9Z4joRleZq2W5Jl7I', 'ASL Logistics CMB (Pvt) Ltd - Sri Lanka', '', '0112 317 517', '', '131 W A D Ramanayake Mawatha, Colombo 00700, Sri Lanka', '', 0, 7, 5.0, 14, NULL, 71, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:27:30', 0),
(591, 3, 'ChIJvRjOQgpZ4joRV73nQT_v16E', 'Globactiv Logistics (Pvt) Ltd', '', '076 892 7927', 'info@globactiv.lk', 'No 46/38, Forbes and Walker Building , Level 4, Nawam mawatha, Mawatha, Colombo 02, Sri Lanka', 'http://www.globactiv.lk/', 1, 8, 4.6, 35, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:27:32', 0),
(592, 3, 'ChIJ1QJ542VZ4joR4SM31EIrkgc', 'Worldwide Logistics Lanka (Pvt) Ltd', '', '0117 870 000', 'info@worldwide-lanka.com', '116, 10 Rosmead Pl, Colombo 00700, Sri Lanka', 'https://www.worldwide-lanka.com/', 1, 9, 3.9, 18, NULL, 11, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:27:34', 0),
(593, 3, 'ChIJEeWwOe9Y4joRFYhj8N-Fn6M', 'Dart Global Logistics (Pvt) Ltd', '', '0114 609 600', 'info@dartglobal.com', '260 Sri Ramanathan Mawatha, Colombo 00015, Sri Lanka', 'https://www.dartglobal.com/', 1, 10, 4.2, 99, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:27:36', 0),
(594, 3, 'ChIJtRBtVtxb4joRe6pQ3Y8nD3g', 'Lanka Shipping & Global Logistics (Pvt) Ltd', '', '0117 325 325', '', '15/2 C, 15/2 C Joseph\'s Ln, Colombo 00400, Sri Lanka', 'http://www.lankashipping.lk/', 1, 11, 4.7, 6, NULL, 15, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:27:38', 0),
(595, 3, 'ChIJDQzEMmBZ4joR3vAnCc6YfqY', 'DBS Logistics Limited', '', '0117 557 000', '', '10 Alfred House Gardens, Colombo 00300, Sri Lanka', '', 0, 12, 4.7, 23, NULL, 71, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:27:39', 0),
(596, 3, 'ChIJl34nzA1Z4joRaaPETMJb_JU', 'United Logistics Colombo', '', '0117 817 818', '', '281/15 Deans Road, Colombo 01000, Sri Lanka', '', 0, 13, 4.1, 21, NULL, 71, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:27:42', 0),
(597, 3, 'ChIJFRa7di1a4joRE84mDPG6s3U', 'CL Synergy Limited', '', '0115 300 250', 'info@clsynergy.com', '30 Sri Uttarananda Mawatha, Colombo 00300, Sri Lanka', 'http://www.clsynergy.com/', 1, 14, 4.4, 62, NULL, 27, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:27:50', 0),
(598, 3, 'ChIJV2Kt7vBY4joRgd29-yNrQEU', 'Trico Logistics Ltd', '', '076 690 3958', '', '478/6 K. Cyril C. Perera Mawatha, Colombo 01300, Sri Lanka', 'http://www.tricologi.net/', 1, 15, 3.9, 211, NULL, 25, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:27:54', 0),
(599, 3, 'ChIJvxc1ZqVZ4joRcMjUHyhbt10', 'Kerry Logistics Lanka (Pvt) Ltd', '', '0112 104 060', '', '5th Floor, 77 Park St, Colombo 00200, Sri Lanka', '', 0, 16, 4.4, 8, NULL, 65, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:27:55', 0),
(600, 3, 'ChIJHX4cehVZ4joRB5R9T5t_lQY', 'Hellmann Worldwide Logistics', '', '0112 316 700', '', '50/25 A, Sir James Pieris Mawatha, Colombo 02200, Sri Lanka', 'http://www.hellmann.com/', 1, 17, 4.5, 24, NULL, 21, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:28:01', 0),
(601, 3, 'ChIJibooV01Y4joRAOSUQhvID-M', 'EFL 3PL Global - Sri Lanka', '', '0114 791 000', 'lka-hearu@efl.global', '390 Avissawella Rd, Colombo, Sri Lanka', 'http://www.efl3pl.global/', 1, 18, 4.9, 179, NULL, 35, 'all', 'Colombo, Sri Lanka', 'Logistics company', 0, NULL, '2026-04-30 09:28:03', 0),
(602, 3, 'ChIJ00c4RcrDeUgRvBUMDujMc50', 'Castle Gym', '', '0115 871 1826', 'hello@castlegym.co.uk', '34 Maid Marian Way, Nottingham NG1 6GF, UK', 'https://www.castlegym.co.uk/', 1, 2, 5.0, 242, NULL, 35, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:29:10', 0),
(603, 3, 'ChIJW4PL9H_BeUgRXSPZ3WkJRFY', 'The Gym Group Nottingham City', '', '0300 303 4800', '', '14 Trinity Sq, Nottingham NG1 4AF, UK', 'https://www.thegymgroup.com/find-a-gym/nottingham-gyms/nottingham-city/?utm_source=google&utm_medium=organic&utm_campaign=gmb-listing&utm_content=Nottingham%20City', 1, 3, 4.3, 937, NULL, 35, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:29:16', 0),
(604, 3, 'ChIJdXtvmdXDeUgR877Bo28YMYU', 'Formula One Gym', '', '0115 950 5009', 'enquiries@formulaonegym.co.uk', '21 Victoria St, Nottingham NG1 2EW, UK', 'https://www.formulaonegym.co.uk/', 1, 4, 4.5, 313, NULL, 35, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:29:19', 0),
(605, 3, 'ChIJtXVBbuXBeUgRO9WswORLRFg', 'David Lloyd Nottingham', '', '0115 900 7000', '', 'Aspley Ln, Nottingham NG8 5AR, UK', 'https://www.davidlloyd.co.uk/clubs/nottingham/?utm_source=google&utm_medium=places&utm_campaign=LPM_nottingham%2F&y_source=1_OTEzMzc3ODQtNzE1LWxvY2F0aW9uLndlYnNpdGU%3D', 1, 5, 4.5, 1239, NULL, 35, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:29:19', 0),
(606, 3, 'ChIJO7RUPVHoeUgRHk8sSKZLthE', 'Village Gym Nottingham', '', '0115 896 5065', 'memberservices@village-hotels.com', 'Brailsford Way, Beeston, Nottingham NG9 6DL, UK', 'https://www.villagegym.co.uk/locations/nottingham/?utm_source=Google&utm_medium=organic&utm_campaign=nottingham', 1, 6, 4.5, 342, NULL, 35, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:29:21', 0),
(607, 3, 'ChIJ6X8F1ojBeUgRk-hc3b8-PUs', 'JD Gyms Nottingham', '', '0115 822 2290', '', 'Chettles Trade Park, Midland Way, Nottingham NG7 3AG, UK', 'https://www.jdgyms.co.uk/gym/nottingham/', 1, 7, 4.0, 276, NULL, 35, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:29:23', 0),
(608, 3, 'ChIJU43YDs_DeUgRVM7y_TF1gq0', 'Nottingham Strong', '', '0115 646 1264', 'train@nottinghamstrong.com', '42 Church St, Lenton, Nottingham NG7 2FH, UK', 'http://www.nottinghamstrong.com/', 0, 8, 4.9, 162, NULL, 85, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:29:26', 0),
(609, 3, 'ChIJVweKKQ_BeUgRN65gZzZXXs0', 'The Gym Group Nottingham Sherwood', '', '0300 303 4800', '', '684 Mansfield Rd, Sherwood, Nottingham NG5 2GE, UK', 'https://www.thegymgroup.com/find-a-gym/nottingham-gyms/nottingham-sherwood/?utm_source=google&utm_medium=organic&utm_campaign=gmb-listing&utm_content=Nottingham%20Sherwood', 1, 9, 4.6, 786, NULL, 35, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:29:31', 0),
(610, 3, 'ChIJoyqK2tDDeUgR7KWWXr3tuLk', 'Virgin Active', '', '020 8167 6480', '', 'London Rd, Nottingham NG2 3AE, UK', 'https://www.virginactive.co.uk/clubs/nottingham?utm_source=google&utm_medium=local&utm_campaign=local', 1, 10, 4.1, 508, NULL, 35, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:29:34', 0),
(611, 3, 'ChIJ0aw_RTrBeUgRhCRAuNTsF9k', 'The Gym Group Nottingham Radford', '', '0300 303 4800', '', 'castle retail park, 2 Radford Blvd, Nottingham NG7 5QR, UK', 'https://www.thegymgroup.com/find-a-gym/nottingham-gyms/nottingham-radford/?utm_source=google&utm_medium=organic&utm_campaign=gmb-listing&utm_content=Nottingham%20Radford', 1, 11, 4.8, 659, NULL, 35, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:29:39', 0),
(612, 3, 'ChIJuVvA3tfDeUgROdjQ9ulfO_c', 'Liberty Gym', '', '0115 958 2590', '', 'Manvers St, Sneinton, Nottingham NG2 4PP, UK', 'https://www.facebook.com/libertygymnottingham/', 0, 12, 4.8, 129, NULL, 85, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:29:43', 0),
(613, 3, 'ChIJsfMTx2LBeUgRJx0Z0abFV-8', 'Winners Gym Ltd', '', '07852 992804', '', 'Roden House, Roden St, Mapperley, Nottingham NG3 1JH, UK', 'https://www.facebook.com/WinnersGymLtd', 0, 13, 4.8, 74, NULL, 77, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:29:46', 0),
(614, 3, 'ChIJ4Vjll6DBeUgR_HFjlDpqWi4', 'Kinetic Studios', '', '07929 577475', '', '37 Haydn Rd, Sherwood, Nottingham NG5 2LA, UK', 'http://www.kineticstudios.co.uk/', 1, 14, 4.9, 53, NULL, 27, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:29:57', 0),
(615, 3, 'ChIJs_i8JyXBeUgRcedT7Vd76sQ', 'Nuffield Health Nottingham Fitness & Wellbeing Gym', '', '0115 822 0306', '', 'Plains Road, Mapperley, Nottingham NG3 5RH, UK', 'https://www.nuffieldhealth.com/gyms/nottingham?utm_source=google&utm_medium=local&utm_campaign=GoogleLocal-Nottingham', 1, 15, 4.0, 141, NULL, 35, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:29:59', 0),
(616, 3, 'ChIJOePhEl_DeUgRXOyUQdHutyY', 'PureGym Nottingham Castle Marina', '', '', 'info.nottinghamcastlemarina@puregym.com', '20 Castle Bridge Rd, Nottingham NG7 1GX, UK', 'https://www.puregym.com/gyms/nottingham-castle-marina/?utm_source=local&utm_campaign=local_search-nottingham-castle-marina-&utm_medium=organic', 1, 16, 4.1, 341, NULL, 30, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:30:00', 0),
(617, 3, 'ChIJqUu8VuvDeUgR_A5pJLDH3m4', 'PureGym Nottingham West Bridgford', '', '', 'info.nottinghamwestbridgford@puregym.com', 'Wilford Ln, West Bridgford, Nottingham NG2 7QY, UK', 'https://www.puregym.com/gyms/nottingham-west-bridgford/?utm_source=local&utm_campaign=local_search-nottingham-west-bridgford-&utm_medium=organic', 1, 17, 4.3, 223, NULL, 30, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:30:00', 0),
(618, 3, 'ChIJ6z-Y_kHFeUgR9xK5hdkLxQw', 'Paradigm Gym', '', '07817 843126', 'paradigmnotts@gmail.com', 'Unit 1, Chris Allsop Industrial park, Colwick, Nottingham NG4 2JR, UK', 'https://paradigmgym.co.uk/', 1, 18, 4.9, 65, NULL, 27, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:30:02', 0),
(619, 3, 'ChIJ_4dhdr3BeUgRgcZDaLugMTs', 'PureGym Nottingham Basford', '', '', 'info.nottingham@puregym.com', 'Sovereign House, 184 Nottingham Rd, Nottingham NG7 7BA, UK', 'https://www.puregym.com/gyms/nottingham-basford/?utm_source=local&utm_campaign=local_search-nottingham-basford-&utm_medium=organic', 1, 19, 4.1, 829, NULL, 30, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:30:02', 0),
(620, 3, 'ChIJFTev4sXBeUgRbTEviBcN0co', 'Fitness 4 Women - Female Only Gym Nottingham', '', '0115 919 1346', 'info@fitness4womennottingham.com', '1st Floor, Family Mall Market, St Ann\'s Well Rd, Nottingham NG3 3HR, UK', 'https://www.fitness4womennottingham.com/', 1, 20, 4.2, 46, NULL, 27, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:30:08', 0),
(621, 3, 'ChIJjxHt4NfDeUgRyW9x-WV1QMg', 'Victoria Leisure Centre', '', '0115 876 1600', '', 'Gedling St, Nottingham NG1 1DB, UK', 'https://www.activenottingham.com/centres/victoria-leisure-centre/', 1, 21, 4.2, 447, NULL, 35, 'all', 'Nottingham, UK', 'Gym', 0, NULL, '2026-04-30 09:30:14', 0),
(622, 3, 'ChIJF6ckp-VbXz4R2jTAVBBN2fc', 'Carrefour', '', '800 73232', '', 'Sharjah City Centre - شارع الكورنيش - الصناعيات - المنطقة الصناعية - الشارقة - United Arab Emirates', 'https://www.carrefouruae.com/?utm_source=GMBlisting&utm_medium=Organic&utm_campaign=carrefour-uae-gmb', 1, 2, 4.4, 11694, NULL, 35, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:18', 0),
(623, 3, 'ChIJt_MvFWNfXz4RpuiQF2XxPCY', 'Safari Hypermarket Sharjah', '', '06 506 6888', '', 'Sheikh Khalifa Bin Zayed Al Nahyan Rd - Muwaileh Commercial - Industrial Area - Sharjah - United Arab Emirates', 'https://safarihypermarket.ae/', 1, 3, 4.3, 9934, NULL, 35, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:23', 0),
(624, 3, 'ChIJrd2YhYlZXz4R4OC49FiHDpk', 'Nesto Hypermarket', '', '06 714 9999', 'ro.india@nestogroup.com', 'nearby Zulekha Hospital - Bu Tena - Sharjah - United Arab Emirates', 'https://nestogroup.com/?utm_source=Google&utm_medium=GBP&utm_campaign=Bu-Tina-Sharjah&utm_content=Nesto', 1, 4, 4.2, 10835, NULL, 35, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:25', 0),
(625, 3, 'ChIJ--fibdtZXz4Rwwp-QZ-UNwU', 'Lulu Hypermarket - Maysaloon', '', '06 561 2222', 'customercare@luluhypermarket.com', 'Al Kuwait St - near Lulu Center - Maysaloon - Sharjah - United Arab Emirates', 'https://www.luluhypermarket.com/en-ae', 1, 5, 4.0, 4053, NULL, 35, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:25', 0),
(626, 3, 'ChIJI6KUlXRdXz4RJeseKuGFbvI', 'Nesto Hypermarket - Al Majaz', '', '050 697 2195', 'ro.india@nestogroup.com', 'Al Khan St - Al Majaz District - Al Majaz - Sharjah - United Arab Emirates', 'https://nestogroup.com/?utm_source=Google&utm_medium=GBP&utm_campaign=Al-Majaz-Sharjah&utm_content=Nesto', 1, 6, 4.2, 2258, NULL, 35, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:27', 0),
(627, 3, 'ChIJ9ziNxqVeXz4R5iWGfyAvFr0', 'Al Mubarak Hypermarket L.L.C sharjah', '', '055 807 4545', '', '8C56+6F3 - Third Industrial St - Industrial Area 3 - Industrial Area - Sharjah - United Arab Emirates', 'https://neartail.com/ae/mubaraksharjah', 1, 7, 4.2, 1283, NULL, 35, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:29', 0),
(628, 3, 'ChIJbed66fdZXz4RiHtrFLvw2wE', 'Lulu Hypermarket - Sharjah Central', '', '06 555 7744', 'customercare@luluhypermarket.com', '376 Sheikh Rashid Bin Saqr Al Qasimi St - Samnan - Halwan - Sharjah - United Arab Emirates', 'https://www.luluhypermarket.com/en-ae', 1, 8, 4.4, 2496, NULL, 35, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:29', 0),
(629, 3, 'ChIJOZEFvVJZXz4Rx-cjfXavl94', 'Macro Emirates', '', '06 542 1334', '', 'Second Industrial Street,Industrial Area 6 - Industrial Areas - Industrial Area - Sharjah - United Arab Emirates', '', 0, 9, 4.0, 6361, NULL, 85, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:31', 0),
(630, 3, 'ChIJ9ft3wP1dXz4RIN-vDgPThqI', 'Night To Night Hypermarket', '', '055 963 7794', 'info@nighttonighthypermarket.ae', 'Al Wahda St - Hay Al Nahda - Sharjah - United Arab Emirates', 'https://nighttonighthypermarket.ae/', 1, 10, 4.2, 3264, NULL, 35, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:32', 0),
(631, 3, 'ChIJb60L_9tbXz4R7Z-RafHANoI', 'New City Centre Hypermarket', '', '06 559 7922', '', 'Sharjah - Bu Shaghara - Hay Al Qasimiah - Sharjah - United Arab Emirates', '', 0, 11, 3.9, 1574, NULL, 75, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:35', 0),
(632, 3, 'ChIJd-rsBj1fXz4R7HjnQw2Jfxg', 'Super Bonanza Hypermarket', '', '06 557 4441', '', '8F66+64G - University City Rd - University City - Industrial Area - Sharjah - United Arab Emirates', '', 0, 12, 3.9, 1608, NULL, 75, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:36', 0),
(633, 3, 'ChIJ8ZGhwixaXz4Rs5Wulwlcwa4', 'Grand Mall Sharjah', '', '06 528 8622', 'help@grandhyper.com', '49 Ibrahim Mohammed Al Medfa\'a St - Al Mussalla - Hay Al Gharb - Sharjah - United Arab Emirates', 'http://www.grandhyper.com/', 1, 13, 4.0, 6856, NULL, 35, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:39', 0),
(634, 3, 'ChIJszlv9GxZXz4R4EM_hEm_gyo', 'Sharjah Coop, Halwan', '', '600 548884', '', '340 Sharjah Coop - Sheikh Zayed St - Al Abar - Halwan - Sharjah - United Arab Emirates', 'https://www.shjcoop.ae/', 1, 14, 4.3, 4390, NULL, 35, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:41', 0),
(635, 3, 'ChIJXzkkOIxZXz4RJJC38xeGpKc', 'Sharjah Coop, Sweihat', '', '600 548884', '', '441 Sharjah Coop - Sheikh Khalid Bin Khalid Al Qasimi St - Al Swaihat - Sharjah - United Arab Emirates', 'https://www.shjcoop.ae/', 1, 15, 3.7, 43, NULL, 17, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:42', 0),
(636, 3, 'ChIJFc7OCnxZXz4RcH5lUWYT6C4', 'Nesto Hypermarket - Al Yarmook', '', '056 501 2068', 'ro.india@nestogroup.com', 'Near Co-op Head Office - Al Wahda St - Al Yarmook - Hay Al Qasimiah - Sharjah - United Arab Emirates', 'https://nestogroup.com/?utm_source=Google&utm_medium=GBP&utm_campaign=Al-Yarmook-Sharjah&utm_content=Nesto', 1, 16, 4.0, 1658, NULL, 35, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:44', 0),
(637, 3, 'ChIJa3A_Ic9bXz4RhAvYXYi6R9c', 'Spinneys', '', '600 575756', '', 'King Faisal St - Al Majaz 1 - Al Majaz - Sharjah - United Arab Emirates', 'https://www.spinneys.com/', 1, 17, 4.3, 2154, NULL, 35, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:47', 0),
(638, 3, 'ChIJw1OuNNVdXz4ROtT3K6wMvpo', 'LuLu Hypermarket - Al Nahda', '', '06 536 7777', 'customercare@luluhypermarket.com', 'Park - Al Ittihad Rd - opposite to Al Nahda - Hay Al Nahda - Al Nahda - Dubai - United Arab Emirates', 'https://www.luluhypermarket.com/en-ae', 1, 18, 4.1, 8640, NULL, 35, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:48', 0),
(639, 3, 'ChIJ6XcWyfj19T4R1IvIBk09g5Y', 'Greens Hypermarket (FZC) | Talal Market', '', '056 520 5245', 'info@talalgroupintl.com', 'Al Ruqa Al Hamra - Saif Zone - Sharjah - United Arab Emirates', 'https://www.talalgroupintl.com/', 1, 19, 3.8, 344, NULL, 25, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:50', 0),
(640, 3, 'ChIJL116AbFZXz4Rx_5xdfCpnIA', 'OnMart Hypermarket LLC | اون مارت هايبرماركت', '', '', 'info@onmart.ae', 'First Industrial St - Industrial Area 5 - Industrial Area - Sharjah - United Arab Emirates', 'http://www.onmart.ae/', 1, 20, 4.1, 125, NULL, 30, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:51', 0),
(641, 3, 'ChIJvcZj7t5bXz4RAkCs82Ra2VM', 'Big Bazaar Hypermarket', '', '06 524 4200', '', '29 Aws Bin Thabet St - Bu Shaghara - Hay Al Qasimiah - Sharjah - United Arab Emirates', '', 0, 21, 3.8, 855, NULL, 75, 'all', 'Sharjah, UAE', 'Grocery store', 0, NULL, '2026-04-30 09:30:53', 0),
(642, 3, 'ChIJDYoc0KVyXz4R3dGDn6m-4Lo', 'Global Shipping & Logistics LLC', '', '04 885 1566', 'info@gsldubai.com', 'Dubai Investment Park - مجمع دبي للاستثمار الأول - دبي - United Arab Emirates', 'https://www.gsldubai.com/', 1, 2, 4.3, 371, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:16', 0),
(643, 3, 'ChIJ5w95ep5pXz4RxLMwNr0ybc0', 'Alma Cargo Services | Best Logistics and Shipping Company in Dubai | Customs Clearance Agent Dubai | Freight Forwarding UAE', '', '050 624 0236', 'alsahal@emirates.net.ae', 'Oppo Al Khail Mall - Latifa Bint Hamdan St - القوز الصناعية الأولى - القوز - دبي - United Arab Emirates', 'https://www.almacargodubai.com/', 1, 3, 4.8, 104, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:21', 0),
(644, 3, 'ChIJC-ju8ypDXz4Rih2XYd3eP4w', 'Emirates Logistics Head Office', '', '04 337 7177', 'info@emirateslogistics.com', '2nd Floor - New Sharaf Building - شارع خالد بن الوليد - next to Burjuman Metro Station - أم هرير الأولى - Umm Hurair 1 - دبي - United Arab Emirates', 'https://emirateslogistics.com/', 1, 4, 4.1, 179, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:25', 0),
(645, 3, 'ChIJ8UP47U5DXz4R1rsTqH_1S-Y', 'Grand FreightX Shipping LLC - Logistic Company in Dubai, UAE', '', '06 577 4002', '', 'Warehouse No # 05 | Terminal # 03 Cargo Centre | Sharjah Airport - الرقعة الحمراء - دبي - United Arab Emirates', 'http://www.grandfreightx.com/', 0, 5, 4.6, 32, NULL, 77, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:30', 0),
(646, 3, 'ChIJj9P-7x1dXz4R698myVcajNg', 'Jenae Logistics LLC - Cargo Village HQ.', '', '04 282 4811', 'jasleem@jenaelogistics.com', 'Office No: 4079, Cargo Mega Terminal Emirates Sky Cargo Building, Dubai Cargo Village - Dubai Int\'l Airport - Dubai - United Arab Emirates', 'https://www.jenaelogistics.com/', 1, 6, 4.0, 27, NULL, 21, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:32', 0),
(647, 3, 'ChIJyTW089sLXz4Rh8mGWAm8Kt0', 'Global Shipping & Logistics LLC | DIC Branch', '', '04 885 1566', 'info@gsldubai.com', 'Dubai - Saih Shuaib 2 - Dubai - United Arab Emirates', 'http://www.gsldubai.com/', 1, 7, 4.5, 229, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:32', 0),
(648, 3, 'ChIJayhO_KAMXz4RC99P2cQCXA8', 'Global Logistics', '', '', 'info@gnplogistics.com', 'Madinat Al Mataar - Dubai South Logistics District - Dubai - United Arab Emirates', 'http://www.gnplogistics.com/', 1, 8, 4.8, 58, NULL, 22, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:34', 0),
(649, 3, 'ChIJM9qJMKNdXz4RpCErd4719f8', 'GLOBAL CONNECT LOGISTICS LLC DUBAI', '', '050 694 3055', 'info@gclexp.com', 'WAREHOUSE NO-6 - opposite MEGA FIX TYRE - Al Qusais Industrial Area - Dubai - United Arab Emirates', 'https://www.gclexp.com/', 1, 9, 4.0, 80, NULL, 27, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:35', 0),
(650, 3, 'ChIJ8eB6XZkNXz4RBDIroNP_IR4', 'Sea Prince Logistics JAFZA | Warehouse in Jebel Ali | Shipping and Custom clearance in Dubai | Since 1996', '', '054 995 7598', 'zahid@seaprince.ae', '24°57\'27. 55°03\'57.0\"E Plot # MO0706, Street N200, JAFZA North - ميناء جبل علي - منطقة جبل علي الحرة - دبي - United Arab Emirates', 'https://seaprince.ae/', 1, 10, 4.8, 74, NULL, 27, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:38', 0),
(651, 3, 'ChIJezR_pXhnXz4RS4kT7OwZb_Y', 'LSC FREIGHTS & LOGISTICS LLC', '', '055 366 5495', 'harvinder@lscfreights.com', 'Montana Building - Office 206 Za\'abeel St - Al Karama - Dubai - United Arab Emirates', 'http://www.lscfreights.com/', 1, 11, 5.0, 164, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:39', 0),
(652, 3, 'ChIJtZSMUe5DXz4R9JWT1ordCpo', 'Hyat Logistics Cargo & Clearance', '', '04 325 7338', 'info@hyatlogisticsuae.com', 'Office 55A, Al Farda A Block, Dubai Customs - مدينة دبي الملاحية - الميناء - دبي - United Arab Emirates', 'http://hyatlogisticsuae.com/', 1, 12, 5.0, 113, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:40', 0),
(653, 3, 'ChIJl7oJELxDXz4R2g5wg3Llm64', 'S A G Logistic Services LLC', '', '04 349 4262', 'ssinha@saglogistic.com', 'Fahidi Heights - AWR Properties - 1202, Level 12 - Al Hamriya - Dubai - United Arab Emirates', 'https://saglogistic.com/', 1, 13, 4.7, 52, NULL, 27, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:44', 0),
(654, 3, 'ChIJV1bBQ0ZdXz4RYxPCXghIFCs', 'Safe Box Logistics LLC Dubai', '', '04 370 7344', 'info@safeboxlogistics.com', 'DAFZA office #120 - Block D - Freight Gate 5 2rd Floor - السطوة - دبي - United Arab Emirates', 'http://www.safeboxlogistics.com/', 1, 14, 4.7, 86, NULL, 27, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:48', 0),
(655, 3, 'ChIJDxBnR61dXz4RfcSWqnjsCOQ', 'Union Logistics Fze', '', '04 299 6565', 'info@unionlogistics.ae', 'W40 C - Dubai Int\'l Airport - Dubai Airport Free Zone - Dubai - United Arab Emirates', 'http://www.unionlogistics.ae/', 1, 15, 4.4, 47, NULL, 27, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:48', 0),
(656, 3, 'ChIJWzHZW0pcXz4RnbU0fTqDSZU', '\'Tre\'Log LLC-Shipping Freight & Projects.Dubai.UAE', '', '', 'sales@trelogs.com', 'Airport FZ Metro - Al Twar 5 - Dubai - United Arab Emirates', 'https://www.trelogs.com/', 1, 16, 4.9, 127, NULL, 30, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:50', 0),
(657, 3, 'ChIJSVSxMs9dXz4RUh004UUSYbo', 'United Cargo & Logistics UAE', '', '058 171 2249', 'sales@unitedcargomct.com', 'Makateb Building ,19 A street - شارع المكتوم - بور سعيد - ديرة - دبي - United Arab Emirates', 'https://www.unitedcargoworld.com/', 1, 17, 4.5, 100, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:55', 0),
(658, 3, 'ChIJeeOmU2gLXz4REL0SGyCoaxU', 'INTEGRATED NATIONAL LOGISTICS DWC LLC', '', '04 816 0600', 'info@inl.ae', 'Plots W5,W6,W7 - Dubai South - Dubai South Logistics District - Dubai - United Arab Emirates', 'https://www.inl.ae/', 1, 18, 4.3, 133, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:57', 0),
(659, 3, 'ChIJHXj0TDNoXz4RZuDw-mcl6rY', 'DSV - Global Transport and Logistics', '', '04 870 1157', '', 'near مطار ال مكتوم الدولي في دبي وورلد سنترال - Business Bay - Dubai Logistics City - Dubai - United Arab Emirates', 'http://ae.dsv.com/', 1, 19, 4.2, 26, NULL, 21, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:31:58', 0),
(660, 3, 'ChIJjVpmvmpdXz4RpozOTbyhidc', 'Best logistics Services in Dubai | Good Shipping Company in Dubai | BLOMEX Freight Services LLC', '', '050 782 9447', 'info@blomexfreight.com', 'Deira City Center Dubai Shopping Center - Port Saeed - Deira - Dubai - United Arab Emirates', 'https://www.blomexfreight.com/', 1, 20, 5.0, 2, NULL, 15, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:32:00', 0),
(661, 3, 'ChIJIS9pkUVdXz4RxdAA-6GHe9Q', 'APL Global Logistics Import & Export Dubai', '', '04 294 9439', '', 'Warehouse No. D-26 DAFZA - مطار دبي الدولي - Dubai Airport Free Zone - دبي - United Arab Emirates', 'http://www.aplgloballogistic.com/', 1, 21, 4.9, 28, NULL, 21, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:32:06', 0);
INSERT INTO `lead_gen_results` (`id`, `user_id`, `place_id`, `name`, `owner_name`, `phone`, `email`, `address`, `website`, `has_website`, `api_calls`, `rating`, `ratings_total`, `price_level`, `opportunity_score`, `search_mode`, `location`, `industry`, `imported`, `lead_id`, `created_at`, `website_found_by_crawler`) VALUES
(662, 3, 'ChIJDYoc0KVyXz4R3dGDn6m-4Lo', 'Global Shipping & Logistics LLC', '', '04 885 1566', 'info@gsldubai.com', 'Dubai Investment Park - مجمع دبي للاستثمار الأول - دبي - United Arab Emirates', 'https://www.gsldubai.com/', 1, 2, 4.3, 371, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:33:48', 0),
(663, 3, 'ChIJ5w95ep5pXz4RxLMwNr0ybc0', 'Alma Cargo Services | Best Logistics and Shipping Company in Dubai | Customs Clearance Agent Dubai | Freight Forwarding UAE', '', '050 624 0236', 'alsahal@emirates.net.ae', 'Oppo Al Khail Mall - Latifa Bint Hamdan St - القوز الصناعية الأولى - القوز - دبي - United Arab Emirates', 'https://www.almacargodubai.com/', 1, 3, 4.8, 104, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:33:51', 0),
(664, 3, 'ChIJC-ju8ypDXz4Rih2XYd3eP4w', 'Emirates Logistics Head Office', '', '04 337 7177', 'info@emirateslogistics.com', '2nd Floor - New Sharaf Building - شارع خالد بن الوليد - next to Burjuman Metro Station - أم هرير الأولى - Umm Hurair 1 - دبي - United Arab Emirates', 'https://emirateslogistics.com/', 1, 4, 4.1, 179, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:33:56', 0),
(665, 3, 'ChIJ8UP47U5DXz4R1rsTqH_1S-Y', 'Grand FreightX Shipping LLC - Logistic Company in Dubai, UAE', '', '06 577 4002', '', 'Warehouse No # 05 | Terminal # 03 Cargo Centre | Sharjah Airport - الرقعة الحمراء - دبي - United Arab Emirates', 'http://www.grandfreightx.com/', 0, 5, 4.6, 32, NULL, 77, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:02', 0),
(666, 3, 'ChIJj9P-7x1dXz4R698myVcajNg', 'Jenae Logistics LLC - Cargo Village HQ.', '', '04 282 4811', 'jasleem@jenaelogistics.com', 'Office No: 4079, Cargo Mega Terminal Emirates Sky Cargo Building, Dubai Cargo Village - Dubai Int\'l Airport - Dubai - United Arab Emirates', 'https://www.jenaelogistics.com/', 1, 6, 4.0, 27, NULL, 21, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:03', 0),
(667, 3, 'ChIJyTW089sLXz4Rh8mGWAm8Kt0', 'Global Shipping & Logistics LLC | DIC Branch', '', '04 885 1566', 'info@gsldubai.com', 'Dubai - Saih Shuaib 2 - Dubai - United Arab Emirates', 'http://www.gsldubai.com/', 1, 7, 4.5, 229, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:03', 0),
(668, 3, 'ChIJayhO_KAMXz4RC99P2cQCXA8', 'Global Logistics', '', '', 'info@gnplogistics.com', 'Madinat Al Mataar - Dubai South Logistics District - Dubai - United Arab Emirates', 'http://www.gnplogistics.com/', 1, 8, 4.8, 58, NULL, 22, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:05', 0),
(669, 3, 'ChIJM9qJMKNdXz4RpCErd4719f8', 'GLOBAL CONNECT LOGISTICS LLC DUBAI', '', '050 694 3055', 'info@gclexp.com', 'WAREHOUSE NO-6 - opposite MEGA FIX TYRE - Al Qusais Industrial Area - Dubai - United Arab Emirates', 'https://www.gclexp.com/', 1, 9, 4.0, 80, NULL, 27, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:05', 0),
(670, 3, 'ChIJ8eB6XZkNXz4RBDIroNP_IR4', 'Sea Prince Logistics JAFZA | Warehouse in Jebel Ali | Shipping and Custom clearance in Dubai | Since 1996', '', '054 995 7598', 'zahid@seaprince.ae', '24°57\'27. 55°03\'57.0\"E Plot # MO0706, Street N200, JAFZA North - ميناء جبل علي - منطقة جبل علي الحرة - دبي - United Arab Emirates', 'https://seaprince.ae/', 1, 10, 4.8, 74, NULL, 27, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:07', 0),
(671, 3, 'ChIJezR_pXhnXz4RS4kT7OwZb_Y', 'LSC FREIGHTS & LOGISTICS LLC', '', '055 366 5495', 'harvinder@lscfreights.com', 'Montana Building - Office 206 Za\'abeel St - Al Karama - Dubai - United Arab Emirates', 'http://www.lscfreights.com/', 1, 11, 5.0, 164, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:08', 0),
(672, 3, 'ChIJtZSMUe5DXz4R9JWT1ordCpo', 'Hyat Logistics Cargo & Clearance', '', '04 325 7338', 'info@hyatlogisticsuae.com', 'Office 55A, Al Farda A Block, Dubai Customs - مدينة دبي الملاحية - الميناء - دبي - United Arab Emirates', 'http://hyatlogisticsuae.com/', 1, 12, 5.0, 113, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:09', 0),
(673, 3, 'ChIJl7oJELxDXz4R2g5wg3Llm64', 'S A G Logistic Services LLC', '', '04 349 4262', 'ssinha@saglogistic.com', 'Fahidi Heights - AWR Properties - 1202, Level 12 - Al Hamriya - Dubai - United Arab Emirates', 'https://saglogistic.com/', 1, 13, 4.7, 52, NULL, 27, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:09', 0),
(674, 3, 'ChIJV1bBQ0ZdXz4RYxPCXghIFCs', 'Safe Box Logistics LLC Dubai', '', '04 370 7344', 'info@safeboxlogistics.com', 'DAFZA office #120 - Block D - Freight Gate 5 2rd Floor - السطوة - دبي - United Arab Emirates', 'http://www.safeboxlogistics.com/', 1, 14, 4.7, 86, NULL, 27, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:12', 0),
(675, 3, 'ChIJDxBnR61dXz4RfcSWqnjsCOQ', 'Union Logistics Fze', '', '04 299 6565', 'info@unionlogistics.ae', 'W40 C - Dubai Int\'l Airport - Dubai Airport Free Zone - Dubai - United Arab Emirates', 'http://www.unionlogistics.ae/', 1, 15, 4.4, 47, NULL, 27, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:12', 0),
(676, 3, 'ChIJWzHZW0pcXz4RnbU0fTqDSZU', '\'Tre\'Log LLC-Shipping Freight & Projects.Dubai.UAE', '', '', 'sales@trelogs.com', 'Airport FZ Metro - Al Twar 5 - Dubai - United Arab Emirates', 'https://www.trelogs.com/', 1, 16, 4.9, 127, NULL, 30, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:14', 0),
(677, 3, 'ChIJSVSxMs9dXz4RUh004UUSYbo', 'United Cargo & Logistics UAE', '', '058 171 2249', 'sales@unitedcargomct.com', 'Makateb Building ,19 A street - شارع المكتوم - بور سعيد - ديرة - دبي - United Arab Emirates', 'https://www.unitedcargoworld.com/', 1, 17, 4.5, 100, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:19', 0),
(678, 3, 'ChIJeeOmU2gLXz4REL0SGyCoaxU', 'INTEGRATED NATIONAL LOGISTICS DWC LLC', '', '04 816 0600', 'info@inl.ae', 'Plots W5,W6,W7 - Dubai South - Dubai South Logistics District - Dubai - United Arab Emirates', 'https://www.inl.ae/', 1, 18, 4.3, 133, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:20', 0),
(679, 3, 'ChIJHXj0TDNoXz4RZuDw-mcl6rY', 'DSV - Global Transport and Logistics', '', '04 870 1157', '', 'near مطار ال مكتوم الدولي في دبي وورلد سنترال - Business Bay - Dubai Logistics City - Dubai - United Arab Emirates', 'http://ae.dsv.com/', 1, 19, 4.2, 26, NULL, 21, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:20', 0),
(680, 3, 'ChIJjVpmvmpdXz4RpozOTbyhidc', 'Best logistics Services in Dubai | Good Shipping Company in Dubai | BLOMEX Freight Services LLC', '', '050 782 9447', 'info@blomexfreight.com', 'Deira City Center Dubai Shopping Center - Port Saeed - Deira - Dubai - United Arab Emirates', 'https://www.blomexfreight.com/', 1, 20, 5.0, 2, NULL, 15, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:21', 0),
(681, 3, 'ChIJIS9pkUVdXz4RxdAA-6GHe9Q', 'APL Global Logistics Import & Export Dubai', '', '04 294 9439', '', 'Warehouse No. D-26 DAFZA - مطار دبي الدولي - Dubai Airport Free Zone - دبي - United Arab Emirates', 'http://www.aplgloballogistic.com/', 1, 21, 4.9, 28, NULL, 21, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:34:28', 0),
(682, 3, 'ChIJDYoc0KVyXz4R3dGDn6m-4Lo', 'Global Shipping & Logistics LLC', '', '04 885 1566', 'info@gsldubai.com', 'Dubai Investment Park - مجمع دبي للاستثمار الأول - دبي - United Arab Emirates', 'https://www.gsldubai.com/', 1, 2, 4.3, 371, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:14', 0),
(683, 3, 'ChIJ5w95ep5pXz4RxLMwNr0ybc0', 'Alma Cargo Services | Best Logistics and Shipping Company in Dubai | Customs Clearance Agent Dubai | Freight Forwarding UAE', '', '050 624 0236', 'alsahal@emirates.net.ae', 'Oppo Al Khail Mall - Latifa Bint Hamdan St - القوز الصناعية الأولى - القوز - دبي - United Arab Emirates', 'https://www.almacargodubai.com/', 1, 3, 4.8, 104, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:17', 0),
(684, 3, 'ChIJC-ju8ypDXz4Rih2XYd3eP4w', 'Emirates Logistics Head Office', '', '04 337 7177', 'info@emirateslogistics.com', '2nd Floor - New Sharaf Building - شارع خالد بن الوليد - next to Burjuman Metro Station - أم هرير الأولى - Umm Hurair 1 - دبي - United Arab Emirates', 'https://emirateslogistics.com/', 1, 4, 4.1, 179, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:21', 0),
(685, 3, 'ChIJ8UP47U5DXz4R1rsTqH_1S-Y', 'Grand FreightX Shipping LLC - Logistic Company in Dubai, UAE', '', '06 577 4002', '', 'Warehouse No # 05 | Terminal # 03 Cargo Centre | Sharjah Airport - الرقعة الحمراء - دبي - United Arab Emirates', 'http://www.grandfreightx.com/', 0, 5, 4.6, 32, NULL, 77, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:26', 0),
(686, 3, 'ChIJj9P-7x1dXz4R698myVcajNg', 'Jenae Logistics LLC - Cargo Village HQ.', '', '04 282 4811', 'jasleem@jenaelogistics.com', 'Office No: 4079, Cargo Mega Terminal Emirates Sky Cargo Building, Dubai Cargo Village - Dubai Int\'l Airport - Dubai - United Arab Emirates', 'https://www.jenaelogistics.com/', 1, 6, 4.0, 27, NULL, 21, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:27', 0),
(687, 3, 'ChIJyTW089sLXz4Rh8mGWAm8Kt0', 'Global Shipping & Logistics LLC | DIC Branch', '', '04 885 1566', 'info@gsldubai.com', 'Dubai - Saih Shuaib 2 - Dubai - United Arab Emirates', 'http://www.gsldubai.com/', 1, 7, 4.5, 229, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:28', 0),
(688, 3, 'ChIJayhO_KAMXz4RC99P2cQCXA8', 'Global Logistics', '', '', 'info@gnplogistics.com', 'Madinat Al Mataar - Dubai South Logistics District - Dubai - United Arab Emirates', 'http://www.gnplogistics.com/', 1, 8, 4.8, 58, NULL, 22, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:29', 0),
(689, 3, 'ChIJM9qJMKNdXz4RpCErd4719f8', 'GLOBAL CONNECT LOGISTICS LLC DUBAI', '', '050 694 3055', 'info@gclexp.com', 'WAREHOUSE NO-6 - opposite MEGA FIX TYRE - Al Qusais Industrial Area - Dubai - United Arab Emirates', 'https://www.gclexp.com/', 1, 9, 4.0, 80, NULL, 27, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:30', 0),
(690, 3, 'ChIJ8eB6XZkNXz4RBDIroNP_IR4', 'Sea Prince Logistics JAFZA | Warehouse in Jebel Ali | Shipping and Custom clearance in Dubai | Since 1996', '', '054 995 7598', 'zahid@seaprince.ae', '24°57\'27. 55°03\'57.0\"E Plot # MO0706, Street N200, JAFZA North - ميناء جبل علي - منطقة جبل علي الحرة - دبي - United Arab Emirates', 'https://seaprince.ae/', 1, 10, 4.8, 74, NULL, 27, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:31', 0),
(691, 3, 'ChIJezR_pXhnXz4RS4kT7OwZb_Y', 'LSC FREIGHTS & LOGISTICS LLC', '', '055 366 5495', 'harvinder@lscfreights.com', 'Montana Building - Office 206 Za\'abeel St - Al Karama - Dubai - United Arab Emirates', 'http://www.lscfreights.com/', 1, 11, 5.0, 164, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:33', 0),
(692, 3, 'ChIJtZSMUe5DXz4R9JWT1ordCpo', 'Hyat Logistics Cargo & Clearance', '', '04 325 7338', 'info@hyatlogisticsuae.com', 'Office 55A, Al Farda A Block, Dubai Customs - مدينة دبي الملاحية - الميناء - دبي - United Arab Emirates', 'http://hyatlogisticsuae.com/', 1, 12, 5.0, 113, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:34', 0),
(693, 3, 'ChIJl7oJELxDXz4R2g5wg3Llm64', 'S A G Logistic Services LLC', '', '04 349 4262', 'ssinha@saglogistic.com', 'Fahidi Heights - AWR Properties - 1202, Level 12 - Al Hamriya - Dubai - United Arab Emirates', 'https://saglogistic.com/', 1, 13, 4.7, 52, NULL, 27, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:35', 0),
(694, 3, 'ChIJV1bBQ0ZdXz4RYxPCXghIFCs', 'Safe Box Logistics LLC Dubai', '', '04 370 7344', 'info@safeboxlogistics.com', 'DAFZA office #120 - Block D - Freight Gate 5 2rd Floor - السطوة - دبي - United Arab Emirates', 'http://www.safeboxlogistics.com/', 1, 14, 4.7, 86, NULL, 27, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:37', 0),
(695, 3, 'ChIJDxBnR61dXz4RfcSWqnjsCOQ', 'Union Logistics Fze', '', '04 299 6565', 'info@unionlogistics.ae', 'W40 C - Dubai Int\'l Airport - Dubai Airport Free Zone - Dubai - United Arab Emirates', 'http://www.unionlogistics.ae/', 1, 15, 4.4, 47, NULL, 27, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:38', 0),
(696, 3, 'ChIJWzHZW0pcXz4RnbU0fTqDSZU', '\'Tre\'Log LLC-Shipping Freight & Projects.Dubai.UAE', '', '', 'sales@trelogs.com', 'Airport FZ Metro - Al Twar 5 - Dubai - United Arab Emirates', 'https://www.trelogs.com/', 1, 16, 4.9, 127, NULL, 30, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:39', 0),
(697, 3, 'ChIJSVSxMs9dXz4RUh004UUSYbo', 'United Cargo & Logistics UAE', '', '058 171 2249', 'sales@unitedcargomct.com', 'Makateb Building ,19 A street - شارع المكتوم - بور سعيد - ديرة - دبي - United Arab Emirates', 'https://www.unitedcargoworld.com/', 1, 17, 4.5, 100, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:44', 0),
(698, 3, 'ChIJeeOmU2gLXz4REL0SGyCoaxU', 'INTEGRATED NATIONAL LOGISTICS DWC LLC', '', '04 816 0600', 'info@inl.ae', 'Plots W5,W6,W7 - Dubai South - Dubai South Logistics District - Dubai - United Arab Emirates', 'https://www.inl.ae/', 1, 18, 4.3, 133, NULL, 35, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:46', 0),
(699, 3, 'ChIJHXj0TDNoXz4RZuDw-mcl6rY', 'DSV - Global Transport and Logistics', '', '04 870 1157', '', 'near مطار ال مكتوم الدولي في دبي وورلد سنترال - Business Bay - Dubai Logistics City - Dubai - United Arab Emirates', 'http://ae.dsv.com/', 1, 19, 4.2, 26, NULL, 21, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:46', 0),
(700, 3, 'ChIJjVpmvmpdXz4RpozOTbyhidc', 'Best logistics Services in Dubai | Good Shipping Company in Dubai | BLOMEX Freight Services LLC', '', '050 782 9447', 'info@blomexfreight.com', 'Deira City Center Dubai Shopping Center - Port Saeed - Deira - Dubai - United Arab Emirates', 'https://www.blomexfreight.com/', 1, 20, 5.0, 2, NULL, 15, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:46', 0),
(701, 3, 'ChIJIS9pkUVdXz4RxdAA-6GHe9Q', 'APL Global Logistics Import & Export Dubai', '', '04 294 9439', '', 'Warehouse No. D-26 DAFZA - مطار دبي الدولي - Dubai Airport Free Zone - دبي - United Arab Emirates', 'http://www.aplgloballogistic.com/', 1, 21, 4.9, 28, NULL, 21, 'all', 'Dubai, UAE', 'Logistics company', 0, NULL, '2026-04-30 09:38:54', 0),
(702, 3, 'ChIJYSgh5DVDXz4R4tdjqGfBJJI', 'Desert Wave Travel Agency', '', '04 243 7141', 'info@desertwavetours.com', 'Office # 302 - same building of UBL Bank, Bank Street Building - Exit 3 - near to Burjuman Metro Station - المنخول - دبي - United Arab Emirates', 'https://desertwavetours.com/', 1, 2, 4.9, 1536, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:03', 0),
(703, 3, 'ChIJ_YTM_jlcXz4RbrOTtNSiR1g', 'Regal Dubai Travel Agency - UAE Visas & Worldwide Visit Visa Services Provider in Dubai for Over 12 Years.', '', '055 826 4490', 'info@regaluae.com', 'Office Bin Fahad Building - No 312, 3rd Floor - 4 Damascus Street - Al Qusais 2 - Dubai - United Arab Emirates', 'https://www.regaltoursuae.com/', 1, 3, 4.7, 3428, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:06', 0),
(704, 3, 'ChIJj8PogVVdXz4RGUvypYDgZUc', 'Travel Saga Tourism - Travel Agency', '', '04 268 4645', 'booking@travelsaga.com', 'New Century City Tower - Office 303 - Al Ittihad Rd - opp.to Deira City Centre - Port Saeed - Deira - Dubai - United Arab Emirates', 'http://travelsaga.com/', 1, 4, 4.9, 1446, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:11', 0),
(705, 3, 'ChIJDwG1rChpXz4RqZa_-fCTVSI', 'Neo Travels: Travel Agency Dubai', '', '058 140 6730', 'sales@neotravels.ae', 'Oasis Mall - Office #100 - Sheikh Zayed Rd - Al Qouz First - Al Quoz - Dubai - United Arab Emirates', 'https://neotravels.ae/', 1, 5, 5.0, 350, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:12', 0),
(706, 3, 'ChIJicEa5ENdXz4RPlMFsx-EYkY', 'Pinoy Tourism - Dubai (Deira)', '', '04 295 9000', '', 'Room 638, 6th Floor, Entrance 3, Office Tower - المرقبات - ديرة - دبي - United Arab Emirates', 'https://pinoytourism.com/', 1, 6, 4.9, 14551, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:22', 0),
(707, 3, 'ChIJx4v4VkRdXz4R3LnH7mv4740', 'TAAL TOURISM L.L.C - Travel Agency In Dubai, UAE', '', '056 526 2117', 'ask@taaltourism.com', 'unit no:253, BIN FAHAD 4 - شارع دمشق - القصيص ٢ - دبي - United Arab Emirates', 'https://www.taaltourism.com/', 1, 7, 4.8, 448, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:23', 0),
(708, 3, 'ChIJMaNjNYt4vgwRmHHW2Gy7MB0', 'Next Holidays - Best Travel Agency in Dubai, UAE', '', '056 507 1346', '', '1210-1211 - The Regal Tower - الخليج التجاري - دبي - United Arab Emirates', 'https://www.nextholidays.com/', 1, 8, 4.7, 213, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:25', 0),
(709, 3, 'ChIJuZEt3xRDXz4RyZMTAAKl1MQ', 'Pluto Travels LLC Dubai', '', '04 392 0930', '', '2805, Prism tower - Business Bay - Dubai - United Arab Emirates', 'http://www.plutotravels.ae/', 1, 9, 4.7, 299, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:26', 0),
(710, 3, 'ChIJh8UHHclpXz4R9UxY4_MH7Eg', 'AlCabana Luxury Travel Boutique | Travel Agency Company in Dubai, UAE.', '', '056 170 0776', 'hello@alcabana.com', 'S14, W05 - Warsan First - Russia Cluster - Dubai - United Arab Emirates', 'http://www.alcabana.com/', 1, 10, 4.9, 483, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:28', 0),
(711, 3, 'ChIJ5ziGKsxCXz4RFVgQSbG-IJQ', 'Uranus Travel & Tours - Holiday & Tours Packages', '', '04 335 5559', 'online@uranustravel.com', '1G/2B, Sultan Business Center - next to Lamcy Plaza - عود ميثاء - دبي - United Arab Emirates', 'https://www.uranustravel.com/', 1, 11, 4.6, 687, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:29', 0),
(712, 3, 'ChIJU05cac9dXz4Rewcei0uJSYc', 'Go Kite Travel & Tours Dubai', '', '054 354 9443', 'info@kite.travel', 'ACICO Business Park - Building, Office #111,112 - Road - behind Nissan Showroom - Port Saeed - Deira - Dubai - United Arab Emirates', 'https://www.gokite.travel/', 1, 12, 4.8, 2843, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:29', 0),
(713, 3, 'ChIJDedsk05DXz4RNN8cdKD7CL4', 'AV Best Travel LLC', '', '056 264 7343', '', 'The Tower Plaza Hotel - Office 2321 - 2320 Sheikh Zayed Rd - Trade Center First - Dubai - United Arab Emirates', 'https://besttravel.am/en/', 1, 13, 4.9, 668, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:32', 0),
(714, 3, 'ChIJC2-Nk7NdXz4R25_OId-0tDU', 'Amani Travel & Tourism', '', '052 391 5786', 'noushad.eran@gmail.com', 'Dubai Wasl Aqua - Shop # 5 - 39th St - Al Karama - Dubai - United Arab Emirates', 'https://www.amanitravels.com/', 1, 14, 4.6, 1215, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:35', 0),
(715, 3, 'ChIJ5RxHkfpdXz4RDqb2TWO-M0g', 'AL FALAHEHY TRAVELS LLC', '', '050 436 7712', 'info@alfalahehytravelsllc.com', 'NEW CENTURY TOWER - opp. DEIRA CITY CENTRE - Port Saeed - Deira - Dubai - United Arab Emirates', 'http://www.alfalahehytravelsllc.com/', 1, 15, 4.9, 1153, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:44', 0),
(716, 3, 'ChIJ2aJeViBdXz4RfMwd15cS9Pg', 'Gulliver Travels', '', '04 235 4481', 'info@gullivertravelsae.com', 'Office No.15, 4th Floor, E Block, Al Naboodah Building - Deira - بور سعيد - ديرة - دبي - United Arab Emirates', 'https://gullivertravels.ae/', 1, 16, 4.9, 713, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:46', 0),
(717, 3, 'ChIJIXVmM0BDXz4Rj590-2nfbx8', 'Angel Wings Travel Agency Dubai', '', '054 336 4928', 'booking@angelwingsinternational.net', 'Conrad Dubai Office Tower, Dubai World Trade Centre - Sheikh Zayed Rd - Trade Center Second - Dubai - United Arab Emirates', 'https://angelwingsinternational.net/', 1, 17, 4.9, 488, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:50', 0),
(718, 3, 'ChIJV8fKYOdDXz4RV59YbUoMz9Q', 'Forever Tourism LLC', '', '04 388 9941', 'info@forevertourism.com', '111, Sultan Building, 125287 - Bur Dubai - beside Lamcy Plaza - عود ميثاء - دبي - United Arab Emirates', 'https://www.forevertourism.com/', 1, 18, 4.9, 791, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:51', 0),
(719, 3, 'ChIJv4jfE0BDXz4RCF1KhDuIgR0', 'TravNook Travel & Tourism - تراف نوك للسفر و السياحة', '', '054 438 8038', 'contact@travnook.com', 'One Central at Ibis Hotel, Dubai World Trade Centre - Level 1 - Sheikh Zayed Rd - Trade Center Second - Dubai - United Arab Emirates', 'https://travnook.com/?utm_source=Google_Business_Profile&utm_medium=gbp_view_website', 1, 19, 4.7, 843, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:53', 0),
(720, 3, 'ChIJ8whHlfxDXz4R3a3BRqJ9H1o', 'Rio Travels', '', '04 327 6600', 'info@riotravels.ae', 'https://maps.app.goo.gl - Al Bada\' - Dubai - United Arab Emirates', 'https://riotravels.ae/', 1, 20, 4.7, 1031, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:39:57', 0),
(721, 3, 'ChIJsc60WzxDXz4RHYeNrPOymcM', 'UTM Travel & Visa', '', '04 420 7800', '', 'JVC Room #503A - Prime Business Centre - Al Barsha South Fourth - Jumeirah Village Circle - Dubai - United Arab Emirates', 'http://www.utmservices.com/', 1, 21, 4.9, 1523, NULL, 35, 'all', 'Dubai, UAE', 'Travel agency', 0, NULL, '2026-04-30 09:40:01', 0),
(722, 3, 'ChIJ06F7vR5ZqDsRhDXb6xIJ3Fg', 'BYJU\'S Tuition Centre - RS Puram', '', '090090 02000', '', 'Door 22A, 3rd Floor, Thiruvenkatasamy Road (East, opp. to Poniah Hospital, R.S. Puram, Coimbatore, Tamil Nadu 641002, India', 'https://byjus.com/btc/byjus-tuition-centre-rs-puram-coimbatore/?utm_source=GMB&utm_medium=btc-link&utm_campaign=GMB-profile', 1, 2, 4.8, 253, NULL, 35, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:18', 0),
(723, 3, 'ChIJx_eRuIpZqDsRkNs_WU3vJGw', 'Murali Sir\'s NCE - Tuition Center (Home Tuition Available)', '', '098946 50864', '', '11, Ramakrishna Nagar, New siddhapudur, Cbe - 641044, G K D Nagar, Coimbatore, Tamil Nadu 641044, India', '', 0, 3, 5.0, 80, NULL, 77, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:21', 0),
(724, 3, 'ChIJYyiFZlZYqDsRXr28ssw24cg', 'JR Tuition & JR Tutorial college | JR Nios Academy 10th 11th 12th Hsc SSLC Best coaching Study Centre college in Coimbatore', '', '094459 20082', 'ceo@jrtutorialcollege.com', '418,6th st extn, Dr Rajendra Prasad Rd, Gandhipuram, Coimbatore, Tamil Nadu 641012, India', 'https://jrtutorialcollege.com/', 1, 4, 4.9, 96, NULL, 27, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:22', 0),
(725, 3, 'ChIJL9Dc1R5ZqDsR8-A2KadKPsU', 'APT Tuition Center', '', '098947 88559', '', '4B, Robertson Rd, Near Milk Company, R.S. Puram, Coimbatore, Tamil Nadu 641002, India', 'http://www.apttuition.com/', 1, 5, 4.8, 112, NULL, 35, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:34', 0),
(726, 3, 'ChIJP5oqgH9XqDsRGn-zQyB3V_8', 'Velan Institute - Best Tuition center CBSE IGCSE ICSE Stateboard,Best maths tuition center,physics chemistry accounts', '', '080151 52525', '', '43, extension street, sungam, Ondipudur, Coimbatore, Tamil Nadu 641016, India', '', 0, 6, 5.0, 128, NULL, 85, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:35', 0),
(727, 3, 'ChIJWxaDHg9ZqDsRERh3s9keLXg', 'Kovai Tuition Center', '', '096559 13579', 'admin@kovaituitions.com', 'No.206, 7h street, Gandhipuram, Coimbatore, Tamil Nadu 641012, India', 'http://www.kovaituitions.com/', 1, 7, 3.8, 21, NULL, 11, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:35', 0),
(728, 3, 'ChIJlRAuHQBZqDsRFdlb5z2lLBY', 'Vedantu Learning Centre Coimbatore (VLC), RS Puram - JEE / NEET / Foundation', '', '089719 07345', 'vcare@vedantu.com', 'Third Floor, Sri Skandha Towers, 14/2A, Cowley Brown Rd, R.S. Puram, Coimbatore, Tamil Nadu 641002, India', 'https://www.vedantu.com/offline-centres/coimbatore/rs-puram?utm_source=organic&utm_medium=googlemybusiness&utm_campaign=offline', 1, 8, 4.7, 107, NULL, 35, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:36', 0),
(729, 3, 'ChIJD5yKTehYqDsRRbWSkfNJMcU', 'Roots Tuition Centre', '', '094863 85036', '', 'Shop No.77, Collector Sivakumar St, Saibaba Colony, Coimbatore, Tamil Nadu 641038, India', '', 0, 9, 4.9, 31, NULL, 77, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:38', 0),
(730, 3, 'ChIJHzP38mdZqDsRbu6kt3Oj0dw', 'Medvann Home Tuition in Coimbatore', '', '090955 22133', 'contact@medvann.com', '5A, Parameshwaran Layout Rd, Papanaickenpalayam, G K D Nagar, Coimbatore, Tamil Nadu 641037, India', 'http://medvann.com/', 1, 10, 4.9, 97, NULL, 27, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:40', 0),
(731, 3, 'ChIJ57A-23dXqDsRfObYq5n01Qg', 'tuition centre in coimbatore(KARKA KASADARA)', '', '093428 49660', 'karkakasadaratuition@gmail.com', '348, Kamaraj Rd, Uppilipalayam, Centennary Memorial Middle School, Rajiv Gandhi St, near by Gandhi school, Gandhipudur, Lakshmipuram, Varadharajapuram, Coimbatore, Tamil Nadu 641015, India', 'http://karkakasadaratuitioncenter.org/', 1, 11, 4.7, 55, NULL, 27, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:42', 0),
(732, 3, 'ChIJmRN2OlNZqDsRvrZRcXjTxwE', 'Treasure Academy-OFFLINE /ONLINE CLASS / HOME TUITION- -Best Coaching-Grade Lkg- 12 (All Subjects )VADAVALLI,Coimbatore.', '', '098942 65677', '', '30/72 sundaram street, north, 30, 72, Sundaram St, Near Gandhi Park, R.S. Puram, Coimbatore, Tamil Nadu 641001, India', '', 0, 12, 4.9, 29, NULL, 71, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:43', 0),
(733, 3, 'ChIJ89XGPA1ZqDsRZ_x9RGQko-Y', 'Futurenxt', '', '099526 04882', 'futurenxtcbe@gmail.com', '3a, Sathy Rd, Ramakrishnapuram, Ganapathy, Coimbatore, Tamil Nadu 641006, India', 'https://futurenxt.co.in/', 1, 13, 4.8, 173, NULL, 35, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:44', 0),
(734, 3, 'ChIJXdxSU3xZqDsRJRfeHQTpkns', 'Aspire Tuition Center', '', '098433 18566', '', 'Karpaga Vinayakar Street, Maniyakarampalayam Rd, Ganapathy Housing Unit, Karpaga Vinyaha Nagar, Ganapathy, Coimbatore, Tamil Nadu 641006, India', 'http://www.aspireacademics.in/', 1, 14, 4.9, 90, NULL, 27, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:47', 0),
(735, 3, 'ChIJGwiEMRpZqDsRyR4le--2q7w', 'Maths toppers Academy(CBSE Tuition centre,CBSE Maths Tuitions,Isc Tuitions,Best tuitions Centre)', '', '090920 10105', '', 'Park Royal Building, 7th St, Gandhipuram, Coimbatore, Tamil Nadu 641012, India', '', 0, 15, 4.9, 137, NULL, 85, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:48', 0),
(736, 3, 'ChIJUzxafIpZqDsRaSU1evsABA0', 'SRI SAI TUITION & MATHS COACHING CENTRE for ( I - XII) matric,CBSE,ICSE, IGCSE,ISC BOARD', '', '072006 91245', '', '25/36, Rajammal Layout Rd, near saffron apartment, Gandhi Park, Selvapuram North, Ponnaiah Raja Puram, Coimbatore, Tamil Nadu 641001, India', '', 0, 16, 5.0, 18, NULL, 71, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:49', 0),
(737, 3, 'ChIJrd5IGupZqDsRAosiW2brvVo', 'Centum Tuition Centre - Best Coaching Centre in Coimbatore', '', '098422 55399', 'centumsuresh@gmail.com', 'Devi Nivas, Chinnammal St, above Bombay Dyeing Rose, Saibaba Colony, K K Pudur, Coimbatore, Tamil Nadu 641038, India', 'https://centumtuitioncentre.com/', 1, 17, 4.7, 40, NULL, 27, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:49', 0),
(738, 3, 'ChIJiy-qySlZqDsRM_9NK1wg78g', 'KV TUITION CENTER', '', '076675 57412', '', 'Sugar Cane Institute Rd, Seeranaickenpalayam, Coimbatore, Tamil Nadu 641007, India', '', 0, 18, 4.8, 62, NULL, 77, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:53', 0),
(739, 3, 'ChIJacBaP1xXqDsR2yrf04F7HKI', 'IGCSE, AS & A Level Tuition & IB Tuitions. Best A & AS Level Tuition & TOP IB DP1 & DP2 Tuition', '', '090421 71729', 'admin@thechennaituition.com', '18, Peelamedu - Pudur Main Rd, TNHB Colony, Madhusudhan Layout, Civil Aerodrome Post, Nehru Nagar West, Coimbatore, Tamil Nadu 641014, India', 'https://thechennaituition.com/', 1, 19, 5.0, 21, NULL, 21, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:56', 0),
(740, 3, 'ChIJh3JDSZBZqDsRsZ4ny-9qHm8', 'Aadhishankara Academy', '', '090030 33993', 'abcd@gmail.com', 'V.V. Complex, 14/3, Sowripalayam Rd, Chinna Ayyavu Thevar Layout, Meena Estate, Coimbatore, Tamil Nadu 641028, India', 'https://aadhishankara.academy/', 1, 20, 4.9, 275, NULL, 35, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:40:57', 0),
(741, 3, 'ChIJmT7z6GVZqDsR4Gr1sA-TOcA', 'BYJU\'S Tuition Centre - Avinashi Road', '', '090090 02000', 'support@byjus.com', 'Sabtharang Building, 1140, Avinashi Rd, near Lakshmi Mills Signal, Opposite Airtel Bharathi Center, Lakshmi Mills, Coimbatore, Tamil Nadu 641037, India', 'https://byjus.com/btc/byjus-tuition-centre-avinashi-road-coimbatore/?utm_source=GMB&utm_medium=btc-link&utm_campaign=GMB-profile', 1, 21, 4.7, 118, NULL, 35, 'all', 'Coimbatore, India', 'Tuition center', 0, NULL, '2026-04-30 09:41:00', 0),
(742, 3, 'ChIJTz3IfhO7cEgRCQc9v8oU8BM', 'Jack The Plumber', '', '07933 138914', 'info@jacktheplumber.com', '301 Prince of Wales Ln, Birmingham B14 4LN, UK', 'https://jacktheplumber.co.uk/', 1, 2, 5.0, 252, NULL, 35, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:41:49', 0),
(743, 3, 'ChIJc6uE8WGVcEgRD6BTz9tJwKk', 'Ideal Plumbing Services', '', '07440 753962', 'info@idealplumbingservices.co.uk', 'Vicarage Rd, Birmingham B15 3EZ, UK', 'http://www.idealplumbingservices.co.uk/', 1, 3, 4.9, 147, NULL, 35, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:41:54', 0),
(744, 3, 'ChIJgbwzBu-VcEgR1lEDlR1lBrA', 'Summit Plumbing & Heating', '', '07908 045029', 'info@summitplumbingandheating.co.uk', '26 Westhaven Dr, Birmingham B31 1DR, UK', 'http://summitplumbingandheating.co.uk/', 0, 4, 4.9, 94, NULL, 77, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:41:56', 0),
(745, 3, 'ChIJLV-kCfa_cEgRMqRbEVpLaBI', 'MK Plumbing Birmingham', '', '07355 565264', '', '23 Hill Top Rd, Birmingham B31 5AN, UK', 'https://mkplumbingbirmingham.co.uk/', 1, 5, 5.0, 139, NULL, 35, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:41:58', 0),
(746, 3, 'ChIJ7XRWihe9cEgRjdvdaUwawl4', 'GD Plumber Heating Gas Services Harborne Birmingham', '', '07863 555996', '', '304 Quinton Rd, Harborne, Birmingham B17 0RF, UK', 'https://www.gdplumbingandheatingservices.co.uk/', 1, 6, 5.0, 136, NULL, 35, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:42:00', 0),
(747, 3, 'ChIJtWbC82y9cEgRh5wjnzUJED8', 'SUPREME PLUMBERS', '', '0121 816 0544', '', '2A Suite, Blackthorn House, Birmingham B3 1RL, UK', 'https://supreme-plumbers-aq2qoadp7ocmvqll.builder-preview.com/', 1, 7, 4.9, 107, NULL, 35, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:42:00', 0),
(748, 3, 'ChIJsX3AUkK_cEgRxahGY-VBpk8', 'Swift Emergency Plumber', '', '07866 745190', 'seancmurray@me.com', '90 Hollie Lucas Rd, King\'s Heath, Birmingham B13 0QN, UK', 'https://www.swiftemergencyplumber.com/', 1, 8, 4.9, 169, NULL, 35, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:42:03', 0),
(749, 3, 'ChIJ65dGKlW7cEgRSLuxBbRzKCQ', 'Mac Plumbing And Heating', '', '0121 448 4356', 'info@macplumbingheating.co.uk', '56 Heybarnes Rd, Birmingham B10 9JA, UK', 'https://macplumbheat.co.uk/', 1, 9, 5.0, 112, NULL, 35, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:42:05', 0),
(750, 3, 'ChIJIQicPD6_cEgRBDRBPLldXwc', 'AB Plumbing & Heating', '', '0345 163 0022', 'sales@abplumbing-heating.co.uk', 'Unit 1, 202 Pershore Rd S, King\'s Norton, Birmingham B30 3EU, UK', 'https://abplumbing-heating.co.uk/', 0, 10, 4.9, 73, NULL, 77, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:42:06', 0),
(751, 3, 'ChIJpwEx3C6_cEgRGh5TOdz_hA8', 'LTF PLUMBING', '', '07577 304279', 'dave@lab6.com', '1581 Pershore Rd, Stirchley, Birmingham B30 2JF, UK', 'https://ltfplumbing.co.uk/book-an-appointment', 0, 11, 5.0, 99, NULL, 77, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:42:07', 0),
(752, 3, 'ChIJ8xV_p0S6cEgRcb7nARQUHTE', 'Afterglow Plumbing & Heating Limited', '', '07826 924452', 'info@afterglowbirmingham.co.uk', '83 Beechmore Rd, Sheldon, Birmingham B26 3AS, UK', 'https://afterglowheating.co.uk/', 0, 12, 4.9, 81, NULL, 77, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:42:08', 0),
(753, 3, 'ChIJ7z_29L29cEgRboywkvryveU', '2nd City Gas Plumbing & Heating Ltd', '', '0121 426 6014', 'office@2ndcitygasplumbingandheating.co.uk', '1 Balden Rd, Harborne, Birmingham B17 9EU, UK', 'https://2ndcitygasplumbingandheating.co.uk/?utm_source=google_profile&utm_campaign=localo&utm_medium=mainlink', 0, 13, 4.5, 162, NULL, 85, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:42:21', 0),
(754, 3, 'ChIJofRg7z2jcEgRP_452UjZnHQ', 'Matt Plumbing and Heating Birmingham', '', '07958 591079', '', '8 Thetford Rd, Birmingham B42 2JB, UK', 'http://www.mattplumbingandheating.com/', 0, 14, 4.6, 48, NULL, 77, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:42:24', 0),
(755, 3, 'ChIJd6S2p2u9cEgRFwwtkW2KQIA', 'Power Plus Heating Ltd', '', '07305 928834', '', 'Bath Row, Birmingham B1 1JW, UK', 'http://powerplusheating.workfol.io/', 1, 15, 5.0, 342, NULL, 35, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:42:33', 0),
(756, 3, 'ChIJkWTEts0Rzm4RfZo77E7rcFw', 'HydroGreen Heating and Gas Engineering - Birmingham & Solihull', '', '07443 365157', 'hydrogreenheatingandgas@gmail.com', '102 Bracebridge Rd, Erdington, Birmingham B24 8JG, UK', 'https://hydrogreenheatinga.wixsite.com/hydrogreenheatingand', 1, 16, 5.0, 529, NULL, 35, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:42:34', 0),
(757, 3, 'ChIJocsrY_C9cEgRa9UST7SDvVs', '1st Choice Plumber', '', '07474 828484', '', 'Hawkesyard Rd, Birmingham B24 8LP, UK', 'http://1st-choice-plumber.com/', 1, 17, 5.0, 28, NULL, 21, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:42:37', 0),
(758, 3, 'ChIJuUvJpFOWcEgRzAyhyJCRL6E', 'F & P Plumbing and Heating Ltd', '', '0121 330 0323', 'info@fandpplumbing.co.uk', 'Industrial Estate, Unit 16 Long Ln, Halesowen B62 9LD, UK', 'http://www.fandpplumbing.co.uk/', 0, 18, 4.9, 219, NULL, 85, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:42:40', 0),
(759, 3, 'ChIJ32If3XI2Oa8Rm5SGLedYYZ8', 'K1 Heating Solution', '', '0121 798 2867', 'info@k1heatingsolution.co.uk', '20 Partridge Rd, Sheldon, Birmingham B26 2DA, UK', 'http://k1heatingsolution.com/', 1, 19, 5.0, 109, NULL, 35, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:42:42', 0),
(760, 3, 'ChIJ3fo3EMcRxSkRnB5RAVBSKs8', 'Firstgas Emergency Plumber - Birmingham', '', '0121 400 0246', '', '109 Guildford Dr, Birmingham B19 2LZ, UK', 'http://www.firstgasemergencyplumberbirmingham.co.uk/', 1, 20, 5.0, 5, NULL, 15, 'all', 'Birmingham , UK', 'Plumber', 0, NULL, '2026-04-30 09:42:42', 0),
(761, 3, 'ChIJ__9TRpdmeGkRrilsxhVjzxY', 'Travellers Paradise', '', '(07) 4031 1344', 'geckoscairns@gmail.com', 'Cairns Central, 187 Bunda St, Parramatta Park QLD 4870, Australia', 'https://travellersparadisecairns.com.au/', 1, 2, 4.3, 139, NULL, 35, 'all', 'Cairns , Australia', 'Guest house', 0, NULL, '2026-04-30 09:42:54', 0),
(762, 3, 'ChIJO1XmzxxneGkRKpnEUKzGg7Q', 'Cairns Central Sharehouse', '', '0428 171 744', '', 'Level 1/36/38 McLeod St, Cairns City QLD 4870, Australia', '', 0, 3, 4.3, 21, NULL, 71, 'all', 'Cairns , Australia', 'Guest house', 0, NULL, '2026-04-30 09:42:57', 0),
(763, 3, 'ChIJu5rYnO1neGkRC-plpWOkP-g', 'Wanna Stay Hostel', '', '0407 543 020', 'info@mysite.com', '72-74 Grafton St, Cairns City QLD 4870, Australia', 'http://www.wannastayhostel.com/', 1, 4, 4.5, 107, NULL, 35, 'all', 'Cairns , Australia', 'Guest house', 0, NULL, '2026-04-30 09:43:01', 0),
(764, 3, 'ChIJVVVhIqCRwCwRL80RtyiS2qo', 'The Cozy Hostel', '', '0423 263 114', 'thecozyhostel@gmail.com', '4 Harriet Pl, Darwin City NT 0800, Australia', 'http://www.thecozydarwin.com/', 1, 2, 4.1, 195, NULL, 35, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:43:12', 0),
(765, 3, 'ChIJrfeszhiRwCwRv_SFRbQJ5h8', 'MOM Darwin', '', '(08) 8989 2979', 'info@momdarwin.net.au', '7/52 Mitchell St, Darwin City NT 0800, Australia', 'http://www.momdarwin.net.au/', 1, 3, 3.7, 538, NULL, 25, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:43:18', 0),
(766, 3, 'ChIJMTJ66FiRwCwRC1WN2EIzrrM', 'Darwin Hostel', '', '0423 263 114', 'darwinhostel@gmail.com', '88 Mitchell St, Darwin City NT 0800, Australia', 'http://www.darwinhostel.com/', 1, 4, 3.8, 136, NULL, 25, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:43:18', 0),
(767, 3, 'ChIJJa9McqKRwCwRWnAuBONPRH4', 'Darwin City B & B', '', '(08) 8941 3636', '', '4 Zealandia Cres, Larrakeyah NT 0820, Australia', '', 0, 5, 4.6, 9, NULL, 65, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:43:20', 0),
(768, 3, 'ChIJl-tdxAuRwCwRsKIBs6UgfL8', 'The Leea Darwin', '', '(08) 8946 0111', 'hello@theleea.com.au', '64 Cavenagh St, Darwin City NT 0800, Australia', 'https://www.theleea.com.au/', 1, 6, 4.0, 942, NULL, 35, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:43:28', 0),
(769, 3, 'ChIJK3q53Q6RwCwRKeb_v0758o4', 'Luma Luma Holiday Apartments', '', '(08) 8981 1899', 'enquiries@lumaluma.com.au', '26 Knuckey St, Darwin City NT 0800, Australia', 'http://www.lumaluma.com.au/', 1, 7, 3.6, 137, NULL, 25, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:43:29', 0),
(770, 3, 'ChIJxaitUAiRwCwRtaI55ZqTyL0', 'Youth Shack', '', '(08) 8981 5221', 'info@youthshack.com.au', '69 Mitchell St, Darwin City NT 0800, Australia', 'https://www.youthshack.com.au/', 1, 8, 3.2, 253, NULL, 25, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:43:31', 0),
(771, 3, 'ChIJt8_sPAaRwCwRkh3RuvPpvX8', 'Palms City Resort', '', '(08) 8982 9200', '', '64 Esplanade, Darwin City NT 0800, Australia', 'http://www.palmscityresort.com/', 1, 9, 4.3, 741, NULL, 35, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:43:40', 0),
(772, 3, 'ChIJQdAw3giRwCwRDNc8FLGxRHU', 'Darwin City Hotel', '', '(08) 7981 5125', 'stay@darwincityhotel.com', '59 Smith St, Darwin City NT 0800, Australia', 'https://www.darwincityhotel.com/', 1, 10, 3.8, 497, NULL, 25, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:43:41', 0),
(773, 3, 'ChIJ7TA4DwuRwCwRb_HxIvqJPiM', 'Argus Hotel Darwin', '', '(08) 8941 8300', 'reservations@argushotel.com.au', '13 Shepherd St, Darwin City NT 0800, Australia', 'http://www.argusaccommodation.com.au/argus-hotel/', 1, 11, 4.3, 656, NULL, 35, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:43:47', 0),
(774, 3, 'ChIJ5-sWagmRwCwRqviT6u1lWJI', 'Mindil Beach Casino Resort', '', '(08) 8943 8888', '', 'Mindil Beach, Gilruth Ave, Darwin City NT 0820, Australia', 'https://www.mindilbeachcasinoresort.com.au/', 1, 12, 4.1, 3405, NULL, 35, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:43:51', 0),
(775, 3, 'ChIJvTdUUXKRwCwRvHidXkNS7SI', 'The Heritage Darwin', '', '(08) 8981 8111', 'reservations@heritagedarwin.com.au', '84 Mitchell St, Darwin City NT 0800, Australia', 'https://heritagedarwin.com.au/', 1, 13, 4.0, 32, NULL, 27, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:43:53', 0),
(776, 3, 'ChIJOe_fwAiRwCwRpC_4YR94lEc', 'Rydges Darwin Central', '', '(08) 8944 9000', '', '21 Knuckey St, Darwin City NT 0801, Australia', 'https://www.rydges.com/accommodation/darwin-nt/rydges-darwin-central/?utm_source=google&utm_medium=organic&utm_campaign=gmb', 1, 14, 3.8, 524, NULL, 25, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:43:53', 0),
(777, 3, 'ChIJDRLiigmRwCwRRxiPiaBOZVg', 'Courtyard by Marriott Darwin', '', '(08) 8942 5555', '', '81 Smith St, Darwin City NT 0800, Australia', 'https://www.marriott.com/en-us/hotels/drwcy-courtyard-darwin/overview/?scid=f2ae0541-1279-4f24-b197-a979c79310b0', 1, 15, NULL, 603, NULL, 25, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:43:55', 0),
(778, 3, 'ChIJ70hAvuSRwCwRv-xaDziIZOE', 'Hilton Garden Inn Darwin', '', '(08) 8943 3600', '', '122 Esplanade, Darwin City NT 0800, Australia', 'https://www.hilton.com/en/hotels/drwddgi-hilton-garden-inn-darwin/?SEO_id=GMB-APAC-GI-DRWDDGI', 1, 16, 4.2, 210, NULL, 35, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:44:14', 0),
(779, 3, 'ChIJVe_keAORwCwRX-quSW_rkoQ', 'Vibe Hotel Darwin Waterfront', '', '(08) 8982 9999', '', '7 Kitchener Dr, Darwin City NT 0800, Australia', 'https://vibehotels.com/book-accommodation/darwin/hotel-darwin-waterfront/?utm_source=googleplaces&utm_medium=organic&utm_campaign=googleplaces&utm_content=vibe-darwin-waterfront&utm_term=plcid_14878817266618143585', 1, 17, 4.1, 591, NULL, 35, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:44:17', 0),
(780, 3, 'ChIJCbmYtA6RwCwRjR5xr5rI_Wo', 'Mantra Pandanas Darwin', '', '(08) 8901 2900', 'pandanas.res@mantra.com.au', '43 Knuckey St, Darwin City NT 0800, Australia', 'https://all.accor.com/lien_externe.svlt?goto=fiche_hotel&code_hotel=B3N2&merchantid=seo-maps-AU-B3N2&sourceid=aw-cen&utm_medium=seo%20maps&utm_source=google%20Maps&utm_campaign=seo%20maps', 1, 18, 4.0, 803, NULL, 35, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:44:19', 0),
(781, 3, 'ChIJb7qaZM6iwCwR4pY5nj8Zt68', 'Palmerston Sunset Retreat', '', '0408 241 950', '', '8 Renwick Ct, Gray NT 0830, Australia', 'http://www.palmerstonretreat.com.au/', 1, 19, 3.7, 14, NULL, 11, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:44:23', 0),
(782, 3, 'ChIJv93uFA6RwCwRNd_SK3iGe2c', 'Ramada Suites by Wyndham Zen Quarter Darwin', '', '(08) 7912 5212', '', '6 Carey St, Darwin City NT 0800, Australia', 'http://www.zenquarter.com/', 1, 20, 4.3, 1149, NULL, 35, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:44:32', 0),
(783, 3, 'ChIJkV0v26CRwCwRuUMGOiR0HOE', 'DoubleTree by Hilton Hotel Esplanade Darwin', '', '(08) 8980 0800', '', '116 Esplanade, Darwin City NT 0800, Australia', 'https://www.hilton.com/en/hotels/drweddi-doubletree-esplanade-darwin/?SEO_id=GMB-APAC-DI-DRWEDDI', 1, 21, 4.1, 896, NULL, 35, 'all', 'Darwin , Australia', 'Guest house', 0, NULL, '2026-04-30 09:44:52', 0),
(784, 3, 'ChIJdYWlVMDFQDsRLOQ8wPxMD4Q', 'Liberty Guest House', '', '777-2984', '', 'Irumathee Magu, Mahibadhoo 00040, Maldives', 'http://www.libertymaldives.com/', 1, 2, 4.3, 106, NULL, 35, 'all', 'Maldives', 'Guest house', 0, NULL, '2026-04-30 09:45:06', 0),
(785, 3, 'ChIJHx4sdRR_PzsRTc667XFiZtU', 'Reef House Maldives', '', '798-8991', '', 'Kimbingas Magu – 01 Goalhi Rowhouse R1104, Lot No.11121, 23000, Maldives', 'http://reefhousemaldives.site/', 1, 3, 4.5, 137, NULL, 35, 'all', 'Maldives', 'Guest house', 0, NULL, '2026-04-30 09:45:10', 0),
(786, 3, 'ChIJvy15FtOfajsRdyIe7OZKEoA', 'Coral Castle Guest House, Baa Goidhoo, Maldives', '', '777-7484', '', 'Bodu Magu Goidhoo, Baa Atoll 06130, Maldives', '', 0, 4, 4.6, 14, NULL, 71, 'all', 'Maldives', 'Guest house', 0, NULL, '2026-04-30 09:45:11', 0),
(787, 3, 'ChIJ____o8-oODsR5YNSWUU1uPs', 'Island life Maldives Retreat & Spa', '', '745-7972', 'reservations.ilmrs@gmail.com', 'Island Life Maldives Retreat & Spa Maghoodoo, Maldives', 'https://www.islandlifemaldives.info/', 1, 5, 4.3, 51, NULL, 27, 'all', 'Maldives', 'Guest house', 0, NULL, '2026-04-30 09:45:15', 0),
(788, 3, 'ChIJnXOLUKuaPzsRO9U9iYCB3l0', 'Samura Guest House', '', '973-2922', '', 'Thulusdhoo, Maldives', 'https://samuramaldives.com/', 1, 6, 4.7, 240, NULL, 35, 'all', 'Maldives', 'Guest house', 0, NULL, '2026-04-30 09:45:17', 0),
(789, 3, 'ChIJRUQIkD6pODsRdojaraopadY', 'Plumeria Maldives', '', '933-9441', 'reservations@plumeriamaldives.com', 'Moony Night, Thinadhoo, Maldives', 'https://www.plumeriamaldives.com/', 1, 7, 4.5, 477, NULL, 35, 'all', 'Maldives', 'Guest house', 0, NULL, '2026-04-30 09:45:23', 1),
(790, 3, 'ChIJxxTCLpqpQDsRchJ80XlKWLA', 'Madi Grand Maldives', '', '931-1221', '', 'Odi Balaa Magu, Near Community E-Centre, Odi Balaa Magu, Fulidhoo 10010, Maldives', 'https://www.madigrand.com/', 1, 8, 4.8, 84, NULL, 27, 'all', 'Maldives', 'Guest house', 0, NULL, '2026-04-30 09:45:25', 0),
(791, 3, 'ChIJUwHYcgiZqTMRS0t5hwMbMAE', 'Cebu Westown Lagoon', '', '0917 149 2021', '', 'Mantawi Drive, Subangdaku, 6014 Cebu N Rd, Tipolo, Mandaue, 6014 Cebu, Philippines', 'http://cebuwestownlagoon.com/', 1, 2, 4.2, 1945, NULL, 35, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:45:57', 0),
(792, 3, 'ChIJxfT9I1WdqTMRiqcYGYapnLw', 'NUSTAR Resort Cebu', '', '(032) 888 8282', 'contactus@nustar.com.ph', 'Kawit Island, Cebu City, 6000 Cebu, Philippines', 'https://nustar.ph/', 1, 3, 4.6, 3376, NULL, 35, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:45:59', 0),
(793, 3, 'ChIJjce2kPuWqTMRYP6-ZLEpvHw', 'Be Resort Mactan', '', '(032) 236 8888', 'becomm@beresorts.com', 'Punta Engaño Rd, Lapu-Lapu, Cebu, Philippines', 'https://beresortmactan.com/', 1, 4, 4.1, 1773, NULL, 35, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:46:01', 0),
(794, 3, 'ChIJc6C-uNqZqTMRY7ZWbEJDbQg', 'Cube9 Resort and Spa', '', '0995 759 5924', '', 'Hadsan Cove, Agus, Lapu-Lapu, 6015 Cebu, Philippines', '', 0, 5, 4.4, 117, NULL, 85, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:46:03', 0),
(795, 3, 'ChIJK3Gx-OGWqTMROX_qKq-4KGs', 'Shangri-La Mactan, Cebu', '', '(032) 231 0288', 'mactan@shangri-la.com', 'Punta Engaño Rd, Lapu-Lapu City, Cebu, 6015 Cebu, Philippines', 'http://www.shangri-la.com/cebu/mactanresort/', 1, 6, 4.6, 11751, NULL, 35, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:46:04', 0),
(796, 3, 'ChIJ8YGNzzyZqTMRqErRGAqxdck', 'Waterfront Cebu City Hotel & Casino', '', '(032) 232 6888', 'whc.rsvn@waterfronthotels.net', 'Salinas Dr, Lahug, Cebu City, 6000 Cebu, Philippines', 'http://www.waterfronthotels.com.ph/', 1, 7, 4.3, 6926, NULL, 35, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:46:09', 0),
(797, 3, 'ChIJH5u-OnecqTMRlls-lYZ7n4M', 'Fili Hotel at NUSTAR Resort Cebu', '', '(032) 888 8282', 'contactus@nustar.com.ph', 'Kawit Island, Tower 1, Cebu City, 6000 Cebu, Philippines', 'https://www.nustar.ph/hotels/fili', 1, 8, 4.3, 311, NULL, 35, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:46:12', 0),
(798, 3, 'ChIJBWNR6gehqTMRil23iZhMJeE', 'Mist Mountain Resort', '', '0998 295 9408', '', 'Taptap, Cebu City, 6000 Cebu, Philippines', 'https://www.facebook.com/MistMountainResort/', 0, 9, 4.7, 118, NULL, 85, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:46:15', 0),
(799, 3, 'ChIJT2EdT3GZqTMR23I5HKvPEZE', 'Cebu Family Suites powered by Cocotel', '', '0919 069 5748', 'csr@cocotel.com', '27 P. Almendras St, Cebu City, 6000 Cebu, Philippines', 'https://www.cocotel.com/ph/cebu-family-suites-by-cocotel-powered-by-fave', 1, 10, 4.2, 73, NULL, 27, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:46:18', 0),
(800, 3, 'ChIJM-jk5EuZqTMR8U2RqVFRPLg', 'Hamersons Hotel Cebu', '', '', 'reservations@cebu-hamersonshotels.com', '0337 Don Mariano Cui St, Cebu City, 6000 Cebu, Philippines', 'https://cebu-hamersonshotels.com/', 1, 11, 4.1, 396, NULL, 30, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:46:20', 0),
(801, 3, 'ChIJscevspmZqTMR5Obr8qGoQ4o', 'Cebu Quincentennial Hotel', '', '(032) 520 4488', '', 'Cardinal Rosales Ave. cor Pope John Paul II Ave (formerly, 23 Minore Park, Juan Luna Avenue, Cebu City, 6000 Cebu, Philippines', 'https://cebuquincentennialhotel.com/', 1, 12, 4.4, 130, NULL, 35, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:46:22', 0),
(802, 3, 'ChIJX7j-8cyeqTMRhWku8qaoRxM', 'Crown Regency Residences Cebu', '', '(032) 255 7541', '', '8VFM+Q79, V Rama Ave, Guadalupe, Cebu City, 6000 Cebu, Philippines', '', 0, 13, 3.5, 394, NULL, 75, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:46:25', 0),
(803, 3, 'ChIJmSi050WaqTMRbVsvXrt-wQM', 'Alta Cebu Resort, Mactan', '', '(032) 496 7399', '', 'Cordova, 6017 Cebu, Philippines', '', 0, 14, 3.9, 790, NULL, 75, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:46:26', 0),
(804, 3, 'ChIJ5-_YihWZqTMRCOZKasfVLH0', 'Tubod Flowing Water Resort', '', '(032) 383 1533', '', 'Upper Pakigne Tubod Minglanilla Cebu City, Cebu City, Cebu, Philippines', 'http://www.tubodflowing.weebly.com/', 1, 15, 4.0, 558, NULL, 35, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:46:31', 0),
(805, 3, 'ChIJCbpCDmCfqTMRd1MMY7nuWw8', 'Serenity Farm and Resort Busay', '', '0917 726 0775', '', 'Sitio Tiguib Bridge, Malubog, Cebu City, 6000 Cebu, Philippines', 'https://www.facebook.com/serenityfarmresortbusay/', 0, 16, 4.3, 571, NULL, 85, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:46:33', 0),
(806, 3, 'ChIJI7V-9DhlqTMRhnL364aECaI', 'La Joya Farm Resort & Spa', '', '(032) 402 0533', 'reservations@lajoyafarmcebu.com', 'Jose G. Escario Street, Rosario, Aloguinsan, 6040 Cebu, Philippines', 'http://lajoyafarmcebu.com/', 1, 17, 4.5, 92, NULL, 27, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:46:36', 0);
INSERT INTO `lead_gen_results` (`id`, `user_id`, `place_id`, `name`, `owner_name`, `phone`, `email`, `address`, `website`, `has_website`, `api_calls`, `rating`, `ratings_total`, `price_level`, `opportunity_score`, `search_mode`, `location`, `industry`, `imported`, `lead_id`, `created_at`, `website_found_by_crawler`) VALUES
(807, 3, 'ChIJV82PYeiYqTMRsCj6_VoCUDg', 'Asmara Urban Resort & Lifestyle Village Inc.', '', '0917 708 1079', 'info@asmararesort.com', '8WV6+W89, Paseo Saturnino, Cebu City, 6000 Cebu, Philippines', 'https://www.asmaraurbanresort.com/', 1, 18, 4.3, 350, 2, 43, 'all', 'Cebu, Philippines', 'Resort', 0, NULL, '2026-04-30 09:46:37', 0),
(808, 3, 'ChIJ8bChomNt-TIRmCWLu1JwtZI', 'Ta-Cow Davao', '', '0999 888 0958', '', 'Corner Lapu-Lapu St, Porras St, Poblacion District, Davao City, 8000 Davao del Sur, Philippines', 'https://thefatcowgroup.com/', 1, 2, 4.8, 3125, NULL, 35, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:47:35', 0),
(809, 3, 'ChIJmQLStApt-TIRgGcsk-U3cls', 'The Fat Cow', '', '0917 888 8312', '', 'V. Mapa St, Poblacion District, Davao City, Davao del Sur, Philippines', 'https://thefatcowgroup.com/', 1, 3, 4.8, 2348, 2, 43, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:47:37', 0),
(810, 3, 'ChIJUcAWGjZs-TIRST-MBQwn1Zs', 'Vikings Luxury Buffet SM Lanang Davao', '', '(02) 8845 4647', 'user@domain.com', 'Upper Ground Level, The Fountain Court, SM Lanang Premiere, J.P. Laurel Ave, Agdao District, Davao City, Davao del Sur, Philippines', 'http://vikings.ph/', 1, 4, 4.8, 23350, 3, 50, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:47:38', 0),
(811, 3, 'ChIJ6Xhpp3dt-TIRpTWfKdRe7bc', 'Davao Dencia\'s Restaurant', '', '(082) 226 4336', '', 'General Luna St, Poblacion District, Davao City, Davao del Sur, Philippines', '', 0, 5, 4.3, 1052, 2, 93, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:47:42', 0),
(812, 3, 'ChIJN26rayBs-TIRa5-Aj6ufARE', 'Garden Bay Restaurant & Resort', '', '(082) 234 8491', '', 'Maryknoll Dr, Lanang, Davao City, 8000 Davao del Sur, Philippines', '', 0, 6, 4.7, 2706, 2, 93, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:47:44', 0),
(813, 3, 'ChIJmVKqCVxt-TIROsQL0glR6Ng', 'Jack\'s Ridge', '', '(082) 297 8830', '', '117 Shrine Hills Rd, Talomo, Davao City, Davao del Sur, Philippines', 'http://www.jacksridgedavao.com/', 1, 7, 4.4, 3645, 2, 43, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:47:50', 0),
(814, 3, 'ChIJCwGF1URz-TIRFADwKUGTL3M', 'Made Simple', '', '(082) 315 7800', '', '2nd Floor, CT DRive, Tulip Dr, Matina, Davao City, 8000 Davao del Sur, Philippines', 'http://facebook.com/MADESIMPLERESTAURANT', 0, 8, 4.9, 534, NULL, 85, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:47:53', 0),
(815, 3, 'ChIJAQAAu5dy-TIRaGoJFT5WyU4', 'Tong Yang, SM City Davao Ecoland', '', '0917 716 6888', 'user@domain.com', 'Ground Floor, Annex, Annex, Quimpo Blvd, Talomo, Davao City, 8000 Davao del Sur, Philippines', 'https://www.vikings.ph/tongyang', 1, 9, 4.9, 6120, 2, 43, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:47:54', 0),
(816, 3, 'ChIJyfrn2Xlt-TIRBfL6mQ-l8Sc', 'Bondi&Bourke Davao', '', '0998 593 3763', '', '115 P Pelayo St, Poblacion District, Davao City, 8000 Davao del Sur, Philippines', 'http://www.bondiandbourke.com/', 1, 10, 4.5, 473, 3, 50, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:47:59', 0),
(817, 3, 'ChIJg5Lgmwpt-TIRuVQW_pyDpjM', 'Rekado Filipino Comfort Cuisine', '', '(082) 224 3031', '', '1050 Emilio Jacinto Ext, Poblacion District, Davao City, 8000 Davao del Sur, Philippines', 'https://m.facebook.com/rekadodavao/', 0, 11, 4.4, 712, 2, 93, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:48:01', 0),
(818, 3, 'ChIJL0ixLcJt-TIRR8xhu9arQHQ', 'Cafe Tavera', '', '', '', '44 Avanceña St, Poblacion District, Davao City, Davao del Sur, Philippines', '', 0, 12, 4.7, 142, NULL, 80, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:48:03', 0),
(819, 3, 'ChIJs1DWLlJt-TIR3tB_MHpQenA', 'Asian Cow by Chef Patrick Co', '', '0917 133 2389', '', '1020 Mabini Place, Corner Quirino and, Mabini St, Poblacion District, Davao City, Davao del Sur, Philippines', 'http://thefatcowgroup.com/', 1, 13, 4.8, 1885, 2, 43, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:48:04', 0),
(820, 3, 'ChIJ-XXVVlhz-TIR6CM1ZEj76zw', 'Sky View Restaurant', '', '0917 621 1565', '', '3J42+Q4M, Quimpo Blvd, Ecoland, Davao City, Davao del Sur, Philippines', '', 0, 14, 4.3, 68, NULL, 77, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:48:06', 0),
(821, 3, 'ChIJVVVVVVUR-TIRkuyUU_got4U', 'Yellow Fin Seafood Restaurant', '', '(082) 297 8777', 'contact@foodplacee.com', 'Sandawa Plaza, Quimpo Blvd, Talomo, Davao City, 8000 Davao del Sur, Philippines', 'https://yellowfinseafoodrestaurant.shop/', 1, 15, 4.4, 829, 2, 43, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:48:07', 0),
(822, 3, 'ChIJ0QfkFHht-TIRgIPNjVTc9LA', 'De Bonte Koe European Bar and Restaurant', '', '(082) 317 2217', 'manager@debontekoe.ph', 'Habana Compound, 29 Rizal St, Poblacion, Lungsod ng Dabaw, 8000 Lalawigan ng Davao del Sur, Philippines', 'http://www.debontekoe.ph/', 1, 16, 4.6, 242, NULL, 35, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:48:17', 0),
(823, 3, 'ChIJK0XcaABt-TIROGrq5Y8UAYk', 'La Fle e Ristorante & Lounge', '', '0954 470 2043', '', '2F, SK Complex Building, J.P. Laurel Ave, Bajada, Davao City, 8000 Davao del Sur, Philippines', '', 0, 17, 4.7, 216, NULL, 85, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:48:18', 0),
(824, 3, 'ChIJ1b06m0xt-TIRp11lg_TVsLk', 'MISA Dining & Listening Room', '', '0998 979 1889', '', '1st floor, West JBT Land & Realty Corp. Bldg, Insular Village, Phase 2 Brgy. Hizon, Lanang, Davao City, 8000 Davao del Sur, Philippines', '', 0, 18, 4.7, 66, NULL, 77, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:48:20', 0),
(825, 3, 'ChIJ2Q103Aht-TIRcof-GvkorKY', 'Benjarong Bar and Restaurant Davao', '', '0905 562 1370', 'customerservice@dusit.com', 'Stella Hizon Reyes Rd, Bo. Pampanga, Davao City, 8000 Davao del Sur, Philippines', 'https://www.dusit.com/dusitthani-residencedavao/dining', 1, 19, 4.4, 59, NULL, 27, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:48:24', 0),
(826, 3, 'ChIJXWLn2k1t-TIRmM31aYllUX8', 'MarinaTuna', '', '0917 704 9863', '', 'Bo. Pampanga, Km. 8 Davao Agusan Road, Lanang, Davao City, Davao del Sur, Philippines', 'https://marinatuna.shop/', 1, 20, 4.5, 947, 2, 43, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:48:26', 0),
(827, 3, 'ChIJUXgazXVt-TIRjUEGEhvnWDY', 'Kalye\'t-Kusina Resto', '', '0939 198 0508', '', 'Pelayo 918 Pelayo Compound J.Camus Extension Corner, J. Abad Santos St, Poblacion District, Davao City, 8000 Davao del Sur, Philippines', '', 0, 21, 4.7, 303, 2, 93, 'all', 'Davao, Philippines', 'Restaurant', 0, NULL, '2026-04-30 09:48:28', 0),
(828, 3, 'ChIJo-HVqJGFsUcRAU8pknByUkQ', 'CompanyCheck Deutschland GmbH', '', '040 999996010', 'info@ccd-mail.de', 'Paul-Nevermann-Platz 5, 22765 Hamburg, Germany', 'https://companycheck-deutschland.de/arbeitsmedizin-hamburg/', 1, 2, 4.6, 217, NULL, 35, 'all', 'Altona, Germany', 'Private Limited Company', 0, NULL, '2026-04-30 09:50:07', 0),
(829, 3, 'ChIJF2F4YXuPsUcRVkkIPu1R5K0', 'altona Diagnostics', '', '040 54806760', 'info@altona-diagnostics.com', 'Virchowstraße 17, 22767 Hamburg, Germany', 'https://www.altona-diagnostics.com/', 1, 3, 4.9, 7, NULL, 15, 'all', 'Altona, Germany', 'Private Limited Company', 0, NULL, '2026-04-30 09:50:10', 0),
(830, 3, 'ChIJLx5a0h27Y0ERuZgYVabQimw', 'ArcelorMittal Hamburg', '', '040 74080', '', 'Dradenaustraße 33, 21129 Hamburg, Germany', 'https://hamburg.arcelormittal.com/', 1, 4, 3.1, 202, NULL, 25, 'all', 'Altona, Germany', 'Private Limited Company', 0, NULL, '2026-04-30 09:50:21', 0),
(831, 3, 'ChIJKdIjyQ6PsUcRcWpdRKDOQbo', 'Foviagenx Holdings Limited', '', '040 88366160', 'info@foviatech.com', 'Raboisen 32, 20095 Hamburg, Germany', 'http://www.foviatech.com/', 1, 5, NULL, 0, NULL, 5, 'all', 'Altona, Germany', 'Private Limited Company', 0, NULL, '2026-04-30 09:50:23', 0),
(832, 3, 'ChIJ3THf8ICFsUcRQ4v2hauw7pc', 'GBE brokers – Germany – Branch Office', '', '040 605901040', 'info@gbebrokers.com', 'Große Elbstraße 145B, 22767 Hamburg, Germany', 'https://www.gbebrokers.com/', 1, 6, 4.9, 614, NULL, 35, 'all', 'Altona, Germany', 'Private Limited Company', 0, NULL, '2026-04-30 09:50:24', 0),
(833, 3, 'ChIJM9rYtQKPsUcRnSQF5vl3tB0', 'Dornier Suntrace GmbH', '', '040 80903540', '', 'Große Elbstraße 145C, 22767 Hamburg, Germany', 'https://www.dornier-group.com/renewables/', 1, 7, 5.0, 4, NULL, 15, 'all', 'Altona, Germany', 'Private Limited Company', 0, NULL, '2026-04-30 09:50:29', 0),
(834, 3, 'ChIJAX4YAaaOsUcRMxMsi4eXHQs', 'FlowShare', '', '040 22858147', '', 'Am Sandtorkai 32, 20457 Hamburg, Germany', 'https://getflowshare.com/', 1, 8, 5.0, 20, NULL, 21, 'all', 'Altona, Germany', 'Private Limited Company', 0, NULL, '2026-04-30 09:50:33', 0),
(835, 3, 'ChIJ0XyABCyJsUcRfk3rJiXKm6k', 'YOOtheme', '', '', '', 'Hongkongstraße 10a, 20457 Hamburg, Germany', 'https://yootheme.com/', 1, 9, 4.2, 32, NULL, 22, 'all', 'Altona, Germany', 'Private Limited Company', 0, NULL, '2026-04-30 09:50:36', 0),
(836, 3, 'ChIJW7IRzeyOsUcRjMta_7lfXyA', 'LESER GmbH & Co. KG', '', '040 25165100', '', 'Wendenstraße 133-135, 20537 Hamburg, Germany', 'https://www.leser.com/', 1, 10, 4.3, 21, NULL, 21, 'all', 'Altona, Germany', 'Private Limited Company', 0, NULL, '2026-04-30 09:50:39', 0),
(837, 3, 'ChIJmQzuWTGjY0ERmy1Zr4ID_oo', 'Zim Germany GmbH & Co KG', '', '0800 1777711', '', 'Hammerbrookstraße 90, 20097 Hamburg, Germany', '', 0, 11, 4.2, 5, NULL, 65, 'all', 'Altona, Germany', 'Private Limited Company', 0, NULL, '2026-04-30 09:50:41', 0),
(838, 3, 'ChIJX7V9kYPL1IkRapBqEEiKRCU', 'Risingmax Pvt. Ltd. - IT Development & Consulting Company | Custom App & Software Development Services Provider', '', '(917) 451-3717', 'idea@risingmax.com', '151 Yonge St 9th floor, Toronto, ON M5C 2W7, Canada', 'https://www.risingmax.com/?utm_source=seo&utm_medium=organic&utm_campaign=canada', 0, 2, 5.0, 1, NULL, 65, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:51:50', 0),
(839, 3, 'ChIJDzqL4E89K4gRRwYVM_QXTQA', 'Diamant Company LTD', '', '(866) 363-4262', '%20info@diamantcompany.com', '3650 Weston Rd Unit 16, North York, ON M9L 1W2, Canada', 'http://www.diamantgrp.com/', 1, 3, 4.9, 49, NULL, 27, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:51:54', 0),
(840, 3, 'ChIJH5okqzbL1IkRdMB86Evgkac', 'Net Solutions Canada Limited', '', '(416) 720-1790', 'info@netsolutions.com', '111 Queen St E Building Suite 450, Toronto, ON M5C 1S2, Canada', 'https://www.netsolutions.com/', 1, 4, 5.0, 3, NULL, 15, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:52:01', 0),
(841, 3, 'ChIJlxoeWtA0K4gRakYqAYqXmrc', 'Kira Inc', '', '(888) 710-3454', '', '370 King St W #500, Toronto, ON M5V 1J9, Canada', 'https://kirasystems.com/', 1, 5, 4.7, 17, NULL, 21, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:52:04', 0),
(842, 3, 'ChIJ-5UUcjfL1IkR-Fth5oT6aI4', 'Suffescom Solutions Pvt. Ltd.', '', '', '', '151 Yonge St 11th Floor, Toronto, ON M5C 2W7, Canada', 'https://www.suffescom.co/', 1, 6, NULL, 0, NULL, 0, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:52:07', 0),
(843, 3, 'ChIJvasA3DczK4gRzLiW-stihos', 'JIG Technologies', '', '(416) 850-2684', 'info@jig.to', '250 Merton St, Toronto, ON M4S 1B1, Canada', 'https://jigtechnologies.com/', 1, 7, 4.8, 34, NULL, 27, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:52:08', 0),
(844, 3, 'ChIJT3pz_d00K4gRoqeVUT6iPF4', 'Nulogy Corporation', '', '(416) 204-0427', '', '480 University Ave #1200, Toronto, ON M5G 1V2, Canada', 'https://nulogy.com/', 1, 8, 4.0, 25, NULL, 21, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:52:13', 0),
(845, 3, 'ChIJNXThfzLL1IkR_iXWDXL7Z3c', 'Blanc Labs', '', '(647) 994-5465', 'info@blanclabs.com', '67 Yonge St #1501, Toronto, ON M5E 1J8, Canada', 'http://blanclabs.com/', 1, 9, 4.7, 11, NULL, 21, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:52:19', 0),
(846, 3, 'ChIJu1XWrvw4K4gRfuEBs5Molew', 'Tata Consultancy Services', '', '(647) 790-7200', '', '400 University Ave 25th Floor, Toronto, ON M5G 1S5, Canada', 'http://www.tcs.com/', 1, 10, 4.1, 154, NULL, 35, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:52:19', 0),
(847, 3, 'ChIJX7V9kYPL1IkRapBqEEiKRCU', 'Risingmax Pvt. Ltd. - IT Development & Consulting Company | Custom App & Software Development Services Provider', '', '(917) 451-3717', 'idea@risingmax.com', '151 Yonge St 9th floor, Toronto, ON M5C 2W7, Canada', 'https://www.risingmax.com/?utm_source=seo&utm_medium=organic&utm_campaign=canada', 0, 2, 5.0, 1, NULL, 65, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:53:14', 0),
(848, 3, 'ChIJH5okqzbL1IkRdMB86Evgkac', 'Net Solutions Canada Limited', '', '(416) 720-1790', 'info@netsolutions.com', '111 Queen St E Building Suite 450, Toronto, ON M5C 1S2, Canada', 'https://www.netsolutions.com/', 1, 3, 5.0, 3, NULL, 15, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:53:21', 0),
(849, 3, 'ChIJlxoeWtA0K4gRakYqAYqXmrc', 'Kira Inc', '', '(888) 710-3454', '', '370 King St W #500, Toronto, ON M5V 1J9, Canada', 'https://kirasystems.com/', 1, 4, 4.7, 17, NULL, 21, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:53:24', 0),
(850, 3, 'ChIJ-5UUcjfL1IkR-Fth5oT6aI4', 'Suffescom Solutions Pvt. Ltd.', '', '', '', '151 Yonge St 11th Floor, Toronto, ON M5C 2W7, Canada', 'https://www.suffescom.co/', 1, 5, NULL, 0, NULL, 0, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:53:26', 0),
(851, 3, 'ChIJvasA3DczK4gRzLiW-stihos', 'JIG Technologies', '', '(416) 850-2684', 'info@jig.to', '250 Merton St, Toronto, ON M4S 1B1, Canada', 'https://jigtechnologies.com/', 1, 6, 4.8, 34, NULL, 27, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:53:28', 0),
(852, 3, 'ChIJT3pz_d00K4gRoqeVUT6iPF4', 'Nulogy Corporation', '', '(416) 204-0427', '', '480 University Ave #1200, Toronto, ON M5G 1V2, Canada', 'https://nulogy.com/', 1, 7, 4.0, 25, NULL, 21, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:53:32', 0),
(853, 3, 'ChIJNXThfzLL1IkR_iXWDXL7Z3c', 'Blanc Labs', '', '(647) 994-5465', 'info@blanclabs.com', '67 Yonge St #1501, Toronto, ON M5E 1J8, Canada', 'http://blanclabs.com/', 1, 8, 4.7, 11, NULL, 21, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:53:35', 0),
(854, 3, 'ChIJu1XWrvw4K4gRfuEBs5Molew', 'Tata Consultancy Services', '', '(647) 790-7200', '', '400 University Ave 25th Floor, Toronto, ON M5G 1S5, Canada', 'http://www.tcs.com/', 1, 9, 4.1, 154, NULL, 35, 'all', 'Toronto, Canada', 'Private Limited Company', 0, NULL, '2026-04-30 09:53:35', 0),
(855, 3, 'ChIJibhxRe_3qjsRszkdr4Oq5cI', 'Atheenapandian private limited - Srirangam branch', '', '', 'atheenapandian@gmail.com', '190/81, W Adayavalanjan St, Srirangam, Melavasal, Srirangam, Tiruchirappalli, Tamil Nadu 620006, India', 'https://www.atheenapandian.com/', 1, 2, 5.0, 105, NULL, 30, 'all', 'Srirangam, India', 'Private Limited', 0, NULL, '2026-04-30 14:12:03', 0),
(856, 3, 'ChIJzROAUlX1qjsRO68r1YfT4pY', 'Mahajwala International Private Limited', '', '075986 75973', 'postbox@mahajwala.com', '100/C1, Gandhi Rd, Srirangam, Tiruchirappalli, Tamil Nadu 620006, India', 'https://www.mahajwala.com/', 1, 3, 5.0, 6, NULL, 15, 'all', 'Srirangam, India', 'Private Limited', 0, NULL, '2026-04-30 14:12:05', 0),
(857, 3, 'ChIJi7lqIjb3qjsR4eMN2VpzmG0', 'VSA Tech Solutions Pvt Ltd', '', '099009 01223', '', 'No 20 MRR Garden, Srirangam, Tiruchirappalli, Tamil Nadu 620005, India', 'http://vsatechsolutions.com/', 1, 4, 5.0, 3, NULL, 15, 'all', 'Srirangam, India', 'Private Limited', 0, NULL, '2026-04-30 14:12:06', 0),
(858, 3, 'ChIJq6qqqi_0qjsRC8kcA92N-vE', 'A.S. Power Solutions Pvt Ltd.', '', '088701 01121', '', 'No.3, Karthikeyan Garden, Srirangam, Thiruvanaikoil, Tiruchirappalli, Tamil Nadu 620005, India', 'http://aspowerpvt.com/', 1, 5, 4.8, 5, NULL, 15, 'all', 'Srirangam, India', 'Private Limited', 0, NULL, '2026-04-30 14:12:06', 0),
(859, 3, 'ChIJZZnFmJD1qjsREsJXzVQBf_g', 'NextEnergy Solutions India Private Limited | Trichy', '', '099522 63280', '', '09, Ragavendra Garden, Sriramapuram, opposite to Sri Venkatesa Cinemas, Srirangam, Thiruvanaikoil, Tiruchirappalli, Tamil Nadu 620005, India', 'https://www.nextenergyindia.com/', 1, 6, 4.8, 86, NULL, 27, 'all', 'Srirangam, India', 'Private Limited', 0, NULL, '2026-04-30 14:12:09', 0),
(860, 3, 'ChIJFyqUU7r1qjsRrINgND3lOGw', 'Arthur Grand Technologies Private Limited', '', '', 'hello@arthurgrand.com', '169, Stony Meadows,1st Floor 10th Main Road, Ponnagar Extension, Karumandapam, Tiruchirappalli, Tamil Nadu 620001, India', 'http://www.arthurgrand.com/', 1, 2, 4.5, 28, NULL, 16, 'all', 'Tiruchirappalli, India', 'Private Limited', 0, NULL, '2026-04-30 14:12:39', 0),
(861, 3, 'ChIJgRvBJd_0qjsRs1_Gl1l311w', 'Astonish InfoTech Private Limited', '', '099949 54031', 'admin@astonishinfotech.com', 'G-7, Technology Park (BUTP), Bharathidasan University, Kajamalai Campus, near Law College, Tiruchirappalli, Tamil Nadu 620023, India', 'https://astonishinfotech.com/', 1, 3, 4.4, 122, NULL, 35, 'all', 'Tiruchirappalli, India', 'Private Limited', 0, NULL, '2026-04-30 14:12:39', 0),
(862, 3, 'ChIJS_RhvUL1qjsR-eQn_NlPbEQ', 'Harihar Alloys Pvt. Ltd', '', '', 'marketing@hariharalloy.com', '6 Thomas Street, 2nd Floor, Race Course Road, Kaja Nagar, Tiruchirappalli, Tamil Nadu 620020, India', 'http://hariharalloys.com/', 1, 4, 4.9, 8, NULL, 10, 'all', 'Tiruchirappalli, India', 'Private Limited', 0, NULL, '2026-04-30 14:12:40', 0),
(863, 3, 'ChIJe75oaG31qjsR9Xj47xD26tY', 'Accentra Technologies (India) Pvt. Ltd', '', '098424 18187', '', '38, 3rd Main Rd, Jaya Nagar Extension, Ponnagar Extension, Karumandapam, Tiruchirappalli, Tamil Nadu 620001, India', 'http://www.accentra.co.in/', 1, 5, 4.5, 17, NULL, 21, 'all', 'Tiruchirappalli, India', 'Private Limited', 0, NULL, '2026-04-30 14:12:44', 0),
(864, 3, 'ChIJW5MvxIL1qjsRUmfAPQRbdg0', 'Integrass Solutions India Pvt Ltd', '', '0431 406 0537', 'info@integrass.com', '15/9, 6th Main Rd, Srinivase Nagar North, Srinivasa Nagar North, Srinivasa Nagar, Tiruchirappalli, Tamil Nadu 620017, India', 'http://www.integrass.com/', 1, 6, 4.7, 39, NULL, 27, 'all', 'Tiruchirappalli, India', 'Private Limited', 0, NULL, '2026-04-30 14:12:50', 0),
(865, 3, 'ChIJ4YdDXn5ZqDsR5slH2iOoFzg', 'LuLu Hypermarket Coimbatore', '', '0422 691 1001', '', 'Lakshmi Mills 627, B1A, Avinashi Rd, GM Nagar, Pudur, Pappanaickenpalayam, Coimbatore, Tamil Nadu 641037, India', '', 0, 2, 4.0, 9410, NULL, 85, 'all', 'Coimbatore, India', 'Grocery store', 0, NULL, '2026-04-30 14:14:32', 0),
(866, 3, 'ChIJk9ScV_xYqDsR2keWz0utenA', 'Smart Bazaar', '', '', '', 'Savithri, XXXJ+GV6 Unitea Center, 3, Race Course Rd, Mamarath Thottom Ganapathy, Race Course, Coimbatore, Tamil Nadu 641018, India', 'https://reliancesmartbazaar.com/', 1, 3, 3.9, 7072, NULL, 20, 'all', 'Coimbatore, India', 'Grocery store', 0, NULL, '2026-04-30 14:14:32', 0),
(867, 3, 'ChIJnWvF7x5ZqDsR534AMitkpJU', 'NILGIRIS SUPER MARKET - SNV HOLDINGS PRIVATE LIMITED-', '', '090033 90023', '', '103-107, THIRUVENKATA SWAMY ROAD, R.S. Puram, Coimbatore, Tamil Nadu 641002, India', '', 0, 4, 4.2, 4876, NULL, 85, 'all', 'Coimbatore, India', 'Grocery store', 0, NULL, '2026-04-30 14:14:34', 0),
(868, 3, 'ChIJR8qVyVVYqDsRUOd2n09qJ1g', 'Doraisingh Supermarket', '', '098430 78430', '', '1031, 1038, Cross Cut Rd, Gandhipuram, Coimbatore, Tamil Nadu 641012, India', '', 0, 5, 4.2, 506, NULL, 85, 'all', 'Coimbatore, India', 'Grocery store', 0, NULL, '2026-04-30 14:14:36', 0),
(869, 3, 'ChIJQWYWPIZZqDsRbShgYAYcT24', 'Dennis Hyper Market', '', '', '', 'Dennis Hyper Market, Trichy Rd, opposite Kia Motors, Krishna Colony, Coimbatore, Tamil Nadu 641005, India', 'https://www.dennishypermarket.com/', 1, 6, 4.3, 1418, NULL, 30, 'all', 'Coimbatore, India', 'Grocery store', 0, NULL, '2026-04-30 14:14:40', 1),
(870, 3, 'ChIJq1ISbt2XuEwRJPQdTYtJcBk', 'Randstad Canada', '', '(418) 525-6766', 'catherine.villeneuve@randstad.ca', '815 Bd Lebourgneuf, Québec, QC G2J 0C1, Canada', 'https://www.randstad.ca/fr/jobs/quebec/quebec/?utm_source=google&utm_medium=organic&utm_campaign=gmb&utm_content=quebec', 1, 2, 4.5, 138, NULL, 35, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:48:32', 0),
(871, 3, 'ChIJG5WGhDKRuEwRy20lMEvkScY', 'Robert Half® Agence de placement', '', '(418) 579-4070', 'sample@test.com', 'Tour 1, 2828 Boul Laurier Suite 700, Office 706, Québec City, QC G1V 0B9, Canada', 'https://www.roberthalf.com/ca/fr/nos-bureaux/qc-ville-de-quebec?utm_source=gmb_listing&utm_medium=organic&utm_campaign=local_listing', 1, 3, 4.8, 21, NULL, 21, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:48:36', 0),
(872, 3, 'ChIJIXaAdHGWuEwRcKGcSCWuqv8', 'Adecco', '', '(418) 523-9922', 'u003e@adecco.ca', '305 Boulevard Charest E Bureau 200, Québec, QC G1K 3H3, Canada', 'https://www.adecco.com/en-ca/locations/quebec/quebec-city/adecco-quebec-city?utm_source=gmb&utm_medium=yext', 1, 4, 4.5, 92, NULL, 27, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:48:39', 0),
(873, 3, 'ChIJDcB-cDKRuEwRo88TyYABkSY', 'Les Services de gestion Quantum limitée', '', '(418) 621-8800', 'info@quantum.ca', '3107 Av. des Hôtels, Québec, QC G1W 4W5, Canada', 'https://www.quantum.ca/', 1, 5, 5.0, 101, NULL, 35, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:48:41', 0),
(874, 3, 'ChIJB3XnuEQayUwRtZc5-7rKkz8', 'Randstad Canada', '', '(514) 350-0033', 'aida.kadiric@randstad.ca', '525 Av. Viger O, Montréal, QC H2Z 1G6, Canada', 'https://www.randstad.ca/fr/jobs/quebec/montreal/?utm_source=google&utm_medium=organic&utm_campaign=gmb&utm_content=montreal', 1, 6, 4.7, 598, NULL, 35, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:48:43', 0),
(875, 3, 'ChIJPeJx1TOYuEwRxyPAUNQwJOg', 'Placement De Personnel Maxsys', '', '(418) 622-4443', '', '1305 Bd Lebourgneuf Suite 101, Québec City, QC G2K 2E4, Canada', '', 0, 7, 4.3, 32, NULL, 77, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:48:45', 0),
(876, 3, 'ChIJNX0g1UYayUwRhsY1l7r65fs', 'Groupe RP', '', '(514) 844-4454', 'info@grouperp.ca', '1130 Rue Sherbrooke O Suite 1100, Montréal, QC H3A 2M8, Canada', 'https://grouperp.ca/', 1, 8, 4.9, 514, NULL, 35, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:48:48', 0),
(877, 3, 'ChIJv-t2iSqWuEwRA5GNV50q3ck', 'Drake International', '', '(418) 529-9371', '', '2828 Boul Laurier suite 700, Québec, QC G2K 0K9, Canada', 'https://ca.drakeintl.com/', 1, 9, 3.4, 20, NULL, 11, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:48:54', 0),
(878, 3, 'ChIJTZWQ2KkZyUwRnFgCx9KUKxQ', 'Canada Global Recruitment | Agence d\'emploi - Employment agency | Montréal', '', '(514) 894-6700', '', '5473 Royalmount Ave. office 207, Montreal, QC H4P 1J3, Canada', 'https://www.canada-global.ca/', 1, 10, 4.8, 110, NULL, 35, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:48:58', 0),
(879, 3, 'ChIJtX9OQsyWuEwR_K4twTRNExU', 'Aleanza Recrutement', '', '(418) 476-7898', '', '325 Rue de l\'Espinay bureau 500, Québec City, QC G1L 4L1, Canada', 'http://aleanza.com/', 1, 11, 4.8, 99, NULL, 27, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:49:06', 0),
(880, 3, 'ChIJqSJozVyXuEwRM_L2kPSBQTw', 'Job Alliance', '', '(418) 781-1924', '', '650 Rue Père-Marquette, Québec, QC G1S 1Z7, Canada', 'http://www.job-alliance.com/', 1, 12, 5.0, 9, NULL, 15, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:49:18', 0),
(881, 3, 'ChIJTQoYL_YXyUwRK8xn26nJtUQ', 'Aerotek', '', '(514) 798-6439', '', '9800 Boul. Cavendish Suite 120, Montreal, QC H4M 2V9, Canada', 'https://www.aerotek.com/en/locations/canada/quebec/montreal?ecid=ls_aero_bizlist_091222_seo7123133', 1, 13, 3.9, 123, NULL, 25, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:49:27', 0),
(882, 3, 'ChIJVxhZuiiXuEwR2ipltE7OFMw', 'Groupe RP', '', '(418) 692-9284', 'info@grouperp.ca', '1170 Bd Lebourgneuf Ste 408, Québec City, QC G2K 2E3, Canada', 'https://grouperp.ca/', 1, 14, 5.0, 3, NULL, 15, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:49:30', 0),
(883, 3, 'ChIJf07s97GRuEwRIOV8qNCZzc8', 'Recrutement International Québec', '', '(418) 651-0958', 'info@rinq.ca', '2750 Ch Ste-Foy, Porte 2 bur.268, Québec, QC G1V 1V6, Canada', 'http://www.rinq.ca/', 1, 15, 4.1, 9, NULL, 15, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:49:33', 0),
(884, 3, 'ChIJj3pBolwayUwRZTxhoadNKQk', 'Adecco', '', '(514) 847-1105', 'u003e@adecco.ca', '1001 Blvd. De Maisonneuve Ouest Suite 1200, Montreal, QC H3A 3C8, Canada', 'https://www.adecco.com/en-ca/locations/quebec/montreal/adecco-montreal?utm_source=gmb&utm_medium=yext', 1, 16, 4.3, 130, NULL, 35, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:49:36', 0),
(885, 3, 'ChIJxzq-IOOWuEwRtctQQsv29uI', 'Extra multi-ressources', '', '(418) 681-7173', '', '2160 Rue Lavoisier, Sainte-Foy–Sillery–Cap-Rouge, QC G1N 4E5, Canada', 'https://www.extraressources.ca/?utm_source=google&utm_medium=organic&utm_campaign=myBusiness&utm_term=bureau-ste-foy&utm_content=bureau-ste-foy', 1, 17, 3.9, 49, NULL, 17, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:49:45', 0),
(886, 3, 'ChIJCcLFsSiRuEwRWdTARKnxUrs', 'TalentWorld', '', '(418) 694-5293', 'info@talentworld.com', '5500 Bd des Galeries Local 300, Québec City, QC G2K 2E2, Canada', 'https://www.talentworld.com/', 1, 18, 4.6, 10, NULL, 21, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:49:47', 0),
(887, 3, 'ChIJRzevCvuXuEwRyVrlp0FpM7w', 'Placement Erisma', '', '(418) 683-7224', 'info@placementerisma.com', '450 Ave Saint-Jean-Baptiste suite 200, Québec City, QC G2E 6H5, Canada', 'https://placementerisma.com/', 1, 19, 4.0, 1, NULL, 15, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:49:52', 0),
(888, 3, 'ChIJNWkDi2OXuEwRkHLaInhEudo', 'Bédard ressources humaines', '', '(418) 977-3311', 'info@bedardressources.com', '2360 Ch Ste-Foy #390, Québec, QC G1V 4H2, Canada', 'https://www.bedardressources.com/', 1, 20, 4.1, 44, NULL, 27, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:49:55', 0),
(889, 3, 'ChIJi4nI30WWuEwRlGy3iepRh8I', 'GIT Services-conseils en emploi', '', '(418) 686-1888', 'gitcre@git.qc.ca', '245 Rue Soumande, Québec, QC G1M 3H6, Canada', 'https://www.git.qc.ca/', 1, 21, 4.9, 263, NULL, 35, 'all', 'Quebec, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:49:59', 0),
(890, 3, 'ChIJJWsOwQxDK4gRHlL_-TYWMRE', 'InVision Staffing', '', '(416) 923-0874', 'jobs2@invisionstaffing.ca', '2578 Bristol Cir #15, Oakville, ON L6H 6Z7, Canada', 'https://invisionstaffing.ca/', 1, 2, 4.8, 366, NULL, 35, 'all', 'Oakville, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:50:40', 0),
(891, 3, 'ChIJ3TCZFuhcK4gR6wVP-q8nzJM', 'AppleOne Employment Services - Oakville', '', '(905) 339-3333', '', '3027 Harvester Rd Suite 303B, Burlington, ON L7N 3G7, Canada', 'https://www.appleone.ca/', 1, 3, 4.8, 426, NULL, 35, 'all', 'Oakville, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:50:46', 0),
(892, 3, 'ChIJRVVC88pCK4gRx4ckIUdpquA', 'Summit Search Group', '', '(905) 257-9300', '', '2010 Winston Park Dr Suite 200, Oakville, ON L6H 6X7, Canada', 'http://www.summitsearchgroup.com/?utm_source=gb&utm_medium=organic', 1, 4, 4.9, 269, NULL, 35, 'all', 'Oakville, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:51:01', 0),
(893, 3, 'ChIJe70FIypDK4gR_r9QWWeUM_s', 'HealthOPM Staffing and Recruitment Agency', '', '(905) 491-6808', 'info@healthopm.com', '2275 Upper Middle Rd E Suite 101, Oakville, ON L6H 0C3, Canada', 'https://www.healthopm.com/', 1, 5, 5.0, 29, NULL, 21, 'all', 'Oakville, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:51:04', 0),
(894, 3, 'ChIJuR-5a6ZDK4gRnUUFtb_ceNw', 'Prestige Recruiting Solutions', '', '(289) 205-0437', 'enquiries@prestigerecruitingsolutions.com', '1011 Upper Middle Rd E C3, Oakville, ON L6H 5Z9, Canada', 'https://prestigerecruitingsolutions.com/?utm_source=organic&utm_medium=gbp&utm_campaign=search-egine', 1, 6, 4.8, 16, NULL, 21, 'all', 'Oakville, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:51:10', 0),
(895, 3, 'ChIJLer21ZBDK4gR_PQ18y7h19c', 'Workker', '', '(800) 922-1337', 'info@workkerapp.com', '1393 North Service Rd E Unit 104, Oakville, ON L6H 1A7, Canada', 'http://www.workkerapp.com/', 1, 7, 4.8, 105, NULL, 35, 'all', 'Oakville, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:51:11', 0),
(896, 3, 'ChIJK6lI8_9cK4gRLLdeakRcgwo', 'Raise', '', '(800) 567-9675', '', '610 Chartwell Rd Suite 101, Oakville, ON L6J 4A5, Canada', 'https://raiserecruiting.com/', 0, 8, 4.4, 118, NULL, 85, 'all', 'Oakville, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:51:29', 0),
(897, 3, 'ChIJhW4nDCRHK4gRetVoCestQ_A', 'Toper Temps Inc', '', '(905) 276-2792', '', '1670 North Service Rd E Suite 204, Oakville, ON L6H 7G3, Canada', 'http://www.topertemps.com/', 1, 9, 4.3, 39, NULL, 27, 'all', 'Oakville, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:51:35', 0),
(898, 3, 'ChIJ10Bq29JgK4gRxs8PslsAjmA', 'Recruiting Concepts Inc', '', '(905) 466-6948', '', '407 Iroquois Shore Rd, Oakville, ON L6H 1M3, Canada', 'http://www.recruitingconcepts.ca/', 1, 10, 4.7, 18, NULL, 21, 'all', 'Oakville, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:51:48', 0),
(899, 3, 'ChIJP3ymk99DK4gRtCoXvFf-Rmw', 'Patch Tech Staffing', '', '(905) 291-5599', 'info@patchstaffing.com', '1393 North Service Rd E Unit 104, Oakville, ON L6H 1A7, Canada', 'https://www.patchstaffing.com/', 0, 11, 4.9, 13, NULL, 71, 'all', 'Oakville, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:51:48', 0),
(900, 3, 'ChIJ-V8Wd4lDK4gRj8jZRrNCsNc', 'CPUS Engineering Staffing Solutions Inc.', '', '(905) 822-0471', 'info@cpus.ca', '710 Dorval Dr Suite 501, Oakville, ON L6K 3V7, Canada', 'http://www.cpus.ca/', 1, 12, 5.0, 2, NULL, 15, 'all', 'Oakville, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:51:51', 0),
(901, 3, 'ChIJvRD4NzhDK4gRz2dMhZtqQy8', 'Permasearch', '', '(905) 418-2040', 'info@permasearch.com', '1393 North Service Rd E Unit 104, Oakville, ON L6H 1A7, Canada', 'https://www.permasearch.com/', 1, 13, 5.0, 17, NULL, 21, 'all', 'Oakville, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:51:52', 0),
(902, 3, 'ChIJQdavWFVgK4gR3qNoKh6B60Q', 'Recruiting in Motion - Oakville/Burlington', '', '(905) 863-6428', 'sean@recruitinginmotion.com', '1075 North Service Rd W Suite 100, Oakville, ON L6M 2G2, Canada', 'https://www.recruitinginmotion.com/', 1, 14, NULL, 0, NULL, 5, 'all', 'Oakville, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:51:58', 0),
(903, 3, 'ChIJKTa16zxHK4gRz1do8Zgbal0', 'Elite Staffing Solutions Inc', '', '(905) 803-8045', '', '170 Robert Speck Pkwy # 202, Mississauga, ON L4Z 3G1, Canada', 'http://www.elitestaffing.ca/', 1, 15, 4.9, 69, NULL, 27, 'all', 'Oakville, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:52:06', 0),
(904, 3, 'ChIJneURBupcK4gR-DJgpZ863cA', 'Randstad Canada', '', '(905) 637-3473', 'samuel.fox@randstad.ca', '1100 Walkers Line, Burlington, ON L7N 2G3, Canada', 'https://www.randstad.ca/jobs/ontario/burlington/?utm_source=google&utm_medium=organic&utm_campaign=gmb&utm_content=burlington', 1, 16, 4.5, 493, NULL, 35, 'all', 'Oakville, Canada', 'Recruitment agency', 0, NULL, '2026-04-30 16:52:08', 0),
(905, 3, 'ChIJF_i9hQGf4jAR1zgH-laj95o', 'Michael Page Thailand - Recruitment Agency', '', '02 012 5000', 'enquiries@michaelpage.co.th', 'Bhiraj Tower at Emquartier 689, Level 41, Unit 4108, 4109 ถ. สุขุมวิท แขวงคลองตันเหนือ เขตวัฒนา กรุงเทพมหานคร 10110, Thailand', 'https://www.michaelpage.co.th/?utm_source=google&utm_medium=organic&utm_campaign=gmb&utm_content=Thailand', 1, 2, 4.9, 886, NULL, 35, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:53:33', 0),
(906, 3, 'ChIJJVTFPsud4jAR3jBh3lZ1dc4', 'Smartcruit Consultant Recruitment Co., Ltd.', '', '02 096 2225', 'info@smartcruit.co.th', '496-502 5th Floor Gaysorn Amarin Thanon Phloen Chit, Khwaeng Lumphini, Pathum Wan, Krung Thep Maha Nakhon 10330, Thailand', 'http://www.smartcruit.co/', 1, 3, 5.0, 744, NULL, 35, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:53:37', 0),
(907, 3, 'ChIJbfwrJy6f4jARdNtVQy9_dLI', 'Robert Walters Recruitment Agency Thailand', '', '02 344 4800', 'eastern.seaboard@robertwalters.com', '17th Floor, Unit 1702 Q House Lumpini 1 S Sathon Rd, Khwaeng Thung Maha Mek, Khet Sathon, Krung Thep Maha Nakhon 10120, Thailand', 'https://www.robertwalters.co.th/contact-us/thailand/bangkok.html?utm_source=gmb&utm_medium=organic&utm_campaign=bangkok', 1, 4, 4.7, 85, NULL, 27, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:53:39', 0),
(908, 3, 'ChIJ2zz47iyf4jARjCazst6i3QU', 'Manpower Thailand (Si Lom Branch)', '', '02 171 2323', '', '323, United Center, 46 th Floor, Unit 4604, Si Lom Road, Si Lom, Khet Bang Rak, Bangkok, 10500, แขวงสีลม เขตบางรัก กรุงเทพมหานคร 10500, Thailand', 'https://www.manpowerthailand.com/th?utm_source=google&utm_medium=gmb&utm_campaign=silom', 1, 5, 4.9, 57, NULL, 27, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:53:41', 0),
(909, 3, 'ChIJbxxX0c-e4jAR_vemnBlvhcg', 'HRnetOne Thailand', '', '085 911 1939', 'singapore@hrnetone.com', 'The Great Room Park, 1 Silom, Si Lom, Khet Bang Rak, Krung Thep Maha Nakhon 10500, Thailand', 'http://www.hrnetone.com/', 1, 6, 5.0, 284, NULL, 35, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:53:48', 0),
(910, 3, 'ChIJ_TgVzu-e4jAR6GmhpQviFm0', 'RECRUITdee (RDA Group Recruitment)', '', '02 258 3880', 'contact@recruitdee.com', 'RDA Group Recruitment Ltd 159/18, Unit 1105/1, 11th Floor, Serm-Mit Towers, ซอย สุขุมวิท 21 North, เขตคลองเตย กรุงเทพมหานคร 10110, Thailand', 'https://www.recruitdee.com/', 1, 7, 4.9, 101, NULL, 35, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:53:49', 0),
(911, 3, 'ChIJd6WtGeee4jARnSPtheDyL5Q', 'Peak Recruitment | Food & Agriculture - Thailand Office', '', '02 107 2698', 'info@peak-recruit.com', 'Sathorn Nakorn Tower Building 100/30-100/33 Unit No. 401 19th Floor, North Sathorn Road, แขวงสีลม เขตบางรัก กรุงเทพมหานคร 10500, Thailand', 'http://peak-recruit.com/', 1, 8, 4.9, 32, NULL, 27, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:53:52', 0),
(912, 3, 'ChIJ76dPn-ae4jARsCriZ5mq5c0', 'Elabram Recruitment Co., Ltd (Thailand)', '', '02 656 0088', '', '973, Unit 8B, 8th Floor, President Tower, ถนน เพลินจิต แขวงลุมพินี เขตปทุมวัน กรุงเทพมหานคร 10330, Thailand', 'https://elabram.com/th/index.html', 1, 9, 4.9, 149, NULL, 35, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:53:53', 0),
(913, 3, 'ChIJs404kNGZ4jARzcR2hf5nHos', 'ManpowerGroup Thailand (Head Office)', '', '02 171 2399', '', '31, 31/1, 33 Bhiraj Tower, Floor 2 S Sathon Rd, Khwaeng Yan Nawa, Khet Sathon, Krung Thep Maha Nakhon 10120, Thailand', 'https://www.manpowerthailand.com/th?utm_source=google&utm_medium=gmb&utm_campaign=hq', 1, 10, 4.9, 82, NULL, 27, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:53:56', 0),
(914, 3, 'ChIJoZKmCwSf4jARFout8Q0jt4s', 'JAC Recruitment Ltd.', '', '02 261 1270', 'thailand@jac-recruitment.co.th', '10F Emporium Tower 622 Soi Sukhumvit 24, Klongton, Klongtoey, Krung Thep Maha Nakhon 10110, Thailand', 'https://www.jac-recruitment.co.th/', 1, 11, 4.2, 13, NULL, 21, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:53:58', 0),
(915, 3, 'ChIJM_5Rh01v9GQR4V5KNS7TU6I', 'JacksonGrant Recruitment', '', '02 653 3998', 'support@jacksongrant.io', 'Two Pacific Place Unit1805 18th Floor, 142 Sukhumvit Rd, Khwaeng Khlong Toei, Khet Khlong Toei, Krung Thep Maha Nakhon 10110, Thailand', 'https://www.jacksongrant.io/', 1, 12, 5.0, 106, NULL, 35, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:54:00', 0),
(916, 3, 'ChIJLT3GGOOe4jARQl1BWKaMIO8', 'Personnel Consultant', '', '02 260 8454', '', 'Interchange 21 Building, 399 North Klongtoey, Wattana, Khwaeng Khlong Toei Nuea, Watthana, Krung Thep Maha Nakhon 10110, Thailand', 'http://www.personnelconsultant.co.th/', 1, 13, 4.4, 28, NULL, 21, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:54:04', 0),
(917, 3, 'ChIJCTUP9Nye4jARAC5JUeqEKMk', 'EPS Thailand - Recruitment & Outsourcing Agency', '', '02 105 4633', 'info@eps.in.th', 'Unit 1704, Level 17, Mercury Tower,, Ploenchit Road, Lumphini,, Lumpini, Pathum Wan, Bangkok 10330, Thailand', 'https://eps.in.th/', 1, 14, 4.2, 13, NULL, 21, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:54:04', 0),
(918, 3, 'ChIJVzP2Dwuf4jARNzuVFB2H_D0', 'Talent First Recruitment', '', '094 324 9962', '', '548 One City Centre, 37th floor, Room S37011, ถนน เพลินจิต แขวงลุมพินี เขตปทุมวัน กรุงเทพมหานคร 10330, Thailand', 'http://www.talentfirst.co.th/', 1, 15, 5.0, 11, NULL, 21, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:54:15', 0),
(919, 3, 'ChIJf8BAW_Of4jARb5pGcuHw00A', 'Asia HR Recruitment Agency (Thailand) Co., Ltd.', '', '02 041 8655', '', '23rd Floor, Two Pacific Place, 142 Sukhumvit Rd, Khwaeng Khlong Toei, Watthana, Krung Thep Maha Nakhon 10110, Thailand', 'https://www.asia-hr.com/', 1, 16, 3.9, 7, NULL, 5, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:54:20', 0),
(920, 3, 'ChIJdViqMzGf4jARCaXy6fMCMrs', 'CGP Thailand (Cornerstone Global Partners)', '', '095 119 8882', 'enquiriesth@cornerstoneglobalpartners.com', 'Building Room 30.18, 30th Floor, 1 Convent Rd, Si Lom, Khet Bang Rak, Krung Thep Maha Nakhon 10500, Thailand', 'https://www.cgpthailand.com/', 1, 17, 4.9, 31, NULL, 27, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:54:21', 0),
(921, 3, 'ChIJtVfLqt6e4jAR4PnMKEQ_oj4', 'Criterion Asia Recruitment (Thailand) Co., Ltd.', '', '02 258 6790', 'matt.z@criterionasia.com', '725 อาคารเอส เมโทร ชั้น 10 Sukhumvit Rd, Khwaeng Khlong Tan Nuea, Watthana, Krung Thep Maha Nakhon 10110, Thailand', 'http://www.criterionasia.com/', 1, 18, 4.3, 9, NULL, 15, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:54:22', 0),
(922, 3, 'ChIJ1Ym-JOee4jARaoHHMSOOx1A', 'ANCOR THAILAND', '', '02 653 2680', '', '6 O-Nes Tower 13th Floor, Suite, 1302-1303 ซ. สุขุมวิท 6 แขวงคลองเตย เขตคลองเตย กรุงเทพมหานคร 10110, Thailand', 'https://ancor.co.th/', 1, 19, 4.5, 23, NULL, 21, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:54:24', 0),
(923, 3, 'ChIJj4xOigmf4jARtplXAX7EJpw', 'Smart Search Recruitment', '', '02 714 8088', 'info@ssrecruitment.com', 'Major Tower Thonglor - 12th Floor Thonglor Soi 10, 55/10 ถ. สุขุมวิท แขวงคลองตันเหนือ เขตวัฒนา กรุงเทพมหานคร 10110, Thailand', 'http://www.ssrecruitment.com/', 1, 20, 4.1, 18, NULL, 21, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:54:25', 0),
(924, 3, 'ChIJG424z_ee4jARY7wh9Uj6mec', 'PRTR Group Public Company Limited', '', '02 716 0000', 'whistle-blowing@prtr.com', '2034/82 อาคาร, Itanthai Tower, ชั้นที่ 18 New Petchaburi Rd, Khwaeng Bang Kapi, Khet Huai Khwang, Krung Thep Maha Nakhon 10310, Thailand', 'https://www.prtr.com/', 1, 21, 3.8, 60, NULL, 17, 'all', 'Bangkok CBD, Thailand', 'Recruitment agency', 0, NULL, '2026-04-30 16:54:27', 0),
(925, 3, 'ChIJcWEULt9jUzoRHfMNA9BLm5o', 'CIEL HR Services Private Limited', '', '086809 39401', '', '100, New Shopping Complex (Mall 100 3rd Floor, Jawaharlal Nehru St, Heritage Town, Puducherry, 605001, India', 'http://www.cielhr.com/', 1, 2, 4.6, 54, NULL, 27, 'all', 'Pondicherry, India', 'Recruitment agency', 0, NULL, '2026-04-30 16:54:54', 0),
(926, 3, 'ChIJMRP-wNVjUzoRxAmxo-XzquQ', 'NAVAYUGA CONSULTANCY SERVICE', '', '0413 420 0993', 'navayuga.hr@gmail.com', '28, Chetty St, cutting, Heritage Town, Puducherry, 605001, India', 'http://www.navayuga-india.com/', 1, 3, 4.2, 143, NULL, 35, 'all', 'Pondicherry, India', 'Recruitment agency', 0, NULL, '2026-04-30 16:54:56', 0),
(927, 3, 'ChIJHUwqTXJhUzoR_yBVvTyU8gM', 'Saraswathy Consultancy India Private Limited', '', '099405 57332', '', 'First Floor, 14, Fourth Cross St, Kamalam Nagar, Reddiarpalayam, Puducherry, 605010, India', 'http://www.saraswathyconsultancy.com/', 1, 4, 4.5, 14, NULL, 21, 'all', 'Pondicherry, India', 'Recruitment agency', 0, NULL, '2026-04-30 16:54:56', 0),
(928, 3, 'ChIJ8_pFqddhUzoRhRQXyzgYodg', 'SS GROUPS MAN POWER CONSULTANCY', '', '080721 73040', '', 'NO.131 1ST FLOOR, Thiruvalluvar Salai, Ilango Nagar, Puducherry, 605013, India', '', 0, 5, 5.0, 21, NULL, 71, 'all', 'Pondicherry, India', 'Recruitment agency', 0, NULL, '2026-04-30 16:54:58', 0),
(929, 3, 'ChIJK_M3rAthUzoREgAGMSrQy_g', 'RENAISSANCE MANAGEMENT CONSULTANTS', '', '099449 09999', '', '6th Cross, Thanthai Periyar Nagar, Thanthai Periyar Nagar, Puducherry, 605005, India', 'https://renjobs.blogspot.com/', 1, 6, 4.8, 18, NULL, 21, 'all', 'Pondicherry, India', 'Recruitment agency', 0, NULL, '2026-04-30 16:55:02', 0),
(930, 3, 'ChIJwy06dI9hUzoR-NsYxKAtZC0', 'FirstStep Recruits', '', '095249 52477', '', '9, 1st Floor, Mettupalayam-Moolakulam Rd, Mothilal Nagar, Moolakulam, Puducherry, 605010, India', '', 0, 7, 4.7, 16, NULL, 71, 'all', 'Pondicherry, India', 'Recruitment agency', 0, NULL, '2026-04-30 16:55:04', 0),
(931, 3, 'ChIJyQv2laFhUzoRDzddx7-tksA', 'Ve.BK Manpower Consultancy', '', '080 4877 5345', 've.bkmanpowerconsultancy@gmail.com', 'No.34, 1st Cross Rd, Ajees Nagar, Reddiarpalayam, Puducherry, 605010, India', 'http://www.vebkmanpowerconsultancy.com/', 1, 8, 3.0, 2, NULL, 5, 'all', 'Pondicherry, India', 'Recruitment agency', 0, NULL, '2026-04-30 16:55:05', 0),
(932, 3, 'ChIJC_52OcdhUzoRIgcer0zd9Uw', 'Krish Consultancy', '', '078068 98831', 'info@krishconsultancy.com', 'Gundu Salai Rd, Jhansi Nagar, Sundararaja Nagar, Karamanikuppam, Puducherry, 605004, India', 'https://krishconsultancy.com/home', 1, 9, 4.9, 7, NULL, 15, 'all', 'Pondicherry, India', 'Recruitment agency', 0, NULL, '2026-04-30 16:55:09', 1),
(933, 3, 'ChIJAykj_gBhUzoRQc5EIFLIm70', 'SURABI JOBS-Best training and job placement agency in pondicherry', '', '099448 25409', '', '130,1st floor, Kamaraj Salai, Sithankudi, Brindavan Colony, Puducherry, 605013, India', '', 0, 10, NULL, 0, NULL, 55, 'all', 'Pondicherry, India', 'Recruitment agency', 0, NULL, '2026-04-30 16:55:13', 0),
(934, 3, 'ChIJ38fO5gphUzoRRmqltl2BDpk', 'CloudLogic Technologies', '', '098408 02462', '', '27 & 28, Bajanai Madam Street, Ellaipillaichavady, Thanthai Periyar Nagar, Puducherry, 605005, India', 'https://cloudlogic.tech/', 1, 2, 4.6, 252, NULL, 35, 'all', 'Pondicherry, India', 'Software company', 0, NULL, '2026-04-30 16:56:51', 0),
(935, 3, 'ChIJz-aF4aVhUzoRBLpIWk_fAvk', 'RKS Infotech', '', '090876 77222', 'info@yourname.com', '4th floor, SVR Plaza, NO.88, Villianur Main Rd, Sithananda Nagar, Sithantha Nagar, Thanthai Periyar Nagar, Puducherry, 605005, India', 'http://www.rksinfotech.com/', 1, 3, 5.0, 479, NULL, 35, 'all', 'Pondicherry, India', 'Software company', 0, NULL, '2026-04-30 16:56:51', 0),
(936, 3, 'ChIJj1k09dthUzoRrTgXNwRcgS0', 'AAHA Solutions', '', '095515 65200', 'info@aahasolutions.com', '27, 3rd Cross Rd, Sithankudi, Brindavan Colony, Puducherry, 605013, India', 'http://www.aahasolutions.com/', 1, 4, 4.6, 721, NULL, 35, 'all', 'Pondicherry, India', 'Software company', 0, NULL, '2026-04-30 16:56:53', 0),
(937, 3, 'ChIJz5iHOYBhUzoR9bf3Wo88HJs', 'Cherri Technologies', '', '080150 65014', 'support1@cherritech.com', '1st Floor, Needarajapaiyer St, opp. to Savarayalu Nayakar Government Girls High School, MG Road Area, Puducherry, 605001, India', 'https://www.cherritech.com/', 1, 5, 4.5, 112, NULL, 35, 'all', 'Pondicherry, India', 'Software company', 0, NULL, '2026-04-30 16:56:53', 0),
(938, 3, 'ChIJC8dVnG1gUzoRyideQtFUfto', 'Seyfert Infotech', '', '097901 30348', 'info@seyfertinfotech.com', 'First Floor, No.34, 4th Cross Rd, Jawahar Nagar, Kavery Nagar, Reddiarpalayam, Puducherry, 605005, India', 'https://www.seyfertinfotech.com/', 1, 6, 4.9, 215, NULL, 35, 'all', 'Pondicherry, India', 'Software company', 0, NULL, '2026-04-30 16:56:54', 0),
(939, 3, 'ChIJo73kT4VhUzoRP74wEI8WnfE', 'Ilahi Technologies', '', '082487 31840', '', '2nd Floor, 74/A, 1st Cross St, Sankardass Swamigal Nagar, Puducherry, 605003, India', '', 0, 7, 4.9, 89, NULL, 77, 'all', 'Pondicherry, India', 'Software company', 0, NULL, '2026-04-30 16:56:57', 0),
(940, 3, 'ChIJq6pfxphhUzoRYyPXBxG2-lg', 'Askan Technologies', '', '090422 71393', 'kannan@askantech.com', 'First Floor, RS No.348/8A1 East Coast Road, By pass, Kottakuppam, Tamil Nadu 605104, India', 'https://www.askantech.com/', 1, 8, 4.8, 311, NULL, 35, 'all', 'Pondicherry, India', 'Software company', 0, NULL, '2026-04-30 16:56:59', 0),
(941, 3, 'ChIJ2RlywH1hUzoRgjowaBQamgA', 'NKINFYX GROUPS PRIVATE LIMITED (Puducherry Administrative Office)', '', '', 'info@nkinfyxgroups.in', '1st Floor, 9, 3rd Cross St, Saranarayana Nagar, Reddiarpalayam, Puducherry, 605010, India', 'https://nkinfyxgroups.in/', 1, 2, 5.0, 14, NULL, 16, 'all', 'Puducherry, India', 'Private limited', 0, NULL, '2026-04-30 16:58:38', 0),
(942, 3, 'ChIJ5azlrdRhUzoR39BzMHTOmXE', 'Namlatech India Private Limited', '', '093609 65067', 'smourali@namlatic.com', 'No 2 Ground Floor Rangaswamy Nagar, 3rd Cross St, Murungapakkam, Puducherry, 605004, India', 'http://www.namlatech.com/', 1, 3, 4.9, 9, NULL, 15, 'all', 'Puducherry, India', 'Private limited', 0, NULL, '2026-04-30 16:58:41', 0),
(943, 3, 'ChIJzwZCLiSfVDoRbG5D8vV5y74', 'Amcor Flexibles India Pvt Ltd.', '', '', 'amcor.digital@amcor.com', 'Bahour Commune, Cuddalore Road, Kandanpet Village, Kattukuppam, Pillayarkuppam, Kattukuppam, Puducherry 607403, India', 'https://www.amcor.com/', 1, 4, 4.4, 17, NULL, 16, 'all', 'Puducherry, India', 'Private limited', 0, NULL, '2026-04-30 16:58:42', 0),
(944, 3, 'ChIJcX5jRy5hUzoREkQNvdNY6y4', 'MILKY HAVEN INDIA PRIVATE LIMITED (CORPORATE OFFICE)', '', '0413 235 5662', 'ustomercare@milkyhaven.com', 'Plot 217, First Floor, Bharathiyar Street, 4th Cross Ext, Jayamurthy Raja Nagar, Ozhandai Keerapalaiyam, Mudaliarpet, Puducherry, 605004, India', 'https://milkyhaven.com/', 1, 5, 5.0, 49, NULL, 27, 'all', 'Puducherry, India', 'Private limited', 0, NULL, '2026-04-30 16:58:42', 0),
(945, 3, 'ChIJhwCtoUJnUzoRdSjyaUw_FIw', 'EMOX MANUFACTURING PVT. LTD.', '', '0413 267 7343', 'velly@emox.co.in', 'Sedarapet, Puducherry, 605111, India', 'http://www.emox.co.in/', 1, 6, 4.1, 47, NULL, 27, 'all', 'Puducherry, India', 'Private limited', 0, NULL, '2026-04-30 16:58:43', 0),
(946, 3, 'ChIJ2RlywH1hUzoRgjowaBQamgA', 'NKINFYX GROUPS PRIVATE LIMITED (Puducherry Administrative Office)', '', '', 'info@nkinfyxgroups.in', '1st Floor, 9, 3rd Cross St, Saranarayana Nagar, Reddiarpalayam, Puducherry, 605010, India', 'https://nkinfyxgroups.in/', 1, 2, 5.0, 14, NULL, 16, 'all', 'Pondicherry, India', 'Private limited', 0, NULL, '2026-04-30 16:58:51', 0),
(947, 3, 'ChIJuZftaUyeVDoRYsOeofQfOBg', 'PondyBiz Technology Solutions Private Limited', '', '097901 14036', '', 'No. 72, Landmark building, 100 Feet Rd, Jhansi Nagar, Sundararaja Nagar, Mudaliarpet, Puducherry, 605004, India', '', 0, 3, 4.2, 12, NULL, 71, 'all', 'Pondicherry, India', 'Private limited', 0, NULL, '2026-04-30 16:58:52', 0),
(948, 3, 'ChIJIWCp9nlhUzoRRQDSNkfrKGk', 'Syscorp Technology Pvt Ltd', '', '', 'sales@itsk.in', 'No.37, Kamaraj Salai, Thattanchavady, Puducherry, 605009, India', 'https://syscorp.in/?utm_source=Google&utm_medium=organic&utm_campaign=gbp_localSEO_oct08', 1, 4, 4.8, 33, NULL, 22, 'all', 'Pondicherry, India', 'Private limited', 0, NULL, '2026-04-30 16:58:52', 0),
(949, 3, 'ChIJdQiMUHxhUzoRSa4fDI-56fk', 'SSG Consulting India Private Limited', '', '0413 221 2774', '', '#11 Salai Vinayagar Temple street 45 Feet Road Corner, Venkata Nagar, Puducherry, 605011, India', 'https://www.rencata.com/', 1, 5, 4.4, 33, NULL, 27, 'all', 'Pondicherry, India', 'Private limited', 0, NULL, '2026-04-30 16:58:56', 0),
(950, 3, 'ChIJ9dwJpgNhUzoRU5Sa84V9KDA', 'EnterpriseMinds India Private Limited', '', '073396 27828', '', 'Manatec Towers, Lawspet Main Road, Gnanapragasam Nagar, Saram, Puducherry, 605008, India', 'https://www.eminds.ai/', 1, 6, 5.0, 2, NULL, 15, 'all', 'Pondicherry, India', 'Private limited', 0, NULL, '2026-04-30 16:58:58', 0),
(951, 3, 'ChIJ6aF_uxdhUzoR30Hr6SgzAkk', 'Crution Private Limited', '', '0413 295 4764', 'info@crution.com', 'No: 15, First floor, 1st Cross-Kamban Nagar, Reddiarpalayam, Puducherry, 605010, India', 'https://crution.com/', 1, 7, 4.1, 9, NULL, 15, 'all', 'Pondicherry, India', 'Private limited', 0, NULL, '2026-04-30 16:58:59', 0),
(952, 3, 'ChIJl3HMsAphUzoR3IzG8DNNFxE', 'iMarque Solutions Pvt Ltd', '', '0413 406 5809', 'info@imarque.com', 'Plot No: 4 & 5 4th Cross, near to IG Statue, Ellaipillaichavady, Sithananda Nagar, Thanthai Periyar Nagar, Puducherry, 605005, India', 'http://www.imarque.com/', 1, 8, 4.2, 112, NULL, 35, 'all', 'Pondicherry, India', 'Private limited', 0, NULL, '2026-04-30 16:58:59', 0),
(953, 3, 'ChIJKWRPdA1hUzoRfeX3ZugZnEU', 'Maddox Solutions Private Limited', '', '0413 420 5880', 'info@maddoxsolutions.com', '63, 14th Cross St, 6th Cross Extension, Extension, Anna Nagar, Puducherry, 605005, India', 'http://www.maddoxsolutions.com/', 1, 9, 4.3, 17, NULL, 21, 'all', 'Pondicherry, India', 'Private limited', 0, NULL, '2026-04-30 16:59:02', 0);
INSERT INTO `lead_gen_results` (`id`, `user_id`, `place_id`, `name`, `owner_name`, `phone`, `email`, `address`, `website`, `has_website`, `api_calls`, `rating`, `ratings_total`, `price_level`, `opportunity_score`, `search_mode`, `location`, `industry`, `imported`, `lead_id`, `created_at`, `website_found_by_crawler`) VALUES
(954, 3, 'ChIJQzsYbqdhUzoRxedvpLrEr2U', 'Roadmap IT Solutions Pvt Ltd', '', '0413 420 7333', 'sweetalert2@11.js', '5, Republic St, behind Sun Pharmacy, Kavery Nagar, Reddiarpalayam, Puducherry, 605010, India', 'https://roadmapit.com/', 1, 2, 4.0, 114, NULL, 35, 'all', 'Pondicherry, India', 'Private Limited Company', 0, NULL, '2026-04-30 17:01:12', 0),
(955, 3, 'ChIJ5azlrdRhUzoR39BzMHTOmXE', 'Namlatech India Private Limited', '', '093609 65067', 'smourali@namlatic.com', 'No 2 Ground Floor Rangaswamy Nagar, 3rd Cross St, Murungapakkam, Puducherry, 605004, India', 'http://www.namlatech.com/', 1, 2, 4.9, 9, NULL, 15, 'all', 'Pondicherry, India', 'PVT LTD', 0, NULL, '2026-04-30 17:03:13', 0),
(956, 3, 'ChIJhwCtoUJnUzoRdSjyaUw_FIw', 'EMOX MANUFACTURING PVT. LTD.', '', '0413 267 7343', 'velly@emox.co.in', 'Sedarapet, Puducherry, 605111, India', 'http://www.emox.co.in/', 1, 3, 4.1, 47, NULL, 27, 'all', 'Pondicherry, India', 'PVT LTD', 0, NULL, '2026-04-30 17:03:14', 0),
(957, 3, 'ChIJwxITNWxhUzoRU2ihDipkzis', 'Tender Software India Private Limited', '', '097878 72738', 'info@tendersoftware.in', '1st, 2nd and 3rd floor, Thiru Arcade, 3, Arul Nesan Street, Pazhani Raja Udayar Nagar, Lawspet, Puducherry, 605008, India', 'https://tendersoftware.in/', 1, 4, 4.0, 39, NULL, 27, 'all', 'Pondicherry, India', 'PVT LTD', 0, NULL, '2026-04-30 17:03:17', 0),
(958, 3, 'ChIJ_cA7J2BnUzoRaks4EHlzNms', 'Ganges Internationale Pvt Ltd', '', '011 4709 0225', 'contactus@gangesintl.com', 'XPXR+G29, Sedarapet, Puducherry 605109, India', 'http://www.gangesintl.com/', 1, 5, 3.7, 132, NULL, 25, 'all', 'Pondicherry, India', 'PVT LTD', 0, NULL, '2026-04-30 17:03:20', 0),
(959, 3, 'ChIJA4sbgDqeVDoRKPtx62Tf05A', 'Lenovo India Private Limited', '', '0413 261 9400', 'orderstatus1@lenovo.com', '1A, Edayarpalayam, Puducherry 605007, India', 'https://www.lenovo.com/in/en/', 1, 6, 4.2, 69, NULL, 27, 'all', 'Pondicherry, India', 'PVT LTD', 0, NULL, '2026-04-30 17:03:20', 0),
(960, 3, 'ChIJB_Alo21hUzoR0MXVkb1WfTs', 'Integra Software Services Pvt. Ltd.', '', '0413 421 2124', '', 'SH 49, Pakkamudayanpet, Annamalai Nagar, Puducherry, 605008, India', 'https://integranxt.com/', 1, 7, 3.8, 359, NULL, 25, 'all', 'Pondicherry, India', 'PVT LTD', 0, NULL, '2026-04-30 17:03:27', 0),
(961, 3, 'ChIJe0wECHxhUzoR_lTjkBUJGgM', 'Plumage Technology Private Limited', '', '', 'sales@plumagetech.com', 'R.S.No, 17/2, Gothi Industrial Complex, Main Rd, Kurumbapet, Marie Oulgaret, Vazhudavur, Villianur, Puducherry 605009, India', 'https://plumagetech.com/', 1, 8, 4.6, 13, NULL, 16, 'all', 'Pondicherry, India', 'PVT LTD', 0, NULL, '2026-04-30 17:03:28', 0),
(962, 3, 'ChIJ5azlrdRhUzoR39BzMHTOmXE', 'Namlatech India Private Limited', '', '093609 65067', 'smourali@namlatic.com', 'No 2 Ground Floor Rangaswamy Nagar, 3rd Cross St, Murungapakkam, Puducherry, 605004, India', 'http://www.namlatech.com/', 1, 2, 4.9, 9, NULL, 15, 'all', 'Pondicherry, India', 'PVT LTD', 0, NULL, '2026-04-30 17:05:09', 0),
(963, 3, 'ChIJhwCtoUJnUzoRdSjyaUw_FIw', 'EMOX MANUFACTURING PVT. LTD.', '', '0413 267 7343', 'velly@emox.co.in', 'Sedarapet, Puducherry, 605111, India', 'http://www.emox.co.in/', 1, 3, 4.1, 47, NULL, 27, 'all', 'Pondicherry, India', 'PVT LTD', 0, NULL, '2026-04-30 17:05:10', 0),
(964, 3, 'ChIJwxITNWxhUzoRU2ihDipkzis', 'Tender Software India Private Limited', '', '097878 72738', 'info@tendersoftware.in', '1st, 2nd and 3rd floor, Thiru Arcade, 3, Arul Nesan Street, Pazhani Raja Udayar Nagar, Lawspet, Puducherry, 605008, India', 'https://tendersoftware.in/', 1, 4, 4.0, 39, NULL, 27, 'all', 'Pondicherry, India', 'PVT LTD', 0, NULL, '2026-04-30 17:05:12', 0),
(965, 3, 'ChIJ_cA7J2BnUzoRaks4EHlzNms', 'Ganges Internationale Pvt Ltd', '', '011 4709 0225', 'contactus@gangesintl.com', 'XPXR+G29, Sedarapet, Puducherry 605109, India', 'http://www.gangesintl.com/', 1, 5, 3.7, 132, NULL, 25, 'all', 'Pondicherry, India', 'PVT LTD', 0, NULL, '2026-04-30 17:05:14', 0),
(966, 3, 'ChIJA4sbgDqeVDoRKPtx62Tf05A', 'Lenovo India Private Limited', '', '0413 261 9400', 'orderstatus1@lenovo.com', '1A, Edayarpalayam, Puducherry 605007, India', 'https://www.lenovo.com/in/en/', 1, 6, 4.2, 69, NULL, 27, 'all', 'Pondicherry, India', 'PVT LTD', 0, NULL, '2026-04-30 17:05:15', 0),
(967, 3, 'ChIJB_Alo21hUzoR0MXVkb1WfTs', 'Integra Software Services Pvt. Ltd.', '', '0413 421 2124', '', 'SH 49, Pakkamudayanpet, Annamalai Nagar, Puducherry, 605008, India', 'https://integranxt.com/', 1, 7, 3.8, 359, NULL, 25, 'all', 'Pondicherry, India', 'PVT LTD', 0, NULL, '2026-04-30 17:05:27', 0),
(968, 3, 'ChIJe0wECHxhUzoR_lTjkBUJGgM', 'Plumage Technology Private Limited', '', '', 'sales@plumagetech.com', 'R.S.No, 17/2, Gothi Industrial Complex, Main Rd, Kurumbapet, Marie Oulgaret, Vazhudavur, Villianur, Puducherry 605009, India', 'https://plumagetech.com/', 1, 8, 4.6, 13, NULL, 16, 'all', 'Pondicherry, India', 'PVT LTD', 0, NULL, '2026-04-30 17:05:27', 0),
(969, 3, 'ChIJC2z7J6ZZqDsRvLJfktlW5bI', 'Lantrasoft Pvt. Ltd.', '', '(614) 888-6364', 'info@lantrasoft.com', '1000, Avinashi Rd, Grey Town, ATT Colony, Gopalapuram, Uppilipalayam, Coimbatore, Tamil Nadu 641018, India', 'https://www.lantrasoft.com/', 1, 2, 4.8, 60, NULL, 27, 'all', 'Coimbatore, India', 'PVT LTD', 0, NULL, '2026-04-30 17:06:47', 0),
(970, 3, 'ChIJAQCwWb9ZqDsRGZC1QMbMBHM', 'ANGLER Technologies India Pvt Ltd', '', '093616 63982', 'enquiry@angleritech.com', '1247, Trichy Rd, Chinthamani, Rukmani Nagar, Coimbatore, Tamil Nadu 641045, India', 'https://www.angleritech.com/', 1, 3, 3.6, 135, NULL, 25, 'all', 'Coimbatore, India', 'PVT LTD', 0, NULL, '2026-04-30 17:06:51', 0),
(971, 3, 'ChIJj58Pv7hZqDsR3FTLPrqoPZA', 'Mettler-Toledo India Private limited', '', '0422 220 2500', '', 'SWDC IN, IndiQube Kovai, Krupa Complex, 1332B, 2nd Floor, Avinashi Rd, Nava India Rd, Peelamedu West, Coimbatore, Tamil Nadu 641004, India', 'http://in.mt.com/in/en/home/microsites/TuringSoft.html', 1, 4, 4.3, 64, NULL, 27, 'all', 'Coimbatore, India', 'PVT LTD', 0, NULL, '2026-04-30 17:06:54', 0),
(972, 3, 'ChIJKea-lP9YqDsRtPx_Udq_2NI', 'Crisp System India Pvt Ltd', '', '0422 350 5348', '', '2nd Floor, CRISP TOWER, 120, Nehru St, Peranaidu Layout, Ram Nagar, Coimbatore, Tamil Nadu 641009, India', 'http://crispsystem.com/', 1, 5, 3.2, 75, NULL, 17, 'all', 'Coimbatore, India', 'PVT LTD', 0, NULL, '2026-04-30 17:07:00', 0),
(973, 3, 'ChIJf5fV-3xZqDsRG37klN0zrFE', 'MNC CORPORATE SYSTEM INDIA PVT LTD - Coimbatore', '', '073580 07211', 'mncindiafilings@gmail.com', '1st floor, 35/3, Desabandhu St, Ramarkovk, Ram Nagar, Coimbatore, Tamil Nadu 641009, India', 'https://mncindiafilings.com/', 1, 6, 5.0, 10, NULL, 21, 'all', 'Coimbatore, India', 'PVT LTD', 0, NULL, '2026-04-30 17:07:03', 0),
(974, 3, 'ChIJDZqwn1laqDsRLvmKZmLaovI', 'Harness Digitech Private Limited', '', '0422 220 9800', '', 'Span Venture SEZ, Embassy Tech Zone, G1, Pollachi Main Rd, Eachanari, Coimbatore, Tamil Nadu 641021, India', '', 0, 7, 4.2, 36, NULL, 77, 'all', 'Coimbatore, India', 'PVT LTD', 0, NULL, '2026-04-30 17:07:09', 0),
(975, 3, 'ChIJYVVVlcRZqDsRSTGusQ3LzI8', 'Carolina Technology Solutions Pvt Ltd.', '', '0422 438 3403', 'support@carotechs.com', 'No.23, Ansari St, Ram Nagar, Coimbatore, Tamil Nadu 641009, India', 'https://www.carotechs.com/', 1, 8, 3.6, 27, NULL, 11, 'all', 'Coimbatore, India', 'PVT LTD', 0, NULL, '2026-04-30 17:07:11', 0),
(976, 3, 'ChIJtTQMWBxbqDsRhzO2xuoga0M', 'PiscesER1 Marine Infotech Pvt Ltd, Coimbatore', '', '', 'demo@gmail.com', '1St Floor, KSK Building, 3/290, Palakkad - Coimbatore Rd, Pulakadu, Kuniyamuthur, Coimbatore, Tamil Nadu 641008, India', 'https://www.pisceser1marine.com/', 1, 9, 4.5, 13, NULL, 16, 'all', 'Coimbatore, India', 'PVT LTD', 0, NULL, '2026-04-30 17:07:14', 0),
(977, 3, 'ChIJh8Nk8ptYqDsReQK6vdZVluY', 'Genuine Infotech Private Limited', '', '0422 437 2678', 'admin@genuineinfotech.com', '69A, Diwan Bahadur Rd, R.S. Puram, Coimbatore, Tamil Nadu 641002, India', 'https://www.genuineinfotech.com/', 1, 10, 4.8, 12, NULL, 21, 'all', 'Coimbatore, India', 'PVT LTD', 0, NULL, '2026-04-30 17:07:15', 0),
(978, 3, 'ChIJofnpBEVaqDsR90-vMBjw11s', 'J-Technologies India Ltd', '', '0422 308 2805', '', '27, SIDCO Private Industrial Estate, Kurichi, Coimbatore, Tamil Nadu 641021, India', 'http://www.jtechindia.com/', 1, 11, 4.1, 45, NULL, 27, 'all', 'Coimbatore, India', 'PVT LTD', 0, NULL, '2026-04-30 17:07:16', 0),
(979, 3, 'ChIJgWy8TOtaqDsRusNLNrlimfk', 'Mech N Tech Engineers Pvt Ltd', '', '098946 12384', 'mechntechengineers@gmail.com', '17, 18, Revathi Nagar, Barathipuram, Malumichampatti, Tamil Nadu 641050, India', 'http://www.mechntech.in/', 1, 12, 4.5, 35, NULL, 27, 'all', 'Coimbatore, India', 'PVT LTD', 0, NULL, '2026-04-30 17:07:20', 0),
(980, 3, 'ChIJeUis0uVYqDsRawoQbZDzO24', 'INDSYS Techservices India Private Limited', '', '095009 96164', '', '1st Floor, Vaishnavi Complex, Ranga Konar St, Kattoor Main, Kattoor, Ram Nagar, Coimbatore, Tamil Nadu 641009, India', 'http://www.indsystech.com/', 1, 13, 4.4, 17, NULL, 21, 'all', 'Coimbatore, India', 'PVT LTD', 0, NULL, '2026-04-30 17:07:21', 0),
(981, 3, 'ChIJAQCwWb9ZqDsRGZC1QMbMBHM', 'ANGLER Technologies India Pvt Ltd', '', '093616 63982', 'enquiry@angleritech.com', '1247, Trichy Rd, Chinthamani, Rukmani Nagar, Coimbatore, Tamil Nadu 641045, India', 'https://www.angleritech.com/', 1, 2, 3.6, 135, NULL, 25, 'all', 'Coimbatore, India', 'Pvt Ltd', 0, NULL, '2026-04-30 17:08:28', 0),
(982, 3, 'ChIJKea-lP9YqDsRtPx_Udq_2NI', 'Crisp System India Pvt Ltd', '', '0422 350 5348', '', '2nd Floor, CRISP TOWER, 120, Nehru St, Peranaidu Layout, Ram Nagar, Coimbatore, Tamil Nadu 641009, India', 'http://crispsystem.com/', 1, 3, 3.2, 75, NULL, 17, 'all', 'Coimbatore, India', 'Pvt Ltd', 0, NULL, '2026-04-30 17:08:33', 0),
(983, 3, 'ChIJC2z7J6ZZqDsRvLJfktlW5bI', 'Lantrasoft Pvt. Ltd.', '', '(614) 888-6364', 'info@lantrasoft.com', '1000, Avinashi Rd, Grey Town, ATT Colony, Gopalapuram, Uppilipalayam, Coimbatore, Tamil Nadu 641018, India', 'https://www.lantrasoft.com/', 1, 4, 4.8, 60, NULL, 27, 'all', 'Coimbatore, India', 'Pvt Ltd', 0, NULL, '2026-04-30 17:08:35', 0),
(984, 3, 'ChIJj58Pv7hZqDsR3FTLPrqoPZA', 'Mettler-Toledo India Private limited', '', '0422 220 2500', '', 'SWDC IN, IndiQube Kovai, Krupa Complex, 1332B, 2nd Floor, Avinashi Rd, Nava India Rd, Peelamedu West, Coimbatore, Tamil Nadu 641004, India', 'http://in.mt.com/in/en/home/microsites/TuringSoft.html', 1, 5, 4.3, 64, NULL, 27, 'all', 'Coimbatore, India', 'Pvt Ltd', 0, NULL, '2026-04-30 17:08:37', 0),
(985, 3, 'ChIJf5fV-3xZqDsRG37klN0zrFE', 'MNC CORPORATE SYSTEM INDIA PVT LTD - Coimbatore', '', '073580 07211', 'mncindiafilings@gmail.com', '1st floor, 35/3, Desabandhu St, Ramarkovk, Ram Nagar, Coimbatore, Tamil Nadu 641009, India', 'https://mncindiafilings.com/', 1, 6, 5.0, 10, NULL, 21, 'all', 'Coimbatore, India', 'Pvt Ltd', 0, NULL, '2026-04-30 17:08:38', 0),
(986, 3, 'ChIJAQCwWb9ZqDsRGZC1QMbMBHM', 'ANGLER Technologies India Pvt Ltd', '', '093616 63982', 'enquiry@angleritech.com', '1247, Trichy Rd, Chinthamani, Rukmani Nagar, Coimbatore, Tamil Nadu 641045, India', 'https://www.angleritech.com/', 1, 2, 3.6, 135, NULL, 25, 'all', 'Coimbatore, India', 'Pvt Ltd', 0, NULL, '2026-04-30 17:08:57', 0),
(987, 3, 'ChIJKea-lP9YqDsRtPx_Udq_2NI', 'Crisp System India Pvt Ltd', '', '0422 350 5348', '', '2nd Floor, CRISP TOWER, 120, Nehru St, Peranaidu Layout, Ram Nagar, Coimbatore, Tamil Nadu 641009, India', 'http://crispsystem.com/', 1, 3, 3.2, 75, NULL, 17, 'all', 'Coimbatore, India', 'Pvt Ltd', 0, NULL, '2026-04-30 17:09:02', 0),
(988, 3, 'ChIJC2z7J6ZZqDsRvLJfktlW5bI', 'Lantrasoft Pvt. Ltd.', '', '(614) 888-6364', 'info@lantrasoft.com', '1000, Avinashi Rd, Grey Town, ATT Colony, Gopalapuram, Uppilipalayam, Coimbatore, Tamil Nadu 641018, India', 'https://www.lantrasoft.com/', 1, 4, 4.8, 60, NULL, 27, 'all', 'Coimbatore, India', 'Pvt Ltd', 0, NULL, '2026-04-30 17:09:04', 0),
(989, 3, 'ChIJj58Pv7hZqDsR3FTLPrqoPZA', 'Mettler-Toledo India Private limited', '', '0422 220 2500', '', 'SWDC IN, IndiQube Kovai, Krupa Complex, 1332B, 2nd Floor, Avinashi Rd, Nava India Rd, Peelamedu West, Coimbatore, Tamil Nadu 641004, India', 'http://in.mt.com/in/en/home/microsites/TuringSoft.html', 1, 5, 4.3, 64, NULL, 27, 'all', 'Coimbatore, India', 'Pvt Ltd', 0, NULL, '2026-04-30 17:09:06', 0),
(990, 3, 'ChIJf5fV-3xZqDsRG37klN0zrFE', 'MNC CORPORATE SYSTEM INDIA PVT LTD - Coimbatore', '', '073580 07211', 'mncindiafilings@gmail.com', '1st floor, 35/3, Desabandhu St, Ramarkovk, Ram Nagar, Coimbatore, Tamil Nadu 641009, India', 'https://mncindiafilings.com/', 1, 6, 5.0, 10, NULL, 21, 'all', 'Coimbatore, India', 'Pvt Ltd', 0, NULL, '2026-04-30 17:09:08', 0),
(991, 3, 'ChIJl1ecolVYqDsRdoDIGkNM9m8', 'V3 Computers - Computer Store / gaming pc / assembled pc shop coimbatore', '', '090475 89001', '', '219, A13 Madura Towers 9th Street Gandhipuram, Coimbatore, Tamil Nadu 641012, India', 'https://www.v3computers.com/', 1, 2, 5.0, 1688, NULL, 35, 'all', 'Coimbatore, India', 'Computer shop', 0, NULL, '2026-04-30 17:09:39', 0),
(992, 3, 'ChIJIUIZtldYqDsRvae5y7IOHX0', 'WAYMARK - The Computer Store', '', '095008 86166', 'amulguts@gmail.com', '12, 9th St, Tatabad, Gandhipuram, Coimbatore, Tamil Nadu 641012, India', 'https://www.thecomputerstore.co.in/', 1, 3, 4.8, 1445, NULL, 35, 'all', 'Coimbatore, India', 'Computer shop', 0, NULL, '2026-04-30 17:09:40', 0),
(993, 3, 'ChIJp81UoulXqDsRbkHrP8XibTk', 'BYOS Computer Store', '', '075300 15155', '', '93, Avinashi Rd, Periyar Nagar, Sri Nagar, Hope College, PEELAMEDU, Coimbatore, Tamil Nadu 641004, India', '', 0, 4, 4.8, 1226, NULL, 85, 'all', 'Coimbatore, India', 'Computer shop', 0, NULL, '2026-04-30 17:09:42', 0),
(994, 3, 'ChIJ2_ze-lVYqDsRFSS9JpyCJ9A', 'Elixir Computers', '', '096006 06018', 'elixirtechnologiescbe@gmail.com', '574 dheepam complex,2 Nd Street, Dr Rajendra Prasad Rd, 2nd Street Extension, Gandhipuram, Coimbatore, Tamil Nadu 641012, India', 'https://elixircomputers.com/', 1, 5, 4.9, 791, NULL, 35, 'all', 'Coimbatore, India', 'Computer shop', 0, NULL, '2026-04-30 17:09:42', 0),
(995, 3, 'ChIJd4NNq-tZqDsROYm0bxo9jw4', 'HAPPY COMPUTERS WORLD', '', '099439 35353', 'happycomputersworldcbe@gmail.com', 'NO.126, Kumaran Complex, Dr Rajendra Prasad Rd, Gandhipuram, Coimbatore, Tamil Nadu 641012, India', 'https://happycomputersworld.com/', 1, 6, 4.6, 462, NULL, 35, 'all', 'Coimbatore, India', 'Computer shop', 0, NULL, '2026-04-30 17:09:43', 0),
(996, 3, 'ChIJgwDaVPy5qjsRrB-J5NISLxw', 'VEBBOX software solutions', '', '063793 21835', 'info@vebbox.in', '753,1, st floor, Mullai St, opposite to indian overseas bank, New Housing Unit, Thanjavur, Tamil Nadu 613005, India', 'http://vebbox.com/', 0, 2, 4.7, 201, NULL, 85, 'all', 'Thanjavur, India', 'Software company', 0, NULL, '2026-04-30 17:10:15', 0),
(997, 3, 'ChIJFblvDba4qjsR3Whw6KhYWWs', 'Sardonyx Technologies Private Limited', '', '04362 243 433', '', '97/3,APM IT park,Palliagraharam,Perambalur to Manamadurai NH, Palliagraharam, Thanjavur, Tamil Nadu 613003, India', 'http://www.sardonyx.in/', 1, 3, 4.4, 210, NULL, 35, 'all', 'Thanjavur, India', 'Software company', 0, NULL, '2026-04-30 17:10:19', 0),
(998, 3, 'ChIJeefiFE-5qjsR31iQTaGlB-I', 'Imaggar Technologies pvt ltd', '', '095663 82650', '', '4th floor, Devi towers, Neithal St, Natchathira Nagar, Thanjavur, Tamil Nadu 613005, India', 'https://imaggar.in/', 1, 4, 4.9, 12, NULL, 21, 'all', 'Thanjavur, India', 'Software company', 0, NULL, '2026-04-30 17:10:20', 0),
(999, 3, 'ChIJIfTQuMq5qjsR4YB6dK1Bxbk', 'Tech Vaseegrah', '', '085240 89733', 'techvaseegrah@gmail.com', 'Vijaya Nagar, 11, post, near Rettipalaiyam Road, Srinivasapuram, Wahab Nagar, Thanjavur, Tamil Nadu 613009, India', 'https://www.techvaseegrah.com/', 1, 5, 5.0, 70, NULL, 27, 'all', 'Thanjavur, India', 'Software company', 0, NULL, '2026-04-30 17:10:20', 0),
(1000, 3, 'ChIJv5aMVLW5qjsRmz03HytSmJA', 'Next Future Technology Private Limited', '', '080720 22595', '', 'Annai Muthammal Nagar, Road, Marungai, Thanjavur, Tamil Nadu 613501, India', 'http://www.nextfuturetechnology.com/', 1, 6, 5.0, 49, NULL, 27, 'all', 'Thanjavur, India', 'Software company', 0, NULL, '2026-04-30 17:10:36', 0),
(1001, 3, 'ChIJgwDaVPy5qjsRrB-J5NISLxw', 'VEBBOX software solutions', '', '063793 21835', 'info@vebbox.in', '753,1, st floor, Mullai St, opposite to indian overseas bank, New Housing Unit, Thanjavur, Tamil Nadu 613005, India', 'http://vebbox.com/', 0, 2, 4.7, 201, NULL, 85, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:10:54', 0),
(1002, 3, 'ChIJoTby5TW5qjsRWxwYJO6eN5s', 'Alpha Software Solution', '', '098844 59869', '', '30/3,Arunagirinathar Street,Ezhil Nagar,Municipal Colony, Medical College Rd, Indira Nagar, Thanjavur, Tamil Nadu 613007, India', 'http://alphass.in/', 1, 3, 4.6, 31, NULL, 27, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:10:56', 0),
(1003, 3, 'ChIJ7aDmiLG5qjsR6_5bHjD7tao', 'Sky Tech Solution', '', '087780 96147', 'info@myskytechsolution.com', 'Nakshatra Nagar, 7, 1st St, Natchathira Nagar, Thanjavur, Tamil Nadu 613005, India', 'https://myskytechsolution.com/', 1, 4, 4.6, 25, NULL, 21, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:10:57', 0),
(1004, 3, 'ChIJJclSV9S4qjsR7dCcfRvE2Og', 'TechSwing Solutions Pvt Ltd', '', '04362 225 066', 'info@tech-swing.com', '19, Raja Nagar Rd, near New Bus Stand Road, Lakshmi Nagar, New Housing Unit, Thanjavur, Tamil Nadu 613005, India', 'http://tech-swing.com/', 0, 5, 5.0, 2, NULL, 65, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:10:57', 0),
(1005, 3, 'ChIJIfTQuMq5qjsR4YB6dK1Bxbk', 'Tech Vaseegrah', '', '085240 89733', 'techvaseegrah@gmail.com', 'Vijaya Nagar, 11, post, near Rettipalaiyam Road, Srinivasapuram, Wahab Nagar, Thanjavur, Tamil Nadu 613009, India', 'https://www.techvaseegrah.com/', 1, 6, 5.0, 70, NULL, 27, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:10:57', 0),
(1006, 3, 'ChIJu_WFfsVHVToR1l16Wq-T_r8', 'VVASAI Software Solutions Private Limited', '', '', 'info@vvasai.com', 'Jayam Nagar, ByPass, Gnanam Nagar, Thanjavur, Tamil Nadu 613001, India', 'https://vvasai.com/', 1, 7, 5.0, 5, NULL, 10, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:10:58', 0),
(1007, 3, 'ChIJhXz8J424qjsREluBAQepoNg', 'YOGA\'S IT Solutions | Web Designing & Digital Marketing Company', '', '098942 34199', 'support@yogasgroup.org', 'Mercy Palace, No. 7, 2nd Street, Anna Nagar, Villar Road, Burma Colony, Thanjavur, Tamil Nadu 613006, India', 'https://www.yogasgroup.org/', 1, 8, 4.9, 160, NULL, 35, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:01', 0),
(1008, 3, 'ChIJv5aMVLW5qjsRmz03HytSmJA', 'Next Future Technology Private Limited', '', '080720 22595', '', 'Annai Muthammal Nagar, Road, Marungai, Thanjavur, Tamil Nadu 613501, India', 'http://www.nextfuturetechnology.com/', 1, 9, 5.0, 49, NULL, 27, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:01', 0),
(1009, 3, 'ChIJeefiFE-5qjsR31iQTaGlB-I', 'Imaggar Technologies pvt ltd', '', '095663 82650', '', '4th floor, Devi towers, Neithal St, Natchathira Nagar, Thanjavur, Tamil Nadu 613005, India', 'https://imaggar.in/', 1, 10, 4.9, 12, NULL, 21, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:02', 0),
(1010, 3, 'ChIJh1Ww2HO_qjsRNwvRNM8Otm0', 'A for Analytics IT Solution Pvt Ltd,,', '', '', 'info@aforanalytic.com', '21A, Mullai Nagar, Gandhipuram, Thanjavur, Tamil Nadu 613004, India', 'http://www.aforanalytic.com/', 1, 11, 4.9, 26, NULL, 16, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:08', 0),
(1011, 3, 'ChIJFblvDba4qjsR3Whw6KhYWWs', 'Sardonyx Technologies Private Limited', '', '04362 243 433', '', '97/3,APM IT park,Palliagraharam,Perambalur to Manamadurai NH, Palliagraharam, Thanjavur, Tamil Nadu 613003, India', 'http://www.sardonyx.in/', 1, 12, 4.4, 210, NULL, 35, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:12', 0),
(1012, 3, 'ChIJEwAAADG_qjsRX6apm-1IFRc', 'Ebrain Technologies', '', '099440 07339', 'support@ebraintechnologies.com', '15, Second Floor, Karups Nagar, Trichy Main Road, next to Dr. Mohan\'s Diabetes Centre, AVP Azhagammal Nagar, Thanjavur, Tamil Nadu 613005, India', 'https://ebraintechnologies.com/', 1, 13, 4.9, 26, NULL, 21, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:12', 0),
(1013, 3, 'ChIJV50sefW5qjsRJTbHp2vfv5w', 'Higglerslab Solutions Private Limited', '', '093859 18001', 'contact@higglerslab.com', '27 ,Second Floor, Second Street, Madhakottai Rd, Bank Staff Colony, Rajaliar Nagar, Annai Sathya Nagar, Thanjavur, Tamil Nadu 613005, India', 'https://www.higglerslab.com/', 1, 14, 4.9, 19, NULL, 21, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:14', 0),
(1014, 3, 'ChIJdZkLgEK5qjsRtIMdZ-nAbok', 'MIMA Software Solutions', '', '096002 98766', '', '21/249, Marutham St, New Housing Unit, Thanjavur, Tamil Nadu 613005, India', 'http://www.mima.co.in/', 1, 15, 5.0, 5, NULL, 15, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:15', 0),
(1015, 3, 'ChIJO98mROy5qjsRsVLQ51dCXg8', 'Gurusoft Technology Private Limited', '', '099651 19255', 'sales@gurusoft.com.sg', 'Plot No.7, Madhakottai Rd, Geetha Nagar, Moovendhar Nagar, Annai Sathya Nagar, Thanjavur, Tamil Nadu 613005, India', 'https://www.gurusofttech.com/Contact-Us', 1, 16, 3.7, 23, NULL, 11, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:15', 0),
(1016, 3, 'ChIJQXkjzWG5qjsRQE0P-SggDVo', 'Sumeru Technology Solutions Pvt Ltd', '', '', '', 'No.898, G1, 2nd Floor, HIG, Neithal Street, Trichy Main Rd, opp. Nasar Hotel, New Housing Unit, Thanjavur, Tamil Nadu 613005, India', 'http://www.sumerusolutions.com/', 1, 17, 5.0, 7, NULL, 10, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:16', 0),
(1017, 3, 'ChIJcf1xX1a5qjsRHhHjrgk9Cso', 'Jadayu Software Technology | Software Training Institute in Thanjavur | Computer Education Center Thanjavur', '', '087549 63381', '', 'First Floor, Bishop Sundaram Complex, Pudukkottai Rd, Thanjavur, Tamil Nadu 613007, India', '', 0, 18, 4.9, 153, NULL, 85, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:17', 0),
(1018, 3, 'ChIJZfdI1xu5qjsROQcbloenuiU', 'A.M.T Software Solution', '', '', '', '205, 5th Cross St, Janame Jeyam Nagar, Sundram Nagar, Thanjavur, Tamil Nadu 613007, India', '', 0, 19, 5.0, 4, NULL, 60, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:19', 0),
(1019, 3, 'ChIJgQcmbz65qjsRzxyE6gpDrtY', 'TECHBINOS IT SERVICES LLP', '', '063797 96758', 'hrteam@techbinos.com', '5th Floor, Meenakshi Elite, STREET, Pudukkottai Rd, Cauvery Nagar West, Natchathira Nagar, Thanjavur, Tamil Nadu 613007, India', 'https://www.techbinos.com/', 1, 20, 4.9, 47, NULL, 27, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:24', 0),
(1020, 3, 'ChIJawqk-4a5qjsRTuAIa7xjBsY', 'ARA Discoveries Pvt Ltd – IT Solutions & Digital Marketing', '', '081100 25254', '', '67A, Giri Rd, Srinivasapuram, Balaganapathy Nagar, Thanjavur, Tamil Nadu 613009, India', 'https://discovermarketing.co/', 0, 21, 4.9, 57, NULL, 77, 'all', 'Thanjavur, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:25', 0),
(1021, 3, 'ChIJF5go1ab1qjsRPFhS8S387ps', 'esoft IT Solutions.', '', '080724 20182', '', 'C, II-Floor, Land Mark: Lakshmi Complex, Bus Stop, 145/74, Salai Rd, Thillai Nagar East, Thillai Nagar, Tiruchirappalli, Tamil Nadu 620018, India', 'http://e-soft.in/', 1, 2, 4.9, 1012, NULL, 35, 'all', 'Lalgudi, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:47', 0),
(1022, 3, 'ChIJReEf9ZZPqjsR8Sgxsk7SE04', 'Dice Technology Software Solutions', '', '097878 51828', 'info@dicetechnology.com', 'Building No: 5/7, RRR Complex,, Salem Main road, Opposite to new bus stand, Musiri, Tamil Nadu 621211, India', 'https://www.dicetechnology.com/', 1, 3, 5.0, 20, NULL, 21, 'all', 'Lalgudi, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:48', 0),
(1023, 3, 'ChIJV6QG4jHxqjsRql7lmhVSq4w', 'ARP Software Solutions', '', '29805816', '', 'VRG8+2FX, Akilandeshwari Nagar, Lalgudi, Tamil Nadu 621601, India', 'https://www.arpsoftwaresolutions.com/', 1, 4, NULL, 0, NULL, 5, 'all', 'Lalgudi, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:50', 1),
(1024, 3, 'ChIJ77tZpNrxqjsRQpWz0n3Nqcg', 'TTS Business Services Private Limited', '', '0431 402 1969', 'info@ttsbusinessservices.com', '48/S1, Second Floor, Poovalur Road,Siruthaiyur, Lalgudi - 621 601, Paramasivapuram, Trichy, Lalgudi, Tamil Nadu 621601, India', 'http://www.ttsbusinessservices.com/', 1, 5, 3.0, 2, NULL, 5, 'all', 'Lalgudi, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:52', 0),
(1025, 3, 'ChIJAaoxe5KLqjsR0fKoCaO0j30', 'Mercury Softech', '', '063826 94426', 'admin@mercurysoftech.com', '3rd Floor, GrandTowers, Cheran Salai, LIC Colony, Ayyappa Nagar, K K Nagar, Tiruchirappalli, Tamil Nadu 620021, India', 'https://www.mercurysoftech.com/', 1, 6, 4.9, 52, NULL, 27, 'all', 'Lalgudi, India', 'Software solutions', 0, NULL, '2026-04-30 17:11:52', 0),
(1026, 3, 'ChIJnyjSf_QbqzsRnOAjdYR2V78', 'R Tech Solution', '', '093454 42659', 'info@rtechsolution.in', 'No.206A, 2nd Floor, Second Cross Road, Vivekanandhar Street, Post Office Salai, Super Nagar, Perambalur, Tamil Nadu 621212, India', 'https://rtechsolution.in/', 1, 2, 5.0, 85, NULL, 27, 'all', 'Perambalur, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:03', 0),
(1027, 3, 'ChIJbcKT_uobqzsRBKQYuyaK0Hk', 'Sharkvels Software Solutions Pvt Ltd', '', '078455 81755', '', 'road, Arumadal, Perambalur, Tamil Nadu 621220, India', '', 0, 3, 5.0, 9, NULL, 65, 'all', 'Perambalur, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:05', 0),
(1028, 3, 'ChIJhQIoJJ0bqzsRGvNedyGSSBY', 'Math IT Solutions', '', '097897 96528', 'mathitslns@gmail.com', 'Rajanagar, Thuraimangalam, Perambalur, Tamil Nadu 621220, India', 'http://www.mathitsolutions.com/', 1, 4, 4.7, 21, NULL, 21, 'all', 'Perambalur, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:08', 0),
(1029, 3, 'ChIJadHT6isbqzsRMEFDSs-lMk8', 'Greator Software', '', '063856 29914', '', 'First Floor, No.1, Qasim Complex Abiramipuram, Permbalur Collector Office Road, Thuraimangalam, Perambalur, Tamil Nadu 621212, India', 'https://www.greatorsoftware.com/', 1, 5, 5.0, 2, NULL, 15, 'all', 'Perambalur, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:09', 0),
(1030, 3, 'ChIJ77QRTkUbqzsRKHhMJbXBY2I', 'Jet IT Solutions', '', '093633 45960', '', '41, Eachampatti, Perambalur, Tamil Nadu 621220, India', 'https://jetitsolution.com/', 1, 6, 5.0, 5, NULL, 15, 'all', 'Perambalur, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:15', 0),
(1031, 3, 'ChIJfSeRXgAbqzsRvLAuQH49qCo', 'Sumisa Technologies', '', '', 'contact@sumisatech.com', '6VR8+3H4, Samiyappa Nagar, Perambalur, Tamil Nadu 621212, India', 'https://sumisatech.com/', 1, 7, 5.0, 3, NULL, 10, 'all', 'Perambalur, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:16', 0),
(1032, 3, 'ChIJySFDonobqzsR9o9e9sfST_s', 'Analasoft Technologies', '', '099945 67247', 'info@analasoft.com', 'No 5,First Floor, Near Ulavar Santhai, N Madhavi Rd, Samiyappa Nagar, Perambalur, Tamil Nadu 621212, India', 'http://analasoft.com/', 1, 8, 5.0, 9, NULL, 15, 'all', 'Perambalur, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:17', 0),
(1033, 3, 'ChIJJ-rI3IQbqzsRwMDhZZueHos', 'Anzee tech solution | IT | Online training | Devops | AWS | Azure | Cyber Security | Python | Ansible | Jenkins| AI', '', '091598 35086', '', 'No. 28, Postoffice, Old bus stand, 1st Cross Rd, near Post office, near nasma tailors, Thuraimangalam, Perambalur, Tamil Nadu 621220, India', 'https://www.anzeetechsolution.com/', 1, 9, NULL, 0, NULL, 5, 'all', 'Perambalur, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:18', 0),
(1034, 3, 'ChIJF5go1ab1qjsRPFhS8S387ps', 'esoft IT Solutions.', '', '080724 20182', '', 'C, II-Floor, Land Mark: Lakshmi Complex, Bus Stop, 145/74, Salai Rd, Thillai Nagar East, Thillai Nagar, Tiruchirappalli, Tamil Nadu 620018, India', 'http://e-soft.in/', 1, 2, 4.9, 1012, NULL, 35, 'all', 'Srirangam, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:44', 0),
(1035, 3, 'ChIJMxIj_871qjsRaDMCtaaNLsc', 'CLOUD & BEYOND', '', '0431 497 2486', 'support@cloudandbeyond.com', '188, Kanchi Illam,12th cross Ganapathy Nagar, Srirangam, Srirangam, Tiruchirappalli, Tamil Nadu 620005, India', 'https://cloudandbeyond.com/', 1, 3, 4.8, 28, NULL, 21, 'all', 'Srirangam, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:45', 0),
(1036, 3, 'ChIJi7lqIjb3qjsR4eMN2VpzmG0', 'VSA Tech Solutions Pvt Ltd', '', '099009 01223', '', 'No 20 MRR Garden, Srirangam, Tiruchirappalli, Tamil Nadu 620005, India', 'http://vsatechsolutions.com/', 1, 4, 5.0, 3, NULL, 15, 'all', 'Srirangam, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:46', 0),
(1037, 3, 'ChIJo70BV6b1qjsROS3FzLZHbg4', 'Infovenz Software Solutions', '', '088254 50563', 'sales@infovenz.com', 'First Floor, LG Complex, Bus Stand, Kaliyamman Kovil St, near Chathiram, Tiruchirappalli, Tamil Nadu 620002, India', 'https://infovenz.com/', 1, 5, 4.6, 51, NULL, 27, 'all', 'Srirangam, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:49', 0),
(1038, 3, 'ChIJ-97vurP3qjsRDp7XMJoofzM', 'Tech Factory IT Solutions', '', '083007 50996', 'info@techfactoryitsolutions.in', 'C40,EAST ADAIYAVALANJAN STREET, Srirangam, Tiruchirappalli, Tamil Nadu 620006, India', 'https://techfactoryitsolutions.in/', 1, 6, 4.8, 5, NULL, 15, 'all', 'Srirangam, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:49', 0),
(1039, 3, 'ChIJAUCGCOf1qjsRdO8GEkL_Rok', 'Tidi Software Solutions Pvt Ltd', '', '', '', '28, Sembaruthi Rd, Saravana Nagar, Sanjeevi Nagar, Tiruchirappalli, Tamil Nadu 620002, India', '', 0, 7, 5.0, 5, NULL, 60, 'all', 'Srirangam, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:52', 0),
(1040, 3, 'ChIJGyhZtX9LqCcRInJbFlyCyB4', 'SamCore Solution | Software Hardware | Development & Testing', '', '098432 07899', '', 'Anna Silai, No.16, 2nd Floor, Ranga Complex Chatram, Bus Stand, Old Karur Rd, Melachinthamani, Tiruchirappalli, Tamil Nadu 620002, India', 'http://www.samcoresolution.in/', 1, 8, 4.7, 164, NULL, 35, 'all', 'Srirangam, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:52', 0),
(1041, 3, 'ChIJ0-R9BWb1qjsRH2Btbe5FqmQ', 'RamTech IT Solutions', '', '', '', 'No.4,Pulimanadabam, Amma Mandapam, Pushpak Nagar, extn, Srirangam, Tiruchirappalli, Tamil Nadu 620006, India', 'https://ramtech.pro/', 1, 9, NULL, 0, NULL, 0, 'all', 'Srirangam, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:55', 0),
(1042, 3, 'ChIJsVnH93P1qjsR-pV0jRG5w7I', 'KO Innovation Software Solutions Private Limited', '', '093848 10322', 'contactus@koinnovation.com', '12, 10th Cross St, Thillai Nagar East, Tennur, Tiruchirappalli, Tamil Nadu 620018, India', 'https://www.koinnovation.com/', 1, 10, 4.9, 8, NULL, 15, 'all', 'Srirangam, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:56', 0),
(1043, 3, 'ChIJq6qqqi_0qjsRC8kcA92N-vE', 'A.S. Power Solutions Pvt Ltd.', '', '088701 01121', '', 'No.3, Karthikeyan Garden, Srirangam, Thiruvanaikoil, Tiruchirappalli, Tamil Nadu 620005, India', 'http://aspowerpvt.com/', 1, 11, 4.8, 5, NULL, 15, 'all', 'Srirangam, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:57', 0),
(1044, 3, 'ChIJVVUBerH1qjsRaqlpEbHmXAY', 'ANJV Software Solutions Pvt Ltd', '', '090802 23188', 'anjvsoft@gmail.com', 'NO.3, Gem Plaza, Ground floor, chatram bus stand, Tiruchirappalli, Tamil Nadu 620002, India', 'http://www.anjvsoft.com/', 1, 12, 4.9, 9, NULL, 15, 'all', 'Srirangam, India', 'Software solutions', 0, NULL, '2026-04-30 17:12:58', 0),
(1045, 3, 'ChIJgw9GyiLfqjsRAMtlbsrnRXQ', 'Jamaito Solutions - IT Development and AI-Powered Digital Marketing', '', '096889 44767', '', '43MC+595, Trichy Main Rd, MIN Nagar, Ariyalur, Tamil Nadu 621704, India', 'https://jamaito.com/', 1, 2, 5.0, 26, NULL, 21, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:16', 0),
(1046, 3, 'ChIJ_x2AN1krqzsRdx1Hzt23Jss', 'Towards Technology', '', '098944 67245', 'info@towardstechno.com', 'First Floor, 9A4, Sevaga St, opposite to VM Hospital, Jayankondam, Tamil Nadu 621802, India', 'http://www.towardstechnology.net/', 1, 3, 4.8, 234, NULL, 35, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:26', 0),
(1047, 3, 'ChIJiyAElLrfqjsRTt_QBF0FSPI', 'PC Wizard computer\'s complete solution', '', '086680 59492', '', 'Indiragandhi st, market, 25J, Pattunoolkara Theru, next to A S Hospital, Ethraj Nagar, Ariyalur, Tamil Nadu 621704, India', '', 0, 4, 4.9, 108, NULL, 85, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:28', 0),
(1048, 3, 'ChIJt--9xPPfqjsRfQa3aYXepyE', 'Smart System', '', '096263 64776', '', 'Kalai Complex, Court Street, MIN Nagar, Ariyalur, Tamil Nadu 621704, India', 'https://www.smartsystem.com/', 1, 5, 4.9, 35, NULL, 27, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:35', 1),
(1049, 3, 'ChIJd9VsUG3fqjsRfvY-tPWDiOw', 'Phoenix Service Ulagam - Epson Authorised Service Centre,Computer, Laptop, Repairs | CSC & Insurance Services in Ariyalur', '', '094883 86360', '', '8, 2305-2, Jayankondam Rd, opp. COLLECTOR OFFICE, MIN Nagar, Ariyalur, Tamil Nadu 621704, India', '', 0, 6, 4.8, 43, NULL, 77, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:36', 0),
(1050, 3, 'ChIJ95i5ECffqjsRU4kGqGh1DqM', 'Niransoft Technologies Private Limited', '', '', 'info@niransoft.com', 'Mela, Nadu Agraharam St, MIN Nagar, Ariyalur, Tamil Nadu 621704, India', 'https://www.niransoft.com/', 1, 7, 5.0, 3, NULL, 10, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:38', 0),
(1051, 3, 'ChIJvVvym4shqzsRuklrlniUouE', 'KS communication online services and Xerox Ariyalur', '', '', '', 'H6A, JJ NAGAR, Sendurai Rd, Subramaniya Nagar, Ariyalur, Tamil Nadu 621713, India', '', 0, 8, 5.0, 43, NULL, 72, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:40', 0),
(1052, 3, 'ChIJ9-ZbY8keBA0RLcV43eLb83w', 'Vision7 Tech Solutions - Digital Marketing Company Ariyalur', '', '096004 14394', '', 'South Street, 1/128, Sendurai Rd, Vila Ngara, Ariyalur, Tamil Nadu 621704, India', '', 0, 9, 5.0, 3, NULL, 65, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:42', 0),
(1053, 3, 'ChIJZ4MuPkohqzsR6k5DzTlzBsE', 'Ariyalur Home Appliances & Electronics', '', '083444 10011', '', '18, Kamarajan Street, 6th Medical, College Road, Kamarajar Nagar, Ariyalur, Tamil Nadu 621704, India', '', 0, 10, 5.0, 2, NULL, 65, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:44', 0),
(1054, 3, 'ChIJM9HscYIgqzsRboHFIQ7oQSM', 'GeeVee Systems', '', '098424 85552', '', '43VC+6QW, Rajajinagar, Ariyalur, Tamil Nadu 621704, India', '', 0, 11, 4.1, 62, NULL, 77, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:46', 0),
(1055, 3, 'ChIJw8mCoPzfqjsRJclA82i6EL4', 'Sky Computer Education and Technology', '', '', '', 'No. 36, Raghavan Shopping Complex, Vellalar St, near National Super Market, Meala Agraharam, MIN Nagar, Ariyalur, Tamil Nadu 621704, India', '', 0, 12, 4.7, 18, NULL, 66, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:48', 0),
(1056, 3, 'ChIJ3x7LadTfqjsRdfQt0ysAPuE', 'SMS XEROX & ONLINE SERVICES', '', '', '', '45, Court Street, MIN Nagar, Ariyalur, Tamil Nadu 621704, India', '', 0, 13, 4.9, 23, NULL, 66, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:50', 0),
(1057, 3, 'ChIJ2ZM-PHzfqjsR6rtW0R0fWz0', 'RRB COMPUTERS', '', '094434 11131', '', 'SIVAPERUMAL STREET, 43QC+CCM, Ethraj Nagar, Ariyalur, Tamil Nadu 621704, India', '', 0, 14, 4.3, 120, NULL, 85, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:53', 0),
(1058, 3, 'ChIJfT7SDq3fqjsRjz3sZkMi1N4', 'ANANDHA ELECTRONICS', '', '097513 47973', '', 'Ethraj Nagar, Ariyalur, Tamil Nadu 621704, India', '', 0, 15, 4.6, 23, NULL, 71, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:56', 0),
(1059, 3, 'ChIJ0QsT9FLfqjsREmc_OXrHLj0', 'MS XEROX ARIYALUR', '', '097885 05078', '', 'J R.J COMPLEX, 17, Vilangara St, Ethraj Nagar, Ariyalur, Tamil Nadu 621704, India', '', 0, 16, 4.9, 26, NULL, 71, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:58', 0),
(1060, 3, 'ChIJ9bTyok_RqjsRd_i9KhdjLDg', 'Sathya Agencies, Ariyalur - Electronics and Home Appliances Store - Buy Latest Mobiles, AC, LED TV, Washing Machine etc.', '', '095662 20985', 'info@sathyaindia.com', 'No. 5A & 5B, Market St, MIN Nagar, Ariyalur, Tamil Nadu 621704, India', 'https://www.sathya.store/', 1, 17, 4.9, 1425, NULL, 35, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:14:59', 0),
(1061, 3, 'ChIJNxxszyDfqjsRH0sHy3HIIp0', 'Sun Digital Ariyalur', '', '081109 56104', '', 'Sendurai Rd, opposite Niranjana Complex, Palpannai, Ethraj Nagar, Ariyalur, Tamil Nadu 621704, India', '', 0, 18, 5.0, 5, NULL, 65, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:01', 0),
(1062, 3, 'ChIJHxkSiXvfqjsRp66eoqX23BQ', 'CADD Centre', '', '097152 72662', '', '#9,II Floor ,Nadu Agraharam, MRF Upstairs, Ariyalur, MIN Nagar, Tamil Nadu, Ariyalur, Tamil Nadu 621704, India', 'https://www.caddcentre.com/?utm_source=gmb&utm_medium=organic&utm_campaign=sulekhapromanage-cadd-centre-ariyalur', 1, 19, 5.0, 19, NULL, 21, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:02', 0),
(1063, 3, 'ChIJMUL5fxTfqjsR1ccbc2koCQM', 'Leaders Higher Educational Services', '', '', '', '3nd Cross Rd, near by Kms Hospital, near by Ariyalur, Alagappa Nagar, Vila Ngara, Ariyalur, Tamil Nadu 621704, India', 'https://www.instagram.com/leaders_karthikeyan_ariyalur/', 0, 20, 5.0, 1, NULL, 60, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:03', 0),
(1064, 3, 'ChIJv-Xdg3vfqjsR1GJ6O7d9E3M', 'padhma digital', '', '084383 21518', '', 'Ariyalur South, Ariyalur, Tamil Nadu 621704, India', '', 0, 21, 3.9, 76, NULL, 67, 'all', 'Ariyalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:05', 0),
(1065, 3, 'ChIJfc0ze5MbqzsRUgQTeXhJDYI', 'A2 TECH', '', '084387 80088', 'support@a2tech.org', 'VASANTH&CO BACKSIDE, NO. 5, DEVAKI COMPLEX, opposite to CHINNAMANI RAJESWARI MAHAL, PALAKARAI, Thuraimangalam, Perambalur, Tamil Nadu 621220, India', 'https://a2tech.org/', 1, 2, 5.0, 178, NULL, 35, 'all', 'Perambalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:31', 1),
(1066, 3, 'ChIJnyjSf_QbqzsRnOAjdYR2V78', 'R Tech Solution', '', '093454 42659', 'info@rtechsolution.in', 'No.206A, 2nd Floor, Second Cross Road, Vivekanandhar Street, Post Office Salai, Super Nagar, Perambalur, Tamil Nadu 621212, India', 'https://rtechsolution.in/', 1, 3, 5.0, 85, NULL, 27, 'all', 'Perambalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:35', 0),
(1067, 3, 'ChIJ77QRTkUbqzsRKHhMJbXBY2I', 'Jet IT Solutions', '', '093633 45960', '', '41, Eachampatti, Perambalur, Tamil Nadu 621220, India', 'https://jetitsolution.com/', 1, 4, 5.0, 5, NULL, 15, 'all', 'Perambalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:36', 0),
(1068, 3, 'ChIJfSeRXgAbqzsRvLAuQH49qCo', 'Sumisa Technologies', '', '', 'contact@sumisatech.com', '6VR8+3H4, Samiyappa Nagar, Perambalur, Tamil Nadu 621212, India', 'https://sumisatech.com/', 1, 5, 5.0, 3, NULL, 10, 'all', 'Perambalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:36', 0),
(1069, 3, 'ChIJhQIoJJ0bqzsRGvNedyGSSBY', 'Math IT Solutions', '', '097897 96528', 'mathitslns@gmail.com', 'Rajanagar, Thuraimangalam, Perambalur, Tamil Nadu 621220, India', 'http://www.mathitsolutions.com/', 1, 6, 4.7, 21, NULL, 21, 'all', 'Perambalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:38', 0),
(1070, 3, 'ChIJadHT6isbqzsRMEFDSs-lMk8', 'Greator Software', '', '063856 29914', '', 'First Floor, No.1, Qasim Complex Abiramipuram, Permbalur Collector Office Road, Thuraimangalam, Perambalur, Tamil Nadu 621212, India', 'https://www.greatorsoftware.com/', 1, 7, 5.0, 2, NULL, 15, 'all', 'Perambalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:38', 0),
(1071, 3, 'ChIJJ-rI3IQbqzsRwMDhZZueHos', 'Anzee tech solution | IT | Online training | Devops | AWS | Azure | Cyber Security | Python | Ansible | Jenkins| AI', '', '091598 35086', '', 'No. 28, Postoffice, Old bus stand, 1st Cross Rd, near Post office, near nasma tailors, Thuraimangalam, Perambalur, Tamil Nadu 621220, India', 'https://www.anzeetechsolution.com/', 1, 8, NULL, 0, NULL, 5, 'all', 'Perambalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:38', 0),
(1072, 3, 'ChIJTWCHNG8bqzsRRS3YLm78anI', 'DREAMZ WORLD', '', '093677 99996', '', 'Attur - Perambalur Rd, Sungu Pettai, Perambalur, Tamil Nadu 621212, India', '', 0, 9, 4.8, 197, NULL, 85, 'all', 'Perambalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:42', 0),
(1073, 3, 'ChIJySFDonobqzsR9o9e9sfST_s', 'Analasoft Technologies', '', '099945 67247', 'info@analasoft.com', 'No 5,First Floor, Near Ulavar Santhai, N Madhavi Rd, Samiyappa Nagar, Perambalur, Tamil Nadu 621212, India', 'http://analasoft.com/', 1, 10, 5.0, 9, NULL, 15, 'all', 'Perambalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:43', 0),
(1074, 3, 'ChIJCyxKTKMbqzsRPpnmnRWsLZ0', 'SASS SYSTEMS AND SECURITY', '', '097513 77007', '', '137/B8-A1, II FLOOR, EVER GREEN PLAZA, Sungu Pettai, Venketwasapuram, Tamil Nadu 621212, India', '', 0, 11, 4.0, 10, NULL, 71, 'all', 'Perambalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:45', 0),
(1075, 3, 'ChIJw-PSWXEdqzsRXfQ7CQuWWJo', 'Elysium Academy', '', '094422 20202', 'info@elysiumacademy.org', '2nd Floor, Ponmanam Plaza, above Reliance Trends, near New Bus Stand, Thuraimangalam, Perambalur, Tamil Nadu 621220, India', 'https://elysiumacademy.org/elysium-academy-software-training-institute-in-perambalur/', 1, 12, 4.9, 86, NULL, 27, 'all', 'Perambalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:47', 0),
(1076, 3, 'ChIJ405SGngbqzsRvVMQn_rebc0', 'SRS COMPUTER & SERVICE', '', '073056 70008', '', '74, royal Enfield showroom, Sungu Pettai, Venketwasapuram, Tamil Nadu 621212, India', '', 0, 13, 3.8, 45, NULL, 67, 'all', 'Perambalur, India', 'IT services', 0, NULL, '2026-04-30 17:15:52', 0),
(1077, 3, 'ChIJK4A5ZIxmBzsR-u6QvDZuKLc', 'Ricky Systems', '', '099443 54726', '', '#2,First Floor,Anandhagiri 3rd Street, Kodaikanal, Tamil Nadu 624101, India', '', 0, 2, 4.9, 17, NULL, 71, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:16:47', 0),
(1078, 3, 'ChIJFY0BNGdmBzsR7zZoFiReUGg', 'ClifNet Computers', '', '093445 77771', '', 'Bazaar Rd, Kodaikanal, Tamil Nadu 624101, India', '', 0, 3, 4.9, 17, NULL, 71, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:16:49', 0),
(1079, 3, 'ChIJVSCoXvlnBzsRpu4lCpr0vY0', 'Kodai Computers', '', '096553 08070', '', 'Kamarajar Rd, Anna Nagar, Kodaikanal, Tamil Nadu 624101, India', '', 0, 4, 4.9, 87, NULL, 77, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:16:52', 0),
(1080, 3, 'ChIJOfmeTK9nBzsR4QI5WvvP9IU', 'Limras Eronet Broadband Services Pvt Ltd,. Kodaikanal', '', '094435 58235', '', 'MDR897, 91, Convent Rd, near Fire Service Station, Naidupuram, Kodaikanal, Tamil Nadu 624101, India', '', 0, 5, 4.0, 4, NULL, 65, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:16:54', 0),
(1081, 3, 'ChIJHfNDwqpnBzsR5yaieVJ44nE', 'Mobile tech Services', '', '081444 77222', '', 'moonjikal, Kodaikanal, Tamil Nadu 624101, India', '', 0, 6, 4.9, 244, NULL, 85, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:16:57', 0),
(1082, 3, 'ChIJpSMmq2BmBzsRp2fD_pSrjk0', 'Hi-Tech Internet Cafe', '', '084892 72077', '', '6FPV+673, Woodville Rd, Kodaikanal, Tamil Nadu 624101, India', '', 0, 7, 3.2, 26, NULL, 61, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:16:58', 0),
(1083, 3, 'ChIJI__uw3pnBzsRDfe5TZv7lZA', 'DJ COMPUTERS SALES & SERVICE', '', '098423 69490', '', 'DJ COMPUTERS SALES & SERVICE ,St.annes complex, Moonjikkal, Kodaikanal, Tamil Nadu 624101, India', '', 0, 8, 5.0, 15, NULL, 71, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:17:00', 0),
(1084, 3, 'ChIJ6SBZMsxnBzsR0LZaViMRP4s', 'kodaikanal cctv camera &real estates and properties', '', '094429 91723', 'arulkodaiit@gmail.com', 'Moonjikkal, Kodaikanal, dindigul, Tamil Nadu 624101, India', 'http://nithininfotech.in/', 1, 9, 4.8, 27, NULL, 21, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:17:00', 0),
(1085, 3, 'ChIJpepGJP9nBzsRXueVTz7NwM0', 'Zostel Kodaikanal', '', '044 4011 5829', '', '6FWW+4VC Sunnydale Bungalow, Sivanandi Rd, Kodaikanal, Tamil Nadu 624101, India', 'https://www.zostel.com/zostel/kodaikanal/kodaikanal-kdkh541/', 1, 10, 4.5, 3526, NULL, 35, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:17:01', 0),
(1086, 3, 'ChIJTQZK9AdnBzsRTKg-fYoccCA', 'kodaikanal taxi services', '', '', 'tajholidays61@gmail.com', 'SHOP - 23, SHOPPING COMPLEX, BUS STAND, Kodaikanal, Tamil Nadu 624101, India', 'https://kodaikanaltaxiservices.com/', 1, 11, 5.0, 24, NULL, 16, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:17:01', 0),
(1087, 3, 'ChIJYewoCmFmBzsRClnU57a7vww', 'ROHINI SECURITY SERVICES - RSS', '', '', '', 'Old, Bus Stand, Kodaikanal, Tamil Nadu 624101, India', 'http://rohinisecurityservices.com/', 1, 12, 4.8, 27, NULL, 16, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:17:02', 0),
(1088, 3, 'ChIJkT7jMrVnBzsRylwgtOg6c38', 'KODAI CAB', '', '097514 98168', 'kodaicab@gmail.com', 'Convent Rd, depot, Kodaikanal, Tamil Nadu 624101, India', 'http://kodaicab.com/', 1, 13, 4.8, 742, NULL, 35, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:17:03', 0),
(1089, 3, 'ChIJJcKSG_ZnBzsRsnX-ktdnVCQ', 'Kodai MB cab', '', '099424 72778', 'info@kodaimbcabsholidays.com', 'Sivanandi Rd, Kodaikanal, Tamil Nadu 624101, India', 'https://kodaimbcabsholidays.com/', 1, 14, 4.9, 145, NULL, 35, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:17:03', 0),
(1090, 3, 'ChIJCw1s4dNnBzsRu9OIh3D09f4', 'kodaikanal adventure jeep safari', '', '097916 77263', 'eshukutty1443@gmail.com', 'Convent Rd, Naidupuram, Kodaikanal, Tamil Nadu 624101, India', 'https://kodaikanaladventurejeepsafari.in/', 1, 15, 5.0, 5, NULL, 15, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:17:06', 0),
(1091, 3, 'ChIJA3AyDbNnBzsR5bZK2tQlo50', 'KODAI JOURNEY', '', '099522 27577', '', 'Bus stand, Kodaikanal, Tamil Nadu 624101, India', 'https://kodaijourney.com/', 1, 16, 4.9, 477, NULL, 35, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:17:07', 0),
(1092, 3, 'ChIJxdltSgNnBzsRgfZfsXsLLxM', 'Kodaikanal taxis', '', '082489 83815', '', 'MM St, near kodai international, Kodaikanal, Tamil Nadu 624101, India', 'https://kodaikanaltaxis.com/', 1, 17, 4.9, 23, NULL, 21, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:17:12', 0),
(1093, 3, 'ChIJo-woCmFmBzsRgPNt5kCsARM', 'MRR Tours & Travels Best Travels in Kodaikanal', '', '097884 56971', '', 'Seven Road, Junction, Kodaikanal, Tamil Nadu 624101, India', 'http://www.mrrtoursandtravels.com/', 1, 18, 4.9, 577, NULL, 35, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:17:13', 0),
(1094, 3, 'ChIJu8Z_RbFnBzsR3oeZDr87ZkA', 'Kodaikanal Call Taxi - Car Rental Hire in Kodaikanal - Best Travels in Kodaikanal - Cab Service in Kodaikanal', '', '075980 92864', '', '17/362, Fern Hill Rd, Kodaikanal, Tamil Nadu 624101, India', 'https://www.kodaikanalcalltaxi.com/', 1, 19, 4.9, 2301, NULL, 35, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:17:20', 0),
(1095, 3, 'ChIJYfwGPIpmBzsR_yrC0LhBQFE', 'Kodai Make My Cabs Tours and Travels in Kodaikanal - Car Rental Hire in Kodaikanal', '', '094868 41995', '', 'No 5, PCK Complex, Convent Road, Vilpatti Rd, near JC Residency, Naidupuram, Kodaikanal, Tamil Nadu 624101, India', 'https://www.kodaimakemycabs.com/', 1, 20, 4.9, 1271, NULL, 35, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:17:20', 0),
(1096, 3, 'ChIJ6aoiauFnBzsR_ENUolo2LXQ', 'Kodai Cabs', '', '082486 08745', '', 'Kamarajar Rd, Kodaikanal, Tamil Nadu 624101, India', 'http://www.kodaicabs.co.in/', 1, 21, 4.9, 192, NULL, 35, 'all', 'Kodaikanal, India', 'IT services', 0, NULL, '2026-04-30 17:17:21', 0),
(1097, 3, 'ChIJgQcmbz65qjsRzxyE6gpDrtY', 'TECHBINOS IT SERVICES LLP', '', '063797 96758', 'hrteam@techbinos.com', '5th Floor, Meenakshi Elite, STREET, Pudukkottai Rd, Cauvery Nagar West, Natchathira Nagar, Thanjavur, Tamil Nadu 613007, India', 'https://www.techbinos.com/', 1, 2, 4.9, 47, NULL, 27, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:17:54', 0),
(1098, 3, 'ChIJlUy1JK_HqjsRgpPV63sw7so', 'Endless Tech Solutions', '', '088708 69346', 'endlesstechsolutions.in@gmail.com', '1st Floor, ravi complex, No 2835, Town Police Station Road, near CRC Bus stop, Sundaram pillai Nagar, Thanjavur, Tamil Nadu 613008, India', 'http://endlesstechsolutions.in/', 1, 3, 5.0, 47, NULL, 27, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:00', 0),
(1099, 3, 'ChIJ7aDmiLG5qjsR6_5bHjD7tao', 'Sky Tech Solution', '', '087780 96147', 'info@myskytechsolution.com', 'Nakshatra Nagar, 7, 1st St, Natchathira Nagar, Thanjavur, Tamil Nadu 613005, India', 'https://myskytechsolution.com/', 1, 4, 4.6, 25, NULL, 21, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:01', 0),
(1100, 3, 'ChIJ0wG0uZu4qjsRDcMG-ArB17A', 'G Tech System Service And Maintenance', '', '095857 58578', '', 'No 215, sarafoji Market East Gate, opposite to Arumuga Nadar maligai shop, Rajakrisnapuram, Thanjavur, Tamil Nadu 613001, India', '', 0, 5, 4.8, 357, NULL, 85, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:03', 0);
INSERT INTO `lead_gen_results` (`id`, `user_id`, `place_id`, `name`, `owner_name`, `phone`, `email`, `address`, `website`, `has_website`, `api_calls`, `rating`, `ratings_total`, `price_level`, `opportunity_score`, `search_mode`, `location`, `industry`, `imported`, `lead_id`, `created_at`, `website_found_by_crawler`) VALUES
(1101, 3, 'ChIJhXz8J424qjsREluBAQepoNg', 'YOGA\'S IT Solutions | Web Designing & Digital Marketing Company', '', '098942 34199', 'support@yogasgroup.org', 'Mercy Palace, No. 7, 2nd Street, Anna Nagar, Villar Road, Burma Colony, Thanjavur, Tamil Nadu 613006, India', 'https://www.yogasgroup.org/', 1, 6, 4.9, 160, NULL, 35, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:06', 0),
(1102, 3, 'ChIJMb5b6W65qjsRZOvVTEkB1XU', 'Compucare IT Corporation Thanjavur', '', '063856 68231', 'info@compucareit.com', '2nd Floor, upstairs of PPDS, Balu Complex, 1783, S Main St, Rajakrisnapuram, Thanjavur, Tamil Nadu 613009, India', 'http://www.compucareit.com/', 1, 7, 5.0, 35, NULL, 27, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:06', 0),
(1103, 3, 'ChIJIfTQuMq5qjsR4YB6dK1Bxbk', 'Tech Vaseegrah', '', '085240 89733', 'techvaseegrah@gmail.com', 'Vijaya Nagar, 11, post, near Rettipalaiyam Road, Srinivasapuram, Wahab Nagar, Thanjavur, Tamil Nadu 613009, India', 'https://www.techvaseegrah.com/', 1, 8, 5.0, 70, NULL, 27, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:06', 0),
(1104, 3, 'ChIJJclSV9S4qjsR7dCcfRvE2Og', 'TechSwing Solutions Pvt Ltd', '', '04362 225 066', 'info@tech-swing.com', '19, Raja Nagar Rd, near New Bus Stand Road, Lakshmi Nagar, New Housing Unit, Thanjavur, Tamil Nadu 613005, India', 'http://tech-swing.com/', 0, 9, 5.0, 2, NULL, 65, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:07', 0),
(1105, 3, 'ChIJFblvDba4qjsR3Whw6KhYWWs', 'Sardonyx Technologies Private Limited', '', '04362 243 433', '', '97/3,APM IT park,Palliagraharam,Perambalur to Manamadurai NH, Palliagraharam, Thanjavur, Tamil Nadu 613003, India', 'http://www.sardonyx.in/', 1, 10, 4.4, 210, NULL, 35, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:11', 0),
(1106, 3, 'ChIJoTby5TW5qjsRWxwYJO6eN5s', 'Alpha Software Solution', '', '098844 59869', '', '30/3,Arunagirinathar Street,Ezhil Nagar,Municipal Colony, Medical College Rd, Indira Nagar, Thanjavur, Tamil Nadu 613007, India', 'http://alphass.in/', 1, 11, 4.6, 31, NULL, 27, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:13', 0),
(1107, 3, 'ChIJu_WFfsVHVToR1l16Wq-T_r8', 'VVASAI Software Solutions Private Limited', '', '', 'info@vvasai.com', 'Jayam Nagar, ByPass, Gnanam Nagar, Thanjavur, Tamil Nadu 613001, India', 'https://vvasai.com/', 1, 12, 5.0, 5, NULL, 10, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:14', 0),
(1108, 3, 'ChIJY0kVlpq4qjsRk0Z_mj4Ir0w', 'isysway Computer Education |IELTS Spoken English in Thanjavur |Summer Courses in Thanjavur|CADD center in Thanjavur', '', '098940 47812', 'info@isysway.in', 'basement floor, Nallaiah Complex, railway station, near thanjavur, Graham Nagar, Shivaji Nagar, Thanjavur, Tamil Nadu 613001, India', 'http://www.isysway.in/', 1, 13, 4.9, 2474, NULL, 35, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:16', 0),
(1109, 3, 'ChIJO98mROy5qjsRsVLQ51dCXg8', 'Gurusoft Technology Private Limited', '', '099651 19255', 'sales@gurusoft.com.sg', 'Plot No.7, Madhakottai Rd, Geetha Nagar, Moovendhar Nagar, Annai Sathya Nagar, Thanjavur, Tamil Nadu 613005, India', 'https://www.gurusofttech.com/Contact-Us', 1, 14, 3.7, 23, NULL, 11, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:16', 0),
(1110, 3, 'ChIJ_WOy9rW5qjsRoagutORrJBs', 'TechSlide IT Solutions Pvt Ltd - Internet Marketing Service & Web Development Company', '', '097898 93722', 'info@consulting.com', '2 Indra Nagar 1st Cross, Medical College Rd, Eswari Nagar, Indira Nagar, Thanjavur, Tamil Nadu 613007, India', 'https://techslideitsolutions.com/', 1, 15, 4.5, 8, NULL, 15, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:18', 0),
(1111, 3, 'ChIJY0-NsuG5qjsRYlW6JhNDv3o', 'MONISA INFOTECH', '', '090781 45222', '', 'No .B, 120, Municipal Colony Rd, Eiswari Nagar, Thanjavur, Tamil Nadu 613007, India', '', 0, 16, 5.0, 13, NULL, 71, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:22', 0),
(1112, 3, 'ChIJEwAAADG_qjsRX6apm-1IFRc', 'Ebrain Technologies', '', '099440 07339', 'support@ebraintechnologies.com', '15, Second Floor, Karups Nagar, Trichy Main Road, next to Dr. Mohan\'s Diabetes Centre, AVP Azhagammal Nagar, Thanjavur, Tamil Nadu 613005, India', 'https://ebraintechnologies.com/', 1, 17, 4.9, 26, NULL, 21, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:22', 0),
(1113, 3, 'ChIJQXkjzWG5qjsRQE0P-SggDVo', 'Sumeru Technology Solutions Pvt Ltd', '', '', '', 'No.898, G1, 2nd Floor, HIG, Neithal Street, Trichy Main Rd, opp. Nasar Hotel, New Housing Unit, Thanjavur, Tamil Nadu 613005, India', 'http://www.sumerusolutions.com/', 1, 18, 5.0, 7, NULL, 10, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:22', 0),
(1114, 3, 'ChIJK6aZ5nO5qjsRPmRw7dLZ45k', 'Sivamsoft Infotech Private Limited', '', '070929 63387', '', 'No 63, Ganesh Nagar, Membalam, Thanjavur, Tamil Nadu 613007, India', '', 0, 19, 4.5, 2, NULL, 65, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:24', 0),
(1115, 3, 'ChIJsWML9oO5qjsRXTKXLuCfCMk', 'Rebelskool Consulting Pvt Ltd', '', '04362 450 352', '', '271,4th Street, NK Rd, Arokiya Nagar, Teachers Colony, Thanjavur, Tamil Nadu 613006, India', 'https://rebelskool.com/', 1, 20, 5.0, 2, NULL, 15, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:30', 0),
(1116, 3, 'ChIJETnElbu5qjsR8VK2vk04GBM', 'JSK COMPUTERS', '', '093604 26843', 'info@jskcomputers.in', 'Ukkadai Ambal Colony, M.Chavady, Thanjavur, Tamil Nadu 613001, India', 'https://www.jskcomputers.com/', 1, 21, 5.0, 1, NULL, 15, 'all', 'Thanjavur, India', 'IT services', 0, NULL, '2026-04-30 17:18:37', 0),
(1117, 3, 'ChIJF5go1ab1qjsRPFhS8S387ps', 'esoft IT Solutions.', '', '080724 20182', '', 'C, II-Floor, Land Mark: Lakshmi Complex, Bus Stop, 145/74, Salai Rd, Thillai Nagar East, Thillai Nagar, Tiruchirappalli, Tamil Nadu 620018, India', 'http://e-soft.in/', 1, 2, 4.9, 1012, NULL, 35, 'all', 'Tiruchirappalli, India', 'IT services', 0, NULL, '2026-04-30 17:19:08', 0),
(1118, 3, 'ChIJQ3rMYRr1qjsRjk4b9gj9-VY', 'Fantasy Solution | Software & Hardware Solutions | Best Project Center In Trichy', '', '090430 95535', 'info@fantasysolution.in', '16, Samnath Plazza, Third Floor, Madurai Main Rd, Melapudur, Sangillyandapuram, Tiruchirappalli, Tamil Nadu 620001, India', 'http://www.fantasysolution.in/', 1, 3, 4.7, 1128, NULL, 35, 'all', 'Tiruchirappalli, India', 'IT services', 0, NULL, '2026-04-30 17:19:09', 0),
(1119, 3, 'ChIJ4f___5BPqjsRR7uGj1sP5oE', 'Adssan IT', '', '097891 08542', 'sales@adssan.com', 'Om complex, 3/6, Sankaran Pillai Road, Melachinthamani, Tiruchirappalli, Tamil Nadu 620002, India', 'https://adssan.com/', 1, 4, 4.9, 114, NULL, 35, 'all', 'Tiruchirappalli, India', 'IT services', 0, NULL, '2026-04-30 17:19:11', 0),
(1120, 3, 'ChIJKaJbMo_1qjsRHzepslRlme0', 'Trichy IT Services', '', '096001 14466', '', 'New no: 12, old No: 14 7th main road, Vayalur Rd, Srinivase Nagar North, Srinivasa Nagar North, Tiruchirappalli, Tamil Nadu 620017, India', 'https://www.uniqtechnologies.co.in/', 1, 5, 3.1, 16, NULL, 11, 'all', 'Tiruchirappalli, India', 'IT services', 0, NULL, '2026-04-30 17:19:14', 0),
(1121, 3, 'ChIJabMD2071qjsRulIXjVDKD10', 'Rapport It Services Pvt Ltd', '', '', 'contact@rapporttalents.com', 'No 48, 4th Floor, Thanjavur Rd, North Ukkadai, Ariyamangalam Area, Tiruchirappalli, Tamil Nadu 620010, India', 'https://www.rapportit.com/', 1, 6, 3.7, 9, NULL, 0, 'all', 'Tiruchirappalli, India', 'IT services', 0, NULL, '2026-04-30 17:19:17', 0),
(1122, 3, 'ChIJgRvBJd_0qjsR1xnKwV0Ct80', 'Shalom Infotech', '', '093451 22554', 'info@shalominfotech.com', 'G3 Bharathidasan university technology park, Race Course Road, Tiruchirappalli, Tamil Nadu 620023, India', 'http://www.shalominfotech.com/', 1, 7, 4.2, 58, NULL, 27, 'all', 'Tiruchirappalli, India', 'IT services', 0, NULL, '2026-04-30 17:19:18', 0),
(1123, 3, 'ChIJsZiT9xD1qjsROiqeFjXk11Y', 'Vagus Technologies', '', '0431 241 5353', '', 'V Floor, Jenny Plaza, 5, Bharathiar Salai, Melapudur, Cantonment, Tiruchirappalli, Tamil Nadu 620001, India', 'http://www.vagustech.com/', 1, 8, 3.8, 126, NULL, 25, 'all', 'Tiruchirappalli, India', 'IT services', 0, NULL, '2026-04-30 17:19:21', 0),
(1124, 3, 'ChIJS55BMlX1qjsRHAm5UySo6zY', 'Sysent Technologies', '', '', 'info@sysentech.com', '3rd Floor, Gem Plaza, No.3, College Rd, near SRC, near Periyasamy Tower, Melachinthamani, Tiruchirappalli, Tamil Nadu 620002, India', 'https://sysentech.com/', 1, 9, 5.0, 28, NULL, 16, 'all', 'Tiruchirappalli, India', 'IT services', 0, NULL, '2026-04-30 17:19:25', 0),
(1125, 3, 'ChIJx-PXyPbT-joRgWhxIs057b4', 'Lushanth Pvt Ltd', '', '077 425 2727', 'info@lushanth.com', '73/1 New Boundary Rd, Batticaloa 30000, Sri Lanka', 'http://lushanth.com/', 1, 2, 5.0, 26, NULL, 21, 'all', 'Batticola, India', 'Private Limited', 0, NULL, '2026-04-30 17:24:33', 0),
(1126, 3, 'ChIJa6kQeakz5ToRzeMYy3x9bPo', 'Brandix Apparel Solutions (Pvt) Ltd - Batticaloa', '', '0654 653 555', 'hello@oddly.co', '30000, Sri Lanka', 'http://www.brandix.com/', 0, 3, 4.5, 150, NULL, 85, 'all', 'Batticola, India', 'Private Limited', 0, NULL, '2026-04-30 17:24:35', 0),
(1127, 3, 'ChIJHcNXAADN-joRXOMriWYamlA', 'SUTHA INDUSTRIES pvt ltd', '', '076 081 6436', 'suthaindustries2003@gmail.com', 'PM7W+QFJ, Munai St, Batticaloa, Sri Lanka', 'http://suthaindustries.com/', 1, 4, 5.0, 18, NULL, 21, 'all', 'Batticola, India', 'Private Limited', 0, NULL, '2026-04-30 17:24:36', 0),
(1128, 3, 'ChIJHaFwn8jN-joR24vAR14o4G4', 'SPM Technologies', '', '074 016 3180', 'tech@spm.indust', '26/4A Boundary Rd S, Batticaloa 30000, Sri Lanka', 'https://tech.spm.industries/', 1, 5, 5.0, 5, NULL, 15, 'all', 'Batticola, India', 'Private Limited', 0, NULL, '2026-04-30 17:24:38', 0),
(1129, 3, 'ChIJ21RN3VDN-joReEdaIOHqLNk', 'Sattar Textiles (Pvt) Ltd | சத்தார் டெக்ஸ்டைல்ஸ்', '', '077 675 1525', '', '72 Main Street, Batticaloa 30000, Sri Lanka', 'https://www.sattartextiles.com/', 1, 6, 3.9, 307, NULL, 25, 'all', 'Batticola, India', 'Private Limited', 0, NULL, '2026-04-30 17:24:47', 1),
(1130, 3, 'ChIJN4YKaA_N-joRp8Y0LCfKeCU', 'SPM', '', '0652 223 443', 'info@spm.indust', 'No: 57 Lloyd\'s Ave, Batticaloa 30000, Sri Lanka', 'https://spm.industries/', 1, 7, 5.0, 10, NULL, 21, 'all', 'Batticola, India', 'Private Limited', 0, NULL, '2026-04-30 17:24:49', 0),
(1131, 3, 'ChIJsXuiWbnN-joR6mccXC7bonM', 'The Traveller Global (pvt) ltd', '', '0652 223 399', '', '373B Trinco Rd, Batticaloa 30000, Sri Lanka', 'https://www.travellerglobal.com/', 1, 8, 4.5, 11, NULL, 21, 'all', 'Batticola, India', 'Private Limited', 0, NULL, '2026-04-30 17:24:53', 1),
(1132, 3, 'ChIJA_tZdADN-joR6ma4-SQb0Nc', 'Organo Family Restaurant (pvt) ltd', '', '075 432 4315', '', 'PM7X+4R5, Chapel St, Batticaloa, Sri Lanka', '', 0, 9, 4.0, 10, NULL, 71, 'all', 'Batticola, India', 'Private Limited', 0, NULL, '2026-04-30 17:24:56', 0),
(1133, 3, 'ChIJGY-CAfXN-joR5yIA8hpMBDg', 'SPM Renewables', '', '074 016 3181', 'renewables@spm.indust', '26/4A Boundary Rd S, Batticaloa 30000, Sri Lanka', 'https://renewables.spm.industries/', 1, 10, 4.9, 8, NULL, 15, 'all', 'Batticola, India', 'Private Limited', 0, NULL, '2026-04-30 17:24:57', 0),
(1134, 3, 'ChIJT4i24lvN-joRDs9RQngehYg', 'Sunshine', '', '0654 927 927', '', '136 Trinco Rd, Batticaloa 30000, Sri Lanka', 'http://www.tomato.lk/', 1, 11, 3.6, 877, 2, 33, 'all', 'Batticola, India', 'Private Limited', 0, NULL, '2026-04-30 17:24:58', 0),
(1135, 3, 'ChIJiRpQIDcRVToR9_g6XQclAwU', 'Gail India Ltd', '', '04368 220 949', 'info@gailindialimited.co.in', '1st & 2nd Floor, AHM Complex,Opposite to Uniqcon Plaza, Kamarajar Salai, Karaikal, Puducherry 609602, India', 'https://www.gailonline.com/', 1, 2, 4.3, 44, NULL, 27, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:20', 0),
(1136, 3, 'ChIJC12u7wAXVToR3YZ5klRClZg', 'Dpay Consultancy Services Private limited', '', '063840 55055', '', '29, 2nd Cross St, PSR Gold nagar, PSR Nagar, Karaikal, Puducherry 609602, India', '', 0, 3, 5.0, 10, NULL, 71, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:25', 0),
(1137, 3, 'ChIJC8qUzDMXVToR4AMeEjBMb5M', 'La Gravity ventures pvt.ltd', '', '', '', '155/1, RR.Garden Extension, Karaikal, Kottucherry, Puducherry 609609, India', '', 0, 4, NULL, 0, NULL, 50, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:27', 0),
(1138, 3, 'ChIJuWhYFYQTVToRneCIcI9FkeA', 'Deltafy Solutions Private Limited - #1 Software Testing Company', '', '093451 64446', '', 'East Coast Rd, Nagore, Karaikal, Puducherry 609604, India', 'https://www.deltafyqa.com/%20https://www.deltafy.in/%20%20https://www.deltafy.digital/', 1, 5, 4.9, 14, NULL, 21, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:27', 0),
(1139, 3, 'ChIJDfMIPrkRVToRITIqHgxtZ4I', 'WINAIR HVAC Solution Pvt. Ltd.', '', '063802 65039', '', '16, VG Nagar, Nitheeswaram, Karaikal, Puducherry 609602, India', '', 0, 6, 5.0, 6, NULL, 65, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:29', 0),
(1140, 3, 'ChIJS33tczERVToRiEIMoFMufbQ', 'soundarya safety device sales & services private Limited', '', '094438 85197', '', '113/8, Nehru Street,Mathakady, near New Bridge, Karaikal, Puducherry 609602, India', '', 0, 7, 4.7, 7, NULL, 65, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:31', 0),
(1141, 3, 'ChIJWdRTqjkRVToRmIXfoK5T-_Q', 'Royal Square private limited', '', '', '', 'WRGV+VCC, Karaikal Bypass Rd, Karaikal, Puducherry 609602, India', '', 0, 8, 4.2, 33, NULL, 72, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:33', 0),
(1142, 3, 'ChIJCZC_OeYTVToRh1pCJNFHaYQ', 'Seagrass Tech Private Limited', '', '079048 40244', 'info@seagrasstech.com', 'No: 32, Akkaraivattam village, Nagore Main Road, Karaikal, Puducherry 609604, India', 'http://seagrasstech.com/', 1, 9, 3.6, 5, NULL, 5, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:36', 0),
(1143, 3, 'ChIJC-q8SjYRVToRd3wPziH8mwg', 'SIS INDIA LIMITED- BO KARAIKAL', '', '', '', 'WRGQ+55F, Karaikal, Puducherry 609602, India', '', 0, 10, 4.0, 1, NULL, 60, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:39', 0),
(1144, 3, 'ChIJ5c0NitkXVToROSuMGsCsT08', 'HyperDynamics Private Limited', '', '088258 02996', '', '5, Majestic colony, Puthuthurai, Dharmapuram, Karaikal, Puducherry 609602, India', '', 0, 11, NULL, 0, NULL, 55, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:41', 0),
(1145, 3, 'ChIJNdXxeDURVToRb3WLez5XR0c', 'K TRADING COMPANY', '', '094877 72882', 'joseph.khayrallah@ktrading.net', 'Chennai - Nagapattinam Highway, 187, Bharathiyar Rd, Karaikal, Puducherry 609602, India', 'https://www.ktrading.net/', 1, 12, 4.9, 11, NULL, 21, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:48', 1),
(1146, 3, 'ChIJNVCoAmMRVToRjVhBRmRvGQc', 'AADHINETRA TECHNOLOGIES PVT LTD.,', '', '', '', '20/B, BALAKRISHNA NAGAR, P.K. Salai, Karaikal, Puducherry 609602, India', '', 0, 13, 5.0, 2, NULL, 60, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:51', 0),
(1147, 3, 'ChIJs4p_PUoRVToRauKa_1LqZjI', 'AHSAN SOLUTIONS', '', '', '', '1B, Jubaitha, Ganesh Nagar 2nd Cross, Karaikal, Puducherry 609602, India', 'http://ahsansolutions.com/', 1, 14, 5.0, 2, NULL, 10, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:52', 0),
(1148, 3, 'ChIJtaqqqi4RVToR6DP-sA6zXXM', 'Team Trans Logistics Pvt Ltd-Karaikal', '', '096262 88406', '', 'No.422/2,1st Floor, Bharathiar Road, Thalathiru Post, Nehrunagar, Karaikal, Puducherry 609605, India', 'http://www.teamtranslogistics.com/', 1, 15, 5.0, 1, NULL, 15, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:55', 0),
(1149, 3, 'ChIJzRXaBHURVToR-iElhdVssPM', 'GAC Shipping (India) Private Limited', '', '044 2522 1588', 'webmaster@gac.com', 'AVJ Complex, Second Floor, Door. 6 Bharathiar Road, Thalatheru Post Karaikal Puducherry, Nehrunagar, Karaikal, Tamil Nadu 609605, India', 'https://gac.com/india', 1, 16, NULL, 0, NULL, 5, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:56', 0),
(1150, 3, 'ChIJRRlxdTURVToRhBprjbm9mxI', 'The New India Assurance Company Limited', '', '04368 222 652', '', 'WRGJ+2X7, Karaikal, Puducherry 609602, India', '', 0, 17, 4.3, 30, NULL, 77, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:58', 0),
(1151, 3, 'ChIJvaxDhiURVToRISjqYkehvtc', 'Safexpress Pvt. Ltd.', '', '1800 11 3113', '', 'Idumban Chettiyar Salai, Karaikal, Puducherry 609605, India', 'https://www.safexpress.com/', 1, 18, 1.5, 2, NULL, 5, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:25:58', 0),
(1152, 3, 'ChIJjbOytDMRVToRGSkZuqIuqQs', 'Kals Beverages Private Limited', '', '', '', '43, Pulian Kottai Salai, Karaikal, Puducherry 609602, India', '', 0, 19, NULL, 0, NULL, 50, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:26:00', 0),
(1153, 3, 'ChIJ4xRjoksRVToRGJEoCPxlXro', 'PSR Thangamaligai Pvt. Ltd.', '', '093630 51210', 'psrgroupss@gmail.com', '102,Bharathiyar Road, East, Coast Road, Masthan, Karaikal, Puducherry 609602, India', 'https://psrthangamaligai.com/', 1, 20, 4.6, 2365, NULL, 35, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:26:01', 0),
(1154, 3, 'ChIJk-UK0o4RVToR6Iy1XQpgz1k', 'KTM SHOWROOM KARAIKAL ( AAKASH AUTOMOBILES PVT LTD)', '', '', '', '42, Jawaharlal Nehru St, Karaikal, Puducherry 609602, India', 'https://www.instagram.com/ktmkaraikal?igsh=MTM5dWN4cGd6bXVyOA==', 0, 21, 5.0, 7, NULL, 60, 'all', 'Karaikal, India', 'Private Limited', 0, NULL, '2026-04-30 17:26:03', 0),
(1155, 3, 'ChIJ2RlywH1hUzoRgjowaBQamgA', 'NKINFYX GROUPS PRIVATE LIMITED (Puducherry Administrative Office)', '', '', 'info@nkinfyxgroups.in', '1st Floor, 9, 3rd Cross St, Saranarayana Nagar, Reddiarpalayam, Puducherry, 605010, India', 'https://nkinfyxgroups.in/', 1, 2, 5.0, 14, NULL, 16, 'all', 'Pondicherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:26:30', 0),
(1156, 3, 'ChIJuZftaUyeVDoRYsOeofQfOBg', 'PondyBiz Technology Solutions Private Limited', '', '097901 14036', '', 'No. 72, Landmark building, 100 Feet Rd, Jhansi Nagar, Sundararaja Nagar, Mudaliarpet, Puducherry, 605004, India', '', 0, 3, 4.2, 12, NULL, 71, 'all', 'Pondicherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:26:31', 0),
(1157, 3, 'ChIJIWCp9nlhUzoRRQDSNkfrKGk', 'Syscorp Technology Pvt Ltd', '', '', 'sales@itsk.in', 'No.37, Kamaraj Salai, Thattanchavady, Puducherry, 605009, India', 'https://syscorp.in/?utm_source=Google&utm_medium=organic&utm_campaign=gbp_localSEO_oct08', 1, 4, 4.8, 33, NULL, 22, 'all', 'Pondicherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:26:32', 0),
(1158, 3, 'ChIJdQiMUHxhUzoRSa4fDI-56fk', 'SSG Consulting India Private Limited', '', '0413 221 2774', '', '#11 Salai Vinayagar Temple street 45 Feet Road Corner, Venkata Nagar, Puducherry, 605011, India', 'https://www.rencata.com/', 1, 5, 4.4, 33, NULL, 27, 'all', 'Pondicherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:26:35', 0),
(1159, 3, 'ChIJ2RlywH1hUzoRgjowaBQamgA', 'NKINFYX GROUPS PRIVATE LIMITED (Puducherry Administrative Office)', '', '', 'info@nkinfyxgroups.in', '1st Floor, 9, 3rd Cross St, Saranarayana Nagar, Reddiarpalayam, Puducherry, 605010, India', 'https://nkinfyxgroups.in/', 1, 2, 5.0, 14, NULL, 16, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:14', 0),
(1160, 3, 'ChIJ5azlrdRhUzoR39BzMHTOmXE', 'Namlatech India Private Limited', '', '093609 65067', 'smourali@namlatic.com', 'No 2 Ground Floor Rangaswamy Nagar, 3rd Cross St, Murungapakkam, Puducherry, 605004, India', 'http://www.namlatech.com/', 1, 3, 4.9, 9, NULL, 15, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:17', 0),
(1161, 3, 'ChIJcX5jRy5hUzoREkQNvdNY6y4', 'MILKY HAVEN INDIA PRIVATE LIMITED (CORPORATE OFFICE)', '', '0413 235 5662', 'ustomercare@milkyhaven.com', 'Plot 217, First Floor, Bharathiyar Street, 4th Cross Ext, Jayamurthy Raja Nagar, Ozhandai Keerapalaiyam, Mudaliarpet, Puducherry, 605004, India', 'https://milkyhaven.com/', 1, 4, 5.0, 49, NULL, 27, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:18', 0),
(1162, 3, 'ChIJzwZCLiSfVDoRbG5D8vV5y74', 'Amcor Flexibles India Pvt Ltd.', '', '', 'amcor.digital@amcor.com', 'Bahour Commune, Cuddalore Road, Kandanpet Village, Kattukuppam, Pillayarkuppam, Kattukuppam, Puducherry 607403, India', 'https://www.amcor.com/', 1, 5, 4.4, 17, NULL, 16, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:19', 0),
(1163, 3, 'ChIJhwCtoUJnUzoRdSjyaUw_FIw', 'EMOX MANUFACTURING PVT. LTD.', '', '0413 267 7343', 'velly@emox.co.in', 'Sedarapet, Puducherry, 605111, India', 'http://www.emox.co.in/', 1, 6, 4.1, 47, NULL, 27, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:20', 0),
(1164, 3, 'ChIJwxITNWxhUzoRU2ihDipkzis', 'Tender Software India Private Limited', '', '097878 72738', 'info@tendersoftware.in', '1st, 2nd and 3rd floor, Thiru Arcade, 3, Arul Nesan Street, Pazhani Raja Udayar Nagar, Lawspet, Puducherry, 605008, India', 'https://tendersoftware.in/', 1, 7, 4.0, 39, NULL, 27, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:22', 0),
(1165, 3, 'ChIJA4sbgDqeVDoRKPtx62Tf05A', 'Lenovo India Private Limited', '', '0413 261 9400', 'orderstatus1@lenovo.com', '1A, Edayarpalayam, Puducherry 605007, India', 'https://www.lenovo.com/in/en/', 1, 8, 4.2, 69, NULL, 27, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:23', 0),
(1166, 3, 'ChIJ9dwJpgNhUzoRU5Sa84V9KDA', 'EnterpriseMinds India Private Limited', '', '073396 27828', '', 'Manatec Towers, Lawspet Main Road, Gnanapragasam Nagar, Saram, Puducherry, 605008, India', 'https://www.eminds.ai/', 1, 9, 5.0, 2, NULL, 15, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:25', 0),
(1167, 3, 'ChIJe0wECHxhUzoR_lTjkBUJGgM', 'Plumage Technology Private Limited', '', '', 'sales@plumagetech.com', 'R.S.No, 17/2, Gothi Industrial Complex, Main Rd, Kurumbapet, Marie Oulgaret, Vazhudavur, Villianur, Puducherry 605009, India', 'https://plumagetech.com/', 1, 10, 4.6, 13, NULL, 16, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:25', 0),
(1168, 3, 'ChIJuZftaUyeVDoRYsOeofQfOBg', 'PondyBiz Technology Solutions Private Limited', '', '097901 14036', '', 'No. 72, Landmark building, 100 Feet Rd, Jhansi Nagar, Sundararaja Nagar, Mudaliarpet, Puducherry, 605004, India', '', 0, 11, 4.2, 12, NULL, 71, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:26', 0),
(1169, 3, 'ChIJIWCp9nlhUzoRRQDSNkfrKGk', 'Syscorp Technology Pvt Ltd', '', '', 'sales@itsk.in', 'No.37, Kamaraj Salai, Thattanchavady, Puducherry, 605009, India', 'https://syscorp.in/?utm_source=Google&utm_medium=organic&utm_campaign=gbp_localSEO_oct08', 1, 12, 4.8, 33, NULL, 22, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:26', 0),
(1170, 3, 'ChIJ_cA7J2BnUzoRaks4EHlzNms', 'Ganges Internationale Pvt Ltd', '', '011 4709 0225', 'contactus@gangesintl.com', 'XPXR+G29, Sedarapet, Puducherry 605109, India', 'http://www.gangesintl.com/', 1, 13, 3.7, 132, NULL, 25, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:29', 0),
(1171, 3, 'ChIJQzsYbqdhUzoRxedvpLrEr2U', 'Roadmap IT Solutions Pvt Ltd', '', '0413 420 7333', 'sweetalert2@11.js', '5, Republic St, behind Sun Pharmacy, Kavery Nagar, Reddiarpalayam, Puducherry, 605010, India', 'https://roadmapit.com/', 1, 14, 4.0, 114, NULL, 35, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:32', 0),
(1172, 3, 'ChIJ0-ulL9heUzoRWZS3PALKQfw', 'Allianz Biosciences Pvt Ltd', '', '044 4205 0273', 'info@abpl.co.in', 'R.S. No.55/1,2, 3, Whirlpool Road, Thiruvandarkoil, Puducherry 605102, India', 'http://abpl.co.in/', 1, 15, 4.7, 45, NULL, 27, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:34', 0),
(1173, 3, 'ChIJvzo_odRhUzoR63E4bf3jVho', 'LDBS India Private Limited', '', '075989 09688', '', '38, 5th Cross Street, nearby Ragavendira Temple, Ragavendra Nagar, Vallalar Nagar, Reddiarpalayam, Boomiyanpet, Puducherry, 605005, India', 'https://ldbsindia.com/', 1, 16, 3.7, 3, NULL, 5, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:35', 0),
(1174, 3, 'ChIJ03cXaFRhUzoRdHVz62g0TAM', 'Fastenex Private Limited', '', '', 'sales@fastenex.co.in', 'Industrial Estate, B4, 3rd street, near Muruga Theater signal, Anandapuram, Thattanchavady, Puducherry, 605009, India', 'https://fastenex.co.in/', 1, 17, 4.4, 12, NULL, 16, 'all', 'Puducherry, India', 'Private Limited', 0, NULL, '2026-04-30 17:28:36', 0),
(1175, 3, 'ChIJvRqi367FADsRALdRwx6yuPg', 'Elysium Technologies Private Limited', '', '099447 93398', 'info@elysiumtechnologies.com', 'Ground Floor, A Block, Elysium Campus, 229, Church Rd, Anna Nagar, Madurai, Sathamangalam, Tamil Nadu 625020, India', 'https://elysiumtechnologies.com/', 1, 2, 4.0, 237, NULL, 35, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:03', 0),
(1176, 3, 'ChIJ5TnUwBjTADsRMKc5uyDVNYI', 'Notasco Technologies India Pvt Ltd', '', '090427 72367', '', '2nd Floor, Srinivasa Nagar, 409/3, Madurai Rd, behind Trends, Tirumangalam, Madurai, Tamil Nadu 625706, India', 'https://notasco.com/', 1, 3, 5.0, 278, NULL, 35, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:03', 0),
(1177, 3, 'ChIJqVPP6uXFADsRocr-H8ckeXI', 'Abservetech Private Limited', '', '092223 79222', 'support@abservetech.com', '146-147, Vakkil New St, Simmakkal, Madurai Main, Madurai, Tamil Nadu 625001, India', 'https://www.abservetech.com/', 1, 4, 4.6, 604, NULL, 35, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:04', 0),
(1178, 3, 'ChIJD-EOjavFADsR3_RLIWKucEo', 'HIYA TechSolutions Private Limited', '', '0452 425 0749', 'info@hiya.tech', '5/389 VOC Main Street, Thasildhar Nagar, Mandure, Vandiyur, Madurai, Tamil Nadu 625020, India', 'http://www.hiya.tech/', 1, 5, 3.9, 124, NULL, 25, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:08', 0),
(1179, 3, 'ChIJveDLOrbFADsRpIUb2__SpV4', 'OptiSol Business Solutions Pvt Ltd - Madurai', '', '0452 439 2350', 'info@optisolbusiness.com', 'OptiSol Business Solutions Pvt Ltd, Kamala 2nd St, Chinna Chokikulam, Madurai, Tamil Nadu 625002, India', 'http://www.optisolbusiness.com/', 1, 6, 4.5, 46, NULL, 27, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:09', 0),
(1180, 3, 'ChIJm10XsojFADsRX34KOBIrJxU', 'SATHYA Technosoft India Private Limited', '', '099523 00300', 'support@sathyainfo.com', '16-17, B 1st Floor, A.R. Plaza, N Veli St, Simmakkal, Madurai Main, Madurai, Poondhotam, Tamil Nadu 625001, India', 'https://www.sathyainfo.com/', 1, 7, 4.7, 63, NULL, 27, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:09', 0),
(1181, 3, 'ChIJddwVjK7FADsRpqxk-ABaMO0', 'Chella Software Private Limited', '', '095009 80413', 'business@chelsoft.com', 'Plot No.6, ELCOT - SEZ, Ilandhaikulam, Pandi Kovil Ring Rd, Madurai, Tamil Nadu 625020, India', 'http://www.chelsoft.com/', 1, 8, 4.8, 32, NULL, 27, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:10', 0),
(1182, 3, 'ChIJP-tHlz3PADsR-s0u8vWYLSU', 'Zlendo Technologies Private Limited', '', '090379 77475', 'contact@zlendo.com', '36/1, Ganapathy St, Alagappan Nagar, Madurai, Tamil Nadu 625003, India', 'https://zlendo.com/', 1, 9, 4.7, 17, NULL, 21, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:12', 0),
(1183, 3, 'ChIJSfnLFmnPADsRgmUWOHoZ5w8', 'Vinsup Infotech (P) Limited', '', '094896 49696', 'vinsupacademy.madurai@gmail.com', 'No.246, 1st Floor, P.M.Tower, Kalavasal Signal, Kalavasal, Madurai, Tamil Nadu 625016, India', 'https://vinsupacademy.in/', 1, 10, 4.8, 146, NULL, 35, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:14', 0),
(1184, 3, 'ChIJAU0JgM7FADsRYsIP1ZFbIRY', 'Sun Pressing Pvt. Ltd Nippon', '', '080125 13000', 'nippon@sunpressing.com', 'F2 & 3, SIDCO, Industrial Estate, K.Pudur, Tamil Nadu 625007, India', 'http://www.sunpressing.com/', 0, 11, 3.8, 16, NULL, 61, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:17', 0),
(1185, 3, 'ChIJCbMym6XFADsR8XRoxzkdYFU', 'Elysium Group of Companies', '', '0452 439 0702', 'info@elysiumgroups.com', '230, Church Rd, Vaigai Colony, Madurai, Sathamangalam, Tamil Nadu 625020, India', 'http://elysiumgroups.com/', 1, 12, 4.6, 189, NULL, 35, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:18', 0),
(1186, 3, 'ChIJodUOEO7FADsRG4DeNvGAyMs', 'SG private limited', '', '', '', 'SVSK TOWERS, Gandhi Nagar, Shenoy Nagar, Chinna Chokikulam, Madurai, Tamil Nadu 625020, India', '', 0, 13, 3.5, 22, NULL, 56, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:22', 0),
(1187, 3, 'ChIJyfqFsoHFADsRreVMO62H1-E', 'Blaze Web Services Pvt Ltd', '', '0452 437 6515', 'ba@blazehexa.com', '48/A2, Kanmaikarai Main Rd, Pethaniapuram 2, Kalavasal, Pandian Nagar, Madurai, Tamil Nadu 625016, India', 'https://blazehexa.com/', 1, 14, 4.7, 67, NULL, 27, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:24', 0),
(1188, 3, 'ChIJYTeIGgXFADsRNcus9GC5fGI', 'Wtilth Technologies Private Limited', '', '063803 76807', '', '692/6A, Kannadasan St, Bharathi Veethi, Gomathipuram, Madurai, Tamil Nadu 625020, India', 'http://www.wtilth.com/', 1, 15, 5.0, 19, NULL, 21, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:28', 0),
(1189, 3, 'ChIJ8YeFgYjFADsRgSwrS69ttKk', 'Cogzidel Technologies', '', '0452 234 4002', '', '184, N Veli St, Madurai Main, Madurai, Tamil Nadu 625001, India', '', 0, 16, 3.2, 132, NULL, 75, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:31', 0),
(1190, 3, 'ChIJw-DqzUvPADsR9t7hGdflO4w', '10decoders Consultancy Services Pvt Ltd - Madurai', '', '', 'contact@10decoders.com', 'Sarvacenter Building, 37, Madakulam Main Rd, New Vilangudi, Vilangudi, Palangantham, Madurai, Tamil Nadu 625018, India', 'https://10decoders.com/contactus/', 1, 17, 4.3, 6, NULL, 10, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:33', 0),
(1191, 3, 'ChIJ7botFFnFADsRxgd4kaZI24A', 'Boostimize Technologies Pvt Ltd', '', '080726 27482', 'ceo@boostinise.ai', 'Plot No.8, 2nd Cross St, Kumaran Nagar, Tangamanal Nagar, S Alangulam, Madurai, Tamil Nadu 625017, India', 'https://www.boostimize.ai/', 1, 18, 5.0, 21, NULL, 21, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:35', 0),
(1192, 3, 'ChIJiwNB0nnFADsRExDPBFw3HjI', 'UFours IT Solution Private Limited', '', '081484 57995', 'info@ufours.com', 'Crime Branch, 40/10a, Power House Rd, Periyar, Madurai Main, Madurai, Tamil Nadu 625001, India', 'https://www.ufours.com/', 1, 19, 4.5, 19, NULL, 21, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:36', 0),
(1193, 3, 'ChIJwzPLVfvFADsRiPjGiAobw1Q', 'Bigdbiz Solutions Private Limited', '', '', '', 'Mahatma Gandhi Nagar Main Rd, Viswanathapuram, Mahatma Gandhi Nagar, Madurai, Tamil Nadu 625014, India', 'https://bigbakeryerp.com/', 1, 20, 4.7, 50, NULL, 22, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:38', 0),
(1194, 3, 'ChIJdwkLpnXPADsRjm1ButDtk7s', 'Sumanas Technologies - Best App development company in Madurai', '', '099528 70443', 'info@sumanastech.com', '244/3 Vivek Street, Bypass Rd, near Corporation Park, Velmurugan Nagar, Durai Samy Nagar, Madurai, Madakkulam, Tamil Nadu 625003, India', 'https://www.sumanastech.com/branches/mobile-app-development-company-in-madurai/?utm_source=google&utm_medium=organic&utm_campaign=gbp', 1, 21, 4.6, 57, NULL, 27, 'all', 'Madurai, India', 'Private Limited', 0, NULL, '2026-04-30 17:30:38', 0),
(1195, 3, 'ChIJ3QkO_rvxqzsR9rd_V2fAz2M', 'Optimus Technocrates India Private Limited', '', '094433 92673', '', 'THIRD FLOOR , TNR COMPLEX NEAR ATC BUS DEPOT , NEW, Bus Stand, Angammal Colony, Salem, Tamil Nadu 636009, India', 'http://www.optimustechno.com/', 1, 2, 4.9, 59, NULL, 27, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:00', 0),
(1196, 3, 'ChIJaZAdUKj9qzsR6gbcISF0dQI', 'Tamilzorous Private Limited', '', '093637 24124', 'contact@tamilzorous.com', '10, Second Floor, TIDEL Neo Karuppur (PO, Kullagoundanoor, Salem, Tamil Nadu 636011, India', 'https://tamilzorous.com/', 1, 3, 4.9, 44, NULL, 27, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:01', 0),
(1197, 3, 'ChIJCyylZszxqzsRwE_TJ-GpB1o', 'Ideal Life Sciences Private Limited', '', '098429 75295', 'ideallifesciences@gmail.com', 'First floor, 2A, Venkat Rao Rd, I Agraharam, Salem, Tamil Nadu 636001, India', 'http://ideallifesciences.in/', 1, 4, 4.9, 22, NULL, 21, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:03', 0),
(1198, 3, 'ChIJA855xmrwqzsRgYVUgQ_nyY4', 'First American India Pvt Ltd (FAI)', '', '0427 243 1299', '', '27, Meyyanur Main Rd, Meyyanur, Salem, Tamil Nadu 636004, India', '', 0, 5, 4.2, 282, NULL, 85, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:07', 0),
(1199, 3, 'ChIJP9kM9irwqzsRLfGzvF8CTqg', 'SALEM MICROBES PVT.LTD', '', '093448 37525', 'vijay@salemmicrobes.com', '21, Bajanai Madam St, Gugai, Salem, Tamil Nadu 636006, India', 'http://www.salemmicrobes.com/', 1, 6, 4.9, 96, NULL, 27, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:10', 0),
(1200, 3, 'ChIJRTNlPzTwqzsRPOP3-_E9taU', 'Elintsys Technologies India Pvt ltd', '', '073390 04896', 'info@elintsys.com', 'Tamil Sangam Rd, Sankar Nagar, Salem, Tamil Nadu 636007, India', 'http://www.elintsys.com/', 1, 7, 3.9, 25, NULL, 11, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:17', 0),
(1201, 3, 'ChIJ8QBqDNjxqzsRSxn8hJFJzDI', 'Nipurna IT Solutions Pvt., Ltd.,', '', '091593 18886', 'sales@nipurnait.com', '1st floor, D.No 41, Tamil Sangam Rd, Sankar Nagar, Salem, Tamil Nadu 636007, India', 'https://nipurnait.com/', 1, 8, 5.0, 5, NULL, 15, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:18', 0),
(1202, 3, 'ChIJG2uh52XwqzsRgW3CVZS0tCA', 'ERP Logic India Pvt Ltd (Main branch)', '', '0427 231 2666', 'info@noblq.com', 'Kavin Bharathi Arcade, 17 Padmavathi Nagar, Near Tata Colony, Salem, Tamil Nadu 636005, India', 'http://www.noblq.com/', 1, 9, 4.4, 38, NULL, 27, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:22', 0),
(1203, 3, 'ChIJY2t02CbxqzsRjJNBJQxPYAk', 'KALAR PHARMA PRIVATE LIMITED', '', '', '', 'No. 59/1, Gandhi Rd, Kurichi Colony, Hasthampatti, Salem, Tamil Nadu 636007, India', '', 0, 10, 5.0, 3, NULL, 60, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:25', 0),
(1204, 3, 'ChIJ4fuQmSvxqzsRfkRNiCeEBY4', 'Smartam Engineers Pvt Ltd', '', '', '', '2nd floor, Kandhaiya Complex, 50/28, Sarada College Rd, near Shanmuga Hospital, Hasthampatti, Salem, Tamil Nadu 636016, India', 'https://www.smartam.co/', 1, 11, 4.9, 16, NULL, 16, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:30', 0),
(1205, 3, 'ChIJe763vt_vqzsR_9-A3MOmc8Q', 'Salem Food Products Private Limited', '', '0427 227 1900', '', '154, Sankari Main Rd, Kathayammal Nagar, Nethimedu, Salem, Tamil Nadu 636002, India', '', 0, 12, 4.1, 117, NULL, 85, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:33', 0),
(1206, 3, 'ChIJoZg51WrwqzsR50G2aruSnx0', 'Capgemini Technology Services India Ltd', '', '0427 392 0300', 'cgcompanysecretary.in@capgemini.com', 'PT Towers, 3 Roads, Main Rd, Subramania Nagar, Suramangalam, Salem, Tamil Nadu 636009, India', 'https://www.capgemini.com/in-en/what-we-do/group-overview/capgemini-technology-services-india-limited-formerly-known-as-igate-global-solutions-limited/', 1, 13, 4.2, 316, NULL, 35, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:35', 0),
(1207, 3, 'ChIJ0bKwNQDxqzsRh3maAGnnKDc', 'SALEM TURNTECH ENGINEERING INDIA PRIVATE LIMITED', '', '097903 86680', '', 'Elumalai Gounder Thottam, 4/156-11, Mamangam, Salem, Tamil Nadu 636302, India', '', 0, 14, NULL, 0, NULL, 55, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:37', 0),
(1208, 3, 'ChIJyem8qEzwqzsRj3pvnOQIAX0', 'SRC PROJECTS PRIVATE LIMITED - SALEM', '', '0427 231 2343', '', '4B, Gandhi Rd, Lakshmipuram, Hasthampatti, Salem, Tamil Nadu 636007, India', 'http://www.srcprojects.in/', 1, 15, 4.3, 82, NULL, 27, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:38', 0),
(1209, 3, 'ChIJHd1GkBjyqzsRldfMpjv8O2A', 'Cologenesis Healthcare Pvt Ltd', '', '', '', 'Plot No.-51, The Salem Co-op Industrial Estate, Udayapatti, Salem, Tamil Nadu 636140, India', 'http://cologenesis.com/', 1, 16, 4.0, 10, NULL, 16, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:42', 0),
(1210, 3, 'ChIJQYLQoA0HrDsRT4ZCfDxHSrs', 'Omsys Technology India Private Limited', '', '097511 78337', 'suresh@omsys.in', 'Omsys Technology India Private Limited, Salem, Tamil Nadu 636351, India', 'https://www.omsys.in/', 1, 17, 5.0, 2, NULL, 15, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:43', 0),
(1211, 3, 'ChIJuyPUnUzxqzsR8GRu-Thr6Nc', 'LezDo TechMed Pvt Ltd', '', '080155 31935', 'info@lezdotechmed.com', 'Building No.210, Level 2, Junction Main Rd, State Bank Colony, Meyyanur, Salem, Tamil Nadu 636004, India', 'https://lezdotechmed.in/', 1, 18, 4.7, 6, NULL, 15, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:45', 0),
(1212, 3, 'ChIJz6G3Im_xqzsR8hue5Jdb2MQ', 'Startechnico Technocrats Private Limited', '', '077084 79216', '', 'Meyanoor, Bye- Pass Road, Angammal Colony, Salem, Tamil Nadu 636009, India', '', 0, 19, 5.0, 1, NULL, 65, 'all', 'Salem, India', 'Private Limited', 0, NULL, '2026-04-30 17:31:47', 0),
(1213, 3, 'ChIJY5XR515T9KQRZZvipETbuCI', 'MITA IT Automations Pvt. Ltd.', '', '', 'info@mita.in', 'vanivilas medu, Signal, Meenachinayakkanpatti, Dindigul, Tamil Nadu 624001, India', 'https://mita.in/', 1, 2, 4.9, 60, NULL, 22, 'all', 'Dindigul, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:04', 0),
(1214, 3, 'ChIJabFZtgWrADsR880h5AZfmJk', 'SG pvt Ltd', '', '', '', 'Suganya lodge back side, Begambur, Dindigul, Tamil Nadu 624001, India', '', 0, 3, 3.0, 7, NULL, 50, 'all', 'Dindigul, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:04', 0),
(1215, 3, 'ChIJ45oxjlaqADsRbVFxdAEUMWg', 'BALAVIGNA WEAVING MILLS PVT. LTD - REGISTERED OFFICE', '', '080 3740 1420', '', '11/42, Kasthuribai Road, Nagal Nagar, Tamil Nadu 624003, India', 'https://balavignaorganic.com/', 1, 4, 3.9, 8, NULL, 5, 'all', 'Dindigul, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:10', 0),
(1216, 3, 'ChIJrawgtSSrADsR2KMRXJ8OBIY', 'XPRESSBEES LOGISTICS SOLUTIONS PVT LTD', '', '', '', '9XFQ+RFJ, Unnamed Road, AKMG Nagar, Cooperative Nagar, Dindigul, Tamil Nadu 624005, India', '', 0, 5, 2.3, 41, NULL, 62, 'all', 'Dindigul, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:12', 0),
(1217, 3, 'ChIJu5evGluqADsRHwsaj0PjFvg', 'Top Anil Marketing Company', '', '0451 243 1969', 'marketing@theanilgroup.com', 'A-11/1, L.G.B Compound, 3rd Street, Mengles Rd, Mendonsa Colony, Dindigul, Tamil Nadu 624001, India', 'http://www.theanilgroup.com/', 1, 6, 3.9, 56, NULL, 17, 'all', 'Dindigul, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:13', 0),
(1218, 3, 'ChIJIxI1YfaqADsREgAgFg2kgag', 'Narasu\'s Coffee', '', '094427 00799', 'info@narasuscoffe.in', 'Main Rd, Begambur, Dindigul, Tamil Nadu 624001, India', 'http://www.narasuscoffee.in/', 1, 7, 4.7, 6, NULL, 15, 'all', 'Dindigul, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:15', 0),
(1219, 3, 'ChIJR9O4hrgzVToRI_oMeBEaE50', 'SwordNex Technologies Private Limited', '', '', 'info@swordnex.com', 'Ravi Plaza, No. 15C, 60 Feet Rd, near New bus stand, John Selvaraj Nagar, Kumbakonam, Tamil Nadu 612001, India', 'https://www.swordnex.com/', 0, 2, 5.0, 21, NULL, 66, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:29', 0),
(1220, 3, 'ChIJRX2vrrUyVToRSl4AgVYkL4Y', 'Annaa Silicon Technology Private Limited', '', '0435 242 7274', '', '44 A/47 Kumaragam, 2nd Floor, Abimukeswarar East Street, Kumbakonam - 612 001. Thanjavur (Dt, Kanmani Devi Nagar, Kumbakonam, Tamil Nadu 612001, India', 'http://annaasilicontechnology.com/', 1, 3, 3.9, 24, NULL, 11, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:31', 0),
(1221, 3, 'ChIJ3y7E6GVAUSURGuO08NfC1jA', 'SalonCapp Technologies Private Limited', '', '087004 97004', '', 'Second Floor, Plot No 24, Kumbakonam - Chennai Rd, near MRV mahal, Sethuraman Nagar, Melacavery, Kumbakonam, Tamil Nadu 612002, India', 'https://saloncapp.com/', 1, 4, 5.0, 1, NULL, 15, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:31', 0),
(1222, 3, 'ChIJi-pLtEszVToRC5X1fNXIxho', 'SRI KATHAYEE AMMAN PACKERS & MOVERS LOGISTICAL (P) LTD & Transport IBA Approved COMPANY', '', '095853 71777', 'srikathayeeammanpackersandmove@gmail.com', 'No :27, Kumbakonam - Karaikal Rd, near Petrol Bunk, Mutthupillai Mandapam, Kumbakonam, Tamil Nadu 612001, India', 'https://srikathayeeammanpackersandmovers.com/', 1, 5, 4.9, 212, NULL, 35, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:33', 0),
(1223, 3, 'ChIJv5avycw1VToRGDkwZ153xGs', 'Azusys Technologies Pvt Ltd - Software Development | Digital Marketing | WhatsApp Marketing Company in kumbakonam', '', '082201 69698', 'info@azusystech.com', '2/167/1 Sallikollai St, Nalur, post, Thirucherai, Kumbakonam, Tamil Nadu 612605, India', 'http://azusystech.com/', 1, 6, 4.9, 23, NULL, 21, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:33', 0),
(1224, 3, 'ChIJUbIU3bMyVToRw_o31Omi8gM', 'Up2datez', '', '082209 88645', 'admin@up2datez.com', '912, Banadurai Thirumanjana Veethi, Banadhurai New St, Durga Nagar, Anna Nagar, Kumbakonam, Tamil Nadu 612001, India', 'http://www.up2datez.com/', 1, 7, 4.5, 113, NULL, 35, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:42', 0),
(1225, 3, 'ChIJkXaq82bNqjsRGAfjc7P0Q7U', 'Meithee Tech Private Limited', '', '', 'support@meitheetech.com', '78, Bakthapuri St, near Kumbakonam Court, Anna Nagar, Kumbakonam, Tamil Nadu 612001, India', 'https://meitheetech.com/', 1, 8, 5.0, 3, NULL, 10, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:43', 0),
(1226, 3, 'ChIJ48Ao90_NqjsRrs6xGS8JBaU', 'UpSpring Infotech (P) Limited', '', '089034 11674', 'info@upspring.it', '2/27, Reddi Rao Tank South, kumbeswaran thirumanjana veedhi, Valayapettai Agraharam, Kumbakonam, Tamil Nadu 612001, India', 'http://upspring.it/', 1, 9, 4.3, 3, NULL, 15, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:44', 0),
(1227, 3, 'ChIJ94TRNd4zVToRB3wDEJyCySc', 'Habilesec India Private Limited', '', '083105 06586', '', '46/10 Sankar Iyer Thottam, 3rd St, Kumbakonam, Tamil Nadu 612001, India', 'http://www.habilesec.com/', 1, 10, 5.0, 16, NULL, 21, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:44', 0),
(1228, 3, 'ChIJ2xiH31TNqjsRFkF9IMh-lRk', 'Kali BMH Systems (P) Ltd', '', '0435 320 7701', '', 'Chennai Road, Melacavery, Kumbakonam, Tamil Nadu 612002, India', 'http://www.kalimhsonline.com/', 1, 11, 4.9, 12, NULL, 21, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:46', 0),
(1229, 3, 'ChIJF3I6yU_NqjsRc07zWRt95OA', 'P.S. Tamarind Private Limited', '', '0435 242 0019', 'psindia@pstamarind.com', 'Street #34, P Shunmugam Rd, Valayapettai Agraharam, Kumbakonam, Tamil Nadu 612001, India', 'https://pstamarind.com/', 1, 12, 4.6, 14, NULL, 21, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:51', 0),
(1230, 3, 'ChIJ-WAFl7AyVToR2Ic5fU4wnuU', 'VS Engineering Services Pvt. Ltd.', '', '094878 18199', 'projects@vs-engineering.in', '99, Mothilal St, John Selvaraj Nagar, Kumbakonam, Tamil Nadu 612001, India', 'http://vs-engineering.in/', 1, 13, 5.0, 25, NULL, 21, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:54', 0),
(1231, 3, 'ChIJ9-MFlkzNqjsRQ_0GaErG2r8', 'Godamwale Trading & Logistics Private Limited', '', '094424 65112', '', '39/22, \"Sree Bala Niwas\", Banadurai North Street, Valayapettai Agraharam, Kumbakonam, Tamil Nadu 612001, India', 'http://www.godamwale.com/', 1, 14, 5.0, 2, NULL, 15, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:33:59', 0),
(1232, 3, 'ChIJjdUOTM4zVToRRfK0D591x7o', 'Navabrind IT Solutions Pvt.Ltd.,', '', '', '', 'XCC2+JW3, Srinagar Colony, Kumbakonam, Tamil Nadu 612001, India', 'https://navabrindsol.com/', 1, 15, 4.6, 5, NULL, 10, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:34:00', 0),
(1233, 3, 'ChIJ7-16kC3NqjsRkDjdTgtBQ7k', 'TRADEGENIUZ CAPITAL PVT LTD / TRADING TRAINING ACADEMY', '', '087547 61834', 'support@tradegeniuz.com', '1st Floor, Sameer towers, Hajiyar St, Anna Nagar, Kumbakonam, Tamil Nadu 612001, India', 'http://www.tradegeniuz.com/', 1, 16, 4.8, 751, NULL, 35, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:34:03', 0),
(1234, 3, 'ChIJrxV7WNozVToR68I4Nad5lso', 'HAVIN INFRASTRUCTURES PRIVATE LIMITED', '', '096262 66242', 'havininfrastructurespvtltd@gmail.com', 'No 89/35, Nageshwaran Kovil South Street, Gandhi Adigal Salai, Valayapettai Agraharam, Kumbakonam, Tamil Nadu 612001, India', 'https://havininfrastructurespvtltd.com/', 1, 17, 5.0, 56, NULL, 27, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:34:04', 0),
(1235, 3, 'ChIJgfkekpwzVToRmfabL3LEDho', 'MDS Digital Hub Pvt Ltd', '', '063847 58182', 'admin@mdsdigitalhub.com', 'Kumbakonam - Chennai Rd, Malik Nagar, Koranattukkaruppur, Kumbakonam, Tamil Nadu 612001, India', 'https://mdsdigitalhub.com/', 1, 18, 5.0, 20, NULL, 21, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:34:04', 0),
(1236, 3, 'ChIJTwlOdAAzVToRCCNTFK4TBhI', 'TECH ENGINEERING INFRA(INDIA) PVT LTD', '', '', 'info@techengineering.net', '147, 3rd street, Kumbakonam - Karaikal Rd, Vivekananda Nagar, Kumbakonam, Tamil Nadu 612001, India', 'http://www.techengineering.net/', 1, 19, 5.0, 1, NULL, 10, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:34:05', 0),
(1237, 3, 'ChIJ24wuAwDNqjsRn9goEZSHMpQ', 'Integrated', '', '', 'press@google.com', 'X97J+F58, Pachayappa St, Karna Kollai Agraharam, Anna Nagar, Kumbakonam, Tamil Nadu 612001, India', 'https://www.google.com/url?sa=t&source=web&rct=j&opi=89978449&url=https://www.integratedindia.in/&ved=2ahUKEwij_vv-3sWNAxWqxjgGHcUTGI0QFnoECCAQAQ&usg=AOvVaw21KMSTHH2iuxa9hObWryrA', 1, 20, 4.0, 32, NULL, 22, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:34:06', 0),
(1238, 3, 'ChIJIfdwxbAyVToRI8RjziZRQDY', 'Micro Solution Technology Private Limited (MSTS)', '', '094890 90858', 'barathiraja.t@msts.in', '50, John Selvaraj Nagar Rd, John Selvaraj Nagar, Kumbakonam, Tamil Nadu 612001, India', 'http://msts.in/', 1, 21, 4.6, 23, NULL, 21, 'all', 'Kumbakonam, India', 'Private Limited', 0, NULL, '2026-04-30 17:34:13', 0),
(1239, 3, 'ChIJz_tfwMgbqzsRvNl73msaI0w', 'Perambalur Fabrication India Private Limited', '', '094437 82933', '', '7VHP+VMF, Perambalur, Tamil Nadu 621220, India', '', 0, 2, 4.4, 7, NULL, 65, 'all', 'Perambalur, India', 'Private Limited', 0, NULL, '2026-04-30 17:34:41', 0),
(1240, 3, 'ChIJbcKT_uobqzsRBKQYuyaK0Hk', 'Sharkvels Software Solutions Pvt Ltd', '', '078455 81755', '', 'road, Arumadal, Perambalur, Tamil Nadu 621220, India', '', 0, 3, 5.0, 9, NULL, 65, 'all', 'Perambalur, India', 'Private Limited', 0, NULL, '2026-04-30 17:34:43', 0),
(1241, 3, 'ChIJfSeRXgAbqzsRvLAuQH49qCo', 'Sumisa Technologies', '', '', 'contact@sumisatech.com', '6VR8+3H4, Samiyappa Nagar, Perambalur, Tamil Nadu 621212, India', 'https://sumisatech.com/', 1, 4, 5.0, 3, NULL, 10, 'all', 'Perambalur, India', 'Private Limited', 0, NULL, '2026-04-30 17:34:44', 0),
(1242, 3, 'ChIJr83tQgAbqzsRGtVXX8vgYhg', 'Sky group of company', '', '075503 76992', '', 'B35-2.Samiyappa nagar, Vadakkumathevi Road, Renga Nagar, Perambalur, Tamil Nadu 621212, India', 'https://www.facebook.com/share/v/RuynHrLPXgzSr9BV/?mibextid=oFDknk', 0, 5, 5.0, 2, NULL, 65, 'all', 'Perambalur, India', 'Private Limited', 0, NULL, '2026-04-30 17:34:46', 0),
(1243, 3, 'ChIJWZKI9IEbqzsRZh_03t49nEs', 'Thulasi Pharmacy - Perambalur, Trichy', '', '099436 93000', 'info@thulasipharmacy.com', 'No:106/S, Trichy Main Rd, Sungu Pettai, Venketwasapuram, Tiruchirappalli, Tamil Nadu 621212, India', 'http://www.thulasipharmacy.com/', 1, 6, 4.9, 434, NULL, 35, 'all', 'Perambalur, India', 'Private Limited', 0, NULL, '2026-04-30 17:34:53', 0),
(1244, 3, 'ChIJySFDonobqzsR9o9e9sfST_s', 'Analasoft Technologies', '', '099945 67247', 'info@analasoft.com', 'No 5,First Floor, Near Ulavar Santhai, N Madhavi Rd, Samiyappa Nagar, Perambalur, Tamil Nadu 621212, India', 'http://analasoft.com/', 1, 7, 5.0, 9, NULL, 15, 'all', 'Perambalur, India', 'Private Limited', 0, NULL, '2026-04-30 17:34:54', 0),
(1245, 3, 'ChIJcZqLZQAXqzsRFv5vsBcDYRU', 'JR one kothari foot wear pvt ltd', '', '', '', 'JR ONE KOTHARI FOOTWEAR PVT LTD, Sipcot industrial park, Eraiyur, Tamil Nadu 621133, India', '', 0, 8, 4.2, 39, NULL, 72, 'all', 'Perambalur, India', 'Private Limited', 0, NULL, '2026-04-30 17:34:55', 0),
(1246, 3, 'ChIJlfoiJAAbqzsRI5AldtCR0UA', 'RRR Group', '', '1747658929', 'vijayawada@rrrgroup.in', '6WR2+R29, Min Nagar, Perambalur, Tamil Nadu 621220, India', 'https://rrrgroupindia.com/', 1, 9, 5.0, 1, NULL, 15, 'all', 'Perambalur, India', 'Private Limited', 0, NULL, '2026-04-30 17:34:58', 1);
INSERT INTO `lead_gen_results` (`id`, `user_id`, `place_id`, `name`, `owner_name`, `phone`, `email`, `address`, `website`, `has_website`, `api_calls`, `rating`, `ratings_total`, `price_level`, `opportunity_score`, `search_mode`, `location`, `industry`, `imported`, `lead_id`, `created_at`, `website_found_by_crawler`) VALUES
(1247, 3, 'ChIJj9hQjnkbqzsRAgPj4PD8a6g', 'Dhanalakshmi Srinivasan Hotels', '', '073737 68901', 'reservations@dshotel.net', 'Collector\'s Office Road, New Bus Stand Rd, Palakarai, Sungu Pettai, Perambalur, Tamil Nadu 621212, India', 'http://dshotel.net/', 1, 10, 4.2, 2699, NULL, 35, 'all', 'Perambalur, India', 'Private Limited', 0, NULL, '2026-04-30 17:35:03', 0),
(1248, 3, 'ChIJq_4XGU55ADsR4RjP-BWUxvI', 'BY8LABS AI Pvt Ltd.', '', '081898 98884', '', 'Santhanathapuram, Pudukkottai, Tamil Nadu 622001, India', 'https://by8labs.com/', 1, 2, 4.9, 201, NULL, 35, 'all', 'Pudukottai, India', 'Private Limited', 0, NULL, '2026-04-30 17:35:20', 0),
(1249, 2, 'ChIJn9Yf7HTN-joRCKAKOE29hu8', 'Eastern Fitness Gym', '', '077 174 5447', '', '43, 2/1 Vanniah\'s Road, Batticaloa, Sri Lanka', '', 0, 2, 4.6, 66, NULL, 77, 'all', 'Batticaloa, Sri Lanka', 'gym', 1, 7, '2026-05-01 08:23:49', 0),
(1250, 2, 'ChIJd-DxEXrN-joRajDvT1mi36o', 'WarriorFit Batticaloa', '', '070 414 1402', '', 'Private Bus Stand, First Floor, Batticaloa 30000, Sri Lanka', 'https://www.facebook.com/people/WarriorFit-Gym', 0, 3, 5.0, 11, NULL, 71, 'all', 'Batticaloa, Sri Lanka', 'gym', 1, 8, '2026-05-01 08:23:51', 0),
(1251, 2, 'ChIJyRrEDGXN-joRV4QWZeq8Ni0', 'KESHI Good Life Fitness Centre', '', '077 670 5633', '', 'Advocate\'s Rd, Batticaloa, Sri Lanka', '', 0, 4, 4.6, 27, NULL, 71, 'all', 'Batticaloa, Sri Lanka', 'gym', 1, 9, '2026-05-01 08:23:53', 0),
(1252, 2, 'ChIJWRdOIozT-joRvvfB4zC0DM0', 'Rk Gym', '', '+0400-045', '', 'PMHQ+PRP, Batticaloa, Sri Lanka', 'https://www.rkgym.com/', 1, 5, 4.9, 7, NULL, 15, 'all', 'Batticaloa, Sri Lanka', 'gym', 0, NULL, '2026-05-01 08:23:57', 1),
(1253, 2, 'ChIJ9-NuLAnN-joRITB5qRCIW3A', 'GYM and Fitness Centre', '', '077 174 5447', '', '34 Vanniah\'s Ln, Batticaloa, Sri Lanka', '', 0, 6, 4.5, 2, NULL, 65, 'all', 'Batticaloa, Sri Lanka', 'gym', 1, 12, '2026-05-01 08:23:59', 0),
(1254, 2, 'ChIJ84n8vELN-joRpWIcaDqCA9E', 'Roni Gym', '', '', '', 'PPG3+586, Batticaloa, Sri Lanka', '', 0, 7, 4.7, 3, NULL, 60, 'all', 'Batticaloa, Sri Lanka', 'gym', 1, 13, '2026-05-01 08:24:01', 0),
(1255, 2, 'ChIJh3VIKOHN-joR_D-P2OYkoR8', 'Iron paradise', '', '077 032 1995', '', 'PP86+36P, Kallady, Sri Lanka', '', 0, 8, 4.6, 11, NULL, 71, 'all', 'Batticaloa, Sri Lanka', 'gym', 1, 10, '2026-05-01 08:24:03', 0),
(1256, 2, 'ChIJeXJ9UVrN-joRf_Bp7TDbnWQ', 'MJ One Mobile and Sports', '', '077 723 2342', '', 'Batticaloa, Sri Lanka', '', 0, 9, 4.6, 25, NULL, 71, 'all', 'Batticaloa, Sri Lanka', 'gym', 1, 11, '2026-05-01 08:24:05', 0),
(1257, 2, 'ChIJ7VZMB2LN-joRazkzROclzco', 'Fit for Life', '', '077 287 9134', 'info@fitforlife.com', 'PM9W+C2X, Boundary Rd, Batticaloa 30000, Sri Lanka', 'https://www.fitforlife.com/', 1, 10, NULL, 0, NULL, 5, 'all', 'Batticaloa, Sri Lanka', 'gym', 0, NULL, '2026-05-01 08:24:12', 1),
(1258, 2, 'ChIJfwnpI9LT-joR5x83oLD-rGU', 'கரவெட்டி MGR நற்பணி மன்றம்', '', '074 006 0335', '', 'Batticaloa 30018, Sri Lanka', 'https://www.mgr.com/', 1, 11, NULL, 0, NULL, 5, 'all', 'Batticaloa, Sri Lanka', 'gym', 0, NULL, '2026-05-01 08:24:16', 1),
(1259, 3, 'ChIJeUwGJHIZ6zkReIMr3o1MB_M', 'Nepal Pavilion Inn', '', '01-5320383', 'booking@nepalpavilioninn.com', 'Amrit Marg, Thamel Post Box 6062, Kathmandu 44600, Nepal', 'http://www.nepalpavilioninn.com/', 1, 2, 4.2, 326, NULL, 35, 'all', 'Kathmandu, Nepal', 'Hotel', 0, NULL, '2026-05-02 02:32:21', 0),
(1260, 3, 'ChIJPXDk_aAZ6zkRMCMv9LvRxgo', 'Hotel Mega & Apartment', '', '985-1340075', 'info@hotelmegakathmandu.com', 'At, सात घुम्ती मार्ग, Kathmandu 44600, Nepal', 'http://www.hotelmegakathmandu.com/', 1, 3, 4.7, 391, NULL, 35, 'all', 'Kathmandu, Nepal', 'Hotel', 0, NULL, '2026-05-02 02:32:24', 0),
(1261, 3, 'ChIJ_RUrZbYZ6zkRn_P32yhvh4E', 'Hotel Himalaya', '', '01-5423900', 'info@hotelhimalaya.com', '3584 Yala Sadak, Lalitpur 44600, Nepal', 'http://www.hotelhimalaya.com/', 1, 4, 4.2, 3267, NULL, 35, 'all', 'Kathmandu, Nepal', 'Hotel', 0, NULL, '2026-05-02 02:32:26', 0),
(1262, 3, 'ChIJWXEmlfwY6zkRaJfLq63DP9M', 'Kathmandu Guest House', '', '01-4700800', 'ota@ktmgh.com', 'P885+3RG, Saathgumti-16, Kathmandu 44600, Nepal', 'http://www.ktmgh.com/', 1, 5, 4.3, 1066, NULL, 35, 'all', 'Kathmandu, Nepal', 'Hotel', 0, NULL, '2026-05-02 02:32:31', 0),
(1263, 3, 'ChIJO0h2NPsY6zkRwy_BjgIwBsU', 'Kathmandu Suite Home', '', '980-1026351', 'sales@kathmandusuitehome.com', 'P884+2QH, 16 Thamel Marg, Kathmandu 44600, Nepal', 'http://www.kathmandusuitehome.com/', 1, 6, 4.6, 570, NULL, 35, 'all', 'Kathmandu, Nepal', 'Hotel', 0, NULL, '2026-05-02 02:32:34', 0),
(1264, 3, 'ChIJ57UfS_0b6zkRwPJGXzHaziY', 'Lavie Garden', '', '980-8996175', '', 'Ramhiti Boudha Rd, Kathmandu 44600, Nepal', 'https://garden.laviehospitality.com.np/', 1, 2, 4.6, 4508, 2, 43, 'all', 'Kathmandu, Nepal', 'Restaurant', 0, NULL, '2026-05-02 02:32:57', 0),
(1265, 3, 'ChIJB_vJq38Z6zkR90mVz1Mzi2Y', 'Walnut Bistro - Restaurant in Kathmandu', '', '01-4510484', 'walnutbistro19@gmail.com', 'Do-Cha Marga, Kathmandu 44600, Nepal', 'http://walnutbistronepal.com/', 1, 3, 4.7, 3089, 2, 43, 'all', 'Kathmandu, Nepal', 'Restaurant', 0, NULL, '2026-05-02 02:33:02', 0),
(1266, 3, 'ChIJhVJq6-IY6zkRAcY456Xoiso', 'Kathmandu Grill Restaurant & Wine Bar', '', '982-8425333', 'reservation@kathmandugrill.com', 'Chaksibari Marg, Kathmandu 44600, Nepal', 'https://www.kathmandugrill.com/', 1, 4, 4.7, 2351, 2, 43, 'all', 'Kathmandu, Nepal', 'Restaurant', 0, NULL, '2026-05-02 02:33:05', 0),
(1267, 3, 'ChIJ0eBjuSgZ6zkRFrzS1BS9v-w', 'Nepalaya Rooftop Restaurant', '', '01-5901321', 'info@hotelnepalaya.com', 'Thamel, Kwabahal-17, Inside Hotel Nepalaya, Kathmandu 44600, Nepal', 'http://www.hotelnepalaya.com/restaurant', 1, 5, 4.7, 972, 2, 43, 'all', 'Kathmandu, Nepal', 'Restaurant', 0, NULL, '2026-05-02 02:33:09', 0),
(1268, 3, 'ChIJD3hJz3kZ6zkRWXOu725x47Q', 'Jasper Restaurant', '', '980-3050739', '', 'P876, Thabahi Road, Kathmandu 44600, Nepal', 'https://jasperrestaurantthamel.com/', 1, 6, 4.7, 3807, NULL, 35, 'all', 'Kathmandu, Nepal', 'Restaurant', 0, NULL, '2026-05-02 02:33:13', 0),
(1269, 3, 'ChIJC3ba0RQY6zkRqpR2eFCT0ts', 'Tribhuvan University', '', '01-4331076', 'tuexam@tribhuvan-university.edu.np', 'Kathmandu, Kirtipur 44600, Nepal', 'http://www.tribhuvan-university.edu.np/', 1, 2, 4.1, 1724, NULL, 35, 'all', 'Kathmandu, Nepal', 'Campus', 0, NULL, '2026-05-02 02:33:41', 0),
(1270, 3, 'ChIJ5WvwvOIY6zkRt18XiuC-_rM', 'Amrit Campus', '', '01-4521476', '', 'Leknath Marg, Kathmandu 44600, Nepal', 'https://www.ac.tu.edu.np/', 1, 3, 4.0, 227, NULL, 35, 'all', 'Kathmandu, Nepal', 'Campus', 0, NULL, '2026-05-02 02:33:43', 0),
(1271, 3, 'ChIJpxrSUagZ6zkRmTAVQiryFBU', 'Shanker Dev Campus', '', '01-4226931', '', 'P839+7QG, Adwait Marg, Kathmandu 44600, Nepal', 'https://shankerdevcampus.edu.np/', 1, 4, 4.1, 486, NULL, 35, 'all', 'Kathmandu, Nepal', 'Campus', 0, NULL, '2026-05-02 02:33:56', 0),
(1272, 3, 'ChIJkUYpm44Z6zkRj3ffTEwMNbA', 'Baneshwor Multiple Campus', '', '01-4620310', 'bmc@baneshworcampus.edu.np', 'Kathmandu 44600, Nepal', 'http://www.baneshworcampus.edu.np/', 1, 5, 3.8, 242, NULL, 25, 'all', 'Kathmandu, Nepal', 'Campus', 0, NULL, '2026-05-02 02:34:05', 0),
(1273, 3, 'ChIJXaC9CnoZ6zkRWVfHRK5dPxE', 'Pashupati Multiple Campus (PMC)', '', '01-4470412', 'info@pashupatimultiplecampus.edu.np', 'चरुमती मार्ग, Kathmandu 44602, Nepal', 'http://pashupatimultiplecampus.com/', 1, 6, 4.0, 190, NULL, 35, 'all', 'Kathmandu, Nepal', 'Campus', 0, NULL, '2026-05-02 02:34:06', 0),
(1274, 3, 'ChIJ9zrXNQAZ6zkRI_s0n2Mfosc', 'Evolve Fitness', '', '980-2362300', '', 'Narayan Gopal Sadak, Kathmandu 44600, Nepal', 'http://www.evolvefitness.com.np/', 1, 2, 4.9, 253, NULL, 35, 'all', 'Kathmandu, Nepal', 'Gym', 0, NULL, '2026-05-02 02:34:23', 0),
(1275, 3, 'ChIJ7aSVm7EZ6zkR79c04wjiAjQ', 'Evolution Gym', '', '981-5813774', '', 'Unnamed Road, Kathmandu 44600, Nepal', '', 0, 3, 4.7, 331, NULL, 85, 'all', 'Kathmandu, Nepal', 'Gym', 0, NULL, '2026-05-02 02:34:31', 0),
(1276, 3, 'ChIJn1KzhacZ6zkR6DoBDAnfaXU', 'IFit Nepal', '', '984-1028774', 'ifitnepal@gmail.com', 'P853+5JH, Kathmandu 44600, Nepal', 'http://ifitnepal.com/', 1, 4, 4.9, 28, NULL, 21, 'all', 'Kathmandu, Nepal', 'Gym', 0, NULL, '2026-05-02 02:34:35', 0),
(1277, 3, 'ChIJy6ef4lEZ6zkRwhGNLIX99uQ', 'NRK Fitness', '', '01-4537240', '', '5th floor, Santa Plaza, Kalopul-Ratopul Rd, काठमाडौँ 44600, Nepal', '', 0, 5, 4.5, 150, NULL, 85, 'all', 'Kathmandu, Nepal', 'Gym', 0, NULL, '2026-05-02 02:34:37', 0),
(1278, 3, 'ChIJvZknuj4Z6zkRf2B3_3rThB0', 'Regal Fitness Nepal', '', '976-1667111', 'regalfitnessclubteku@gmail.com', 'City Center Mall (5th floor, काठमाडौँ 44605, Nepal', 'http://www.regalfitnessnepal.com/', 1, 6, 4.8, 96, NULL, 27, 'all', 'Kathmandu, Nepal', 'Gym', 0, NULL, '2026-05-02 02:34:41', 0),
(1279, 3, 'ChIJrQrq5lIR4zoRZYX2jY20Wso', 'Advanced Technological Institute - Kegalle', '', '0352 221 297', 'atikegalle@sliate.ac.lk', '69W4+WRG, Bandaranayaka Ave, Kegalle, Sri Lanka', 'http://kegalle.sliate.ac.lk/', 1, 2, 4.9, 40, NULL, 27, 'all', 'Kegalle, Sri Lanka', 'Training Institute', 0, NULL, '2026-05-03 07:17:59', 0),
(1280, 3, 'ChIJeVtR4AIX4zoRYVTgNvAAJ-M', 'Kegalle Vocational Training Campus', '', '070 745 9824', '', 'Polgahawela Road, Kegalle 71000, Sri Lanka', '', 0, 3, 5.0, 5, NULL, 65, 'all', 'Kegalle, Sri Lanka', 'Training Institute', 0, NULL, '2026-05-03 07:18:01', 0),
(1281, 3, 'ChIJbXo8iroW4zoRzouWKShWbfk', 'Technical College - Kegalle', '', '0352 222 441', '', '11, Olagankanda Junior School, Swarna Jayanthi Road, Kegalle, Sri Lanka', '', 0, 4, 4.5, 51, NULL, 77, 'all', 'Kegalle, Sri Lanka', 'Training Institute', 0, NULL, '2026-05-03 07:18:03', 0),
(1282, 3, 'ChIJLZGGWloX4zoRRZBG7OBNhm0', 'eBirth Business Academy - Kegalle Branch', '', '077 267 5455', '', 'No 315 Main Street, Kegalle, Sri Lanka', 'http://www.ebirth.net/', 1, 5, 4.8, 434, NULL, 35, 'all', 'Kegalle, Sri Lanka', 'Training Institute', 0, NULL, '2026-05-03 07:18:03', 0),
(1283, 3, 'ChIJ5_1HYcwW4zoRSGIb1JIgAiw', 'ESOFT Metro College', '', '0352 222 045', 'info@esoft.lk', '245 A1, Kegalle, Sri Lanka', 'http://www.esoft.lk/', 1, 6, 4.0, 24, NULL, 21, 'all', 'Kegalle, Sri Lanka', 'Training Institute', 0, NULL, '2026-05-03 07:18:03', 0),
(1284, 3, 'ChIJPU2jyFln4zoRYw6T1SE3Dx8', 'Ideal Web Design', '', '077 844 7474', 'info@idealwebdesign.lk', '2nd Ln, Kandy, Sri Lanka', 'http://idealwebdesign.lk/', 1, 2, 4.9, 28, NULL, 21, 'all', 'Kandy, Sri Lanka', 'Website Design', 0, NULL, '2026-05-03 09:26:46', 0),
(1285, 3, 'ChIJqxJ84nt-320R7TRvgvYmtuE', 'HTK Designs – Expert Web Design & SEO Services in Kandy, Sri Lanka.', '', '077 343 4744', 'info@htkdesigns.com', '123, 20B Hewaheta Rd, Kandy 20000, Sri Lanka', 'https://htkdesigns.com/', 1, 3, 5.0, 3, NULL, 15, 'all', 'Kandy, Sri Lanka', 'Website Design', 0, NULL, '2026-05-03 09:26:47', 0),
(1286, 3, 'ChIJaTC0Ipdo4zoR423VYkFCL3w', 'Media Base | IT Training Center | Advertising & WEB Designing Company', '', '077 979 7676', '', 'No 693/3A Peradeniya Rd, Kandy 20000, Sri Lanka', 'http://www.mediabase.lk/', 1, 4, 5.0, 63, NULL, 27, 'all', 'Kandy, Sri Lanka', 'Website Design', 0, NULL, '2026-05-03 09:26:54', 0),
(1287, 3, 'ChIJaUpbrSdm4zoRXycefVF8gQ0', 'McSoftsis Web Design Kandy', '', '070 244 7722', '', 'hanthana place, no 31 Gamunu Mawatha, මහනුවර 20000, Sri Lanka', 'https://www.mcsoftsis.com/', 1, 5, 5.0, 19, NULL, 21, 'all', 'Kandy, Sri Lanka', 'Website Design', 0, NULL, '2026-05-03 09:26:55', 0),
(1288, 3, 'ChIJsUKzwuhn4zoRUrS5NMLcq74', 'Inspirenix Web Design & Digital Marketing Sri Lanka', '', '077 066 8809', 'john@doe.com', '138 gamudawa, Pallekele 20168, Sri Lanka', 'https://inspirenix.com/', 0, 6, 5.0, 19, NULL, 71, 'all', 'Kandy, Sri Lanka', 'Website Design', 0, NULL, '2026-05-03 09:26:59', 0),
(1289, 3, 'ChIJGQloeiT1qjsRjCkLJzAuHGE', 'Courtyard by Marriott Tiruchirappalli', '', '0431 424 4555', '', 'Collectorate\'s Office, Road, SBI Officers Colony, Raja Colony, Tiruchirappalli, Tamil Nadu 620001, India', 'https://www.marriott.com/en-us/hotels/trzcy-courtyard-tiruchirappalli/overview/?scid=f2ae0541-1279-4f24-b197-a979c79310b0', 1, 2, 4.5, 2036, NULL, 35, 'all', 'Tiruchirappalli, India', 'Hotel', 0, NULL, '2026-05-03 10:20:35', 0),
(1290, 3, 'ChIJiT0igFL1qjsRC2hzwzPGt5U', 'Hotel Rhythm Grand Suite', '', '078711 54545', 'hotelrhythmgrand@gmail.com', '1st Cross, VN Nagar Extension Atlas Hospital Road, Chathiram Busstand, near LRR Marriage Hall, V N Nagar, Tiruchirappalli, Tamil Nadu 620002, India', 'https://www.hotelrhythmgrandsuite.com/', 1, 3, 4.3, 338, NULL, 35, 'all', 'Tiruchirappalli, India', 'Hotel', 0, NULL, '2026-05-03 10:20:39', 0),
(1291, 3, 'ChIJxwN09RT1qjsR8ZF9bwVOjMs', 'Hotel Tamilnadu', '', '0431 241 3362', '', 'QMWM+V8G, McDonalds Rd, near Govt tourist office, Melapudur, Cantonment, Tiruchirappalli, Tamil Nadu 620001, India', 'http://www.ttdconline.com/', 1, 4, 4.2, 1849, NULL, 35, 'all', 'Tiruchirappalli, India', 'Hotel', 0, NULL, '2026-05-03 10:20:42', 0),
(1292, 3, 'ChIJrQddknj1qjsRuw9QBKWyKks', 'Park Avenue Hotel, Trichy', '', '0431 291 0060', 'info@parkavenuehotel.in', 'Raj Tower, Karur Bypass Rd, Annamalai Nagar, Tiruchirappalli, Tamil Nadu 620018, India', 'https://parkavenuehotel.in/trichy/', 1, 5, 4.4, 444, NULL, 35, 'all', 'Tiruchirappalli, India', 'Hotel', 0, NULL, '2026-05-03 10:20:49', 0),
(1293, 3, 'ChIJX3Cxwa71qjsRq8ltfG-klzA', 'Hotel Susee Park', '', '0431 281 2345', '', 'No-45 Singarathope Street, Super Bazaar, Singarathope, Tharanallur, Tiruchirappalli, Tamil Nadu 620008, India', '', 0, 6, 3.8, 878, NULL, 75, 'all', 'Tiruchirappalli, India', 'Hotel', 0, NULL, '2026-05-03 10:20:55', 0),
(1294, 2, 'ChIJM7z_aAC9-zoR1SGtMmLXJew', 'Capital Trincomalee', '', '077 877 3188', '', '34, Beach Road, Alles Garden, Trincomalee 31000, Sri Lanka', '', 0, 2, 4.8, 301, NULL, 85, 'all', 'Trincomalee, Sri Lanka', 'hotel', 1, 14, '2026-05-05 14:27:37', 0),
(1295, 2, 'ChIJIT1xgbG9-zoRj4sb7A1NQAk', 'Trinco Relax Hut', '', '077 513 9304', '', '190 Nilaveli Rd, Trincomalee, Sri Lanka', '', 0, 3, 3.7, 63, NULL, 67, 'all', 'Trincomalee, Sri Lanka', 'hotel', 1, 16, '2026-05-05 14:27:38', 0),
(1296, 2, 'ChIJacnrBgC9-zoRt_aUeI0l3OQ', 'Neem Tree House Trincomalee', '', '071 918 9529', 'user@domain.com', '77 Main St, Trincomalee 31000, Sri Lanka', 'http://www.neemtreehouse.com/', 1, 4, 4.5, 20, NULL, 21, 'all', 'Trincomalee, Sri Lanka', 'hotel', 0, NULL, '2026-05-05 14:27:39', 0),
(1297, 2, 'ChIJk0miUt69-zoRM-nAaHJEwgI', 'Trinco Diyash Beach Hotel', '', '077 445 6814', '', 'Murugapuri, 662/10 Ehamparam Rd, Trincomalee 31000, Sri Lanka', '', 0, 5, 4.7, 25, NULL, 71, 'all', 'Trincomalee, Sri Lanka', 'hotel', 1, 15, '2026-05-05 14:27:41', 0),
(1298, 2, 'ChIJBaPo6E68-zoR7PH1yg2kktE', 'Amaranthé Bay Resort & Spa', '', '0262 050 200', 'reservations@amaranthebay.com', '101/17, Alles Garden Road, Uppuveli, Trincomalee, Sri Lanka', 'http://amaranthebay.com/', 1, 6, 4.5, 975, NULL, 35, 'all', 'Trincomalee, Sri Lanka', 'hotel', 0, NULL, '2026-05-05 14:27:45', 0),
(1299, 2, 'ChIJM7z_aAC9-zoR1SGtMmLXJew', 'Capital Trincomalee', '', '077 877 3188', '', '34, Beach Road, Alles Garden, Trincomalee 31000, Sri Lanka', '', 0, 2, 4.8, 301, NULL, 85, 'all', 'Trincomalee, Sri Lanka', 'hotel', 0, NULL, '2026-05-05 14:27:46', 0),
(1300, 2, 'ChIJIT1xgbG9-zoRj4sb7A1NQAk', 'Trinco Relax Hut', '', '077 513 9304', '', '190 Nilaveli Rd, Trincomalee, Sri Lanka', '', 0, 3, 3.7, 63, NULL, 67, 'all', 'Trincomalee, Sri Lanka', 'hotel', 0, NULL, '2026-05-05 14:27:47', 0),
(1301, 2, 'ChIJacnrBgC9-zoRt_aUeI0l3OQ', 'Neem Tree House Trincomalee', '', '071 918 9529', 'user@domain.com', '77 Main St, Trincomalee 31000, Sri Lanka', 'http://www.neemtreehouse.com/', 1, 4, 4.5, 20, NULL, 21, 'all', 'Trincomalee, Sri Lanka', 'hotel', 0, NULL, '2026-05-05 14:27:48', 0),
(1302, 2, 'ChIJk0miUt69-zoRM-nAaHJEwgI', 'Trinco Diyash Beach Hotel', '', '077 445 6814', '', 'Murugapuri, 662/10 Ehamparam Rd, Trincomalee 31000, Sri Lanka', '', 0, 5, 4.7, 25, NULL, 71, 'all', 'Trincomalee, Sri Lanka', 'hotel', 0, NULL, '2026-05-05 14:27:49', 0),
(1303, 2, 'ChIJBaPo6E68-zoR7PH1yg2kktE', 'Amaranthé Bay Resort & Spa', '', '0262 050 200', 'reservations@amaranthebay.com', '101/17, Alles Garden Road, Uppuveli, Trincomalee, Sri Lanka', 'http://amaranthebay.com/', 1, 6, 4.5, 975, NULL, 35, 'all', 'Trincomalee, Sri Lanka', 'hotel', 0, NULL, '2026-05-05 14:27:55', 0),
(1304, 2, 'ChIJKZE_cE-8-zoR1rF4o6Ud6NA', 'Trinco Blu by Cinnamon', '', '0262 222 307', '', '8.619344, J699+75H Sampalthivu Post Uppuveli, 81.218409 Sarvodaya Rd, Trincomalee 83408, Sri Lanka', 'https://www.cinnamonhotels.com/trinco-blu-by-cinnamon?utm_source=google&utm_medium=organic&utm_campaign=gbp', 1, 7, 4.5, 4224, NULL, 35, 'all', 'Trincomalee, Sri Lanka', 'hotel', 0, NULL, '2026-05-05 14:28:10', 0),
(1305, 2, 'ChIJQxT3DAC9-zoRV4_4FBSQU_0', 'Blue Beach Hotel Trinco', '', '077 229 8049', '', 'No 13 EhamparamRoad, Trincomalee 31000, Sri Lanka', '', 0, 8, 4.8, 12, NULL, 71, 'all', 'Trincomalee, Sri Lanka', 'hotel', 0, NULL, '2026-05-05 14:28:12', 0),
(1306, 2, 'ChIJVzbLUPe8-zoRXoLG-ZYSJy4', 'Pleasant Park Holiday Inn', '', '077 225 1601', '', '729 Ehamparam Rd, Trincomalee 31000, Sri Lanka', 'https://www.booking.com/hotel/lk/pleasant-park-holiday-inn.no.html?aid=376378;label=Bookings-NO-RGM5e4nsTAPL1oykUnum1AS193318508777:pl:ta:p1:p21.267.000:ac:ap1t1:neg:fi:tiaud-261710242782:kwd-65526620:lp9069436:li:dec:dm;sid=c81c1b865503deef4498f17a3ed71a66;all_sr_blocks=237409605_101613067_2_1_0;checkin=2017-05-26;checkout=2017-05-27;dest_id=-2237624;dest_type=city;dist=0;group_adults=2;group_children=0;highlighted_blocks=237409605_101613067_2_1_0;hpos=1;no_rooms=1;room1=A,A;sb_price_type=total', 0, 9, 4.5, 182, NULL, 85, 'all', 'Trincomalee, Sri Lanka', 'hotel', 0, NULL, '2026-05-05 14:28:14', 0),
(1307, 2, 'ChIJiZQuDw-9-zoRcRIpef6x0xg', 'Blue Diamond Resort', '', '070 336 7305', '', 'Uppuveli Beach, Uppuveli, No.662/9 Nilaveli Rd, Trincomalee 31000, Sri Lanka', 'https://bluediamondresort.shop/', 1, 10, 4.6, 983, NULL, 35, 'all', 'Trincomalee, Sri Lanka', 'hotel', 0, NULL, '2026-05-05 14:28:17', 0),
(1308, 2, 'ChIJD5xM0Je9-zoRYvijmYtj0Ck', 'Trinco beach by DSK', '', '076 033 3521', 'info@trincobeach.com', '328 Dyke St, Trincomalee 31000, Sri Lanka', 'https://trincobeach.com/', 1, 11, 4.4, 95, NULL, 27, 'all', 'Trincomalee, Sri Lanka', 'hotel', 0, NULL, '2026-05-05 14:28:18', 0);


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
(37, 3, 'Colombo, Sri Lanka', 'academy', 3, 6, 0.0410, '2026-04-28 09:42:00'),
(38, 3, 'Batticaloa, Sri Lanka', 'Hotel', 5, 11, 0.0470, '2026-04-28 14:44:51'),
(39, 3, 'Colombo, Sri Lanka', 'HR consultancy', 1, 6, 0.0350, '2026-04-28 16:13:40'),
(40, 3, 'Trichy, India', 'Food delivery', 1, 6, 0.0350, '2026-04-28 16:15:52'),
(41, 3, 'Tiruchirappalli, India', 'Textile company', 3, 6, 0.0410, '2026-04-28 16:16:55'),
(42, 3, 'Batticaloa, Sri Lanka', 'Guest house', 5, 6, 0.0470, '2026-04-30 08:45:24'),
(43, 3, 'Batticaloa, Sri Lanka', 'Tuition center', 3, 6, 0.0410, '2026-04-30 08:45:48'),
(44, 3, 'Batticaloa, Sri Lanka', 'Pharmacy', 4, 6, 0.0440, '2026-04-30 08:46:27'),
(45, 3, 'Batticaloa, Sri Lanka', 'clinic', 1, 6, 0.0350, '2026-04-30 08:47:09'),
(46, 3, 'Batticaloa, Sri Lanka', 'Restaurant', 16, 21, 0.0800, '2026-04-30 08:48:34'),
(47, 3, 'Batticaloa, Sri Lanka', 'Driving school', 8, 10, 0.0560, '2026-04-30 08:49:54'),
(48, 3, 'Kandy, Sri Lanka', 'Guest house', 1, 2, 0.0350, '2026-04-30 08:51:02'),
(49, 3, 'Colombo, Sri Lanka', 'HR consultancy', 3, 21, 0.0410, '2026-04-30 08:52:56'),
(50, 3, 'Colombo, Sri Lanka', 'Private clinic', 0, 6, 0.0320, '2026-04-30 08:58:21'),
(51, 3, 'Colombo, Sri Lanka', 'Private clinic', 0, 6, 0.0320, '2026-04-30 08:58:57'),
(52, 3, 'Colombo, Sri Lanka', 'Logistics company', 0, 6, 0.0320, '2026-04-30 08:59:29'),
(53, 3, 'Colombo, Sri Lanka', 'Law firm', 0, 6, 0.0320, '2026-04-30 08:59:48'),
(54, 3, 'Kandy, Sri Lanka', 'Boutique hotel', 1, 6, 0.0350, '2026-04-30 09:00:58'),
(55, 3, 'Kandy, Sri Lanka', 'Guest house', 2, 3, 0.0380, '2026-04-30 09:01:26'),
(56, 3, 'Kandy, Sri Lanka', 'Tuition center', 8, 21, 0.0560, '2026-04-30 09:02:30'),
(57, 3, 'Kandy, Sri Lanka', 'ayurveda spa', 9, 21, 0.0590, '2026-04-30 09:03:37'),
(58, 3, 'Kandy, Sri Lanka', 'travel agent', 1, 21, 0.0350, '2026-04-30 09:05:17'),
(59, 3, 'Batticaloa, Sri Lanka', 'Tuition center', 6, 15, 0.0500, '2026-04-30 09:06:51'),
(60, 3, 'Colombo, Sri Lanka', 'Tuition center', 7, 21, 0.0530, '2026-04-30 09:08:03'),
(61, 3, 'Jaffna , Sri Lanka', 'Tuition center', 8, 21, 0.0560, '2026-04-30 09:09:07'),
(62, 3, 'Jaffna , Sri Lanka', 'Restaurant', 13, 21, 0.0710, '2026-04-30 09:10:17'),
(63, 3, 'Jaffna , Sri Lanka', 'seafood restaurant', 11, 21, 0.0650, '2026-04-30 09:11:13'),
(64, 3, 'Negombo, Sri Lanka', 'dive center', 0, 4, 0.0320, '2026-04-30 09:14:08'),
(65, 3, 'Negombo, Sri Lanka', 'surf school', 2, 12, 0.0380, '2026-04-30 09:15:02'),
(66, 3, 'Negombo, Sri Lanka', 'Tour operator', 0, 21, 0.0320, '2026-04-30 09:16:14'),
(67, 3, 'Negombo, Sri Lanka', 'Trincomalee', 14, 21, 0.0740, '2026-04-30 09:17:25'),
(68, 3, 'Trincomalee , Sri Lanka', 'Tour operator', 3, 6, 0.0410, '2026-04-30 09:18:18'),
(69, 3, 'Trincomalee, Sri Lanka', 'Guest house', 8, 11, 0.0560, '2026-04-30 09:19:10'),
(70, 3, 'Colombo, Sri Lanka', 'Beauty salon', 8, 21, 0.0560, '2026-04-30 09:20:57'),
(71, 3, 'Colombo, Sri Lanka', 'Hardware store', 8, 21, 0.0560, '2026-04-30 09:21:54'),
(72, 3, 'Brampton, Canada', 'Grocery store', 1, 21, 0.0350, '2026-04-30 09:25:34'),
(73, 3, 'Brampton, Canada', 'Catering service', 6, 19, 0.0500, '2026-04-30 09:27:06'),
(74, 3, 'Nottingham, UK', 'Gym', 3, 21, 0.0410, '2026-04-30 09:30:14'),
(75, 3, 'Sharjah, UAE', 'Grocery store', 4, 21, 0.0440, '2026-04-30 09:30:53'),
(76, 3, 'Dubai, UAE', 'Logistics company', 1, 21, 0.0350, '2026-04-30 09:32:06'),
(77, 3, 'Dubai, UAE', 'Logistics company', 1, 21, 0.0350, '2026-04-30 09:34:28'),
(78, 3, 'Dubai, UAE', 'Logistics company', 1, 21, 0.0350, '2026-04-30 09:38:54'),
(79, 3, 'Dubai, UAE', 'Travel agency', 0, 21, 0.0320, '2026-04-30 09:40:01'),
(80, 3, 'Coimbatore, India', 'Tuition center', 7, 21, 0.0530, '2026-04-30 09:41:00'),
(81, 3, 'Birmingham , UK', 'Plumber', 7, 20, 0.0530, '2026-04-30 09:42:42'),
(82, 3, 'Cairns , Australia', 'Guest house', 1, 4, 0.0350, '2026-04-30 09:43:01'),
(83, 3, 'Darwin , Australia', 'Guest house', 1, 21, 0.0350, '2026-04-30 09:44:52'),
(84, 3, 'Maldives', 'Guest house', 1, 8, 0.0350, '2026-04-30 09:45:25'),
(85, 3, 'Davao, Philippines', 'Restaurant', 9, 21, 0.0590, '2026-04-30 09:48:28'),
(86, 3, 'Altona, Germany', 'Private Limited Company', 10, 11, 0.0620, '2026-04-30 09:50:41'),
(87, 3, 'Srirangam, India', 'Private Limited', 0, 6, 0.0320, '2026-04-30 14:12:09'),
(88, 3, 'Tiruchirappalli, India', 'Private Limited', 0, 6, 0.0320, '2026-04-30 14:12:50'),
(89, 3, 'Coimbatore, India', 'Grocery store', 3, 6, 0.0410, '2026-04-30 14:14:40'),
(90, 3, 'Quebec, Canada', 'Recruitment agency', 1, 21, 0.0350, '2026-04-30 16:49:59'),
(91, 3, 'Bangkok CBD, Thailand', 'Recruitment agency', 0, 21, 0.0320, '2026-04-30 16:54:27'),
(92, 3, 'Puducherry, India', 'Private limited', 0, 6, 0.0320, '2026-04-30 16:58:43'),
(93, 3, 'Coimbatore, India', 'Pvt Ltd', 0, 6, 0.0320, '2026-04-30 17:08:38'),
(94, 3, 'Coimbatore, India', 'Pvt Ltd', 0, 6, 0.0320, '2026-04-30 17:09:08'),
(95, 3, 'Coimbatore, India', 'Computer shop', 1, 6, 0.0350, '2026-04-30 17:09:43'),
(96, 3, 'Thanjavur, India', 'Software company', 1, 6, 0.0350, '2026-04-30 17:10:36'),
(97, 3, 'Thanjavur, India', 'Software solutions', 5, 21, 0.0470, '2026-04-30 17:11:25'),
(98, 3, 'Lalgudi, India', 'Software solutions', 0, 6, 0.0320, '2026-04-30 17:11:52'),
(99, 3, 'Perambalur, India', 'Software solutions', 1, 9, 0.0350, '2026-04-30 17:12:18'),
(100, 3, 'Ariyalur, India', 'IT services', 14, 21, 0.0740, '2026-04-30 17:15:05'),
(101, 3, 'Kodaikanal, India', 'IT services', 7, 21, 0.0530, '2026-04-30 17:17:21'),
(102, 3, 'Thanjavur, India', 'IT services', 4, 21, 0.0440, '2026-04-30 17:18:37'),
(103, 3, 'Batticola, India', 'Private Limited', 10, 11, 0.0620, '2026-04-30 17:24:58'),
(104, 3, 'Karaikal, India', 'Private Limited', 20, 21, 0.0920, '2026-04-30 17:26:03'),
(105, 3, 'Madurai, India', 'Private Limited', 20, 21, 0.0920, '2026-04-30 17:30:38'),
(106, 3, 'Dindigul, India', 'Private Limited', 6, 7, 0.0500, '2026-04-30 17:33:15'),
(107, 3, 'Kumbakonam, India', 'Private Limited', 20, 21, 0.0920, '2026-04-30 17:34:13'),
(108, 3, 'Perambalur, India', 'Private Limited', 9, 10, 0.0590, '2026-04-30 17:35:03'),
(109, 2, 'Batticaloa, Sri Lanka', 'gym', 7, 11, 0.0530, '2026-05-01 08:24:16'),
(110, 3, 'Kathmandu, Nepal', 'Hotel', 0, 6, 0.0320, '2026-05-02 02:32:34'),
(111, 3, 'Kathmandu, Nepal', 'Restaurant', 0, 6, 0.0320, '2026-05-02 02:33:13'),
(112, 3, 'Kathmandu, Nepal', 'Campus', 0, 6, 0.0320, '2026-05-02 02:34:06'),
(113, 3, 'Kathmandu, Nepal', 'Gym', 2, 6, 0.0380, '2026-05-02 02:34:41'),
(114, 3, 'Kegalle, Sri Lanka', 'Training Institute', 2, 6, 0.0380, '2026-05-03 07:18:03'),
(115, 3, 'Kandy, Sri Lanka', 'Website Design', 1, 6, 0.0350, '2026-05-03 09:26:59'),
(116, 3, 'Tiruchirappalli, India', 'Hotel', 1, 6, 0.0350, '2026-05-03 10:20:55'),
(117, 2, 'Trincomalee, Sri Lanka', 'hotel', 3, 6, 0.0410, '2026-05-05 14:27:45'),
(118, 2, 'Trincomalee, Sri Lanka', 'hotel', 5, 11, 0.0470, '2026-05-05 14:28:18');

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
(1, 1, 'Premium Blog', 'Arthy Paranitharan', 'advance', 4000.00, 'LKR', '2026-04-26', '', 3, '2026-04-27 11:39:18'),
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
  `nic_number` varchar(30) DEFAULT NULL,
  `epf_member_no` varchar(30) DEFAULT NULL,
  `working_days` int(11) DEFAULT NULL,
  `days_paid` int(11) DEFAULT NULL,
  `payslip_ref` varchar(50) DEFAULT NULL,
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

INSERT INTO `payslips` (`id`, `template_id`, `employee_id`, `employee_name`, `employee_email`, `employee_phone`, `nic_number`, `epf_member_no`, `working_days`, `days_paid`, `payslip_ref`, `designation`, `department`, `employee_id_no`, `pay_period`, `pay_date`, `basic_salary`, `allowances`, `deductions`, `gross_salary`, `total_deductions`, `net_salary`, `currency`, `bank_name`, `account_no`, `notes`, `status`, `created_by`, `created_at`) VALUES
(1, 1, 3, 'Vignesh G', 'vignesh.g@thepadak.com', '+91 8525822546', NULL, NULL, NULL, NULL, NULL, 'Software Developer', 'Development', 'EMP-01', 'April 2026', '2026-04-25', 30000.00, '[]', '[]', 30000.00, 0.00, 30000.00, 'INR', 'HDFC Bank', '', '', 'issued', 3, '2026-04-24 09:26:34');

-- --------------------------------------------------------

--
-- Table structure for table `payslip_templates`
--

CREATE TABLE `payslip_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `company_reg_no` varchar(100) DEFAULT NULL,
  `company_phone` varchar(50) DEFAULT NULL,
  `company_email` varchar(150) DEFAULT NULL,
  `epf_employer_no` varchar(50) DEFAULT NULL,
  `authorized_by` varchar(150) DEFAULT NULL,
  `authorized_title` varchar(150) DEFAULT NULL,
  `signature_image` varchar(500) DEFAULT NULL,
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

INSERT INTO `payslip_templates` (`id`, `name`, `company_name`, `company_reg_no`, `company_phone`, `company_email`, `epf_employer_no`, `authorized_by`, `authorized_title`, `signature_image`, `company_address`, `company_logo`, `footer_note`, `is_default`, `created_by`, `created_at`) VALUES
(1, 'Testing Template', 'Padak', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nothing', 'uploads/documents/logo_69eae5c6505fe.png', 'This is a computer-generated payslip and requires no signature.', 0, 3, '2026-04-24 09:08:46');

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
  `department_role` enum('general','tele_caller','digital_marketing','software_developer','graphics_designer') DEFAULT 'general',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `avatar`, `phone`, `department`, `department_role`, `status`, `last_login`, `created_at`) VALUES
(1, 'Manager', 'manager@thepadak.com', '$2y$10$3.5QhvfumMj.22FYneOAnOu1ZsF6yg0iIqKausdogECfEacsX3x12', 'manager', NULL, '', 'Management', 'general', 'active', '2026-05-06 08:43:42', '2026-04-14 09:10:22'),
(2, 'Thiki', 'thiki@thepadak.com', '$2y$10$coRWR1BmT/jdTrfuK/RDleG6GTSbp791Llgm509no1dhXJYnl0Poq', 'admin', 'avatar_2_1777272800.png', '+41 798235584', 'Leadership', 'general', 'active', '2026-04-27 12:22:31', '2026-04-14 09:10:22'),
(3, 'Vignesh', 'vignesh@thepadak.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'avatar_3_1776997592.png', '', 'Development', 'general', 'active', '2026-05-08 09:20:00', '2026-04-14 09:10:22'),
(4, 'Member', 'member@thepadak.com', '$2y$10$31V1PALKq2KcgLKxek.mCOtEhjPSegnveTuOnQIXkCz2vHPolbzHG', 'member', NULL, '+41 798235584', 'Telecaller Intern', 'tele_caller', 'active', '2026-05-06 08:40:55', '2026-04-27 12:21:54');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=212;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

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

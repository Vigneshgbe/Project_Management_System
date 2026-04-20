-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 20, 2026 at 05:03 PM
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
(3, 1, 3, '2026-04-20 19:59:38'),
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
(1, 1, 'padak.service@gmail.com', '$2y$10$e6.vvXVy7iMPSF63fF3AReH8Lg6sNrRGEDvWq.NwyR.TkLa5bYLei', 'active', '2026-04-20 09:51:46', NULL, NULL, '2026-04-19 23:10:40');

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
  `category` varchar(100) DEFAULT 'General',
  `access` enum('all','admin','manager') DEFAULT 'all',
  `uploaded_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `title`, `description`, `filename`, `original_name`, `file_size`, `file_type`, `project_id`, `contact_id`, `category`, `access`, `uploaded_by`, `created_at`) VALUES
(1, 'Agreement Hypernova', 'This is my resume file', 'doc_69e445c51158a4.84392903.pdf', 'VigneshG_Software_Engineer_Resume.pdf', 54995, '0', 1, NULL, 'Development', 'manager', 1, '2026-04-19 08:32:29'),
(2, 'CRM AGREEMENT', 'Canada crm project agreement', 'doc_69e50c0bb80be1.07252161.pdf', 'CRM SOFTWARE DEVELOPMENT AGREEMENT.pdf', 155717, 'application/pdf', 2, NULL, 'Agreement', 'all', 3, '2026-04-19 22:38:27');

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
(1, '2026-01', 'January 2026', 10.00, '', 3, '2026-04-18 13:49:09');

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
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `project_id`, `assigned_to`, `created_by`, `status`, `priority`, `due_date`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 'Get Quotes from Client Blog', 'Collect Quotes from Client for frontend', 1, 1, 1, 'todo', 'medium', '2026-04-30', NULL, '2026-04-19 08:30:39', '2026-04-19 10:09:40');

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
(1, 'Admin', 'admin@thepadak.com', '$2y$10$3.5QhvfumMj.22FYneOAnOu1ZsF6yg0iIqKausdogECfEacsX3x12', 'manager', NULL, '', 'Management', 'active', '2026-04-19 08:27:01', '2026-04-14 09:10:22'),
(2, 'Thiki', 'thiki@thepadak.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', NULL, '', 'Leadership', 'active', '2026-04-19 22:24:36', '2026-04-14 09:10:22'),
(3, 'Vignesh', 'vignesh@thepadak.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, '', 'Development', 'active', '2026-04-20 19:59:13', '2026-04-14 09:10:22');

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_created` (`created_at`);

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
  ADD KEY `created_by` (`created_by`);
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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `chat_channels`
--
ALTER TABLE `chat_channels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `chat_members`
--
ALTER TABLE `chat_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lead_activities`
--
ALTER TABLE `lead_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

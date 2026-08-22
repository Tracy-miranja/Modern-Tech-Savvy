-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 21, 2026 at 01:10 PM
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
-- Database: `amsol`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_qualifications`
--

CREATE TABLE `academic_qualifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `institution_name` varchar(255) NOT NULL,
  `certification_obtained` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `access_requests`
--

CREATE TABLE `access_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `requester_id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `registration_token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `log_name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `causer_type` varchar(255) DEFAULT NULL,
  `causer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `batch_uuid` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `log_name`, `description`, `subject_type`, `event`, `subject_id`, `causer_type`, `causer_id`, `properties`, `batch_uuid`, `created_at`, `updated_at`) VALUES
(1, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-01-21 19:12:06', '2026-01-21 19:12:06'),
(2, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-01-22 05:18:41', '2026-01-22 05:18:41'),
(3, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-01-22 09:43:22', '2026-01-22 09:43:22'),
(4, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-01-22 11:54:26', '2026-01-22 11:54:26'),
(5, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-01-22 19:38:11', '2026-01-22 19:38:11'),
(6, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-01-23 05:07:33', '2026-01-23 05:07:33'),
(7, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-01-23 08:00:26', '2026-01-23 08:00:26'),
(8, 'default', 'Impersonated business', 'App\\Models\\Business', NULL, 4, 'App\\Models\\User', 9, '[]', NULL, '2026-01-23 08:00:52', '2026-01-23 08:00:52'),
(9, 'default', 'Impersonated business', 'App\\Models\\Business', NULL, 3, 'App\\Models\\User', 9, '[]', NULL, '2026-01-23 08:02:07', '2026-01-23 08:02:07'),
(10, 'default', 'Impersonated business', 'App\\Models\\Business', NULL, 3, 'App\\Models\\User', 9, '[]', NULL, '2026-01-23 08:04:07', '2026-01-23 08:04:07'),
(11, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-02-21 10:57:21', '2026-02-21 10:57:21'),
(12, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-02-21 20:03:31', '2026-02-21 20:03:31'),
(15, 'default', 'Resent two-factor authentication code', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-02-22 19:21:33', '2026-02-22 19:21:33'),
(16, 'default', 'Resent two-factor authentication code', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-02-22 19:21:39', '2026-02-22 19:21:39'),
(17, 'default', 'Resent two-factor authentication code', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-02-22 19:21:45', '2026-02-22 19:21:45'),
(20, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-02-22 19:22:52', '2026-02-22 19:22:52'),
(21, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-02-23 13:35:11', '2026-02-23 13:35:11'),
(22, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-02-24 06:14:36', '2026-02-24 06:14:36'),
(23, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-03-02 11:59:15', '2026-03-02 11:59:15'),
(24, 'default', 'Resent two-factor authentication code', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-03-04 08:39:10', '2026-03-04 08:39:10'),
(25, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-03-04 09:22:16', '2026-03-04 09:22:16'),
(26, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-03-20 07:57:27', '2026-03-20 07:57:27'),
(27, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-04-24 19:07:13', '2026-04-24 19:07:13'),
(28, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-04-25 05:11:53', '2026-04-25 05:11:53'),
(29, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-04-25 17:06:06', '2026-04-25 17:06:06'),
(30, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-04-29 14:45:04', '2026-04-29 14:45:04'),
(31, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-05-03 06:11:05', '2026-05-03 06:11:05'),
(32, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-05-03 11:43:08', '2026-05-03 11:43:08'),
(33, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-05-04 05:57:31', '2026-05-04 05:57:31'),
(34, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-05-04 11:46:14', '2026-05-04 11:46:14'),
(35, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-06-22 08:37:34', '2026-06-22 08:37:34'),
(36, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-06-22 13:18:04', '2026-06-22 13:18:04'),
(37, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-06-23 06:32:56', '2026-06-23 06:32:56'),
(40, 'default', 'Resent two-factor authentication code', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-06-23 06:45:06', '2026-06-23 06:45:06'),
(41, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-06-23 06:51:27', '2026-06-23 06:51:27'),
(42, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-06-23 11:20:06', '2026-06-23 11:20:06'),
(43, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-07-09 14:37:20', '2026-07-09 14:37:20'),
(45, 'default', 'Resent two-factor authentication code', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-08-06 08:57:07', '2026-08-06 08:57:07'),
(46, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-08-06 08:57:21', '2026-08-06 08:57:21'),
(47, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-08-10 07:20:31', '2026-08-10 07:20:31'),
(48, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-08-10 08:57:26', '2026-08-10 08:57:26'),
(49, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-08-11 07:15:40', '2026-08-11 07:15:40'),
(50, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-08-11 13:27:43', '2026-08-11 13:27:43'),
(51, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-08-12 05:25:45', '2026-08-12 05:25:45'),
(52, 'default', 'Granted krest-admin access', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-08-12 06:00:51', '2026-08-12 06:00:51'),
(53, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-08-17 13:22:51', '2026-08-17 13:22:51'),
(54, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-08-20 07:29:51', '2026-08-20 07:29:51'),
(55, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-08-21 08:59:38', '2026-08-21 08:59:38'),
(56, 'default', 'Completed two-factor authentication', 'App\\Models\\User', NULL, 9, 'App\\Models\\User', 9, '[]', NULL, '2026-08-21 09:41:33', '2026-08-21 09:41:33');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `loggable_type` varchar(255) NOT NULL,
  `loggable_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `title`, `description`, `loggable_type`, `loggable_id`, `created_at`, `updated_at`) VALUES
(13, 9, 'created', 'KrestHR created Business', 'KrestHR has created a Business record.', 'App\\Models\\Business', 3, '2026-01-20 18:30:33', '2026-01-20 18:30:33'),
(16, NULL, 'updated', 'System updated User', 'System has updated a User record.', 'App\\Models\\User', 9, '2026-01-20 18:45:15', '2026-01-20 18:45:15'),
(17, 9, 'created', 'KrestHR created Department', 'KrestHR has created a Department record.', 'App\\Models\\Department', 16, '2026-01-20 19:20:06', '2026-01-20 19:20:06'),
(18, 9, 'created', 'KrestHR created Department', 'KrestHR has created a Department record.', 'App\\Models\\Department', 17, '2026-01-20 19:20:51', '2026-01-20 19:20:51'),
(19, 9, 'created', 'KrestHR created Department', 'KrestHR has created a Department record.', 'App\\Models\\Department', 18, '2026-01-20 19:21:15', '2026-01-20 19:21:15'),
(20, 9, 'created', 'KrestHR created Department', 'KrestHR has created a Department record.', 'App\\Models\\Department', 19, '2026-01-20 19:21:35', '2026-01-20 19:21:35'),
(21, 9, 'created', 'KrestHR created Department', 'KrestHR has created a Department record.', 'App\\Models\\Department', 20, '2026-01-20 19:22:07', '2026-01-20 19:22:07'),
(22, 9, 'created', 'KrestHR created Department', 'KrestHR has created a Department record.', 'App\\Models\\Department', 21, '2026-01-20 19:22:25', '2026-01-20 19:22:25'),
(23, 9, 'created', 'KrestHR created Department', 'KrestHR has created a Department record.', 'App\\Models\\Department', 22, '2026-01-20 19:22:40', '2026-01-20 19:22:40'),
(24, 9, 'created', 'KrestHR created Department', 'KrestHR has created a Department record.', 'App\\Models\\Department', 23, '2026-01-20 19:22:54', '2026-01-20 19:22:54'),
(25, 9, 'created', 'KrestHR created Department', 'KrestHR has created a Department record.', 'App\\Models\\Department', 24, '2026-01-20 19:23:07', '2026-01-20 19:23:07'),
(26, 9, 'created', 'KrestHR created Department', 'KrestHR has created a Department record.', 'App\\Models\\Department', 25, '2026-01-20 19:24:12', '2026-01-20 19:24:12'),
(27, 9, 'created', 'KrestHR created JobCategory', 'KrestHR has created a JobCategory record.', 'App\\Models\\JobCategory', 1, '2026-01-20 19:30:12', '2026-01-20 19:30:12'),
(28, 9, 'created', 'KrestHR created JobCategory', 'KrestHR has created a JobCategory record.', 'App\\Models\\JobCategory', 2, '2026-01-20 19:30:25', '2026-01-20 19:30:25'),
(29, 9, 'created', 'KrestHR created JobCategory', 'KrestHR has created a JobCategory record.', 'App\\Models\\JobCategory', 3, '2026-01-20 19:30:39', '2026-01-20 19:30:39'),
(30, 9, 'created', 'KrestHR created JobCategory', 'KrestHR has created a JobCategory record.', 'App\\Models\\JobCategory', 4, '2026-01-20 19:33:20', '2026-01-20 19:33:20'),
(31, 9, 'created', 'KrestHR created JobCategory', 'KrestHR has created a JobCategory record.', 'App\\Models\\JobCategory', 5, '2026-01-20 19:33:33', '2026-01-20 19:33:33'),
(32, 9, 'created', 'KrestHR created JobCategory', 'KrestHR has created a JobCategory record.', 'App\\Models\\JobCategory', 6, '2026-01-20 19:33:46', '2026-01-20 19:33:46'),
(33, 9, 'created', 'KrestHR created JobCategory', 'KrestHR has created a JobCategory record.', 'App\\Models\\JobCategory', 7, '2026-01-20 19:33:57', '2026-01-20 19:33:57'),
(34, 9, 'created', 'KrestHR created JobCategory', 'KrestHR has created a JobCategory record.', 'App\\Models\\JobCategory', 8, '2026-01-20 19:34:09', '2026-01-20 19:34:09'),
(35, 9, 'created', 'KrestHR created JobCategory', 'KrestHR has created a JobCategory record.', 'App\\Models\\JobCategory', 9, '2026-01-20 19:34:20', '2026-01-20 19:34:20'),
(36, 9, 'created', 'KrestHR created JobCategory', 'KrestHR has created a JobCategory record.', 'App\\Models\\JobCategory', 10, '2026-01-20 19:34:31', '2026-01-20 19:34:31'),
(45, NULL, 'updated', 'System updated User', 'System has updated a User record.', 'App\\Models\\User', 1, '2026-01-21 19:02:02', '2026-01-21 19:02:02'),
(46, 9, 'created', 'KrestHR created Business', 'KrestHR has created a Business record.', 'App\\Models\\Business', 4, '2026-01-21 19:08:09', '2026-01-21 19:08:09'),
(48, 9, 'created', 'KrestHR created Location', 'KrestHR has created a Location record.', 'App\\Models\\Location', 1, '2026-01-22 10:23:56', '2026-01-22 10:23:56'),
(49, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 17, '2026-01-22 10:26:31', '2026-01-22 10:26:31'),
(50, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 11, '2026-01-22 10:26:40', '2026-01-22 10:26:40'),
(51, 9, 'created', 'KrestHR created EmploymentDetail', 'KrestHR has created a EmploymentDetail record.', 'App\\Models\\EmploymentDetail', 1, '2026-01-22 10:26:40', '2026-01-22 10:26:40'),
(52, 9, 'created', 'KrestHR created EmployeePaymentDetail', 'KrestHR has created a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 1, '2026-01-22 10:26:40', '2026-01-22 10:26:40'),
(54, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 19, '2026-01-22 10:58:58', '2026-01-22 10:58:58'),
(55, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 12, '2026-01-22 10:59:07', '2026-01-22 10:59:07'),
(56, 9, 'created', 'KrestHR created EmploymentDetail', 'KrestHR has created a EmploymentDetail record.', 'App\\Models\\EmploymentDetail', 2, '2026-01-22 10:59:07', '2026-01-22 10:59:07'),
(57, 9, 'created', 'KrestHR created EmployeePaymentDetail', 'KrestHR has created a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 2, '2026-01-22 10:59:07', '2026-01-22 10:59:07'),
(58, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 20, '2026-01-22 11:02:18', '2026-01-22 11:02:18'),
(59, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 13, '2026-01-22 11:02:57', '2026-01-22 11:02:57'),
(60, 9, 'created', 'KrestHR created EmploymentDetail', 'KrestHR has created a EmploymentDetail record.', 'App\\Models\\EmploymentDetail', 3, '2026-01-22 11:02:57', '2026-01-22 11:02:57'),
(61, 9, 'created', 'KrestHR created EmployeePaymentDetail', 'KrestHR has created a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 3, '2026-01-22 11:02:57', '2026-01-22 11:02:57'),
(62, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 21, '2026-01-22 11:06:17', '2026-01-22 11:06:17'),
(63, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 14, '2026-01-22 11:06:24', '2026-01-22 11:06:24'),
(64, 9, 'created', 'KrestHR created EmploymentDetail', 'KrestHR has created a EmploymentDetail record.', 'App\\Models\\EmploymentDetail', 4, '2026-01-22 11:06:24', '2026-01-22 11:06:24'),
(65, 9, 'created', 'KrestHR created EmployeePaymentDetail', 'KrestHR has created a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 4, '2026-01-22 11:06:24', '2026-01-22 11:06:24'),
(70, 9, 'created', 'KrestHR created Payroll', 'KrestHR has created a Payroll record.', 'App\\Models\\Payroll', 5, '2026-02-21 20:05:09', '2026-02-21 20:05:09'),
(71, 9, 'created', 'KrestHR created PayrollFormula', 'KrestHR has created a PayrollFormula record.', 'App\\Models\\PayrollFormula', 1, '2026-02-21 21:04:31', '2026-02-21 21:04:31'),
(72, 9, 'created', 'KrestHR created PayrollFormulaBracket', 'KrestHR has created a PayrollFormulaBracket record.', 'App\\Models\\PayrollFormulaBracket', 1, '2026-02-21 21:04:31', '2026-02-21 21:04:31'),
(73, 9, 'created', 'KrestHR created PayrollFormulaBracket', 'KrestHR has created a PayrollFormulaBracket record.', 'App\\Models\\PayrollFormulaBracket', 2, '2026-02-21 21:04:31', '2026-02-21 21:04:31'),
(74, 9, 'created', 'KrestHR created PayrollFormulaBracket', 'KrestHR has created a PayrollFormulaBracket record.', 'App\\Models\\PayrollFormulaBracket', 3, '2026-02-21 21:04:32', '2026-02-21 21:04:32'),
(75, 9, 'deleted', 'KrestHR deleted Payroll', 'KrestHR has deleted a Payroll record.', 'App\\Models\\Payroll', 5, '2026-02-21 21:04:54', '2026-02-21 21:04:54'),
(76, 9, 'created', 'KrestHR created Payroll', 'KrestHR has created a Payroll record.', 'App\\Models\\Payroll', 6, '2026-02-21 21:05:08', '2026-02-21 21:05:08'),
(77, 9, 'deleted', 'KrestHR deleted Payroll', 'KrestHR has deleted a Payroll record.', 'App\\Models\\Payroll', 6, '2026-02-21 21:09:45', '2026-02-21 21:09:45'),
(78, 9, 'created', 'KrestHR created Payroll', 'KrestHR has created a Payroll record.', 'App\\Models\\Payroll', 7, '2026-02-22 19:36:15', '2026-02-22 19:36:15'),
(79, 9, 'updated', 'KrestHR updated EmployeePaymentDetail', 'KrestHR has updated a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 2, '2026-02-24 08:09:35', '2026-02-24 08:09:35'),
(80, 9, 'updated', 'KrestHR updated EmployeePaymentDetail', 'KrestHR has updated a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 1, '2026-02-24 09:45:18', '2026-02-24 09:45:18'),
(81, 9, 'created', 'KrestHR created Deduction', 'KrestHR has created a Deduction record.', 'App\\Models\\Deduction', 7, '2026-02-24 11:23:05', '2026-02-24 11:23:05'),
(82, 9, 'created', 'KrestHR created Location', 'KrestHR has created a Location record.', 'App\\Models\\Location', 2, '2026-03-02 12:00:16', '2026-03-02 12:00:16'),
(83, 9, 'updated', 'KrestHR updated Employee', 'KrestHR has updated a Employee record.', 'App\\Models\\Employee', 13, '2026-03-02 12:00:47', '2026-03-02 12:00:47'),
(84, 9, 'updated', 'KrestHR updated EmployeePaymentDetail', 'KrestHR has updated a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 3, '2026-03-02 12:00:47', '2026-03-02 12:00:47'),
(85, 9, 'created', 'KrestHR created Payroll', 'KrestHR has created a Payroll record.', 'App\\Models\\Payroll', 8, '2026-03-02 12:02:22', '2026-03-02 12:02:22'),
(86, 9, 'created', 'KrestHR created Relief', 'KrestHR has created a Relief record.', 'App\\Models\\Relief', 1, '2026-03-02 14:09:41', '2026-03-02 14:09:41'),
(87, 9, 'created', 'KrestHR created Relief', 'KrestHR has created a Relief record.', 'App\\Models\\Relief', 2, '2026-03-02 14:10:38', '2026-03-02 14:10:38'),
(88, 9, 'created', 'KrestHR created Deduction', 'KrestHR has created a Deduction record.', 'App\\Models\\Deduction', 8, '2026-03-20 11:13:09', '2026-03-20 11:13:09'),
(89, 9, 'updated', 'KrestHR updated Deduction', 'KrestHR has updated a Deduction record.', 'App\\Models\\Deduction', 8, '2026-03-20 11:18:05', '2026-03-20 11:18:05'),
(90, 9, 'updated', 'KrestHR updated EmployeePaymentDetail', 'KrestHR has updated a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 4, '2026-03-20 11:26:22', '2026-03-20 11:26:22'),
(91, 9, 'updated', 'KrestHR updated Payroll', 'KrestHR has updated a Payroll record.', 'App\\Models\\Payroll', 8, '2026-03-20 11:27:23', '2026-03-20 11:27:23'),
(92, 9, 'created', 'KrestHR created Payroll', 'KrestHR has created a Payroll record.', 'App\\Models\\Payroll', 9, '2026-04-25 05:47:16', '2026-04-25 05:47:16'),
(95, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 24, '2026-05-03 06:22:05', '2026-05-03 06:22:05'),
(96, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 15, '2026-05-03 06:22:16', '2026-05-03 06:22:16'),
(97, 9, 'created', 'KrestHR created EmploymentDetail', 'KrestHR has created a EmploymentDetail record.', 'App\\Models\\EmploymentDetail', 5, '2026-05-03 06:22:16', '2026-05-03 06:22:16'),
(98, 9, 'created', 'KrestHR created EmployeePaymentDetail', 'KrestHR has created a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 5, '2026-05-03 06:22:16', '2026-05-03 06:22:16'),
(99, 9, 'updated', 'KrestHR updated Employee', 'KrestHR has updated a Employee record.', 'App\\Models\\Employee', 15, '2026-05-03 07:26:45', '2026-05-03 07:26:45'),
(100, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 25, '2026-05-03 07:29:02', '2026-05-03 07:29:02'),
(101, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 16, '2026-05-03 07:29:31', '2026-05-03 07:29:31'),
(102, 9, 'created', 'KrestHR created EmploymentDetail', 'KrestHR has created a EmploymentDetail record.', 'App\\Models\\EmploymentDetail', 6, '2026-05-03 07:29:31', '2026-05-03 07:29:31'),
(103, 9, 'created', 'KrestHR created EmployeePaymentDetail', 'KrestHR has created a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 6, '2026-05-03 07:29:31', '2026-05-03 07:29:31'),
(104, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 26, '2026-05-04 06:28:16', '2026-05-04 06:28:16'),
(105, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 17, '2026-05-04 06:28:48', '2026-05-04 06:28:48'),
(106, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 27, '2026-05-04 06:28:48', '2026-05-04 06:28:48'),
(107, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 18, '2026-05-04 06:28:55', '2026-05-04 06:28:55'),
(108, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 28, '2026-05-04 06:50:05', '2026-05-04 06:50:05'),
(109, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 19, '2026-05-04 06:50:11', '2026-05-04 06:50:11'),
(110, 9, 'created', 'KrestHR created EmploymentDetail', 'KrestHR has created a EmploymentDetail record.', 'App\\Models\\EmploymentDetail', 7, '2026-05-04 06:50:11', '2026-05-04 06:50:11'),
(111, 9, 'created', 'KrestHR created EmployeePaymentDetail', 'KrestHR has created a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 7, '2026-05-04 06:50:11', '2026-05-04 06:50:11'),
(112, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 29, '2026-05-04 06:50:11', '2026-05-04 06:50:11'),
(113, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 20, '2026-05-04 06:50:14', '2026-05-04 06:50:14'),
(114, 9, 'created', 'KrestHR created EmploymentDetail', 'KrestHR has created a EmploymentDetail record.', 'App\\Models\\EmploymentDetail', 8, '2026-05-04 06:50:14', '2026-05-04 06:50:14'),
(115, 9, 'created', 'KrestHR created EmployeePaymentDetail', 'KrestHR has created a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 8, '2026-05-04 06:50:14', '2026-05-04 06:50:14'),
(116, 9, 'deleted', 'KrestHR deleted User', 'KrestHR has deleted a User record.', 'App\\Models\\User', 28, '2026-05-04 07:16:49', '2026-05-04 07:16:49'),
(117, 9, 'deleted', 'KrestHR deleted Employee', 'KrestHR has deleted a Employee record.', 'App\\Models\\Employee', 19, '2026-05-04 07:16:49', '2026-05-04 07:16:49'),
(118, 9, 'deleted', 'KrestHR deleted User', 'KrestHR has deleted a User record.', 'App\\Models\\User', 29, '2026-05-04 07:16:53', '2026-05-04 07:16:53'),
(119, 9, 'deleted', 'KrestHR deleted Employee', 'KrestHR has deleted a Employee record.', 'App\\Models\\Employee', 20, '2026-05-04 07:16:53', '2026-05-04 07:16:53'),
(120, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 30, '2026-05-04 07:17:08', '2026-05-04 07:17:08'),
(121, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 21, '2026-05-04 07:17:15', '2026-05-04 07:17:15'),
(122, 9, 'created', 'KrestHR created EmploymentDetail', 'KrestHR has created a EmploymentDetail record.', 'App\\Models\\EmploymentDetail', 9, '2026-05-04 07:17:15', '2026-05-04 07:17:15'),
(123, 9, 'created', 'KrestHR created EmployeePaymentDetail', 'KrestHR has created a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 9, '2026-05-04 07:17:15', '2026-05-04 07:17:15'),
(124, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 31, '2026-05-04 07:17:15', '2026-05-04 07:17:15'),
(125, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 22, '2026-05-04 07:17:21', '2026-05-04 07:17:21'),
(126, 9, 'created', 'KrestHR created EmploymentDetail', 'KrestHR has created a EmploymentDetail record.', 'App\\Models\\EmploymentDetail', 10, '2026-05-04 07:17:21', '2026-05-04 07:17:21'),
(127, 9, 'created', 'KrestHR created EmployeePaymentDetail', 'KrestHR has created a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 10, '2026-05-04 07:17:21', '2026-05-04 07:17:21'),
(128, 9, 'deleted', 'KrestHR deleted User', 'KrestHR has deleted a User record.', 'App\\Models\\User', 30, '2026-05-04 07:18:05', '2026-05-04 07:18:05'),
(129, 9, 'deleted', 'KrestHR deleted Employee', 'KrestHR has deleted a Employee record.', 'App\\Models\\Employee', 21, '2026-05-04 07:18:05', '2026-05-04 07:18:05'),
(130, 9, 'deleted', 'KrestHR deleted User', 'KrestHR has deleted a User record.', 'App\\Models\\User', 31, '2026-05-04 07:18:09', '2026-05-04 07:18:09'),
(131, 9, 'deleted', 'KrestHR deleted Employee', 'KrestHR has deleted a Employee record.', 'App\\Models\\Employee', 22, '2026-05-04 07:18:09', '2026-05-04 07:18:09'),
(132, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 32, '2026-05-04 07:18:45', '2026-05-04 07:18:45'),
(133, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 23, '2026-05-04 07:18:55', '2026-05-04 07:18:55'),
(134, 9, 'created', 'KrestHR created EmploymentDetail', 'KrestHR has created a EmploymentDetail record.', 'App\\Models\\EmploymentDetail', 11, '2026-05-04 07:18:55', '2026-05-04 07:18:55'),
(135, 9, 'created', 'KrestHR created EmployeePaymentDetail', 'KrestHR has created a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 11, '2026-05-04 07:18:55', '2026-05-04 07:18:55'),
(136, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 33, '2026-05-04 07:18:55', '2026-05-04 07:18:55'),
(137, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 24, '2026-05-04 07:18:58', '2026-05-04 07:18:58'),
(138, 9, 'created', 'KrestHR created EmploymentDetail', 'KrestHR has created a EmploymentDetail record.', 'App\\Models\\EmploymentDetail', 12, '2026-05-04 07:18:58', '2026-05-04 07:18:58'),
(139, 9, 'created', 'KrestHR created EmployeePaymentDetail', 'KrestHR has created a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 12, '2026-05-04 07:18:58', '2026-05-04 07:18:58'),
(140, 9, 'deleted', 'KrestHR deleted User', 'KrestHR has deleted a User record.', 'App\\Models\\User', 32, '2026-05-04 07:40:49', '2026-05-04 07:40:49'),
(141, 9, 'deleted', 'KrestHR deleted Employee', 'KrestHR has deleted a Employee record.', 'App\\Models\\Employee', 23, '2026-05-04 07:40:49', '2026-05-04 07:40:49'),
(142, 9, 'deleted', 'KrestHR deleted User', 'KrestHR has deleted a User record.', 'App\\Models\\User', 33, '2026-05-04 07:40:53', '2026-05-04 07:40:53'),
(143, 9, 'deleted', 'KrestHR deleted Employee', 'KrestHR has deleted a Employee record.', 'App\\Models\\Employee', 24, '2026-05-04 07:40:53', '2026-05-04 07:40:53'),
(144, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 34, '2026-05-04 07:41:08', '2026-05-04 07:41:08'),
(145, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 25, '2026-05-04 07:41:14', '2026-05-04 07:41:14'),
(146, 9, 'created', 'KrestHR created EmploymentDetail', 'KrestHR has created a EmploymentDetail record.', 'App\\Models\\EmploymentDetail', 13, '2026-05-04 07:41:14', '2026-05-04 07:41:14'),
(147, 9, 'created', 'KrestHR created EmployeePaymentDetail', 'KrestHR has created a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 13, '2026-05-04 07:41:14', '2026-05-04 07:41:14'),
(148, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 35, '2026-05-04 07:41:14', '2026-05-04 07:41:14'),
(149, 9, 'created', 'KrestHR created Employee', 'KrestHR has created a Employee record.', 'App\\Models\\Employee', 26, '2026-05-04 07:41:17', '2026-05-04 07:41:17'),
(150, 9, 'created', 'KrestHR created EmploymentDetail', 'KrestHR has created a EmploymentDetail record.', 'App\\Models\\EmploymentDetail', 14, '2026-05-04 07:41:17', '2026-05-04 07:41:17'),
(151, 9, 'created', 'KrestHR created EmployeePaymentDetail', 'KrestHR has created a EmployeePaymentDetail record.', 'App\\Models\\EmployeePaymentDetail', 14, '2026-05-04 07:41:17', '2026-05-04 07:41:17'),
(152, 9, 'created', 'KrestHR created JobPost', 'KrestHR has created a JobPost record.', 'App\\Models\\JobPost', 1, '2026-06-22 10:15:29', '2026-06-22 10:15:29'),
(153, 9, 'updated', 'KrestHR updated JobPost', 'KrestHR has updated a JobPost record.', 'App\\Models\\JobPost', 1, '2026-06-22 10:15:55', '2026-06-22 10:15:55'),
(154, 9, 'created', 'KrestHR created User', 'KrestHR has created a User record.', 'App\\Models\\User', 36, '2026-06-22 10:20:57', '2026-06-22 10:20:57'),
(155, 9, 'created', 'KrestHR created Applicant', 'KrestHR has created a Applicant record.', 'App\\Models\\Applicant', 1, '2026-06-22 10:21:00', '2026-06-22 10:21:00'),
(156, 9, 'created', 'KrestHR created Application', 'KrestHR has created a Application record.', 'App\\Models\\Application', 1, '2026-06-22 10:27:06', '2026-06-22 10:27:06'),
(157, 9, 'updated', 'KrestHR updated Applicant', 'KrestHR has updated a Applicant record.', 'App\\Models\\Applicant', 1, '2026-06-22 12:02:43', '2026-06-22 12:02:43'),
(158, 9, 'updated', 'KrestHR updated Application', 'KrestHR has updated a Application record.', 'App\\Models\\Application', 1, '2026-06-22 12:38:26', '2026-06-22 12:38:26'),
(159, 9, 'created', 'KrestHR created Interview', 'KrestHR has created a Interview record.', 'App\\Models\\Interview', 1, '2026-06-23 07:01:25', '2026-06-23 07:01:25'),
(160, 9, 'updated', 'KrestHR updated Application', 'KrestHR has updated a Application record.', 'App\\Models\\Application', 1, '2026-06-23 07:01:25', '2026-06-23 07:01:25'),
(161, 9, 'created', 'KrestHR created LeavePeriod', 'KrestHR has created a LeavePeriod record.', 'App\\Models\\LeavePeriod', 1, '2026-07-09 14:39:55', '2026-07-09 14:39:55'),
(162, NULL, 'updated', 'System updated User', 'System has updated a User record.', 'App\\Models\\User', 9, '2026-08-10 08:56:03', '2026-08-10 08:56:03'),
(163, 9, 'created', 'KrestHR created Payroll', 'KrestHR has created a Payroll record.', 'App\\Models\\Payroll', 10, '2026-08-11 12:11:45', '2026-08-11 12:11:45'),
(164, 9, 'updated', 'KrestHR updated Payroll', 'KrestHR has updated a Payroll record.', 'App\\Models\\Payroll', 10, '2026-08-11 12:11:45', '2026-08-11 12:11:45'),
(165, 9, 'deleted', 'KrestHR deleted Payroll', 'KrestHR has deleted a Payroll record.', 'App\\Models\\Payroll', 10, '2026-08-11 12:23:53', '2026-08-11 12:23:53'),
(166, 9, 'created', 'KrestHR created PayrollFormula', 'KrestHR has created a PayrollFormula record.', 'App\\Models\\PayrollFormula', 37, '2026-08-11 13:12:17', '2026-08-11 13:12:17'),
(167, 9, 'created', 'KrestHR created PayrollFormulaBracket', 'KrestHR has created a PayrollFormulaBracket record.', 'App\\Models\\PayrollFormulaBracket', 4, '2026-08-11 13:12:17', '2026-08-11 13:12:17'),
(168, 9, 'created', 'KrestHR created Payroll', 'KrestHR has created a Payroll record.', 'App\\Models\\Payroll', 11, '2026-08-12 06:52:33', '2026-08-12 06:52:33'),
(169, 9, 'updated', 'KrestHR updated Payroll', 'KrestHR has updated a Payroll record.', 'App\\Models\\Payroll', 11, '2026-08-12 06:52:33', '2026-08-12 06:52:33'),
(170, 9, 'created', 'KrestHR created Interview', 'KrestHR has created a Interview record.', 'App\\Models\\Interview', 2, '2026-08-12 08:27:51', '2026-08-12 08:27:51'),
(171, 9, 'created', 'KrestHR created PayrollFormula', 'KrestHR has created a PayrollFormula record.', 'App\\Models\\PayrollFormula', 38, '2026-08-21 09:44:35', '2026-08-21 09:44:35'),
(172, 9, 'created', 'KrestHR created PayrollFormulaBracket', 'KrestHR has created a PayrollFormulaBracket record.', 'App\\Models\\PayrollFormulaBracket', 5, '2026-08-21 09:44:35', '2026-08-21 09:44:35'),
(173, 9, 'created', 'KrestHR created PayrollFormulaBracket', 'KrestHR has created a PayrollFormulaBracket record.', 'App\\Models\\PayrollFormulaBracket', 6, '2026-08-21 09:45:02', '2026-08-21 09:45:02'),
(174, 9, 'created', 'KrestHR created PayrollFormulaBracket', 'KrestHR has created a PayrollFormulaBracket record.', 'App\\Models\\PayrollFormulaBracket', 7, '2026-08-21 09:48:10', '2026-08-21 09:48:10');

-- --------------------------------------------------------

--
-- Table structure for table `advances`
--

CREATE TABLE `advances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `allowances`
--

CREATE TABLE `allowances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `rate` decimal(5,2) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `calculation_basis` varchar(255) DEFAULT NULL,
  `applies_to` varchar(255) DEFAULT NULL,
  `is_taxable` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_employer_contribution` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `age` tinyint(3) UNSIGNED DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `plwd` tinyint(1) NOT NULL DEFAULT 0,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `home_county` varchar(100) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `zip_code` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `linkedin_profile` varchar(255) DEFAULT NULL,
  `portfolio_url` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `current_job_title` varchar(255) DEFAULT NULL,
  `current_company` varchar(255) DEFAULT NULL,
  `experience_level` varchar(255) DEFAULT NULL,
  `education_level` varchar(255) DEFAULT NULL,
  `desired_salary` varchar(255) DEFAULT NULL,
  `job_preferences` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`id`, `user_id`, `fullname`, `full_name`, `id_number`, `email`, `phone`, `gender`, `dob`, `age`, `nationality`, `plwd`, `address`, `city`, `home_county`, `state`, `zip_code`, `country`, `linkedin_profile`, `portfolio_url`, `summary`, `current_job_title`, `current_company`, `experience_level`, `education_level`, `desired_salary`, `job_preferences`, `source`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 36, 'tracy  miranja', NULL, NULL, NULL, '+769212978', NULL, NULL, NULL, NULL, 0, '123 maint t', NULL, NULL, NULL, NULL, 'kenya', NULL, NULL, NULL, 'software developer', 'google', 'Mid-level', NULL, NULL, 'web developer', NULL, 9, '2026-06-22 10:21:00', '2026-06-22 12:02:43');

-- --------------------------------------------------------

--
-- Table structure for table `applicant_skills`
--

CREATE TABLE `applicant_skills` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `applicant_id` bigint(20) UNSIGNED NOT NULL,
  `skill_id` bigint(20) UNSIGNED NOT NULL,
  `skill_level` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `applicant_id` bigint(20) UNSIGNED NOT NULL,
  `job_post_id` bigint(20) UNSIGNED NOT NULL,
  `cover_letter` longtext DEFAULT NULL,
  `stage` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `match_score` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `business_id`, `location_id`, `applicant_id`, `job_post_id`, `cover_letter`, `stage`, `notes`, `created_by`, `match_score`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 1, 1, NULL, 'in_progress', NULL, 9, NULL, '2026-06-22 10:27:06', '2026-06-23 07:01:25');

-- --------------------------------------------------------

--
-- Table structure for table `application_academics`
--

CREATE TABLE `application_academics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `application_id` bigint(20) UNSIGNED NOT NULL,
  `qualification_level` varchar(80) NOT NULL,
  `institution_name` varchar(255) NOT NULL,
  `institution_country` varchar(100) DEFAULT NULL,
  `qualification_name` varchar(255) NOT NULL,
  `certificate_number` varchar(100) DEFAULT NULL,
  `year_completed` smallint(5) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_documents`
--

CREATE TABLE `application_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `application_id` bigint(20) UNSIGNED NOT NULL,
  `doc_type` varchar(60) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `media_id` bigint(20) UNSIGNED DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_memberships`
--

CREATE TABLE `application_memberships` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `application_id` bigint(20) UNSIGNED NOT NULL,
  `organization_name` varchar(255) NOT NULL,
  `membership_number` varchar(120) NOT NULL,
  `membership_type` varchar(100) DEFAULT NULL,
  `year_joined` smallint(5) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_work_experiences`
--

CREATE TABLE `application_work_experiences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `application_id` bigint(20) UNSIGNED NOT NULL,
  `employer_name` varchar(255) NOT NULL,
  `employer_contact` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `job_title` varchar(255) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `achievements` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendances`
--

CREATE TABLE `attendances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `work_schedule_id` bigint(20) UNSIGNED DEFAULT NULL,
  `shift_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `clock_in` time DEFAULT NULL,
  `expected_clock_in` time DEFAULT NULL,
  `clock_out` time DEFAULT NULL,
  `expected_clock_out` time DEFAULT NULL,
  `overtime_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `overtime_regular` decimal(5,2) NOT NULL DEFAULT 0.00,
  `overtime_holiday` decimal(5,2) NOT NULL DEFAULT 0.00,
  `regular_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `late_minutes` decimal(5,2) NOT NULL DEFAULT 0.00,
  `early_departure_minutes` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_absent` tinyint(1) NOT NULL DEFAULT 0,
  `is_working_day` tinyint(1) NOT NULL DEFAULT 1,
  `is_holiday` tinyint(1) NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `logged_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `device_mac` varchar(64) DEFAULT NULL,
  `punch_latitude` decimal(10,7) DEFAULT NULL,
  `punch_longitude` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `businesses`
--

CREATE TABLE `businesses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `hr_email` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `industry` varchar(255) NOT NULL,
  `company_size` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `non_working_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`non_working_days`)),
  `code` varchar(255) NOT NULL,
  `registration_no` varchar(255) DEFAULT NULL,
  `tax_pin_no` varchar(255) DEFAULT NULL,
  `business_license_no` varchar(255) DEFAULT NULL,
  `physical_address` varchar(255) DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `currency` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `enforce_geofence` tinyint(1) NOT NULL DEFAULT 0,
  `enforce_mac` tinyint(1) NOT NULL DEFAULT 0,
  `extra_geofences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra_geofences`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `businesses`
--

INSERT INTO `businesses` (`id`, `user_id`, `company_name`, `email`, `hr_email`, `slug`, `industry`, `company_size`, `phone`, `country`, `non_working_days`, `code`, `registration_no`, `tax_pin_no`, `business_license_no`, `physical_address`, `verified`, `currency`, `created_at`, `updated_at`, `enforce_geofence`, `enforce_mac`, `extra_geofences`) VALUES
(2, 9, 'Krest', 'miranjatracy@gmail.com', NULL, 'krest', 'Technology', 'Small', '+254712030232', 'Kenya', NULL, '+254', NULL, NULL, NULL, NULL, 1, 'KES', '2026-01-20 17:51:27', '2026-01-20 17:51:27', 0, 0, NULL),
(3, 9, 'grace tech', NULL, NULL, 'grace-tech', 'logistics-and-supply-chain', '51-200', '++254712 030232', 'Kenya', NULL, '+254', '4534656', 'P9087435464', '34645654645', 'juja, kalimoni', 1, NULL, '2026-01-20 18:30:33', '2026-01-20 18:30:33', 0, 0, NULL),
(4, 9, 'KrestHR', NULL, NULL, 'kresthr', 'finance', '11-50', '++254769212978', 'Kenya', NULL, '+254', 'A67ytrwif', 'PA00JK7YU!26', 'Juja', 'P.O BOX  38121, Nairobi', 0, NULL, '2026-01-21 19:08:09', '2026-01-21 19:08:09', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `business_currencies`
--

CREATE TABLE `business_currencies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `currency_code` varchar(10) NOT NULL,
  `currency_name` varchar(100) NOT NULL,
  `symbol` varchar(10) DEFAULT NULL,
  `decimal_places` tinyint(3) UNSIGNED NOT NULL DEFAULT 2,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `rate_mode` enum('auto','manual') NOT NULL DEFAULT 'auto',
  `manual_rate` decimal(20,8) DEFAULT NULL,
  `auto_rate` decimal(20,8) DEFAULT NULL,
  `rate_fetched_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_currencies`
--

INSERT INTO `business_currencies` (`id`, `business_id`, `currency_code`, `currency_name`, `symbol`, `decimal_places`, `is_primary`, `rate_mode`, `manual_rate`, `auto_rate`, `rate_fetched_at`, `created_at`, `updated_at`) VALUES
(1, 2, 'USD', 'United States Dollar', '$', 2, 0, 'manual', 126.00000000, 129.51138300, '2026-03-20 11:12:12', '2026-03-20 11:12:09', '2026-03-20 11:26:40'),
(2, 2, 'KES', 'Kenyan Shilling', 'KSh', 2, 1, 'auto', NULL, 1.00000000, '2026-03-20 11:28:37', '2026-03-20 11:28:37', '2026-03-20 11:28:37');

-- --------------------------------------------------------

--
-- Table structure for table `business_modules`
--

CREATE TABLE `business_modules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `module_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `subscription_ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_modules`
--

INSERT INTO `business_modules` (`id`, `business_id`, `module_id`, `is_active`, `subscription_ends_at`, `created_at`, `updated_at`) VALUES
(3, 2, 5, 1, NULL, '2026-01-21 19:13:01', '2026-01-21 19:13:01'),
(4, 2, 6, 1, NULL, '2026-01-21 19:13:01', '2026-01-21 19:13:01');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('5c785c036466adea360111aa28563bfd556b5fba', 'i:2;', 1787305327),
('5c785c036466adea360111aa28563bfd556b5fba:timer', 'i:1787305327;', 1787305327),
('fx_rates_usd_base', 'a:166:{s:3:\"USD\";i:1;s:3:\"AED\";d:3.6725;s:3:\"AFN\";d:63.931872;s:3:\"ALL\";d:83.474203;s:3:\"AMD\";d:377.402368;s:3:\"ANG\";d:1.79;s:3:\"AOA\";d:921.554267;s:3:\"ARS\";d:1452.25;s:3:\"AUD\";d:1.416505;s:3:\"AWG\";d:1.79;s:3:\"AZN\";d:1.699507;s:3:\"BAM\";d:1.694603;s:3:\"BBD\";i:2;s:3:\"BDT\";d:122.794329;s:3:\"BGN\";d:1.635264;s:3:\"BHD\";d:0.376;s:3:\"BIF\";d:2975.085171;s:3:\"BMD\";i:1;s:3:\"BND\";d:1.279786;s:3:\"BOB\";d:6.936549;s:3:\"BRL\";d:5.257551;s:3:\"BSD\";i:1;s:3:\"BTN\";d:93.064693;s:3:\"BWP\";d:13.837788;s:3:\"BYN\";d:3.061224;s:3:\"BZD\";i:2;s:3:\"CAD\";d:1.372665;s:3:\"CDF\";d:2276.526928;s:3:\"CHF\";d:0.790629;s:3:\"CLF\";d:0.023173;s:3:\"CLP\";d:915.941442;s:3:\"CNH\";d:6.889574;s:3:\"CNY\";d:6.899831;s:3:\"COP\";d:3699.199374;s:3:\"CRC\";d:468.436368;s:3:\"CUP\";i:24;s:3:\"CVE\";d:95.53767;s:3:\"CZK\";d:21.235811;s:3:\"DJF\";d:177.721;s:3:\"DKK\";d:6.474457;s:3:\"DOP\";d:60.605218;s:3:\"DZD\";d:132.200465;s:3:\"EGP\";d:52.238547;s:3:\"ERN\";i:15;s:3:\"ETB\";d:155.708707;s:3:\"EUR\";d:0.866447;s:3:\"FJD\";d:2.209327;s:3:\"FKP\";d:0.747454;s:3:\"FOK\";d:6.474246;s:3:\"GBP\";d:0.747866;s:3:\"GEL\";d:2.722269;s:3:\"GGP\";d:0.747454;s:3:\"GHS\";d:10.9371;s:3:\"GIP\";d:0.747454;s:3:\"GMD\";d:74.183643;s:3:\"GNF\";d:8761.847747;s:3:\"GTQ\";d:7.664963;s:3:\"GYD\";d:209.163866;s:3:\"HKD\";d:7.832995;s:3:\"HNL\";d:26.510626;s:3:\"HRK\";d:6.528169;s:3:\"HTG\";d:131.131114;s:3:\"HUF\";d:339.305905;s:3:\"IDR\";d:16956.74925;s:3:\"ILS\";d:3.115728;s:3:\"IMP\";d:0.747454;s:3:\"INR\";d:93.065308;s:3:\"IQD\";d:1309.867136;s:3:\"IRR\";d:1171832.162966;s:3:\"ISK\";d:124.677277;s:3:\"JEP\";d:0.747454;s:3:\"JMD\";d:157.607169;s:3:\"JOD\";d:0.709;s:3:\"JPY\";d:158.277833;s:3:\"KES\";d:129.511383;s:3:\"KGS\";d:87.466993;s:3:\"KHR\";d:4014.325388;s:3:\"KID\";d:1.416488;s:3:\"KMF\";d:426.259037;s:3:\"KRW\";d:1493.868581;s:3:\"KWD\";d:0.306696;s:3:\"KYD\";d:0.833333;s:3:\"KZT\";d:480.681298;s:3:\"LAK\";d:21632.427932;s:3:\"LBP\";i:89500;s:3:\"LKR\";d:311.291596;s:3:\"LRD\";d:183.565465;s:3:\"LSL\";d:16.797113;s:3:\"LYD\";d:6.384585;s:3:\"MAD\";d:9.364331;s:3:\"MDL\";d:17.494868;s:3:\"MGA\";d:4160.296323;s:3:\"MKD\";d:53.581241;s:3:\"MMK\";d:2098.957323;s:3:\"MNT\";d:3560.009503;s:3:\"MOP\";d:8.067985;s:3:\"MRU\";d:39.960279;s:3:\"MUR\";d:46.480435;s:3:\"MVR\";d:15.448173;s:3:\"MWK\";d:1742.547895;s:3:\"MXN\";d:17.793646;s:3:\"MYR\";d:3.935985;s:3:\"MZN\";d:63.639336;s:3:\"NAD\";d:16.797113;s:3:\"NGN\";d:1358.155938;s:3:\"NIO\";d:36.843753;s:3:\"NOK\";d:9.528567;s:3:\"NPR\";d:148.903508;s:3:\"NZD\";d:1.707466;s:3:\"OMR\";d:0.384497;s:3:\"PAB\";i:1;s:3:\"PEN\";d:3.443768;s:3:\"PGK\";d:4.32566;s:3:\"PHP\";d:59.999521;s:3:\"PKR\";d:279.669555;s:3:\"PLN\";d:3.70293;s:3:\"PYG\";d:6494.439218;s:3:\"QAR\";d:3.64;s:3:\"RON\";d:4.429108;s:3:\"RSD\";d:101.934449;s:3:\"RUB\";d:85.824461;s:3:\"RWF\";d:1458.351518;s:3:\"SAR\";d:3.75;s:3:\"SBD\";d:7.937153;s:3:\"SCR\";d:13.936836;s:3:\"SDG\";d:543.563998;s:3:\"SEK\";d:9.335347;s:3:\"SGD\";d:1.279595;s:3:\"SHP\";d:0.747454;s:3:\"SLE\";d:24.597159;s:3:\"SLL\";d:24597.158802;s:3:\"SOS\";d:571.665143;s:3:\"SRD\";d:37.74181;s:3:\"SSP\";d:4580.287032;s:3:\"STN\";d:21.227705;s:3:\"SYP\";d:112.412065;s:3:\"SZL\";d:16.797113;s:3:\"THB\";d:32.706881;s:3:\"TJS\";d:9.571879;s:3:\"TMT\";d:3.499562;s:3:\"TND\";d:2.929171;s:3:\"TOP\";d:2.365221;s:3:\"TRY\";d:44.308785;s:3:\"TTD\";d:6.773261;s:3:\"TVD\";d:1.416488;s:3:\"TWD\";d:31.870474;s:3:\"TZS\";d:2592.628414;s:3:\"UAH\";d:43.971314;s:3:\"UGX\";d:3775.227354;s:3:\"UYU\";d:40.634024;s:3:\"UZS\";d:12200.908054;s:3:\"VES\";d:455.2547;s:3:\"VND\";d:26170.785373;s:3:\"VUV\";d:118.982062;s:3:\"WST\";d:2.700608;s:3:\"XAF\";d:568.345383;s:3:\"XCD\";d:2.7;s:3:\"XCG\";d:1.79;s:3:\"XDR\";d:0.734893;s:3:\"XOF\";d:568.345383;s:3:\"XPF\";d:103.393654;s:3:\"YER\";d:238.3931;s:3:\"ZAR\";d:16.79741;s:3:\"ZMW\";d:19.582227;s:3:\"ZWG\";d:25.3041;s:3:\"ZWL\";d:25.3041;}', 1774026732),
('leave_accrual:last_run_date', 's:10:\"2026-08-21\";', 2102662621),
('trends_Attendance_2_2026', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1787324393),
('trends_Attendance_3_2026', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1769176935),
('trends_Attendance_4_2026', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1769176915),
('trends_EmployeePayroll_2_2026', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:4:{i:0;O:26:\"App\\Models\\EmployeePayroll\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"employee_payrolls\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:3:{s:5:\"month\";s:7:\"2026-02\";s:13:\"total_net_pay\";s:9:\"807744.00\";s:15:\"total_gross_pay\";s:10:\"1140000.00\";}s:11:\"\0*\0original\";a:3:{s:5:\"month\";s:7:\"2026-02\";s:13:\"total_net_pay\";s:9:\"807744.00\";s:15:\"total_gross_pay\";s:10:\"1140000.00\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:24:{s:10:\"allowances\";s:4:\"json\";s:10:\"deductions\";s:4:\"json\";s:8:\"overtime\";s:4:\"json\";s:7:\"reliefs\";s:4:\"json\";s:12:\"basic_salary\";s:5:\"float\";s:17:\"housing_allowance\";s:5:\"float\";s:9:\"gross_pay\";s:5:\"float\";s:4:\"paye\";s:5:\"float\";s:19:\"paye_before_reliefs\";s:5:\"float\";s:4:\"shif\";s:5:\"float\";s:4:\"nssf\";s:5:\"float\";s:7:\"pension\";s:5:\"float\";s:12:\"housing_levy\";s:5:\"float\";s:4:\"helb\";s:5:\"float\";s:14:\"taxable_income\";s:5:\"float\";s:15:\"personal_relief\";s:5:\"float\";s:16:\"insurance_relief\";s:5:\"float\";s:13:\"pay_after_tax\";s:5:\"float\";s:14:\"loan_repayment\";s:5:\"float\";s:16:\"advance_recovery\";s:5:\"float\";s:20:\"deductions_after_tax\";s:5:\"float\";s:7:\"net_pay\";s:5:\"float\";s:21:\"pwd_exemption_applied\";s:7:\"boolean\";s:20:\"pwd_exemption_amount\";s:5:\"float\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:32:{i:0;s:10:\"payroll_id\";i:1;s:11:\"employee_id\";i:2;s:26:\"employee_payment_detail_id\";i:3;s:12:\"basic_salary\";i:4;s:17:\"housing_allowance\";i:5;s:9:\"gross_pay\";i:6;s:4:\"paye\";i:7;s:19:\"paye_before_reliefs\";i:8;s:4:\"shif\";i:9;s:4:\"nssf\";i:10;s:7:\"pension\";i:11;s:12:\"housing_levy\";i:12;s:4:\"helb\";i:13;s:14:\"taxable_income\";i:14;s:7:\"reliefs\";i:15;s:15:\"personal_relief\";i:16;s:16:\"insurance_relief\";i:17;s:13:\"pay_after_tax\";i:18;s:14:\"loan_repayment\";i:19;s:16:\"advance_recovery\";i:20;s:20:\"deductions_after_tax\";i:21;s:7:\"net_pay\";i:22;s:10:\"deductions\";i:23;s:8:\"overtime\";i:24;s:10:\"allowances\";i:25;s:9:\"bank_name\";i:26;s:14:\"account_number\";i:27;s:18:\"attendance_present\";i:28;s:17:\"attendance_absent\";i:29;s:13:\"days_in_month\";i:30;s:21:\"pwd_exemption_applied\";i:31;s:20:\"pwd_exemption_amount\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:26:\"App\\Models\\EmployeePayroll\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"employee_payrolls\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:3:{s:5:\"month\";s:7:\"2026-03\";s:13:\"total_net_pay\";s:9:\"693320.00\";s:15:\"total_gross_pay\";s:10:\"1179999.78\";}s:11:\"\0*\0original\";a:3:{s:5:\"month\";s:7:\"2026-03\";s:13:\"total_net_pay\";s:9:\"693320.00\";s:15:\"total_gross_pay\";s:10:\"1179999.78\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:24:{s:10:\"allowances\";s:4:\"json\";s:10:\"deductions\";s:4:\"json\";s:8:\"overtime\";s:4:\"json\";s:7:\"reliefs\";s:4:\"json\";s:12:\"basic_salary\";s:5:\"float\";s:17:\"housing_allowance\";s:5:\"float\";s:9:\"gross_pay\";s:5:\"float\";s:4:\"paye\";s:5:\"float\";s:19:\"paye_before_reliefs\";s:5:\"float\";s:4:\"shif\";s:5:\"float\";s:4:\"nssf\";s:5:\"float\";s:7:\"pension\";s:5:\"float\";s:12:\"housing_levy\";s:5:\"float\";s:4:\"helb\";s:5:\"float\";s:14:\"taxable_income\";s:5:\"float\";s:15:\"personal_relief\";s:5:\"float\";s:16:\"insurance_relief\";s:5:\"float\";s:13:\"pay_after_tax\";s:5:\"float\";s:14:\"loan_repayment\";s:5:\"float\";s:16:\"advance_recovery\";s:5:\"float\";s:20:\"deductions_after_tax\";s:5:\"float\";s:7:\"net_pay\";s:5:\"float\";s:21:\"pwd_exemption_applied\";s:7:\"boolean\";s:20:\"pwd_exemption_amount\";s:5:\"float\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:32:{i:0;s:10:\"payroll_id\";i:1;s:11:\"employee_id\";i:2;s:26:\"employee_payment_detail_id\";i:3;s:12:\"basic_salary\";i:4;s:17:\"housing_allowance\";i:5;s:9:\"gross_pay\";i:6;s:4:\"paye\";i:7;s:19:\"paye_before_reliefs\";i:8;s:4:\"shif\";i:9;s:4:\"nssf\";i:10;s:7:\"pension\";i:11;s:12:\"housing_levy\";i:12;s:4:\"helb\";i:13;s:14:\"taxable_income\";i:14;s:7:\"reliefs\";i:15;s:15:\"personal_relief\";i:16;s:16:\"insurance_relief\";i:17;s:13:\"pay_after_tax\";i:18;s:14:\"loan_repayment\";i:19;s:16:\"advance_recovery\";i:20;s:20:\"deductions_after_tax\";i:21;s:7:\"net_pay\";i:22;s:10:\"deductions\";i:23;s:8:\"overtime\";i:24;s:10:\"allowances\";i:25;s:9:\"bank_name\";i:26;s:14:\"account_number\";i:27;s:18:\"attendance_present\";i:28;s:17:\"attendance_absent\";i:29;s:13:\"days_in_month\";i:30;s:21:\"pwd_exemption_applied\";i:31;s:20:\"pwd_exemption_amount\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:2;O:26:\"App\\Models\\EmployeePayroll\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"employee_payrolls\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:3:{s:5:\"month\";s:7:\"2026-04\";s:13:\"total_net_pay\";s:9:\"793693.00\";s:15:\"total_gross_pay\";s:10:\"1179999.78\";}s:11:\"\0*\0original\";a:3:{s:5:\"month\";s:7:\"2026-04\";s:13:\"total_net_pay\";s:9:\"793693.00\";s:15:\"total_gross_pay\";s:10:\"1179999.78\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:24:{s:10:\"allowances\";s:4:\"json\";s:10:\"deductions\";s:4:\"json\";s:8:\"overtime\";s:4:\"json\";s:7:\"reliefs\";s:4:\"json\";s:12:\"basic_salary\";s:5:\"float\";s:17:\"housing_allowance\";s:5:\"float\";s:9:\"gross_pay\";s:5:\"float\";s:4:\"paye\";s:5:\"float\";s:19:\"paye_before_reliefs\";s:5:\"float\";s:4:\"shif\";s:5:\"float\";s:4:\"nssf\";s:5:\"float\";s:7:\"pension\";s:5:\"float\";s:12:\"housing_levy\";s:5:\"float\";s:4:\"helb\";s:5:\"float\";s:14:\"taxable_income\";s:5:\"float\";s:15:\"personal_relief\";s:5:\"float\";s:16:\"insurance_relief\";s:5:\"float\";s:13:\"pay_after_tax\";s:5:\"float\";s:14:\"loan_repayment\";s:5:\"float\";s:16:\"advance_recovery\";s:5:\"float\";s:20:\"deductions_after_tax\";s:5:\"float\";s:7:\"net_pay\";s:5:\"float\";s:21:\"pwd_exemption_applied\";s:7:\"boolean\";s:20:\"pwd_exemption_amount\";s:5:\"float\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:32:{i:0;s:10:\"payroll_id\";i:1;s:11:\"employee_id\";i:2;s:26:\"employee_payment_detail_id\";i:3;s:12:\"basic_salary\";i:4;s:17:\"housing_allowance\";i:5;s:9:\"gross_pay\";i:6;s:4:\"paye\";i:7;s:19:\"paye_before_reliefs\";i:8;s:4:\"shif\";i:9;s:4:\"nssf\";i:10;s:7:\"pension\";i:11;s:12:\"housing_levy\";i:12;s:4:\"helb\";i:13;s:14:\"taxable_income\";i:14;s:7:\"reliefs\";i:15;s:15:\"personal_relief\";i:16;s:16:\"insurance_relief\";i:17;s:13:\"pay_after_tax\";i:18;s:14:\"loan_repayment\";i:19;s:16:\"advance_recovery\";i:20;s:20:\"deductions_after_tax\";i:21;s:7:\"net_pay\";i:22;s:10:\"deductions\";i:23;s:8:\"overtime\";i:24;s:10:\"allowances\";i:25;s:9:\"bank_name\";i:26;s:14:\"account_number\";i:27;s:18:\"attendance_present\";i:28;s:17:\"attendance_absent\";i:29;s:13:\"days_in_month\";i:30;s:21:\"pwd_exemption_applied\";i:31;s:20:\"pwd_exemption_amount\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:3;O:26:\"App\\Models\\EmployeePayroll\":30:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:17:\"employee_payrolls\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:3:{s:5:\"month\";s:7:\"2026-08\";s:13:\"total_net_pay\";s:9:\"143486.00\";s:15:\"total_gross_pay\";s:9:\"200000.00\";}s:11:\"\0*\0original\";a:3:{s:5:\"month\";s:7:\"2026-08\";s:13:\"total_net_pay\";s:9:\"143486.00\";s:15:\"total_gross_pay\";s:9:\"200000.00\";}s:10:\"\0*\0changes\";a:0:{}s:8:\"\0*\0casts\";a:24:{s:10:\"allowances\";s:4:\"json\";s:10:\"deductions\";s:4:\"json\";s:8:\"overtime\";s:4:\"json\";s:7:\"reliefs\";s:4:\"json\";s:12:\"basic_salary\";s:5:\"float\";s:17:\"housing_allowance\";s:5:\"float\";s:9:\"gross_pay\";s:5:\"float\";s:4:\"paye\";s:5:\"float\";s:19:\"paye_before_reliefs\";s:5:\"float\";s:4:\"shif\";s:5:\"float\";s:4:\"nssf\";s:5:\"float\";s:7:\"pension\";s:5:\"float\";s:12:\"housing_levy\";s:5:\"float\";s:4:\"helb\";s:5:\"float\";s:14:\"taxable_income\";s:5:\"float\";s:15:\"personal_relief\";s:5:\"float\";s:16:\"insurance_relief\";s:5:\"float\";s:13:\"pay_after_tax\";s:5:\"float\";s:14:\"loan_repayment\";s:5:\"float\";s:16:\"advance_recovery\";s:5:\"float\";s:20:\"deductions_after_tax\";s:5:\"float\";s:7:\"net_pay\";s:5:\"float\";s:21:\"pwd_exemption_applied\";s:7:\"boolean\";s:20:\"pwd_exemption_amount\";s:5:\"float\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:32:{i:0;s:10:\"payroll_id\";i:1;s:11:\"employee_id\";i:2;s:26:\"employee_payment_detail_id\";i:3;s:12:\"basic_salary\";i:4;s:17:\"housing_allowance\";i:5;s:9:\"gross_pay\";i:6;s:4:\"paye\";i:7;s:19:\"paye_before_reliefs\";i:8;s:4:\"shif\";i:9;s:4:\"nssf\";i:10;s:7:\"pension\";i:11;s:12:\"housing_levy\";i:12;s:4:\"helb\";i:13;s:14:\"taxable_income\";i:14;s:7:\"reliefs\";i:15;s:15:\"personal_relief\";i:16;s:16:\"insurance_relief\";i:17;s:13:\"pay_after_tax\";i:18;s:14:\"loan_repayment\";i:19;s:16:\"advance_recovery\";i:20;s:20:\"deductions_after_tax\";i:21;s:7:\"net_pay\";i:22;s:10:\"deductions\";i:23;s:8:\"overtime\";i:24;s:10:\"allowances\";i:25;s:9:\"bank_name\";i:26;s:14:\"account_number\";i:27;s:18:\"attendance_present\";i:28;s:17:\"attendance_absent\";i:29;s:13:\"days_in_month\";i:30;s:21:\"pwd_exemption_applied\";i:31;s:20:\"pwd_exemption_amount\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1787324391),
('trends_EmployeePayroll_3_2026', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1769176932),
('trends_EmployeePayroll_4_2026', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1769176914),
('trends_LeaveRequest_2_2026', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1787324397),
('trends_LeaveRequest_3_2026', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1769176936),
('trends_LeaveRequest_4_2026', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1769176916),
('trends_Loan_2_2026', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1787324398),
('trends_Loan_3_2026', 'O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:0:{}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}', 1769176936);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaigns`
--

CREATE TABLE `campaigns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `utm_source` varchar(255) NOT NULL,
  `utm_medium` varchar(255) NOT NULL,
  `utm_campaign` varchar(255) NOT NULL,
  `target_url` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `has_survey` tinyint(1) NOT NULL DEFAULT 0,
  `survey_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`survey_config`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `client_business` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_submissions`
--

CREATE TABLE `contact_submissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `country` varchar(100) NOT NULL,
  `inquiry_type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `source` varchar(255) DEFAULT NULL COMMENT 'e.g., website, campaign',
  `utm_source` varchar(255) DEFAULT NULL,
  `utm_medium` varchar(255) DEFAULT NULL,
  `utm_campaign` varchar(255) DEFAULT NULL,
  `status` enum('new','contacted','qualified','closed') DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deductions`
--

CREATE TABLE `deductions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `calculation_basis` enum('basic_pay','gross_pay','cash_pay','taxable_pay','custom') NOT NULL,
  `computation_method` enum('fixed','rate','formula') NOT NULL DEFAULT 'fixed',
  `amount` decimal(15,2) DEFAULT NULL,
  `rate` decimal(5,2) DEFAULT NULL,
  `employer_rate` decimal(8,4) DEFAULT NULL,
  `formula` varchar(255) DEFAULT NULL,
  `actual_amount` tinyint(1) DEFAULT 0,
  `fraction_to_consider` enum('employee_only','employee_and_employer') NOT NULL,
  `limit` decimal(15,2) DEFAULT NULL,
  `employer_limit` decimal(15,2) DEFAULT NULL,
  `round_off` enum('round_off_up','round_off_down') NOT NULL,
  `decimal_places` tinyint(3) UNSIGNED NOT NULL DEFAULT 2,
  `is_statutory` tinyint(1) DEFAULT 0,
  `is_optional` tinyint(1) DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deductions`
--

INSERT INTO `deductions` (`id`, `business_id`, `location_id`, `name`, `slug`, `description`, `calculation_basis`, `computation_method`, `amount`, `rate`, `employer_rate`, `formula`, `actual_amount`, `fraction_to_consider`, `limit`, `employer_limit`, `round_off`, `decimal_places`, `is_statutory`, `is_optional`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 1, NULL, 'Sacco Contribution', 'sacco-contribution', 'Employee savings with Sacco', 'basic_pay', 'rate', NULL, 5.00, NULL, NULL, 0, 'employee_only', 10000.00, NULL, 'round_off_up', 2, 0, 1, 1, '2025-03-27 04:01:00', '2025-03-27 04:01:00'),
(3, 1, NULL, 'Employer Loan Repayment', 'employer-loan-repayment', 'Repayment of loan from employer', 'gross_pay', 'fixed', 20000.00, NULL, NULL, NULL, 0, 'employee_only', NULL, NULL, 'round_off_down', 2, 0, 1, 1, '2025-03-27 04:02:00', '2025-03-27 04:02:00'),
(4, 1, NULL, 'Union Dues', 'union-dues', 'Monthly contribution to employee union', 'taxable_pay', 'rate', NULL, 2.00, NULL, NULL, 0, 'employee_only', 5000.00, NULL, 'round_off_up', 2, 0, 1, 1, '2025-03-27 04:03:00', '2025-03-27 04:03:00'),
(5, 1, NULL, 'Welfare Fund', 'welfare-fund', 'Contribution to staff welfare activities', 'basic_pay', 'fixed', 500.00, NULL, NULL, NULL, 0, 'employee_only', NULL, NULL, 'round_off_down', 2, 0, 1, 1, '2025-03-27 04:04:00', '2025-03-27 04:04:00'),
(6, 1, NULL, 'Custom Loan Deduction', 'custom-loan-deduction', 'Custom loan with fringe benefit adjustment', 'gross_pay', 'formula', NULL, NULL, NULL, 'FringeBenefit(5%)', 0, 'employee_only', NULL, NULL, 'round_off_up', 2, 0, 1, 1, '2025-03-27 04:05:00', '2025-03-27 04:05:00'),
(7, 2, NULL, 'HELB Loan', 'helb-loan', 'HELB Loan', 'gross_pay', 'fixed', 4000.00, NULL, NULL, NULL, 1, 'employee_only', 0.00, NULL, 'round_off_up', 0, 0, 1, 9, '2026-02-24 11:23:05', '2026-02-24 11:23:05'),
(8, 2, NULL, 'pension', 'pension', 'pension', 'gross_pay', 'rate', NULL, 10.00, 6.0000, NULL, 1, 'employee_and_employer', 30000.00, 30000.00, 'round_off_up', 0, 0, 1, 9, '2026-03-20 11:13:09', '2026-03-20 11:18:05');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `business_id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(6, 1, 'Finance', 'finance', 'Handles budgeting, accounting, and financial planning', '2026-01-18 19:31:06', '2026-01-18 19:31:06'),
(7, 1, 'Customer Support', 'customer-support', 'Provides assistance and support to customers', '2026-01-18 19:31:06', '2026-01-18 19:31:06'),
(8, 1, 'Research & Development', 'rnd', 'Develops new products and improves existing services', '2026-01-18 19:31:06', '2026-01-18 19:31:06'),
(9, 1, 'Legal', 'legal', 'Manages legal affairs and ensures compliance', '2026-01-18 19:31:06', '2026-01-18 19:31:06'),
(10, 1, 'Procurement', 'procurement', 'Handles purchasing and vendor management', '2026-01-18 19:31:06', '2026-01-18 19:31:06'),
(11, 1, 'Quality Assurance', 'quality-assurance', 'Ensures products/services meet quality standards', '2026-01-18 19:31:06', '2026-01-18 19:31:06'),
(12, 1, 'Logistics', 'logistics', 'Manages shipping, warehousing, and delivery operations', '2026-01-18 19:31:06', '2026-01-18 19:31:06'),
(13, 1, 'Administration', 'administration', 'Handles administrative tasks and office management', '2026-01-18 19:31:06', '2026-01-18 19:31:06'),
(14, 1, 'IT Security', 'it-security', 'Ensures cybersecurity and protection of company data', '2026-01-18 19:31:06', '2026-01-18 19:31:06'),
(15, 1, 'Training & Development', 'training-development', 'Conducts employee training programs', '2026-01-18 19:31:06', '2026-01-18 19:31:06'),
(16, 2, 'finance', 'finance-1', 'Short Department Description...', '2026-01-20 19:20:06', '2026-01-20 19:20:06'),
(17, 2, 'Human Resource', 'human-resource', 'resource', '2026-01-20 19:20:51', '2026-01-20 19:20:51'),
(18, 2, 'Sales', 'sales', 'Sales', '2026-01-20 19:21:15', '2026-01-20 19:21:15'),
(19, 2, 'Operations', 'operations', 'Operations', '2026-01-20 19:21:35', '2026-01-20 19:21:35'),
(20, 2, 'ICT', 'ict', 'ICT', '2026-01-20 19:22:07', '2026-01-20 19:22:07'),
(21, 2, 'Supply Chain', 'supply-chain', 'Supply Chain', '2026-01-20 19:22:25', '2026-01-20 19:22:25'),
(22, 2, 'Marketing', 'marketing', 'Marketing', '2026-01-20 19:22:40', '2026-01-20 19:22:40'),
(23, 2, 'Administration', 'administration-1', 'Administration', '2026-01-20 19:22:54', '2026-01-20 19:22:54'),
(24, 2, 'Logistics', 'logistics-1', 'Logistics', '2026-01-20 19:23:07', '2026-01-20 19:23:07'),
(25, 2, 'MIS', 'mis', 'MIS', '2026-01-20 19:24:12', '2026-01-20 19:24:12');

-- --------------------------------------------------------

--
-- Table structure for table `emergency_contacts`
--

CREATE TABLE `emergency_contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `relationship` varchar(255) NOT NULL,
  `contact_address` varchar(255) NOT NULL,
  `telephone` varchar(255) NOT NULL,
  `additional_instructions` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `team_id` bigint(20) UNSIGNED DEFAULT NULL,
  `manager_id` bigint(20) UNSIGNED DEFAULT NULL,
  `manager_override` tinyint(1) NOT NULL DEFAULT 0,
  `organogram_role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_code` varchar(255) NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `alternate_phone` varchar(255) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `place_of_birth` varchar(255) DEFAULT NULL,
  `marital_status` enum('single','married','divorced','widowed') NOT NULL,
  `national_id` varchar(255) NOT NULL,
  `place_of_issue` varchar(255) DEFAULT NULL,
  `tax_no` varchar(255) NOT NULL,
  `nhif_no` varchar(255) DEFAULT NULL,
  `nssf_no` varchar(255) DEFAULT NULL,
  `passport_no` varchar(255) DEFAULT NULL,
  `passport_issue_date` date DEFAULT NULL,
  `passport_expiry_date` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `permanent_address` varchar(255) DEFAULT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `is_exempt_from_payroll` tinyint(1) NOT NULL DEFAULT 0,
  `resident_status` varchar(50) DEFAULT NULL,
  `kra_employee_status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `registered_device_mac` varchar(64) DEFAULT NULL,
  `is_overtime_eligible` tinyint(1) NOT NULL DEFAULT 1,
  `overtime_rate_regular` decimal(5,2) DEFAULT NULL,
  `overtime_rate_holiday` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `business_id`, `is_active`, `location_id`, `department_id`, `team_id`, `manager_id`, `manager_override`, `organogram_role_id`, `employee_code`, `gender`, `alternate_phone`, `date_of_birth`, `place_of_birth`, `marital_status`, `national_id`, `place_of_issue`, `tax_no`, `nhif_no`, `nssf_no`, `passport_no`, `passport_issue_date`, `passport_expiry_date`, `address`, `permanent_address`, `blood_group`, `is_exempt_from_payroll`, `resident_status`, `kra_employee_status`, `created_at`, `updated_at`, `registered_device_mac`, `is_overtime_eligible`, `overtime_rate_regular`, `overtime_rate_holiday`) VALUES
(11, 17, 2, 1, 1, 17, NULL, NULL, 0, NULL, '090000', 'male', '0727086937', '1997-03-03', 'nairobi', 'single', '11143498', NULL, 'A001625190Z', '127022', '24556677', NULL, NULL, NULL, 'juja, kalimoni', 'Home River Street, 872', 'AB+', 0, 'Resident', 'Primary Employee', '2026-01-22 10:26:40', '2026-01-22 10:26:40', NULL, 1, NULL, NULL),
(12, 19, 2, 1, 1, 16, NULL, NULL, 0, NULL, '090001', 'female', '0711234567', '2000-07-12', 'nairobi', 'married', '21143498', NULL, 'A001625190E', '1270345', '24557456', NULL, NULL, NULL, 'juja, kalimoni', 'Home River Street, 872', 'AB+', 0, 'Resident', 'Primary Employee', '2026-01-22 10:59:07', '2026-01-22 10:59:07', NULL, 1, NULL, NULL),
(13, 20, 2, 1, 2, 18, NULL, NULL, 0, NULL, '090002', 'female', '0722345678', '2004-02-06', 'nairobi', 'married', '1434983', NULL, 'A001625190L', '1270345', '24557345', NULL, NULL, NULL, 'juja, kalimoni', 'pioneer estate buruburu', 'AB+', 0, 'Resident', 'Primary Employee', '2026-01-22 11:02:57', '2026-03-02 12:00:47', NULL, 1, NULL, NULL),
(14, 21, 2, 1, 1, 19, NULL, NULL, 0, NULL, '090003', 'female', '0733456789', '1988-07-08', 'nairobi', 'married', '1143498', NULL, 'A001625190P', '3247022', '84557', NULL, NULL, NULL, 'juja, kalimoni', 'kroad', 'AB+', 0, 'Resident', 'Primary Employee', '2026-01-22 11:06:24', '2026-01-22 11:06:24', NULL, 1, NULL, NULL),
(15, 24, 2, 1, NULL, 21, NULL, NULL, 0, NULL, 'A233', 'female', '0769212978', '1998-05-03', 'nanyuki', 'single', '32456789', NULL, 'AWEX34HSKK67', '45678399', 'p45667849', NULL, NULL, NULL, 'nairobi', 'Nairobi', 'A+', 0, 'Resident', 'Primary Employee', '2026-05-03 06:22:16', '2026-05-03 07:26:45', NULL, 1, NULL, NULL),
(16, 25, 2, 1, NULL, 17, NULL, NULL, 0, NULL, 'A234', 'female', '0769212978', '2020-12-12', 'nanyuki', 'married', '82456789', NULL, 'EX34HSKK67', '5678399', 'J45667849', NULL, NULL, NULL, 'nairobi', 'Nairobi', 'A+', 0, 'Resident', 'Primary Employee', '2026-05-03 07:29:31', '2026-05-03 07:29:31', NULL, 1, NULL, NULL),
(17, 26, 2, 1, 1, 16, NULL, NULL, 0, NULL, 'EMP0011', 'male', '254723456789', '1990-05-15', 'Nairobi', 'single', '21235678', 'Nairobi Passport Office', 'KRA10345', 'NHIF06789', 'NSSF68765', '9P123456', '2020-01-01', '2030-01-01', 'P.O. Box 123, Nairobi', 'P.O. Box 456, Nairobi', 'A+', 0, 'Resident', 'Primary Employee', '2026-05-04 06:28:48', '2026-05-04 06:28:48', NULL, 1, NULL, NULL),
(18, 27, 2, 1, 1, 16, NULL, NULL, 0, NULL, 'EMP002', 'male', '254723406790', '1990-05-16', 'Nairobi', 'single', '12345679', 'Nairobi Passport Office', 'KRA92346', 'NHIF46790', 'NSSF90766', '8P123457', '2020-01-02', '2030-01-02', 'P.O. Box 123, Nairobi', 'P.O. Box 456, Nairobi', 'A+', 1, 'Resident', 'Primary Employee', '2026-05-04 06:28:55', '2026-05-04 06:28:55', NULL, 1, NULL, NULL),
(25, 34, 2, 1, 1, 16, NULL, NULL, 0, NULL, 'EMP1001', 'female', '254722000001', '1988-03-10', 'Mombasa', 'married', '30000001', 'Mombasa Passport Office', 'KRA30000001', 'NHIF30001', 'NSSF30001', 'A1000001', '2019-05-01', '2029-05-01', 'P.O. Box 1, Nairobi', 'P.O. Box 2, Nairobi', 'B+', 0, 'Resident', 'Primary Employee', '2026-05-04 07:41:14', '2026-05-04 07:41:14', NULL, 1, NULL, NULL),
(26, 35, 2, 1, 1, 16, NULL, NULL, 0, NULL, 'EMP1002', 'male', '254722000002', '1992-07-22', 'Kisumu', 'single', '30000002', 'Kisumu Passport Office', 'KRA30000002', 'NHIF30002', 'NSSF30002', 'B1000002', '2021-03-15', '2031-03-15', 'P.O. Box 3, Nairobi', 'P.O. Box 4, Nairobi', 'O+', 0, 'Resident', 'Primary Employee', '2026-05-04 07:41:17', '2026-05-04 07:41:17', NULL, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_allowances`
--

CREATE TABLE `employee_allowances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `allowance_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_contact_details`
--

CREATE TABLE `employee_contact_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `work_phone` varchar(255) DEFAULT NULL,
  `work_phone_code` varchar(255) DEFAULT NULL,
  `work_phone_country` varchar(255) DEFAULT NULL,
  `work_email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL,
  `email_signature` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_contract_actions`
--

CREATE TABLE `employee_contract_actions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `action_type` enum('termination','reminder','suspension') NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `action_date` date NOT NULL,
  `status` enum('active','reversed','sent') NOT NULL,
  `issued_by_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_deductions`
--

CREATE TABLE `employee_deductions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `deduction_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_departments`
--

CREATE TABLE `employee_departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_family_members`
--

CREATE TABLE `employee_family_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `relationship` varchar(255) NOT NULL,
  `date_of_birth` date NOT NULL,
  `contact_address` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_payment_details`
--

CREATE TABLE `employee_payment_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `basic_salary` decimal(12,2) NOT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `payment_type` enum('salary','hourly') NOT NULL DEFAULT 'salary',
  `currency` enum('KES','USD','TZS','EUR') NOT NULL,
  `payment_mode` enum('bank','cash','cheque','mpesa') NOT NULL,
  `account_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_code` varchar(255) DEFAULT NULL,
  `bank_branch` varchar(255) DEFAULT NULL,
  `bank_branch_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_consultant` tinyint(1) NOT NULL DEFAULT 0,
  `wht_payment_type` varchar(255) DEFAULT NULL,
  `wht_residency` varchar(255) NOT NULL DEFAULT 'resident',
  `wht_pin` varchar(255) DEFAULT NULL,
  `consultant_shif_covered` tinyint(1) NOT NULL DEFAULT 0,
  `consultant_shif_basis` varchar(255) DEFAULT NULL,
  `consultant_shif_fixed_amount` decimal(12,2) DEFAULT NULL,
  `consultant_nssf_covered` tinyint(1) NOT NULL DEFAULT 0,
  `consultant_nssf_basis` varchar(255) DEFAULT NULL,
  `consultant_nssf_fixed_amount` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_payment_details`
--

INSERT INTO `employee_payment_details` (`id`, `employee_id`, `basic_salary`, `hourly_rate`, `payment_type`, `currency`, `payment_mode`, `account_name`, `account_number`, `bank_name`, `bank_code`, `bank_branch`, `bank_branch_code`, `created_at`, `updated_at`, `is_consultant`, `wht_payment_type`, `wht_residency`, `wht_pin`, `consultant_shif_covered`, `consultant_shif_basis`, `consultant_shif_fixed_amount`, `consultant_nssf_covered`, `consultant_nssf_basis`, `consultant_nssf_fixed_amount`) VALUES
(1, 11, 200000.00, 0.00, 'salary', 'KES', 'bank', 'john mwangi', '4660390016', 'NCBA', '07', 'buruburu', '209', '2026-01-22 10:26:40', '2026-02-24 09:45:18', 0, NULL, 'resident', NULL, 0, NULL, NULL, 0, NULL, NULL),
(2, 12, 30000.00, 0.00, 'salary', 'KES', 'bank', 'aisha ali', '4760390016', 'equity', '07', 'buruburu', '209', '2026-01-22 10:59:07', '2026-02-24 08:09:35', 0, NULL, 'resident', NULL, 0, NULL, NULL, 0, NULL, NULL),
(3, 13, 100000.00, 0.00, 'salary', 'KES', 'bank', 'Otieno peter', '60390016', 'equity', '07', 'juja', '209', '2026-01-22 11:02:57', '2026-03-02 12:00:47', 0, NULL, 'resident', NULL, 0, NULL, NULL, 0, NULL, NULL),
(4, 14, 6746.03, 0.00, 'salary', 'USD', 'bank', 'grace', '860390016', 'equity', '07', 'juja', '209', '2026-01-22 11:06:24', '2026-03-20 11:26:22', 0, NULL, 'resident', NULL, 0, NULL, NULL, 0, NULL, NULL),
(5, 15, 20000.00, 0.00, 'salary', 'KES', 'bank', 'Awrtqwfewor.wegfewihg', '123546576879', 'Equity', '009', 'juja', '022', '2026-05-03 06:22:16', '2026-05-03 06:22:16', 0, NULL, 'resident', NULL, 0, NULL, NULL, 0, NULL, NULL),
(6, 16, 210.00, 0.00, 'salary', 'KES', 'bank', 'rtqwfewor.wegfewihg', '546576879', 'Equity', '009', 'juja', '022', '2026-05-03 07:29:31', '2026-05-03 07:29:31', 0, NULL, 'resident', NULL, 0, NULL, NULL, 0, NULL, NULL),
(13, 25, 55000.00, NULL, 'salary', 'KES', 'bank', 'Alice Wanjiru', '9876543001', 'Equity Bank', 'EQ001', 'Nairobi Main', '001', '2026-05-04 07:41:14', '2026-05-04 07:41:14', 0, NULL, 'resident', NULL, 0, NULL, NULL, 0, NULL, NULL),
(14, 26, 48000.00, NULL, 'salary', 'KES', 'bank', 'Brian Otieno', '9876543002', 'Equity Bank', 'EQ002', 'Nairobi Main', '002', '2026-05-04 07:41:17', '2026-05-04 07:41:17', 0, NULL, 'resident', NULL, 0, NULL, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_payrolls`
--

CREATE TABLE `employee_payrolls` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payroll_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `employee_payment_detail_id` bigint(20) UNSIGNED DEFAULT NULL,
  `basic_salary` decimal(15,2) DEFAULT NULL,
  `housing_allowance` decimal(15,2) DEFAULT NULL,
  `gross_pay` decimal(15,2) DEFAULT NULL,
  `paye` decimal(15,2) DEFAULT NULL,
  `paye_before_reliefs` decimal(15,2) DEFAULT NULL,
  `shif` decimal(15,2) DEFAULT NULL,
  `nhif` int(11) DEFAULT NULL,
  `nssf` decimal(15,2) DEFAULT NULL,
  `pension` decimal(15,2) DEFAULT NULL,
  `housing_levy` decimal(15,2) DEFAULT NULL,
  `helb` decimal(15,2) DEFAULT NULL,
  `taxable_income` decimal(15,2) DEFAULT NULL,
  `reliefs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reliefs`)),
  `personal_relief` decimal(15,2) DEFAULT NULL,
  `insurance_relief` decimal(15,2) DEFAULT NULL,
  `pwd_exemption_applied` tinyint(1) NOT NULL DEFAULT 0,
  `pwd_exemption_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `pay_after_tax` decimal(15,2) DEFAULT NULL,
  `loan_repayment` decimal(15,2) DEFAULT NULL,
  `advance_recovery` decimal(15,2) DEFAULT NULL,
  `deductions_after_tax` decimal(15,2) DEFAULT NULL,
  `net_pay` decimal(15,2) DEFAULT NULL,
  `employee_currency` varchar(10) NOT NULL DEFAULT 'KES',
  `tax_currency` varchar(10) NOT NULL DEFAULT 'KES',
  `exchange_rate` decimal(15,6) NOT NULL DEFAULT 1.000000,
  `basic_salary_orig` decimal(15,2) DEFAULT NULL,
  `gross_pay_orig` decimal(15,2) DEFAULT NULL,
  `net_pay_orig` decimal(15,2) DEFAULT NULL,
  `deductions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deductions`)),
  `allowances` longtext DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `attendance_present` int(11) DEFAULT NULL,
  `attendance_absent` int(11) DEFAULT NULL,
  `days_in_month` int(11) DEFAULT NULL,
  `is_consultant` tinyint(1) NOT NULL DEFAULT 0,
  `wht_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `wht_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `overtime` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`overtime`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_payrolls`
--

INSERT INTO `employee_payrolls` (`id`, `payroll_id`, `employee_id`, `employee_payment_detail_id`, `basic_salary`, `housing_allowance`, `gross_pay`, `paye`, `paye_before_reliefs`, `shif`, `nhif`, `nssf`, `pension`, `housing_levy`, `helb`, `taxable_income`, `reliefs`, `personal_relief`, `insurance_relief`, `pwd_exemption_applied`, `pwd_exemption_amount`, `pay_after_tax`, `loan_repayment`, `advance_recovery`, `deductions_after_tax`, `net_pay`, `employee_currency`, `tax_currency`, `exchange_rate`, `basic_salary_orig`, `gross_pay_orig`, `net_pay_orig`, `deductions`, `allowances`, `bank_name`, `account_number`, `attendance_present`, `attendance_absent`, `days_in_month`, `is_consultant`, `wht_amount`, `wht_rate`, `overtime`, `created_at`, `updated_at`) VALUES
(1, 5, 11, 1, 200000.00, NULL, 200000.00, 51549.35, 51549.35, 5500.00, NULL, 2280.00, NULL, 3000.00, 0.00, 189220.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"Personal Relief\\\",\\\"amount\\\":\\\"2400.000\\\",\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\"}}\"', 2400.00, 0.00, 0, 0.00, 148450.65, 0.00, 0.00, 10780.65, 137670.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'NCBA', '4660390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-02-21 20:05:09', '2026-02-21 20:05:09'),
(2, 5, 12, 2, 30000.00, NULL, 30000.00, 3131.25, 3131.25, 825.00, NULL, 1800.00, NULL, 450.00, 0.00, 26925.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"Personal Relief\\\",\\\"amount\\\":\\\"2400.000\\\",\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\"}}\"', 2400.00, 0.00, 0, 0.00, 26868.75, 0.00, 0.00, 3075.75, 23793.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '4760390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-02-21 20:05:09', '2026-02-21 20:05:09'),
(3, 5, 13, 3, 100000.00, NULL, 100000.00, 22824.35, 22824.35, 2750.00, NULL, 2280.00, NULL, 1500.00, 0.00, 93470.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"Personal Relief\\\",\\\"amount\\\":\\\"2400.000\\\",\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\"}}\"', 2400.00, 0.00, 0, 0.00, 77175.65, 0.00, 0.00, 6530.65, 70645.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '60390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-02-21 20:05:09', '2026-02-21 20:05:09'),
(4, 5, 14, 4, 50000.00, NULL, 50000.00, 8461.85, 8461.85, 1375.00, NULL, 2280.00, NULL, 750.00, 0.00, 45595.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"Personal Relief\\\",\\\"amount\\\":\\\"2400.000\\\",\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\"}}\"', 2400.00, 0.00, 0, 0.00, 41538.15, 0.00, 0.00, 4405.15, 37133.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '860390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-02-21 20:05:09', '2026-02-21 20:05:09'),
(5, 6, 11, 1, 200000.00, NULL, 200000.00, 50289.35, 50289.35, 5500.00, NULL, 6480.00, NULL, 3000.00, 0.00, 185020.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"Personal Relief\\\",\\\"amount\\\":\\\"2400.000\\\",\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\"}}\"', 2400.00, 0.00, 0, 0.00, 149710.65, 0.00, 0.00, 14980.65, 134730.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'NCBA', '4660390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-02-21 21:05:08', '2026-02-21 21:05:08'),
(6, 6, 12, 2, 30000.00, NULL, 30000.00, 3446.25, 3446.25, 825.00, NULL, 540.00, NULL, 450.00, 0.00, 28185.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"Personal Relief\\\",\\\"amount\\\":\\\"2400.000\\\",\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\"}}\"', 2400.00, 0.00, 0, 0.00, 26553.75, 0.00, 0.00, 1815.75, 24738.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '4760390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-02-21 21:05:08', '2026-02-21 21:05:08'),
(7, 6, 13, 3, 100000.00, NULL, 100000.00, 23166.35, 23166.35, 2750.00, NULL, 1140.00, NULL, 1500.00, 0.00, 94610.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"Personal Relief\\\",\\\"amount\\\":\\\"2400.000\\\",\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\"}}\"', 2400.00, 0.00, 0, 0.00, 76833.65, 0.00, 0.00, 5390.65, 71443.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '60390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-02-21 21:05:08', '2026-02-21 21:05:08'),
(8, 6, 14, 4, 50000.00, NULL, 50000.00, 8983.85, 8983.85, 1375.00, NULL, 540.00, NULL, 750.00, 0.00, 47335.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"Personal Relief\\\",\\\"amount\\\":\\\"2400.000\\\",\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\"}}\"', 2400.00, 0.00, 0, 0.00, 41016.15, 0.00, 0.00, 2665.15, 38351.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '860390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-02-21 21:05:08', '2026-02-21 21:05:08'),
(9, 7, 11, 1, 200000.00, NULL, 200000.00, 51549.35, 51549.35, 5500.00, NULL, 2280.00, NULL, 3000.00, 0.00, 189220.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"Personal Relief\\\",\\\"amount\\\":\\\"2400.000\\\",\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\"}}\"', 2400.00, 0.00, 0, 0.00, 148450.65, 0.00, 0.00, 10780.65, 137670.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'NCBA', '4660390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-02-22 19:36:15', '2026-02-22 19:36:15'),
(10, 7, 12, 2, 30000.00, NULL, 30000.00, 3131.25, 3131.25, 825.00, NULL, 1800.00, NULL, 450.00, 0.00, 26925.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"Personal Relief\\\",\\\"amount\\\":\\\"2400.000\\\",\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\"}}\"', 2400.00, 0.00, 0, 0.00, 26868.75, 0.00, 0.00, 3075.75, 23793.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '4760390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-02-22 19:36:15', '2026-02-22 19:36:15'),
(11, 7, 13, 3, 100000.00, NULL, 100000.00, 22824.35, 22824.35, 2750.00, NULL, 2280.00, NULL, 1500.00, 0.00, 93470.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"Personal Relief\\\",\\\"amount\\\":\\\"2400.000\\\",\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\"}}\"', 2400.00, 0.00, 0, 0.00, 77175.65, 0.00, 0.00, 6530.65, 70645.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '60390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-02-22 19:36:15', '2026-02-22 19:36:15'),
(12, 7, 14, 4, 50000.00, NULL, 50000.00, 8461.85, 8461.85, 1375.00, NULL, 2280.00, NULL, 750.00, 0.00, 45595.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"Personal Relief\\\",\\\"amount\\\":\\\"2400.000\\\",\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\"}}\"', 2400.00, 0.00, 0, 0.00, 41538.15, 0.00, 0.00, 4405.15, 37133.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '860390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-02-22 19:36:15', '2026-02-22 19:36:15'),
(13, 8, 11, 1, 200000.00, NULL, 200000.00, 51549.35, 51549.35, 5500.00, NULL, 2280.00, NULL, 3000.00, 0.00, 189220.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"Personal Relief\\\",\\\"amount\\\":\\\"2400.000\\\",\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\"}}\"', 2400.00, 0.00, 0, 0.00, 148450.65, 0.00, 0.00, 10780.65, 137670.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'NCBA', '4660390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-03-02 12:02:22', '2026-03-02 12:02:22'),
(14, 8, 12, 2, 30000.00, NULL, 30000.00, 0.00, 0.00, 825.00, NULL, 1800.00, NULL, 450.00, 0.00, 0.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"Personal Relief\\\",\\\"amount\\\":\\\"2400.000\\\",\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\"}}\"', 2400.00, 0.00, 1, 26925.00, 30000.00, 0.00, 0.00, 30000.00, 0.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '4760390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-03-02 12:02:22', '2026-03-02 12:02:22'),
(15, 8, 13, 3, 100000.00, NULL, 100000.00, 22824.35, 22824.35, 2750.00, NULL, 2280.00, NULL, 1500.00, 0.00, 93470.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"Personal Relief\\\",\\\"amount\\\":\\\"2400.000\\\",\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\"}}\"', 2400.00, 0.00, 0, 0.00, 77175.65, 0.00, 0.00, 6530.65, 70645.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '60390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-03-02 12:02:22', '2026-03-02 12:02:22'),
(16, 8, 14, 4, 849999.78, NULL, 849999.78, 241589.52, 243989.52, 23375.00, NULL, 2280.00, NULL, 12750.00, 0.00, 804874.77, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"personal relief\\\",\\\"amount\\\":2400,\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\",\\\"display_amount\\\":2400}}\"', 2400.00, 0.00, 0, 0.00, 608410.26, 0.00, 0.00, 123405.26, 485005.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"{\\\"pension\\\":{\\\"name\\\":\\\"pension\\\",\\\"amount\\\":84999.98,\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"after_tax\\\"},\\\"0\\\":{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}}\"', '\"[]\"', 'equity', '860390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-03-02 12:02:22', '2026-03-20 11:27:23'),
(17, 9, 11, 1, 200000.00, NULL, 200000.00, 43149.35, 45549.35, 5500.00, NULL, 2280.00, NULL, 3000.00, 0.00, 169220.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"personal relief\\\",\\\"amount\\\":2400,\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\",\\\"display_amount\\\":2400}}\"', 2400.00, 0.00, 0, 0.00, 156850.65, 0.00, 0.00, 30780.65, 126070.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"{\\\"pension\\\":{\\\"name\\\":\\\"pension\\\",\\\"amount\\\":20000,\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"after_tax\\\"},\\\"0\\\":{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}}\"', '\"[]\"', 'NCBA', '4660390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-04-25 05:47:16', '2026-04-25 05:47:16'),
(18, 9, 12, 2, 30000.00, NULL, 30000.00, 0.00, 0.00, 825.00, NULL, 1800.00, NULL, 450.00, 0.00, 0.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"personal relief\\\",\\\"amount\\\":2400,\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\",\\\"display_amount\\\":2400}}\"', 2400.00, 0.00, 1, 26925.00, 30000.00, 0.00, 0.00, 3075.00, 26925.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '4760390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-04-25 05:47:16', '2026-04-25 05:47:16'),
(19, 9, 13, 3, 100000.00, NULL, 100000.00, 20424.35, 22824.35, 2750.00, NULL, 2280.00, NULL, 1500.00, 0.00, 93470.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"personal relief\\\",\\\"amount\\\":2400,\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\",\\\"display_amount\\\":2400}}\"', 2400.00, 0.00, 0, 0.00, 79575.65, 0.00, 0.00, 6530.65, 73045.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '60390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-04-25 05:47:16', '2026-04-25 05:47:16'),
(20, 9, 14, 4, 849999.78, NULL, 849999.78, 243941.52, 246341.52, 23375.00, NULL, 2280.00, NULL, 12750.00, 0.00, 811594.78, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"personal relief\\\",\\\"amount\\\":2400,\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\",\\\"display_amount\\\":2400}}\"', 2400.00, 0.00, 0, 0.00, 606058.26, 0.00, 0.00, 38405.26, 567653.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '860390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-04-25 05:47:16', '2026-04-25 05:47:16'),
(21, 10, 13, 3, 100000.00, NULL, 100000.00, 20424.35, 22824.35, 2750.00, NULL, 2280.00, NULL, 1500.00, 0.00, 93470.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"personal relief\\\",\\\"amount\\\":2400,\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\",\\\"display_amount\\\":2400}}\"', 2400.00, 0.00, 0, 0.00, 79575.65, 0.00, 0.00, 6530.65, 73045.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '60390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-08-11 12:11:45', '2026-08-11 12:11:45'),
(22, 11, 13, 3, 100000.00, NULL, 100000.00, 19308.35, 21708.35, 2750.00, NULL, 6000.00, NULL, 1500.00, 0.00, 89750.00, '\"{\\\"personal-relief\\\":{\\\"name\\\":\\\"personal relief\\\",\\\"amount\\\":2400,\\\"is_taxable\\\":false,\\\"tax_application\\\":\\\"before_tax\\\",\\\"display_amount\\\":2400}}\"', 2400.00, 0.00, 0, 0.00, 80691.65, 0.00, 0.00, 10250.65, 70441.00, 'KES', 'KES', 1.000000, NULL, NULL, NULL, '\"[{\\\"name\\\":\\\"Absenteeism Charge\\\",\\\"amount\\\":0}]\"', '\"[]\"', 'equity', '60390016', 30, 0, 30, 0, 0.00, 0.00, '\"{\\\"amount\\\":0}\"', '2026-08-12 06:52:33', '2026-08-12 06:52:33');

-- --------------------------------------------------------

--
-- Table structure for table `employee_payroll_details`
--

CREATE TABLE `employee_payroll_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED DEFAULT NULL,
  `has_insurance` tinyint(1) DEFAULT 0,
  `insurance_premium` decimal(15,2) DEFAULT NULL,
  `has_mortgage` tinyint(1) DEFAULT 0,
  `mortgage_interest` decimal(15,2) DEFAULT NULL,
  `has_hosp` tinyint(1) DEFAULT 0,
  `hosp_deposit` decimal(15,2) DEFAULT NULL,
  `has_helb` tinyint(1) DEFAULT 0,
  `has_disability_exemption` tinyint(1) DEFAULT 0,
  `pwd_certificate_no` varchar(255) DEFAULT NULL,
  `pwd_ncpwd_membership_no` varchar(255) DEFAULT NULL,
  `pwd_exemption_limit` decimal(15,2) NOT NULL DEFAULT 150000.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employee_payroll_details`
--

INSERT INTO `employee_payroll_details` (`id`, `employee_id`, `business_id`, `has_insurance`, `insurance_premium`, `has_mortgage`, `mortgage_interest`, `has_hosp`, `hosp_deposit`, `has_helb`, `has_disability_exemption`, `pwd_certificate_no`, `pwd_ncpwd_membership_no`, `pwd_exemption_limit`, `created_at`, `updated_at`) VALUES
(1, 12, NULL, 0, NULL, 0, NULL, 0, NULL, 0, 1, 'PWD-1234', 'PWD-045', 150000.00, '2026-02-24 08:09:35', '2026-02-24 08:09:35'),
(2, 11, NULL, 0, NULL, 0, NULL, 0, NULL, 0, 0, NULL, NULL, 150000.00, '2026-02-24 09:45:18', '2026-02-24 09:45:18'),
(3, 13, NULL, 0, NULL, 0, NULL, 0, NULL, 0, 0, NULL, NULL, 150000.00, '2026-03-02 12:00:47', '2026-03-02 12:00:47'),
(4, 14, NULL, 0, NULL, 0, NULL, 0, NULL, 0, 0, NULL, NULL, 150000.00, '2026-03-20 11:26:22', '2026-03-20 11:26:22'),
(5, 15, 2, 0, NULL, 0, NULL, 0, NULL, 0, 0, NULL, NULL, 150000.00, '2026-05-03 06:22:16', '2026-05-03 06:22:16'),
(6, 16, 2, 0, NULL, 0, NULL, 0, NULL, 0, 0, NULL, NULL, 150000.00, '2026-05-03 07:29:31', '2026-05-03 07:29:31');

-- --------------------------------------------------------

--
-- Table structure for table `employee_payroll_settings`
--

CREATE TABLE `employee_payroll_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `payroll_period` varchar(255) NOT NULL,
  `basic_salary` decimal(12,2) DEFAULT NULL,
  `allowances` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowances`)),
  `deductions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deductions`)),
  `reliefs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reliefs`)),
  `overtime` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`overtime`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_reliefs`
--

CREATE TABLE `employee_reliefs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `relief_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_task`
--

CREATE TABLE `employee_task` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employment_details`
--

CREATE TABLE `employment_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `job_category_id` bigint(20) UNSIGNED NOT NULL,
  `shift_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employment_date` date NOT NULL,
  `probation_end_date` date DEFAULT NULL,
  `second_probation_end_date` date DEFAULT NULL,
  `contract_end_date` date DEFAULT NULL,
  `retirement_date` date DEFAULT NULL,
  `employment_term` enum('contract','fulltime','permanent','Consultant','internship','locum') DEFAULT NULL,
  `job_description` text DEFAULT NULL,
  `license_reg_number` varchar(255) DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','terminated','suspended') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employment_details`
--

INSERT INTO `employment_details` (`id`, `employee_id`, `department_id`, `job_category_id`, `shift_id`, `employment_date`, `probation_end_date`, `second_probation_end_date`, `contract_end_date`, `retirement_date`, `employment_term`, `job_description`, `license_reg_number`, `license_expiry_date`, `created_at`, `updated_at`, `status`) VALUES
(1, 11, 17, 1, NULL, '2025-04-02', NULL, NULL, NULL, NULL, 'contract', NULL, NULL, NULL, '2026-01-22 10:26:40', '2026-01-22 10:26:40', 'active'),
(2, 12, 16, 2, NULL, '2023-02-01', NULL, NULL, NULL, NULL, 'contract', NULL, NULL, NULL, '2026-01-22 10:59:07', '2026-01-22 10:59:07', 'active'),
(3, 13, 18, 3, NULL, '2025-06-04', NULL, NULL, NULL, NULL, 'contract', NULL, NULL, NULL, '2026-01-22 11:02:57', '2026-01-22 11:02:57', 'active'),
(4, 14, 19, 4, NULL, '2025-10-08', NULL, NULL, NULL, NULL, 'contract', NULL, NULL, NULL, '2026-01-22 11:06:24', '2026-01-22 11:06:24', 'active'),
(5, 15, 21, 1, NULL, '2026-05-03', NULL, NULL, NULL, NULL, 'contract', NULL, NULL, NULL, '2026-05-03 06:22:16', '2026-05-03 06:22:16', 'active'),
(6, 16, 17, 2, NULL, '2025-01-29', NULL, NULL, NULL, NULL, 'contract', NULL, NULL, NULL, '2026-05-03 07:29:31', '2026-05-03 07:29:31', 'active'),
(13, 25, 16, 1, NULL, '2022-06-01', NULL, NULL, NULL, NULL, 'permanent', 'Oversee HR operations and staff welfare', NULL, NULL, '2026-05-04 07:41:14', '2026-05-04 07:41:14', 'active'),
(14, 26, 16, 1, NULL, '2023-03-01', NULL, NULL, NULL, NULL, 'permanent', 'Support HR policies and employee records', NULL, NULL, '2026-05-04 07:41:17', '2026-05-04 07:41:17', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `experiences`
--

CREATE TABLE `experiences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `applicant_id` bigint(20) UNSIGNED NOT NULL,
  `company` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `years` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(255) NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `is_working_day` tinyint(1) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `industries`
--

CREATE TABLE `industries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `industries`
--

INSERT INTO `industries` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'Information Technology', 'information-technology', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(2, 'Healthcare', 'healthcare', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(3, 'Finance', 'finance', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(4, 'Education', 'education', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(5, 'Retail', 'retail', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(6, 'Manufacturing', 'manufacturing', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(7, 'Construction', 'construction', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(8, 'Real Estate', 'real-estate', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(9, 'Hospitality', 'hospitality', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(10, 'Entertainment', 'entertainment', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(11, 'Automotive', 'automotive', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(12, 'Telecommunications', 'telecommunications', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(13, 'Energy', 'energy', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(14, 'Agriculture', 'agriculture', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(15, 'Aerospace', 'aerospace', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(16, 'Logistics and Supply Chain', 'logistics-and-supply-chain', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(17, 'Food and Beverage', 'food-and-beverage', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(18, 'Fashion', 'fashion', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(19, 'Media and Publishing', 'media-and-publishing', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(20, 'Pharmaceuticals', 'pharmaceuticals', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(21, 'Sports and Recreation', 'sports-and-recreation', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(22, 'Legal Services', 'legal-services', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(23, 'Consulting', 'consulting', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(24, 'Environmental Services', 'environmental-services', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(25, 'Transportation', 'transportation', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(26, 'Government and Public Administration', 'government-and-public-administration', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(27, 'Non-Profit and Social Services', 'non-profit-and-social-services', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(28, 'Marketing and Advertising', 'marketing-and-advertising', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(29, 'E-Commerce', 'e-commerce', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(30, 'Beauty and Personal Care', 'beauty-and-personal-care', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(31, 'Insurance', 'insurance', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(32, 'Cybersecurity', 'cybersecurity', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(33, 'Event Management', 'event-management', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(34, 'Research and Development', 'research-and-development', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(35, 'Art and Design', 'art-and-design', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(36, 'Pet Care', 'pet-care', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(37, 'Fitness and Wellness', 'fitness-and-wellness', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(38, 'Waste Management', 'waste-management', '2025-02-04 00:34:17', '2025-02-04 00:34:17');

-- --------------------------------------------------------

--
-- Table structure for table `interviews`
--

CREATE TABLE `interviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `application_id` bigint(20) UNSIGNED NOT NULL,
  `interviewer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('phone','video','in-person') NOT NULL DEFAULT 'in-person',
  `location` varchar(255) DEFAULT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'scheduled',
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL,
  `outcome` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `interviews`
--

INSERT INTO `interviews` (`id`, `application_id`, `interviewer_id`, `created_by`, `type`, `location`, `meeting_link`, `status`, `scheduled_at`, `notes`, `outcome`, `created_at`, `updated_at`) VALUES
(1, 1, 9, 9, 'video', 'virtual', 'https://youtu.be/15Bgxmn7Ja4', 'scheduled', '2026-06-24 08:00:00', NULL, NULL, '2026-06-23 07:01:25', '2026-06-23 07:01:25'),
(2, 1, 9, 9, 'phone', 'Nairobi', NULL, 'scheduled', '2026-08-13 08:28:00', NULL, NULL, '2026-08-12 08:27:51', '2026-08-12 08:27:51');

-- --------------------------------------------------------

--
-- Table structure for table `interview_feedback`
--

CREATE TABLE `interview_feedback` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `interview_id` bigint(20) UNSIGNED NOT NULL,
  `interviewer_id` bigint(20) UNSIGNED NOT NULL,
  `comments` text NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `recommendation` enum('hire','reject','second_interview') NOT NULL DEFAULT 'second_interview',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_categories`
--

CREATE TABLE `job_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_categories`
--

INSERT INTO `job_categories` (`id`, `business_id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 2, 'HR Assistant', 'hr-assistant', 'Short Job Category Description...', '2026-01-20 19:30:12', '2026-01-20 19:30:12'),
(2, 2, 'Accounts Officer', 'accounts-officer', 'Enter some description...', '2026-01-20 19:30:25', '2026-01-20 19:30:25'),
(3, 2, 'Sales Executive', 'sales-executive', 'Enter some description...', '2026-01-20 19:30:39', '2026-01-20 19:30:39'),
(4, 2, 'Customer Service Office', 'customer-service-office', 'Enter some description...', '2026-01-20 19:33:20', '2026-01-20 19:33:20'),
(5, 2, 'IT Support Officer', 'it-support-officer', 'Enter some description...', '2026-01-20 19:33:33', '2026-01-20 19:33:33'),
(6, 2, 'Procurement Officer', 'procurement-officer', 'Enter some description...', '2026-01-20 19:33:46', '2026-01-20 19:33:46'),
(7, 2, 'Marketing Officer', 'marketing-officer', 'Enter some description...', '2026-01-20 19:33:57', '2026-01-20 19:33:57'),
(8, 2, 'Branch Administrator', 'branch-administrator', 'Enter some description...', '2026-01-20 19:34:09', '2026-01-20 19:34:09'),
(9, 2, 'Logistics Coordinator', 'logistics-coordinator', 'Enter some description...', '2026-01-20 19:34:20', '2026-01-20 19:34:20'),
(10, 2, 'Data Clerk', 'data-clerk', 'Enter some description...', '2026-01-20 19:34:31', '2026-01-20 19:34:31');

-- --------------------------------------------------------

--
-- Table structure for table `job_posts`
--

CREATE TABLE `job_posts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `requirements` longtext DEFAULT NULL,
  `salary_range` varchar(255) DEFAULT NULL,
  `number_of_positions` int(11) NOT NULL DEFAULT 1,
  `employment_type` enum('full-time','part-time','contract','internship') NOT NULL,
  `place` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `closing_date` date DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_posts`
--

INSERT INTO `job_posts` (`id`, `business_id`, `location_id`, `department_id`, `title`, `slug`, `description`, `requirements`, `salary_range`, `number_of_positions`, `employment_type`, `place`, `created_by`, `status`, `closing_date`, `is_public`, `closed_at`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, NULL, 'web developer', 'web-developer', '<div>\r\n<h2>What is Lorem Ipsum?</h2>\r\n<p><strong>Lorem Ipsum</strong>&nbsp;is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since 1966, when designers at Letraset and James Mosley, the librarian at St Bride Printing Library in London, took a 1914 Cicero translation and scrambled it to make dummy text for Letraset\'s Body Type sheets. It has survived not only many decades, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised thanks to these sheets and more recently with desktop publishing software including versions of Lorem Ipsum.</p>\r\n</div>\r\n<div>\r\n<h2>Why do we use it?</h2>\r\n<p>It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using \'Content here, content here\', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for \'lorem ipsum\' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).</p>\r\n</div>\r\n<p>&nbsp;</p>\r\n<div>\r\n<h2>Where does it come from?</h2>\r\n<p>Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source. Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of \"de Finibus Bonorum et Malorum\" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance. The first line of Lorem Ipsum, \"Lorem ipsum dolor sit amet..\", comes from a line in section 1.10.32.</p>\r\n<p>The standard chunk of Lorem Ipsum used since 1966 is reproduced below for those interested. Sections 1.10.32 and 1.10.33 from \"de Finibus Bonorum et Malorum\" by Cicero are also reproduced in their exact original form, accompanied by English versions from the 1914 translation by H. Rackham</p>\r\n</div>', NULL, NULL, 1, 'full-time', 'nairobi', 9, 'open', NULL, 1, NULL, '2026-06-22 10:15:29', '2026-06-22 10:15:55');

-- --------------------------------------------------------

--
-- Table structure for table `kpis`
--

CREATE TABLE `kpis` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `calculation_method` varchar(255) NOT NULL,
  `target_value` varchar(255) NOT NULL,
  `comparison_operator` varchar(255) NOT NULL,
  `business_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `job_category_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpi_results`
--

CREATE TABLE `kpi_results` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kpi_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  `result_value` decimal(15,2) DEFAULT NULL,
  `meets_target` tinyint(1) NOT NULL DEFAULT 0,
  `measured_at` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `contact_submission_id` bigint(20) UNSIGNED DEFAULT NULL,
  `campaign_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `survey_responses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`survey_responses`)),
  `status` enum('new','contacted','qualified','converted','lost') DEFAULT 'new',
  `label` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL COMMENT 'e.g., website, campaign',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_activities`
--

CREATE TABLE `lead_activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `lead_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `activity_type` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_delegations`
--

CREATE TABLE `leave_delegations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `delegate_id` bigint(20) UNSIGNED NOT NULL,
  `leave_request_id` bigint(20) UNSIGNED NOT NULL,
  `duties_delegated` text NOT NULL,
  `delegate_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `declined_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_entitlements`
--

CREATE TABLE `leave_entitlements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `leave_period_id` bigint(20) UNSIGNED NOT NULL,
  `carry_forward` decimal(5,2) NOT NULL,
  `entitled_days` decimal(5,2) NOT NULL,
  `accrued_days` decimal(5,2) NOT NULL,
  `carryover_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `adjustment_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `adjustment_reason` text DEFAULT NULL,
  `policy_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`policy_snapshot`)),
  `last_accrued_at` timestamp NULL DEFAULT NULL,
  `total_days` decimal(5,2) NOT NULL,
  `days_taken` decimal(5,2) NOT NULL DEFAULT 0.00,
  `days_pending` decimal(8,2) NOT NULL DEFAULT 0.00,
  `days_remaining` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_periods`
--

CREATE TABLE `leave_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `accept_applications` tinyint(1) NOT NULL DEFAULT 1,
  `can_accrue` tinyint(1) NOT NULL DEFAULT 1,
  `restrict_applications_within_dates` tinyint(1) NOT NULL DEFAULT 0,
  `archive` tinyint(1) NOT NULL DEFAULT 0,
  `autocreate` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_periods`
--

INSERT INTO `leave_periods` (`id`, `business_id`, `name`, `slug`, `start_date`, `end_date`, `is_active`, `accept_applications`, `can_accrue`, `restrict_applications_within_dates`, `archive`, `autocreate`, `created_at`, `updated_at`) VALUES
(1, 2, 'period 2026', 'period-2026', '2026-01-01', '2027-10-10', 1, 1, 1, 0, 0, 0, '2026-07-09 14:39:55', '2026-07-09 14:39:55');

-- --------------------------------------------------------

--
-- Table structure for table `leave_policies`
--

CREATE TABLE `leave_policies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `job_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `gender_applicable` enum('all','male','female') NOT NULL DEFAULT 'all',
  `default_days` int(11) NOT NULL,
  `accrual_frequency` enum('monthly','quarterly','yearly') NOT NULL,
  `accrual_amount` decimal(5,2) NOT NULL,
  `max_carryover_days` int(11) NOT NULL DEFAULT 0,
  `prorated_for_new_employees` tinyint(1) NOT NULL DEFAULT 1,
  `minimum_service_days_required` int(11) NOT NULL DEFAULT 0,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `reference_number` varchar(255) NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` decimal(5,2) NOT NULL,
  `revocation_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`revocation_history`)),
  `half_day` tinyint(1) NOT NULL DEFAULT 0,
  `attachment` varchar(255) DEFAULT NULL,
  `requires_documentation` tinyint(1) NOT NULL DEFAULT 0,
  `is_tentative` tinyint(1) NOT NULL DEFAULT 0,
  `current_approval_level` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `approval_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`approval_history`)),
  `half_day_type` enum('first_half','second_half') DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `handover_notes` text DEFAULT NULL,
  `handover_attachment` varchar(255) DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 1,
  `is_paid` tinyint(1) NOT NULL DEFAULT 1,
  `allowance_accruable` tinyint(1) NOT NULL DEFAULT 1,
  `allows_half_day` tinyint(1) NOT NULL DEFAULT 1,
  `requires_attachment` tinyint(1) NOT NULL DEFAULT 0,
  `max_continuous_days` int(11) DEFAULT NULL,
  `min_notice_days` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `allows_backdating` tinyint(1) NOT NULL DEFAULT 0,
  `approval_levels` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `approval_chain` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`approval_chain`)),
  `excluded_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`excluded_days`)),
  `excluded_dates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`excluded_dates`)),
  `exclude_public_holidays` tinyint(1) NOT NULL DEFAULT 1,
  `exclude_non_working_days` tinyint(1) NOT NULL DEFAULT 1,
  `is_stepwise` tinyint(1) NOT NULL DEFAULT 0,
  `stepwise_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stepwise_rules`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_type_lists`
--

CREATE TABLE `leave_type_lists` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `term_months` int(11) NOT NULL DEFAULT 12,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_repayments`
--

CREATE TABLE `loan_repayments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_id` bigint(20) UNSIGNED NOT NULL,
  `repayment_date` date NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `country` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `company_size` varchar(255) DEFAULT NULL,
  `registration_no` varchar(255) DEFAULT NULL,
  `tax_pin_no` varchar(255) DEFAULT NULL,
  `business_license_no` varchar(255) DEFAULT NULL,
  `physical_address` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `radius_m` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `business_id`, `name`, `country`, `slug`, `company_size`, `registration_no`, `tax_pin_no`, `business_license_no`, `physical_address`, `latitude`, `longitude`, `radius_m`, `created_at`, `updated_at`) VALUES
(1, 2, 'main business', NULL, 'main-business', '11-50', NULL, NULL, NULL, 'juja, kalimoni', NULL, NULL, NULL, '2026-01-22 10:23:56', '2026-01-22 10:23:56'),
(2, 2, 'mombasa', NULL, 'mombasa', '1-10', NULL, NULL, NULL, 'juja, kalimoni', NULL, NULL, NULL, '2026-03-02 12:00:16', '2026-03-02 12:00:16');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `banned_until` timestamp NULL DEFAULT NULL,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `attempts`, `banned_until`, `last_attempt_at`, `created_at`, `updated_at`) VALUES
(11, '127.0.0.1', 0, NULL, NULL, '2026-01-20 18:45:14', '2026-01-20 18:45:14');

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `browser` varchar(255) NOT NULL,
  `device` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `network` varchar(255) DEFAULT NULL,
  `login_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `ip_address`, `browser`, `device`, `location`, `network`, `login_at`, `created_at`, `updated_at`) VALUES
(1, 1, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-01-21 19:02:02', '2026-01-21 19:02:02', '2026-01-21 19:02:02'),
(2, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-01-21 19:03:35', '2026-01-21 19:03:35', '2026-01-21 19:03:35'),
(3, 9, '127.0.0.1', 'Edge', 'Desktop', 'Localhost', 'Local Network', '2026-01-21 19:06:47', '2026-01-21 19:06:47', '2026-01-21 19:06:47'),
(4, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-01-21 19:12:05', '2026-01-21 19:12:05', '2026-01-21 19:12:05'),
(5, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-01-22 05:18:41', '2026-01-22 05:18:41', '2026-01-22 05:18:41'),
(6, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-01-22 09:43:22', '2026-01-22 09:43:22', '2026-01-22 09:43:22'),
(7, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-01-22 11:54:26', '2026-01-22 11:54:26', '2026-01-22 11:54:26'),
(8, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-01-22 19:38:10', '2026-01-22 19:38:10', '2026-01-22 19:38:10'),
(9, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-01-23 05:07:32', '2026-01-23 05:07:32', '2026-01-23 05:07:32'),
(10, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-01-23 08:00:26', '2026-01-23 08:00:26', '2026-01-23 08:00:26'),
(11, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-02-21 10:57:20', '2026-02-21 10:57:20', '2026-02-21 10:57:20'),
(12, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-02-21 20:03:30', '2026-02-21 20:03:30', '2026-02-21 20:03:30'),
(13, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-02-22 19:22:52', '2026-02-22 19:22:52', '2026-02-22 19:22:52'),
(14, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-02-23 13:35:10', '2026-02-23 13:35:10', '2026-02-23 13:35:10'),
(15, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-02-24 06:14:36', '2026-02-24 06:14:36', '2026-02-24 06:14:36'),
(16, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-03-02 11:59:14', '2026-03-02 11:59:14', '2026-03-02 11:59:14'),
(17, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-03-04 09:22:16', '2026-03-04 09:22:16', '2026-03-04 09:22:16'),
(18, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-03-20 07:57:27', '2026-03-20 07:57:27', '2026-03-20 07:57:27'),
(19, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-04-24 19:07:12', '2026-04-24 19:07:12', '2026-04-24 19:07:12'),
(20, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-04-25 05:11:53', '2026-04-25 05:11:53', '2026-04-25 05:11:53'),
(21, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-04-25 17:06:05', '2026-04-25 17:06:05', '2026-04-25 17:06:05'),
(22, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-04-29 14:45:04', '2026-04-29 14:45:04', '2026-04-29 14:45:04'),
(23, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-05-03 06:11:04', '2026-05-03 06:11:04', '2026-05-03 06:11:04'),
(24, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-05-03 11:43:07', '2026-05-03 11:43:07', '2026-05-03 11:43:07'),
(25, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-05-04 05:57:31', '2026-05-04 05:57:31', '2026-05-04 05:57:31'),
(26, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-05-04 11:46:13', '2026-05-04 11:46:13', '2026-05-04 11:46:13'),
(27, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-06-22 08:37:34', '2026-06-22 08:37:34', '2026-06-22 08:37:34'),
(28, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-06-22 13:18:04', '2026-06-22 13:18:04', '2026-06-22 13:18:04'),
(29, 9, '127.0.0.1', 'Chrome', 'Mobile', 'Localhost', 'Local Network', '2026-06-23 06:32:56', '2026-06-23 06:32:56', '2026-06-23 06:32:56'),
(30, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-06-23 06:51:27', '2026-06-23 06:51:27', '2026-06-23 06:51:27'),
(31, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-06-23 11:20:06', '2026-06-23 11:20:06', '2026-06-23 11:20:06'),
(32, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-07-09 14:37:20', '2026-07-09 14:37:20', '2026-07-09 14:37:20'),
(33, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-08-06 08:57:20', '2026-08-06 08:57:20', '2026-08-06 08:57:20'),
(34, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-08-10 07:20:31', '2026-08-10 07:20:31', '2026-08-10 07:20:31'),
(35, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-08-10 08:57:26', '2026-08-10 08:57:26', '2026-08-10 08:57:26'),
(36, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-08-11 07:15:39', '2026-08-11 07:15:39', '2026-08-11 07:15:39'),
(37, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-08-11 13:27:42', '2026-08-11 13:27:42', '2026-08-11 13:27:42'),
(38, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-08-12 05:25:45', '2026-08-12 05:25:45', '2026-08-12 05:25:45'),
(39, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-08-17 13:22:51', '2026-08-17 13:22:51', '2026-08-17 13:22:51'),
(40, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-08-20 07:29:50', '2026-08-20 07:29:50', '2026-08-20 07:29:50'),
(41, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-08-21 08:59:37', '2026-08-21 08:59:37', '2026-08-21 08:59:37'),
(42, 9, '127.0.0.1', 'Chrome', 'Desktop', 'Localhost', 'Local Network', '2026-08-21 09:41:33', '2026-08-21 09:41:33', '2026-08-21 09:41:33');

-- --------------------------------------------------------

--
-- Table structure for table `mandatory_leave_periods`
--

CREATE TABLE `mandatory_leave_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `scope_type` varchar(255) NOT NULL,
  `scope_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`scope_ids`)),
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mandatory_leave_period_deductions`
--

CREATE TABLE `mandatory_leave_period_deductions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mandatory_leave_period_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `leave_entitlement_id` bigint(20) UNSIGNED NOT NULL,
  `days_deducted` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) DEFAULT NULL,
  `collection_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `disk` varchar(255) NOT NULL,
  `conversions_disk` varchar(255) DEFAULT NULL,
  `size` bigint(20) UNSIGNED NOT NULL,
  `manipulations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`manipulations`)),
  `custom_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`custom_properties`)),
  `generated_conversions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`generated_conversions`)),
  `responsive_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`responsive_images`)),
  `order_column` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `media`
--

INSERT INTO `media` (`id`, `model_type`, `model_id`, `uuid`, `collection_name`, `name`, `file_name`, `mime_type`, `disk`, `conversions_disk`, `size`, `manipulations`, `custom_properties`, `generated_conversions`, `responsive_images`, `order_column`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\Business', 3, 'c51dc767-930f-4864-bcf5-4c278a2ca8e4', 'businesses', 'krstlogo', 'krstlogo.png', 'image/png', 'public', 'public', 92931, '[]', '[]', '[]', '[]', 1, '2026-01-20 18:30:33', '2026-01-20 18:30:33'),
(2, 'App\\Models\\Business', 4, '27a48481-be7a-4c15-952c-a0e81b44cdaf', 'businesses', 'krest-removebg-preview', 'krest-removebg-preview.png', 'image/png', 'public', 'public', 53916, '[]', '[]', '[]', '[]', 1, '2026-01-21 19:08:10', '2026-01-21 19:08:10'),
(4, 'App\\Models\\Employee', 11, 'aadaee3f-b715-4ea3-85b3-20ff61dc1964', 'avatars', 'roanhy', 'roanhy.png', 'image/png', 'public', 'public', 55005, '[]', '[]', '[]', '[]', 1, '2026-02-24 09:45:20', '2026-02-24 09:45:20'),
(5, 'App\\Models\\User', 36, '20a456c9-7582-4356-a2ea-a3b50f874b8a', 'avatars', 'med6F82', 'med6F82.tmp', 'image/png', 'public', 'public', 377, '[]', '[]', '[]', '[]', 1, '2026-06-22 10:20:59', '2026-06-22 10:20:59'),
(6, 'App\\Models\\Application', 1, '2b1706d8-2822-48d3-aaf9-e78cad5b9190', 'applications', 'sheryl', 'sheryl.pdf', 'application/pdf', 'public', 'public', 367842, '[]', '[]', '[]', '[]', 1, '2026-06-22 10:27:07', '2026-06-22 10:27:07');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2024_12_28_020422_create_permission_tables', 1),
(5, '2024_12_28_020618_create_media_table', 1),
(6, '2024_12_28_020943_create_statuses_table', 1),
(7, '2024_12_28_024918_create_businesses_table', 1),
(8, '2024_12_28_024919_create_industries_table', 1),
(9, '2024_12_28_025027_create_modules_table', 1),
(10, '2025_01_14_025030_create_job_categories_table', 1),
(11, '2025_01_14_025032_create_shifts_table', 1),
(12, '2025_01_14_052658_create_departments_table', 1),
(13, '2025_01_30_082503_create_locations_table', 2),
(14, '2025_01_14_083726_create_employees_table', 1),
(15, '2025_01_14_083727_create_spouses_table', 3),
(16, '2025_01_14_083728_create_emergency_contacts_table', 3),
(17, '2025_01_14_083729_create_academic_qualifications_table', 3),
(18, '2025_01_14_083730_create_previous_employments_table', 3),
(19, '2025_01_16_113024_create_employee_family_members_table', 3),
(20, '2025_01_16_113239_create_employment_details_table', 3),
(21, '2025_01_16_113352_create_employee_payment_details_table', 3),
(22, '2025_01_16_113502_create_employee_contact_details_table', 3),
(23, '2025_01_16_113640_create_employee_documents_table', 3),
(24, '2025_01_17_014648_create_payroll_formulas_table', 3),
(25, '2025_01_17_020359_create_payroll_formula_brackets_table', 3),
(26, '2025_01_17_050009_create_reliefs_table', 3),
(27, '2025_01_17_050010_create_employee_reliefs_table', 3),
(28, '2025_01_17_050011_create_allowances_table', 3),
(29, '2025_01_17_050012_create_employee_allowances_table', 3),
(30, '2025_01_17_050013_create_deductions_table', 3),
(31, '2025_01_17_050014_create_employee_deductions_table', 3),
(32, '2025_01_17_050015_create_leave_type_lists_table', 3),
(33, '2025_01_23_100649_create_leave_types_table', 3),
(34, '2025_01_23_100656_create_leave_policies_table', 3),
(35, '2025_01_23_123044_create_leave_requests_table', 3),
(36, '2025_01_23_130905_create_leave_delegations_table', 3),
(37, '2025_01_23_131303_create_leave_periods_table', 3),
(38, '2025_01_23_143454_create_leave_entitlements_table', 3),
(39, '2025_01_24_025902_create_clients_table', 3),
(40, '2025_01_27_063018_create_access_requests_table', 3),
(41, '2025_01_30_165752_create_payrolls_table', 3),
(42, '2025_02_02_085754_create_employee_payrolls_table', 3),
(43, '2025_02_05_025648_add_location_id_to_employees_table', 2),
(44, '2025_02_07_042439_create_advances_table', 4),
(45, '2025_02_07_052854_create_loans_table', 4),
(46, '2025_02_07_053852_create_loan_repayments_table', 4),
(47, '2025_02_10_210013_create_skills_table', 4),
(48, '2025_02_10_210016_create_job_posts_table', 4),
(49, '2025_02_10_211642_create_applicants_table', 4),
(50, '2025_02_10_211643_create_applicant_skills_table', 4),
(51, '2025_02_10_213257_create_applications_table', 4),
(52, '2025_02_10_221803_create_interviews_table', 4),
(53, '2025_02_10_224847_create_interview_feedback_table', 4),
(54, '2025_02_11_031751_add_timestamps_to_applicants_table', 2),
(55, '2025_02_11_065712_update_payrolls_table', 5),
(56, '2025_02_12_070910_create_tasks_table', 5),
(57, '2025_02_14_044514_add_unique_constraint_to_payrolls', 5),
(58, '2025_02_16_084350_create_attendances_table', 5),
(59, '2025_02_16_104550_create_overtimes_table', 5),
(60, '2025_02_24_040831_create_notifications_table', 5),
(61, '2025_02_24_041321_create_notification_preferences_table', 5),
(62, '2025_02_24_041640_create_notification_logs_table', 5),
(63, '2025_03_03_090714_add_deductions_to_employee_payrolls', 5),
(64, '2025_03_05_080459_create_reports_table', 5),
(65, '2025_03_07_044156_create_activity_log_table', 5),
(66, '2025_03_07_044157_add_event_column_to_activity_log_table', 5),
(67, '2025_03_07_044158_add_batch_uuid_column_to_activity_log_table', 5),
(68, '2025_03_07_045135_create_activity_logs_table', 5),
(69, '2025_03_19_002636_create_kpis_table', 5),
(70, '2025_03_19_002909_create_kpi_results_table', 5),
(71, '2025_03_19_075457_make_description_nullable_in_overtimes_table', 5),
(72, '2025_03_19_080922_create_warnings_table', 5),
(73, '2025_03_19_121616_create_pay_grades_table', 5),
(74, '2025_03_22_000000_create_employee_payroll_settings_table', 5),
(75, '2025_03_22_000001_create_payslips_table', 5),
(76, '2025_03_22_000002_create_employee_payrolls_table', 2),
(77, '2025_04_02_095805_add_resident_and_kra_status_to_employees', 6),
(78, '2025_04_03_042150_add_reliefs_to_employee_payrolls_table', 6),
(79, '2025_04_03_123318_create_recruitment_table', 6),
(80, '2025_04_13_121643_add_department_and_job_category_to_kpis_table', 7),
(81, '2025_08_21_091423_update_calculation_basis_enum_in_deductions_table', 7),
(82, '2025_09_06_160848_add_attachment_to_leave_requests_table', 8),
(83, '2025_09_06_233702_add_hr_email_to_business_table', 8),
(84, '2025_09_15_174148_add_workflow_fields_to_leave_requests', 8),
(85, '2025_09_15_180252_add_fields_to_leave_types_table', 8),
(86, '2025_09_16_153232_add_license_fields_to_employment_details_table', 8),
(87, '2025_09_17_100539_add_second_probation_end_date_to_employment_details_table', 8),
(88, '2025_09_17_103023_update_employment_term_enum_in_employment_details_table', 8),
(89, '2025_10_22_150702_add_excluded_dates_to_leave_types_table', 8),
(90, '2025_11_04_163117_create_employee_departments_table', 8),
(91, '2025_11_04_164517_add_attendance_settings_columns_to_businesses_table', 8),
(92, '2025_11_04_164705_add_device_and_coords_to_attendances_table', 8),
(93, '2025_11_04_164823_add_geo_columns_to_locations_table', 8),
(94, '2025_11_05_111322_add_registered_device_mac_to_employees_table', 8),
(95, '2025_11_09_115443_add_revocation_history_to_leave_requests', 8),
(96, '2025_11_09_214227_add_is_active_to_employees_table', 8),
(97, '2025_11_14_133654_update_leave_entitlements_table', 8),
(98, '2026_01_11_001700_add_status_to_leave_requests_table', 8),
(99, '2026_01_18_214503_create_personnel_positions_table', 9),
(100, '2026_01_17_105854_create_organogram_positions_table', 10),
(101, '2026_01_17_110046_create_organogram_position_holders_table', 11),
(102, '2026_01_18_223224_create_login_attempts_table', 12),
(103, '2026_01_20_212616_add_verified_to_businesses_table', 13),
(104, '2026_01_20_214246_fix_two_factor_codes_id_column', 14),
(105, '2026_01_20_215834_create_login_logs_table', 15),
(106, '2026_01_20_224915_add_business_id_to_roles_table', 16),
(107, '2026_01_21_000141_add_payroll_and_status_fields_to_employees', 17),
(108, '2026_01_21_215941_add_banned_until_to_login_attempts_table', 18),
(109, '2026_01_23_081733_add_is_active_to_employee_reliefs_table', 19),
(110, '2026_02_21_162005_add_hourly_rate_to_employee_payment_details', 20),
(111, '2026_02_21_173841_add_dates_to_employee_reliefs_table', 21),
(112, '2026_02_21_175555_add_status_and_emailed_to_payrolls_table', 22),
(113, '2026_02_21_180127_restructure_employee_payrolls_table', 23),
(114, '2026_02_21_100917_create_work_schedules_table', 24),
(115, '2026_02_21_101207_create_holidays_table', 24),
(116, '2026_02_21_101511_enhance_attendances_table', 24),
(117, '2026_02_21_101604_enhance_overtimes_table', 24),
(118, '2026_02_21_101629_add_overtimes_to_employees_table', 24),
(119, '2026_02_21_101804_add_locationid_to_overtimes', 24),
(120, '2026_02_21_101839_make_approvedby_nullable', 24),
(121, '2026_02_21_101942_create_rship_shift_schedule_attendances', 24),
(122, '2026_02_21_234742_add_country_to_payroll_formulas_table', 25),
(123, '2026_02_22_000315_update_payroll_formulas_to_match_production', 26),
(124, '2026_02_24_093010_add_pwd_fields_to_employee_payroll_details_table', 27),
(125, '2026_02_24_110239_add_pwd_fields_to_employee_payroll_details_table', 28),
(126, '2026_02_24_125516_update_allowances_table_add_missing_columns', 29),
(127, '2026_03_20_140003_add_currency_conversion_to_employee_payrolls', 30),
(128, '2026_03_20_140105_add_create_business_currencies_table', 30),
(129, '2026_03_20_140141_add_employer_rate_to_deductions_table', 30),
(130, '2026_04_25_134343_add_wht_fields_to_employee_payment_details', 31),
(131, '2026_04_25_134457_create_withholding_payments_table', 31),
(132, '2026_04_25_140434_create_withholding_tax_rates_table', 31),
(133, '2026_04_25_140735_add_wht_to_employee_payrolls', 31),
(134, '2026_04_25_140906_update_applicants_table', 31),
(135, '2026_04_25_141013_update_applicants_table_for_external', 31),
(136, '2026_04_25_141132_create_application_profiles_table', 31),
(137, '2026_04_25_141602_create_experiences_table.', 32),
(138, '2026_05_03_102408_make_location_id_nullable_on_employees_table', 33),
(139, '2026_06_22_130927_add_status_to_job_posts_table', 34),
(140, '2026_06_22_131401_add_missing_columns_to_job_posts_table', 35),
(141, '2026_06_22_145946_add_missing_columns_to_applicants_table', 36),
(142, '2026_06_23_095957_add_status_to_interviews_table', 37),
(143, '2026_07_13_000002_create_employee_contract_actions_table', 38),
(144, '2026_07_13_000003_add_status_to_employment_details_table', 39),
(145, '2026_07_13_000004_add_is_active_to_leave_periods_table', 40),
(146, '2026_07_13_000006_add_exclude_public_holidays_to_leave_types_table', 40),
(147, '2026_07_13_000007_add_manager_id_to_employees_table', 40),
(148, '2026_07_13_000008_add_declined_at_to_leave_delegations_table', 40),
(149, '2026_07_13_000009_add_handover_notes_to_leave_requests_table', 40),
(150, '2026_07_13_000010_add_disciplinary_fields_to_warnings_table', 40),
(151, '2026_07_13_000011_add_suspension_to_employee_contract_actions_action_type', 40),
(152, '2026_07_13_000012_add_suspended_to_employment_details_status', 40),
(153, '2026_07_13_000013_create_performance_cycles_table', 41),
(154, '2026_07_13_000014_create_performance_objectives_table', 41),
(155, '2026_07_13_000015_create_performance_key_results_table', 41),
(156, '2026_07_13_000016_create_performance_reviews_table', 41),
(157, '2026_07_13_000017_create_organogram_roles_table', 41),
(158, '2026_07_13_000018_add_organogram_role_id_to_employees_table', 41),
(159, '2026_07_13_000019_add_manager_override_to_employees_table', 41),
(160, '2026_07_13_000020_add_cascade_fields_to_performance_objectives_table', 41),
(161, '2026_07_13_000021_add_confidence_and_grading_to_performance_objectives_table', 41),
(162, '2026_07_13_000022_add_goal_lock_to_performance_cycles_table', 41),
(163, '2026_07_14_000001_create_performance_feedback_requests_table', 41),
(164, '2026_07_14_000002_create_performance_feedback_responses_table', 41),
(165, '2026_07_14_000003_add_non_working_days_to_businesses_table', 41),
(166, '2026_07_14_000004_add_handover_attachment_to_leave_requests_table', 41),
(167, '2026_07_15_000001_add_exclude_non_working_days_to_leave_types_table', 41),
(168, '2026_07_15_000002_add_hierarchy_fields_to_organogram_roles_table', 41),
(169, '2026_07_15_000003_create_teams_table', 41),
(170, '2026_07_15_000004_add_team_id_to_employees_table', 41),
(171, '2026_07_15_000005_create_organogram_positions_table', 41),
(172, '2026_07_15_000006_migrate_existing_org_structure_to_positions', 41),
(173, '2026_07_15_000007_add_adjustment_fields_to_leave_entitlements_table', 41),
(174, '2026_07_16_000001_add_approval_chain_to_leave_types_table', 41),
(175, '2026_07_16_000002_add_country_to_locations_table', 41),
(176, '2026_07_16_000003_add_location_id_to_holidays_table', 41),
(177, '2026_07_20_174247_add_third_rule_to_payrolls_table', 41),
(178, '2026_07_21_114232_create_mandatory_leave_periods_table', 41),
(179, '2026_07_21_114238_create_mandatory_leave_period_deductions_table', 41),
(180, '2026_07_21_214248_add_days_pending_to_leave_entitlements_table', 41),
(181, '2026_07_23_000001_scope_employee_identity_uniqueness_to_business', 42),
(182, '2026_07_29_000001_scope_leave_period_name_uniqueness_to_business', 42),
(183, '2026_08_10_125329_add_disciplinary_fields_to_warnings_table', 43),
(184, '2026_08_12_115907_add_assignment_columns_to_kpis_table', 44);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(7, 'App\\Models\\User', 9),
(11, 'App\\Models\\User', 17),
(11, 'App\\Models\\User', 19),
(11, 'App\\Models\\User', 20),
(11, 'App\\Models\\User', 21),
(11, 'App\\Models\\User', 24),
(11, 'App\\Models\\User', 25),
(11, 'App\\Models\\User', 26),
(11, 'App\\Models\\User', 27),
(11, 'App\\Models\\User', 34),
(11, 'App\\Models\\User', 35),
(12, 'App\\Models\\User', 36),
(13, 'App\\Models\\User', 9),
(14, 'App\\Models\\User', 9);

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price_monthly` decimal(10,2) NOT NULL,
  `price_yearly` decimal(10,2) NOT NULL,
  `is_core` tinyint(1) NOT NULL DEFAULT 0,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`features`)),
  `icon` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `name`, `slug`, `description`, `price_monthly`, `price_yearly`, `is_core`, `features`, `icon`, `created_at`, `updated_at`) VALUES
(1, 'Core HR Management', 'core-hr-management', 'Essential HR features including employee management, attendance, and basic reporting', 0.00, 0.00, 1, '[\"Employee Database\",\"Attendance Management\",\"Leave Management\",\"Basic Reports\",\"Document Management\"]', 'people-fill', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(2, 'Payroll Management', 'payroll-management', 'Complete payroll processing system with tax calculations and compliance', 49.99, 499.99, 0, '[\"Salary Processing\",\"Tax Calculations\",\"Payslip Generation\",\"Statutory Compliance\",\"Multiple Payment Methods\",\"Payroll Reports\"]', 'wallet2', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(3, 'Recruitment & Onboarding', 'recruitment-onboarding', 'End-to-end recruitment solution from job posting to onboarding', 39.99, 399.99, 0, '[\"Job Posting Management\",\"Applicant Tracking\",\"Interview Scheduling\",\"Candidate Assessment\",\"Onboarding Workflow\",\"Document Collection\"]', 'person-plus-fill', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(4, 'Performance Management', 'performance-management', 'Complete performance evaluation and goal tracking system', 29.99, 299.99, 0, '[\"Goal Setting & Tracking\",\"Performance Reviews\",\"360\\u00b0 Feedback\",\"Skills Assessment\",\"Development Plans\",\"Performance Analytics\"]', 'graph-up-arrow', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(5, 'Learning Management', 'learning-management', 'Employee training and development platform', 34.99, 349.99, 0, '[\"Course Management\",\"Training Schedules\",\"Learning Paths\",\"Assessment Tools\",\"Certification Tracking\",\"Training Reports\"]', 'journal-bookmark', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(6, 'Time & Attendance', 'time-attendance', 'Advanced time tracking and attendance management', 24.99, 249.99, 0, '[\"Time Tracking\",\"Shift Management\",\"Overtime Calculation\",\"Leave Planning\",\"Attendance Reports\",\"Mobile Check-in\"]', 'clock-fill', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(7, 'Asset Management', 'asset-management', 'Track and manage company assets and resources', 19.99, 199.99, 0, '[\"Asset Tracking\",\"Maintenance Scheduling\",\"Asset Assignment\",\"Inventory Management\",\"Asset Reports\",\"Depreciation Tracking\"]', 'box-seam', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(8, 'Employee Self-Service', 'employee-self-service', 'Portal for employees to manage their information and requests', 14.99, 149.99, 0, '[\"Profile Management\",\"Leave Requests\",\"Expense Claims\",\"Document Access\",\"Payslip Download\",\"Benefits Enrollment\"]', 'person-workspace', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(9, 'CRM Integration', 'crm-integration', 'Customer relationship management integration with HR', 44.99, 449.99, 0, '[\"Contact Management\",\"Lead Tracking\",\"Sales Pipeline\",\"Customer Support\",\"Email Integration\",\"Analytics & Reports\"]', 'people', '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(10, 'Project Management', 'project-management', 'Project planning and resource management tools', 39.99, 399.99, 0, '[\"Project Planning\",\"Task Management\",\"Resource Allocation\",\"Time Tracking\",\"Project Reports\",\"Team Collaboration\"]', 'clipboard-data', '2025-02-04 00:34:17', '2025-02-04 00:34:17');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`) VALUES
('0d6bb84d-2fed-48b2-a1ec-f8286f314a6c', 'App\\Notifications\\SystemAlertNotification', 'App\\Models\\User', 9, '{\"message\":\"System maintenance scheduled.\",\"data\":{\"details\":\"Server will be down for 2 hours.\"}}', NULL, '2026-01-21 19:03:35', '2026-01-21 19:03:35'),
('1f07658b-fe17-47dc-ade3-505604dc3f11', 'App\\Notifications\\BusinessChangedNotification', 'App\\Models\\User', 9, '{\"message\":\"Business grace tech created by KrestHR\",\"business_id\":3,\"user_id\":9}', NULL, '2026-01-20 18:30:40', '2026-01-20 18:30:40'),
('23b56575-0815-4298-bf3e-fe24a7dc2a91', 'App\\Notifications\\SystemAlertNotification', 'App\\Models\\User', 1, '{\"message\":\"System maintenance scheduled.\",\"data\":{\"details\":\"Server will be down for 2 hours.\"}}', NULL, '2026-01-21 19:02:08', '2026-01-21 19:02:08'),
('75116792-76a3-488a-9869-87d4eb87bca5', 'App\\Notifications\\BusinessChangedNotification', 'App\\Models\\User', 9, '{\"message\":\"Business KrestHR created by KrestHR\",\"business_id\":4,\"user_id\":9}', NULL, '2026-01-21 19:08:43', '2026-01-21 19:08:43'),
('9d79f995-4486-4428-9f81-bb57b107055f', 'App\\Notifications\\SystemAlertNotification', 'App\\Models\\User', 9, '{\"message\":\"System maintenance scheduled.\",\"data\":{\"details\":\"Server will be down for 2 hours.\"}}', NULL, '2026-01-21 19:06:47', '2026-01-21 19:06:47');

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `notification_type` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `sent_at` timestamp NULL DEFAULT NULL,
  `channel` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `email` tinyint(1) NOT NULL DEFAULT 1,
  `database` tinyint(1) NOT NULL DEFAULT 1,
  `sms` tinyint(1) NOT NULL DEFAULT 0,
  `slack` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organogram_positions`
--

CREATE TABLE `organogram_positions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `personnel_position_id` bigint(20) UNSIGNED DEFAULT NULL,
  `level` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 999,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `organogram_positions`
--

INSERT INTO `organogram_positions` (`id`, `name`, `business_id`, `title`, `code`, `parent_id`, `personnel_position_id`, `level`, `sort_order`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'John Mwangi', 2, 'CEO', NULL, NULL, NULL, 1, 999, NULL, 1, '2026-01-22 10:52:34', '2026-01-22 10:52:34'),
(2, 'Aisha Ali', 2, 'finance', NULL, 1, NULL, 2, 999, NULL, 1, '2026-01-22 11:06:57', '2026-01-22 11:06:57'),
(3, 'Peter Otieno', 2, 'sales manager', NULL, 1, NULL, 2, 999, NULL, 1, '2026-01-22 11:07:31', '2026-01-22 11:07:31'),
(4, 'Peter Otieno', 2, 'accounts Clerk', NULL, 2, NULL, 3, 999, NULL, 1, '2026-01-22 20:05:49', '2026-01-22 20:05:49'),
(5, 'grace Wanjiku', 2, 'CEO', NULL, NULL, NULL, 1, 999, NULL, 1, '2026-02-23 13:45:25', '2026-02-23 13:45:25'),
(6, 'John Mwangi', 2, 'Hr manager', NULL, 1, NULL, 2, 999, NULL, 1, '2026-02-23 13:46:41', '2026-02-23 13:46:41');

-- --------------------------------------------------------

--
-- Table structure for table `organogram_position_holders`
--

CREATE TABLE `organogram_position_holders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `organogram_position_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `organogram_position_holders`
--

INSERT INTO `organogram_position_holders` (`id`, `organogram_position_id`, `employee_id`, `start_date`, `end_date`, `is_primary`, `created_at`, `updated_at`) VALUES
(1, 1, 11, '2026-01-22', NULL, 1, '2026-01-22 10:52:34', '2026-01-22 10:52:34'),
(2, 2, 12, '2026-01-22', NULL, 1, '2026-01-22 11:06:57', '2026-01-22 11:06:57'),
(3, 3, 13, '2026-01-22', NULL, 1, '2026-01-22 11:07:31', '2026-01-22 11:07:31'),
(4, 4, 13, '2026-01-22', NULL, 1, '2026-01-22 20:05:49', '2026-01-22 20:05:49'),
(5, 5, 14, '2026-02-23', NULL, 1, '2026-02-23 13:45:25', '2026-02-23 13:45:25'),
(6, 6, 11, '2026-02-23', NULL, 1, '2026-02-23 13:46:41', '2026-02-23 13:46:41');

-- --------------------------------------------------------

--
-- Table structure for table `organogram_roles`
--

CREATE TABLE `organogram_roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `level` int(10) UNSIGNED NOT NULL,
  `reports_to_role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `spatie_role_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `overtimes`
--

CREATE TABLE `overtimes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `overtime_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `overtime_type` enum('regular','holiday','manual') NOT NULL DEFAULT 'regular',
  `rate` decimal(8,2) NOT NULL,
  `total_pay` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `attendance_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`email`, `token`, `created_at`) VALUES
('aisha.ali@example.com', '$2y$12$3fphifqKianeBOdaQffiCuSFcevBqk3YfG7NM3R2n6uZmism6HQZC', '2026-01-22 10:58:59'),
('alice.wanjiru@example.com', '$2y$12$KbJOi7z6rfxAvqwy34ob1.AD8iJozHxhUJFnKqfxY49l/AKVTRXpG', '2026-05-04 07:41:08'),
('alice@gmail.com', '$2y$12$MpvAyuGK.12HwajJxDLZ2OjP907OCteGGgJAzXImNaGuVyW7GTZCG', '2026-05-03 07:29:03'),
('brian.otieno@example.com', '$2y$12$996dn6cdiO4Ex8So70JvJu8qIIvYZrr4Ym7PUirgZCSSNGLu/l0te', '2026-05-04 07:41:14'),
('grace.wanjiku@example.com', '$2y$12$Sc7KDz2V5djtNQAo9/PBzeNoNXd4Gpu7RmNYwf/5enpLkFAoi5uTK', '2026-01-22 11:06:17'),
('john.mwangi@example.com', '$2y$12$eYAMOwycN.Xr95zUUTWoBuDxGmt.6ZiTiiMWY8srnZZbbZtfxc4B.', '2026-01-22 10:26:32'),
('john.smithe@example.com', '$2y$12$X6uESSOTzESxb8SzUW6ExekioA4GrcG4KKjggHyrYbFXjulxDQEqe', '2026-05-04 06:28:16'),
('krestworks.tech@gmail.com', '$2y$12$kX/bKE4yd91Y.GRBXz7c2.AlUMzzeO7H5WunFIvdQ/4/Ia0N7jic6', '2026-05-03 06:22:06'),
('ohn.mith@example.com', '$2y$12$Xm8gR6nt7Ul0yD7r92qxKeGS7nZAWPL.b2EoMyoBWzP.3BFre8eMK', '2026-05-04 06:28:49'),
('peter.otieno@example.com', '$2y$12$S8EtLC254LhlDSD5OSOSpOeBLq8jiD7zGAd9/rNi/9HWUgWWlaGF2', '2026-01-22 11:02:18');

-- --------------------------------------------------------

--
-- Table structure for table `payrolls`
--

CREATE TABLE `payrolls` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `third_rule` tinyint(1) DEFAULT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payroll_type` varchar(255) NOT NULL,
  `currency` varchar(255) NOT NULL,
  `staff` int(11) NOT NULL,
  `payrun_year` year(4) NOT NULL,
  `payrun_month` tinyint(3) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `emailed` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payrolls`
--

INSERT INTO `payrolls` (`id`, `third_rule`, `business_id`, `location_id`, `payroll_type`, `currency`, `staff`, `payrun_year`, `payrun_month`, `created_at`, `updated_at`, `status`, `emailed`) VALUES
(7, NULL, 2, NULL, 'monthly', 'KES', 4, '2026', 2, '2026-02-22 19:36:15', '2026-02-22 19:36:15', 'open', NULL),
(8, NULL, 2, NULL, 'monthly', 'KES', 1, '2026', 3, '2026-03-02 12:02:22', '2026-03-20 11:27:23', 'open', NULL),
(9, NULL, 2, NULL, 'monthly', 'KES', 4, '2026', 4, '2026-04-25 05:47:16', '2026-04-25 05:47:16', 'open', NULL),
(11, 1, 2, NULL, 'monthly', 'KES', 1, '2026', 8, '2026-08-12 06:52:33', '2026-08-12 06:52:33', 'open', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payroll_formulas`
--

CREATE TABLE `payroll_formulas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `formula_type` enum('rate','fixed','progressive','expression') DEFAULT NULL,
  `calculation_basis` enum('basic_pay','gross_pay','cash_pay','taxable_pay') NOT NULL,
  `is_progressive` tinyint(1) NOT NULL DEFAULT 0,
  `is_statutory` tinyint(1) NOT NULL DEFAULT 0,
  `minimum_amount` decimal(15,2) DEFAULT NULL,
  `limit` decimal(15,2) DEFAULT NULL,
  `round_off` enum('round_up','round_down','nearest') DEFAULT NULL,
  `applies_to` enum('all','specific') NOT NULL DEFAULT 'all',
  `expression` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_formulas`
--

INSERT INTO `payroll_formulas` (`id`, `business_id`, `country`, `name`, `slug`, `description`, `formula_type`, `calculation_basis`, `is_progressive`, `is_statutory`, `minimum_amount`, `limit`, `round_off`, `applies_to`, `expression`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Kenya', 'PAYE', 'paye', 'Kenya PAYE tax on taxable income', 'progressive', 'taxable_pay', 1, 1, NULL, NULL, 'nearest', 'all', NULL, '2025-03-20 21:40:10', '2025-03-20 21:40:10'),
(2, NULL, 'Kenya', 'NHIF', 'nhif', 'National Hospital Insurance Fund contribution', 'progressive', 'gross_pay', 1, 1, NULL, NULL, 'nearest', 'all', NULL, '2025-03-20 21:40:10', '2025-03-20 21:40:10'),
(5, NULL, 'Kenya', 'SHIF', 'shif', 'Social Health Insurance Fund contribution', 'expression', 'gross_pay', 0, 1, 300.00, NULL, 'nearest', 'all', 'max(gross_pay * 0.0275, 300)', '2025-03-20 21:40:10', '2025-08-19 06:45:49'),
(6, NULL, 'Kenya', 'Housing Levy', 'housing-levy', 'Affordable Housing Levy at 1.5% of gross pay', 'rate', 'gross_pay', 0, 1, 1.50, NULL, 'nearest', 'all', NULL, '2025-04-02 07:00:00', '2025-04-02 07:00:00'),
(7, NULL, 'Kenya', 'HELB', 'helb', 'Higher Education Loans Board deduction (flat rate if applicable)', 'fixed', 'gross_pay', 0, 1, 0.00, NULL, 'nearest', 'all', NULL, '2025-04-02 07:00:00', '2025-04-02 07:00:00'),
(9, NULL, 'Nigeria', 'Nigeria PAYE', 'nigeria-paye', 'Pay As You Earn (PAYE) tax for Nigeria, based on 2024 FIRS tax bands (NGN, monthly)', 'progressive', 'taxable_pay', 1, 1, NULL, NULL, 'nearest', 'all', NULL, '2025-04-29 15:50:00', '2025-04-29 15:50:00'),
(10, NULL, 'Nigeria', 'Nigeria NHIS', 'nigeria-nhis', 'National Health Insurance Scheme, 5% of basic salary (2024 rates)', 'rate', 'basic_pay', 0, 1, NULL, NULL, 'nearest', 'all', 'basic_pay * 0.05', '2025-04-29 15:50:00', '2025-04-29 15:50:00'),
(11, NULL, 'Nigeria', 'Nigeria Pension', 'nigeria-pension', 'Mandatory pension contribution, 8% of basic salary, housing, and transport allowances (2024 rates)', 'rate', 'gross_pay', 0, 1, NULL, NULL, 'nearest', 'all', 'gross_pay * 0.08', '2025-04-29 15:50:00', '2025-04-29 15:50:00'),
(13, NULL, 'Uganda', 'Uganda NSSF', 'uganda-nssf', 'National Social Security Fund, 5% of gross pay (2024 rates)', 'rate', 'gross_pay', 0, 1, 5.00, NULL, 'nearest', 'all', NULL, '2025-04-29 15:50:00', '2026-01-23 09:10:05'),
(14, NULL, 'Tanzania', 'Tanzania PAYE', 'tanzania-paye', 'Pay As You Earn (PAYE) tax for Tanzania, based on 2024 TRA tax bands (TZS, monthly)', 'progressive', 'taxable_pay', 1, 1, NULL, NULL, 'nearest', 'all', NULL, '2025-04-29 15:50:00', '2025-04-29 15:50:00'),
(15, NULL, 'Tanzania', 'Tanzania NSSF', 'tanzania-nssf', 'National Social Security Fund, 10% of gross pay (2024 rates)', 'rate', 'gross_pay', 0, 1, NULL, NULL, 'nearest', 'all', 'gross_pay * 0.10', '2025-04-29 15:50:00', '2025-04-29 15:50:00'),
(16, NULL, 'Rwanda', 'Rwanda PAYE', 'rwanda-paye', 'Pay As You Earn (PAYE) tax for Rwanda, based on 2024 RRA tax bands (RWF, monthly)', 'progressive', 'taxable_pay', 1, 1, NULL, NULL, 'nearest', 'all', NULL, '2025-04-29 15:50:00', '2025-04-29 15:50:00'),
(17, NULL, 'Rwanda', 'Rwanda RSSB Pension', 'rwanda-rssb-pension', 'Rwanda Social Security Board pension, 3% of gross pay (2024 rates)', 'rate', 'gross_pay', 0, 1, NULL, NULL, 'nearest', 'all', 'gross_pay * 0.03', '2025-04-29 15:50:00', '2025-04-29 15:50:00'),
(18, NULL, 'Senegal', 'Senegal PAYE', 'senegal-paye', 'Income tax (IR) for Senegal, based on 2024 DGID tax bands (XOF, monthly)', 'progressive', 'taxable_pay', 1, 1, NULL, NULL, 'nearest', 'all', NULL, '2025-04-29 15:50:00', '2025-04-29 15:50:00'),
(19, NULL, 'Senegal', 'Senegal CSS', 'senegal-css', 'Caisse de Sécurité Sociale, 7% of gross pay up to XOF 636,000 (2024 rates)', 'rate', 'gross_pay', 0, 1, NULL, 636000.00, 'nearest', 'all', 'gross_pay * 0.07', '2025-04-29 15:50:00', '2025-04-29 15:50:00'),
(20, NULL, 'South Africa', 'South Africa PAYE', 'south-africa-paye', 'Pay As You Earn (PAYE) tax for South Africa, based on 2024 SARS tax bands (ZAR, monthly)', 'progressive', 'taxable_pay', 1, 1, NULL, NULL, 'nearest', 'all', NULL, '2025-04-29 15:50:00', '2025-04-29 15:50:00'),
(21, NULL, 'South Africa', 'South Africa UIF', 'south-africa-uif', 'Unemployment Insurance Fund, 1% of gross pay up to ZAR 17,712 (2024 rates)', 'rate', 'gross_pay', 0, 1, NULL, 17712.00, 'nearest', 'all', 'gross_pay * 0.01', '2025-04-29 15:50:00', '2025-04-29 15:50:00'),
(22, NULL, 'Ethiopia', 'Ethiopia PAYE', 'ethiopia-paye', 'Income tax for Ethiopia, based on 2024 ERCA tax bands (ETB, monthly)', 'progressive', 'taxable_pay', 1, 1, NULL, NULL, 'nearest', 'all', NULL, '2025-04-29 15:50:00', '2025-04-29 15:50:00'),
(23, NULL, 'Ethiopia', 'Ethiopia Pension', 'ethiopia-pension', 'Mandatory pension contribution, 7% of gross pay (2024 rates)', 'rate', 'gross_pay', 0, 1, NULL, NULL, 'nearest', 'all', 'gross_pay * 0.07', '2025-04-29 15:50:00', '2025-04-29 15:50:00'),
(38, 2, 'Kenya', 'NSSF', 'nssf', 'a total rate of 12% of pensionable earnings, split equally as 6% from the employee and 6% matched by the employer. Deductions are divided into Tier I (up to KES 9,000) and Tier II (KES 9,001 to KES 108,000) based on current limit', 'progressive', 'gross_pay', 1, 1, NULL, 6480.00, 'nearest', 'all', NULL, '2026-08-21 09:44:35', '2026-08-21 09:44:35');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_formula_brackets`
--

CREATE TABLE `payroll_formula_brackets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payroll_formula_id` bigint(20) UNSIGNED NOT NULL,
  `min` decimal(15,2) NOT NULL,
  `max` decimal(15,2) DEFAULT NULL,
  `rate` decimal(5,2) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_formula_brackets`
--

INSERT INTO `payroll_formula_brackets` (`id`, `payroll_formula_id`, `min`, `max`, `rate`, `amount`, `created_at`, `updated_at`) VALUES
(7, 38, 0.00, 9000.00, 6.00, 540.00, '2026-08-21 09:48:10', '2026-08-21 09:48:10');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_settings`
--

CREATE TABLE `payroll_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `allowances` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowances`)),
  `deductions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deductions`)),
  `reliefs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reliefs`)),
  `overtime` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`overtime`)),
  `loans` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`loans`)),
  `advances` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`advances`)),
  `absenteeism_charge` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_settings`
--

INSERT INTO `payroll_settings` (`id`, `employee_id`, `year`, `month`, `allowances`, `deductions`, `reliefs`, `overtime`, `loans`, `advances`, `absenteeism_charge`, `created_at`, `updated_at`) VALUES
(160, 20, 2025, 4, '{\"1\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":3000,\"rate\":0,\"is_active\":true},\"4\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":10,\"is_active\":true},\"5\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":5,\"is_active\":true},\"3\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":5000,\"rate\":0,\"is_active\":true},\"4\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":2000,\"rate\":0,\"is_active\":true},\"5\":{\"user_id\":45,\"employee_code\":\"EMP9234\",\"name\":\"Paul Mukami\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-04-01 08:25:55', '2025-04-03 17:36:57'),
(161, 15, 2025, 4, '{\"1\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":15000,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":7000,\"rate\":0,\"is_active\":true}}', '{\"1\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":2000,\"rate\":0,\"is_active\":true},\"4\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":39,\"employee_code\":\"EMP023\",\"name\":\"Prudence Peru\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-04-03 13:01:20', '2025-04-03 13:01:20'),
(162, 12, 2025, 4, '{\"1\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":15000,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":7000,\"rate\":0,\"is_active\":true}}', '{\"1\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":5000,\"rate\":0,\"is_active\":true}}', '{\"1\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":3000,\"rate\":0,\"is_active\":true},\"3\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":36,\"employee_code\":\"EMP900\",\"name\":\"Georgina Patrick\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-04-07 03:50:00', '2025-04-07 03:50:00'),
(163, 24, 2025, 6, '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '{\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Advance on 2025-06-18\",\"item_id\":4,\"amount\":40000,\"rate\":0,\"is_active\":true}}', 0.00, '2025-06-17 06:46:13', '2025-06-17 06:46:13'),
(164, 32, 2025, 6, '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-06-17 06:46:13', '2025-06-17 06:46:13'),
(165, 35, 2025, 6, '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-06-17 06:46:13', '2025-06-17 06:46:13'),
(166, 40, 2025, 6, '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-06-17 06:46:13', '2025-06-17 06:46:13'),
(167, 42, 2025, 6, '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-06-17 06:46:13', '2025-06-17 06:46:13'),
(168, 43, 2025, 6, '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-06-17 06:46:13', '2025-06-17 06:46:13'),
(169, 24, 2025, 2, '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":500,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":500,\"rate\":0,\"is_active\":true}}', '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":30000,\"rate\":0,\"is_active\":true},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '{\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Advance on 2025-06-18\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false}}', 0.00, '2025-08-21 07:20:47', '2025-08-21 07:34:14'),
(170, 32, 2025, 2, '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:20:47', '2025-08-21 07:20:47'),
(171, 35, 2025, 2, '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:20:47', '2025-08-21 07:20:47'),
(172, 40, 2025, 2, '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:20:47', '2025-08-21 07:20:47'),
(173, 42, 2025, 2, '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:20:47', '2025-08-21 07:20:47'),
(174, 43, 2025, 2, '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:20:47', '2025-08-21 07:20:47');
INSERT INTO `payroll_settings` (`id`, `employee_id`, `year`, `month`, `allowances`, `deductions`, `reliefs`, `overtime`, `loans`, `advances`, `absenteeism_charge`, `created_at`, `updated_at`) VALUES
(175, 46, 2025, 2, '{\"1\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:20:47', '2025-08-21 07:20:47'),
(176, 47, 2025, 2, '{\"1\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:20:47', '2025-08-21 07:20:47'),
(177, 49, 2025, 2, '{\"1\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:20:47', '2025-08-21 07:20:47'),
(178, 50, 2025, 2, '{\"1\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:20:47', '2025-08-21 07:20:47'),
(179, 51, 2025, 2, '{\"1\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:20:47', '2025-08-21 07:20:47'),
(180, 24, 2025, 8, '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":5,\"is_active\":true},\"6\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":2000,\"rate\":0,\"is_active\":true}}', '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":2000,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":500,\"rate\":0,\"is_active\":true}}', '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":30000,\"rate\":0,\"is_active\":true},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '{\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Advance on 2025-06-18\",\"item_id\":4,\"amount\":30000,\"rate\":0,\"is_active\":true}}', 0.00, '2025-08-21 07:41:38', '2025-08-21 12:52:47'),
(181, 32, 2025, 8, '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:41:38', '2025-08-21 12:51:43'),
(182, 35, 2025, 8, '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:41:38', '2025-08-21 12:51:44'),
(183, 40, 2025, 8, '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:41:38', '2025-08-21 12:51:44'),
(184, 42, 2025, 8, '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:41:38', '2025-08-21 12:51:44'),
(185, 43, 2025, 8, '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:41:38', '2025-08-21 12:51:44'),
(186, 46, 2025, 8, '{\"1\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:41:38', '2025-08-21 12:51:44'),
(187, 47, 2025, 8, '{\"1\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:41:38', '2025-08-21 12:51:44'),
(188, 49, 2025, 8, '{\"1\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:41:38', '2025-08-21 12:51:44');
INSERT INTO `payroll_settings` (`id`, `employee_id`, `year`, `month`, `allowances`, `deductions`, `reliefs`, `overtime`, `loans`, `advances`, `absenteeism_charge`, `created_at`, `updated_at`) VALUES
(189, 50, 2025, 8, '{\"1\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:41:38', '2025-08-21 12:51:44'),
(190, 51, 2025, 8, '{\"1\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"7\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"doctor\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"sacco shares\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-08-21 07:41:38', '2025-08-21 12:51:44'),
(191, 24, 2025, 9, '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":15000,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":3000,\"rate\":0,\"is_active\":true},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":500,\"rate\":0,\"is_active\":true}}', '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":10000,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":5000,\"rate\":0,\"is_active\":true}}', '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":30000,\"rate\":0,\"is_active\":true},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '{\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Advance on 2025-06-18\",\"item_id\":4,\"amount\":30000,\"rate\":0,\"is_active\":true}}', 0.00, '2025-09-03 12:27:16', '2025-09-09 12:38:31'),
(192, 32, 2025, 9, '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-09-03 12:27:16', '2025-09-08 06:52:30'),
(193, 35, 2025, 9, '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-09-03 12:27:16', '2025-09-08 06:52:31'),
(194, 40, 2025, 9, '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-09-03 12:27:16', '2025-09-08 06:52:31'),
(195, 42, 2025, 9, '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-09-03 12:27:16', '2025-09-08 06:52:31'),
(196, 43, 2025, 9, '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-09-03 12:27:16', '2025-09-08 06:52:31'),
(197, 46, 2025, 9, '{\"1\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-09-03 12:27:16', '2025-09-08 06:52:31'),
(198, 47, 2025, 9, '{\"1\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-09-03 12:27:16', '2025-09-08 06:52:31'),
(199, 49, 2025, 9, '{\"1\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-09-03 12:27:16', '2025-09-08 06:52:31'),
(200, 50, 2025, 9, '{\"1\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-09-03 12:27:16', '2025-09-08 06:52:31'),
(201, 51, 2025, 9, '{\"1\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"wfedgrt\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2025-09-03 12:27:16', '2025-09-08 06:52:31'),
(202, 60, 2026, 1, '{\"10\":{\"user_id\":429,\"employee_code\":\"1\",\"name\":\"kawera Munyamu\",\"item_name\":\"Transport Allowance\",\"item_id\":10,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '{\"1\":{\"user_id\":429,\"employee_code\":\"1\",\"name\":\"kawera Munyamu\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true}}', '[]', '[]', '[]', 0.00, '2026-01-23 11:44:48', '2026-01-23 14:01:18'),
(203, 24, 2026, 1, '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '{\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Advance on 2025-06-18\",\"item_id\":4,\"amount\":30000,\"rate\":0,\"is_active\":true}}', 0.00, '2026-01-29 13:48:24', '2026-02-20 07:22:24');
INSERT INTO `payroll_settings` (`id`, `employee_id`, `year`, `month`, `allowances`, `deductions`, `reliefs`, `overtime`, `loans`, `advances`, `absenteeism_charge`, `created_at`, `updated_at`) VALUES
(204, 24, 2026, 2, '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":15000,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '{\"4\":{\"user_id\":94,\"employee_code\":\"01\",\"name\":\"Tracy Miranja\",\"item_name\":\"Advance on 2025-06-18\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false}}', 0.00, '2026-02-18 09:22:04', '2026-02-18 09:23:14'),
(205, 32, 2026, 2, '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":5000,\"rate\":0,\"is_active\":true},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:04', '2026-02-18 09:22:04'),
(206, 35, 2026, 2, '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":5000,\"rate\":0,\"is_active\":true}}', '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:04', '2026-02-18 09:22:04'),
(207, 40, 2026, 2, '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":3000,\"rate\":0,\"is_active\":true},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:04', '2026-02-18 09:22:04'),
(208, 42, 2026, 2, '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:04', '2026-02-18 09:22:04'),
(209, 43, 2026, 2, '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:04', '2026-02-18 09:22:04'),
(210, 46, 2026, 2, '{\"1\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:05', '2026-02-18 09:22:05'),
(211, 47, 2026, 2, '{\"1\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:05', '2026-02-18 09:22:05'),
(212, 49, 2026, 2, '{\"1\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:05', '2026-02-18 09:22:05'),
(213, 50, 2026, 2, '{\"1\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:05', '2026-02-18 09:22:05'),
(214, 51, 2026, 2, '{\"1\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:05', '2026-02-18 09:22:05'),
(215, 52, 2026, 2, '{\"1\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:05', '2026-02-18 09:22:05'),
(216, 54, 2026, 2, '{\"1\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:05', '2026-02-18 09:22:05'),
(217, 55, 2026, 2, '{\"1\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:05', '2026-02-18 09:22:05'),
(218, 56, 2026, 2, '{\"1\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:05', '2026-02-18 09:22:05');
INSERT INTO `payroll_settings` (`id`, `employee_id`, `year`, `month`, `allowances`, `deductions`, `reliefs`, `overtime`, `loans`, `advances`, `absenteeism_charge`, `created_at`, `updated_at`) VALUES
(219, 58, 2026, 2, '{\"1\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:05', '2026-02-18 09:22:05'),
(220, 61, 2026, 2, '{\"1\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-18 09:22:05', '2026-02-18 09:22:05'),
(221, 32, 2026, 1, '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":106,\"employee_code\":\"03\",\"name\":\"Dickson Abukal\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24'),
(222, 35, 2026, 1, '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":136,\"employee_code\":\"003\",\"name\":\"Annet wanjiru\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24'),
(223, 40, 2026, 1, '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":141,\"employee_code\":\"56783\",\"name\":\"Leah Gitau\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24'),
(224, 42, 2026, 1, '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":150,\"employee_code\":\"04\",\"name\":\"Maria Wairagu\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24'),
(225, 43, 2026, 1, '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":152,\"employee_code\":\"05\",\"name\":\"stacy george\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24'),
(226, 46, 2026, 1, '{\"1\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":171,\"employee_code\":\"233\",\"name\":\"George oracha\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24'),
(227, 47, 2026, 1, '{\"1\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":175,\"employee_code\":\"12\",\"name\":\"James Kinyala\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24'),
(228, 49, 2026, 1, '{\"1\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":414,\"employee_code\":\"56780\",\"name\":\"Joseph Mutemi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24'),
(229, 50, 2026, 1, '{\"1\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":415,\"employee_code\":\"030\",\"name\":\"Gladys Karimi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24'),
(230, 51, 2026, 1, '{\"1\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":416,\"employee_code\":\"56789\",\"name\":\"Hellen Akinyi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24'),
(231, 52, 2026, 1, '{\"1\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":417,\"employee_code\":\"EMP001\",\"name\":\"John Smith\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24'),
(232, 54, 2026, 1, '{\"1\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":419,\"employee_code\":\"033\",\"name\":\"Gladys Karimi\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24'),
(233, 55, 2026, 1, '{\"1\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":422,\"employee_code\":\"012345\",\"name\":\"Joseph Mutemi Munyao\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24');
INSERT INTO `payroll_settings` (`id`, `employee_id`, `year`, `month`, `allowances`, `deductions`, `reliefs`, `overtime`, `loans`, `advances`, `absenteeism_charge`, `created_at`, `updated_at`) VALUES
(234, 56, 2026, 1, '{\"1\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":423,\"employee_code\":\"090000\",\"name\":\"tracyee ree\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24'),
(235, 58, 2026, 1, '{\"1\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":426,\"employee_code\":\"090100\",\"name\":\"munyoki john\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24'),
(236, 61, 2026, 1, '{\"1\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Housing Allowance\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Transport Allowance\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Meal Allowance\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Performance Bonus\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Overtime Allowance\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Medical Allowance\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false},\"9\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"sacco\",\"item_id\":9,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"HELB Loan Repayment\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":false},\"2\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Sacco Contribution\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Employer Loan Repayment\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Union Dues\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Welfare Fund\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false},\"6\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Custom Loan Deduction\",\"item_id\":6,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Personal Relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Insurance Relief\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false},\"3\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Mortgage Interest Relief\",\"item_id\":3,\"amount\":0,\"rate\":0,\"is_active\":false},\"4\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Home Ownership Savings Plan Relief\",\"item_id\":4,\"amount\":0,\"rate\":0,\"is_active\":false},\"5\":{\"user_id\":431,\"employee_code\":\"56784\",\"name\":\"Sandra Ingado\",\"item_name\":\"Disabled Person Relief\",\"item_id\":5,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-02-20 07:22:24', '2026-02-20 07:22:24'),
(237, 11, 2026, 2, '[]', '{\"7\":{\"user_id\":17,\"employee_code\":\"090000\",\"name\":\"John Mwangi\",\"item_name\":\"HELB Loan\",\"item_id\":7,\"amount\":4000,\"rate\":0,\"is_active\":true}}', '{\"1\":{\"user_id\":17,\"employee_code\":\"090000\",\"name\":\"John Mwangi\",\"item_name\":\"Unknown Item (ID: 1)\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true}}', '[]', '[]', '[]', 0.00, '2026-02-24 11:23:46', '2026-02-24 11:23:46'),
(238, 12, 2026, 2, '[]', '{\"7\":{\"user_id\":19,\"employee_code\":\"090001\",\"name\":\"Aisha Ali\",\"item_name\":\"HELB Loan\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":19,\"employee_code\":\"090001\",\"name\":\"Aisha Ali\",\"item_name\":\"Unknown Item (ID: 1)\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true}}', '[]', '[]', '[]', 0.00, '2026-02-24 11:23:46', '2026-02-24 11:23:46'),
(239, 13, 2026, 2, '[]', '{\"7\":{\"user_id\":20,\"employee_code\":\"090002\",\"name\":\"Peter Otieno\",\"item_name\":\"HELB Loan\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":20,\"employee_code\":\"090002\",\"name\":\"Peter Otieno\",\"item_name\":\"Unknown Item (ID: 1)\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true}}', '[]', '[]', '[]', 0.00, '2026-02-24 11:23:46', '2026-02-24 11:23:46'),
(240, 14, 2026, 2, '[]', '{\"7\":{\"user_id\":21,\"employee_code\":\"090003\",\"name\":\"grace Wanjiku\",\"item_name\":\"HELB Loan\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":21,\"employee_code\":\"090003\",\"name\":\"grace Wanjiku\",\"item_name\":\"Unknown Item (ID: 1)\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true}}', '[]', '[]', '[]', 0.00, '2026-02-24 11:23:46', '2026-02-24 11:23:46'),
(241, 14, 2026, 3, '[]', '{\"7\":{\"user_id\":21,\"employee_code\":\"090003\",\"name\":\"grace Wanjiku\",\"item_name\":\"HELB Loan\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":21,\"employee_code\":\"090003\",\"name\":\"grace Wanjiku\",\"item_name\":\"pension\",\"item_id\":8,\"amount\":0,\"rate\":10,\"is_active\":true}}', '{\"1\":{\"user_id\":21,\"employee_code\":\"090003\",\"name\":\"grace Wanjiku\",\"item_name\":\"personal relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":21,\"employee_code\":\"090003\",\"name\":\"grace Wanjiku\",\"item_name\":\"tracy_miranja\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-03-20 11:27:13', '2026-03-20 11:27:13'),
(242, 11, 2026, 4, '[]', '{\"7\":{\"user_id\":17,\"employee_code\":\"090000\",\"name\":\"John Mwangi\",\"item_name\":\"HELB Loan\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":17,\"employee_code\":\"090000\",\"name\":\"John Mwangi\",\"item_name\":\"pension\",\"item_id\":8,\"amount\":0,\"rate\":10,\"is_active\":true}}', '{\"1\":{\"user_id\":17,\"employee_code\":\"090000\",\"name\":\"John Mwangi\",\"item_name\":\"personal relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":17,\"employee_code\":\"090000\",\"name\":\"John Mwangi\",\"item_name\":\"tracy_miranja\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-04-25 05:47:01', '2026-04-25 05:47:01'),
(243, 12, 2026, 4, '[]', '{\"7\":{\"user_id\":19,\"employee_code\":\"090001\",\"name\":\"Aisha Ali\",\"item_name\":\"HELB Loan\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":19,\"employee_code\":\"090001\",\"name\":\"Aisha Ali\",\"item_name\":\"pension\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":19,\"employee_code\":\"090001\",\"name\":\"Aisha Ali\",\"item_name\":\"personal relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":19,\"employee_code\":\"090001\",\"name\":\"Aisha Ali\",\"item_name\":\"tracy_miranja\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-04-25 05:47:01', '2026-04-25 05:47:01'),
(244, 13, 2026, 4, '[]', '{\"7\":{\"user_id\":20,\"employee_code\":\"090002\",\"name\":\"Peter Otieno\",\"item_name\":\"HELB Loan\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":20,\"employee_code\":\"090002\",\"name\":\"Peter Otieno\",\"item_name\":\"pension\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":20,\"employee_code\":\"090002\",\"name\":\"Peter Otieno\",\"item_name\":\"personal relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":20,\"employee_code\":\"090002\",\"name\":\"Peter Otieno\",\"item_name\":\"tracy_miranja\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-04-25 05:47:01', '2026-04-25 05:47:01'),
(245, 14, 2026, 4, '[]', '{\"7\":{\"user_id\":21,\"employee_code\":\"090003\",\"name\":\"grace Wanjiku\",\"item_name\":\"HELB Loan\",\"item_id\":7,\"amount\":0,\"rate\":0,\"is_active\":false},\"8\":{\"user_id\":21,\"employee_code\":\"090003\",\"name\":\"grace Wanjiku\",\"item_name\":\"pension\",\"item_id\":8,\"amount\":0,\"rate\":0,\"is_active\":false}}', '{\"1\":{\"user_id\":21,\"employee_code\":\"090003\",\"name\":\"grace Wanjiku\",\"item_name\":\"personal relief\",\"item_id\":1,\"amount\":0,\"rate\":0,\"is_active\":true},\"2\":{\"user_id\":21,\"employee_code\":\"090003\",\"name\":\"grace Wanjiku\",\"item_name\":\"tracy_miranja\",\"item_id\":2,\"amount\":0,\"rate\":0,\"is_active\":false}}', '[]', '[]', '[]', 0.00, '2026-04-25 05:47:01', '2026-04-25 05:47:01'),
(246, 13, 2026, 8, '[]', '[]', '{\"1\":{\"user_id\":20,\"employee_code\":\"090002\",\"name\":\"Peter Otieno\",\"item_name\":\"personal relief\",\"item_id\":1,\"amount\":2400,\"rate\":0,\"is_active\":true}}', '[]', '[]', '[]', 0.00, '2026-08-11 11:15:44', '2026-08-11 11:15:44');

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

CREATE TABLE `payslips` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `payroll_id` bigint(20) UNSIGNED NOT NULL,
  `file` varchar(255) NOT NULL,
  `month` varchar(255) NOT NULL,
  `year` varchar(255) NOT NULL,
  `gross_pay` decimal(12,2) NOT NULL,
  `net_pay` decimal(12,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pay_grades`
--

CREATE TABLE `pay_grades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `job_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_cycles`
--

CREATE TABLE `performance_cycles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `kpi_weight` decimal(5,2) NOT NULL DEFAULT 40.00,
  `okr_weight` decimal(5,2) NOT NULL DEFAULT 40.00,
  `competency_weight` decimal(5,2) NOT NULL DEFAULT 20.00,
  `status` enum('draft','active','closed') NOT NULL DEFAULT 'draft',
  `lock_goals_on_start` tinyint(1) NOT NULL DEFAULT 1,
  `self_review_due_date` date DEFAULT NULL,
  `manager_review_due_date` date DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `performance_cycles`
--

INSERT INTO `performance_cycles` (`id`, `business_id`, `name`, `description`, `start_date`, `end_date`, `kpi_weight`, `okr_weight`, `competency_weight`, `status`, `lock_goals_on_start`, `self_review_due_date`, `manager_review_due_date`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 2, 'system', NULL, '2026-08-12', '2027-01-12', 40.00, 40.00, 20.00, 'active', 1, '2026-10-12', '2026-10-28', 9, '2026-08-12 09:01:43', '2026-08-12 09:01:50');

-- --------------------------------------------------------

--
-- Table structure for table `performance_feedback_requests`
--

CREATE TABLE `performance_feedback_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `performance_cycle_id` bigint(20) UNSIGNED NOT NULL,
  `subject_employee_id` bigint(20) UNSIGNED NOT NULL,
  `reviewer_employee_id` bigint(20) UNSIGNED NOT NULL,
  `requested_by` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','submitted','declined') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_feedback_responses`
--

CREATE TABLE `performance_feedback_responses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `performance_feedback_request_id` bigint(20) UNSIGNED NOT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`answers`)),
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_key_results`
--

CREATE TABLE `performance_key_results` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `performance_objective_id` bigint(20) UNSIGNED NOT NULL,
  `description` varchar(255) NOT NULL,
  `target_value` decimal(12,2) NOT NULL DEFAULT 100.00,
  `current_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(255) DEFAULT NULL,
  `weight` decimal(5,2) NOT NULL DEFAULT 100.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_objectives`
--

CREATE TABLE `performance_objectives` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `performance_cycle_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `scope` enum('company','department','individual') NOT NULL DEFAULT 'individual',
  `parent_objective_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `weight` decimal(5,2) NOT NULL DEFAULT 100.00,
  `status` enum('on_track','at_risk','off_track','completed') NOT NULL DEFAULT 'on_track',
  `confidence` enum('on_track','at_risk','critical') NOT NULL DEFAULT 'on_track',
  `final_score` decimal(3,2) DEFAULT NULL,
  `alignment_status` enum('draft','proposed','approved') NOT NULL DEFAULT 'approved',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_reviews`
--

CREATE TABLE `performance_reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `performance_cycle_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `reviewer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `kpi_score` decimal(5,2) DEFAULT NULL,
  `okr_score` decimal(5,2) DEFAULT NULL,
  `competency_score` decimal(5,2) DEFAULT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `self_assessment` text DEFAULT NULL,
  `manager_assessment` text DEFAULT NULL,
  `status` enum('pending_self','pending_manager','completed') NOT NULL DEFAULT 'pending_self',
  `self_submitted_at` timestamp NULL DEFAULT NULL,
  `manager_submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personnel_positions`
--

CREATE TABLE `personnel_positions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `previous_employments`
--

CREATE TABLE `previous_employments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `employer_name` varchar(255) NOT NULL,
  `business_or_profession` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `capacity_employed` varchar(255) NOT NULL,
  `reason_for_leaving` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `psychometric_tests`
--

CREATE TABLE `psychometric_tests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `applicant_id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `test_type` varchar(255) NOT NULL,
  `results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`results`)),
  `score` int(11) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reliefs`
--

CREATE TABLE `reliefs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'deductible_after_tax',
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `computation_method` enum('fixed','percentage') NOT NULL DEFAULT 'fixed',
  `greatest_or_least_of` varchar(255) NOT NULL DEFAULT 'least',
  `amount` decimal(10,2) NOT NULL,
  `actual_amount` tinyint(1) NOT NULL DEFAULT 0,
  `percentage_of_amount` decimal(5,2) DEFAULT NULL,
  `percentage_of` varchar(255) DEFAULT NULL,
  `fraction_to_consider` varchar(255) NOT NULL DEFAULT 'employee_only',
  `limit` decimal(10,2) DEFAULT NULL,
  `round_off` varchar(255) NOT NULL DEFAULT 'round_off_up',
  `decimal_places` tinyint(4) NOT NULL DEFAULT 2,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reliefs`
--

INSERT INTO `reliefs` (`id`, `business_id`, `type`, `location_id`, `name`, `slug`, `description`, `computation_method`, `greatest_or_least_of`, `amount`, `actual_amount`, `percentage_of_amount`, `percentage_of`, `fraction_to_consider`, `limit`, `round_off`, `decimal_places`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 2, 'deductible_after_tax', NULL, 'personal relief', 'personal-relief', 'relief', 'fixed', 'least', 2400.00, 0, NULL, 'basic_salary', 'employee_only', NULL, 'round_off_up', 2, 1, '2026-03-02 14:09:41', '2026-03-02 14:09:41'),
(2, 2, 'deductible_after_tax', NULL, 'tracy_miranja', 'tracy-miranja', 'fixed', 'fixed', 'least', 2400.00, 0, NULL, 'basic_salary', 'employee_only', NULL, 'round_off_up', 2, 1, '2026-03-02 14:10:38', '2026-03-02 14:10:38');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `business_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `business_id`, `created_at`, `updated_at`) VALUES
(7, 'business-admin', 'web', 2, '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(8, 'business-hr', 'web', 2, '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(9, 'business-finance', 'web', 2, '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(10, 'business-it', 'web', 2, '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(11, 'business-employee', 'web', 2, '2025-02-04 00:34:17', '2025-04-24 22:38:16'),
(12, 'applicant', 'web', 2, '2025-02-04 00:34:17', '2025-02-04 00:34:17'),
(13, 'super-admin', 'web', NULL, '2026-08-10 08:56:03', '2026-08-10 08:56:03'),
(14, 'krest-admin', 'web', NULL, '2026-08-12 05:25:57', '2026-08-12 05:25:57');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('KKCBQHKwVJYWhv1XnuAwu2FDMCAqXryKPDXQjkL5', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSk8zdFUwYzR2U0gwcXJlQzBoeVBtdFJpMncwZ1YwUnZSQXNISmJTVSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7fX0=', 1787303612),
('mrEGEfSFu0kd6ndgZ1QNEpWa3HZWp5Y8rNACm8hq', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiNG1ZQ0prbjJNaDBtdWRiRGI5Q29PWlV6YmhkRmlnd0gzem1Gdkt1UyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1787307537),
('oUZirc30ktIsQe5X33dcjGXNYFm5RRPN0K87mYQk', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiQ2F1blBoOGlMWmVGMDMwNmtjWlNlY0RRbmpieDFRQkh3ZmlPekFZNCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1787307536),
('XGiIM1dfr0zZfslbjVAiD6H0jTsCIlNGz8hn5Otp', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiMWpmeVp0Nmx5WjZlWkpkZVVvQTJJSFhVaTBVRUdBeHI1OVN5TnZPVSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1787307537),
('y3CIpdrXoDDZ3mAXXWR3I97exOfZk0ai9mkNcEny', 9, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo3OntzOjY6Il90b2tlbiI7czo0MDoiaGk2UDliWjQ2SlFNWTZpdnp5UG53ZGptY3VuTFJ0NWczTFFCZ0w3eCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9idXNpbmVzcy9rcmVzdC9wYXlyb2xsLWZvcm11bGFzIjt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6OTtzOjEyOiIyZmFfdmVyaWZpZWQiO2I6MTtzOjIwOiJhY3RpdmVfYnVzaW5lc3Nfc2x1ZyI7czo1OiJrcmVzdCI7czoxMToiYWN0aXZlX3JvbGUiO3M6MTE6ImtyZXN0LWFkbWluIjt9', 1787310134);

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spouses`
--

CREATE TABLE `spouses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `surname` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `national_id` varchar(255) NOT NULL,
  `current_employer` varchar(255) DEFAULT NULL,
  `spouse_contact` varchar(255) DEFAULT NULL,
  `spouse_postal_address` varchar(255) DEFAULT NULL,
  `spouse_physical_address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `statuses`
--

CREATE TABLE `statuses` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `reason` text DEFAULT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statuses`
--

INSERT INTO `statuses` (`id`, `name`, `reason`, `model_type`, `model_id`, `created_at`, `updated_at`) VALUES
(5, 'setup', NULL, 'App\\Models\\User', 9, '2026-01-20 17:55:24', '2026-01-20 17:55:24'),
(6, 'module', NULL, 'App\\Models\\Business', 3, '2026-01-20 18:30:34', '2026-01-20 18:30:34'),
(7, 'module', NULL, 'App\\Models\\User', 9, '2026-01-20 18:30:34', '2026-01-20 18:30:34'),
(8, 'active', NULL, 'App\\Models\\User', 9, '2026-01-20 18:33:21', '2026-01-20 18:33:21'),
(9, 'active', NULL, 'App\\Models\\Business', 2, '2026-01-20 18:33:21', '2026-01-20 18:33:21'),
(10, 'active', NULL, 'App\\Models\\Department', 16, '2026-01-20 19:20:06', '2026-01-20 19:20:06'),
(11, 'active', NULL, 'App\\Models\\Department', 17, '2026-01-20 19:20:51', '2026-01-20 19:20:51'),
(12, 'active', NULL, 'App\\Models\\Department', 18, '2026-01-20 19:21:15', '2026-01-20 19:21:15'),
(13, 'active', NULL, 'App\\Models\\Department', 19, '2026-01-20 19:21:35', '2026-01-20 19:21:35'),
(14, 'active', NULL, 'App\\Models\\Department', 20, '2026-01-20 19:22:07', '2026-01-20 19:22:07'),
(15, 'active', NULL, 'App\\Models\\Department', 21, '2026-01-20 19:22:25', '2026-01-20 19:22:25'),
(16, 'active', NULL, 'App\\Models\\Department', 22, '2026-01-20 19:22:40', '2026-01-20 19:22:40'),
(17, 'active', NULL, 'App\\Models\\Department', 23, '2026-01-20 19:22:54', '2026-01-20 19:22:54'),
(18, 'active', NULL, 'App\\Models\\Department', 24, '2026-01-20 19:23:07', '2026-01-20 19:23:07'),
(19, 'active', NULL, 'App\\Models\\Department', 25, '2026-01-20 19:24:12', '2026-01-20 19:24:12'),
(20, 'active', NULL, 'App\\Models\\JobCategory', 1, '2026-01-20 19:30:12', '2026-01-20 19:30:12'),
(21, 'active', NULL, 'App\\Models\\JobCategory', 2, '2026-01-20 19:30:25', '2026-01-20 19:30:25'),
(22, 'active', NULL, 'App\\Models\\JobCategory', 3, '2026-01-20 19:30:39', '2026-01-20 19:30:39'),
(23, 'active', NULL, 'App\\Models\\JobCategory', 4, '2026-01-20 19:33:20', '2026-01-20 19:33:20'),
(24, 'active', NULL, 'App\\Models\\JobCategory', 5, '2026-01-20 19:33:33', '2026-01-20 19:33:33'),
(25, 'active', NULL, 'App\\Models\\JobCategory', 6, '2026-01-20 19:33:46', '2026-01-20 19:33:46'),
(26, 'active', NULL, 'App\\Models\\JobCategory', 7, '2026-01-20 19:33:57', '2026-01-20 19:33:57'),
(27, 'active', NULL, 'App\\Models\\JobCategory', 8, '2026-01-20 19:34:09', '2026-01-20 19:34:09'),
(28, 'active', NULL, 'App\\Models\\JobCategory', 9, '2026-01-20 19:34:20', '2026-01-20 19:34:20'),
(29, 'active', NULL, 'App\\Models\\JobCategory', 10, '2026-01-20 19:34:31', '2026-01-20 19:34:31'),
(30, 'module', NULL, 'App\\Models\\Business', 4, '2026-01-21 19:08:13', '2026-01-21 19:08:13'),
(31, 'module', NULL, 'App\\Models\\User', 9, '2026-01-21 19:08:13', '2026-01-21 19:08:13'),
(32, 'active', NULL, 'App\\Models\\User', 9, '2026-01-21 19:13:01', '2026-01-21 19:13:01'),
(33, 'active', NULL, 'App\\Models\\Business', 2, '2026-01-21 19:13:01', '2026-01-21 19:13:01'),
(34, 'active', NULL, 'App\\Models\\Location', 1, '2026-01-22 10:23:57', '2026-01-22 10:23:57'),
(35, 'active', NULL, 'App\\Models\\Location', 2, '2026-03-02 12:00:17', '2026-03-02 12:00:17'),
(36, 'active', NULL, 'App\\Models\\User', 36, '2026-06-22 10:20:57', '2026-06-22 10:20:57'),
(37, 'active', NULL, 'App\\Models\\Applicant', 1, '2026-06-22 10:21:00', '2026-06-22 10:21:00'),
(38, 'applied', NULL, 'App\\Models\\Application', 1, '2026-06-22 10:27:06', '2026-06-22 10:27:06'),
(39, 'active', NULL, 'App\\Models\\Applicant', 1, '2026-06-22 12:02:43', '2026-06-22 12:02:43'),
(40, 'active', NULL, 'App\\Models\\LeavePeriod', 1, '2026-07-09 14:39:55', '2026-07-09 14:39:55');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `two_factor_codes`
--

CREATE TABLE `two_factor_codes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `provider_token` text DEFAULT NULL,
  `social_id` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `country`, `code`, `provider`, `provider_token`, `social_id`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Krest Admin', 'barazatracy44@gmail.com', '+254712345678', 'Kenya', '+254', NULL, NULL, NULL, NULL, '$2y$12$g.XAPg/eyscy9A2V5Xc.A.Zs8kXrlfkfIAIFB91nUhQieBMXjeUkq', NULL, '2026-01-20 14:06:02', '2026-01-21 19:02:02'),
(9, 'KrestHR', 'miranjatracy@gmail.com', '+254712030232', 'Kenya', '+254769212973', NULL, NULL, NULL, '2026-08-10 08:56:03', '$2y$12$KhlZR7FmAoB5aTxUWfq/H.aYExV5n7cGMICMDhaLfJgtUEIXR0pg2', NULL, '2026-01-20 17:50:14', '2026-08-10 08:56:03'),
(17, 'John Mwangi', 'john.mwangi@example.com', '0727086937', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-22 10:26:31', '2026-01-22 10:26:31'),
(19, 'Aisha Ali', 'aisha.ali@example.com', '0711234567', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-22 10:58:58', '2026-01-22 10:58:58'),
(20, 'Peter Otieno', 'peter.otieno@example.com', '0722345678', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-22 11:02:18', '2026-01-22 11:02:18'),
(21, 'grace Wanjiku', 'grace.wanjiku@example.com', '0733456789', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-22 11:06:17', '2026-01-22 11:06:17'),
(24, 'Krest Works', 'krestworks.tech@gmail.com', '0769212978', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-03 06:22:05', '2026-05-03 06:22:05'),
(25, 'Alice muliga', 'alice@gmail.com', '0769212970', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-03 07:29:02', '2026-05-03 07:29:02'),
(26, 'John Smithe', 'john.smithe@example.com', '254712345670', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-04 06:28:16', '2026-05-04 06:28:16'),
(27, 'ohn mith', 'ohn.mith@example.com', '254702345679', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-04 06:28:48', '2026-05-04 06:28:48'),
(34, 'Alice Wanjiru', 'alice.wanjiru@example.com', '254711000001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-04 07:41:08', '2026-05-04 07:41:08'),
(35, 'Brian Otieno', 'brian.otieno@example.com', '254711000002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-04 07:41:14', '2026-05-04 07:41:14'),
(36, 'tracy  miranja', 'krestworks.tech2@gmail.com', '+769212978', 'kenya', NULL, NULL, NULL, NULL, NULL, '$2y$12$JCoNXxW2pMb8z.wGxUjSI.phvud5S1EfoNo8W/ZObQMgIlZKmyHje', NULL, '2026-06-22 10:20:57', '2026-06-22 10:20:57');

-- --------------------------------------------------------

--
-- Table structure for table `warnings`
--

CREATE TABLE `warnings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `case_id` varchar(255) DEFAULT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `case_type` enum('verbal_warning','written_warning','final_warning','suspension','termination') NOT NULL DEFAULT 'written_warning',
  `severity` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `previous_case_id` bigint(20) UNSIGNED DEFAULT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'misconduct',
  `offence` text DEFAULT NULL,
  `reported_by_name` varchar(255) DEFAULT NULL,
  `stage` varchar(255) NOT NULL DEFAULT 'informal_action',
  `hearing_date` date DEFAULT NULL,
  `decision_outcome` varchar(255) NOT NULL DEFAULT 'pending',
  `appeal_status` varchar(255) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by` bigint(20) UNSIGNED DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `issued_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warnings`
--

INSERT INTO `warnings` (`id`, `case_id`, `employee_id`, `case_type`, `severity`, `previous_case_id`, `business_id`, `category`, `offence`, `reported_by_name`, `stage`, `hearing_date`, `decision_outcome`, `appeal_status`, `issue_date`, `reason`, `description`, `attachment`, `status`, `acknowledged_at`, `acknowledged_by`, `resolution_notes`, `issued_by`, `created_at`, `updated_at`) VALUES
(1, 'c6y99ujns', 16, 'written_warning', 'medium', NULL, 2, 'misconduct', 'lateness', NULL, 'informal_action', NULL, 'pending', NULL, '2026-06-23', 'lateness', 'wafes', NULL, 'active', NULL, NULL, NULL, 9, '2026-06-23 07:44:31', '2026-08-11 10:25:46'),
(2, 'cwzppls9r', 12, 'written_warning', 'medium', NULL, 2, 'misconduct', 'll', NULL, 'informal_action', NULL, 'pending', NULL, '2026-06-23', 'll', NULL, NULL, 'active', NULL, NULL, NULL, 9, '2026-06-23 08:41:59', '2026-06-23 08:41:59'),
(3, 'cym65xjyj', 14, 'written_warning', 'medium', NULL, 2, 'misconduct', 'noise', NULL, 'informal_action', NULL, 'pending', NULL, '2026-06-17', 'noise', NULL, NULL, 'active', NULL, NULL, NULL, 9, '2026-06-23 08:52:56', '2026-06-23 08:52:56');

-- --------------------------------------------------------

--
-- Table structure for table `withholding_payments`
--

CREATE TABLE `withholding_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `payroll_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_payroll_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_type` varchar(255) NOT NULL,
  `residency` varchar(255) NOT NULL,
  `gross_amount` decimal(15,2) NOT NULL,
  `wht_rate` decimal(5,2) NOT NULL,
  `wht_amount` decimal(15,2) NOT NULL,
  `net_amount` decimal(15,2) NOT NULL,
  `shif_company_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `nssf_company_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_company_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_date` date NOT NULL,
  `is_remitted` tinyint(1) NOT NULL DEFAULT 0,
  `remittance_date` date DEFAULT NULL,
  `certificate_no` varchar(255) DEFAULT NULL,
  `currency` varchar(255) NOT NULL DEFAULT 'KES',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `withholding_tax_rates`
--

CREATE TABLE `withholding_tax_rates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_type` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `residency` varchar(255) NOT NULL,
  `rate` decimal(5,2) NOT NULL,
  `is_final_tax` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_schedules`
--

CREATE TABLE `work_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `shift_id` bigint(20) UNSIGNED DEFAULT NULL,
  `business_id` bigint(20) UNSIGNED NOT NULL,
  `working_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`working_days`)),
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_qualifications`
--
ALTER TABLE `academic_qualifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `academic_qualifications_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `access_requests`
--
ALTER TABLE `access_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_req_bus_token` (`requester_id`,`business_id`,`registration_token`),
  ADD KEY `access_requests_business_id_foreign` (`business_id`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject` (`subject_type`,`subject_id`),
  ADD KEY `causer` (`causer_type`,`causer_id`),
  ADD KEY `activity_log_log_name_index` (`log_name`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_logs_user_id_foreign` (`user_id`),
  ADD KEY `activity_logs_loggable_type_loggable_id_index` (`loggable_type`,`loggable_id`);

--
-- Indexes for table `advances`
--
ALTER TABLE `advances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `advances_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `allowances`
--
ALTER TABLE `allowances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `allowances_slug_unique` (`slug`);

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicants_created_by_index` (`created_by`),
  ADD KEY `applicants_user_id_foreign` (`user_id`);

--
-- Indexes for table `applicant_skills`
--
ALTER TABLE `applicant_skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_skills_applicant_id_foreign` (`applicant_id`),
  ADD KEY `applicant_skills_skill_id_foreign` (`skill_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applications_applicant_id_foreign` (`applicant_id`),
  ADD KEY `applications_job_post_id_foreign` (`job_post_id`),
  ADD KEY `applications_created_by_foreign` (`created_by`);

--
-- Indexes for table `application_academics`
--
ALTER TABLE `application_academics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_academics_application_id_index` (`application_id`);

--
-- Indexes for table `application_documents`
--
ALTER TABLE `application_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_documents_application_id_doc_type_index` (`application_id`,`doc_type`),
  ADD KEY `application_documents_media_id_index` (`media_id`);

--
-- Indexes for table `application_memberships`
--
ALTER TABLE `application_memberships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_memberships_application_id_index` (`application_id`);

--
-- Indexes for table `application_work_experiences`
--
ALTER TABLE `application_work_experiences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_work_experiences_application_id_index` (`application_id`);

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendances_logged_by_foreign` (`logged_by`),
  ADD KEY `attendances_business_id_date_index` (`business_id`,`date`),
  ADD KEY `attendances_employee_id_date_index` (`employee_id`,`date`);

--
-- Indexes for table `businesses`
--
ALTER TABLE `businesses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `businesses_slug_unique` (`slug`),
  ADD KEY `businesses_user_id_foreign` (`user_id`);

--
-- Indexes for table `business_currencies`
--
ALTER TABLE `business_currencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_currencies_business_id_currency_code_unique` (`business_id`,`currency_code`),
  ADD KEY `business_currencies_business_id_index` (`business_id`);

--
-- Indexes for table `business_modules`
--
ALTER TABLE `business_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_modules_business_id_foreign` (`business_id`),
  ADD KEY `business_modules_module_id_foreign` (`module_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clients_business_id_foreign` (`business_id`),
  ADD KEY `clients_client_business_foreign` (`client_business`);

--
-- Indexes for table `deductions`
--
ALTER TABLE `deductions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `deductions_slug_unique` (`slug`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `departments_slug_unique` (`slug`);

--
-- Indexes for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `emergency_contacts_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employees_business_employee_code_unique` (`business_id`,`employee_code`),
  ADD UNIQUE KEY `employees_business_national_id_unique` (`business_id`,`national_id`),
  ADD UNIQUE KEY `employees_business_tax_no_unique` (`business_id`,`tax_no`),
  ADD UNIQUE KEY `employees_business_nssf_no_unique` (`business_id`,`nssf_no`),
  ADD UNIQUE KEY `employees_business_passport_no_unique` (`business_id`,`passport_no`),
  ADD KEY `employees_user_id_foreign` (`user_id`),
  ADD KEY `employees_manager_id_index` (`manager_id`),
  ADD KEY `employees_organogram_role_id_foreign` (`organogram_role_id`),
  ADD KEY `employees_team_id_foreign` (`team_id`);

--
-- Indexes for table `employee_allowances`
--
ALTER TABLE `employee_allowances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_contact_details`
--
ALTER TABLE `employee_contact_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_contact_details_work_email_unique` (`work_email`),
  ADD KEY `employee_contact_details_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `employee_deductions`
--
ALTER TABLE `employee_deductions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_departments`
--
ALTER TABLE `employee_departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_departments_employee_id_department_id_unique` (`employee_id`,`department_id`),
  ADD KEY `employee_departments_department_id_foreign` (`department_id`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_documents_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `employee_family_members`
--
ALTER TABLE `employee_family_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_family_members_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `employee_payment_details`
--
ALTER TABLE `employee_payment_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_payment_details_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `employee_payrolls`
--
ALTER TABLE `employee_payrolls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_payrolls_employee_payment_detail_id_index` (`employee_payment_detail_id`);

--
-- Indexes for table `employee_payroll_details`
--
ALTER TABLE `employee_payroll_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_payroll_settings`
--
ALTER TABLE `employee_payroll_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_payroll_settings_employee_id_payroll_period_unique` (`employee_id`,`payroll_period`);

--
-- Indexes for table `employee_reliefs`
--
ALTER TABLE `employee_reliefs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_task`
--
ALTER TABLE `employee_task`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_task_task_id_foreign` (`task_id`),
  ADD KEY `employee_task_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `employment_details`
--
ALTER TABLE `employment_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employment_details_employee_id_foreign` (`employee_id`),
  ADD KEY `employment_details_department_id_foreign` (`department_id`),
  ADD KEY `employment_details_job_category_id_foreign` (`job_category_id`),
  ADD KEY `employment_details_shift_id_foreign` (`shift_id`);

--
-- Indexes for table `experiences`
--
ALTER TABLE `experiences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `experiences_applicant_id_foreign` (`applicant_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `holidays_slug_unique` (`slug`),
  ADD KEY `holidays_business_id_date_index` (`business_id`,`date`),
  ADD KEY `holidays_location_id_foreign` (`location_id`);

--
-- Indexes for table `industries`
--
ALTER TABLE `industries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `industries_slug_unique` (`slug`);

--
-- Indexes for table `interviews`
--
ALTER TABLE `interviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `interviews_application_id_foreign` (`application_id`),
  ADD KEY `interviews_interviewer_id_foreign` (`interviewer_id`),
  ADD KEY `interviews_created_by_foreign` (`created_by`);

--
-- Indexes for table `interview_feedback`
--
ALTER TABLE `interview_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `interview_feedback_interview_id_foreign` (`interview_id`),
  ADD KEY `interview_feedback_interviewer_id_foreign` (`interviewer_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_categories`
--
ALTER TABLE `job_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_categories_slug_unique` (`slug`);

--
-- Indexes for table `job_posts`
--
ALTER TABLE `job_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_posts_slug_unique` (`slug`),
  ADD KEY `job_posts_created_by_foreign` (`created_by`);

--
-- Indexes for table `kpis`
--
ALTER TABLE `kpis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kpis_slug_unique` (`slug`),
  ADD KEY `kpis_department_id_foreign` (`department_id`),
  ADD KEY `kpis_job_category_id_foreign` (`job_category_id`),
  ADD KEY `kpis_business_id_foreign` (`business_id`),
  ADD KEY `kpis_location_id_foreign` (`location_id`),
  ADD KEY `kpis_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `kpi_results`
--
ALTER TABLE `kpi_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kpi_results_kpi_id_foreign` (`kpi_id`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lead_activities`
--
ALTER TABLE `lead_activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_delegations`
--
ALTER TABLE `leave_delegations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_delegations_employee_id_foreign` (`employee_id`),
  ADD KEY `leave_delegations_delegate_id_foreign` (`delegate_id`),
  ADD KEY `leave_delegations_leave_request_id_foreign` (`leave_request_id`);

--
-- Indexes for table `leave_entitlements`
--
ALTER TABLE `leave_entitlements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_entitlements_employee_id_foreign` (`employee_id`),
  ADD KEY `leave_entitlements_leave_type_id_foreign` (`leave_type_id`),
  ADD KEY `leave_entitlements_leave_period_id_foreign` (`leave_period_id`);

--
-- Indexes for table `leave_periods`
--
ALTER TABLE `leave_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_periods_slug_unique` (`slug`),
  ADD UNIQUE KEY `leave_periods_business_id_name_unique` (`business_id`,`name`);

--
-- Indexes for table `leave_policies`
--
ALTER TABLE `leave_policies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_policies_leave_type_id_foreign` (`leave_type_id`),
  ADD KEY `leave_policies_department_id_foreign` (`department_id`),
  ADD KEY `leave_policies_job_category_id_foreign` (`job_category_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_requests_reference_number_unique` (`reference_number`),
  ADD KEY `leave_requests_employee_id_foreign` (`employee_id`),
  ADD KEY `leave_requests_business_id_foreign` (`business_id`),
  ADD KEY `leave_requests_leave_type_id_foreign` (`leave_type_id`),
  ADD KEY `leave_requests_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_types_slug_unique` (`slug`);

--
-- Indexes for table `leave_type_lists`
--
ALTER TABLE `leave_type_lists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_type_lists_name_unique` (`name`),
  ADD UNIQUE KEY `leave_type_lists_slug_unique` (`slug`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loans_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_repayments_loan_id_foreign` (`loan_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `locations_slug_unique` (`slug`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `login_attempts_ip_address_index` (`ip_address`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `login_logs_user_id_foreign` (`user_id`);

--
-- Indexes for table `mandatory_leave_periods`
--
ALTER TABLE `mandatory_leave_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mandatory_leave_periods_slug_unique` (`slug`),
  ADD KEY `mandatory_leave_periods_business_id_foreign` (`business_id`),
  ADD KEY `mandatory_leave_periods_leave_type_id_foreign` (`leave_type_id`),
  ADD KEY `mandatory_leave_periods_created_by_foreign` (`created_by`);

--
-- Indexes for table `mandatory_leave_period_deductions`
--
ALTER TABLE `mandatory_leave_period_deductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mlp_deductions_period_id_fk` (`mandatory_leave_period_id`),
  ADD KEY `mandatory_leave_period_deductions_employee_id_foreign` (`employee_id`),
  ADD KEY `mandatory_leave_period_deductions_leave_entitlement_id_foreign` (`leave_entitlement_id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `media_uuid_unique` (`uuid`),
  ADD KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  ADD KEY `media_order_column_index` (`order_column`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `modules_slug_unique` (`slug`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notification_logs_user_id_foreign` (`user_id`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notification_preferences_user_id_foreign` (`user_id`);

--
-- Indexes for table `organogram_positions`
--
ALTER TABLE `organogram_positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `organogram_positions_parent_id_foreign` (`parent_id`),
  ADD KEY `organogram_positions_personnel_position_id_foreign` (`personnel_position_id`),
  ADD KEY `organogram_positions_business_id_parent_id_index` (`business_id`,`parent_id`);

--
-- Indexes for table `organogram_position_holders`
--
ALTER TABLE `organogram_position_holders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_position_holder` (`organogram_position_id`,`employee_id`,`start_date`),
  ADD KEY `organogram_position_holders_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `organogram_roles`
--
ALTER TABLE `organogram_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `organogram_roles_business_id_name_unique` (`business_id`,`name`),
  ADD KEY `organogram_roles_reports_to_role_id_foreign` (`reports_to_role_id`);

--
-- Indexes for table `overtimes`
--
ALTER TABLE `overtimes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `overtimes_employee_id_foreign` (`employee_id`),
  ADD KEY `overtimes_business_id_foreign` (`business_id`),
  ADD KEY `overtimes_approved_by_foreign` (`approved_by`),
  ADD KEY `overtimes_attendance_id_foreign` (`attendance_id`),
  ADD KEY `overtimes_location_id_foreign` (`location_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payrolls`
--
ALTER TABLE `payrolls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_unique` (`payrun_year`,`payrun_month`,`business_id`,`location_id`);

--
-- Indexes for table `payroll_formulas`
--
ALTER TABLE `payroll_formulas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_formulas_slug_unique` (`slug`);

--
-- Indexes for table `payroll_formula_brackets`
--
ALTER TABLE `payroll_formula_brackets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_formula_brackets_payroll_formula_id_foreign` (`payroll_formula_id`);

--
-- Indexes for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_year_month` (`employee_id`,`year`,`month`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payslips_employee_id_foreign` (`employee_id`),
  ADD KEY `payslips_payroll_id_foreign` (`payroll_id`);

--
-- Indexes for table `pay_grades`
--
ALTER TABLE `pay_grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pay_grades_job_category_id_foreign` (`job_category_id`),
  ADD KEY `pay_grades_department_id_foreign` (`department_id`),
  ADD KEY `pay_grades_business_id_foreign` (`business_id`);

--
-- Indexes for table `performance_cycles`
--
ALTER TABLE `performance_cycles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `performance_feedback_requests`
--
ALTER TABLE `performance_feedback_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `perf_feedback_unique_nomination` (`performance_cycle_id`,`subject_employee_id`,`reviewer_employee_id`),
  ADD KEY `performance_feedback_requests_business_id_foreign` (`business_id`),
  ADD KEY `performance_feedback_requests_subject_employee_id_foreign` (`subject_employee_id`),
  ADD KEY `performance_feedback_requests_reviewer_employee_id_foreign` (`reviewer_employee_id`),
  ADD KEY `performance_feedback_requests_requested_by_foreign` (`requested_by`);

--
-- Indexes for table `performance_feedback_responses`
--
ALTER TABLE `performance_feedback_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pf_resp_req_unique` (`performance_feedback_request_id`);

--
-- Indexes for table `performance_key_results`
--
ALTER TABLE `performance_key_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `performance_key_results_performance_objective_id_foreign` (`performance_objective_id`);

--
-- Indexes for table `performance_objectives`
--
ALTER TABLE `performance_objectives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `performance_objectives_performance_cycle_id_foreign` (`performance_cycle_id`),
  ADD KEY `performance_objectives_employee_id_foreign` (`employee_id`),
  ADD KEY `performance_objectives_parent_objective_id_foreign` (`parent_objective_id`),
  ADD KEY `performance_objectives_department_id_foreign` (`department_id`);

--
-- Indexes for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `performance_reviews_performance_cycle_id_employee_id_unique` (`performance_cycle_id`,`employee_id`),
  ADD KEY `performance_reviews_employee_id_foreign` (`employee_id`),
  ADD KEY `performance_reviews_reviewer_id_foreign` (`reviewer_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `personnel_positions`
--
ALTER TABLE `personnel_positions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `previous_employments`
--
ALTER TABLE `previous_employments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `previous_employments_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `psychometric_tests`
--
ALTER TABLE `psychometric_tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `psychometric_tests_applicant_id_foreign` (`applicant_id`),
  ADD KEY `psychometric_tests_business_id_foreign` (`business_id`);

--
-- Indexes for table `reliefs`
--
ALTER TABLE `reliefs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reliefs_slug_unique` (`slug`),
  ADD KEY `reliefs_business_id_foreign` (`business_id`),
  ADD KEY `reliefs_location_id_foreign` (`location_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`),
  ADD KEY `idx_roles_business_id` (`business_id`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shifts_slug_unique` (`slug`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `skills_name_unique` (`name`),
  ADD UNIQUE KEY `skills_slug_unique` (`slug`);

--
-- Indexes for table `spouses`
--
ALTER TABLE `spouses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `spouses_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `statuses`
--
ALTER TABLE `statuses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `statuses_model_type_model_id_index` (`model_type`,`model_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tasks_slug_unique` (`slug`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teams_slug_unique` (`slug`),
  ADD KEY `teams_business_id_foreign` (`business_id`),
  ADD KEY `teams_department_id_foreign` (`department_id`);

--
-- Indexes for table `two_factor_codes`
--
ALTER TABLE `two_factor_codes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_phone_unique` (`phone`),
  ADD UNIQUE KEY `users_social_id_unique` (`social_id`);

--
-- Indexes for table `warnings`
--
ALTER TABLE `warnings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `warnings_case_id_unique` (`case_id`),
  ADD KEY `warnings_employee_id_foreign` (`employee_id`),
  ADD KEY `warnings_business_id_foreign` (`business_id`),
  ADD KEY `warnings_issued_by_foreign` (`issued_by`),
  ADD KEY `warnings_previous_case_id_foreign` (`previous_case_id`),
  ADD KEY `warnings_acknowledged_by_foreign` (`acknowledged_by`);

--
-- Indexes for table `withholding_payments`
--
ALTER TABLE `withholding_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `withholding_payments_business_id_foreign` (`business_id`),
  ADD KEY `withholding_payments_employee_id_foreign` (`employee_id`),
  ADD KEY `withholding_payments_payroll_id_foreign` (`payroll_id`),
  ADD KEY `withholding_payments_employee_payroll_id_foreign` (`employee_payroll_id`);

--
-- Indexes for table `withholding_tax_rates`
--
ALTER TABLE `withholding_tax_rates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `work_schedules`
--
ALTER TABLE `work_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `work_schedules_shift_id_foreign` (`shift_id`),
  ADD KEY `work_schedules_business_id_foreign` (`business_id`),
  ADD KEY `work_schedules_employee_id_effective_from_effective_to_index` (`employee_id`,`effective_from`,`effective_to`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_qualifications`
--
ALTER TABLE `academic_qualifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `access_requests`
--
ALTER TABLE `access_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=175;

--
-- AUTO_INCREMENT for table `advances`
--
ALTER TABLE `advances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `allowances`
--
ALTER TABLE `allowances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `applicant_skills`
--
ALTER TABLE `applicant_skills`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `application_academics`
--
ALTER TABLE `application_academics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `application_documents`
--
ALTER TABLE `application_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `application_memberships`
--
ALTER TABLE `application_memberships`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `application_work_experiences`
--
ALTER TABLE `application_work_experiences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `businesses`
--
ALTER TABLE `businesses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `business_currencies`
--
ALTER TABLE `business_currencies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `business_modules`
--
ALTER TABLE `business_modules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deductions`
--
ALTER TABLE `deductions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `employee_allowances`
--
ALTER TABLE `employee_allowances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_contact_details`
--
ALTER TABLE `employee_contact_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_deductions`
--
ALTER TABLE `employee_deductions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_departments`
--
ALTER TABLE `employee_departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_family_members`
--
ALTER TABLE `employee_family_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_payment_details`
--
ALTER TABLE `employee_payment_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `employee_payrolls`
--
ALTER TABLE `employee_payrolls`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `employee_payroll_details`
--
ALTER TABLE `employee_payroll_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `employee_payroll_settings`
--
ALTER TABLE `employee_payroll_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_reliefs`
--
ALTER TABLE `employee_reliefs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_task`
--
ALTER TABLE `employee_task`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employment_details`
--
ALTER TABLE `employment_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `experiences`
--
ALTER TABLE `experiences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `industries`
--
ALTER TABLE `industries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `interviews`
--
ALTER TABLE `interviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `interview_feedback`
--
ALTER TABLE `interview_feedback`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_categories`
--
ALTER TABLE `job_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `job_posts`
--
ALTER TABLE `job_posts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `kpis`
--
ALTER TABLE `kpis`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpi_results`
--
ALTER TABLE `kpi_results`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_activities`
--
ALTER TABLE `lead_activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_delegations`
--
ALTER TABLE `leave_delegations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_entitlements`
--
ALTER TABLE `leave_entitlements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_periods`
--
ALTER TABLE `leave_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leave_policies`
--
ALTER TABLE `leave_policies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_type_lists`
--
ALTER TABLE `leave_type_lists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `mandatory_leave_periods`
--
ALTER TABLE `mandatory_leave_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mandatory_leave_period_deductions`
--
ALTER TABLE `mandatory_leave_period_deductions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=185;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `organogram_positions`
--
ALTER TABLE `organogram_positions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `organogram_position_holders`
--
ALTER TABLE `organogram_position_holders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `organogram_roles`
--
ALTER TABLE `organogram_roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `overtimes`
--
ALTER TABLE `overtimes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payrolls`
--
ALTER TABLE `payrolls`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payroll_formulas`
--
ALTER TABLE `payroll_formulas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `payroll_formula_brackets`
--
ALTER TABLE `payroll_formula_brackets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=247;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pay_grades`
--
ALTER TABLE `pay_grades`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `performance_cycles`
--
ALTER TABLE `performance_cycles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `performance_feedback_requests`
--
ALTER TABLE `performance_feedback_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `performance_feedback_responses`
--
ALTER TABLE `performance_feedback_responses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `performance_key_results`
--
ALTER TABLE `performance_key_results`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `performance_objectives`
--
ALTER TABLE `performance_objectives`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personnel_positions`
--
ALTER TABLE `personnel_positions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `previous_employments`
--
ALTER TABLE `previous_employments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `psychometric_tests`
--
ALTER TABLE `psychometric_tests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reliefs`
--
ALTER TABLE `reliefs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spouses`
--
ALTER TABLE `spouses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `statuses`
--
ALTER TABLE `statuses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `two_factor_codes`
--
ALTER TABLE `two_factor_codes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `warnings`
--
ALTER TABLE `warnings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `withholding_payments`
--
ALTER TABLE `withholding_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `withholding_tax_rates`
--
ALTER TABLE `withholding_tax_rates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_schedules`
--
ALTER TABLE `work_schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_qualifications`
--
ALTER TABLE `academic_qualifications`
  ADD CONSTRAINT `academic_qualifications_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `access_requests`
--
ALTER TABLE `access_requests`
  ADD CONSTRAINT `access_requests_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `access_requests_requester_id_foreign` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `advances`
--
ALTER TABLE `advances`
  ADD CONSTRAINT `advances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `applicants`
--
ALTER TABLE `applicants`
  ADD CONSTRAINT `applicants_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `applicants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `applicant_skills`
--
ALTER TABLE `applicant_skills`
  ADD CONSTRAINT `applicant_skills_applicant_id_foreign` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applicant_skills_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_applicant_id_foreign` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`),
  ADD CONSTRAINT `applications_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `applications_job_post_id_foreign` FOREIGN KEY (`job_post_id`) REFERENCES `job_posts` (`id`);

--
-- Constraints for table `application_academics`
--
ALTER TABLE `application_academics`
  ADD CONSTRAINT `application_academics_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `application_documents`
--
ALTER TABLE `application_documents`
  ADD CONSTRAINT `application_documents_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `application_memberships`
--
ALTER TABLE `application_memberships`
  ADD CONSTRAINT `application_memberships_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `application_work_experiences`
--
ALTER TABLE `application_work_experiences`
  ADD CONSTRAINT `application_work_experiences_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendances`
--
ALTER TABLE `attendances`
  ADD CONSTRAINT `attendances_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_logged_by_foreign` FOREIGN KEY (`logged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `businesses`
--
ALTER TABLE `businesses`
  ADD CONSTRAINT `businesses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_currencies`
--
ALTER TABLE `business_currencies`
  ADD CONSTRAINT `business_currencies_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `business_modules`
--
ALTER TABLE `business_modules`
  ADD CONSTRAINT `business_modules_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_modules_module_id_foreign` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`),
  ADD CONSTRAINT `clients_client_business_foreign` FOREIGN KEY (`client_business`) REFERENCES `businesses` (`id`);

--
-- Constraints for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD CONSTRAINT `emergency_contacts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employees_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_organogram_role_id_foreign` FOREIGN KEY (`organogram_role_id`) REFERENCES `organogram_roles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_contact_details`
--
ALTER TABLE `employee_contact_details`
  ADD CONSTRAINT `employee_contact_details_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_departments`
--
ALTER TABLE `employee_departments`
  ADD CONSTRAINT `employee_departments_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_departments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD CONSTRAINT `employee_documents_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_family_members`
--
ALTER TABLE `employee_family_members`
  ADD CONSTRAINT `employee_family_members_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_payment_details`
--
ALTER TABLE `employee_payment_details`
  ADD CONSTRAINT `employee_payment_details_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_payroll_settings`
--
ALTER TABLE `employee_payroll_settings`
  ADD CONSTRAINT `employee_payroll_settings_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_task`
--
ALTER TABLE `employee_task`
  ADD CONSTRAINT `employee_task_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_task_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employment_details`
--
ALTER TABLE `employment_details`
  ADD CONSTRAINT `employment_details_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `employment_details_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employment_details_job_category_id_foreign` FOREIGN KEY (`job_category_id`) REFERENCES `job_categories` (`id`),
  ADD CONSTRAINT `employment_details_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`);

--
-- Constraints for table `experiences`
--
ALTER TABLE `experiences`
  ADD CONSTRAINT `experiences_applicant_id_foreign` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holidays`
--
ALTER TABLE `holidays`
  ADD CONSTRAINT `holidays_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `holidays_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `interviews`
--
ALTER TABLE `interviews`
  ADD CONSTRAINT `interviews_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interviews_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `interviews_interviewer_id_foreign` FOREIGN KEY (`interviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `interview_feedback`
--
ALTER TABLE `interview_feedback`
  ADD CONSTRAINT `interview_feedback_interview_id_foreign` FOREIGN KEY (`interview_id`) REFERENCES `interviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interview_feedback_interviewer_id_foreign` FOREIGN KEY (`interviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_posts`
--
ALTER TABLE `job_posts`
  ADD CONSTRAINT `job_posts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kpis`
--
ALTER TABLE `kpis`
  ADD CONSTRAINT `kpis_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `kpis_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kpis_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `kpis_job_category_id_foreign` FOREIGN KEY (`job_category_id`) REFERENCES `job_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kpis_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `kpi_results`
--
ALTER TABLE `kpi_results`
  ADD CONSTRAINT `kpi_results_kpi_id_foreign` FOREIGN KEY (`kpi_id`) REFERENCES `kpis` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_delegations`
--
ALTER TABLE `leave_delegations`
  ADD CONSTRAINT `leave_delegations_delegate_id_foreign` FOREIGN KEY (`delegate_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `leave_delegations_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `leave_delegations_leave_request_id_foreign` FOREIGN KEY (`leave_request_id`) REFERENCES `leave_requests` (`id`);

--
-- Constraints for table `leave_entitlements`
--
ALTER TABLE `leave_entitlements`
  ADD CONSTRAINT `leave_entitlements_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `leave_entitlements_leave_period_id_foreign` FOREIGN KEY (`leave_period_id`) REFERENCES `leave_periods` (`id`),
  ADD CONSTRAINT `leave_entitlements_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`);

--
-- Constraints for table `leave_policies`
--
ALTER TABLE `leave_policies`
  ADD CONSTRAINT `leave_policies_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `leave_policies_job_category_id_foreign` FOREIGN KEY (`job_category_id`) REFERENCES `job_categories` (`id`),
  ADD CONSTRAINT `leave_policies_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`);

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `leave_requests_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`),
  ADD CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `leave_requests_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`);

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD CONSTRAINT `loan_repayments_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mandatory_leave_periods`
--
ALTER TABLE `mandatory_leave_periods`
  ADD CONSTRAINT `mandatory_leave_periods_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mandatory_leave_periods_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mandatory_leave_periods_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mandatory_leave_period_deductions`
--
ALTER TABLE `mandatory_leave_period_deductions`
  ADD CONSTRAINT `mandatory_leave_period_deductions_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mandatory_leave_period_deductions_leave_entitlement_id_foreign` FOREIGN KEY (`leave_entitlement_id`) REFERENCES `leave_entitlements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mlp_deductions_period_id_fk` FOREIGN KEY (`mandatory_leave_period_id`) REFERENCES `mandatory_leave_periods` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD CONSTRAINT `notification_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD CONSTRAINT `notification_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `organogram_positions`
--
ALTER TABLE `organogram_positions`
  ADD CONSTRAINT `organogram_positions_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `organogram_positions_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `organogram_positions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `organogram_positions_personnel_position_id_foreign` FOREIGN KEY (`personnel_position_id`) REFERENCES `personnel_positions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `organogram_position_holders`
--
ALTER TABLE `organogram_position_holders`
  ADD CONSTRAINT `organogram_position_holders_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `organogram_position_holders_organogram_position_id_foreign` FOREIGN KEY (`organogram_position_id`) REFERENCES `organogram_positions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `organogram_roles`
--
ALTER TABLE `organogram_roles`
  ADD CONSTRAINT `organogram_roles_reports_to_role_id_foreign` FOREIGN KEY (`reports_to_role_id`) REFERENCES `organogram_roles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `overtimes`
--
ALTER TABLE `overtimes`
  ADD CONSTRAINT `overtimes_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `overtimes_attendance_id_foreign` FOREIGN KEY (`attendance_id`) REFERENCES `attendances` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `overtimes_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `overtimes_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `overtimes_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payroll_formula_brackets`
--
ALTER TABLE `payroll_formula_brackets`
  ADD CONSTRAINT `payroll_formula_brackets_payroll_formula_id_foreign` FOREIGN KEY (`payroll_formula_id`) REFERENCES `payroll_formulas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payslips`
--
ALTER TABLE `payslips`
  ADD CONSTRAINT `payslips_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payslips_payroll_id_foreign` FOREIGN KEY (`payroll_id`) REFERENCES `payrolls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pay_grades`
--
ALTER TABLE `pay_grades`
  ADD CONSTRAINT `pay_grades_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pay_grades_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pay_grades_job_category_id_foreign` FOREIGN KEY (`job_category_id`) REFERENCES `job_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `performance_feedback_requests`
--
ALTER TABLE `performance_feedback_requests`
  ADD CONSTRAINT `performance_feedback_requests_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `performance_feedback_requests_performance_cycle_id_foreign` FOREIGN KEY (`performance_cycle_id`) REFERENCES `performance_cycles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `performance_feedback_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `performance_feedback_requests_reviewer_employee_id_foreign` FOREIGN KEY (`reviewer_employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `performance_feedback_requests_subject_employee_id_foreign` FOREIGN KEY (`subject_employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `performance_feedback_responses`
--
ALTER TABLE `performance_feedback_responses`
  ADD CONSTRAINT `perf_feedback_response_request_fk` FOREIGN KEY (`performance_feedback_request_id`) REFERENCES `performance_feedback_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `performance_key_results`
--
ALTER TABLE `performance_key_results`
  ADD CONSTRAINT `performance_key_results_performance_objective_id_foreign` FOREIGN KEY (`performance_objective_id`) REFERENCES `performance_objectives` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `performance_objectives`
--
ALTER TABLE `performance_objectives`
  ADD CONSTRAINT `performance_objectives_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `performance_objectives_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `performance_objectives_parent_objective_id_foreign` FOREIGN KEY (`parent_objective_id`) REFERENCES `performance_objectives` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `performance_objectives_performance_cycle_id_foreign` FOREIGN KEY (`performance_cycle_id`) REFERENCES `performance_cycles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  ADD CONSTRAINT `performance_reviews_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `performance_reviews_performance_cycle_id_foreign` FOREIGN KEY (`performance_cycle_id`) REFERENCES `performance_cycles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `performance_reviews_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `previous_employments`
--
ALTER TABLE `previous_employments`
  ADD CONSTRAINT `previous_employments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `psychometric_tests`
--
ALTER TABLE `psychometric_tests`
  ADD CONSTRAINT `psychometric_tests_applicant_id_foreign` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `psychometric_tests_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reliefs`
--
ALTER TABLE `reliefs`
  ADD CONSTRAINT `reliefs_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reliefs_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `fk_roles_business_id` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `spouses`
--
ALTER TABLE `spouses`
  ADD CONSTRAINT `spouses_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `teams_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teams_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `warnings`
--
ALTER TABLE `warnings`
  ADD CONSTRAINT `warnings_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `warnings_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warnings_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warnings_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warnings_previous_case_id_foreign` FOREIGN KEY (`previous_case_id`) REFERENCES `warnings` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `withholding_payments`
--
ALTER TABLE `withholding_payments`
  ADD CONSTRAINT `withholding_payments_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `withholding_payments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `withholding_payments_employee_payroll_id_foreign` FOREIGN KEY (`employee_payroll_id`) REFERENCES `employee_payrolls` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `withholding_payments_payroll_id_foreign` FOREIGN KEY (`payroll_id`) REFERENCES `payrolls` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `work_schedules`
--
ALTER TABLE `work_schedules`
  ADD CONSTRAINT `work_schedules_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `work_schedules_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `work_schedules_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

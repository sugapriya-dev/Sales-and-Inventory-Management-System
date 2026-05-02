-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 24, 2026 at 12:03 PM
-- Server version: 5.7.24
-- PHP Version: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `supermarket`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `bank_id` int(11) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('Cash','Bank') NOT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `transaction_date` date DEFAULT NULL,
  `transaction_type` enum('payment','receipt','transfer') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `particulars` varchar(255) DEFAULT NULL,
  `debit` decimal(12,2) DEFAULT '0.00',
  `credit` decimal(12,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `bank_id`, `account_name`, `account_type`, `balance`, `created_at`, `transaction_date`, `transaction_type`, `reference_id`, `reference_no`, `particulars`, `debit`, `credit`) VALUES
(1, 1, 'SBI Ledger', 'Bank', '291072.00', '2026-02-27 06:45:52', NULL, 'payment', NULL, NULL, NULL, '0.00', '0.00'),
(2, 3, 'TMB Ledger', 'Bank', '499800.00', '2026-02-27 06:47:17', NULL, 'payment', NULL, NULL, NULL, '0.00', '0.00'),
(3, 1, 'SBI Ledger', 'Bank', '295720.20', '2026-02-27 07:30:23', '2026-02-27', 'payment', 103, 'INV-0007', 'Purchase payment - Supplier ID: 22, Invoice: INV-0007', '0.00', '800.00'),
(4, 1, 'SBI Ledger', 'Bank', '292000.00', '2026-02-27 07:33:06', '2026-02-27', 'payment', 104, '', 'Purchase payment - Supplier ID: 5, Invoice: ', '0.00', '4000.00'),
(6, 3, 'TMB Ledger', 'Bank', '499800.00', '2026-02-27 07:38:03', '2026-02-27', 'payment', 106, '', 'Purchase payment - Supplier ID: 15, Invoice: ', '0.00', '100.00');

-- --------------------------------------------------------

--
-- Table structure for table `bank`
--

CREATE TABLE `bank` (
  `id` int(11) NOT NULL,
  `bank_name` varchar(50) NOT NULL,
  `accname` varchar(50) NOT NULL,
  `accno` varchar(20) NOT NULL,
  `branch_name` varchar(50) NOT NULL,
  `opening_cash` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `bank`
--

INSERT INTO `bank` (`id`, `bank_name`, `accname`, `accno`, `branch_name`, `opening_cash`, `created_at`) VALUES
(1, 'SBI', 'Suga', '1111222', 'vnr', 300000, '2026-02-10 11:24:11'),
(3, 'TMB', 'Jaya', '4099', 'madurai', 500000, '2026-02-10 11:40:03');

-- --------------------------------------------------------

--
-- Table structure for table `bank_ledger`
--

CREATE TABLE `bank_ledger` (
  `id` int(11) NOT NULL,
  `bank_id` int(11) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` enum('purchase','sale','expense','receipt','payment') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `payee` int(11) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `amt` int(11) NOT NULL,
  `particulars` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `bank_ledger`
--

INSERT INTO `bank_ledger` (`id`, `bank_id`, `transaction_date`, `transaction_type`, `reference_id`, `payee`, `reference_no`, `amt`, `particulars`, `created_at`) VALUES
(18, 1, '2026-03-02', 'purchase', 134, 11, '123', -80, 'Purchase payment - Supplier ID: 11, Invoice: 123', '2026-03-02 07:36:19'),
(19, 3, '2026-03-02', 'sale', 13, 13, 'INV-0028', 30, 'Sales payment - Customer ID: 13, Invoice: INV-0028', '2026-03-02 07:37:00'),
(20, 1, '2026-03-02', 'sale', 25, 25, 'INV-0029', 30, 'Sales payment - Customer ID: 25, Invoice: INV-0029', '2026-03-02 10:56:20'),
(21, 3, '2026-03-02', 'purchase', 137, 15, '', -45, 'Purchase payment - Supplier ID: 15, Invoice: ', '2026-03-02 11:00:05'),
(22, 1, '2026-03-03', 'purchase', 140, 18, '', -80, 'Purchase payment - Supplier ID: 18, Invoice: ', '2026-03-03 05:51:02'),
(23, 1, '2026-03-03', 'sale', 25, 25, 'INV-0034', 30, 'Sales payment - Customer ID: 25, Invoice: INV-0034', '2026-03-03 05:56:21'),
(24, 3, '2026-03-03', 'sale', 14, 14, 'INV-0037', 45, 'Sales payment - Customer ID: 14, Invoice: INV-0037', '2026-03-03 06:10:17'),
(25, 3, '2026-03-03', 'purchase', 142, 12, '', -40, 'Purchase payment - Supplier ID: 12, Invoice: ', '2026-03-03 07:02:18'),
(26, 1, '2026-03-03', 'purchase', 144, 14, '236', -45, 'Purchase payment - Supplier ID: 14, Invoice: 236', '2026-03-03 07:16:16'),
(27, 3, '2026-03-03', 'purchase', 148, 5, 'a3', -30, 'Purchase payment - Supplier ID: 5, Invoice: a3', '2026-03-03 07:44:23'),
(28, 1, '2026-03-03', 'sale', 14, 14, 'INV-0041', 80, 'Sales payment - Customer ID: 14, Invoice: INV-0041', '2026-03-03 11:30:43'),
(29, 1, '2026-03-03', 'sale', 26, 26, 'INV-0042', 40, 'Sales payment - Customer ID: 26, Invoice: INV-0042', '2026-03-03 11:59:45'),
(30, 3, '2026-03-03', 'purchase', 156, 23, 'INV-0001', -80, 'Purchase payment - Supplier ID: 23, Invoice: INV-0001', '2026-03-03 12:03:16'),
(31, 3, '2026-03-04', 'sale', 50, 25, 'INV-0050', 45, 'Sales payment - Customer ID: 25, Invoice: INV-0050', '2026-03-04 07:21:49'),
(34, 3, '2026-03-04', 'purchase', 50, 25, 'INV-0050', -125, 'Purchase - INV-0050', '2026-03-04 07:23:12'),
(35, 3, '2026-03-04', 'payment', 18, 18, 'PAY-1772610825', -30, 'Payment to Supplier - Devi', '2026-03-04 07:53:45'),
(36, NULL, '2026-03-04', 'payment', NULL, 15, 'PAY-1772611333', -50, 'Payment to Supplier - Durga', '2026-03-04 08:02:13'),
(38, NULL, '2026-03-04', 'payment', NULL, 16, 'PAY-1772611697', -10, 'Payment to Supplier - jaya', '2026-03-04 08:08:17'),
(39, NULL, '2026-03-04', 'payment', 0, 16, 'PAY-1772611873', -10, 'Payment to Supplier - jaya', '2026-03-04 08:11:13'),
(40, NULL, '2026-03-04', 'payment', NULL, 16, 'PAY-1772612066', -10, 'Payment to Supplier - jaya', '2026-03-04 08:14:26'),
(41, 3, '2026-03-04', 'purchase', 159, 16, '236', -120, 'Purchase - 236', '2026-03-04 08:19:42'),
(42, 1, '2026-03-04', 'purchase', 161, 22, 'INV-0022', -40, 'Purchase payment - Supplier ID: 22, Invoice: INV-0022', '2026-03-04 10:16:27'),
(43, NULL, '2026-03-04', 'payment', 27, 22, 'PAY-1772619464', -20, 'Payment to Supplier - Jennie', '2026-03-04 10:17:44'),
(44, NULL, '2026-03-04', 'payment', 28, 22, 'PAYMENT', -5, 'Payment to Supplier - Jennie', '2026-03-04 10:21:36'),
(45, NULL, '2026-03-06', 'payment', 30, 18, 'PAYMENT', -20, 'Payment to Supplier - Devi', '2026-03-06 06:06:23'),
(46, 1, '2026-03-06', 'payment', 33, 22, 'PAYMENT', -15, 'Payment to Supplier - Jennie', '2026-03-06 06:23:13'),
(47, 1, '2026-03-06', 'payment', 2, 4, 'PAYMENT', 100, 'Payment to Customer - ', '2026-03-06 06:55:53'),
(48, 3, '2026-03-06', 'payment', 3, 14, 'PAYMENT', 65, 'Payment to Customer - suga', '2026-03-06 06:57:22'),
(49, 1, '2026-03-06', 'payment', 4, 26, 'PAYMENT', 25, 'Payment to Customer - riya', '2026-03-06 07:00:09'),
(50, 1, '2026-03-06', 'purchase', 169, 14, 'qw', -604, 'Purchase - qw', '2026-03-06 10:15:35'),
(51, 1, '2026-03-06', 'purchase', 170, 20, 'k12', -304, 'Purchase - k12', '2026-03-06 10:33:55'),
(58, NULL, '2026-03-06', 'purchase', 171, 16, 'a23', -800, 'Purchase - a23', '2026-03-06 12:20:48'),
(64, 172, '2026-03-07', 'purchase', 1, 12, 'xyz', -120, 'Purchase - xyz', '2026-03-07 06:32:02'),
(65, 172, '2026-03-07', 'purchase', 3, 12, 'xyz', -120, 'Purchase - xyz', '2026-03-07 06:33:40'),
(70, 1, '2026-03-07', 'payment', 6, 25, 'PAYMENT', 225, 'Payment to Customer - Luna', '2026-03-07 07:18:01'),
(71, 1, '2026-03-07', 'purchase', 173, 22, 'c12', -50, 'Purchase - c12', '2026-03-07 07:22:04'),
(72, 1, '2026-03-07', 'purchase', 175, 11, '236', -80, 'Purchase payment - Supplier ID: 11, Invoice: 236', '2026-03-07 08:23:11'),
(73, 1, '2026-03-09', 'sale', 60, 25, 'INV-0060', 300, 'Sales payment - Customer ID: 25, Invoice: INV-0060', '2026-03-09 06:07:12'),
(74, 1, '2026-03-10', 'sale', 63, 25, 'INV-0063', 80, 'Sales payment - Customer ID: 25, Invoice: INV-0063', '2026-03-10 12:25:33'),
(76, 1, '2026-03-23', 'sale', 68, 4, 'INV-0068', 70, 'Sale - INV-0068', '2026-03-23 11:19:10'),
(78, 1, '2026-03-24', 'sale', 71, 14, 'INV-0071', 80, 'Sale - INV-0071', '2026-03-24 07:17:31'),
(79, 1, '2026-03-24', 'sale', 73, 14, 'INV-0073', 120, 'Sales payment - Customer ID: 14, Invoice: INV-0073', '2026-03-24 07:43:30'),
(80, 1, '2026-03-24', 'payment', 9, 25, 'PAYMENT', 40, 'Payment to Customer - Luna', '2026-03-24 08:11:23'),
(81, 1, '2026-03-24', 'receipt', 10, 4, 'PAYMENT', 700, 'Payment to Customer - mona', '2026-03-24 08:46:23'),
(82, 3, '2026-03-24', 'receipt', 14, 25, 'PAYMENT', 100, 'Payment to Customer - Luna', '2026-03-24 11:36:36');

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `billno` varchar(50) NOT NULL,
  `totamt` decimal(10,2) NOT NULL,
  `discount_price` decimal(10,2) NOT NULL,
  `gst_amt` decimal(10,2) DEFAULT NULL,
  `packing_amt` decimal(10,2) DEFAULT NULL,
  `final_amt` decimal(10,2) NOT NULL,
  `payment_mode` varchar(20) DEFAULT 'Cash',
  `sales_id` int(11) NOT NULL,
  `bank_account_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_city` varchar(255) DEFAULT NULL,
  `customer_state` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_aadhar` varchar(20) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `discount_slab_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `cash_in_hand`
--

CREATE TABLE `cash_in_hand` (
  `id` int(11) NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` enum('purchase','sale','expense','receipt','payment') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `payee` int(11) DEFAULT NULL,
  `particulars` varchar(255) DEFAULT NULL,
  `amt` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cash_in_hand`
--

INSERT INTO `cash_in_hand` (`id`, `transaction_date`, `transaction_type`, `reference_id`, `reference_no`, `payee`, `particulars`, `amt`, `created_at`) VALUES
(35, '2026-03-02', 'purchase', 133, '', 11, 'Purchase payment - Supplier ID: 11, Invoice: ', '-120.00', '2026-03-02 07:33:04'),
(36, '2026-03-02', 'sale', 13, 'INV-0001', 13, 'Sales payment - Customer ID: 13, Invoice: INV-0001', '120.00', '2026-03-02 07:36:40'),
(37, '2026-03-02', 'purchase', 135, '', 18, 'Purchase payment - Supplier ID: 18, Invoice: ', '-80.00', '2026-03-02 10:24:23'),
(38, '2026-03-02', 'purchase', 136, '', 15, 'Purchase payment - Supplier ID: 15, Invoice: ', '-40.00', '2026-03-02 10:57:46'),
(39, '2026-03-03', 'purchase', 139, '', 18, 'Purchase payment - Supplier ID: 18, Invoice: ', '-80.00', '2026-03-03 05:50:08'),
(40, '2026-03-03', 'sale', 4, 'INV-0031', 4, 'Sales payment - Customer ID: 4, Invoice: INV-0031', '80.00', '2026-03-03 05:53:01'),
(41, '2026-03-03', 'sale', 14, 'INV-0035', 14, 'Sales payment - Customer ID: 14, Invoice: INV-0035', '80.00', '2026-03-03 06:09:25'),
(42, '2026-03-03', 'purchase', 141, '', 12, 'Purchase payment - Supplier ID: 12, Invoice: ', '-80.00', '2026-03-03 07:01:53'),
(43, '2026-03-03', 'purchase', 146, 'a3', 5, 'Purchase payment - Supplier ID: 5, Invoice: a3', '-80.00', '2026-03-03 07:42:58'),
(44, '2026-03-03', 'purchase', 152, '236', 20, 'Purchase payment - Supplier ID: 20, Invoice: 236', '-30.00', '2026-03-03 09:19:46'),
(45, '2026-03-03', 'sale', 26, 'INV-0043', 26, 'Sales payment - Customer ID: 26, Invoice: INV-0043', '30.00', '2026-03-03 12:00:49'),
(46, '2026-03-03', 'purchase', 154, 'a3', 23, 'Purchase payment - Supplier ID: 23, Invoice: a3', '-30.00', '2026-03-03 12:02:26'),
(47, '2026-03-04', 'sale', 26, 'INV-0045', 26, 'Sales payment - Customer ID: 26, Invoice: INV-0045', '45.00', '2026-03-04 05:45:25'),
(54, '2026-03-04', 'purchase', 157, 'INV-0001', 22, 'Purchase - INV-0001', '-38.00', '2026-03-04 06:51:28'),
(55, '2026-03-04', 'sale', 13, 'INV-0046', 13, 'Sales payment - Customer ID: 13, Invoice: INV-0046', '80.00', '2026-03-04 07:01:37'),
(56, '2026-03-04', 'sale', 48, 'INV-0047', 4, 'Sales payment - Customer ID: 4, Invoice: INV-0047', '30.00', '2026-03-04 07:11:49'),
(57, '2026-03-04', 'purchase', 48, 'INV-0047', 4, 'Purchase - INV-0047', '-25.00', '2026-03-04 07:19:29'),
(58, '2026-03-04', 'sale', 49, 'INV-0049', 26, 'Sales payment - Customer ID: 26, Invoice: INV-0049', '40.00', '2026-03-04 07:20:38'),
(59, '2026-03-04', 'purchase', 49, 'INV-0049', 26, 'Purchase - INV-0049', '-70.00', '2026-03-04 07:21:14'),
(60, '2026-03-04', 'payment', 18, 'PAY-1772609811', 18, 'Payment to Supplier - Devi', '-20.00', '2026-03-04 07:36:51'),
(61, '2026-03-04', 'payment', NULL, 'PAY-1772612515', 15, 'Payment to Supplier - Durga', '-20.00', '2026-03-04 08:21:55'),
(62, '2026-03-04', 'payment', 26, 'PAY-1772619218', 18, 'Payment to Supplier - Devi', '-5.00', '2026-03-04 10:13:38'),
(63, '2026-03-04', 'sale', 51, 'INV-0051', 13, 'Sales payment - Customer ID: 13, Invoice: INV-0051', '304.00', '2026-03-04 11:40:05'),
(64, '2026-03-04', 'payment', 29, 'PAYMENT', 15, 'Payment to Supplier - Durga', '-5.00', '2026-03-04 12:48:15'),
(65, '2026-03-06', 'payment', 31, 'PAYMENT', 22, 'Payment to Supplier - Jennie', '-200.00', '2026-03-06 06:21:27'),
(66, '2026-03-06', 'payment', 34, 'PAYMENT', 22, 'Payment to Supplier - Jennie', '-500.00', '2026-03-06 06:25:36'),
(67, '2026-03-06', 'sale', 52, 'INV-0052', 13, 'Sales payment - Customer ID: 13, Invoice: INV-0052', '80.00', '2026-03-06 06:31:03'),
(68, '2026-03-06', 'receipt', 1, 'PAYMENT', 4, 'Payment to Customer - mona', '20.00', '2026-03-06 06:53:06'),
(69, '2026-03-06', 'purchase', 165, 'abc', 5, 'Purchase payment - Supplier ID: 5, Invoice: abc', '-45.00', '2026-03-06 07:15:15'),
(70, '2026-03-06', 'sale', 53, 'INV-0053', 4, 'Sales payment - Customer ID: 4, Invoice: INV-0053', '300.00', '2026-03-06 07:18:11'),
(71, '2026-03-06', 'sale', 54, 'INV-0054', 13, 'Sales payment - Customer ID: 13, Invoice: INV-0054', '500.00', '2026-03-06 07:52:44'),
(73, '2026-03-06', 'purchase', 55, 'INV-0055', 25, 'Purchase - INV-0055', '-75.00', '2026-03-06 08:21:38'),
(74, '2026-03-06', 'receipt', 5, 'PAYMENT', 4, 'Payment to Customer - mona', '220.00', '2026-03-06 08:22:40'),
(77, '2026-03-06', 'sale', 57, 'INV-0057', 4, 'Sales payment - Customer ID: 4, Invoice: INV-0057', '50.00', '2026-03-06 08:25:13'),
(82, '2026-03-06', 'purchase', 168, 'zz', 22, 'Purchase - zz', '-801.00', '2026-03-06 08:34:49'),
(83, '2026-03-06', 'purchase', 167, 'adc', 16, 'Purchase - adc', '-290.00', '2026-03-06 08:37:16'),
(86, '2026-03-06', 'purchase', 169, 'qw', 14, 'Purchase - qw', '-604.00', '2026-03-06 08:43:24'),
(88, '2026-03-06', 'purchase', 170, 'k12', 20, 'Purchase - k12', '-304.00', '2026-03-06 10:32:28'),
(92, '2026-03-07', 'purchase', 172, 'xyz', 12, 'Purchase - xyz', '-120.00', '2026-03-07 06:34:22'),
(94, '2026-03-06', 'purchase', 57, 'INV-0057', 4, 'Purchase - INV-0057', '-951.00', '2026-03-07 06:43:43'),
(100, '2026-03-07', 'sale', 58, 'INV-0058', 14, 'Sale - INV-0058', '80.00', '2026-03-07 07:16:20'),
(101, '2026-03-07', 'receipt', 7, 'PAYMENT', 13, 'Payment to Customer - jc', '30.00', '2026-03-07 07:20:08'),
(102, '2026-03-07', 'purchase', 174, 'INV-0001', 5, 'Purchase payment - Supplier ID: 5, Invoice: INV-0001', '-450.00', '2026-03-07 08:16:27'),
(104, '2026-03-09', 'sale', 61, 'INV-0061', 26, 'Sale - INV-0061', '40.00', '2026-03-09 07:05:09'),
(105, '2026-03-09', 'purchase', 176, 's12', 15, 'Purchase payment - Supplier ID: 15, Invoice: s12', '-80.00', '2026-03-09 07:26:55'),
(106, '2026-03-09', 'receipt', 8, 'PAYMENT', 25, 'Payment to Customer - Luna', '40.00', '2026-03-09 08:26:42'),
(107, '2026-03-10', 'purchase', 177, 's23', 5, 'Purchase payment - Supplier ID: 5, Invoice: s23', '-80.00', '2026-03-10 06:38:10'),
(108, '2026-03-10', 'sale', 62, 'INV-0062', 25, 'Sales payment - Customer ID: 25, Invoice: INV-0062', '30.00', '2026-03-10 12:25:07'),
(109, '2026-03-11', 'sale', 64, 'INV-0064', 13, 'Sales payment - Customer ID: 13, Invoice: INV-0064', '30.00', '2026-03-11 08:20:38'),
(110, '2026-03-11', 'sale', 66, 'INV-0066', 13, 'Sales payment - Customer ID: 13, Invoice: INV-0066', '110.00', '2026-03-11 12:06:11'),
(111, '2026-03-12', 'sale', 67, 'INV-0067', 4, 'Sales payment - Customer ID: 4, Invoice: INV-0067', '88.00', '2026-03-12 07:32:01'),
(112, '2026-03-23', 'purchase', 179, 'INV-0001', 15, 'Purchase payment - Supplier ID: 15, Invoice: INV-0001', '-50.00', '2026-03-23 11:29:03'),
(113, '2026-03-23', 'sale', 69, 'INV-0069', 13, 'Sales payment - Customer ID: 13, Invoice: INV-0069', '30.00', '2026-03-23 12:23:01'),
(114, '2026-03-24', 'sale', 70, 'INV-0070', 26, 'Sales payment - Customer ID: 26, Invoice: INV-0070', '30.00', '2026-03-24 07:14:32'),
(115, '2026-03-24', 'sale', 72, 'INV-0072', 14, 'Sales payment - Customer ID: 14, Invoice: INV-0072', '70.00', '2026-03-24 07:41:41'),
(116, '2026-03-24', 'sale', 74, 'INV-0074', 14, 'Sales payment - Customer ID: 14, Invoice: INV-0074', '160.00', '2026-03-24 10:17:44'),
(117, '2026-03-24', 'receipt', 11, 'PAYMENT', 14, 'Payment to Customer - suga', '82.00', '2026-03-24 10:20:52'),
(118, '2026-03-24', 'receipt', 12, 'PAYMENT', 25, 'Payment to Customer - Luna', '10.00', '2026-03-24 10:31:22'),
(119, '2026-03-24', 'payment', 35, 'PAYMENT', 14, 'Payment to Supplier - Subu', '-18.00', '2026-03-24 10:34:18'),
(120, '2026-03-24', 'receipt', 13, 'PAYMENT', 26, 'Payment to Customer - riya', '40.00', '2026-03-24 10:35:34');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `category_code`, `created_at`, `updated_at`) VALUES
(6, 'Electrical Goods', 'ELECTRIC', '2026-02-25 11:36:56', '2026-02-25 11:36:56'),
(7, 'Stationery', 'STA-1', '2026-02-25 11:37:16', '2026-02-25 11:37:16'),
(8, 'Dairy', 'DAIRY', '2026-02-25 11:37:43', '2026-02-25 12:00:46'),
(9, 'Fruits', 'Fruit', '2026-02-25 11:38:43', '2026-02-25 11:39:12'),
(10, 'Grocery', 'GROCERY', '2026-02-25 11:49:06', '2026-02-25 11:57:30'),
(11, 'Household', 'HOUSEHOLD', '2026-02-25 12:17:41', '2026-02-25 12:17:58');

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE `company` (
  `id` int(11) NOT NULL,
  `comp_name` varchar(255) NOT NULL,
  `comp_address` text,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `gstno` varchar(15) NOT NULL,
  `comp_state` varchar(50) NOT NULL,
  `website` varchar(255) NOT NULL,
  `opening_cash` int(11) DEFAULT NULL,
  `logo` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`id`, `comp_name`, `comp_address`, `phone`, `email`, `gstno`, `comp_state`, `website`, `opening_cash`, `logo`) VALUES
(1, 'NISQUARE TECH', 'Virudhunagar', '9876543210', 'nisquaretech@gmail.com', '29ABCDE1234F1Z5', 'Tamilnadu', 'https://www.nisquaretech.in/', 10000, 'logo.png');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customername` varchar(50) NOT NULL,
  `phoneno` bigint(20) NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `aadhaarno` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customername`, `phoneno`, `city`, `state`, `created_at`, `aadhaarno`) VALUES
(4, 'mona', 7894563210, 'vnr', 'Karnataka', '2026-02-07 06:31:20', ''),
(13, 'jc', 9123456780, 'sivakasi', 'Kerala', '2026-02-07 07:45:57', ''),
(14, 'suga', 9876543210, 'vnr', 'Tamilnadu', '2026-02-07 07:47:47', ''),
(25, 'Luna', 9999999999, 'vnr', 'Tamilnadu', '2026-02-26 06:32:15', ''),
(26, 'riya', 9876543210, 'vnr', 'Tamilnadu', '2026-03-03 11:59:21', '');

-- --------------------------------------------------------

--
-- Table structure for table `gstslab`
--

CREATE TABLE `gstslab` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `gst_percent` decimal(5,2) NOT NULL,
  `pricetype` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gstslab`
--

INSERT INTO `gstslab` (`id`, `name`, `gst_percent`, `pricetype`, `created_at`) VALUES
(2, '5', '5.00', 'Inclusive', '2026-02-11 06:22:45'),
(3, '18', '18.00', 'Inclusive', '2026-02-11 06:41:15'),
(6, '20', '20.00', 'Inclusive', '2026-02-11 07:35:31'),
(7, '10', '10.00', NULL, '2026-03-02 11:13:53');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `pid` int(11) NOT NULL,
  `pro_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `unittype` varchar(20) NOT NULL,
  `unitprice` decimal(10,2) NOT NULL,
  `purchaseprice` decimal(10,2) NOT NULL,
  `regularprice` decimal(10,2) NOT NULL,
  `taxtype` varchar(10) NOT NULL,
  `hsncode` varchar(20) NOT NULL,
  `initialqty` int(11) NOT NULL,
  `expirydate` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`pid`, `pro_name`, `category`, `unittype`, `unitprice`, `purchaseprice`, `regularprice`, `taxtype`, `hsncode`, `initialqty`, `expirydate`, `created_at`, `updated_at`) VALUES
(6, 'Apple', 'Fruits', 'per_kg', '100.00', '80.10', '100.00', '5.00', '08081000', 55, NULL, '2026-02-11 07:26:41', '2026-03-10 16:16:17'),
(8, 'Banana', 'Fruits', 'per_piece', '60.00', '40.00', '60.00', '5.00', '08039010', 32, NULL, '2026-02-11 07:38:18', '2026-03-10 12:41:56'),
(9, 'LED Bulb 9W', 'Electrical Goods', '', '120.00', '80.00', '120.00', '18.00', '8539', 105, NULL, '2026-02-16 06:13:49', '2026-03-10 12:40:18'),
(10, 'Milk', 'Dairy', '', '50.00', '40.00', '50.00', '5.00', '0401', 54, '2026-03-16', '2026-02-16 06:18:02', '2026-03-10 16:00:07'),
(11, 'Curd', 'Dairy', '', '40.00', '30.00', '40.00', '10.00', '0401', 54, '2026-03-16', '2026-02-16 06:18:52', '2026-03-11 12:50:17'),
(12, 'Pen', 'stationery', 'per_piece', '40.00', '30.40', '40.00', '10.00', '0401', 104, NULL, '2026-02-16 07:25:33', '2026-03-10 16:17:32'),
(13, 'test', 'Dairy', 'per_piece', '100.00', '0.00', '100.00', '18.00', '0401', 30, '2026-02-27', '2026-02-25 08:10:19', NULL),
(14, 'Rice', 'Grocery', 'per_kg', '55.00', '45.00', '55.00', '5.00', '1006', 220, '2026-12-31', '2026-02-25 11:51:16', '2026-03-10 13:20:16'),
(15, 'White Sugar', 'Grocery', 'per_kg', '45.00', '50.00', '45.00', '10.00', '9609', 118, NULL, '2026-03-06 07:51:47', '2026-03-10 13:15:57'),
(17, 'demo', 'Dairy', '', '35.00', '30.00', '35.00', '', '', 1, NULL, '2026-03-11 10:27:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase`
--

CREATE TABLE `purchase` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `invoiceno` varchar(50) DEFAULT NULL,
  `mode` enum('cash','credit','bank') NOT NULL,
  `bankname` varchar(50) DEFAULT NULL,
  `totalamt` decimal(10,2) DEFAULT '0.00',
  `paidamt` decimal(10,2) DEFAULT '0.00',
  `balanceamt` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bank_id` int(11) DEFAULT NULL,
  `discount_total` decimal(10,2) DEFAULT '0.00',
  `gst_total` decimal(10,2) DEFAULT '0.00',
  `packing_charge` decimal(10,2) DEFAULT '0.00',
  `round_off` decimal(10,2) DEFAULT '0.00',
  `grand_total` decimal(10,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `purchase`
--

INSERT INTO `purchase` (`id`, `supplier_id`, `date`, `invoiceno`, `mode`, `bankname`, `totalamt`, `paidamt`, `balanceamt`, `created_at`, `bank_id`, `discount_total`, `gst_total`, `packing_charge`, `round_off`, `grand_total`) VALUES
(133, 11, '2026-03-02', 'INV-0001', 'cash', NULL, '120.00', '0.00', '0.00', '2026-03-02 07:33:04', 0, '0.00', '5.72', '0.00', '-0.10', '120.00'),
(134, 11, '2026-03-02', '123', 'bank', 'SBI', '80.10', '0.00', '0.00', '2026-03-02 07:36:19', 1, '0.00', '3.81', '0.00', '-0.10', '80.00'),
(135, 18, '2026-03-02', '', 'cash', NULL, '80.10', '0.00', '0.00', '2026-03-02 10:24:23', 0, '0.00', '3.81', '0.00', '-0.10', '80.00'),
(136, 15, '2026-03-02', '', 'cash', NULL, '40.00', '0.00', '0.00', '2026-03-02 10:57:45', 0, '0.00', '1.90', '0.00', '0.00', '40.00'),
(137, 15, '2026-03-02', 'INV-0001', 'bank', 'TMB', '45.00', '0.00', '0.00', '2026-03-02 11:00:05', 3, '0.00', '2.14', '0.00', '0.00', '45.00'),
(138, 18, '2026-03-03', '', 'credit', NULL, '80.10', '0.00', '0.00', '2026-03-03 05:49:10', 0, '0.00', '3.81', '0.00', '-0.10', '80.00'),
(139, 18, '2026-03-03', '', 'cash', NULL, '80.10', '0.00', '0.00', '2026-03-03 05:50:08', 0, '0.00', '3.81', '0.00', '-0.10', '80.00'),
(140, 18, '2026-03-03', '', 'bank', 'SBI', '80.10', '0.00', '0.00', '2026-03-03 05:51:02', 1, '0.00', '3.81', '0.00', '-0.10', '80.00'),
(141, 12, '2026-03-03', '', 'cash', NULL, '80.00', '0.00', '0.00', '2026-03-03 07:01:53', 0, '0.00', '12.20', '0.00', '0.00', '80.00'),
(142, 12, '2026-03-03', '', 'bank', 'TMB', '40.00', '0.00', '0.00', '2026-03-03 07:02:18', 3, '0.00', '1.90', '0.00', '0.00', '40.00'),
(143, 12, '2026-03-03', '', 'credit', NULL, '240.00', '0.00', '0.00', '2026-03-03 07:04:22', 0, '0.00', '36.61', '0.00', '0.00', '240.00'),
(144, 14, '2026-03-03', '236', 'bank', 'SBI', '45.00', '0.00', '0.00', '2026-03-03 07:16:16', 1, '0.00', '2.14', '0.00', '0.00', '45.00'),
(145, 15, '2026-03-03', '236', 'credit', NULL, '80.00', '0.00', '0.00', '2026-03-03 07:40:25', 0, '0.00', '12.20', '0.00', '0.00', '80.00'),
(146, 5, '2026-03-03', 'a3', 'cash', NULL, '80.00', '0.00', '0.00', '2026-03-03 07:42:58', 0, '0.00', '12.20', '0.00', '0.00', '80.00'),
(147, 5, '2026-03-03', 'a3', 'credit', NULL, '40.00', '0.00', '0.00', '2026-03-03 07:43:54', 0, '0.00', '1.90', '0.00', '0.00', '40.00'),
(148, 5, '2026-03-03', 'a3', 'bank', 'TMB', '30.40', '0.00', '0.00', '2026-03-03 07:44:23', 3, '0.00', '2.76', '0.00', '-0.40', '30.00'),
(152, 20, '2026-03-03', '236', 'cash', NULL, '30.00', '0.00', '0.00', '2026-03-03 09:19:46', 0, '0.00', '2.73', '0.00', '0.00', '30.00'),
(153, 20, '2026-03-03', '236', 'credit', NULL, '30.00', '0.00', '0.00', '2026-03-03 09:20:13', 0, '0.00', '2.73', '0.00', '0.00', '30.00'),
(154, 23, '2026-03-03', 'a3', 'cash', NULL, '30.00', '0.00', '0.00', '2026-03-03 12:02:26', 0, '0.00', '2.73', '0.00', '0.00', '30.00'),
(155, 23, '2026-03-03', '236', 'credit', NULL, '45.00', '0.00', '0.00', '2026-03-03 12:02:52', 0, '0.00', '2.14', '0.00', '0.00', '45.00'),
(156, 23, '2026-03-03', 'INV-0001', 'bank', 'TMB', '160.10', '0.00', '0.00', '2026-03-03 12:03:16', 3, '10.00', '16.02', '0.00', '-0.10', '150.00'),
(157, 22, '2026-03-04', 'INV-0001', 'cash', NULL, '40.00', '0.00', '0.00', '2026-03-04 06:02:43', 0, '2.00', '1.90', '0.00', '0.00', '38.00'),
(158, 14, '2026-03-04', 'a3', 'credit', NULL, '60.40', '0.00', '0.00', '2026-03-04 06:56:03', 0, '0.00', '5.49', '0.00', '-0.40', '60.00'),
(159, 16, '2026-03-04', '236', 'bank', 'TMB', '120.10', '0.00', '0.00', '2026-03-04 08:06:38', 3, '0.00', '5.72', '0.00', '-0.10', '120.00'),
(160, 16, '2026-03-04', 'a3', 'credit', NULL, '80.00', '0.00', '0.00', '2026-03-04 08:08:02', 0, '0.00', '12.20', '0.00', '0.00', '80.00'),
(161, 22, '2026-03-04', 'INV-0022', 'bank', 'SBI', '40.00', '0.00', '0.00', '2026-03-04 10:16:27', 1, '0.00', '1.90', '0.00', '0.00', '40.00'),
(162, 22, '2026-03-04', 'a3', 'credit', NULL, '40.00', '0.00', '0.00', '2026-03-04 10:17:29', 0, '0.00', '1.90', '0.00', '0.00', '40.00'),
(163, 22, '2026-03-06', 'xyz', 'credit', NULL, '1201.00', '0.00', '0.00', '2026-03-06 06:20:54', 0, '1.00', '57.19', '0.00', '0.00', '1200.00'),
(164, 5, '2026-03-06', 'abc', 'cash', NULL, '45.00', '0.00', '0.00', '2026-03-06 07:14:53', 0, '0.00', '2.14', '0.00', '0.00', '45.00'),
(165, 5, '2026-03-06', 'abc', 'cash', NULL, '45.00', '0.00', '0.00', '2026-03-06 07:15:15', 0, '0.00', '2.14', '0.00', '0.00', '45.00'),
(166, 18, '2026-03-06', 'a12', 'credit', NULL, '500.00', '0.00', '0.00', '2026-03-06 07:54:19', 0, '0.00', '45.45', '0.00', '0.00', '500.00'),
(167, 16, '2026-03-06', 'adc', 'cash', NULL, '290.00', '0.00', '0.00', '2026-03-06 08:24:07', 0, '0.00', '24.63', '0.00', '0.00', '290.00'),
(168, 22, '2026-03-06', 'zz', 'cash', NULL, '801.00', '0.00', '0.00', '2026-03-06 08:30:54', 0, '0.00', '38.14', '0.00', '0.00', '801.00'),
(169, 14, '2026-03-06', 'qw', 'bank', 'SBI', '604.00', '0.00', '0.00', '2026-03-06 08:42:49', 1, '0.00', '54.91', '0.00', '0.00', '604.00'),
(170, 20, '2026-03-06', 'k12', 'bank', 'SBI', '304.00', '0.00', '0.00', '2026-03-06 10:31:38', 1, '0.00', '27.64', '0.00', '0.00', '304.00'),
(171, 16, '2026-03-06', 'a23', 'bank', 'TMB', '800.00', '0.00', '0.00', '2026-03-06 10:42:43', 3, '0.00', '122.03', '0.00', '0.00', '800.00'),
(172, 12, '2026-03-07', 'xyz', 'cash', NULL, '120.40', '0.00', '0.00', '2026-03-07 05:52:23', NULL, '0.00', '7.05', '0.00', '-0.40', '120.00'),
(173, 22, '2026-03-07', 'c12', 'bank', 'SBI', '50.00', '0.00', '0.00', '2026-03-07 06:39:14', 1, '0.00', '4.55', '0.00', '0.00', '50.00'),
(174, 5, '2026-03-07', 'INV-0001', 'cash', NULL, '450.00', '0.00', '0.00', '2026-03-07 08:16:27', 0, '0.00', '21.43', '0.00', '0.00', '450.00'),
(175, 11, '2026-03-07', '236', 'bank', 'SBI', '80.00', '0.00', '0.00', '2026-03-07 08:23:11', 1, '0.00', '12.20', '0.00', '0.00', '80.00'),
(176, 15, '2026-03-09', 's12', 'cash', NULL, '80.10', '0.00', '0.00', '2026-03-09 07:26:55', 0, '0.00', '3.81', '0.00', '-0.10', '80.00'),
(177, 5, '2026-03-10', 's23', 'cash', NULL, '80.00', '0.00', '0.00', '2026-03-10 06:38:10', 0, '0.00', '12.20', '0.00', '0.00', '80.00'),
(178, 16, '2026-03-11', 'j12', 'credit', NULL, '30.00', '0.00', '0.00', '2026-03-11 10:28:03', 0, '0.00', '0.00', '0.00', '0.00', '30.00'),
(179, 15, '2026-03-23', 'INV-0001', 'cash', NULL, '50.00', '0.00', '0.00', '2026-03-23 11:29:03', 0, '0.00', '4.55', '0.00', '0.00', '50.00');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `gst_percent` decimal(5,2) DEFAULT '0.00',
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `product_id`, `gst_percent`, `quantity`, `unit_price`, `total`) VALUES
(185, 133, 8, '5.00', 1, '40.00', '40.00'),
(186, 133, 6, '5.00', 1, '80.10', '80.10'),
(187, 134, 6, '5.00', 1, '80.10', '80.10'),
(188, 135, 6, '5.00', 1, '80.10', '80.10'),
(189, 136, 10, '5.00', 1, '40.00', '40.00'),
(191, 138, 6, '5.00', 1, '80.10', '80.10'),
(192, 139, 6, '5.00', 1, '80.10', '80.10'),
(193, 140, 6, '5.00', 1, '80.10', '80.10'),
(194, 141, 9, '18.00', 1, '80.00', '80.00'),
(195, 142, 8, '5.00', 1, '40.00', '40.00'),
(196, 143, 9, '18.00', 3, '80.00', '240.00'),
(197, 144, 14, '5.00', 1, '45.00', '45.00'),
(198, 145, 9, '18.00', 1, '80.00', '80.00'),
(199, 137, 14, '5.00', 1, '45.00', '45.00'),
(200, 146, 9, '18.00', 1, '80.00', '80.00'),
(201, 147, 8, '5.00', 1, '40.00', '40.00'),
(202, 148, 12, '10.00', 1, '30.40', '30.40'),
(203, 152, 11, '10.00', 1, '30.00', '30.00'),
(204, 153, 11, '10.00', 1, '30.00', '30.00'),
(205, 154, 11, '10.00', 1, '30.00', '30.00'),
(216, 156, 9, '18.00', 1, '80.00', '80.00'),
(217, 156, 6, '5.00', 1, '80.10', '80.10'),
(232, 157, 10, '5.00', 1, '40.00', '40.00'),
(233, 155, 14, '5.00', 1, '45.00', '45.00'),
(235, 158, 12, '10.00', 1, '30.40', '30.40'),
(236, 158, 11, '10.00', 1, '30.00', '30.00'),
(238, 160, 9, '18.00', 1, '80.00', '80.00'),
(239, 159, 6, '5.00', 1, '80.10', '80.10'),
(240, 159, 8, '5.00', 1, '40.00', '40.00'),
(241, 161, 10, '5.00', 1, '40.00', '40.00'),
(242, 162, 8, '5.00', 1, '40.00', '40.00'),
(243, 163, 8, '5.00', 10, '40.00', '400.00'),
(244, 163, 6, '5.00', 10, '80.10', '801.00'),
(245, 164, 14, '5.00', 1, '45.00', '45.00'),
(246, 165, 14, '5.00', 1, '45.00', '45.00'),
(247, 166, 15, '10.00', 10, '50.00', '500.00'),
(253, 168, 6, '5.00', 10, '80.10', '801.00'),
(254, 167, 15, '10.00', 5, '50.00', '250.00'),
(255, 167, 8, '5.00', 1, '40.00', '40.00'),
(260, 169, 12, '10.00', 10, '30.40', '304.00'),
(261, 169, 11, '10.00', 10, '30.00', '300.00'),
(264, 170, 12, '10.00', 10, '30.40', '304.00'),
(321, 171, 9, '18.00', 10, '80.00', '800.00'),
(346, 172, 14, '5.00', 2, '45.00', '90.00'),
(347, 172, 12, '10.00', 1, '30.40', '30.40'),
(352, 173, 15, '10.00', 1, '50.00', '50.00'),
(353, 174, 14, '5.00', 10, '45.00', '450.00'),
(354, 175, 9, '18.00', 1, '80.00', '80.00'),
(355, 176, 6, '5.00', 1, '80.10', '80.10'),
(356, 177, 9, '18.00', 1, '80.00', '80.00'),
(357, 178, 17, '0.00', 1, '30.00', '30.00'),
(358, 179, 15, '10.00', 1, '50.00', '50.00');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_returns`
--

CREATE TABLE `purchase_returns` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `return_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text,
  `reference_no` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `purchase_returns`
--

INSERT INTO `purchase_returns` (`id`, `supplier_id`, `purchase_id`, `return_date`, `amount`, `reason`, `reference_no`, `created_at`) VALUES
(1, 16, NULL, '2026-02-18', '110.00', 'hkh', '', '2026-02-18 11:44:43'),
(2, 16, NULL, '2026-02-18', '80.00', 'demo', '', '2026-02-18 11:45:45'),
(3, 15, NULL, '2026-02-18', '10000.00', 'test', '', '2026-02-18 11:52:04'),
(4, 12, NULL, '2026-02-18', '160.00', 'kk', '', '2026-02-18 11:59:02'),
(5, 15, NULL, '2026-02-18', '10000.00', 'test', '', '2026-02-18 12:08:16'),
(6, 5, NULL, '2026-02-20', '40.00', 'damage', '', '2026-02-20 05:39:48'),
(7, 12, NULL, '2026-02-23', '160.00', 'damage', '', '2026-02-23 06:01:42'),
(8, 16, NULL, '2026-02-24', '1000.00', 'damage', '', '2026-02-24 08:44:40');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `invoiceno` varchar(50) DEFAULT NULL,
  `mode` enum('cash','credit','bank') NOT NULL,
  `bankname` varchar(50) DEFAULT NULL,
  `bank_id` int(11) DEFAULT NULL,
  `totalamt` decimal(10,2) DEFAULT '0.00',
  `paidamt` decimal(10,2) DEFAULT '0.00',
  `balanceamt` decimal(10,2) DEFAULT '0.00',
  `discount_total` decimal(10,2) DEFAULT '0.00',
  `gst_total` decimal(10,2) DEFAULT '0.00',
  `packing_charge` decimal(10,2) DEFAULT '0.00',
  `round_off` decimal(10,2) DEFAULT '0.00',
  `grand_total` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `date`, `invoiceno`, `mode`, `bankname`, `bank_id`, `totalamt`, `paidamt`, `balanceamt`, `discount_total`, `gst_total`, `packing_charge`, `round_off`, `grand_total`, `created_at`) VALUES
(27, 13, '2026-03-02', 'INV-0001', 'cash', NULL, 0, '120.00', '0.00', '0.00', '0.00', '14.11', '0.00', '0.00', '120.00', '2026-03-02 07:36:40'),
(28, 13, '2026-03-02', 'INV-0028', 'bank', 'TMB', 3, '30.00', '0.00', '0.00', '0.00', '1.43', '0.00', '0.00', '30.00', '2026-03-02 07:36:59'),
(29, 25, '2026-03-02', 'INV-0029', 'bank', 'SBI', 1, '30.00', '0.00', '0.00', '0.00', '1.43', '0.00', '0.00', '30.00', '2026-03-02 10:56:20'),
(30, 4, '2026-03-03', 'INV-0030', 'credit', NULL, 0, '80.10', '0.00', '0.00', '0.00', '3.81', '0.00', '-0.10', '80.00', '2026-03-03 05:48:35'),
(31, 4, '2026-03-03', 'INV-0031', 'cash', NULL, 0, '80.10', '0.00', '0.00', '0.00', '3.81', '0.00', '-0.10', '80.00', '2026-03-03 05:53:01'),
(32, 4, '2026-03-03', 'INV-0032', 'credit', NULL, 0, '80.10', '0.00', '0.00', '0.00', '3.81', '0.00', '-0.10', '80.00', '2026-03-03 05:54:36'),
(33, 25, '2026-03-03', 'INV-0033', 'credit', NULL, 0, '30.00', '0.00', '0.00', '0.00', '2.73', '0.00', '0.00', '30.00', '2026-03-03 05:55:20'),
(34, 25, '2026-03-03', 'INV-0034', 'bank', 'SBI', 1, '30.00', '0.00', '0.00', '0.00', '2.73', '0.00', '0.00', '30.00', '2026-03-03 05:56:20'),
(35, 14, '2026-03-03', 'INV-0035', 'cash', NULL, 0, '80.00', '0.00', '0.00', '0.00', '12.20', '0.00', '0.00', '80.00', '2026-03-03 06:09:25'),
(36, 14, '2026-03-03', 'INV-0036', 'credit', NULL, 0, '40.00', '0.00', '0.00', '0.00', '1.90', '0.00', '0.00', '40.00', '2026-03-03 06:09:53'),
(37, 14, '2026-03-03', 'INV-0037', 'bank', 'TMB', 3, '45.00', '0.00', '0.00', '0.00', '2.14', '0.00', '0.00', '45.00', '2026-03-03 06:10:17'),
(38, 25, '2026-03-03', 'INV-0038', 'credit', NULL, 0, '30.00', '0.00', '0.00', '0.00', '2.73', '0.00', '0.00', '30.00', '2026-03-03 06:21:23'),
(39, 4, '2026-03-03', 'INV-0039', 'credit', NULL, 0, '80.00', '0.00', '0.00', '0.00', '12.20', '0.00', '0.00', '80.00', '2026-03-03 06:22:39'),
(40, 4, '2026-03-03', 'INV-0040', 'credit', NULL, 0, '30.00', '0.00', '0.00', '0.00', '2.73', '0.00', '0.00', '30.00', '2026-03-03 06:55:19'),
(41, 14, '2026-03-03', 'INV-0041', 'bank', 'SBI', 1, '80.10', '0.00', '0.00', '0.00', '3.81', '0.00', '-0.10', '80.00', '2026-03-03 11:30:43'),
(42, 26, '2026-03-03', 'INV-0042', 'bank', 'SBI', 1, '40.00', '0.00', '0.00', '0.00', '1.90', '0.00', '0.00', '40.00', '2026-03-03 11:59:45'),
(43, 26, '2026-03-03', 'INV-0043', 'cash', NULL, 0, '30.00', '0.00', '0.00', '0.00', '2.73', '0.00', '0.00', '30.00', '2026-03-03 12:00:49'),
(44, 26, '2026-03-03', 'INV-0044', 'credit', NULL, 0, '30.00', '0.00', '0.00', '0.00', '2.73', '0.00', '0.00', '30.00', '2026-03-03 12:01:04'),
(45, 26, '2026-03-04', 'INV-0045', 'cash', NULL, 0, '45.00', '0.00', '0.00', '5.00', '2.14', '0.00', '0.00', '40.00', '2026-03-04 05:45:25'),
(46, 13, '2026-03-04', 'INV-0046', 'cash', NULL, 0, '80.00', '0.00', '0.00', '0.00', '12.20', '0.00', '0.00', '80.00', '2026-03-04 07:01:37'),
(47, 4, '2026-03-04', 'INV-0047', 'cash', NULL, 0, '30.40', '0.00', '0.00', '0.00', '2.76', '0.00', '-0.40', '30.00', '2026-03-04 07:08:39'),
(48, 4, '2026-03-04', 'INV-0047', 'cash', NULL, 0, '30.40', '0.00', '0.00', '5.00', '2.76', '0.00', '-0.40', '25.00', '2026-03-04 07:11:49'),
(49, 26, '2026-03-04', 'INV-0049', 'cash', NULL, 0, '70.00', '0.00', '0.00', '0.00', '4.63', '0.00', '0.00', '70.00', '2026-03-04 07:20:38'),
(50, 25, '2026-03-04', 'INV-0050', 'bank', 'TMB', 3, '125.10', '0.00', '0.00', '0.00', '5.96', '0.00', '-0.10', '125.00', '2026-03-04 07:21:49'),
(51, 13, '2026-03-04', 'INV-0051', 'cash', NULL, 0, '304.00', '0.00', '0.00', '0.00', '27.64', '0.00', '0.00', '304.00', '2026-03-04 11:40:05'),
(52, 13, '2026-03-06', 'INV-0052', 'credit', NULL, 0, '110.00', '0.00', '0.00', '0.00', '14.93', '0.00', '0.00', '110.00', '2026-03-06 06:31:03'),
(53, 4, '2026-03-06', 'INV-0053', 'cash', NULL, 0, '300.00', '0.00', '0.00', '0.00', '27.27', '0.00', '0.00', '300.00', '2026-03-06 07:18:11'),
(54, 13, '2026-03-06', 'INV-0054', 'cash', NULL, 0, '500.00', '0.00', '0.00', '0.00', '45.45', '0.00', '0.00', '500.00', '2026-03-06 07:52:44'),
(55, 25, '2026-03-06', 'INV-0055', 'cash', NULL, 0, '70.00', '0.00', '0.00', '5.00', '4.63', '10.00', '0.00', '75.00', '2026-03-06 08:20:30'),
(56, 4, '2026-03-06', 'INV-0056', 'credit', NULL, 0, '30.40', '0.00', '0.00', '0.00', '2.76', '0.00', '-0.40', '30.00', '2026-03-06 08:22:10'),
(57, 4, '2026-03-06', 'INV-0057', 'cash', NULL, 0, '951.00', '0.00', '0.00', '0.00', '51.78', '0.00', '0.00', '951.00', '2026-03-06 08:25:13'),
(58, 14, '2026-03-07', 'INV-0058', 'cash', NULL, 3, '80.10', '0.00', '0.00', '0.00', '3.81', '0.00', '-0.10', '80.00', '2026-03-07 07:12:56'),
(59, 4, '2026-03-07', 'INV-0059', 'credit', NULL, 0, '225.00', '0.00', '0.00', '0.00', '10.71', '0.00', '0.00', '225.00', '2026-03-07 07:16:59'),
(60, 25, '2026-03-09', 'INV-0060', 'bank', 'SBI', 1, '300.00', '0.00', '0.00', '0.00', '27.27', '0.00', '0.00', '300.00', '2026-03-09 06:07:12'),
(61, 26, '2026-03-09', 'INV-0061', 'cash', NULL, 0, '40.00', '0.00', '0.00', '0.00', '1.90', '0.00', '0.00', '40.00', '2026-03-09 07:04:34'),
(62, 25, '2026-03-10', 'INV-0062', 'cash', NULL, 0, '30.00', '0.00', '0.00', '0.00', '2.73', '0.00', '0.00', '30.00', '2026-03-10 12:25:07'),
(63, 25, '2026-03-10', 'INV-0063', 'bank', 'SBI', 1, '80.10', '0.00', '0.00', '0.00', '3.81', '0.00', '-0.10', '80.00', '2026-03-10 12:25:33'),
(65, 26, '2026-03-11', 'INV-0064', 'credit', NULL, 0, '90.00', '0.00', '0.00', '0.00', '6.45', '0.00', '0.00', '90.00', '2026-03-11 10:29:25'),
(66, 13, '2026-03-11', 'INV-0066', 'cash', NULL, 0, '110.10', '0.00', '0.00', '0.00', '6.54', '0.00', '-0.10', '110.00', '2026-03-11 12:06:11'),
(67, 4, '2026-03-12', 'INV-0067', 'cash', NULL, 0, '80.00', '0.00', '0.00', '0.00', '8.00', '0.00', '0.00', '88.00', '2026-03-12 07:32:01'),
(68, 4, '2026-03-23', 'INV-0068', 'bank', 'SBI', 1, '70.00', '0.00', '0.00', '0.00', '4.63', '0.00', '0.00', '70.00', '2026-03-23 11:18:32'),
(69, 13, '2026-03-23', 'INV-0069', 'cash', NULL, 0, '30.00', '0.00', '0.00', '0.00', '2.73', '0.00', '0.00', '30.00', '2026-03-23 12:23:01'),
(70, 26, '2026-03-24', 'INV-0070', 'cash', NULL, 0, '30.40', '0.00', '0.00', '0.00', '2.76', '0.00', '-0.40', '30.00', '2026-03-24 07:14:32'),
(71, 14, '2026-03-24', 'INV-0071', 'bank', 'SBI', 1, '80.10', '0.00', '0.00', '0.00', '3.81', '0.00', '-0.10', '80.00', '2026-03-24 07:15:13'),
(72, 14, '2026-03-24', 'INV-0072', 'cash', NULL, 0, '70.00', '0.00', '0.00', '0.00', '4.63', '0.00', '0.00', '70.00', '2026-03-24 07:41:41'),
(73, 14, '2026-03-24', 'INV-0073', 'bank', 'SBI', 1, '120.10', '0.00', '0.00', '0.00', '5.72', '0.00', '-0.10', '120.00', '2026-03-24 07:43:30'),
(74, 14, '2026-03-24', 'INV-0074', 'cash', NULL, 0, '160.10', '0.00', '0.00', '0.00', '11.09', '0.00', '-0.10', '160.00', '2026-03-24 10:17:44');

-- --------------------------------------------------------

--
-- Table structure for table `sales_items`
--

CREATE TABLE `sales_items` (
  `id` int(11) NOT NULL,
  `sales_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `gst_percent` decimal(5,2) DEFAULT '0.00',
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_price` decimal(10,2) DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `sales_items`
--

INSERT INTO `sales_items` (`id`, `sales_id`, `product_id`, `gst_percent`, `quantity`, `unit_price`, `discount_price`, `total`) VALUES
(51, 27, 9, '18.00', 1, '80.00', '0.00', '80.00'),
(52, 27, 10, '5.00', 1, '40.00', '0.00', '40.00'),
(53, 28, 11, '5.00', 1, '30.00', '0.00', '30.00'),
(54, 29, 11, '5.00', 1, '30.00', '0.00', '30.00'),
(55, 30, 6, '5.00', 1, '80.10', '0.00', '80.10'),
(56, 31, 6, '5.00', 1, '80.10', '0.00', '80.10'),
(57, 32, 6, '5.00', 1, '80.10', '0.00', '80.10'),
(58, 33, 11, '10.00', 1, '30.00', '0.00', '30.00'),
(59, 34, 11, '10.00', 1, '30.00', '0.00', '30.00'),
(60, 35, 9, '18.00', 1, '80.00', '0.00', '80.00'),
(61, 36, 8, '5.00', 1, '40.00', '0.00', '40.00'),
(62, 37, 14, '5.00', 1, '45.00', '0.00', '45.00'),
(63, 38, 11, '10.00', 1, '30.00', '0.00', '30.00'),
(64, 39, 9, '18.00', 1, '80.00', '0.00', '80.00'),
(65, 40, 11, '10.00', 1, '30.00', '0.00', '30.00'),
(66, 41, 6, '5.00', 1, '80.10', '0.00', '80.10'),
(67, 42, 8, '5.00', 1, '40.00', '0.00', '40.00'),
(68, 43, 11, '10.00', 1, '30.00', '0.00', '30.00'),
(69, 44, 11, '10.00', 1, '30.00', '0.00', '30.00'),
(79, 45, 14, '5.00', 1, '45.00', '0.00', '45.00'),
(80, 46, 9, '18.00', 1, '80.00', '0.00', '80.00'),
(84, 48, 12, '10.00', 1, '30.40', '0.00', '30.40'),
(86, 49, 10, '5.00', 1, '40.00', '0.00', '40.00'),
(87, 49, 11, '10.00', 1, '30.00', '0.00', '30.00'),
(92, 50, 14, '5.00', 1, '45.00', '0.00', '45.00'),
(93, 50, 6, '5.00', 1, '80.10', '0.00', '80.10'),
(94, 51, 12, '10.00', 10, '30.40', '0.00', '304.00'),
(96, 52, 9, '18.00', 1, '80.00', '0.00', '80.00'),
(97, 52, 11, '10.00', 1, '30.00', '0.00', '30.00'),
(98, 53, 11, '10.00', 10, '30.00', '0.00', '300.00'),
(99, 54, 15, '10.00', 10, '50.00', '0.00', '500.00'),
(102, 55, 8, '5.00', 1, '40.00', '0.00', '40.00'),
(103, 55, 11, '10.00', 1, '30.00', '0.00', '30.00'),
(104, 56, 12, '10.00', 1, '30.40', '0.00', '30.40'),
(107, 57, 15, '10.00', 3, '50.00', '0.00', '150.00'),
(108, 57, 6, '5.00', 10, '80.10', '0.00', '801.00'),
(126, 58, 6, '5.00', 1, '80.10', '0.00', '80.10'),
(128, 59, 14, '5.00', 5, '45.00', '0.00', '225.00'),
(129, 60, 15, '10.00', 6, '50.00', '0.00', '300.00'),
(131, 61, 8, '5.00', 1, '40.00', '0.00', '40.00'),
(132, 62, 11, '10.00', 1, '30.00', '0.00', '30.00'),
(133, 63, 6, '5.00', 1, '80.10', '0.00', '80.10'),
(135, 65, 15, '10.00', 1, '50.00', '0.00', '50.00'),
(136, 65, 8, '5.00', 1, '40.00', '0.00', '40.00'),
(137, 66, 6, '5.00', 1, '80.10', '0.00', '80.10'),
(138, 66, 11, '10.00', 1, '30.00', '0.00', '30.00'),
(139, 67, 11, '10.00', 1, '30.00', '0.00', '30.00'),
(140, 67, 15, '10.00', 1, '50.00', '0.00', '50.00'),
(142, 68, 11, '10.00', 1, '30.00', '0.00', '30.00'),
(143, 68, 8, '5.00', 1, '40.00', '0.00', '40.00'),
(144, 69, 11, '10.00', 1, '30.00', '0.00', '30.00'),
(145, 70, 12, '10.00', 1, '30.40', '0.00', '30.40'),
(148, 71, 6, '5.00', 1, '80.10', '0.00', '80.10'),
(149, 72, 11, '10.00', 1, '30.00', '0.00', '30.00'),
(150, 72, 10, '5.00', 1, '40.00', '0.00', '40.00'),
(151, 73, 6, '5.00', 1, '80.10', '0.00', '80.10'),
(152, 73, 8, '5.00', 1, '40.00', '0.00', '40.00'),
(153, 74, 6, '5.00', 1, '80.10', '0.00', '80.10'),
(154, 74, 15, '10.00', 1, '50.00', '0.00', '50.00'),
(155, 74, 11, '10.00', 1, '30.00', '0.00', '30.00');

-- --------------------------------------------------------

--
-- Table structure for table `sales_payments`
--

CREATE TABLE `sales_payments` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_mode` enum('cash','bank') NOT NULL,
  `bank_name` varchar(50) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `particulars` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `sales_payments`
--

INSERT INTO `sales_payments` (`id`, `customer_id`, `payment_date`, `amount`, `payment_mode`, `bank_name`, `reference_no`, `particulars`, `created_at`) VALUES
(1, 4, '2026-03-06', '20.00', 'cash', NULL, 'PAYMENT', 'Payment to Customer - mona', '2026-03-06 06:53:06'),
(2, 4, '2026-03-06', '100.00', 'bank', 'SBI', 'PAYMENT', 'Payment to Customer - mona', '2026-03-06 06:55:53'),
(3, 14, '2026-03-06', '65.00', 'bank', 'TMB', 'PAYMENT', 'Payment to Customer - suga', '2026-03-06 06:57:22'),
(4, 26, '2026-03-06', '25.00', 'bank', 'SBI', 'PAYMENT', 'Payment to Customer - riya', '2026-03-06 07:00:09'),
(5, 4, '2026-03-06', '220.00', 'cash', NULL, 'PAYMENT', 'Payment to Customer - mona', '2026-03-06 08:22:40'),
(6, 25, '2026-03-07', '225.00', 'bank', 'SBI', 'PAYMENT', 'Payment to Customer - Luna', '2026-03-07 07:18:01'),
(7, 13, '2026-03-07', '30.00', 'cash', NULL, 'PAYMENT', 'Payment to Customer - jc', '2026-03-07 07:20:08'),
(8, 25, '2026-03-09', '40.00', 'cash', NULL, 'PAYMENT', 'Payment to Customer - Luna', '2026-03-09 08:26:42'),
(9, 25, '2026-03-24', '40.00', 'bank', 'SBI', 'PAYMENT', 'Payment to Customer - Luna', '2026-03-24 08:11:23'),
(10, 4, '2026-03-24', '700.00', 'bank', 'SBI', 'PAYMENT', 'Payment to Customer - mona', '2026-03-24 08:46:23'),
(11, 14, '2026-03-24', '82.00', 'cash', NULL, 'PAYMENT', 'Payment to Customer - suga', '2026-03-24 10:20:52'),
(12, 25, '2026-03-24', '10.00', 'cash', NULL, 'PAYMENT', 'Payment to Customer - Luna', '2026-03-24 10:31:22'),
(13, 26, '2026-03-24', '40.00', 'cash', NULL, 'PAYMENT', 'Payment to Customer - riya', '2026-03-24 10:35:34'),
(14, 25, '2026-03-24', '100.00', 'bank', 'TMB', 'PAYMENT', 'Payment to Customer - Luna', '2026-03-24 11:36:36');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `suppliers_name` varchar(50) NOT NULL,
  `phoneno` varchar(10) NOT NULL,
  `email` varchar(200) NOT NULL,
  `sup_address` varchar(200) NOT NULL,
  `gst_no` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `city` varchar(50) NOT NULL,
  `opening_amt` int(11) NOT NULL,
  `sup_state` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `suppliers_name`, `phoneno`, `email`, `sup_address`, `gst_no`, `created_at`, `updated_at`, `city`, `opening_amt`, `sup_state`) VALUES
(5, 'mona', '6543217890', 'suga@gmail.com', 'vnr', 'abc', '2026-02-09 06:47:33', '2026-02-09 06:47:33', '', 0, ''),
(11, 'pavi', '1234567890', '', 'vnr', 'as', '2026-02-09 07:02:44', '2026-02-09 07:02:44', '', 0, ''),
(12, 'Shalini', '6543217890', '', 'vnr', 'jc', '2026-02-09 07:14:09', '2026-02-17 10:21:52', '', 0, ''),
(14, 'Subu', '999999999', '', 'madurai', 'bb', '2026-02-09 07:42:34', '2026-03-12 08:03:27', 'vnr', 5000, 'Tamilnadu'),
(15, 'Durga', '999999991', '', 'sivakasi', '33PQRSX', '2026-02-09 07:51:58', '2026-03-12 08:07:07', 'Mumbai', 10000, 'Maharashtra'),
(16, 'jaya', '999999992', '', 'sivakasi', '33', '2026-02-09 08:23:04', '2026-03-12 08:05:05', 'vnr', 5000, 'Tamilnadu'),
(18, 'Devi', '9234567800', 'dev@gmail.com', 'vnr', 'bb', '2026-02-24 10:54:01', '2026-03-12 08:03:52', 'vnr', 0, 'Tamilnadu'),
(20, 'Sri', '1112223334', 'sri@gmail.com', 'vnr', 'jc', '2026-02-26 06:04:32', '2026-03-12 08:03:42', 'vnr', 0, 'Tamilnadu'),
(22, 'Jennie', '6666644444', 'jen@gmail.com', 'vnr', 'jc', '2026-02-26 06:14:58', '2026-03-12 08:03:37', 'vnr', 0, 'Tamilnadu'),
(23, 'Saran', '9999955555', '', '', '', '2026-03-03 12:02:07', '2026-03-12 08:02:36', '', 0, 'Tamilnadu');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_ledger`
--

CREATE TABLE `supplier_ledger` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` enum('purchase','payment','return','opening') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `particulars` varchar(255) DEFAULT NULL,
  `debit` decimal(10,2) DEFAULT '0.00',
  `credit` decimal(10,2) DEFAULT '0.00',
  `balance` decimal(10,2) DEFAULT '0.00',
  `payment_mode` varchar(20) DEFAULT NULL,
  `bank_name` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `supplier_ledger`
--

INSERT INTO `supplier_ledger` (`id`, `supplier_id`, `transaction_date`, `transaction_type`, `reference_id`, `reference_no`, `particulars`, `debit`, `credit`, `balance`, `payment_mode`, `bank_name`, `created_at`) VALUES
(1, 23, '2026-03-03', 'payment', 24, '', 'Payment for purchases', '0.00', '20.00', '0.00', 'cash', NULL, '2026-03-03 12:03:43'),
(2, 18, '2026-03-04', 'payment', 25, '', 'Payment for purchases', '0.00', '20.00', '0.00', 'cash', NULL, '2026-03-04 07:33:47');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payments`
--

CREATE TABLE `supplier_payments` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_mode` enum('cash','bank') NOT NULL,
  `bank_name` varchar(50) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `particulars` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `supplier_payments`
--

INSERT INTO `supplier_payments` (`id`, `supplier_id`, `payment_date`, `amount`, `payment_mode`, `bank_name`, `reference_no`, `particulars`, `created_at`) VALUES
(1, 16, '2026-02-18', '1000.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-18 11:47:52'),
(2, 5, '2026-02-20', '1000.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-20 06:00:38'),
(3, 15, '2026-02-20', '1000.00', 'bank', '', '', 'Payment for purchases', '2026-02-20 06:04:35'),
(4, 15, '2026-02-20', '1000.00', 'bank', '', '', 'Payment for purchases', '2026-02-20 06:07:36'),
(5, 15, '2026-02-20', '1000.00', 'bank', '', '', 'Payment for purchases', '2026-02-20 06:08:39'),
(6, 15, '2026-02-20', '80000.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-20 06:09:57'),
(7, 15, '2026-02-20', '80000.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-20 06:10:48'),
(8, 5, '2026-02-20', '500.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-20 06:35:01'),
(9, 11, '2026-02-23', '20000.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-23 05:53:47'),
(10, 12, '2026-02-23', '40000.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-23 06:00:30'),
(11, 15, '2026-02-24', '99010.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-24 08:35:23'),
(12, 16, '2026-02-24', '1000.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-24 08:42:38'),
(13, 14, '2026-02-24', '2000.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-24 10:35:37'),
(14, 18, '2026-02-24', '100.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-24 10:55:39'),
(15, 18, '2026-02-24', '60.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-24 10:56:13'),
(16, 16, '2026-02-24', '2485.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-24 10:57:39'),
(17, 5, '2026-02-24', '490.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-24 11:05:29'),
(18, 5, '2026-02-24', '1000.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-24 11:07:28'),
(19, 18, '2026-02-26', '70.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-26 07:25:08'),
(20, 18, '2026-02-26', '80.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-26 07:42:48'),
(21, 18, '2026-02-26', '70.00', 'cash', NULL, '', 'Payment for purchases', '2026-02-26 08:28:19'),
(22, 16, '2026-03-02', '200.00', 'cash', NULL, '', 'Payment for purchases', '2026-03-02 06:43:46'),
(23, 22, '2026-03-02', '40.00', 'cash', NULL, '', 'Payment for purchases', '2026-03-02 06:56:54'),
(24, 23, '2026-03-03', '20.00', 'cash', NULL, '', 'Payment for purchases', '2026-03-03 12:03:43'),
(25, 18, '2026-03-04', '20.00', 'cash', NULL, '', 'Payment for purchases', '2026-03-04 07:33:47'),
(26, 18, '2026-03-04', '5.00', 'cash', NULL, 'PAY-1772619218', 'Payment to Supplier - Devi', '2026-03-04 10:13:38'),
(27, 22, '2026-03-04', '20.00', 'bank', 'SBI', 'PAY-1772619464', 'Payment to Supplier - Jennie', '2026-03-04 10:17:44'),
(28, 22, '2026-03-04', '5.00', 'bank', 'TMB', 'PAYMENT', 'Payment to Supplier - Jennie', '2026-03-04 10:21:36'),
(29, 15, '2026-03-04', '5.00', 'cash', NULL, 'PAYMENT', 'Payment to Supplier - Durga', '2026-03-04 12:48:15'),
(30, 18, '2026-03-06', '20.00', 'bank', 'SBI', 'PAYMENT', 'Payment to Supplier - Devi', '2026-03-06 06:06:23'),
(31, 22, '2026-03-06', '200.00', 'cash', NULL, 'PAYMENT', 'Payment to Supplier - Jennie', '2026-03-06 06:21:27'),
(33, 22, '2026-03-06', '15.00', 'bank', 'SBI', 'PAYMENT', 'Payment to Supplier - Jennie', '2026-03-06 06:23:13'),
(34, 22, '2026-03-06', '500.00', 'cash', NULL, 'PAYMENT', 'Payment to Supplier - Jennie', '2026-03-06 06:25:36'),
(35, 14, '2026-03-24', '18.00', 'cash', NULL, 'PAYMENT', 'Payment to Supplier - Subu', '2026-03-24 10:34:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(50) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastlogin` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `lastlogin`) VALUES
(1, 'admin', 'admin@gmail.com', '12345', 'admin', '2026-02-06 12:00:42', '2026-03-24 10:15:43'),
(2, 'suga', 'suga@gmail.com', '123', 'staff', '2026-03-12 06:00:34', '2026-03-24 10:15:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bank_id` (`bank_id`);

--
-- Indexes for table `bank`
--
ALTER TABLE `bank`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bank_ledger`
--
ALTER TABLE `bank_ledger`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_id` (`sales_id`),
  ADD KEY `discount_slab_id` (`discount_slab_id`);

--
-- Indexes for table `cash_in_hand`
--
ALTER TABLE `cash_in_hand`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gstslab`
--
ALTER TABLE `gstslab`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`pid`);

--
-- Indexes for table `purchase`
--
ALTER TABLE `purchase`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_id` (`purchase_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `purchase_id` (`purchase_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_id` (`sales_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sales_payments`
--
ALTER TABLE `sales_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_ledger`
--
ALTER TABLE `supplier_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bank`
--
ALTER TABLE `bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bank_ledger`
--
ALTER TABLE `bank_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_in_hand`
--
ALTER TABLE `cash_in_hand`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `gstslab`
--
ALTER TABLE `gstslab`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `purchase`
--
ALTER TABLE `purchase`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=180;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=359;

--
-- AUTO_INCREMENT for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `sales_items`
--
ALTER TABLE `sales_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT for table `sales_payments`
--
ALTER TABLE `sales_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `supplier_ledger`
--
ALTER TABLE `supplier_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`bank_id`) REFERENCES `bank` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`sales_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bills_ibfk_2` FOREIGN KEY (`discount_slab_id`) REFERENCES `gstslab` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase`
--
ALTER TABLE `purchase`
  ADD CONSTRAINT `purchase_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchase` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`pid`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD CONSTRAINT `purchase_returns_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_returns_ibfk_2` FOREIGN KEY (`purchase_id`) REFERENCES `purchase` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD CONSTRAINT `sales_items_ibfk_1` FOREIGN KEY (`sales_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`pid`) ON DELETE CASCADE;

--
-- Constraints for table `sales_payments`
--
ALTER TABLE `sales_payments`
  ADD CONSTRAINT `sales_payments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_ledger`
--
ALTER TABLE `supplier_ledger`
  ADD CONSTRAINT `supplier_ledger_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD CONSTRAINT `supplier_payments_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

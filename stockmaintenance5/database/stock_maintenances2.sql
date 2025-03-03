-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 03, 2025 at 10:37 AM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stock_maintenances2`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(50) NOT NULL,
  `customer_id` varchar(50) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `mobile_number` varchar(15) NOT NULL,
  `business_name` varchar(100) DEFAULT NULL,
  `gst_number` varchar(20) DEFAULT NULL,
  `address` text,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id` (`unique_id`),
  UNIQUE KEY `customer_id` (`customer_id`),
  UNIQUE KEY `mobile_number` (`mobile_number`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `unique_id`, `customer_id`, `customer_name`, `mobile_number`, `business_name`, `gst_number`, `address`, `created_date`, `deleted_at`) VALUES
(1, '677e562ca720f', 'CUST_001', 'Surya', '9876543211', 'Surya Enterprises', 'GST125', 'Virudhunagar', '2025-01-08 10:40:44', 0),
(2, '679b1ff809fe1', 'CUST_002', 'Kannan', '9876543210', 'Oil Enterprises', 'GST123', 'Virudhunagar', '2025-01-30 06:45:12', 0),
(3, '67ab32dc6c18f', 'CUST_003', 'Prakash', '73453535352', 'Prakash Enterprises', 'GST002', 'Virudhunagar', '2025-02-11 11:22:04', 0);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(191) NOT NULL,
  `table_type` varchar(255) NOT NULL,
  `types_unique_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `order_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `vendor_customer_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `invoice_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `lr_no` varchar(100) NOT NULL,
  `lr_date` date DEFAULT NULL,
  `shipment_date` date DEFAULT NULL,
  `shipment_name` varchar(255) DEFAULT NULL,
  `transport_name` varchar(255) DEFAULT NULL,
  `delivery_details` text,
  `original_unique_id` varchar(255) NOT NULL,
  `staff_id` varchar(255) NOT NULL,
  `staff_name` varchar(255) NOT NULL,
  `product_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `product_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `sku` varchar(255) NOT NULL,
  `quantity` int NOT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `product` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `sales_through` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `admin_confirmation` tinyint(1) DEFAULT '0',
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_unique_id` (`unique_id`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `unique_id`, `table_type`, `types_unique_id`, `order_id`, `vendor_customer_id`, `invoice_number`, `lr_no`, `lr_date`, `shipment_date`, `shipment_name`, `transport_name`, `delivery_details`, `original_unique_id`, `staff_id`, `staff_name`, `product_id`, `product_name`, `sku`, `quantity`, `mrp`, `product`, `sales_through`, `admin_confirmation`, `created_date`, `deleted_at`) VALUES
(1, '67b581368b4ca', 'purchase', '67b56ca5bb25c', 'ORD_003', 'VEN_002', 'INV/2025-02-19/003', '', NULL, NULL, NULL, NULL, NULL, '67b56ca5be4e0', 'STA_006', 'Karthick', 'PROD-002', 'Oil Filter', '502', 70, 900.00, 'N/A', 'N/A', 1, '2025-02-19 06:59:02', 0),
(2, '67b581368b957', 'purchase', '67b56ca5bb25c', 'ORD_003', 'VEN_002', 'INV/2025-02-19/003', '', NULL, NULL, NULL, NULL, NULL, '67b56ca5bf3fe', 'STA_006', 'Karthick', 'PROD-003', 'Oil Filter', '502', 120, 900.00, 'N/A', 'N/A', 0, '2025-02-19 06:59:02', 0),
(3, '67b5813ef3579', 'sales', '67b412c8f3993', 'SALE_006', 'CUST_002', 'INV/2025-02-18/006', '', NULL, NULL, NULL, NULL, NULL, '67b412c904db8', 'STA_007', 'Jona', 'PROD-002', 'Oil Filter', '502', 63, 800.00, 'Original', 'DMS Stock', 0, '2025-02-19 06:59:10', 0),
(4, '67b5813ef3938', 'sales', '67b412c8f3993', 'SALE_006', 'CUST_002', 'INV/2025-02-18/006', '', NULL, NULL, NULL, NULL, NULL, '67b412c90802f', 'STA_007', 'Jona', 'PROD-003', 'Oil Filter', '502', 40, 900.00, 'Original', 'Excess Stock', 0, '2025-02-19 06:59:10', 0),
(5, '67b7231defe8c', 'purchase', '67b56ca5bb25c', 'ORD_003', 'VEN_001', 'INV/2025-02-19/003', '', NULL, NULL, NULL, NULL, NULL, '67b56ca5be4e0', 'STA_001', 'Kannan', 'PROD-001', 'Engine Oil', '501', 100, 600.00, 'N/A', 'N/A', 0, '2025-02-20 12:42:05', 0),
(6, '67b7231df17f7', 'purchase', '67b56ca5bb25c', 'ORD_003', 'VEN_001', 'INV/2025-02-19/003', '', NULL, NULL, NULL, NULL, NULL, '67b56ca5bf3fe', 'STA_001', 'Kannan', 'PROD-003', 'Engine Oil', '501', 120, 900.00, 'N/A', 'N/A', 0, '2025-02-20 12:42:05', 0),
(7, '67b802921f240', 'purchase', '67b56ca5bb25c', 'ORD_003', 'VEN_002', 'INV/2025-02-19/003', '', NULL, NULL, NULL, NULL, NULL, '67b56ca5be4e0', 'STA_001', 'Kannan', 'PROD-001', 'Engine Oil', '501', 200, 600.00, 'N/A', 'N/A', 1, '2025-02-21 04:35:30', 0),
(8, '67b8029220d4f', 'purchase', '67b56ca5bb25c', 'ORD_003', 'VEN_002', 'INV/2025-02-19/003', '', NULL, NULL, NULL, NULL, NULL, '67b56ca5bf3fe', 'STA_001', 'Kannan', 'PROD-003', 'Engine Oil', '501', 200, 900.00, 'N/A', 'N/A', 1, '2025-02-21 04:35:30', 0),
(9, '67b82a5ec0381', 'sales', '67b412c8f3993', 'SALE_006', 'CUST_002', 'INV/2025-02-18/006', '', NULL, NULL, NULL, NULL, NULL, '67b412c904db8', 'STA_007', 'Jona', 'PROD-002', 'Oil Filter', '502', 63, 800.00, 'Original', 'DMS Stock', 1, '2025-02-21 07:25:18', 0),
(10, '67b82a5ec0b85', 'sales', '67b412c8f3993', 'SALE_006', 'CUST_002', 'INV/2025-02-18/006', '', NULL, NULL, NULL, NULL, NULL, '67b412c90802f', 'STA_007', 'Jona', 'PROD-003', 'Oil Can', '503', 40, 900.00, 'Original', 'Excess Stock', 1, '2025-02-21 07:25:18', 0),
(11, '67b82afb22336', 'purchase', '67b56ca5bb25c', 'ORD_003', 'VEN_002', 'INV/2025-02-19/003', '', NULL, NULL, NULL, NULL, NULL, '67b56ca5be4e0', 'STA_001', 'Kannan', 'PROD-001', 'Engine Oil', '501', 200, 600.00, 'N/A', 'N/A', 1, '2025-02-21 07:27:55', 0),
(12, '67b82afb224f8', 'purchase', '67b56ca5bb25c', 'ORD_003', 'VEN_002', 'INV/2025-02-19/003', '', NULL, NULL, NULL, NULL, NULL, '67b56ca5bf3fe', 'STA_001', 'Kannan', 'PROD-003', 'Oil Can', '503', 200, 900.00, 'N/A', 'N/A', 1, '2025-02-21 07:27:55', 0),
(13, '67b8329bd6894', 'purchase', '67b56ca5bb25c', 'ORD_003', 'VEN_002', 'INV/2025-02-21/003', '', NULL, NULL, NULL, NULL, NULL, '67b56ca5be4e0', 'STA_008', 'Selvam', 'PROD-001', 'Engine Oil', '501', 200, 500.00, 'N/A', 'N/A', 1, '2025-02-21 08:00:27', 0),
(14, '67b832e340cc7', 'purchase', '67b56ca5bb25c', 'ORD_003', 'VEN_002', 'INV/2025-02-21/003', '', NULL, NULL, NULL, NULL, NULL, '67b56ca5be4e0', 'STA_008', 'Selvam', 'PROD-002', 'Oil Filter', '502', 200, 800.00, 'N/A', 'N/A', 1, '2025-02-21 08:01:39', 0),
(15, '67b979493fffd', 'sales', '67b412c8f3993', 'SALE_006', 'CUST_001', 'INV/2025-02-22/006', '', NULL, NULL, NULL, NULL, NULL, '67b412c904db8', 'STA_007', 'Jona', 'PROD-002', 'Oil Filter', '502', 63, 800.00, 'Original', 'DMS Stock', 1, '2025-02-22 07:14:17', 0),
(16, '67b979494085a', 'sales', '67b412c8f3993', 'SALE_006', 'CUST_001', 'INV/2025-02-22/006', '', NULL, NULL, NULL, NULL, NULL, '67b412c90802f', 'STA_007', 'Jona', 'PROD-003', 'Oil Can', '503', 40, 900.00, 'Original', 'Excess Stock', 1, '2025-02-22 07:14:17', 0),
(17, '67bc019961692', 'sales', '67b412c8f3993', 'SALE_006', 'CUST_001', 'INV/2025-02-22/006', 'LR123456', '2025-02-22', '2025-02-23', 'Express Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '67b412c904db8', 'STA_006', 'Karthick', 'PROD-002', 'Oil Filter', '502', 63, 800.00, 'Original', 'DMS Stock', 1, '2025-02-24 05:20:25', 0),
(18, '67bc019961d72', 'sales', '67b412c8f3993', 'SALE_006', 'CUST_001', 'INV/2025-02-22/006', 'LR123456', '2025-02-22', '2025-02-23', 'Express Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '67b412c90802f', 'STA_006', 'Karthick', 'PROD-003', 'Oil Can', '503', 40, 900.00, 'Original', 'Excess Stock', 1, '2025-02-24 05:20:25', 0),
(19, '67bec2a9e202e', 'sales', '67bea53eab3c7', 'SALE_009', 'CUST_002', 'INV/2025-02-28/009', 'LR123456', '2025-02-22', '2025-02-23', 'Express Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '67bea53eb049e', 'STA_008', 'Selvam', 'PROD-003', 'Oil Can', '503', 100, 1000.00, 'Duplicate', 'Excess Stock', 1, '2025-02-26 07:28:41', 0),
(20, '67bec2a9e26f9', 'sales', '67bea53eab3c7', 'SALE_009', 'CUST_002', 'INV/2025-02-28/009', 'LR123456', '2025-02-22', '2025-02-23', 'Express Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '67bea53eb103d', 'STA_008', 'Selvam', 'PROD-001', 'Engine Oil', '501', 34, 500.00, 'Original', 'Excess Stock', 1, '2025-02-26 07:28:41', 0),
(21, '67becbf637afd', 'purchase', '67b56ca5bb25c', 'ORD_003', 'VEN_001', 'INV/2025-02-26/003', '', NULL, NULL, NULL, NULL, NULL, '67b56ca5be4e0', 'STA_007', 'Jona', 'PROD-001', 'Engine Oil', '501', 30, 500.00, 'N/A', 'N/A', 1, '2025-02-26 08:08:22', 0),
(22, '67bf0e29cb917', 'sales', '67bea53eab3c7', 'SALE_009', 'CUST_002', 'INV/2025-02-28/009', 'LR123456', '2025-02-22', '2025-02-23', 'UExpress Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '67bea53eb049e', 'STA_008', 'Selvam', 'PROD-003', 'Oil Can', '503', 101, 1000.00, 'Duplicate', 'Excess Stock', 0, '2025-02-26 12:50:49', 0),
(23, '67bf0fa530106', 'sales', '67bea53eab3c7', 'SALE_009', 'CUST_002', 'INV/2025-02-28/009', 'LR123456', '2025-02-22', '2025-02-23', 'USExpress Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '67bea53eb049e', 'STA_008', 'Selvam', 'PROD-003', 'Oil Can', '503', 101, 1000.00, 'Duplicate', 'Excess Stock', 0, '2025-02-26 12:57:09', 0),
(24, '67bf0fd832356', 'sales', '67bea53eab3c7', 'SALE_009', 'CUST_002', 'INV/2025-02-28/009', 'LR123456', '2025-02-22', '2025-02-23', 'USExpress Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '67bea53eb049e', 'STA_008', 'Selvam', 'PROD-003', 'Oil Can', '503', 101, 1000.00, 'Duplicate', 'Excess Stock', 0, '2025-02-26 12:58:00', 0),
(25, '67bf10188f609', 'sales', '67bea53eab3c7', 'SALE_009', 'CUST_002', 'INV/2025-02-28/009', 'LR123456', '2025-02-22', '2025-02-23', 'USSExpress Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '67bea53eb049e', 'STA_008', 'Selvam', 'PROD-003', 'Oil Can', '503', 101, 1000.00, 'Duplicate', 'Excess Stock', 0, '2025-02-26 12:59:04', 0),
(26, '67bf11d780539', 'sales', '67bea53eab3c7', 'SALE_009', 'CUST_001', 'INV/2025-02-28/009', 'LR123456', '2025-02-22', '2025-02-23', 'USSExpress Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '67bea53eb049e', 'STA_008', 'Selvam', 'PROD-003', 'Oil Can', '503', 101, 1000.00, 'Duplicate', 'Excess Stock', 0, '2025-02-26 13:06:31', 0),
(27, '67bf1574c9325', 'sales', '67bea53eab3c7', 'SALE_009', 'CUST_002', 'INV/2025-02-28/009', 'LR123456', '2025-02-22', '2025-02-23', 'USSExpress Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '67bea53eb049e', 'STA_008', 'Selvam', 'PROD-003', 'Oil Can', '503', 101, 1000.00, 'Duplicate', 'Excess Stock', 0, '2025-02-26 13:21:56', 0),
(28, '67bf168bc10b4', 'sales', '67bea53eab3c7', 'SALE_009', 'CUST_002', 'INV/2025-02-28/009', 'LR123456', '2025-02-22', '2025-02-23', 'USSExpress Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '', '', '', '', '', '', 0, 0.00, '', '', 0, '2025-02-26 13:26:35', 0),
(29, '67bf168bc161b', 'sales', '67bea53eab3c7', 'SALE_009', 'CUST_002', 'INV/2025-02-28/009', 'LR123456', '2025-02-22', '2025-02-23', 'USSExpress Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '67bea53eb049e', 'STA_008', 'Selvam', 'PROD-003', 'Oil Can', '503', 101, 1000.00, 'Duplicate', 'Excess Stock', 0, '2025-02-26 13:26:35', 0),
(30, '67bf169a3707d', 'sales', '67bea53eab3c7', 'SALE_009', 'CUST_001', 'INV/2025-02-28/010', 'LR123456', '2025-02-22', '2025-02-23', 'USSExpress Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '', '', '', '', '', '', 0, 0.00, '', '', 0, '2025-02-26 13:26:50', 0),
(31, '67bf169a37439', 'sales', '67bea53eab3c7', 'SALE_009', 'CUST_001', 'INV/2025-02-28/010', 'LR123456', '2025-02-22', '2025-02-23', 'USSExpress Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '67bea53eb049e', 'STA_008', 'Selvam', 'PROD-003', 'Oil Can', '503', 101, 1000.00, 'Duplicate', 'Excess Stock', 0, '2025-02-26 13:26:50', 0),
(32, '67c55bf1aabd7', 'purchase', '67b56ca5bb25c', 'ORD_003', 'VEN_001', 'INV/2025-03-03/003', '', NULL, NULL, NULL, NULL, NULL, '67b56ca5bf3fe', 'STA_007', 'Jona', 'PROD-003', 'Oil Can', '503', 200, 900.00, 'N/A', 'N/A', 0, '2025-03-03 07:36:17', 0);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
CREATE TABLE IF NOT EXISTS `product` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `product_id` varchar(20) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `sku` varchar(20) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `subunit` varchar(50) NOT NULL,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `delete_at` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`),
  UNIQUE KEY `sku` (`sku`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`id`, `unique_id`, `date`, `product_id`, `product_name`, `sku`, `unit`, `subunit`, `created_date`, `delete_at`) VALUES
(1, 'b769eb1b-e06a-11ef-87f9-989096d40073', '2025-02-01', 'PROD-001', 'Engine Oil', '501', '1 Bucket', '10 L', '2025-02-01 07:04:01', 0),
(2, 'ee6a5329-e06a-11ef-87f9-989096d40073', '2025-02-01', 'PROD-002', 'Oil Filter', '502', '1 Barrel', '100 L', '2025-02-01 07:05:34', 0),
(3, '948424e3-e529-11ef-9168-989096d40073', '2025-02-07', 'PROD-003', 'Oil Can', '503', '1 Box', '10 L', '2025-02-07 08:00:21', 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_mrp`
--

DROP TABLE IF EXISTS `product_mrp`;
CREATE TABLE IF NOT EXISTS `product_mrp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(50) NOT NULL,
  `product_id` varchar(20) NOT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `opening_stock` int NOT NULL,
  `current_stock` int NOT NULL,
  `minimum_stock` int NOT NULL,
  `excess_stock` int NOT NULL,
  `physical_stock` int NOT NULL,
  `notification` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `delete_at` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_mrp`
--

INSERT INTO `product_mrp` (`id`, `unique_id`, `product_id`, `mrp`, `opening_stock`, `current_stock`, `minimum_stock`, `excess_stock`, `physical_stock`, `notification`, `created_date`, `delete_at`) VALUES
(1, 'b76ac932-e06a-11ef-87f9-989096d40073', 'PROD-001', 500.00, 80, 700, 20, 0, 700, '', '2025-02-01 07:04:02', 0),
(2, 'b76addff-e06a-11ef-87f9-989096d40073', 'PROD-001', 600.00, 60, 200, 15, 200, 400, '', '2025-02-01 07:04:02', 0),
(3, 'ee6a8d7a-e06a-11ef-87f9-989096d40073', 'PROD-002', 800.00, 300, 571, 40, 38, 609, '', '2025-02-01 07:05:34', 0),
(4, 'ee6a9b82-e06a-11ef-87f9-989096d40073', 'PROD-002', 900.00, 250, 430, 35, 60, 490, '', '2025-02-01 07:05:34', 0),
(5, '9484dd7f-e529-11ef-9168-989096d40073', 'PROD-003', 1000.00, 300, 200, 40, 100, 300, '', '2025-02-07 08:00:21', 0),
(6, '9484e95e-e529-11ef-9168-989096d40073', 'PROD-003', 900.00, 250, 480, 35, 30, 510, '', '2025-02-07 08:00:21', 0),
(7, 'e994dcae-eeb9-11ef-a944-989096d40073', 'PROD-003', 500.00, 730, 730, 55, 0, 730, '', '2025-02-19 12:06:12', 0);

-- --------------------------------------------------------

--
-- Table structure for table `purchase`
--

DROP TABLE IF EXISTS `purchase`;
CREATE TABLE IF NOT EXISTS `purchase` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `order_id` varchar(50) NOT NULL,
  `vendor_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `vendor_name` varchar(100) NOT NULL,
  `mobile_number` varchar(15) NOT NULL,
  `business_name` varchar(100) DEFAULT NULL,
  `gst_number` varchar(20) DEFAULT NULL,
  `address` text,
  `invoice_number` varchar(50) NOT NULL,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase`
--

INSERT INTO `purchase` (`id`, `unique_id`, `date`, `order_id`, `vendor_id`, `vendor_name`, `mobile_number`, `business_name`, `gst_number`, `address`, `invoice_number`, `created_date`, `deleted_at`) VALUES
(1, '679dcb8b0e87d', '2024-02-01', 'ORD_001', 'VEN_001', 'Radha Krishnan', '9876543210', 'Radha Enterprises', 'GST005', 'Madurai', 'INV/2025-02-01/001', '2025-02-01 07:21:47', 0),
(2, '67ade0050bd32', '2024-02-13', 'ORD_002', 'VEN_003', 'Kannan', '832478234', 'Oil Business', 'GST003', 'Virudhunagar', 'INV/2025-02-13/002', '2025-02-13 12:05:25', 0),
(3, '67b56ca5bb25c', '2025-02-13', 'ORD_003', 'VEN_001', 'Radha Krishnan', '9876543210', 'Radha Enterprises', 'GST005', 'Madurai', 'INV/2025-03-03/004', '2025-02-19 05:31:17', 0);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_mrp`
--

DROP TABLE IF EXISTS `purchase_mrp`;
CREATE TABLE IF NOT EXISTS `purchase_mrp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(50) NOT NULL,
  `order_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `product_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `quantity` varchar(55) NOT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_mrp`
--

INSERT INTO `purchase_mrp` (`id`, `unique_id`, `order_id`, `product_id`, `product_name`, `sku`, `quantity`, `mrp`, `created_date`, `deleted_at`) VALUES
(1, '679dcb8b0f6a3', 'ORD_001', 'PROD-001', 'Engine Oil', '501', '200 Bucket', 500.00, '2025-02-01 07:21:47', 0),
(2, '679dcb8b0ff97', 'ORD_001', 'PROD-001', 'Engine Oil', '501', '400 Bucket', 600.00, '2025-02-01 07:21:47', 0),
(3, '679dcb8b10437', 'ORD_001', 'PROD-002', 'Oil Filter', '502', '100 Barrel', 800.00, '2025-02-01 07:21:47', 0),
(4, '679dcb8b108af', 'ORD_001', 'PROD-002', 'Oil Filter', '502', '200 Barrel', 900.00, '2025-02-01 07:21:47', 0),
(5, '67ade005132e0', 'ORD_002', 'PROD-001', 'Engine Oil', '501', '500 Bucket', 500.00, '2025-02-13 12:05:25', 0),
(6, '67b56ca5be4e0', 'ORD_003', 'PROD-001', 'Engine Oil', '501', '30 Bucket', 500.00, '2025-02-19 05:31:17', 0),
(7, '67b56ca5bf3fe', 'ORD_003', 'PROD-003', 'Oil Can', '503', '200 Box', 900.00, '2025-02-19 05:31:17', 0);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
CREATE TABLE IF NOT EXISTS `sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `order_id` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `customer_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `customer_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `mobile_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `business_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `gst_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `address` text,
  `lr_no` varchar(100) NOT NULL,
  `lr_date` date DEFAULT NULL,
  `shipment_date` date DEFAULT NULL,
  `shipment_name` varchar(255) DEFAULT NULL,
  `transport_name` varchar(255) DEFAULT NULL,
  `delivery_details` text,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `unique_id`, `date`, `order_id`, `invoice_number`, `customer_id`, `customer_name`, `mobile_number`, `business_name`, `gst_number`, `address`, `lr_no`, `lr_date`, `shipment_date`, `shipment_name`, `transport_name`, `delivery_details`, `created_date`, `deleted_at`) VALUES
(1, '679dcc56e5bf1', '2025-02-01', 'SALE_001', 'INV/2025-02-01/001', 'CUST_001', 'Surya', '9876543211', 'Surya Enterprises', 'GST125', 'Virudhunagar', '', NULL, NULL, NULL, NULL, NULL, '2025-02-01 07:25:10', 0),
(2, '679dec4021cee', '2025-02-01', 'SALE_002', 'INV/2025-02-01/002', 'CUST_002', 'Prakash', '73453535353', 'Prakash Enterprises', 'GST002', 'Virudhunagar', '', NULL, NULL, NULL, NULL, NULL, '2025-02-01 09:41:20', 0),
(3, '679dee06caa70', '2025-02-01', 'SALE_003', 'INV/2025-02-01/003', 'CUST_002', 'Prakash', '73453535353', 'Prakash Enterprises', 'GST002', 'Virudhunagar', '', NULL, NULL, NULL, NULL, NULL, '2025-02-01 09:48:54', 0),
(4, '679def16cbdad', '2025-02-01', 'SALE_004', 'INV/2025-02-01/004', 'CUST_002', 'Prakash', '73453535353', 'Prakash Enterprises', 'GST002', 'Virudhunagar', '', NULL, NULL, NULL, NULL, NULL, '2025-02-01 09:53:26', 0),
(5, '67a9d2515c7ee', '2025-02-10', 'SALE_005', 'INV/2025-02-10/005', 'CUST_002', 'Prakash', '73453535353', 'Prakash Enterprises', 'GST002', 'Virudhunagar', '', NULL, NULL, NULL, NULL, NULL, '2025-02-10 10:17:53', 0),
(6, '67b412c8f3993', '2025-02-18', 'SALE_006', 'INV/2025-02-22/006', 'CUST_001', 'Surya', '9876543211', 'Surya Enterprises', 'GST125', 'Virudhunagar', 'LR123456', '2025-02-22', '2025-02-23', 'Express Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '2025-02-18 04:55:37', 0),
(7, '67bbfb0652ab1', '2025-02-18', 'SALE_007', 'INV/2025-02-22/007', 'CUST_002', 'Kannan', '9876543210', 'Oil Enterprises', 'GST123', 'Virudhunagar', 'LR123456', '2025-02-22', '2025-02-23', 'Express Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '2025-02-24 04:52:22', 0),
(8, '67bc4f618dd08', '2025-02-18', 'SALE_008', 'INV/2025-02-18/006', 'CUST_002', 'Kannan', '9876543210', 'Oil Enterprises', 'GST123', 'Virudhunagar', '', '0000-00-00', '0000-00-00', '', '', '', '2025-02-24 10:52:17', 0),
(9, '67bea53eab3c7', '2025-02-26', 'SALE_009', 'INV/2025-02-28/010', 'CUST_001', 'Surya', '9876543211', 'Surya Enterprises', 'GST125', 'Virudhunagar', 'LR123456', '2025-02-22', '2025-02-23', 'USSExpress Shipping', 'XYZ Transport', 'Delivered at warehouse, received by Mr. John', '2025-02-26 05:23:10', 0);

-- --------------------------------------------------------

--
-- Table structure for table `sales_mrp`
--

DROP TABLE IF EXISTS `sales_mrp`;
CREATE TABLE IF NOT EXISTS `sales_mrp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(255) NOT NULL,
  `order_id` varchar(255) NOT NULL,
  `product_id` varchar(255) NOT NULL,
  `product_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `sku` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `quantity` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `product` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `sales_through` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sales_mrp`
--

INSERT INTO `sales_mrp` (`id`, `unique_id`, `order_id`, `product_id`, `product_name`, `sku`, `quantity`, `mrp`, `product`, `sales_through`, `created_date`, `deleted_at`) VALUES
(1, '679dcc56e6e39', 'SALE_001', 'PROD-001', 'Engine Oil', '501', '50 Bucket', 500.00, 'Original', 'DMS Stock', '2025-02-01 07:25:10', 0),
(2, '679dcc56e76fd', 'SALE_001', 'PROD-001', 'Engine Oil', '501', '160 Bucket', 600.00, 'Duplicate', 'Excess Stock', '2025-02-01 07:25:10', 0),
(3, '679dcc56e82c7', 'SALE_001', 'PROD-002', 'Oil Filter', '502', '190 Barrel', 800.00, 'Original', 'DMS Stock', '2025-02-01 07:25:10', 0),
(4, '679dec4022d9e', 'SALE_002', 'PROD-001', 'Engine Oil', '501', '60 Bucket', 500.00, 'Original', 'DMS Stock', '2025-02-01 09:41:20', 0),
(5, '679dec40234f1', 'SALE_002', 'PROD-002', 'Oil Filter', '502', '90 Barrel', 800.00, 'Original', 'DMS Stock', '2025-02-01 09:41:20', 0),
(6, '679dee06cafcc', 'SALE_003', 'PROD-001', 'Engine Oil', '501', '310 Bucket', 600.00, 'Duplicate', 'Excess Stock', '2025-02-01 09:48:54', 0),
(7, '679def16cc8fd', 'SALE_004', 'PROD-001', 'Engine Oil', '501', '60 Bucket', 600.00, 'Original', 'Excess Stock', '2025-02-01 09:53:26', 0),
(8, '67a9d25162030', 'SALE_005', 'PROD-003', 'Engine Oil', '501', '100 Box', 900.00, 'Original', 'DMS Stock', '2025-02-10 10:17:53', 0),
(9, '67b412c904db8', 'SALE_006', 'PROD-002', 'Oil Filter', '502', '63 Barrel', 800.00, 'Original', 'DMS Stock', '2025-02-18 04:55:37', 0),
(10, '67b412c90802f', 'SALE_006', 'PROD-003', 'Oil Can', '503', '40 Box', 900.00, 'Original', 'Excess Stock', '2025-02-18 04:55:37', 0),
(11, '67bbfb06574d6', 'SALE_007', 'PROD-001', 'Engine Oil', '501', '60 Bucket', 500.00, 'Original', 'DMS Stock', '2025-02-24 04:52:22', 0),
(12, '67bbfb0658953', 'SALE_007', 'PROD-002', 'Oil Filter', '502', '60 Barrel', 800.00, 'Original', 'DMS Stock', '2025-02-24 04:52:22', 0),
(13, '67bc4f6197bbb', 'SALE_008', 'PROD-001', 'Engine Oil', '501', '60 Bucket', 500.00, 'Original', 'DMS Stock', '2025-02-24 10:52:17', 0),
(14, '67bc4f619a53d', 'SALE_008', 'PROD-002', 'Oil Filter', '502', '60 Barrel', 800.00, 'Original', 'DMS Stock', '2025-02-24 10:52:17', 0),
(15, '67bea53eb049e', 'SALE_009', 'PROD-003', 'Oil Can', '503', '100 Box', 1000.00, 'Duplicate', 'Excess Stock', '2025-02-26 05:23:10', 0),
(16, '67bea53eb103d', 'SALE_009', 'PROD-001', 'Engine Oil', '501', '34 Bucket', 500.00, 'Original', 'Excess Stock', '2025-02-26 05:23:10', 0);

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustment`
--

DROP TABLE IF EXISTS `stock_adjustment`;
CREATE TABLE IF NOT EXISTS `stock_adjustment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `stock_id` varchar(50) NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `adjusted_stock` int NOT NULL,
  `adjusted_type` enum('add','subtract') NOT NULL,
  `reason` text,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_adjustment`
--

INSERT INTO `stock_adjustment` (`id`, `unique_id`, `date`, `stock_id`, `product_id`, `product_name`, `mrp`, `adjusted_stock`, `adjusted_type`, `reason`, `created_date`, `deleted_at`) VALUES
(1, '679dcd08bede6', '2025-02-01', 'STO-001', 'PROD-001', 'Engine Oil', 500.00, 1, 'subtract', 'Damaged items', '2025-02-01 07:28:08', 0),
(2, '67b8622ce4de0', '2025-02-21', 'STO-002', 'PROD-002', 'Oil Filter', 800.00, 8, 'subtract', 'Damaged Stock', '2025-02-21 11:23:24', 0);

-- --------------------------------------------------------

--
-- Table structure for table `stock_history`
--

DROP TABLE IF EXISTS `stock_history`;
CREATE TABLE IF NOT EXISTS `stock_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(255) DEFAULT NULL,
  `types` enum('inward','outward') NOT NULL,
  `invoice_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `vendor_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `customer_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `product_id` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `order_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `sku` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `quantity` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_history`
--

INSERT INTO `stock_history` (`id`, `unique_id`, `types`, `invoice_number`, `vendor_id`, `customer_id`, `product_id`, `order_id`, `sku`, `mrp`, `quantity`, `created_date`, `deleted_at`) VALUES
(1, '679dcb8b0f6a3', 'inward', 'INV/2025-02-01/001', 'VEN_001', 'N/A', 'PROD-001', 'ORD_001', '501', 500.00, '200 Bucket', '2025-02-01 07:21:47', 0),
(2, '679dcb8b0ff97', 'inward', 'INV/2025-02-01/001', 'VEN_001', 'N/A', 'PROD-001', 'ORD_001', '501', 600.00, '400 Bucket', '2025-02-01 07:21:47', 0),
(3, '679dcb8b10437', 'inward', 'INV/2025-02-01/001', 'VEN_001', 'N/A', 'PROD-002', 'ORD_001', '502', 800.00, '100 Barrel', '2025-02-01 07:21:47', 0),
(4, '679dcb8b108af', 'inward', 'INV/2025-02-01/001', 'VEN_001', 'N/A', 'PROD-002', 'ORD_001', '502', 900.00, '200 Barrel', '2025-02-01 07:21:47', 0),
(5, '679dcc56e6e39', 'outward', 'INV/2025-02-01/001', 'N/A', 'CUST_001', 'PROD-001', 'SALE_001', '501', 500.00, '50 Bucket', '2025-02-01 07:25:10', 0),
(6, '679dcc56e76fd', 'outward', 'INV/2025-02-01/001', 'N/A', 'CUST_001', 'PROD-001', 'SALE_001', '501', 600.00, '160 Bucket', '2025-02-01 07:25:10', 0),
(7, '679dcc56e82c7', 'outward', 'INV/2025-02-01/001', 'N/A', 'CUST_001', 'PROD-002', 'SALE_001', '502', 800.00, '190 Barrel', '2025-02-01 07:25:10', 0),
(8, '679dec4022d9e', 'outward', 'INV/2025-02-01/002', 'N/A', 'CUST_002', 'PROD-001', 'SALE_002', '501', 500.00, '60 Bucket', '2025-02-01 09:41:20', 0),
(9, '679dec40234f1', 'outward', 'INV/2025-02-01/002', 'N/A', 'CUST_002', 'PROD-002', 'SALE_002', '502', 800.00, '90 Barrel', '2025-02-01 09:41:20', 0),
(10, '679dee06cafcc', 'outward', 'INV/2025-02-01/002', 'N/A', 'CUST_002', 'PROD-001', 'SALE_003', '501', 600.00, '310 Bucket', '2025-02-01 09:48:54', 0),
(11, '679def16cc8fd', 'outward', 'INV/2025-02-01/004', 'N/A', 'CUST_002', 'PROD-001', 'SALE_004', '501', 600.00, '60 Bucket', '2025-02-01 09:53:26', 0),
(12, '67a9d25162030', 'outward', 'INV/2025-02-10/005', 'N/A', 'CUST_002', 'PROD-001', 'SALE_005', '501', 500.00, '64 Bucket', '2025-02-10 10:17:53', 0),
(13, '67ade005132e0', 'inward', 'INV/2025-02-13/002', 'VEN_003', 'N/A', 'PROD-001', 'ORD_002', '501', 500.00, '500 Bucket', '2025-02-13 12:05:25', 0),
(14, '67b412c904db8', 'outward', 'INV/2025-02-22/006', 'N/A', 'CUST_001', 'PROD-002', 'SALE_006', '502', 800.00, '63 Barrel', '2025-02-18 04:55:37', 0),
(15, '67b412c90802f', 'outward', 'INV/2025-02-22/006', 'N/A', 'CUST_001', 'PROD-003', 'SALE_006', '503', 900.00, '40 Box', '2025-02-18 04:55:37', 0),
(16, '67b56ca5be4e0', 'inward', 'INV/2025-02-26/003', 'VEN_001', 'N/A', 'PROD-001', 'ORD_003', '501', 500.00, '30 Bucket', '2025-02-19 05:31:17', 0),
(17, '67b56ca5bf3fe', 'inward', 'INV/2025-03-03/004', 'VEN_001', 'N/A', 'PROD-003', 'ORD_003', '503', 900.00, '200 Box', '2025-02-19 05:31:17', 0),
(18, '67bbfb06574d6', 'outward', 'INV/2025-02-22/007', 'N/A', 'CUST_002', 'PROD-001', 'SALE_007', '501', 500.00, '60 Bucket', '2025-02-24 04:52:22', 0),
(19, '67bbfb0658953', 'outward', 'INV/2025-02-18/006', 'N/A', 'CUST_002', 'PROD-002', 'SALE_007', '502', 800.00, '60 Barrel', '2025-02-24 04:52:22', 0),
(20, '67bc4f6197bbb', 'outward', 'INV/2025-02-18/006', 'N/A', 'CUST_002', 'PROD-001', 'SALE_008', '501', 500.00, '60 Bucket', '2025-02-24 10:52:17', 0),
(21, '67bc4f619a53d', 'outward', 'INV/2025-02-18/006', 'N/A', 'CUST_002', 'PROD-002', 'SALE_008', '502', 800.00, '60 Barrel', '2025-02-24 10:52:17', 0),
(22, '67bea53eb049e', 'outward', 'INV/2025-02-28/009', 'N/A', 'CUST_002', 'PROD-003', 'SALE_009', '503', 1000.00, '100 Box', '2025-02-26 05:23:10', 0),
(23, '67bea53eb103d', 'outward', 'INV/2025-02-28/009', 'N/A', 'CUST_002', 'PROD-001', 'SALE_009', '501', 500.00, '34 Bucket', '2025-02-26 05:23:10', 0);

-- --------------------------------------------------------

--
-- Table structure for table `stock_moment_log`
--

DROP TABLE IF EXISTS `stock_moment_log`;
CREATE TABLE IF NOT EXISTS `stock_moment_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `product_id` varchar(50) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `lob` varchar(100) NOT NULL,
  `inward` int DEFAULT '0',
  `outward` int DEFAULT '0',
  `available_piece` int DEFAULT '0',
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_moment_log`
--

INSERT INTO `stock_moment_log` (`id`, `unique_id`, `date`, `product_id`, `product_name`, `sku`, `mrp`, `lob`, `inward`, `outward`, `available_piece`, `created_date`, `deleted_at`) VALUES
(1, ' 679dcda058c24', '2025-02-01', 'PROD-001', 'Engine Oil', '501', 500.00, 'Mobil', 1, 2, 169, '2025-02-01 07:30:40', 0),
(2, ' 679dcda796343', '2025-02-01', 'PROD-001', 'Engine Oil', '501', 600.00, 'Mobil', 1, 1, 360, '2025-02-01 07:30:47', 0),
(3, ' 679dcdb2a19b3', '2025-02-01', 'PROD-002', 'Oil Filter', '502', 800.00, 'Mobil', 1, 1, 300, '2025-02-01 07:30:58', 0),
(4, ' 679dcdb74fce5', '2025-02-01', 'PROD-002', 'Oil Filter', '502', 900.00, 'Mobil', 1, 0, 350, '2025-02-01 07:31:03', 0),
(5, '  679dcda796343', '2025-02-01', 'PROD-001', 'Engine Oil', '501', 600.00, 'Mobil', 1, 3, 400, '2025-02-01 10:01:34', 0),
(6, '679dcda796343', '2025-02-14', 'PROD-001', 'Engine Oil', '501', 600.00, 'Mobil', 1, 3, 400, '2025-02-01 10:02:09', 0),
(7, '679dcdb2a19b3', '2025-02-01', 'PROD-002', 'Oil Filter', '502', 800.00, 'Mobil', 1, 2, 310, '2025-02-01 10:03:05', 0),
(8, ' 67af31ae7b1ef', '2025-02-01', 'PROD-002', 'Oil Filter', '502', 900.00, 'Mobil', 1, 0, 450, '2025-02-14 12:06:06', 0),
(9, '679dcdb74fce5', '2025-02-21', 'PROD-003', 'Oil Can', '503', 500.00, 'Mobile', 2, 3, 730, '2025-02-14 12:41:48', 0),
(10, '67b8622ce4de0', '2025-02-21', 'PROD-003', 'Oil Can', '503', 500.00, 'Mobile', 2, 3, 730, '2025-02-21 13:13:25', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(50) NOT NULL,
  `name_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','user') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id` (`unique_id`),
  UNIQUE KEY `name_id` (`name_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `unique_id`, `name_id`, `name`, `username`, `password`, `role`, `created_at`, `deleted_at`) VALUES
(1, '6777c1b468544', 'ADM_001', 'John D', 'johndoe', '$2y$10$ukCFYs0tv4dRZcHYVthh8ubLCMOmQhy2S02p.HqcBe4m7xApxqg.O', 'admin', '2025-01-03 10:53:40', 0),
(2, '6777c1de19323', 'STA_001', 'Kannan', 'kannan007', '$2y$10$lW4C9idrYJchtSOyB9hN1.rZK1trlq6eziOwHES79iE.5LMp6J4ZO', 'staff', '2025-01-03 10:54:22', 0),
(3, '6777c310c6291', 'STA_002', 'Radha Krish', 'radha', '$2y$10$Ja/hf7x0TEaeMYg5U.ApgOTBLMmcegTxjQYX1cucf12ooo0xWiudO', 'staff', '2025-01-03 10:59:28', 0),
(4, '679df50f8014d', 'STA_003', 'Surya', 'surya', '$2y$10$fjiSfdykT81ErDee.msz3uqATbb991KRP2/.p1m93A8RvnmrjFYri', 'staff', '2025-02-01 10:18:55', 0),
(5, '67a5f453ad3b0', 'STA_004', 'Godwin', 'godwin', '$2y$10$kHUMuYdjjmB0RpYfoD5AGu699H4k1c4lRd6Kmc5q6HrLtXTxJDnaW', 'staff', '2025-02-07 11:53:55', 1),
(6, '67ab048e2c0b0', 'ADM_002', 'Prakash', 'prakash', '$2y$10$qHxr.pQc0hahrBcsGpefnuzjMCbhbvuwFAzldBdh1581UntT3yySK', 'admin', '2025-02-11 08:04:30', 0),
(7, '67ab14b9a15cb', 'STA_005', 'Selva', 'selva', '$2y$10$wDoXGCLfWecz8ydgUv.UxuFThd0etV2nJ668uW2yFa/5sssDXHlV2', 'staff', '2025-02-11 09:13:29', 0),
(8, '67ab15c84226f', 'STA_006', 'Karthick', 'karthi', '$2y$10$bbNzqdawrP5jtd7qBNAdhOONfRCLE.kEHssr8F5wWRhZ7KmQHyDlC', 'staff', '2025-02-11 09:18:00', 0),
(9, '67ab1e318d22d', 'STA_007', 'Jona', 'jona', '$2y$10$gmsxQLWCCnvoXJtIGt7f4.Vnt1AR.rK9L6X2v6ggA1p1jGJzaVLFG', 'staff', '2025-02-11 09:53:53', 1),
(10, '67ab227ea1e4a', 'STA_008', 'Selvam', 'selvams', '$2y$10$2CnlZ.zWgqkoMGscbQ0S6O5PflPQ1BZSdjS1.4NdZvXVyqwMMduCm', 'staff', '2025-02-11 10:12:14', 1);

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
CREATE TABLE IF NOT EXISTS `vendors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(255) NOT NULL,
  `vendor_id` varchar(50) NOT NULL,
  `vendor_name` varchar(255) NOT NULL,
  `mobile_number` varchar(15) NOT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `gst_number` varchar(50) DEFAULT NULL,
  `address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `unique_id`, `vendor_id`, `vendor_name`, `mobile_number`, `business_name`, `gst_number`, `address`, `created_at`, `deleted_at`) VALUES
(1, '677e5b56842d4', 'VEN_001', 'Radha Krishnan', '9876543210', 'Radha Enterprises', 'GST005', 'Madurai', '2025-01-08 11:02:46', 0),
(2, '677f7890b52a1', 'VEN_002', 'Surya', '86273628372', 'Oil Business', 'GST002', 'Virudhunagar', '2025-01-09 07:19:44', 0),
(3, '679b2050aedc8', 'VEN_003', 'Kannan', '832478234', 'Oil Business', 'GST003', 'Virudhunagar', '2025-01-30 06:46:40', 0);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

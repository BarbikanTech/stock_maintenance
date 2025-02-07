-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 01, 2025 at 10:17 AM
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
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `unique_id`, `customer_id`, `customer_name`, `mobile_number`, `business_name`, `gst_number`, `address`, `created_date`, `deleted_at`) VALUES
(1, '677e562ca720f', 'CUST_001', 'Surya', '9876543211', 'Surya Enterprises', 'GST125', 'Virudhunagar', '2025-01-08 10:40:44', 0),
(2, '679b1ff809fe1', 'CUST_002', 'Prakash', '73453535353', 'Prakash Enterprises', 'GST002', 'Virudhunagar', '2025-01-30 06:45:12', 0);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(191) NOT NULL,
  `table_type` varchar(255) NOT NULL,
  `original_unique_id` varchar(255) NOT NULL,
  `staff_id` varchar(255) NOT NULL,
  `staff_name` varchar(255) NOT NULL,
  `update_quantity` int NOT NULL,
  `admin_confirmation` tinyint(1) DEFAULT '0',
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_unique_id` (`unique_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `unique_id`, `table_type`, `original_unique_id`, `staff_id`, `staff_name`, `update_quantity`, `admin_confirmation`, `created_date`, `deleted_at`) VALUES
(1, '679dce86e3932', 'sales', '679dcc56e76fd', 'STA_001', 'Kannan', 160, 1, '2025-02-01 07:34:30', 0),
(2, '679dcfab440af', 'purchase', '679dcb8b108af', 'STA_002', 'Radha Krish', 200, 1, '2025-02-01 07:39:23', 0),
(3, '679deacd3fee2', 'sales', '679dcc56e82c7', 'STA_001', 'Kannan', 190, 1, '2025-02-01 09:35:09', 0),
(4, '679deba95e918', 'sales', '679dcc56e82c7', 'STA_001', 'Kannan', 190, 1, '2025-02-01 09:38:49', 0),
(5, '679ded7559030', 'sales', '679dec4022d9e', 'STA_001', 'Kannan', 60, 1, '2025-02-01 09:46:29', 0),
(6, '679ded755922a', 'sales', '679dec40234f1', 'STA_001', 'Kannan', 90, 1, '2025-02-01 09:46:29', 0),
(7, '679dee769ac8a', 'sales', '679dee06cafcc', 'STA_001', 'Kannan', 290, 1, '2025-02-01 09:50:46', 0),
(8, '679deea532a79', 'sales', '679dee06cafcc', 'STA_001', 'Kannan', 310, 1, '2025-02-01 09:51:33', 0),
(9, '679def8d326c4', 'sales', '679def16cc8fd', 'STA_001', 'Kannan', 50, 1, '2025-02-01 09:55:25', 0),
(10, '679defcd1da78', 'sales', '679def16cc8fd', 'STA_001', 'Kannan', 60, 1, '2025-02-01 09:56:29', 0);

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
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`id`, `unique_id`, `date`, `product_id`, `product_name`, `sku`, `unit`, `subunit`, `created_date`, `delete_at`) VALUES
(1, 'b769eb1b-e06a-11ef-87f9-989096d40073', '2025-02-01', 'PROD-001', 'Engine Oil', '501', '1 Bucket', '10 L', '2025-02-01 07:04:01', 0),
(2, 'ee6a5329-e06a-11ef-87f9-989096d40073', '2025-02-01', 'PROD-002', 'Oil Filter', '502', '1 Barrel', '100 L', '2025-02-01 07:05:34', 0);

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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_mrp`
--

INSERT INTO `product_mrp` (`id`, `unique_id`, `product_id`, `mrp`, `opening_stock`, `current_stock`, `minimum_stock`, `excess_stock`, `physical_stock`, `notification`, `created_date`, `delete_at`) VALUES
(1, 'b76ac932-e06a-11ef-87f9-989096d40073', 'PROD-001', 500.00, 80, 169, 20, 0, 169, '', '2025-02-01 07:04:02', 0),
(2, 'b76addff-e06a-11ef-87f9-989096d40073', 'PROD-001', 600.00, 60, 250, 15, 150, 400, '', '2025-02-01 07:04:02', 0),
(3, 'ee6a8d7a-e06a-11ef-87f9-989096d40073', 'PROD-002', 800.00, 300, 310, 40, 0, 310, '', '2025-02-01 07:05:34', 0),
(4, 'ee6a9b82-e06a-11ef-87f9-989096d40073', 'PROD-002', 900.00, 250, 450, 35, 0, 450, '', '2025-02-01 07:05:34', 0);

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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase`
--

INSERT INTO `purchase` (`id`, `unique_id`, `date`, `order_id`, `vendor_id`, `vendor_name`, `mobile_number`, `business_name`, `gst_number`, `address`, `invoice_number`, `created_date`, `deleted_at`) VALUES
(1, '679dcb8b0e87d', '2024-02-01', 'ORD_001', 'VEN_001', 'Radha Krishnan', '9876543210', 'Radha Enterprises', 'GST005', 'Madurai', 'INV/2025-02-01/001', '2025-02-01 07:21:47', 0);

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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_mrp`
--

INSERT INTO `purchase_mrp` (`id`, `unique_id`, `order_id`, `product_id`, `product_name`, `sku`, `quantity`, `mrp`, `created_date`, `deleted_at`) VALUES
(1, '679dcb8b0f6a3', 'ORD_001', 'PROD-001', 'Engine Oil', '501', '200 Bucket', 500.00, '2025-02-01 07:21:47', 0),
(2, '679dcb8b0ff97', 'ORD_001', 'PROD-001', 'Engine Oil', '501', '400 Bucket', 600.00, '2025-02-01 07:21:47', 0),
(3, '679dcb8b10437', 'ORD_001', 'PROD-002', 'Oil Filter', '502', '100 Barrel', 800.00, '2025-02-01 07:21:47', 0),
(4, '679dcb8b108af', 'ORD_001', 'PROD-002', 'Oil Filter', '502', '200 Barrel', 900.00, '2025-02-01 07:21:47', 0);

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
  `created_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `unique_id`, `date`, `order_id`, `invoice_number`, `customer_id`, `customer_name`, `mobile_number`, `business_name`, `gst_number`, `address`, `created_date`, `deleted_at`) VALUES
(1, '679dcc56e5bf1', '2025-02-01', 'SALE_001', 'INV/2025-02-01/001', 'CUST_001', 'Surya', '9876543211', 'Surya Enterprises', 'GST125', 'Virudhunagar', '2025-02-01 07:25:10', 0),
(2, '679dec4021cee', '2025-02-01', 'SALE_002', 'INV/2025-02-01/002', 'CUST_002', 'Prakash', '73453535353', 'Prakash Enterprises', 'GST002', 'Virudhunagar', '2025-02-01 09:41:20', 0),
(3, '679dee06caa70', '2025-02-01', 'SALE_003', 'INV/2025-02-01/003', 'CUST_002', 'Prakash', '73453535353', 'Prakash Enterprises', 'GST002', 'Virudhunagar', '2025-02-01 09:48:54', 0),
(4, '679def16cbdad', '2025-02-01', 'SALE_004', 'INV/2025-02-01/004', 'CUST_002', 'Prakash', '73453535353', 'Prakash Enterprises', 'GST002', 'Virudhunagar', '2025-02-01 09:53:26', 0);

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
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(7, '679def16cc8fd', 'SALE_004', 'PROD-001', 'Engine Oil', '501', '60 Bucket', 600.00, 'Original', 'Excess Stock', '2025-02-01 09:53:26', 0);

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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_adjustment`
--

INSERT INTO `stock_adjustment` (`id`, `unique_id`, `date`, `stock_id`, `product_id`, `product_name`, `mrp`, `adjusted_stock`, `adjusted_type`, `reason`, `created_date`, `deleted_at`) VALUES
(1, '679dcd08bede6', '2025-02-01', 'STO-001', 'PROD-001', 'Engine Oil', 500.00, 1, 'subtract', 'Damaged items', '2025-02-01 07:28:08', 0);

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
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(11, '679def16cc8fd', 'outward', 'INV/2025-02-01/004', 'N/A', 'CUST_002', 'PROD-001', 'SALE_004', '501', 600.00, '60 Bucket', '2025-02-01 09:53:26', 0);

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
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_moment_log`
--

INSERT INTO `stock_moment_log` (`id`, `unique_id`, `date`, `product_id`, `product_name`, `sku`, `mrp`, `lob`, `inward`, `outward`, `available_piece`, `created_date`, `deleted_at`) VALUES
(1, ' 679dcda058c24', '2025-02-01', 'PROD-001', 'Engine Oil', '501', 500.00, 'Mobil', 1, 2, 169, '2025-02-01 07:30:40', 0),
(2, ' 679dcda796343', '2025-02-01', 'PROD-001', 'Engine Oil', '501', 600.00, 'Mobil', 1, 1, 360, '2025-02-01 07:30:47', 0),
(3, ' 679dcdb2a19b3', '2025-02-01', 'PROD-002', 'Oil Filter', '502', 800.00, 'Mobil', 1, 1, 300, '2025-02-01 07:30:58', 0),
(4, ' 679dcdb74fce5', '2025-02-01', 'PROD-002', 'Oil Filter', '502', 900.00, 'Mobil', 1, 0, 350, '2025-02-01 07:31:03', 0),
(5, '  679dcda796343', '2025-02-01', 'PROD-001', 'Engine Oil', '501', 600.00, 'Mobil', 1, 3, 400, '2025-02-01 10:01:34', 0),
(6, '679dcda796343', '2025-02-01', 'PROD-001', 'Engine Oil', '501', 600.00, 'Mobil', 1, 3, 400, '2025-02-01 10:02:09', 0),
(7, '679dcdb2a19b3', '2025-02-01', 'PROD-002', 'Oil Filter', '502', 800.00, 'Mobil', 1, 2, 310, '2025-02-01 10:03:05', 0);

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
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `unique_id`, `name_id`, `name`, `username`, `password`, `role`, `created_at`, `deleted_at`) VALUES
(1, '6777c1b468544', 'ADM_001', 'John D', 'johndoe', '$2y$10$ukCFYs0tv4dRZcHYVthh8ubLCMOmQhy2S02p.HqcBe4m7xApxqg.O', 'admin', '2025-01-03 10:53:40', 0),
(2, '6777c1de19323', 'STA_001', 'Kannan', 'kannan007', '$2y$10$lW4C9idrYJchtSOyB9hN1.rZK1trlq6eziOwHES79iE.5LMp6J4ZO', 'staff', '2025-01-03 10:54:22', 0),
(3, '6777c310c6291', 'STA_002', 'Radha Krish', 'radha', '$2y$10$Ja/hf7x0TEaeMYg5U.ApgOTBLMmcegTxjQYX1cucf12ooo0xWiudO', 'staff', '2025-01-03 10:59:28', 0);

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

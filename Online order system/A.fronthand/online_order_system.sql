-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2026 at 06:44 AM
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
-- Database: `online_order_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `admin_name`) VALUES
(19, 'Douglas Ng Khai Harng'),
(20, 'Jenny'),
(21, 'Shian'),
(22, 'shiaan'),
(23, '45yrhbncjv'),
(26, 'Douglas Ng Khai Harng');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`cart_id`, `customer_id`, `product_id`, `quantity`, `created_at`, `updated_at`) VALUES
(6, 23, 11, 117, '2026-04-17 16:31:13', '2026-04-17 16:36:27'),
(7, 23, 12, 90, '2026-04-17 16:36:34', '2026-04-17 16:36:34');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `customer_name`) VALUES
(19, 'Douglas Ng Khai Harng'),
(24, 'Douglas Ng Khai Harng'),
(25, 'Douglas Ng Khai Harng'),
(26, 'Douglas Ng Khai Harng'),
(27, '12345'),
(28, 'Douglas Ng Khai Harng'),
(29, 'Jenny');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('unpaid','pending','paid','refund_pending','refunded') NOT NULL DEFAULT 'unpaid',
  `payment_proof_path` varchar(255) DEFAULT NULL,
  `payment_uploaded_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `ordered_at` datetime NOT NULL DEFAULT current_timestamp(),
  `refund_proof_path` varchar(255) DEFAULT NULL,
  `refund_uploaded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_groups`
--

CREATE TABLE `order_groups` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_items` int(11) NOT NULL DEFAULT 0,
  `status` enum('unpaid','pending','paid','refund_pending','refunded') NOT NULL DEFAULT 'unpaid',
  `payment_proof_path` varchar(255) DEFAULT NULL,
  `refund_proof_path` varchar(255) DEFAULT NULL,
  `payment_uploaded_at` datetime DEFAULT NULL,
  `refund_uploaded_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `ordered_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_groups`
--

INSERT INTO `order_groups` (`order_id`, `customer_id`, `total_amount`, `total_items`, `status`, `payment_proof_path`, `refund_proof_path`, `payment_uploaded_at`, `refund_uploaded_at`, `approved_at`, `approved_by`, `ordered_at`) VALUES
(1, 18, 150.00, 2, 'paid', NULL, NULL, '2026-04-17 14:45:51', NULL, '2026-04-17 14:45:51', NULL, '2026-04-17 14:42:56'),
(2, 22, 210.00, 3, 'paid', NULL, NULL, '2026-04-17 15:44:38', NULL, '2026-04-17 15:44:38', NULL, '2026-04-17 15:44:02'),
(3, 23, 60.00, 1, 'refunded', NULL, 'uploads/refunds/refund_3_1776414228.png', '2026-04-17 16:14:57', '2026-04-17 16:23:48', '2026-04-17 16:24:30', 19, '2026-04-17 16:13:40'),
(4, 25, 234567.00, 1, 'paid', NULL, NULL, '2026-04-17 18:09:49', NULL, '2026-04-17 18:09:49', NULL, '2026-04-17 18:09:23');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `product_name`, `product_price`, `quantity`, `subtotal`) VALUES
(1, 1, 12, 'Computer Stand', 90.00, 1, 90.00),
(2, 1, 11, 'Mouse', 60.00, 1, 60.00),
(3, 2, 12, 'Computer Stand', 90.00, 1, 90.00),
(4, 2, 11, 'Mouse', 60.00, 2, 120.00),
(5, 3, 11, 'Mouse', 60.00, 1, 60.00),
(6, 4, 12, '--------------------------------------------------------------------', 234567.00, 1, 234567.00);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `product_rating` decimal(3,1) DEFAULT NULL,
  `product_stock` int(11) NOT NULL DEFAULT 0,
  `publish_date` date NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `product_price`, `product_rating`, `product_stock`, `publish_date`, `image_path`, `created_at`, `category`) VALUES
(11, 'Mouse', 60.00, 7.0, 345675678, '0766-09-08', 'uploads/product_media/pm_69e08f31d9d270.89250535.png', '2026-04-16 07:26:41', 'Technology'),
(12, '--------------------------------------------------------------------', 234567.00, 9.9, 876542, '5678-03-04', 'uploads/product_media/pm_69e08f9d3a44b2.51575799.png', '2026-04-16 07:28:29', 'Computer');

-- --------------------------------------------------------

--
-- Table structure for table `product_media`
--

CREATE TABLE `product_media` (
  `media_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `media_path` varchar(255) NOT NULL,
  `media_type` enum('image','video') NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `user_id`, `product_id`, `rating`, `comment`, `created_at`) VALUES
(14, 18, 12, 5, 'very Good', '2026-04-17 02:28:50'),
(15, 23, 11, 5, '-p;/.,kio', '2026-04-17 08:29:11'),
(16, 29, 12, 5, '2345678', '2026-04-17 11:13:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `role` enum('admin','customer') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `avatar` varchar(255) DEFAULT NULL,
  `security_question` varchar(255) DEFAULT NULL,
  `security_answer` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role`, `full_name`, `email`, `phone`, `password_hash`, `created_at`, `avatar`, `security_question`, `security_answer`, `address`) VALUES
(26, 'admin', 'Douglas Ng Khai Harng', 'h@gmail.com', '01137129502', '$2y$10$0IHXU8x/5DVK5h7ZUgDKf.h6BtapXwlMd3NbD4N6UIXz3xi8jEfKK', '2026-04-17 10:47:52', 'uploads/admin-default.png', 'My hobby', '$2y$10$zKzEE4rhTDzNPxvrioFK2.ilsNoA3e/RRaskT3osFc5thOED7vhx.', '1234234567'),
(27, 'customer', '12345', '12345@gmail.com', '12345', '$2y$10$M3ISR/Sdza0u1aJMa1YLVeG4TjnV8V3.OCYD2bypx/tkdPEQ0lGy.', '2026-04-17 10:50:22', 'uploads/avatars/img_69e2106e31fbb0.99140862.jpeg', '12345', '$2y$10$AvF84IZ0k9ZRuyjA0oqYLuh/YeuzLYKgK81SzSUl8uS7jv.t.7oHm', '12345'),
(28, 'customer', 'Douglas Ng Khai Harng', 'douglas@gmail.com', '01137129502', '$2y$10$/3ZEWeLfytsaFjnzJbkmpunPfChpxYV0kRu9yCxCORsI834zybclG', '2026-04-17 11:04:15', 'uploads/avatars/img_69e213af21cd30.97051330.png', 'My hobby', '$2y$10$dwBpiH/Bogb3KgVZ8Iifr.R1aG5mbJxn7fzlOs2kURWmhjHNgwDnS', 'Taman Pelangi'),
(29, 'customer', 'Jenny', 'jenny@gmail.com', '01145637892', '$2y$10$IYx15rQmc2o7cKkoNuDMO.c0Q3ktFvycppJEJNzXF.TqSe4cjFBwS', '2026-04-17 11:09:47', 'uploads/avatars/img_69e215a36be058.59491850.png', 'My hobby', '$2y$10$xelyGr01AFFRr/thKxw.R.eKfakd03LGJW46gq7XeztcIROped/xy', 'Taman Tarumt');

-- --------------------------------------------------------

--
-- Table structure for table `user_blacklist`
--

CREATE TABLE `user_blacklist` (
  `blacklist_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `blacklisted_by` int(11) NOT NULL,
  `blacklisted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `unblacklisted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_blacklist`
--

INSERT INTO `user_blacklist` (`blacklist_id`, `user_id`, `email`, `reason`, `is_active`, `blacklisted_by`, `blacklisted_at`, `unblacklisted_at`) VALUES
(2, 18, 'jenny@gmail.com', 'ertyui', 0, 17, '2026-04-17 12:33:20', '2026-04-17 12:34:00'),
(3, 22, 'shiaan1@gmail.com', NULL, 0, 21, '2026-04-17 15:25:23', '2026-04-17 15:25:42');

-- --------------------------------------------------------

--
-- Table structure for table `user_blacklist_removals`
--

CREATE TABLE `user_blacklist_removals` (
  `removal_id` int(11) NOT NULL,
  `blacklist_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `removed_by` int(11) NOT NULL,
  `removed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_blacklist_removals`
--

INSERT INTO `user_blacklist_removals` (`removal_id`, `blacklist_id`, `email`, `removed_by`, `removed_at`, `note`) VALUES
(2, 2, 'jenny@gmail.com', 17, '2026-04-17 12:34:00', 'ertyui'),
(3, 3, 'shiaan1@gmail.com', 21, '2026-04-17 15:25:42', 'fghjk'),
(4, 4, 'shiaan1@gmail.com', 21, '2026-04-17 15:42:07', 'd\'f\'g'),
(5, 5, 'shiaan1@gmail.com', 21, '2026-04-17 15:43:24', 'sdfgh'),
(6, 6, 'shiaan1@gmail.com', 19, '2026-04-17 16:33:50', 'iok'),
(7, 7, 'whyandriod.use@gmail.com', 19, '2026-04-17 16:48:04', 'ok、'),
(8, 8, 'whyandriod.use@gmail.com', 19, '2026-04-17 16:53:35', '123456788654323456789876543234567897654323456788765434567887654334567898765434567890-=-'),
(9, 9, 'whyandriod.use@gmail.com', 19, '2026-04-17 17:07:29', 'dfgh'),
(10, 10, 'whyandriod.use@gmail.com', 19, '2026-04-17 17:15:21', '123'),
(11, 11, 'shiaan1@gmail.com', 19, '2026-04-17 17:58:01', 'asdfg');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(11, 23, 11, '2026-04-17 08:29:17'),
(13, 23, 12, '2026-04-17 08:36:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `uniq_customer_product` (`customer_id`,`product_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `order_groups`
--
ALTER TABLE `order_groups`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD KEY `fk_password_resets_user` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `product_media`
--
ALTER TABLE `product_media`
  ADD PRIMARY KEY (`media_id`),
  ADD KEY `fk_product_media_product` (`product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_blacklist`
--
ALTER TABLE `user_blacklist`
  ADD PRIMARY KEY (`blacklist_id`),
  ADD UNIQUE KEY `uniq_active_email` (`email`,`is_active`);

--
-- Indexes for table `user_blacklist_removals`
--
ALTER TABLE `user_blacklist_removals`
  ADD PRIMARY KEY (`removal_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `order_groups`
--
ALTER TABLE `order_groups`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_media`
--
ALTER TABLE `product_media`
  MODIFY `media_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `user_blacklist`
--
ALTER TABLE `user_blacklist`
  MODIFY `blacklist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_blacklist_removals`
--
ALTER TABLE `user_blacklist_removals`
  MODIFY `removal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`admin_id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_media`
--
ALTER TABLE `product_media`
  ADD CONSTRAINT `fk_product_media_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

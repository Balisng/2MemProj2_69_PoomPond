-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 17, 2026 at 09:36 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `parking_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `spot_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `total_price` double NOT NULL,
  `slip_image` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `payment_status` varchar(20) DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Booking lists that customer booked';

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `spot_id`, `start_time`, `end_time`, `total_price`, `slip_image`, `status`, `payment_status`, `created_at`) VALUES
(1, 1, 4, '2026-08-17 08:13:57', '2026-08-18 04:13:57', 400, NULL, 'pending', 'unpaid', '2026-08-17 07:24:28'),
(2, 2, 3, '2026-08-17 08:29:05', '2026-08-18 04:29:05', 600, NULL, 'pending', 'unpaid', '2026-08-17 07:24:28'),
(3, 2, 4, '2026-08-17 08:29:21', '2026-08-18 04:29:21', 400, NULL, 'pending', 'unpaid', '2026-08-17 07:24:28'),
(4, 2, 4, '2026-08-17 08:41:31', '2026-08-17 10:41:31', 40, NULL, 'pending', 'unpaid', '2026-08-17 07:24:28'),
(5, 2, 3, '2026-08-17 08:47:19', '2026-08-17 12:47:19', 120, NULL, 'pending', 'unpaid', '2026-08-17 07:24:28'),
(6, 3, 5, '2026-08-17 08:49:58', '2026-08-17 12:49:58', 60, 'slip_1786949429_6a82af355f222.png', 'approved', 'paid', '2026-08-17 07:24:28'),
(7, 2, 5, '2026-08-17 14:01:00', '2026-08-17 15:01:00', 15, NULL, 'cancelled', 'unpaid', '2026-08-17 07:24:28'),
(8, 2, 5, '2026-08-17 14:06:00', '2026-08-17 15:06:00', 15, NULL, 'approved', 'paid', '2026-08-17 07:24:28'),
(9, 3, 3, '2026-08-17 14:30:00', '2026-08-18 02:30:00', 360, NULL, 'pending', 'unpaid', '2026-08-17 07:30:44'),
(10, 3, 5, '2026-08-17 14:31:00', '2026-08-18 02:31:00', 180, 'slip_1786951877_6a82b8c5853f9.png', 'cancelled', 'pending_approval', '2026-08-17 07:31:09');

-- --------------------------------------------------------

--
-- Table structure for table `parking_spots`
--

CREATE TABLE `parking_spots` (
  `spot_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  `price_per_hour` double NOT NULL,
  `status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Parking spots';

--
-- Dumping data for table `parking_spots`
--

INSERT INTO `parking_spots` (`spot_id`, `user_id`, `title`, `description`, `contact_phone`, `image`, `latitude`, `longitude`, `price_per_hour`, `status`) VALUES
(1, 1, 'ที่จอดรถ A (ใกล้สถานี)', NULL, NULL, NULL, 7.0085, 100.4747, 30, 'available'),
(2, 1, 'ที่จอดรถ B (หน้าตลาด)', NULL, NULL, NULL, 7.005, 100.47, 20, 'available'),
(3, 1, 'ที่จอดรถ A (ใกล้สถานี)', NULL, NULL, NULL, 7.0085, 100.4747, 30, 'available'),
(4, 1, 'ที่จอดรถ B (หน้าตลาด)', NULL, NULL, NULL, 7.005, 100.47, 20, 'available'),
(5, 2, 'poom', NULL, NULL, NULL, 7.022894, 100.498579, 15, 'available'),
(6, 2, 'หน้าสวน', NULL, NULL, NULL, 7.024283, 100.49925, 15, 'available'),
(7, 2, 'หน้าค่าย', 'หน้าค่าย\r\nติดต่อภูมิ', '0123456789', '', 7.023268, 100.49807, 20, 'available');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `spot_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `booking_id`, `user_id`, `spot_id`, `rating`, `comment`, `created_at`) VALUES
(1, 6, 3, 5, 4, 'glhf', '2026-08-17 07:21:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `role` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Collecting users data';

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `phone`, `role`) VALUES
(1, 'user1', '1234', 'สมชาย ใจดี', '0812345678', 'user'),
(2, 'poom', '020', 'poomrapee saengow', '0987486606', 'user'),
(3, 'tester', '1234', 'tester', '0123456789', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`);

--
-- Indexes for table `parking_spots`
--
ALTER TABLE `parking_spots`
  ADD PRIMARY KEY (`spot_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `parking_spots`
--
ALTER TABLE `parking_spots`
  MODIFY `spot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

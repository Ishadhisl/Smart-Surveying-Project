-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 03:06 AM
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
-- Database: `land_surveying`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `surveyor_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Pending Confirmation','Confirmed','Site Visit','Mapping','Completed') DEFAULT 'Pending',
  `land_size` decimal(10,2) NOT NULL,
  `unit` enum('Perches','Acres') NOT NULL,
  `survey_purpose` varchar(50) DEFAULT 'Bank Loan / Deed',
  `other_purpose` varchar(255) DEFAULT NULL,
  `boundary_points` int(11) DEFAULT 4,
  `lot_count` int(11) DEFAULT 1,
  `village` varchar(100) DEFAULT NULL,
  `estimated_fee` decimal(10,2) NOT NULL,
  `deed_path` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(10,8) DEFAULT NULL,
  `date_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `actual_fee` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('Pending','Advance Paid','Paid') DEFAULT 'Pending',
  `advance_paid` decimal(10,2) DEFAULT 0.00,
  `is_confirmed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `client_id`, `surveyor_id`, `status`, `land_size`, `unit`, `survey_purpose`, `other_purpose`, `boundary_points`, `lot_count`, `village`, `estimated_fee`, `deed_path`, `latitude`, `longitude`, `date_time`, `created_at`, `actual_fee`, `payment_status`, `advance_paid`, `is_confirmed`) VALUES
(1, 1, 2, 'Mapping', 15.00, 'Perches', 'Bank Loan / Deed', NULL, 4, 1, NULL, 20000.00, NULL, 6.92710000, 79.86120000, '2026-05-01 10:00:00', '2026-04-03 04:45:01', 25000.00, 'Pending', 0.00, 1),
(3, 5, NULL, 'Completed', 10.00, 'Perches', 'Bank Loan / Deed', NULL, 4, 1, NULL, 25000.00, 'uploads/1775195028_SEERANGODA-2025-07-01.pdf', 7.70895034, 80.31896272, '2026-04-07 13:00:00', '2026-04-03 05:43:48', NULL, 'Pending', 0.00, 1),
(4, 6, NULL, 'Completed', 10.00, 'Perches', 'Bank Loan / Deed', NULL, 4, 1, NULL, 25000.00, 'uploads/1775243476_1775193381_HIRIPITIYA-2026-01-15.pdf', 6.92710000, 79.86120000, '2026-04-07 09:00:00', '2026-04-03 19:11:16', 20000.00, 'Paid', 0.00, 1),
(7, 10, NULL, 'Completed', 15.00, 'Acres', 'Bank Loan / Deed', NULL, 4, 1, 'Thalduwa', 3760000.00, NULL, 6.95607700, 80.22011505, '2026-04-12 13:15:00', '2026-04-04 15:31:12', 300000.00, 'Paid', 0.00, 1),
(8, 11, NULL, 'Confirmed', 20.00, 'Perches', 'Bank Loan / Deed', NULL, 4, 1, 'Ibbagamuwa', 40000.00, NULL, 7.54629421, 80.44465698, '2026-04-11 12:00:00', '2026-04-04 16:22:39', NULL, 'Pending', 0.00, 1),
(18, 25, NULL, 'Completed', 30.00, 'Perches', 'Bank Loan / Deed', NULL, 4, 1, 'Anuradhapura', 55000.00, NULL, 8.33303610, 80.41203681, '2026-04-11 13:00:00', '2026-04-08 17:30:30', 55000.00, 'Paid', 18333.33, 1),
(21, 29, NULL, 'Completed', 50.00, 'Perches', 'Bank Loan / Deed', NULL, 4, 1, 'Hiripitiya', 85000.00, 'uploads/1775745335_HIRIPITIYA-2026-01-15.pdf', 7.65453267, 80.36478400, '2026-04-13 09:30:00', '2026-04-09 14:35:35', 80000.00, 'Paid', 28333.33, 1),
(22, 30, NULL, 'Pending', 27.00, 'Perches', 'Bank Loan / Deed', NULL, 4, 1, 'Hiripitiya', 50500.00, 'uploads/1775747264_1775745335_HIRIPITIYA-2026-01-15.pdf', 7.65453267, 80.36478400, NULL, '2026-04-09 15:07:44', NULL, 'Pending', 0.00, 0),
(23, 31, NULL, 'Confirmed', 20.00, 'Acres', 'Partitioning (Katti Bederima)', '', 4, 4, 'Yakkala', 3220000.00, NULL, 7.08704130, 80.03389840, '2026-04-16 12:30:00', '2026-04-09 16:54:58', NULL, 'Advance Paid', 1073333.33, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('client','surveyor') NOT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `license_number`, `created_at`) VALUES
(1, 'John Client', 'client@test.com', '0771234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', NULL, '2026-04-03 04:45:01'),
(2, 'Sam Surveyor', NULL, '0777654321', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'surveyor', 'SURV-123', '2026-04-03 04:45:01'),
(4, 'A S Kumara Paranage', NULL, '0717906355', '$2y$10$doywrx0FcHToiQl5WtmJCeQU5N55nAbcBIFX7YODV3R181nuQcI02', 'surveyor', '6642', '2026-04-03 05:19:03'),
(5, 'Anushka Umayanga', 'Anu@gmail.com', '0770087496', '$2y$10$YDrTvhMtUK99/6TBFAbhs.VHSpQEoPFsRo9Bd.AD143dQpQXl1QmO', 'client', NULL, '2026-04-03 05:41:27'),
(6, 'Lasitha Priyasad', 'lasithapriyasad3@gmail.com', '0743398128', '$2y$10$EJg/6RBgCAYy/MRLuhu6VepTwYnxdY.oSmt6uQo8SvX1hR2e5bM4S', 'client', NULL, '2026-04-03 18:53:48'),
(10, 'Kasun Perera', 'Kasun@gmail.com', '071536425', '$2y$10$b/TdOf0kEMcFFVYSHQa7LuT7PMo7.QjmoQ/XaM//ce7vfDXi57WFu', 'client', NULL, '2026-04-04 15:29:47'),
(11, 'Namal Godagama', 'namalgodagama@gmail.com', '0711846188', '$2y$10$ror/3vEDVXVy11V02bpB4OwegdpVi0uWdB6Wbr7G4Ow3guUrlNl1S', 'client', NULL, '2026-04-04 16:19:39'),
(14, 'Samitha Nirmani', 'samitha@gmail.com', '071 5556699', '$2y$10$wnOri7WYQYis1.znShb1z.uJse.k3XEWNChrdgabagv/BpfgKYyvS', 'client', NULL, '2026-04-07 17:11:30'),
(25, 'Nimesha Mihiranga', 'nime@gmail.com', '07128896028', '$2y$10$TNW29sldPKrytp1TQ2YYr.HDi0zJybdlIUjUq8jco6cOMO6Gu5JCC', 'client', NULL, '2026-04-08 17:28:06'),
(27, 'Sayuru Perera', 'sayu@gmail.com', '0741535838', '$2y$10$/GSfkLjDOqXk.D7az4Ol8e/tb221FUJLm3Id35oKqgv1MHS8hNwX6', 'client', NULL, '2026-04-09 14:10:37'),
(29, 'Ishadhi Paranage', 'ishadhiparanage@gmail.com', '0719062507', '$2y$10$umPNG4AsA7PDs8dutjYfiO4LUOfe99Hg9RNCNWYSc5u0Dyg/cUZSi', 'client', NULL, '2026-04-09 14:34:37'),
(30, 'Janakee Pathiraja', 'panjanakee@yahoo.com', '0714474654', '$2y$10$QlvubXyuzYq4KhhBKn3ouuS/33cKjgrV.TVZT80wqYVhQ2fKswFP2', 'client', NULL, '2026-04-09 15:04:32'),
(31, 'Malithi Silva', 'malithisilva@gmail.com', '0764964458', '$2y$10$pmb7gL/LXa3FzE1lKYNG7u44YdVUztV3lynFozJvZfy3rV.EP2Q5K', 'client', NULL, '2026-04-09 16:36:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `surveyor_id` (`surveyor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `license_number` (`license_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`surveyor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

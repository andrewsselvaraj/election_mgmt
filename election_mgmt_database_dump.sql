-- Election Management System Database Dump
-- Generated on: <?php echo date('Y-m-d H:i:s'); ?>
-- Database: election_mgmt

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

-- Table structure for table `booth_master`
CREATE TABLE `booth_master` (
  `booth_id` varchar(36) NOT NULL,
  `booth_number` varchar(100) NOT NULL,
  `booth_name` varchar(255) NOT NULL,
  `location_name_of_building` text NOT NULL,
  `polling_areas` text NOT NULL,
  `polling_station_type` varchar(255) NOT NULL,
  `mla_id` varchar(36) NOT NULL,
  `created_by` varchar(100) DEFAULT 'SYSTEM',
  `created_datetime` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_datetime` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `status` varchar(100) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `MLA_Master`
CREATE TABLE `MLA_Master` (
  `mla_id` varchar(36) NOT NULL,
  `mla_constituency_name` varchar(255) NOT NULL,
  `mla_name` varchar(255) NOT NULL,
  `mla_party` varchar(100) NOT NULL,
  `mla_phone` varchar(20) DEFAULT NULL,
  `mla_email` varchar(100) DEFAULT NULL,
  `mp_id` varchar(36) NOT NULL,
  `created_by` varchar(100) DEFAULT 'SYSTEM',
  `created_datetime` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_datetime` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `status` varchar(100) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `mp_master`
CREATE TABLE `mp_master` (
  `mp_id` varchar(36) NOT NULL,
  `mp_constituency_name` varchar(255) NOT NULL,
  `mp_name` varchar(255) NOT NULL,
  `mp_party` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `mp_phone` varchar(20) DEFAULT NULL,
  `mp_email` varchar(100) DEFAULT NULL,
  `created_by` varchar(100) DEFAULT 'SYSTEM',
  `created_datetime` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_datetime` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `status` varchar(100) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `user_master`
CREATE TABLE `user_master` (
  `user_id` varchar(36) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT 'SYSTEM',
  `created_datetime` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_datetime` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `status` varchar(100) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `voter_master`
CREATE TABLE `voter_master` (
  `voter_unique_ID` varchar(36) NOT NULL,
  `voter_id` varchar(100) NOT NULL,
  `voter_name` varchar(255) NOT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `husband_name` varchar(255) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mla_id` varchar(36) NOT NULL,
  `booth_id` varchar(36) DEFAULT NULL,
  `ward_no` varchar(50) DEFAULT NULL,
  `part_no` varchar(50) DEFAULT NULL,
  `created_by` varchar(100) DEFAULT 'SYSTEM',
  `created_datetime` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_datetime` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `status` varchar(100) NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Indexes for dumped tables

-- Indexes for table `booth_master`
ALTER TABLE `booth_master`
  ADD PRIMARY KEY (`booth_id`),
  ADD UNIQUE KEY `booth_number` (`booth_number`),
  ADD KEY `idx_mla_id` (`mla_id`),
  ADD KEY `idx_status` (`status`);

-- Indexes for table `MLA_Master`
ALTER TABLE `MLA_Master`
  ADD PRIMARY KEY (`mla_id`),
  ADD UNIQUE KEY `mla_constituency_name` (`mla_constituency_name`),
  ADD KEY `idx_mp_id` (`mp_id`),
  ADD KEY `idx_status` (`status`);

-- Indexes for table `mp_master`
ALTER TABLE `mp_master`
  ADD PRIMARY KEY (`mp_id`),
  ADD UNIQUE KEY `mp_constituency_name` (`mp_constituency_name`),
  ADD KEY `idx_state` (`state`),
  ADD KEY `idx_status` (`status`);

-- Indexes for table `user_master`
ALTER TABLE `user_master`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

-- Indexes for table `voter_master`
ALTER TABLE `voter_master`
  ADD PRIMARY KEY (`voter_unique_ID`),
  ADD UNIQUE KEY `voter_id` (`voter_id`),
  ADD KEY `idx_mla_id` (`mla_id`),
  ADD KEY `idx_booth_id` (`booth_id`),
  ADD KEY `idx_status` (`status`);

-- --------------------------------------------------------

-- Foreign key constraints

-- Constraints for table `booth_master`
ALTER TABLE `booth_master`
  ADD CONSTRAINT `fk_booth_mla` FOREIGN KEY (`mla_id`) REFERENCES `MLA_Master` (`mla_id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Constraints for table `MLA_Master`
ALTER TABLE `MLA_Master`
  ADD CONSTRAINT `fk_mla_mp` FOREIGN KEY (`mp_id`) REFERENCES `mp_master` (`mp_id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Constraints for table `voter_master`
ALTER TABLE `voter_master`
  ADD CONSTRAINT `fk_voter_mla` FOREIGN KEY (`mla_id`) REFERENCES `MLA_Master` (`mla_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_voter_booth` FOREIGN KEY (`booth_id`) REFERENCES `booth_master` (`booth_id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- --------------------------------------------------------

-- Sample data insertion (optional - uncomment if you want sample data)

-- Insert sample MP data
-- INSERT INTO `mp_master` (`mp_id`, `mp_constituency_name`, `mp_name`, `mp_party`, `state`, `mp_phone`, `mp_email`, `created_by`, `status`) VALUES
-- ('mp-001', 'Sample MP Constituency', 'Sample MP Name', 'Sample Party', 'Sample State', '1234567890', 'mp@example.com', 'SYSTEM', 'ACTIVE');

-- Insert sample MLA data
-- INSERT INTO `MLA_Master` (`mla_id`, `mla_constituency_name`, `mla_name`, `mla_party`, `mla_phone`, `mla_email`, `mp_id`, `created_by`, `status`) VALUES
-- ('mla-001', 'Sample MLA Constituency', 'Sample MLA Name', 'Sample Party', '1234567890', 'mla@example.com', 'mp-001', 'SYSTEM', 'ACTIVE');

-- Insert sample Booth data
-- INSERT INTO `booth_master` (`booth_id`, `booth_number`, `booth_name`, `location_name_of_building`, `polling_areas`, `polling_station_type`, `mla_id`, `created_by`, `status`) VALUES
-- ('booth-001', 'B001', 'Sample Booth', 'Sample Building', 'Sample Area', 'Regular', 'mla-001', 'SYSTEM', 'ACTIVE');

-- Insert sample User data
-- INSERT INTO `user_master` (`user_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `role`, `created_by`, `status`) VALUES
-- ('user-001', 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 'SYSTEM', 'ACTIVE');

-- Insert sample Voter data
-- INSERT INTO `voter_master` (`voter_unique_ID`, `voter_id`, `voter_name`, `father_name`, `age`, `gender`, `address`, `mla_id`, `booth_id`, `ward_no`, `part_no`, `created_by`, `status`) VALUES
-- ('voter-001', 'V001', 'Sample Voter', 'Sample Father', 25, 'Male', 'Sample Address', 'mla-001', 'booth-001', 'W001', 'P001', 'SYSTEM', 'ACTIVE');

COMMIT;

-- End of database dump

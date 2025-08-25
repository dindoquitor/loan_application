CREATE DATABASE IF NOT EXISTS loan_application_db;
USE loan_application_db;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','user') DEFAULT 'user',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_setup_complete` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: applications
-- --------------------------------------------------------
CREATE TABLE `applications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `application_id` VARCHAR(20) NOT NULL,
  `intended_last_name` VARCHAR(50) DEFAULT NULL,
  `processing_id` VARCHAR(20) DEFAULT NULL,
  `approval_id` VARCHAR(20) DEFAULT NULL,
  `admin_id` INT(11) DEFAULT NULL,
  `loan_amount` DECIMAL(10,2) DEFAULT NULL,
  `term_months` INT(11) DEFAULT NULL,
  `status` ENUM('generated','submitted','co_applicant_completed','approved','rejected') DEFAULT 'generated',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_id` (`application_id`),
  UNIQUE KEY `processing_id` (`processing_id`),
  UNIQUE KEY `approval_id` (`approval_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: applicants
-- --------------------------------------------------------
CREATE TABLE `applicants` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `application_id` VARCHAR(20) NOT NULL,
  `first_name` VARCHAR(50) DEFAULT NULL,
  `middle_name` VARCHAR(50) DEFAULT NULL,
  `last_name` VARCHAR(50) DEFAULT NULL,
  `name_extension` VARCHAR(10) DEFAULT NULL,
  `birthdate` DATE DEFAULT NULL,
  `contact_number` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `region_code` VARCHAR(10) DEFAULT NULL,
  `region` VARCHAR(100) DEFAULT NULL,
  `province_code` VARCHAR(10) DEFAULT NULL,
  `province` VARCHAR(100) DEFAULT NULL,
  `city_code` VARCHAR(10) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `barangay_code` VARCHAR(10) DEFAULT NULL,
  `barangay` VARCHAR(100) DEFAULT NULL,
  `zip_code` VARCHAR(10) DEFAULT NULL,
  `street_address` TEXT DEFAULT NULL,
  `loan_amount` DECIMAL(10,2) DEFAULT NULL,
  `term_months` INT(11) DEFAULT NULL,
  `id_type` VARCHAR(50) DEFAULT NULL,
  `id_front_path` VARCHAR(255) DEFAULT NULL,
  `id_back_path` VARCHAR(255) DEFAULT NULL,
  `signature_path` VARCHAR(255) DEFAULT NULL,
  `relationship_type` ENUM('borrower','co_borrower') DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  CONSTRAINT `applicants_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: application_history
-- --------------------------------------------------------
CREATE TABLE `application_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `application_id` VARCHAR(20) NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `changed_by` INT(11) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `application_history_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`),
  CONSTRAINT `application_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: lender_settings
-- --------------------------------------------------------
CREATE TABLE `lender_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lender_name` VARCHAR(100) DEFAULT NULL,
  `lender_age` INT(11) DEFAULT NULL,
  `lender_address` TEXT DEFAULT NULL,
  `gcash_number` VARCHAR(20) DEFAULT NULL,
  `lbp_account_number` VARCHAR(20) DEFAULT NULL,
  `contact_number` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `interest_rate` DECIMAL(5,2) DEFAULT 10.00,
  `penalty_rate` DECIMAL(5,2) DEFAULT 2.00,
  `max_loan_term` INT(11) DEFAULT 10,
  `lender_signature_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create database
CREATE DATABASE IF NOT EXISTS loan_application_db;
USE loan_application_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Applications table
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(20) NOT NULL UNIQUE,
    processing_id VARCHAR(20) UNIQUE,
    approval_id VARCHAR(20) UNIQUE,
    admin_id INT,
    loan_amount DECIMAL(10,2),
    term_months INT,
    status ENUM('generated', 'submitted', 'co_applicant_completed', 'approved', 'rejected') DEFAULT 'generated',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- Applicants table
CREATE TABLE IF NOT EXISTS applicants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(20) NOT NULL,
    first_name VARCHAR(50),
    middle_name VARCHAR(50),
    last_name VARCHAR(50),
    name_extension VARCHAR(10),
    age INT,
    nationality VARCHAR(50) DEFAULT 'Filipino',
    region VARCHAR(100),
    province VARCHAR(100),
    city VARCHAR(100),
    barangay VARCHAR(100),
    zip_code VARCHAR(10),
    street_address TEXT,
    birthdate DATE,
    contact_number VARCHAR(20),
    email VARCHAR(100),
    id_type VARCHAR(50),
    id_front_path VARCHAR(255),
    id_back_path VARCHAR(255),
    signature_path VARCHAR(255),
    relationship_type ENUM('borrower', 'co_borrower'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE
);

-- Lender settings table
CREATE TABLE IF NOT EXISTS lender_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lender_name VARCHAR(100),
    lender_age INT,
    lender_address TEXT,
    gcash_number VARCHAR(20),
    lbp_account_number VARCHAR(20),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- IT Inventory System database schema
-- Target: MySQL 5.7+ / MySQL 8.0+
--
-- The database name matches .env.example. Change both this file and DB_NAME in
-- .env if a different database name is required.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `hpl`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `hpl`;

DROP TABLE IF EXISTS `returned_custody`;
DROP TABLE IF EXISTS `accessories_assignments`;
DROP TABLE IF EXISTS `accessories_temp`;
DROP TABLE IF EXISTS `accessories`;
DROP TABLE IF EXISTS `parts_history`;
DROP TABLE IF EXISTS `temp_assignments`;
DROP TABLE IF EXISTS `assignments`;
DROP TABLE IF EXISTS `temp_update_pc_parts`;
DROP TABLE IF EXISTS `temp_pc`;
DROP TABLE IF EXISTS `temp_pc_parts`;
DROP TABLE IF EXISTS `pc_parts`;
DROP TABLE IF EXISTS `pcs`;
DROP TABLE IF EXISTS `temporary_storage_parts`;
DROP TABLE IF EXISTS `parts`;
DROP TABLE IF EXISTS `employees`;
DROP TABLE IF EXISTS `company_details`;
DROP TABLE IF EXISTS `register`;
DROP TABLE IF EXISTS `users`;

-- Application users and registration invitations
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `email_code` VARCHAR(64) NOT NULL,
    `username` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `type` ENUM('Administrator', 'Support') NOT NULL DEFAULT 'Support',
    `status` VARCHAR(50) NOT NULL DEFAULT 'Active',
    `signature` VARCHAR(2048) DEFAULT NULL,
    `logged_date` DATETIME DEFAULT NULL,
    `backup_date` DATETIME DEFAULT NULL,
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `updated_at` DATETIME(6) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email_code` (`email_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `register` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email_code` VARCHAR(64) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_register_email_code` (`email_code`),
    UNIQUE KEY `uq_register_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `company_details` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `address` VARCHAR(500) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `contact` VARCHAR(30) NOT NULL,
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `updated_at` DATETIME(6) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employees are kept after resignation so custody and asset history remain valid.
CREATE TABLE `employees` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `EmployeeID` VARCHAR(20) NOT NULL,
    `FirstName` VARCHAR(100) NOT NULL,
    `LastName` VARCHAR(100) NOT NULL,
    `Email` VARCHAR(255) NOT NULL,
    `Department` VARCHAR(150) NOT NULL,
    `WorkStatus` VARCHAR(50) NOT NULL DEFAULT 'ON-SITE',
    `Status` ENUM('Active', 'Resigned') NOT NULL DEFAULT 'Active',
    `Signature` VARCHAR(2048) DEFAULT NULL,
    `signature_upload_date` DATETIME(6) DEFAULT NULL,
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `updated_at` DATETIME(6) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_employees_employee_id` (`EmployeeID`),
    KEY `idx_employees_status` (`Status`),
    KEY `idx_employees_department` (`Department`),
    KEY `idx_employees_signature_date` (`signature_upload_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parts inventory and the staging table used by the bulk-add flow.
CREATE TABLE `parts` (
    `PartID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uniqueID` VARCHAR(100) NOT NULL,
    `PRNumber` VARCHAR(100) DEFAULT NULL,
    `PartType` VARCHAR(100) NOT NULL,
    `Brand` VARCHAR(150) NOT NULL,
    `Model` VARCHAR(150) NOT NULL,
    `SerialNumber` VARCHAR(255) NOT NULL,
    `Status` ENUM('Available', 'In Use', 'Defective') NOT NULL DEFAULT 'Available',
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    -- This remains text because PartsController currently submits both standard
    -- timestamps and the legacy Y-m-d_H:i:s.u format to this column.
    `updated_at` VARCHAR(32) DEFAULT NULL,
    PRIMARY KEY (`PartID`),
    UNIQUE KEY `uq_parts_unique_id` (`uniqueID`),
    UNIQUE KEY `uq_parts_serial_number` (`SerialNumber`),
    KEY `idx_parts_status_type` (`Status`, `PartType`),
    KEY `idx_parts_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `temporary_storage_parts` (
    `PartID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `PRNumber` VARCHAR(100) DEFAULT NULL,
    `PartType` VARCHAR(100) NOT NULL,
    `Brand` VARCHAR(150) NOT NULL,
    `Model` VARCHAR(150) NOT NULL,
    `SerialNumber` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`PartID`),
    UNIQUE KEY `uq_temporary_parts_serial` (`SerialNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Computers and their currently installed parts.
CREATE TABLE `pcs` (
    `PCID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `PCName` VARCHAR(100) NOT NULL,
    `Status` ENUM('Unassigned', 'Assigned', 'Returned') NOT NULL DEFAULT 'Unassigned',
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `updated_at` DATETIME(6) DEFAULT NULL,
    PRIMARY KEY (`PCID`),
    UNIQUE KEY `uq_pcs_name` (`PCName`),
    KEY `idx_pcs_status` (`Status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pc_parts` (
    `PCID` INT UNSIGNED NOT NULL,
    `PartID` INT UNSIGNED NOT NULL,
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`PCID`, `PartID`),
    UNIQUE KEY `uq_pc_parts_part` (`PartID`),
    CONSTRAINT `fk_pc_parts_pc`
        FOREIGN KEY (`PCID`) REFERENCES `pcs` (`PCID`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_pc_parts_part`
        FOREIGN KEY (`PartID`) REFERENCES `parts` (`PartID`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Temporary selections used while building or updating a computer.
CREATE TABLE `temp_pc_parts` (
    `PartID` INT UNSIGNED NOT NULL,
    `PartType` VARCHAR(100) NOT NULL,
    `Brand` VARCHAR(150) NOT NULL,
    `Model` VARCHAR(150) NOT NULL,
    `SerialNumber` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`PartID`),
    CONSTRAINT `fk_temp_pc_parts_part`
        FOREIGN KEY (`PartID`) REFERENCES `parts` (`PartID`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `temp_pc` (
    `PCID` INT UNSIGNED NOT NULL,
    `PCName` VARCHAR(100) NOT NULL,
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`PCID`),
    UNIQUE KEY `uq_temp_pc_name` (`PCName`),
    CONSTRAINT `fk_temp_pc_pc`
        FOREIGN KEY (`PCID`) REFERENCES `pcs` (`PCID`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `temp_update_pc_parts` (
    `PartID` INT UNSIGNED NOT NULL,
    `PartType` VARCHAR(100) NOT NULL,
    `Brand` VARCHAR(150) NOT NULL,
    `Model` VARCHAR(150) NOT NULL,
    `SerialNumber` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`PartID`),
    CONSTRAINT `fk_temp_update_parts_part`
        FOREIGN KEY (`PartID`) REFERENCES `parts` (`PartID`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assignment history. AssignedDate is populated automatically because the
-- controller inserts created_at while the computer list sorts by AssignedDate.
CREATE TABLE `assignments` (
    `AssignmentID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `EmployeeID` VARCHAR(20) NOT NULL,
    `PCID` INT UNSIGNED NOT NULL,
    `AssignedDate` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `ReturnedDate` DATETIME(6) DEFAULT NULL,
    `Status` ENUM('Assigned', 'Returned') NOT NULL DEFAULT 'Assigned',
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`AssignmentID`),
    KEY `idx_assignments_pc_date` (`PCID`, `AssignedDate`),
    KEY `idx_assignments_employee_status` (`EmployeeID`, `Status`),
    CONSTRAINT `fk_assignments_employee`
        FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_assignments_pc`
        FOREIGN KEY (`PCID`) REFERENCES `pcs` (`PCID`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `temp_assignments` (
    `EmployeeID` VARCHAR(20) NOT NULL,
    `PCID` INT UNSIGNED NOT NULL,
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`EmployeeID`, `PCID`),
    UNIQUE KEY `uq_temp_assignments_pc` (`PCID`),
    CONSTRAINT `fk_temp_assignments_employee`
        FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_temp_assignments_pc`
        FOREIGN KEY (`PCID`) REFERENCES `pcs` (`PCID`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `parts_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `PartID` INT UNSIGNED NOT NULL,
    `EmployeeID` VARCHAR(20) NOT NULL,
    `Status` ENUM('Assigned', 'Returned') NOT NULL DEFAULT 'Assigned',
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `updated_at` DATETIME(6) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_parts_history_part_latest` (`PartID`, `id`),
    KEY `idx_parts_history_employee_status` (`EmployeeID`, `Status`),
    KEY `idx_parts_history_created_at` (`created_at`),
    CONSTRAINT `fk_parts_history_part`
        FOREIGN KEY (`PartID`) REFERENCES `parts` (`PartID`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_parts_history_employee`
        FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Accessories inventory, staging, and assignment history.
CREATE TABLE `accessories` (
    `AccessoriesID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `AccessoriesName` VARCHAR(150) NOT NULL,
    `Brand` VARCHAR(150) DEFAULT NULL,
    `PRNumber` VARCHAR(100) DEFAULT NULL,
    `Qty` INT UNSIGNED NOT NULL DEFAULT 0,
    `AssignedCount` INT UNSIGNED NOT NULL DEFAULT 0,
    `DefectiveCount` INT UNSIGNED NOT NULL DEFAULT 0,
    `CreatedAt` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `UpdatedAt` DATETIME(6) DEFAULT NULL,
    PRIMARY KEY (`AccessoriesID`),
    KEY `idx_accessories_name` (`AccessoriesName`),
    KEY `idx_accessories_lookup` (`AccessoriesName`, `Brand`, `PRNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accessories_temp` (
    `AccessoriesID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `AccessoriesName` VARCHAR(150) NOT NULL,
    `Brand` VARCHAR(150) DEFAULT NULL,
    `PRNumber` VARCHAR(100) DEFAULT NULL,
    `Qty` INT UNSIGNED NOT NULL DEFAULT 0,
    `CreatedAt` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`AccessoriesID`),
    KEY `idx_accessories_temp_lookup` (`AccessoriesName`, `Brand`, `PRNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `accessories_assignments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `EmployeeID` VARCHAR(20) NOT NULL,
    `AccessoriesID` INT UNSIGNED NOT NULL,
    `PRNumber` VARCHAR(100) DEFAULT NULL,
    `Status` ENUM('Assigned', 'Returned') NOT NULL DEFAULT 'Assigned',
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    `updated_at` DATETIME(6) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_accessory_assignments_employee_status` (`EmployeeID`, `Status`),
    KEY `idx_accessory_assignments_accessory_status` (`AccessoriesID`, `Status`),
    CONSTRAINT `fk_accessory_assignments_employee`
        FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_accessory_assignments_accessory`
        FOREIGN KEY (`AccessoriesID`) REFERENCES `accessories` (`AccessoriesID`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Snapshot records retained when a PC or accessory is returned.
CREATE TABLE `returned_custody` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `EmployeeID` VARCHAR(20) NOT NULL,
    `PCID` INT UNSIGNED NOT NULL,
    `PCName` VARCHAR(100) NOT NULL,
    `PartID` INT UNSIGNED DEFAULT NULL,
    `AccessoriesID` INT UNSIGNED DEFAULT NULL,
    `AccessoriesPRNumber` VARCHAR(100) DEFAULT NULL,
    `AccessoriesName` VARCHAR(150) DEFAULT NULL,
    `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (`id`),
    KEY `idx_returned_custody_employee_pc` (`EmployeeID`, `PCID`),
    KEY `idx_returned_custody_part` (`PartID`),
    KEY `idx_returned_custody_accessory` (`AccessoriesID`),
    CONSTRAINT `fk_returned_custody_employee`
        FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_returned_custody_pc`
        FOREIGN KEY (`PCID`) REFERENCES `pcs` (`PCID`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_returned_custody_part`
        FOREIGN KEY (`PartID`) REFERENCES `parts` (`PartID`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_returned_custody_accessory`
        FOREIGN KEY (`AccessoriesID`) REFERENCES `accessories` (`AccessoriesID`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- No default administrator is inserted to avoid shipping known credentials.
-- Create the initial user with a password_hash()-generated password and set:
--     type = 'Administrator', status = 'Main Admin'

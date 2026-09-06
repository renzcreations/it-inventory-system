-- IT Inventory SaaS + CMS + RBAC additive migration
-- Compatible with MySQL 5.7+ and MariaDB 10.2+
--
-- IMPORTANT
-- 1. Select the EXISTING application database in phpMyAdmin before importing.
-- 2. Back up the database first.
-- 3. Run this file once. It never drops or truncates existing tables/data.
-- 4. Existing records are assigned to the initial organization (ID 1).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `organizations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(160) NOT NULL,
    `slug` VARCHAR(120) NOT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(40) DEFAULT NULL,
    `timezone` VARCHAR(80) NOT NULL DEFAULT 'Asia/Manila',
    `locale` VARCHAR(16) NOT NULL DEFAULT 'en',
    `currency` CHAR(3) NOT NULL DEFAULT 'PHP',
    `logo_path` VARCHAR(500) DEFAULT NULL,
    `status` VARCHAR(40) NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_organizations_slug` (`slug`),
    KEY `idx_organizations_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `organizations` (`id`, `name`, `slug`, `email`, `status`)
VALUES (1, 'Default Organization', 'default', NULL, 'active')
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Tenant ownership columns. Defaults preserve all current data.
ALTER TABLE `users`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`,
    ADD COLUMN `last_login_ip` VARCHAR(45) DEFAULT NULL AFTER `logged_date`,
    ADD COLUMN `mfa_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `last_login_ip`,
    ADD COLUMN `mfa_secret_encrypted` TEXT DEFAULT NULL AFTER `mfa_enabled`,
    MODIFY COLUMN `type` VARCHAR(80) NOT NULL DEFAULT 'support';

ALTER TABLE `register`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;

ALTER TABLE `company_details`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;

ALTER TABLE `employees`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`,
    ADD COLUMN `JobTitle` VARCHAR(120) DEFAULT NULL AFTER `Department`,
    MODIFY COLUMN `Status` VARCHAR(80) NOT NULL DEFAULT 'active';

ALTER TABLE `parts`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `PartID`,
    MODIFY COLUMN `Status` VARCHAR(80) NOT NULL DEFAULT 'available';

ALTER TABLE `temporary_storage_parts`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `PartID`;

ALTER TABLE `pcs`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `PCID`,
    ADD COLUMN `Category` VARCHAR(100) DEFAULT 'desktop' AFTER `PCName`,
    MODIFY COLUMN `Status` VARCHAR(80) NOT NULL DEFAULT 'unassigned';

ALTER TABLE `pc_parts`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 FIRST;

ALTER TABLE `temp_pc_parts`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 FIRST;

ALTER TABLE `temp_pc`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 FIRST;

ALTER TABLE `temp_update_pc_parts`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 FIRST;

ALTER TABLE `assignments`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `AssignmentID`,
    MODIFY COLUMN `Status` VARCHAR(80) NOT NULL DEFAULT 'assigned';

ALTER TABLE `temp_assignments`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 FIRST;

ALTER TABLE `parts_history`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`,
    MODIFY COLUMN `Status` VARCHAR(80) NOT NULL DEFAULT 'assigned';

ALTER TABLE `accessories`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `AccessoriesID`;

ALTER TABLE `accessories_temp`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `AccessoriesID`;

ALTER TABLE `accessories_assignments`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`,
    MODIFY COLUMN `Status` VARCHAR(80) NOT NULL DEFAULT 'assigned';

ALTER TABLE `returned_custody`
    ADD COLUMN `organization_id` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;

-- Configurable CMS catalogs. Codes are stable application identifiers; labels,
-- colors, order, descriptions, and active state are editable by administrators.
CREATE TABLE IF NOT EXISTS `catalog_groups` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED NOT NULL,
    `code` VARCHAR(100) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` VARCHAR(500) DEFAULT NULL,
    `entity_type` VARCHAR(100) DEFAULT NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_catalog_groups_org_code` (`organization_id`, `code`),
    KEY `idx_catalog_groups_org_sort` (`organization_id`, `sort_order`, `name`),
    CONSTRAINT `fk_catalog_groups_org` FOREIGN KEY (`organization_id`)
        REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `catalog_values` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED NOT NULL,
    `group_id` INT UNSIGNED NOT NULL,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `code` VARCHAR(100) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` VARCHAR(500) DEFAULT NULL,
    `color` VARCHAR(30) DEFAULT 'slate',
    `icon` VARCHAR(100) DEFAULT NULL,
    `metadata` LONGTEXT DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_catalog_values_group_code` (`group_id`, `code`),
    KEY `idx_catalog_values_org_group_active` (`organization_id`, `group_id`, `is_active`, `sort_order`),
    KEY `idx_catalog_values_parent` (`parent_id`),
    CONSTRAINT `fk_catalog_values_org` FOREIGN KEY (`organization_id`)
        REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_catalog_values_group` FOREIGN KEY (`group_id`)
        REFERENCES `catalog_groups` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_catalog_values_parent` FOREIGN KEY (`parent_id`)
        REFERENCES `catalog_values` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `app_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED NOT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'general',
    `setting_key` VARCHAR(150) NOT NULL,
    `setting_value` LONGTEXT DEFAULT NULL,
    `value_type` VARCHAR(30) NOT NULL DEFAULT 'string',
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_app_settings_org_key` (`organization_id`, `setting_key`),
    KEY `idx_app_settings_org_category` (`organization_id`, `category`),
    CONSTRAINT `fk_app_settings_org` FOREIGN KEY (`organization_id`)
        REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `navigation_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED NOT NULL,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `label` VARCHAR(100) NOT NULL,
    `route` VARCHAR(255) NOT NULL,
    `permission` VARCHAR(150) DEFAULT NULL,
    `icon` VARCHAR(80) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_navigation_org_route` (`organization_id`, `route`),
    KEY `idx_navigation_org_active_sort` (`organization_id`, `is_active`, `sort_order`),
    CONSTRAINT `fk_navigation_org` FOREIGN KEY (`organization_id`)
        REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_navigation_parent` FOREIGN KEY (`parent_id`)
        REFERENCES `navigation_items` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Laravel/Yii-style RBAC: users -> roles -> permissions.
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` VARCHAR(500) DEFAULT NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_org_slug` (`organization_id`, `slug`),
    KEY `idx_roles_org_active` (`organization_id`, `is_active`),
    CONSTRAINT `fk_roles_org` FOREIGN KEY (`organization_id`)
        REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `slug` VARCHAR(150) NOT NULL,
    `module` VARCHAR(100) NOT NULL,
    `description` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_permissions_slug` (`slug`),
    KEY `idx_permissions_module_name` (`module`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `permission_id`),
    KEY `idx_role_permissions_permission` (`permission_id`, `role_id`),
    CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`)
        REFERENCES `roles` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`)
        REFERENCES `permissions` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_roles` (
    `organization_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`organization_id`, `user_id`, `role_id`),
    KEY `idx_user_roles_user_org` (`user_id`, `organization_id`),
    KEY `idx_user_roles_role` (`role_id`, `user_id`),
    CONSTRAINT `fk_user_roles_org` FOREIGN KEY (`organization_id`)
        REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`)
        REFERENCES `roles` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(120) NOT NULL,
    `entity_type` VARCHAR(120) DEFAULT NULL,
    `entity_id` VARCHAR(120) DEFAULT NULL,
    `old_values` LONGTEXT DEFAULT NULL,
    `new_values` LONGTEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_org_created` (`organization_id`, `created_at`),
    KEY `idx_audit_org_entity` (`organization_id`, `entity_type`, `entity_id`),
    KEY `idx_audit_user_created` (`user_id`, `created_at`),
    CONSTRAINT `fk_audit_org` FOREIGN KEY (`organization_id`)
        REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report definitions and generated-file audit records.
CREATE TABLE IF NOT EXISTS `report_templates` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED NOT NULL,
    `code` VARCHAR(100) NOT NULL,
    `name` VARCHAR(160) NOT NULL,
    `description` VARCHAR(500) DEFAULT NULL,
    `paper_size` VARCHAR(30) NOT NULL DEFAULT 'A4',
    `orientation` VARCHAR(20) NOT NULL DEFAULT 'portrait',
    `columns_config` LONGTEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_report_templates_org_code` (`organization_id`, `code`),
    KEY `idx_report_templates_org_active` (`organization_id`, `is_active`, `name`),
    CONSTRAINT `fk_report_templates_org` FOREIGN KEY (`organization_id`)
        REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `generated_reports` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `report_code` VARCHAR(100) NOT NULL,
    `filters` LONGTEXT DEFAULT NULL,
    `file_name` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_generated_reports_org_created` (`organization_id`, `created_at`),
    KEY `idx_generated_reports_user_created` (`user_id`, `created_at`),
    CONSTRAINT `fk_generated_reports_org` FOREIGN KEY (`organization_id`)
        REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_generated_reports_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SaaS plan/subscription metadata (payment provider secrets remain outside SQL).
CREATE TABLE IF NOT EXISTS `subscription_plans` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `price_monthly` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `currency` CHAR(3) NOT NULL DEFAULT 'USD',
    `limits_config` LONGTEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_subscription_plans_slug` (`slug`),
    KEY `idx_subscription_plans_active` (`is_active`, `price_monthly`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `organization_subscriptions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED NOT NULL,
    `plan_id` INT UNSIGNED NOT NULL,
    `provider` VARCHAR(80) DEFAULT NULL,
    `provider_customer_id` VARCHAR(190) DEFAULT NULL,
    `provider_subscription_id` VARCHAR(190) DEFAULT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'trialing',
    `trial_ends_at` DATETIME DEFAULT NULL,
    `current_period_ends_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_org_subscriptions_provider_id` (`provider`, `provider_subscription_id`),
    KEY `idx_org_subscriptions_org_status` (`organization_id`, `status`),
    KEY `idx_org_subscriptions_period_end` (`current_period_ends_at`),
    CONSTRAINT `fk_org_subscriptions_org` FOREIGN KEY (`organization_id`)
        REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_org_subscriptions_plan` FOREIGN KEY (`plan_id`)
        REFERENCES `subscription_plans` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Authentication security records. Tokens are stored only as hashes.
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED DEFAULT NULL,
    `identity` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `was_successful` TINYINT(1) NOT NULL DEFAULT 0,
    `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_login_attempts_identity_time` (`identity`, `attempted_at`),
    KEY `idx_login_attempts_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_password_reset_token_hash` (`token_hash`),
    KEY `idx_password_reset_user_expiry` (`user_id`, `expires_at`),
    CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Convert business identifiers from installation-wide uniqueness to tenant
-- uniqueness. Existing values and rows are preserved; only indexes/FKs change.
ALTER TABLE `assignments` DROP FOREIGN KEY `fk_assignments_employee`;
ALTER TABLE `temp_assignments` DROP FOREIGN KEY `fk_temp_assignments_employee`;
ALTER TABLE `parts_history` DROP FOREIGN KEY `fk_parts_history_employee`;
ALTER TABLE `accessories_assignments` DROP FOREIGN KEY `fk_accessory_assignments_employee`;
ALTER TABLE `returned_custody` DROP FOREIGN KEY `fk_returned_custody_employee`;

ALTER TABLE `employees`
    DROP INDEX `uq_employees_employee_id`,
    ADD UNIQUE KEY `uq_employees_org_employee_id` (`organization_id`, `EmployeeID`);
ALTER TABLE `parts`
    DROP INDEX `uq_parts_unique_id`,
    DROP INDEX `uq_parts_serial_number`,
    ADD UNIQUE KEY `uq_parts_org_unique_id` (`organization_id`, `uniqueID`),
    ADD UNIQUE KEY `uq_parts_org_serial` (`organization_id`, `SerialNumber`);
ALTER TABLE `temporary_storage_parts`
    DROP INDEX `uq_temporary_parts_serial`,
    ADD UNIQUE KEY `uq_temporary_parts_org_serial` (`organization_id`, `SerialNumber`);
ALTER TABLE `pcs`
    DROP INDEX `uq_pcs_name`,
    ADD UNIQUE KEY `uq_pcs_org_name` (`organization_id`, `PCName`);

ALTER TABLE `assignments` ADD CONSTRAINT `fk_assignments_employee_org`
    FOREIGN KEY (`organization_id`, `EmployeeID`) REFERENCES `employees` (`organization_id`, `EmployeeID`)
    ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `temp_assignments` ADD CONSTRAINT `fk_temp_assignments_employee_org`
    FOREIGN KEY (`organization_id`, `EmployeeID`) REFERENCES `employees` (`organization_id`, `EmployeeID`)
    ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE `parts_history` ADD CONSTRAINT `fk_parts_history_employee_org`
    FOREIGN KEY (`organization_id`, `EmployeeID`) REFERENCES `employees` (`organization_id`, `EmployeeID`)
    ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `accessories_assignments` ADD CONSTRAINT `fk_accessory_assignments_employee_org`
    FOREIGN KEY (`organization_id`, `EmployeeID`) REFERENCES `employees` (`organization_id`, `EmployeeID`)
    ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `returned_custody` ADD CONSTRAINT `fk_returned_custody_employee_org`
    FOREIGN KEY (`organization_id`, `EmployeeID`) REFERENCES `employees` (`organization_id`, `EmployeeID`)
    ON UPDATE CASCADE ON DELETE RESTRICT;

-- Scalable tenant-first indexes for current access paths.
ALTER TABLE `users`
    ADD KEY `idx_users_org_type_status` (`organization_id`, `type`, `status`),
    ADD KEY `idx_users_org_email` (`organization_id`, `email`);
ALTER TABLE `register`
    ADD KEY `idx_register_org_created` (`organization_id`, `created_at`);
ALTER TABLE `company_details`
    ADD KEY `idx_company_details_org` (`organization_id`);
ALTER TABLE `employees`
    ADD KEY `idx_employees_org_status_department` (`organization_id`, `Status`, `Department`),
    ADD KEY `idx_employees_org_name` (`organization_id`, `LastName`, `FirstName`),
    ADD KEY `idx_employees_org_title` (`organization_id`, `JobTitle`);
ALTER TABLE `parts`
    ADD KEY `idx_parts_org_status_type` (`organization_id`, `Status`, `PartType`),
    ADD KEY `idx_parts_org_created` (`organization_id`, `created_at`),
    ADD KEY `idx_parts_org_pr` (`organization_id`, `PRNumber`);
ALTER TABLE `temporary_storage_parts`
    ADD KEY `idx_temp_storage_parts_org_created` (`organization_id`, `created_at`);
ALTER TABLE `pcs`
    ADD KEY `idx_pcs_org_status_name` (`organization_id`, `Status`, `PCName`),
    ADD KEY `idx_pcs_org_category` (`organization_id`, `Category`);
ALTER TABLE `pc_parts`
    ADD KEY `idx_pc_parts_org_pc` (`organization_id`, `PCID`),
    ADD KEY `idx_pc_parts_org_part` (`organization_id`, `PartID`);
ALTER TABLE `temp_pc_parts`
    ADD KEY `idx_temp_pc_parts_org` (`organization_id`);
ALTER TABLE `temp_pc`
    ADD KEY `idx_temp_pc_org` (`organization_id`);
ALTER TABLE `temp_update_pc_parts`
    ADD KEY `idx_temp_update_parts_org` (`organization_id`);
ALTER TABLE `assignments`
    ADD KEY `idx_assignments_org_status_date` (`organization_id`, `Status`, `AssignedDate`),
    ADD KEY `idx_assignments_org_employee` (`organization_id`, `EmployeeID`, `Status`),
    ADD KEY `idx_assignments_org_pc` (`organization_id`, `PCID`, `Status`);
ALTER TABLE `temp_assignments`
    ADD KEY `idx_temp_assignments_org` (`organization_id`);
ALTER TABLE `parts_history`
    ADD KEY `idx_parts_history_org_part_created` (`organization_id`, `PartID`, `created_at`),
    ADD KEY `idx_parts_history_org_employee_status` (`organization_id`, `EmployeeID`, `Status`);
ALTER TABLE `accessories`
    ADD KEY `idx_accessories_org_name_lookup` (`organization_id`, `AccessoriesName`, `Brand`, `PRNumber`);
ALTER TABLE `accessories_temp`
    ADD KEY `idx_accessories_temp_org` (`organization_id`);
ALTER TABLE `accessories_assignments`
    ADD KEY `idx_accessory_assign_org_employee_status` (`organization_id`, `EmployeeID`, `Status`),
    ADD KEY `idx_accessory_assign_org_item_status` (`organization_id`, `AccessoriesID`, `Status`);
ALTER TABLE `returned_custody`
    ADD KEY `idx_returned_org_employee_pc` (`organization_id`, `EmployeeID`, `PCID`),
    ADD KEY `idx_returned_org_created` (`organization_id`, `created_at`);

-- Foreign keys are added after existing rows receive organization 1.
ALTER TABLE `users` ADD CONSTRAINT `fk_users_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `register` ADD CONSTRAINT `fk_register_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE `company_details` ADD CONSTRAINT `fk_company_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE `employees` ADD CONSTRAINT `fk_employees_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `parts` ADD CONSTRAINT `fk_parts_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `pcs` ADD CONSTRAINT `fk_pcs_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `assignments` ADD CONSTRAINT `fk_assignments_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `parts_history` ADD CONSTRAINT `fk_parts_history_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `accessories` ADD CONSTRAINT `fk_accessories_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `accessories_assignments` ADD CONSTRAINT `fk_accessory_assignments_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE `returned_custody` ADD CONSTRAINT `fk_returned_custody_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT;

-- Default CMS catalog groups.
INSERT INTO `catalog_groups` (`organization_id`, `code`, `name`, `description`, `entity_type`, `is_system`, `sort_order`) VALUES
(1, 'employee_statuses', 'Employee Statuses', 'Employment lifecycle statuses', 'employee', 1, 10),
(1, 'work_arrangements', 'Work Arrangements', 'Where employees perform their work', 'employee', 0, 20),
(1, 'job_titles', 'Job Titles', 'Employee position titles', 'employee', 0, 30),
(1, 'departments', 'Departments', 'Organization departments', 'employee', 0, 40),
(1, 'part_categories', 'Part Categories', 'Computer component categories', 'part', 0, 50),
(1, 'part_statuses', 'Part Statuses', 'Part lifecycle statuses', 'part', 1, 60),
(1, 'accessory_categories', 'Accessory Categories', 'Accessory inventory categories', 'accessory', 0, 70),
(1, 'computer_categories', 'Computer Categories', 'Computer form factors', 'computer', 0, 80),
(1, 'computer_statuses', 'Computer Statuses', 'Computer lifecycle statuses', 'computer', 1, 90),
(1, 'assignment_statuses', 'Assignment Statuses', 'Asset assignment lifecycle statuses', 'assignment', 1, 100)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

INSERT INTO `catalog_values` (`organization_id`, `group_id`, `code`, `name`, `color`, `sort_order`, `is_system`)
SELECT 1, g.id, seed.code, seed.name, seed.color, seed.sort_order, seed.is_system
FROM `catalog_groups` g
INNER JOIN (
    SELECT 'employee_statuses' group_code, 'active' code, 'Active' name, 'emerald' color, 10 sort_order, 1 is_system UNION ALL
    SELECT 'employee_statuses', 'resigned', 'Resigned', 'rose', 20, 1 UNION ALL
    SELECT 'work_arrangements', 'on-site', 'On-site', 'slate', 10, 0 UNION ALL
    SELECT 'work_arrangements', 'wfh', 'Work from home', 'amber', 20, 0 UNION ALL
    SELECT 'work_arrangements', 'hybrid', 'Hybrid', 'sky', 30, 0 UNION ALL
    SELECT 'part_statuses', 'available', 'Available', 'emerald', 10, 1 UNION ALL
    SELECT 'part_statuses', 'in-use', 'In use', 'sky', 20, 1 UNION ALL
    SELECT 'part_statuses', 'defective', 'Defective', 'rose', 30, 1 UNION ALL
    SELECT 'computer_statuses', 'unassigned', 'Unassigned', 'slate', 10, 1 UNION ALL
    SELECT 'computer_statuses', 'assigned', 'Assigned', 'sky', 20, 1 UNION ALL
    SELECT 'computer_statuses', 'returned', 'Returned', 'amber', 30, 1 UNION ALL
    SELECT 'assignment_statuses', 'assigned', 'Assigned', 'sky', 10, 1 UNION ALL
    SELECT 'assignment_statuses', 'returned', 'Returned', 'amber', 20, 1 UNION ALL
    SELECT 'computer_categories', 'desktop', 'Desktop', 'slate', 10, 0 UNION ALL
    SELECT 'computer_categories', 'laptop', 'Laptop', 'slate', 20, 0 UNION ALL
    SELECT 'computer_categories', 'workstation', 'Workstation', 'slate', 30, 0 UNION ALL
    SELECT 'part_categories', 'processor', 'Processor', 'slate', 10, 0 UNION ALL
    SELECT 'part_categories', 'motherboard', 'Motherboard', 'slate', 20, 0 UNION ALL
    SELECT 'part_categories', 'gpu', 'GPU', 'slate', 30, 0 UNION ALL
    SELECT 'part_categories', 'ram', 'RAM', 'slate', 40, 0 UNION ALL
    SELECT 'part_categories', 'hdd', 'HDD', 'slate', 50, 0 UNION ALL
    SELECT 'part_categories', 'ssd', 'SSD', 'slate', 60, 0 UNION ALL
    SELECT 'part_categories', 'monitor', 'Monitor', 'slate', 70, 0 UNION ALL
    SELECT 'accessory_categories', 'keyboard', 'Keyboard', 'slate', 10, 0 UNION ALL
    SELECT 'accessory_categories', 'mouse', 'Mouse', 'slate', 20, 0 UNION ALL
    SELECT 'accessory_categories', 'webcam', 'Webcam', 'slate', 30, 0 UNION ALL
    SELECT 'accessory_categories', 'headset', 'Headset', 'slate', 40, 0 UNION ALL
    SELECT 'accessory_categories', 'chair', 'Chair', 'slate', 50, 0 UNION ALL
    SELECT 'accessory_categories', 'table', 'Table', 'slate', 60, 0
) seed ON seed.group_code = g.code
WHERE g.organization_id = 1
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `color` = VALUES(`color`), `sort_order` = VALUES(`sort_order`);

INSERT INTO `permissions` (`name`, `slug`, `module`, `description`) VALUES
('View dashboard', 'dashboard.view', 'dashboard', 'View inventory summaries'),
('View employees', 'employees.view', 'employees', 'View employee records'),
('Manage employees', 'employees.manage', 'employees', 'Create, update, import, and resign employees'),
('View parts', 'parts.view', 'parts', 'View parts inventory'),
('Manage parts', 'parts.manage', 'parts', 'Create and update parts'),
('View accessories', 'accessories.view', 'accessories', 'View accessory inventory'),
('Manage accessories', 'accessories.manage', 'accessories', 'Create, assign, return, and update accessories'),
('View computers', 'computers.view', 'computers', 'View computers and custody history'),
('Manage computers', 'computers.manage', 'computers', 'Assign, return, and modify computers'),
('Build computers', 'build.manage', 'build', 'Create computer builds'),
('Manage users', 'users.manage', 'users', 'Invite and manage application users'),
('Manage company', 'company.manage', 'settings', 'Manage company identity and contact details'),
('Manage catalogs', 'catalogs.manage', 'settings', 'Manage statuses, categories, departments, and titles'),
('Manage roles', 'roles.manage', 'settings', 'Manage roles and permission assignments'),
('View reports', 'reports.view', 'reports', 'View report center'),
('Export reports', 'reports.export', 'reports', 'Generate and download reports'),
('Manage profile', 'profile.manage', 'profile', 'Update own profile and password'),
('Run backups', 'backup.manage', 'backup', 'Create database backups'),
('View audit log', 'audit.view', 'audit', 'View security and change audit records')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `module` = VALUES(`module`), `description` = VALUES(`description`);

INSERT INTO `roles` (`organization_id`, `name`, `slug`, `description`, `is_system`) VALUES
(1, 'Owner', 'owner', 'Workspace owner with every permission', 1),
(1, 'Administrator', 'administrator', 'Inventory and user administrator', 1),
(1, 'Support', 'support', 'Operational inventory support', 1),
(1, 'Auditor', 'auditor', 'Read-only inventory and audit access', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r CROSS JOIN `permissions` p
WHERE r.organization_id = 1 AND r.slug IN ('owner', 'administrator');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r CROSS JOIN `permissions` p
WHERE r.organization_id = 1 AND r.slug = 'support'
AND p.slug IN ('dashboard.view', 'employees.view', 'parts.view', 'parts.manage',
               'accessories.view', 'accessories.manage', 'computers.view',
               'computers.manage', 'build.manage', 'reports.view', 'reports.export', 'profile.manage');

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r CROSS JOIN `permissions` p
WHERE r.organization_id = 1 AND r.slug = 'auditor'
AND p.slug IN ('dashboard.view', 'employees.view', 'parts.view', 'accessories.view',
               'computers.view', 'reports.view', 'reports.export', 'audit.view', 'profile.manage');

INSERT IGNORE INTO `user_roles` (`organization_id`, `user_id`, `role_id`)
SELECT u.organization_id, u.id, r.id
FROM `users` u
INNER JOIN `roles` r ON r.organization_id = u.organization_id
AND r.slug = CASE
    WHEN LOWER(u.status) = 'main admin' THEN 'owner'
    WHEN LOWER(u.type) = 'administrator' THEN 'administrator'
    ELSE 'support'
END;

INSERT INTO `app_settings` (`organization_id`, `category`, `setting_key`, `setting_value`, `value_type`, `is_public`) VALUES
(1, 'branding', 'app.name', 'IT Inventory', 'string', 1),
(1, 'branding', 'app.tagline', 'Assets, people, and accountability in one place.', 'string', 1),
(1, 'branding', 'app.primary_color', '#0f766e', 'color', 1),
(1, 'branding', 'app.logo_path', '', 'string', 1),
(1, 'regional', 'app.timezone', 'Asia/Manila', 'string', 0),
(1, 'regional', 'app.date_format', 'M j, Y', 'string', 0),
(1, 'security', 'security.session_minutes', '120', 'integer', 0),
(1, 'security', 'security.max_login_attempts', '5', 'integer', 0),
(1, 'security', 'security.lockout_minutes', '15', 'integer', 0),
(1, 'reports', 'reports.footer', 'Generated by IT Inventory', 'string', 0)
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

INSERT INTO `navigation_items` (`organization_id`, `label`, `route`, `permission`, `icon`, `sort_order`) VALUES
(1, 'Dashboard', '/dashboard', 'dashboard.view', 'grid', 10),
(1, 'Employees', '/employee', 'employees.view', 'users', 20),
(1, 'Parts', '/parts', 'parts.view', 'cpu', 30),
(1, 'Accessories', '/accessories', 'accessories.view', 'mouse', 40),
(1, 'Builds', '/build', 'build.manage', 'wrench', 50),
(1, 'Computers', '/computer', 'computers.view', 'monitor', 60),
(1, 'Reports', '/reports', 'reports.view', 'report', 70),
(1, 'Users & roles', '/users', 'users.manage', 'shield', 80),
(1, 'CMS settings', '/settings', 'catalogs.manage', 'settings', 90)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `permission` = VALUES(`permission`), `sort_order` = VALUES(`sort_order`);

INSERT INTO `report_templates` (`organization_id`, `code`, `name`, `description`, `paper_size`, `orientation`) VALUES
(1, 'inventory-summary', 'Inventory Summary', 'Current parts, accessories, and computer totals', 'A4', 'portrait'),
(1, 'asset-register', 'Asset Register', 'Detailed computer and installed-parts register', 'A4', 'landscape'),
(1, 'employee-custody', 'Employee Custody', 'Employee equipment assignments', 'A4', 'portrait'),
(1, 'defective-assets', 'Defective Assets', 'Parts and accessories marked defective', 'A4', 'portrait')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

INSERT INTO `subscription_plans` (`name`, `slug`, `price_monthly`, `currency`, `limits_config`) VALUES
('Starter', 'starter', 0, 'USD', '{"users":5,"assets":500}'),
('Growth', 'growth', 49, 'USD', '{"users":25,"assets":5000}'),
('Business', 'business', 149, 'USD', '{"users":100,"assets":50000}')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `price_monthly` = VALUES(`price_monthly`);

SET FOREIGN_KEY_CHECKS = 1;

-- End of additive migration.

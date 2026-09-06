-- ========================================================
-- NIRMAN ERP DATABASE BACKUP
-- Generated: 2026-09-03 01:55:33
-- Shop ID: 1
-- ========================================================

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `shops`;
CREATE TABLE `shops` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_code` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `proprietor` varchar(100) DEFAULT NULL,
  `address` text NOT NULL,
  `phone` varchar(30) NOT NULL,
  `alt_phone` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(150) DEFAULT NULL,
  `invoice_prefix` varchar(10) NOT NULL DEFAULT 'MT',
  `currency_symbol` varchar(10) NOT NULL DEFAULT '৳',
  `currency_code` varchar(10) NOT NULL DEFAULT 'BDT',
  `currency_position` enum('before','after') NOT NULL DEFAULT 'before',
  `date_format` varchar(20) NOT NULL DEFAULT 'd-m-Y',
  `invoice_footer_shop` text DEFAULT NULL,
  `invoice_footer_customer` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `shop_code` (`shop_code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `shops` (`id`, `shop_code`, `name`, `proprietor`, `address`, `phone`, `alt_phone`, `email`, `website`, `invoice_prefix`, `currency_symbol`, `currency_code`, `currency_position`, `date_format`, `invoice_footer_shop`, `invoice_footer_customer`, `logo_url`, `is_active`, `created_at`, `updated_at`) VALUES ('1', 'SHOP001', 'Maruf Traders', 'Md. Maruf Hasan', 'Highway Road, Station Bazaar, Habiganj, Sylhet', '01711-000000', '01811-000000', 'info@maruftraders.com', 'www.maruftraders.com', 'MT', '৳', 'BDT', 'before', 'd-m-Y', 'Shop Copy - For official accounting and inventory records.', 'Customer Copy - Thank you for your business! Goods once sold can be returned within 7 days with valid memo.', NULL, '1', '2026-09-03 00:55:24', '2026-09-03 01:33:47');

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_shop_setting` (`shop_id`,`setting_key`),
  CONSTRAINT `fk_settings_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`, `shop_id`, `setting_key`, `setting_value`, `is_system`, `created_at`, `updated_at`) VALUES ('1', '1', 'theme_mode', 'system', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `settings` (`id`, `shop_id`, `setting_key`, `setting_value`, `is_system`, `created_at`, `updated_at`) VALUES ('2', '1', 'primary_color', '#1e40af', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `settings` (`id`, `shop_id`, `setting_key`, `setting_value`, `is_system`, `created_at`, `updated_at`) VALUES ('3', '1', 'enable_tax', '0', '0', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `settings` (`id`, `shop_id`, `setting_key`, `setting_value`, `is_system`, `created_at`, `updated_at`) VALUES ('4', '1', 'tax_rate', '0', '0', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `settings` (`id`, `shop_id`, `setting_key`, `setting_value`, `is_system`, `created_at`, `updated_at`) VALUES ('5', '1', 'print_format', 'a4_dual', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `settings` (`id`, `shop_id`, `setting_key`, `setting_value`, `is_system`, `created_at`, `updated_at`) VALUES ('6', '1', 'top_selling_period_days', '30', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `settings` (`id`, `shop_id`, `setting_key`, `setting_value`, `is_system`, `created_at`, `updated_at`) VALUES ('7', '1', 'auto_print_memo', '1', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `settings` (`id`, `shop_id`, `setting_key`, `setting_value`, `is_system`, `created_at`, `updated_at`) VALUES ('8', '1', 'rod_unit_id', '2', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `settings` (`id`, `shop_id`, `setting_key`, `setting_value`, `is_system`, `created_at`, `updated_at`) VALUES ('9', '1', 'cement_unit_id', '1', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `settings` (`id`, `shop_id`, `setting_key`, `setting_value`, `is_system`, `created_at`, `updated_at`) VALUES ('10', '1', 'sand_unit_id', '4', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `settings` (`id`, `shop_id`, `setting_key`, `setting_value`, `is_system`, `created_at`, `updated_at`) VALUES ('11', '1', 'brick_unit_id', '5', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');

DROP TABLE IF EXISTS `document_sequences`;
CREATE TABLE `document_sequences` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `code` varchar(50) NOT NULL,
  `fiscal_year` varchar(10) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  `current_number` bigint(20) unsigned NOT NULL DEFAULT 0,
  `padding` int(10) unsigned NOT NULL DEFAULT 6,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_seq` (`shop_id`,`code`,`fiscal_year`),
  CONSTRAINT `fk_docseq_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `document_sequences` (`id`, `shop_id`, `code`, `fiscal_year`, `prefix`, `current_number`, `padding`, `created_at`, `updated_at`) VALUES ('1', '1', 'MEMO', '2026', 'MT-2026-', '4', '6', '2026-09-03 00:55:24', '2026-09-03 01:48:17');
INSERT INTO `document_sequences` (`id`, `shop_id`, `code`, `fiscal_year`, `prefix`, `current_number`, `padding`, `created_at`, `updated_at`) VALUES ('2', '1', 'PURCHASE', '2026', 'PUR-2026-', '0', '6', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `document_sequences` (`id`, `shop_id`, `code`, `fiscal_year`, `prefix`, `current_number`, `padding`, `created_at`, `updated_at`) VALUES ('3', '1', 'PAYMENT', '2026', 'PAY-2026-', '0', '6', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `document_sequences` (`id`, `shop_id`, `code`, `fiscal_year`, `prefix`, `current_number`, `padding`, `created_at`, `updated_at`) VALUES ('4', '1', 'ADVANCE', '2026', 'ADV-2026-', '2', '6', '2026-09-03 00:55:24', '2026-09-03 01:17:34');
INSERT INTO `document_sequences` (`id`, `shop_id`, `code`, `fiscal_year`, `prefix`, `current_number`, `padding`, `created_at`, `updated_at`) VALUES ('5', '1', 'SALE_RETURN', '2026', 'SR-2026-', '0', '6', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `document_sequences` (`id`, `shop_id`, `code`, `fiscal_year`, `prefix`, `current_number`, `padding`, `created_at`, `updated_at`) VALUES ('6', '1', 'PURCHASE_RETURN', '2026', 'PR-2026-', '0', '6', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `document_sequences` (`id`, `shop_id`, `code`, `fiscal_year`, `prefix`, `current_number`, `padding`, `created_at`, `updated_at`) VALUES ('7', '1', 'QUOTATION', '2026', 'QT-2026-', '0', '6', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `document_sequences` (`id`, `shop_id`, `code`, `fiscal_year`, `prefix`, `current_number`, `padding`, `created_at`, `updated_at`) VALUES ('8', '1', 'ADJUSTMENT', '2026', 'ADJ-2026-', '0', '6', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `document_sequences` (`id`, `shop_id`, `code`, `fiscal_year`, `prefix`, `current_number`, `padding`, `created_at`, `updated_at`) VALUES ('9', '1', 'EXPENSE', '2026', 'EXP-2026-', '0', '6', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `document_sequences` (`id`, `shop_id`, `code`, `fiscal_year`, `prefix`, `current_number`, `padding`, `created_at`, `updated_at`) VALUES ('10', '1', 'INCOME', '2026', 'INC-2026-', '0', '6', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `document_sequences` (`id`, `shop_id`, `code`, `fiscal_year`, `prefix`, `current_number`, `padding`, `created_at`, `updated_at`) VALUES ('11', '1', 'TRANSACTION', '2026', 'TXN-2026-', '4', '6', '2026-09-03 00:55:24', '2026-09-03 01:17:11');

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_shop_role` (`shop_id`,`slug`),
  CONSTRAINT `fk_roles_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `shop_id`, `name`, `slug`, `description`, `is_system`, `created_at`, `updated_at`) VALUES ('1', '1', 'Super Admin', 'super_admin', 'Unrestricted administrative access to all shop modules and settings', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `roles` (`id`, `shop_id`, `name`, `slug`, `description`, `is_system`, `created_at`, `updated_at`) VALUES ('2', '1', 'Admin', 'admin', 'Full shop management including sales, purchases, inventory, finance, and reports', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `roles` (`id`, `shop_id`, `name`, `slug`, `description`, `is_system`, `created_at`, `updated_at`) VALUES ('3', '1', 'Manager', 'manager', 'Operational management including sales, inventory, and customer relations', '0', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `roles` (`id`, `shop_id`, `name`, `slug`, `description`, `is_system`, `created_at`, `updated_at`) VALUES ('4', '1', 'Salesman', 'salesman', 'Front-desk point of sale, customer creation, and quick memo generation', '0', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `roles` (`id`, `shop_id`, `name`, `slug`, `description`, `is_system`, `created_at`, `updated_at`) VALUES ('5', '1', 'Accountant', 'accountant', 'Ledger management, due collections, expenses, bank accounts, and finance reports', '0', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `roles` (`id`, `shop_id`, `name`, `slug`, `description`, `is_system`, `created_at`, `updated_at`) VALUES ('6', '1', 'Stock Manager', 'stock_manager', 'Inventory control, purchases, stock movements, and stock adjustments', '0', '2026-09-03 00:55:24', '2026-09-03 00:55:24');

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('1', 'dashboard', 'dashboard.view', 'View Dashboard', 'Access dashboard overview and KPI statistics', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('2', 'sales', 'sales.create', 'Create Sales Memo', 'Create and finalize new sales memos', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('3', 'sales', 'sales.view', 'View Sales Memo', 'View memo history and details', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('4', 'sales', 'sales.edit', 'Edit Sales Memo', 'Modify sales memo draft/details', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('5', 'sales', 'sales.cancel', 'Cancel Sales Memo', 'Void/Cancel finalized sales memos', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('6', 'sales', 'sales.return', 'Process Sales Return', 'Accept customer product returns', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('7', 'purchases', 'purchase.create', 'Create Purchase Voucher', 'Create supplier procurement orders', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('8', 'purchases', 'purchase.view', 'View Purchases', 'View purchase history and vouchers', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('9', 'purchases', 'purchase.edit', 'Edit Purchases', 'Modify purchase voucher details', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('10', 'purchases', 'purchase.return', 'Process Purchase Return', 'Return materials back to supplier', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('11', 'inventory', 'inventory.view', 'View Inventory', 'View product list, stock levels, and valuation', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('12', 'inventory', 'inventory.manage', 'Manage Products', 'Create and edit products, categories, brands, units', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('13', 'inventory', 'inventory.adjust', 'Adjust Stock', 'Perform manual stock adjustments for damage/loss', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('14', 'customers', 'customer.view', 'View Customers', 'Access customer list and profiles', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('15', 'customers', 'customer.manage', 'Manage Customers', 'Create and edit customer records', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('16', 'customers', 'customer.payment', 'Customer Payments', 'Receive due payments from customers', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('17', 'customers', 'customer.advance', 'Customer Advance', 'Accept and manage advance deposits from customers', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('18', 'suppliers', 'supplier.view', 'View Suppliers', 'Access supplier directory and profiles', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('19', 'suppliers', 'supplier.manage', 'Manage Suppliers', 'Create and edit supplier records', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('20', 'suppliers', 'supplier.payment', 'Supplier Payments', 'Pay supplier dues and disbursements', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('21', 'finance', 'finance.view', 'View Finance', 'Access cash registers, bank accounts, and transactions', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('22', 'finance', 'finance.income', 'Manage Income', 'Record non-sales revenue', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('23', 'finance', 'finance.expense', 'Manage Expense', 'Record overhead and operational expenses', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('24', 'reports', 'reports.sales', 'Sales Reports', 'Access sales analytics and customer sales reports', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('25', 'reports', 'reports.purchase', 'Purchase Reports', 'Access procurement analytics and reports', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('26', 'reports', 'reports.stock', 'Stock Reports', 'Access inventory movement and valuation reports', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('27', 'reports', 'reports.due', 'Due & Advance Reports', 'Access customer and supplier due/advance reports', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('28', 'reports', 'reports.profit', 'Profit & Loss Reports', 'Access financial income statement and profit reports', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('29', 'calculators', 'calculators.use', 'Construction Calculators', 'Access civil engineering construction calculators', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('30', 'settings', 'settings.manage', 'Manage Settings', 'Configure shop preferences, print formats, sequences', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('31', 'users', 'users.manage', 'Manage Users & RBAC', 'Create users, assign roles and modify permissions', '2026-09-03 00:55:24');
INSERT INTO `permissions` (`id`, `module`, `slug`, `name`, `description`, `created_at`) VALUES ('32', 'backup', 'backup.manage', 'Backup & Data Export', 'Download SQL backups and export system data', '2026-09-03 00:55:24');

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '1');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '2');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '3');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '4');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '5');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '6');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '7');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '8');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '9');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '10');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '11');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '12');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '13');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '14');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '15');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '16');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '17');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '18');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '19');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '20');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '21');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '22');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '23');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '24');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '25');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '26');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '27');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '28');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '29');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '30');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '31');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('1', '32');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '1');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '2');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '3');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '4');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '5');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '6');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '7');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '8');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '9');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '10');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '11');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '12');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '13');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '14');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '15');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '16');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '17');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '18');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '19');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '20');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '21');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '22');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '23');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '24');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '25');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '26');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '27');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '28');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '29');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '30');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '31');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('2', '32');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('4', '1');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('4', '2');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('4', '3');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('4', '6');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('4', '11');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('4', '14');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('4', '15');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('4', '16');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('4', '17');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('4', '29');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '1');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '3');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '8');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '11');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '14');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '16');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '17');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '18');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '20');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '21');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '22');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '23');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '24');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '25');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '27');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '28');
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES ('5', '29');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `theme_preference` enum('light','dark','system') NOT NULL DEFAULT 'system',
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_users_shop` (`shop_id`),
  CONSTRAINT `fk_users_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `shop_id`, `username`, `email`, `password_hash`, `full_name`, `phone`, `status`, `theme_preference`, `last_login_at`, `last_login_ip`, `created_at`, `updated_at`) VALUES ('1', '1', 'admin', 'admin@maruftraders.com', '$2y$10$I62f1OxRiHWqgQnLhOHqIuw85rKo9ZiLirV3dO7QgW9MpM4EDvMme', 'System Administrator', '01711000000', 'active', 'system', '2026-09-03 01:28:15', '::1', '2026-09-03 00:55:24', '2026-09-03 01:28:15');
INSERT INTO `users` (`id`, `shop_id`, `username`, `email`, `password_hash`, `full_name`, `phone`, `status`, `theme_preference`, `last_login_at`, `last_login_ip`, `created_at`, `updated_at`) VALUES ('2', '1', 'salesman', 'sales@maruftraders.com', '$2y$10$I62f1OxRiHWqgQnLhOHqIuw85rKo9ZiLirV3dO7QgW9MpM4EDvMme', 'Karim Ullah (Sales)', '01722000000', 'active', 'light', NULL, NULL, '2026-09-03 00:55:24', '2026-09-03 00:57:43');
INSERT INTO `users` (`id`, `shop_id`, `username`, `email`, `password_hash`, `full_name`, `phone`, `status`, `theme_preference`, `last_login_at`, `last_login_ip`, `created_at`, `updated_at`) VALUES ('3', '1', 'accountant', 'accounts@maruftraders.com', '$2y$10$I62f1OxRiHWqgQnLhOHqIuw85rKo9ZiLirV3dO7QgW9MpM4EDvMme', 'Rahim Ahmed (Accounts)', '01733000000', 'active', 'dark', NULL, NULL, '2026-09-03 00:55:24', '2026-09-03 00:57:43');

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE `user_roles` (
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `fk_ur_role` (`role_id`),
  CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES ('1', '1');
INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES ('2', '4');
INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES ('3', '5');

DROP TABLE IF EXISTS `units`;
CREATE TABLE `units` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `is_base_unit` tinyint(1) NOT NULL DEFAULT 1,
  `decimal_places` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_shop_unit` (`shop_id`,`symbol`),
  CONSTRAINT `fk_units_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `units` (`id`, `shop_id`, `name`, `symbol`, `is_base_unit`, `decimal_places`, `created_at`, `updated_at`) VALUES ('1', '1', 'Bag', 'Bag', '1', '0', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `units` (`id`, `shop_id`, `name`, `symbol`, `is_base_unit`, `decimal_places`, `created_at`, `updated_at`) VALUES ('2', '1', 'Kilogram', 'KG', '1', '2', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `units` (`id`, `shop_id`, `name`, `symbol`, `is_base_unit`, `decimal_places`, `created_at`, `updated_at`) VALUES ('3', '1', 'Metric Ton', 'Ton', '0', '3', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `units` (`id`, `shop_id`, `name`, `symbol`, `is_base_unit`, `decimal_places`, `created_at`, `updated_at`) VALUES ('4', '1', 'Cubic Feet', 'CFT', '1', '2', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `units` (`id`, `shop_id`, `name`, `symbol`, `is_base_unit`, `decimal_places`, `created_at`, `updated_at`) VALUES ('5', '1', 'Piece', 'Piece', '1', '0', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `units` (`id`, `shop_id`, `name`, `symbol`, `is_base_unit`, `decimal_places`, `created_at`, `updated_at`) VALUES ('6', '1', 'Bundle', 'Bundle', '0', '0', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `units` (`id`, `shop_id`, `name`, `symbol`, `is_base_unit`, `decimal_places`, `created_at`, `updated_at`) VALUES ('7', '1', 'Box', 'Box', '1', '0', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `units` (`id`, `shop_id`, `name`, `symbol`, `is_base_unit`, `decimal_places`, `created_at`, `updated_at`) VALUES ('8', '1', 'Feet', 'Ft', '1', '2', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `units` (`id`, `shop_id`, `name`, `symbol`, `is_base_unit`, `decimal_places`, `created_at`, `updated_at`) VALUES ('9', '1', 'Meter', 'Mtr', '1', '2', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `units` (`id`, `shop_id`, `name`, `symbol`, `is_base_unit`, `decimal_places`, `created_at`, `updated_at`) VALUES ('10', '1', 'Liter', 'Ltr', '1', '2', '2026-09-03 00:55:24', '2026-09-03 00:55:24');

DROP TABLE IF EXISTS `unit_conversions`;
CREATE TABLE `unit_conversions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `from_unit_id` int(10) unsigned NOT NULL,
  `to_unit_id` int(10) unsigned NOT NULL,
  `conversion_factor` decimal(15,6) NOT NULL COMMENT 'Multiply from_unit qty by factor to get to_unit qty',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_conversion` (`shop_id`,`from_unit_id`,`to_unit_id`),
  KEY `fk_uc_from` (`from_unit_id`),
  KEY `fk_uc_to` (`to_unit_id`),
  CONSTRAINT `fk_uc_from` FOREIGN KEY (`from_unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uc_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uc_to` FOREIGN KEY (`to_unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `unit_conversions` (`id`, `shop_id`, `from_unit_id`, `to_unit_id`, `conversion_factor`, `created_at`) VALUES ('1', '1', '3', '2', '1000.000000', '2026-09-03 00:55:24');
INSERT INTO `unit_conversions` (`id`, `shop_id`, `from_unit_id`, `to_unit_id`, `conversion_factor`, `created_at`) VALUES ('2', '1', '2', '3', '0.001000', '2026-09-03 00:55:24');
INSERT INTO `unit_conversions` (`id`, `shop_id`, `from_unit_id`, `to_unit_id`, `conversion_factor`, `created_at`) VALUES ('3', '1', '6', '5', '100.000000', '2026-09-03 00:55:24');

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_shop_category` (`shop_id`,`name`),
  CONSTRAINT `fk_categories_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `shop_id`, `name`, `code`, `description`, `is_active`, `created_at`, `updated_at`) VALUES ('1', '1', 'Cement', 'CAT-CEM', 'Portland Composite and Pozzolana Cement bags', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `categories` (`id`, `shop_id`, `name`, `code`, `description`, `is_active`, `created_at`, `updated_at`) VALUES ('2', '1', 'Rod / Rebar (MS Deformed Bar)', 'CAT-ROD', '500W / 400D TMT Rebar steel rods of various diameters', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `categories` (`id`, `shop_id`, `name`, `code`, `description`, `is_active`, `created_at`, `updated_at`) VALUES ('3', '1', 'Sand (Balu)', 'CAT-SND', 'Sylhet Sand (Coarse), Local Sand, Filling Sand', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `categories` (`id`, `shop_id`, `name`, `code`, `description`, `is_active`, `created_at`, `updated_at`) VALUES ('4', '1', 'Bricks & Blocks', 'CAT-BRK', 'Auto Bricks, 1st Class Gas-Burned Bricks, Picket Bricks, Concrete Blocks', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `categories` (`id`, `shop_id`, `name`, `code`, `description`, `is_active`, `created_at`, `updated_at`) VALUES ('5', '1', 'Stone / Aggregate (Khoa)', 'CAT-STN', 'Bholaganj Stone Chips, Crushed Boulder Stone, Brick Khoa', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `categories` (`id`, `shop_id`, `name`, `code`, `description`, `is_active`, `created_at`, `updated_at`) VALUES ('6', '1', 'Binding Wire & Nails', 'CAT-WIR', 'GI Binding Wire, Steel Wire, Construction Wire Nails', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `categories` (`id`, `shop_id`, `name`, `code`, `description`, `is_active`, `created_at`, `updated_at`) VALUES ('7', '1', 'Pipes & Fittings', 'CAT-PIP', 'PVC Pipes, GI Pipes, CPVC Plumbing & Drainage Fittings', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `categories` (`id`, `shop_id`, `name`, `code`, `description`, `is_active`, `created_at`, `updated_at`) VALUES ('8', '1', 'Hardware & Tools', 'CAT-HDW', 'Trowels, Shovels, Headpans, Construction Safety & Tools', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `categories` (`id`, `shop_id`, `name`, `code`, `description`, `is_active`, `created_at`, `updated_at`) VALUES ('9', '1', 'Tar', 'tar45', '', '1', '2026-09-03 01:13:14', '2026-09-03 01:13:14');

DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `origin_country` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_shop_brand` (`shop_id`,`name`),
  CONSTRAINT `fk_brands_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `brands` (`id`, `shop_id`, `name`, `code`, `origin_country`, `is_active`, `created_at`, `updated_at`) VALUES ('1', '1', 'Shah Cement', 'BRD-SHAH', 'Bangladesh', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `brands` (`id`, `shop_id`, `name`, `code`, `origin_country`, `is_active`, `created_at`, `updated_at`) VALUES ('2', '1', 'Bashundhara Cement', 'BRD-BASH', 'Bangladesh', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `brands` (`id`, `shop_id`, `name`, `code`, `origin_country`, `is_active`, `created_at`, `updated_at`) VALUES ('3', '1', 'Fresh Cement', 'BRD-FRSH', 'Bangladesh', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `brands` (`id`, `shop_id`, `name`, `code`, `origin_country`, `is_active`, `created_at`, `updated_at`) VALUES ('4', '1', 'Crown Cement', 'BRD-CRWN', 'Bangladesh', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `brands` (`id`, `shop_id`, `name`, `code`, `origin_country`, `is_active`, `created_at`, `updated_at`) VALUES ('5', '1', 'Seven Rings Cement', 'BRD-7RNG', 'Bangladesh', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `brands` (`id`, `shop_id`, `name`, `code`, `origin_country`, `is_active`, `created_at`, `updated_at`) VALUES ('6', '1', 'BSRM Steels', 'BRD-BSRM', 'Bangladesh', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `brands` (`id`, `shop_id`, `name`, `code`, `origin_country`, `is_active`, `created_at`, `updated_at`) VALUES ('7', '1', 'AKS (Abul Khair Steel)', 'BRD-AKS', 'Bangladesh', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `brands` (`id`, `shop_id`, `name`, `code`, `origin_country`, `is_active`, `created_at`, `updated_at`) VALUES ('8', '1', 'KSRM Rebar', 'BRD-KSRM', 'Bangladesh', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `brands` (`id`, `shop_id`, `name`, `code`, `origin_country`, `is_active`, `created_at`, `updated_at`) VALUES ('9', '1', 'GPH Ispat', 'BRD-GPH', 'Bangladesh', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `brands` (`id`, `shop_id`, `name`, `code`, `origin_country`, `is_active`, `created_at`, `updated_at`) VALUES ('10', '1', 'Anwar Ispat', 'BRD-ANW', 'Bangladesh', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `brands` (`id`, `shop_id`, `name`, `code`, `origin_country`, `is_active`, `created_at`, `updated_at`) VALUES ('11', '1', 'Local / Natural Resource', 'BRD-LOC', 'Bangladesh', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `brands` (`id`, `shop_id`, `name`, `code`, `origin_country`, `is_active`, `created_at`, `updated_at`) VALUES ('12', '1', 'local', 'local45', 'Bangladesh', '1', '2026-09-03 01:13:43', '2026-09-03 01:13:43');

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `brand_id` int(10) unsigned DEFAULT NULL,
  `base_unit_id` int(10) unsigned NOT NULL,
  `sku` varchar(50) NOT NULL,
  `name` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `purchase_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `min_selling_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_stock` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `min_stock` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `average_cost` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_shop_sku` (`shop_id`,`sku`),
  KEY `idx_product_search` (`shop_id`,`is_active`,`name`),
  KEY `idx_product_stock` (`shop_id`,`current_stock`,`min_stock`),
  KEY `fk_products_category` (`category_id`),
  KEY `fk_products_brand` (`brand_id`),
  KEY `fk_products_unit` (`base_unit_id`),
  CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `fk_products_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_products_unit` FOREIGN KEY (`base_unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('1', '1', '1', '1', '1', 'CEM-SHAH-PCC', 'Shah Cement Special (PCC) 50kg', 'Portland Composite Cement 50kg Bag', '500.00', '540.00', '520.00', '500.0000', '50.0000', '500.0000', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('2', '1', '1', '2', '1', 'CEM-BASH-PCC', 'Bashundhara Cement (PCC) 50kg', 'High-strength cement for structural casting', '495.00', '535.00', '515.00', '400.0000', '40.0000', '495.0000', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('3', '1', '1', '3', '1', 'CEM-FRSH-PCC', 'Fresh Supermix Cement 50kg', 'Portland Composite Cement', '490.00', '530.00', '510.00', '349.0000', '35.0000', '490.0000', '1', '2026-09-03 00:55:24', '2026-09-03 01:48:17');
INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('4', '1', '1', '4', '1', 'CEM-CRWN-PCC', 'Crown Cement PCC 50kg', 'Premium quality composite cement', '495.00', '535.00', '515.00', '299.0000', '30.0000', '495.0000', '1', '2026-09-03 00:55:24', '2026-09-03 01:48:17');
INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('5', '1', '2', '6', '2', 'ROD-BSRM-500W-16MM', 'BSRM Ultima 500W (16mm / 5 Suta)', 'High grade 500W TMT steel rebar per KG', '92.00', '97.00', '95.00', '10000.0000', '1000.0000', '92.0000', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('6', '1', '2', '6', '2', 'ROD-BSRM-500W-12MM', 'BSRM Ultima 500W (12mm / 4 Suta)', 'High grade 500W TMT steel rebar per KG', '92.00', '97.00', '95.00', '7999.0000', '1000.0000', '92.0000', '1', '2026-09-03 00:55:24', '2026-09-03 01:48:17');
INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('7', '1', '2', '6', '2', 'ROD-BSRM-500W-10MM', 'BSRM Ultima 500W (10mm / 3 Suta)', 'High grade 500W TMT steel rebar per KG', '93.00', '98.00', '96.00', '6999.0000', '1000.0000', '93.0000', '1', '2026-09-03 00:55:24', '2026-09-03 01:48:17');
INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('8', '1', '2', '7', '2', 'ROD-AKS-500W-16MM', 'AKS 500W TMT Rebar (16mm)', 'High yield strength thermo-mechanically treated bar', '90.00', '95.00', '93.00', '8100.0000', '1000.0000', '90.0000', '1', '2026-09-03 00:55:24', '2026-09-03 01:47:16');
INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('9', '1', '2', '7', '2', 'ROD-AKS-500W-12MM', 'AKS 500W TMT Rebar (12mm)', 'High yield strength thermo-mechanically treated bar', '90.00', '95.00', '93.00', '8488.0000', '1000.0000', '90.0000', '1', '2026-09-03 00:55:24', '2026-09-03 01:48:17');
INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('10', '1', '3', '11', '4', 'SND-SYL-COARSE', 'Sylhet Coarse Sand (2.5 FM)', 'High FM coarse red sand for RCC casting per CFT', '42.00', '52.00', '48.00', '4999.0000', '500.0000', '42.0000', '1', '2026-09-03 00:55:24', '2026-09-03 01:48:17');
INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('11', '1', '3', '11', '4', 'SND-LOC-MED', 'Local Medium Sand (1.5 FM)', 'Medium sand for plastering and brick masonry per CFT', '28.00', '38.00', '34.00', '6000.0000', '500.0000', '28.0000', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('12', '1', '4', '11', '5', 'BRK-1ST-AUTO', '1st Class Gas Auto Bricks (Standard)', 'Machine-pressed uniform red clay bricks', '11.50', '13.50', '12.80', '39985.0000', '5000.0000', '11.5000', '1', '2026-09-03 00:55:24', '2026-09-03 01:18:26');
INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('13', '1', '4', '11', '5', 'BRK-PCK-BRK', 'Picket Bricks (Hard Clay)', 'Heavy burnt picket bricks for stone-equivalent chips', '12.00', '14.00', '13.20', '25000.0000', '3000.0000', '12.0000', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('14', '1', '5', '11', '4', 'STN-BHL-3_4', 'Bholaganj Black Stone Chips 3/4 Inch', 'Crushed stone aggregate for heavy load RCC casting', '140.00', '165.00', '155.00', '4000.0000', '400.0000', '140.0000', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('15', '1', '6', '11', '2', 'WIR-GI-BIND-20G', 'GI Binding Wire (20 Gauge)', 'Soft galvanized binding wire for rebar tying per KG', '110.00', '135.00', '125.00', '1499.0000', '100.0000', '110.0000', '1', '2026-09-03 00:55:24', '2026-09-03 01:48:17');
INSERT INTO `products` (`id`, `shop_id`, `category_id`, `brand_id`, `base_unit_id`, `sku`, `name`, `description`, `purchase_price`, `selling_price`, `min_selling_price`, `current_stock`, `min_stock`, `average_cost`, `is_active`, `created_at`, `updated_at`) VALUES ('16', '1', '8', '9', '1', 'jaro11', 'jaro', NULL, '70.00', '80.00', '78.00', '10.0000', '100.0000', '70.0000', '1', '2026-09-03 01:12:22', '2026-09-03 01:12:22');

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `customer_code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `mobile` varchar(30) NOT NULL,
  `alt_mobile` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `customer_type` enum('retail','contractor','dealer','builder','other') NOT NULL DEFAULT 'retail',
  `credit_limit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `opening_due` decimal(15,2) NOT NULL DEFAULT 0.00,
  `opening_advance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_due` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_advance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_shop_cust_code` (`shop_id`,`customer_code`),
  KEY `idx_customer_search` (`shop_id`,`mobile`,`name`),
  CONSTRAINT `fk_customers_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customers` (`id`, `shop_id`, `customer_code`, `name`, `mobile`, `alt_mobile`, `address`, `customer_type`, `credit_limit`, `opening_due`, `opening_advance`, `current_due`, `current_advance`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('1', '1', 'CUST-001', 'Abdul Karim & Sons', '01711111111', NULL, 'Hospital Road, Habiganj Sadar', 'contractor', '500000.00', '85000.00', '0.00', '85000.00', '0.00', 'active', 'Residential building contractor in Sadar', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `customers` (`id`, `shop_id`, `customer_code`, `name`, `mobile`, `alt_mobile`, `address`, `customer_type`, `credit_limit`, `opening_due`, `opening_advance`, `current_due`, `current_advance`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('2', '1', 'CUST-002', 'Rahim Construction Ltd', '01822222222', NULL, 'Court Station Road, Habiganj', 'builder', '1000000.00', '0.00', '50000.00', '0.00', '50000.00', 'active', 'Multi-storey commercial builder', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `customers` (`id`, `shop_id`, `customer_code`, `name`, `mobile`, `alt_mobile`, `address`, `customer_type`, `credit_limit`, `opening_due`, `opening_advance`, `current_due`, `current_advance`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('3', '1', 'CUST-003', 'Engr. Mahbubur Rahman', '01933333333', NULL, 'Shaestanagar, Habiganj', 'retail', '100000.00', '12500.00', '0.00', '12500.00', '0.00', 'active', 'Personal home construction', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `customers` (`id`, `shop_id`, `customer_code`, `name`, `mobile`, `alt_mobile`, `address`, `customer_type`, `credit_limit`, `opening_due`, `opening_advance`, `current_due`, `current_advance`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('4', '1', 'CUST-004', 'Al-Amin Developers', '01744444444', NULL, 'Madhabpur Highway, Habiganj', 'dealer', '750000.00', '0.00', '0.00', '76950.00', '0.00', 'active', 'Sub-dealer and local supplier', '2026-09-03 00:55:24', '2026-09-03 01:47:16');
INSERT INTO `customers` (`id`, `shop_id`, `customer_code`, `name`, `mobile`, `alt_mobile`, `address`, `customer_type`, `credit_limit`, `opening_due`, `opening_advance`, `current_due`, `current_advance`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('5', '1', 'CUST-005', 'Haji Nurul Islam', '01855555555', NULL, 'Nabiganj Bazaar, Habiganj', 'retail', '50000.00', '0.00', '20000.00', '0.00', '18458.00', 'active', 'Advance deposited for cement & rod', '2026-09-03 00:55:24', '2026-09-03 01:48:17');
INSERT INTO `customers` (`id`, `shop_id`, `customer_code`, `name`, `mobile`, `alt_mobile`, `address`, `customer_type`, `credit_limit`, `opening_due`, `opening_advance`, `current_due`, `current_advance`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('6', '1', 'CUST-0006', 'Rabbi', '456456', NULL, 'ggdf', 'retail', '0.00', '0.00', '0.00', '1125.50', '0.00', 'active', '', '2026-09-03 01:07:45', '2026-09-03 01:28:51');

DROP TABLE IF EXISTS `customer_ledger`;
CREATE TABLE `customer_ledger` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` enum('OPENING_BALANCE','SALE','PAYMENT','SALE_RETURN','ADVANCE_ADJUSTMENT','DISCOUNT_ADJUSTMENT','DUE_CORRECTION') NOT NULL,
  `reference_type` varchar(50) NOT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `reference_no` varchar(60) NOT NULL,
  `description` varchar(255) NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Receivable increase (Sales, Debit Notes)',
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Receivable decrease (Payments, Returns, Advances Applied)',
  `running_balance` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Cumulative Current Due',
  `user_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cust_ledger_date` (`shop_id`,`customer_id`,`transaction_date`),
  KEY `fk_cledger_cust` (`customer_id`),
  KEY `fk_cledger_user` (`user_id`),
  CONSTRAINT `fk_cledger_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_cledger_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cledger_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customer_ledger` (`id`, `shop_id`, `customer_id`, `transaction_date`, `transaction_type`, `reference_type`, `reference_id`, `reference_no`, `description`, `debit`, `credit`, `running_balance`, `user_id`, `created_at`) VALUES ('1', '1', '1', '2026-01-01', 'OPENING_BALANCE', 'OPENING', '1', 'OP-BAL-001', 'Opening receivable balance migrated from previous ledger', '85000.00', '0.00', '85000.00', '1', '2026-09-03 00:55:24');
INSERT INTO `customer_ledger` (`id`, `shop_id`, `customer_id`, `transaction_date`, `transaction_type`, `reference_type`, `reference_id`, `reference_no`, `description`, `debit`, `credit`, `running_balance`, `user_id`, `created_at`) VALUES ('2', '1', '3', '2026-01-01', 'OPENING_BALANCE', 'OPENING', '3', 'OP-BAL-003', 'Opening receivable balance migrated from previous ledger', '12500.00', '0.00', '12500.00', '1', '2026-09-03 00:55:24');
INSERT INTO `customer_ledger` (`id`, `shop_id`, `customer_id`, `transaction_date`, `transaction_type`, `reference_type`, `reference_id`, `reference_no`, `description`, `debit`, `credit`, `running_balance`, `user_id`, `created_at`) VALUES ('9', '1', '6', '2026-09-03', 'SALE', 'SALE', '4', 'MT-2026-000001', 'Sales Memo [MT-2026-000001]', '202.50', '0.00', '202.50', '1', '2026-09-03 01:18:26');
INSERT INTO `customer_ledger` (`id`, `shop_id`, `customer_id`, `transaction_date`, `transaction_type`, `reference_type`, `reference_id`, `reference_no`, `description`, `debit`, `credit`, `running_balance`, `user_id`, `created_at`) VALUES ('10', '1', '6', '2026-09-03', 'SALE', 'SALE', '5', 'MT-2026-000002', 'Sales Memo [MT-2026-000002]', '923.00', '0.00', '1125.50', '1', '2026-09-03 01:28:51');
INSERT INTO `customer_ledger` (`id`, `shop_id`, `customer_id`, `transaction_date`, `transaction_type`, `reference_type`, `reference_id`, `reference_no`, `description`, `debit`, `credit`, `running_balance`, `user_id`, `created_at`) VALUES ('11', '1', '4', '2026-09-03', 'SALE', 'SALE', '6', 'MT-2026-000003', 'Sales Memo [MT-2026-000003]', '76950.00', '0.00', '76950.00', '1', '2026-09-03 01:47:16');
INSERT INTO `customer_ledger` (`id`, `shop_id`, `customer_id`, `transaction_date`, `transaction_type`, `reference_type`, `reference_id`, `reference_no`, `description`, `debit`, `credit`, `running_balance`, `user_id`, `created_at`) VALUES ('12', '1', '5', '2026-09-03', 'SALE', 'SALE', '7', 'MT-2026-000004', 'Sales Memo [MT-2026-000004]', '1542.00', '0.00', '1542.00', '1', '2026-09-03 01:48:17');
INSERT INTO `customer_ledger` (`id`, `shop_id`, `customer_id`, `transaction_date`, `transaction_type`, `reference_type`, `reference_id`, `reference_no`, `description`, `debit`, `credit`, `running_balance`, `user_id`, `created_at`) VALUES ('13', '1', '5', '2026-09-03', 'ADVANCE_ADJUSTMENT', 'SALE_ADVANCE', '7', 'MT-2026-000004', 'Advance balance adjusted against memo [MT-2026-000004]', '0.00', '1542.00', '0.00', '1', '2026-09-03 01:48:17');

DROP TABLE IF EXISTS `customer_advances`;
CREATE TABLE `customer_advances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `advance_no` varchar(60) NOT NULL,
  `receipt_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `adjusted_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `refunded_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `remaining_balance` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `account_type` enum('cash','bank') NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `status` enum('active','fully_adjusted','refunded','cancelled') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `advance_no` (`advance_no`),
  KEY `idx_adv_cust` (`shop_id`,`customer_id`,`status`),
  KEY `fk_cadv_cust` (`customer_id`),
  KEY `fk_cadv_user` (`created_by`),
  CONSTRAINT `fk_cadv_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_cadv_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cadv_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customer_advances` (`id`, `shop_id`, `customer_id`, `advance_no`, `receipt_date`, `amount`, `adjusted_amount`, `refunded_amount`, `remaining_balance`, `payment_method`, `account_type`, `account_id`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES ('1', '1', '2', 'ADV-2026-000001', '2026-01-01', '50000.00', '0.00', '0.00', '50000.00', 'Cash', 'cash', '1', 'active', 'Opening advance deposit', '1', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `customer_advances` (`id`, `shop_id`, `customer_id`, `advance_no`, `receipt_date`, `amount`, `adjusted_amount`, `refunded_amount`, `remaining_balance`, `payment_method`, `account_type`, `account_id`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES ('2', '1', '5', 'ADV-2026-000002', '2026-01-01', '20000.00', '1542.00', '0.00', '18458.00', 'bKash', 'bank', '2', 'active', 'Advance received via bKash Merchant', '1', '2026-09-03 00:55:24', '2026-09-03 01:48:17');

DROP TABLE IF EXISTS `customer_advance_adjustments`;
CREATE TABLE `customer_advance_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `advance_id` bigint(20) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `adjustment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_caa_shop` (`shop_id`),
  KEY `fk_caa_adv` (`advance_id`),
  KEY `fk_caa_cust` (`customer_id`),
  KEY `fk_caa_user` (`created_by`),
  CONSTRAINT `fk_caa_adv` FOREIGN KEY (`advance_id`) REFERENCES `customer_advances` (`id`),
  CONSTRAINT `fk_caa_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_caa_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_caa_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customer_advance_adjustments` (`id`, `shop_id`, `advance_id`, `customer_id`, `sale_id`, `adjustment_date`, `amount`, `created_by`, `created_at`) VALUES ('1', '1', '2', '5', '7', '2026-09-03', '1542.00', '1', '2026-09-03 01:48:17');

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `supplier_code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `mobile` varchar(30) NOT NULL,
  `address` text DEFAULT NULL,
  `opening_due` decimal(15,2) NOT NULL DEFAULT 0.00,
  `opening_advance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_due` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_advance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_shop_supp_code` (`shop_id`,`supplier_code`),
  KEY `idx_supplier_search` (`shop_id`,`mobile`,`name`),
  CONSTRAINT `fk_suppliers_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `suppliers` (`id`, `shop_id`, `supplier_code`, `name`, `company_name`, `mobile`, `address`, `opening_due`, `opening_advance`, `current_due`, `current_advance`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('1', '1', 'SUPP-001', 'Shah Cement Regional Agency', 'Shah Cement Industries Ltd', '01712345678', 'South Surma Depot, Sylhet', '250000.00', '0.00', '250000.00', '0.00', 'active', 'Main distributor for Shah Cement', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `suppliers` (`id`, `shop_id`, `supplier_code`, `name`, `company_name`, `mobile`, `address`, `opening_due`, `opening_advance`, `current_due`, `current_advance`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('2', '1', 'SUPP-002', 'BSRM Sylhet Regional Depot', 'BSRM Steels Limited', '01812345678', 'Kadamtoli Steel Market, Sylhet', '450000.00', '0.00', '450000.00', '0.00', 'active', 'Primary TMT rebar source', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `suppliers` (`id`, `shop_id`, `supplier_code`, `name`, `company_name`, `mobile`, `address`, `opening_due`, `opening_advance`, `current_due`, `current_advance`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('3', '1', 'SUPP-003', 'Surma Auto Bricks Syndicate', 'Surma Auto Bricks Industries', '01912345678', 'Bahubal, Habiganj', '80000.00', '0.00', '80000.00', '0.00', 'active', 'Direct brick kiln supplier', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `suppliers` (`id`, `shop_id`, `supplier_code`, `name`, `company_name`, `mobile`, `address`, `opening_due`, `opening_advance`, `current_due`, `current_advance`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('4', '1', 'SUPP-004', 'Jaflong Stone Crushers Hub', 'M/S Jaflong Stone Traders', '01798765432', 'Jaflong, Sylhet', '0.00', '30000.00', '0.00', '30000.00', 'active', 'Advance paid for stone quarry load', '2026-09-03 00:55:24', '2026-09-03 00:55:24');

DROP TABLE IF EXISTS `supplier_ledger`;
CREATE TABLE `supplier_ledger` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` enum('OPENING_BALANCE','PURCHASE','PAYMENT','PURCHASE_RETURN','ADVANCE_PAYMENT','DUE_CORRECTION') NOT NULL,
  `reference_type` varchar(50) NOT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `reference_no` varchar(60) NOT NULL,
  `description` varchar(255) NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Payable decrease (Payments, Returns)',
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Payable increase (Purchases)',
  `running_balance` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Cumulative Current Due',
  `user_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_supp_ledger_date` (`shop_id`,`supplier_id`,`transaction_date`),
  KEY `fk_sledger_supp` (`supplier_id`),
  KEY `fk_sledger_user` (`user_id`),
  CONSTRAINT `fk_sledger_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sledger_supp` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `fk_sledger_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `supplier_ledger` (`id`, `shop_id`, `supplier_id`, `transaction_date`, `transaction_type`, `reference_type`, `reference_id`, `reference_no`, `description`, `debit`, `credit`, `running_balance`, `user_id`, `created_at`) VALUES ('1', '1', '1', '2026-01-01', 'OPENING_BALANCE', 'OPENING', '1', 'SUPP-OP-001', 'Opening payable to Shah Cement distributor', '0.00', '250000.00', '250000.00', '1', '2026-09-03 00:55:24');
INSERT INTO `supplier_ledger` (`id`, `shop_id`, `supplier_id`, `transaction_date`, `transaction_type`, `reference_type`, `reference_id`, `reference_no`, `description`, `debit`, `credit`, `running_balance`, `user_id`, `created_at`) VALUES ('2', '1', '2', '2026-01-01', 'OPENING_BALANCE', 'OPENING', '2', 'SUPP-OP-002', 'Opening payable to BSRM Steels depot', '0.00', '450000.00', '450000.00', '1', '2026-09-03 00:55:24');
INSERT INTO `supplier_ledger` (`id`, `shop_id`, `supplier_id`, `transaction_date`, `transaction_type`, `reference_type`, `reference_id`, `reference_no`, `description`, `debit`, `credit`, `running_balance`, `user_id`, `created_at`) VALUES ('3', '1', '3', '2026-01-01', 'OPENING_BALANCE', 'OPENING', '3', 'SUPP-OP-003', 'Opening payable for brick loads', '0.00', '80000.00', '80000.00', '1', '2026-09-03 00:55:24');

DROP TABLE IF EXISTS `supplier_advances`;
CREATE TABLE `supplier_advances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `advance_no` varchar(60) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `adjusted_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `refunded_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `remaining_balance` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `account_type` enum('cash','bank') NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `status` enum('active','fully_adjusted','refunded','cancelled') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `advance_no` (`advance_no`),
  KEY `fk_sadv_shop` (`shop_id`),
  KEY `fk_sadv_supp` (`supplier_id`),
  KEY `fk_sadv_user` (`created_by`),
  CONSTRAINT `fk_sadv_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sadv_supp` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `fk_sadv_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cash_accounts`;
CREATE TABLE `cash_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_default` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_cash_shop` (`shop_id`),
  CONSTRAINT `fk_cash_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cash_accounts` (`id`, `shop_id`, `account_name`, `opening_balance`, `current_balance`, `is_default`, `status`, `created_at`, `updated_at`) VALUES ('1', '1', 'Main Cash Drawer (দোকানের মূল ক্যাশ)', '150000.00', '150000.00', '1', 'active', '2026-09-03 00:55:24', '2026-09-03 00:55:24');

DROP TABLE IF EXISTS `bank_accounts`;
CREATE TABLE `bank_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `account_title` varchar(150) NOT NULL,
  `account_type` enum('bank','bkash','nagad','rocket','other') NOT NULL DEFAULT 'bank',
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(60) NOT NULL,
  `branch_name` varchar(100) DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_bank_shop` (`shop_id`),
  CONSTRAINT `fk_bank_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bank_accounts` (`id`, `shop_id`, `account_title`, `account_type`, `bank_name`, `account_number`, `branch_name`, `opening_balance`, `current_balance`, `status`, `created_at`, `updated_at`) VALUES ('1', '1', 'Maruf Traders - Islami Bank CD Account', 'bank', 'Islami Bank Bangladesh PLC', '2050123456789012', 'Habiganj Branch', '350000.00', '350000.00', 'active', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `bank_accounts` (`id`, `shop_id`, `account_title`, `account_type`, `bank_name`, `account_number`, `branch_name`, `opening_balance`, `current_balance`, `status`, `created_at`, `updated_at`) VALUES ('2', '1', 'Maruf Traders - bKash Merchant Wallet', 'bkash', 'bKash Merchant', '01711000000', 'Digital Wallet', '45000.00', '45000.00', 'active', '2026-09-03 00:55:24', '2026-09-03 00:55:24');
INSERT INTO `bank_accounts` (`id`, `shop_id`, `account_title`, `account_type`, `bank_name`, `account_number`, `branch_name`, `opening_balance`, `current_balance`, `status`, `created_at`, `updated_at`) VALUES ('3', '1', 'Maruf Traders - Nagad Merchant Wallet', 'nagad', 'Nagad Merchant', '01811000000', 'Digital Wallet', '25000.00', '25000.00', 'active', '2026-09-03 00:55:24', '2026-09-03 00:55:24');

DROP TABLE IF EXISTS `financial_transactions`;
CREATE TABLE `financial_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `transaction_no` varchar(60) NOT NULL,
  `transaction_date` date NOT NULL,
  `account_type` enum('cash','bank') NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `transaction_nature` enum('INFLOW','OUTFLOW') NOT NULL,
  `category` enum('SALE_PAYMENT','CUSTOMER_COLLECTION','CUSTOMER_ADVANCE','PURCHASE_PAYMENT','SUPPLIER_PAYMENT','EXPENSE','INCOME','BANK_DEPOSIT','BANK_WITHDRAWAL','ACCOUNT_TRANSFER','OPENING_BALANCE') NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `reference_no` varchar(60) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_no` (`transaction_no`),
  KEY `idx_fin_date` (`shop_id`,`transaction_date`),
  KEY `idx_fin_acc` (`shop_id`,`account_type`,`account_id`),
  KEY `fk_fintrans_user` (`created_by`),
  CONSTRAINT `fk_fintrans_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fintrans_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `financial_transactions` (`id`, `shop_id`, `transaction_no`, `transaction_date`, `account_type`, `account_id`, `transaction_nature`, `category`, `reference_type`, `reference_id`, `reference_no`, `amount`, `balance_after`, `notes`, `created_by`, `created_at`) VALUES ('1', '1', 'TXN-2026-000001', '2026-01-01', 'cash', '1', 'INFLOW', 'OPENING_BALANCE', 'OPENING', '1', 'OP-CASH-01', '150000.00', '150000.00', 'Shop physical cash opening balance', '1', '2026-09-03 00:55:24');
INSERT INTO `financial_transactions` (`id`, `shop_id`, `transaction_no`, `transaction_date`, `account_type`, `account_id`, `transaction_nature`, `category`, `reference_type`, `reference_id`, `reference_no`, `amount`, `balance_after`, `notes`, `created_by`, `created_at`) VALUES ('2', '1', 'TXN-2026-000002', '2026-01-01', 'bank', '1', 'INFLOW', 'OPENING_BALANCE', 'OPENING', '1', 'OP-BANK-01', '350000.00', '350000.00', 'Islami Bank opening balance', '1', '2026-09-03 00:55:24');
INSERT INTO `financial_transactions` (`id`, `shop_id`, `transaction_no`, `transaction_date`, `account_type`, `account_id`, `transaction_nature`, `category`, `reference_type`, `reference_id`, `reference_no`, `amount`, `balance_after`, `notes`, `created_by`, `created_at`) VALUES ('3', '1', 'TXN-2026-000003', '2026-01-01', 'bank', '2', 'INFLOW', 'OPENING_BALANCE', 'OPENING', '2', 'OP-BKASH-01', '45000.00', '45000.00', 'bKash merchant wallet balance', '1', '2026-09-03 00:55:24');
INSERT INTO `financial_transactions` (`id`, `shop_id`, `transaction_no`, `transaction_date`, `account_type`, `account_id`, `transaction_nature`, `category`, `reference_type`, `reference_id`, `reference_no`, `amount`, `balance_after`, `notes`, `created_by`, `created_at`) VALUES ('4', '1', 'TXN-2026-000004', '2026-01-01', 'bank', '3', 'INFLOW', 'OPENING_BALANCE', 'OPENING', '3', 'OP-NAGAD-01', '25000.00', '25000.00', 'Nagad merchant wallet balance', '1', '2026-09-03 00:55:24');

DROP TABLE IF EXISTS `expense_categories`;
CREATE TABLE `expense_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_expcat_shop` (`shop_id`),
  CONSTRAINT `fk_expcat_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `expense_categories` (`id`, `shop_id`, `name`, `description`, `is_active`, `created_at`) VALUES ('1', '1', 'Shop Rent (দোকান ও গুদাম ভাড়া)', 'Monthly rent for shop and warehouse premises', '1', '2026-09-03 00:55:24');
INSERT INTO `expense_categories` (`id`, `shop_id`, `name`, `description`, `is_active`, `created_at`) VALUES ('2', '1', 'Staff Salaries & Allowances (কর্মচারীদের বেতন)', 'Monthly wages and allowances for sales and delivery staff', '1', '2026-09-03 00:55:24');
INSERT INTO `expense_categories` (`id`, `shop_id`, `name`, `description`, `is_active`, `created_at`) VALUES ('3', '1', 'Electricity & Utilities (বিদ্যুৎ ও ইউটিলিটি বিল)', 'Electricity, water, and municipal utility bills', '1', '2026-09-03 00:55:24');
INSERT INTO `expense_categories` (`id`, `shop_id`, `name`, `description`, `is_active`, `created_at`) VALUES ('4', '1', 'Transport & Freight (মালামাল পরিবহন ভাড়া)', 'Truck, pickup, and trolley transport costs', '1', '2026-09-03 00:55:24');
INSERT INTO `expense_categories` (`id`, `shop_id`, `name`, `description`, `is_active`, `created_at`) VALUES ('5', '1', 'Loading & Unloading Labor (লেবার/কুলি খরচ)', 'Labor costs for loading/unloading rod, cement, sand, and bricks', '1', '2026-09-03 00:55:24');
INSERT INTO `expense_categories` (`id`, `shop_id`, `name`, `description`, `is_active`, `created_at`) VALUES ('6', '1', 'Shop Maintenance & Repairs (মেরামত ও রক্ষণাবেক্ষণ)', 'Hardware, electrical, and structural upkeep of shop', '1', '2026-09-03 00:55:24');
INSERT INTO `expense_categories` (`id`, `shop_id`, `name`, `description`, `is_active`, `created_at`) VALUES ('7', '1', 'Entertainment & Tea (চা-নাস্তা ও আপ্যায়ন)', 'Customer and official visitor entertainment expenses', '1', '2026-09-03 00:55:24');
INSERT INTO `expense_categories` (`id`, `shop_id`, `name`, `description`, `is_active`, `created_at`) VALUES ('8', '1', 'Internet & Mobile Bills (ইন্টারনেট ও ফোন বিল)', 'Broadband internet and company phone recharge', '1', '2026-09-03 00:55:24');
INSERT INTO `expense_categories` (`id`, `shop_id`, `name`, `description`, `is_active`, `created_at`) VALUES ('9', '1', 'Other Miscellaneous Expense (অন্যান্য বিবিধ খরচ)', 'Unforeseen operational overheads', '1', '2026-09-03 00:55:24');

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `expense_no` varchar(60) NOT NULL,
  `expense_date` date NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `account_type` enum('cash','bank') NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `payee_name` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_no` (`expense_no`),
  KEY `idx_expense_date` (`shop_id`,`expense_date`),
  KEY `fk_exp_cat` (`category_id`),
  KEY `fk_exp_user` (`created_by`),
  CONSTRAINT `fk_exp_cat` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`),
  CONSTRAINT `fk_exp_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_exp_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `income_categories`;
CREATE TABLE `income_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_inccat_shop` (`shop_id`),
  CONSTRAINT `fk_inccat_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `income_categories` (`id`, `shop_id`, `name`, `description`, `is_active`, `created_at`) VALUES ('1', '1', 'Cement Bag Empty Sack Sales (খালি বস্তা বিক্রি)', 'Revenue from selling empty cement paper/poly sacks', '1', '2026-09-03 00:55:24');
INSERT INTO `income_categories` (`id`, `shop_id`, `name`, `description`, `is_active`, `created_at`) VALUES ('2', '1', 'Transportation Commission / Markup (পরিবহন কমিশন)', 'Commission earned from arranged customer transports', '1', '2026-09-03 00:55:24');
INSERT INTO `income_categories` (`id`, `shop_id`, `name`, `description`, `is_active`, `created_at`) VALUES ('3', '1', 'Scrap & Steel Waste Sales (স্ক্র্যাপ ও ওয়েস্টেজ বিক্রি)', 'Sale of cut rod remnants and metal scrap', '1', '2026-09-03 00:55:24');
INSERT INTO `income_categories` (`id`, `shop_id`, `name`, `description`, `is_active`, `created_at`) VALUES ('4', '1', 'Other Operating Income (অন্যান্য আয়)', 'Miscellaneous non-sales revenue', '1', '2026-09-03 00:55:24');

DROP TABLE IF EXISTS `incomes`;
CREATE TABLE `incomes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `income_no` varchar(60) NOT NULL,
  `income_date` date NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `account_type` enum('cash','bank') NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `payer_name` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `income_no` (`income_no`),
  KEY `idx_income_date` (`shop_id`,`income_date`),
  KEY `fk_inc_cat` (`category_id`),
  KEY `fk_inc_user` (`created_by`),
  CONSTRAINT `fk_inc_cat` FOREIGN KEY (`category_id`) REFERENCES `income_categories` (`id`),
  CONSTRAINT `fk_inc_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inc_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `memo_number` varchar(60) NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `sale_date` date NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `discount_type` enum('fixed','percentage') NOT NULL DEFAULT 'fixed',
  `discount_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `advance_used` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_cogs` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Snapshot of total Cost of Goods Sold',
  `gross_profit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('paid','partial','due','advance_settled') NOT NULL,
  `sale_status` enum('completed','cancelled','returned','partial_return') NOT NULL DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `memo_number` (`memo_number`),
  KEY `idx_sales_date` (`shop_id`,`sale_date`),
  KEY `idx_sales_cust` (`shop_id`,`customer_id`),
  KEY `fk_sales_cust` (`customer_id`),
  KEY `fk_sales_user` (`created_by`),
  CONSTRAINT `fk_sales_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_sales_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sales_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sales` (`id`, `shop_id`, `memo_number`, `customer_id`, `sale_date`, `subtotal`, `discount_type`, `discount_value`, `discount_amount`, `grand_total`, `paid_amount`, `advance_used`, `due_amount`, `total_cogs`, `gross_profit`, `payment_status`, `sale_status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES ('4', '1', 'MT-2026-000001', '6', '2026-09-03', '202.50', 'fixed', '0.00', '0.00', '202.50', '0.00', '0.00', '202.50', '172.50', '30.00', 'due', 'completed', NULL, '1', '2026-09-03 01:18:26', '2026-09-03 01:18:26');
INSERT INTO `sales` (`id`, `shop_id`, `memo_number`, `customer_id`, `sale_date`, `subtotal`, `discount_type`, `discount_value`, `discount_amount`, `grand_total`, `paid_amount`, `advance_used`, `due_amount`, `total_cogs`, `gross_profit`, `payment_status`, `sale_status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES ('5', '1', 'MT-2026-000002', '6', '2026-09-03', '1023.00', 'fixed', '100.00', '100.00', '923.00', '0.00', '0.00', '923.00', '990.00', '-67.00', 'due', 'completed', NULL, '1', '2026-09-03 01:28:51', '2026-09-03 01:28:51');
INSERT INTO `sales` (`id`, `shop_id`, `memo_number`, `customer_id`, `sale_date`, `subtotal`, `discount_type`, `discount_value`, `discount_amount`, `grand_total`, `paid_amount`, `advance_used`, `due_amount`, `total_cogs`, `gross_profit`, `payment_status`, `sale_status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES ('6', '1', 'MT-2026-000003', '4', '2026-09-03', '85500.00', 'percentage', '10.00', '8550.00', '76950.00', '0.00', '0.00', '76950.00', '81000.00', '-4050.00', 'due', 'completed', NULL, '1', '2026-09-03 01:47:16', '2026-09-03 01:47:16');
INSERT INTO `sales` (`id`, `shop_id`, `memo_number`, `customer_id`, `sale_date`, `subtotal`, `discount_type`, `discount_value`, `discount_amount`, `grand_total`, `paid_amount`, `advance_used`, `due_amount`, `total_cogs`, `gross_profit`, `payment_status`, `sale_status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES ('7', '1', 'MT-2026-000004', '5', '2026-09-03', '1542.00', 'fixed', '0.00', '0.00', '1542.00', '0.00', '1542.00', '0.00', '1412.00', '130.00', 'advance_settled', 'completed', NULL, '1', '2026-09-03 01:48:17', '2026-09-03 01:48:17');

DROP TABLE IF EXISTS `sale_items`;
CREATE TABLE `sale_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `shop_id` int(10) unsigned NOT NULL,
  `item_order` int(10) unsigned NOT NULL DEFAULT 1,
  `product_id` int(10) unsigned NOT NULL,
  `unit_id` int(10) unsigned NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `base_quantity` decimal(15,4) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `unit_cost` decimal(15,4) NOT NULL COMMENT 'Cost snapshot at the time of sale for accurate COGS',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL,
  `cogs_total` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_si_sale` (`sale_id`),
  KEY `idx_si_prod` (`product_id`),
  KEY `fk_si_unit` (`unit_id`),
  CONSTRAINT `fk_si_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_si_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_si_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sale_items` (`id`, `sale_id`, `shop_id`, `item_order`, `product_id`, `unit_id`, `quantity`, `base_quantity`, `unit_price`, `unit_cost`, `discount_amount`, `line_total`, `cogs_total`, `created_at`) VALUES ('4', '4', '1', '1', '12', '5', '15.0000', '15.0000', '13.50', '11.5000', '0.00', '202.50', '172.50', '2026-09-03 01:18:26');
INSERT INTO `sale_items` (`id`, `sale_id`, `shop_id`, `item_order`, `product_id`, `unit_id`, `quantity`, `base_quantity`, `unit_price`, `unit_cost`, `discount_amount`, `line_total`, `cogs_total`, `created_at`) VALUES ('5', '5', '1', '1', '9', '2', '11.0000', '11.0000', '93.00', '90.0000', '0.00', '1023.00', '990.00', '2026-09-03 01:28:51');
INSERT INTO `sale_items` (`id`, `sale_id`, `shop_id`, `item_order`, `product_id`, `unit_id`, `quantity`, `base_quantity`, `unit_price`, `unit_cost`, `discount_amount`, `line_total`, `cogs_total`, `created_at`) VALUES ('6', '6', '1', '1', '8', '2', '900.0000', '900.0000', '95.00', '90.0000', '0.00', '85500.00', '81000.00', '2026-09-03 01:47:16');
INSERT INTO `sale_items` (`id`, `sale_id`, `shop_id`, `item_order`, `product_id`, `unit_id`, `quantity`, `base_quantity`, `unit_price`, `unit_cost`, `discount_amount`, `line_total`, `cogs_total`, `created_at`) VALUES ('7', '7', '1', '1', '9', '2', '1.0000', '1.0000', '95.00', '90.0000', '0.00', '95.00', '90.00', '2026-09-03 01:48:17');
INSERT INTO `sale_items` (`id`, `sale_id`, `shop_id`, `item_order`, `product_id`, `unit_id`, `quantity`, `base_quantity`, `unit_price`, `unit_cost`, `discount_amount`, `line_total`, `cogs_total`, `created_at`) VALUES ('8', '7', '1', '2', '7', '2', '1.0000', '1.0000', '98.00', '93.0000', '0.00', '98.00', '93.00', '2026-09-03 01:48:17');
INSERT INTO `sale_items` (`id`, `sale_id`, `shop_id`, `item_order`, `product_id`, `unit_id`, `quantity`, `base_quantity`, `unit_price`, `unit_cost`, `discount_amount`, `line_total`, `cogs_total`, `created_at`) VALUES ('9', '7', '1', '3', '6', '2', '1.0000', '1.0000', '97.00', '92.0000', '0.00', '97.00', '92.00', '2026-09-03 01:48:17');
INSERT INTO `sale_items` (`id`, `sale_id`, `shop_id`, `item_order`, `product_id`, `unit_id`, `quantity`, `base_quantity`, `unit_price`, `unit_cost`, `discount_amount`, `line_total`, `cogs_total`, `created_at`) VALUES ('10', '7', '1', '4', '4', '1', '1.0000', '1.0000', '535.00', '495.0000', '0.00', '535.00', '495.00', '2026-09-03 01:48:17');
INSERT INTO `sale_items` (`id`, `sale_id`, `shop_id`, `item_order`, `product_id`, `unit_id`, `quantity`, `base_quantity`, `unit_price`, `unit_cost`, `discount_amount`, `line_total`, `cogs_total`, `created_at`) VALUES ('11', '7', '1', '5', '3', '1', '1.0000', '1.0000', '530.00', '490.0000', '0.00', '530.00', '490.00', '2026-09-03 01:48:17');
INSERT INTO `sale_items` (`id`, `sale_id`, `shop_id`, `item_order`, `product_id`, `unit_id`, `quantity`, `base_quantity`, `unit_price`, `unit_cost`, `discount_amount`, `line_total`, `cogs_total`, `created_at`) VALUES ('12', '7', '1', '6', '15', '2', '1.0000', '1.0000', '135.00', '110.0000', '0.00', '135.00', '110.00', '2026-09-03 01:48:17');
INSERT INTO `sale_items` (`id`, `sale_id`, `shop_id`, `item_order`, `product_id`, `unit_id`, `quantity`, `base_quantity`, `unit_price`, `unit_cost`, `discount_amount`, `line_total`, `cogs_total`, `created_at`) VALUES ('13', '7', '1', '7', '10', '4', '1.0000', '1.0000', '52.00', '42.0000', '0.00', '52.00', '42.00', '2026-09-03 01:48:17');

DROP TABLE IF EXISTS `sale_payments`;
CREATE TABLE `sale_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `payment_no` varchar(60) NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `account_type` enum('cash','bank') NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_no` (`payment_no`),
  KEY `idx_spay_sale` (`sale_id`),
  KEY `fk_spay_shop` (`shop_id`),
  KEY `fk_spay_cust` (`customer_id`),
  KEY `fk_spay_user` (`created_by`),
  CONSTRAINT `fk_spay_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_spay_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `fk_spay_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_spay_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sales_returns`;
CREATE TABLE `sales_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `return_no` varchar(60) NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `return_date` date NOT NULL,
  `total_return_amount` decimal(15,2) NOT NULL,
  `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `advance_credited` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_reduced` decimal(15,2) NOT NULL DEFAULT 0.00,
  `account_type` enum('cash','bank') DEFAULT NULL,
  `account_id` int(10) unsigned DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_no` (`return_no`),
  KEY `fk_sret_shop` (`shop_id`),
  KEY `fk_sret_sale` (`sale_id`),
  KEY `fk_sret_cust` (`customer_id`),
  KEY `fk_sret_user` (`created_by`),
  CONSTRAINT `fk_sret_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_sret_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `fk_sret_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sret_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sales_return_items`;
CREATE TABLE `sales_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_id` bigint(20) unsigned NOT NULL,
  `sale_item_id` bigint(20) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `unit_id` int(10) unsigned NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `base_quantity` decimal(15,4) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `unit_cost` decimal(15,4) NOT NULL,
  `line_total` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_sri_ret` (`return_id`),
  KEY `fk_sri_si` (`sale_item_id`),
  KEY `fk_sri_prod` (`product_id`),
  CONSTRAINT `fk_sri_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_sri_ret` FOREIGN KEY (`return_id`) REFERENCES `sales_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sri_si` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `purchases`;
CREATE TABLE `purchases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `purchase_number` varchar(60) NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `purchase_date` date NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('paid','partial','due') NOT NULL,
  `purchase_status` enum('received','cancelled','returned') NOT NULL DEFAULT 'received',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_number` (`purchase_number`),
  KEY `idx_pur_date` (`shop_id`,`purchase_date`),
  KEY `idx_pur_supp` (`shop_id`,`supplier_id`),
  KEY `fk_pur_supp` (`supplier_id`),
  KEY `fk_pur_user` (`created_by`),
  CONSTRAINT `fk_pur_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pur_supp` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `fk_pur_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `purchase_items`;
CREATE TABLE `purchase_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `shop_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `unit_id` int(10) unsigned NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `base_quantity` decimal(15,4) NOT NULL,
  `unit_cost` decimal(15,4) NOT NULL,
  `line_total` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_pi_pur` (`purchase_id`),
  KEY `fk_pi_prod` (`product_id`),
  KEY `fk_pi_unit` (`unit_id`),
  CONSTRAINT `fk_pi_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_pi_pur` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pi_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `purchase_returns`;
CREATE TABLE `purchase_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `return_no` varchar(60) NOT NULL,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `return_date` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `refund_received` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_reduced` decimal(15,2) NOT NULL DEFAULT 0.00,
  `account_type` enum('cash','bank') DEFAULT NULL,
  `account_id` int(10) unsigned DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_no` (`return_no`),
  KEY `fk_pret_shop` (`shop_id`),
  KEY `fk_pret_pur` (`purchase_id`),
  KEY `fk_pret_supp` (`supplier_id`),
  KEY `fk_pret_user` (`created_by`),
  CONSTRAINT `fk_pret_pur` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`),
  CONSTRAINT `fk_pret_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pret_supp` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `fk_pret_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `purchase_return_items`;
CREATE TABLE `purchase_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_id` bigint(20) unsigned NOT NULL,
  `purchase_item_id` bigint(20) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `unit_id` int(10) unsigned NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `base_quantity` decimal(15,4) NOT NULL,
  `unit_cost` decimal(15,4) NOT NULL,
  `line_total` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_pri_ret` (`return_id`),
  KEY `fk_pri_pi` (`purchase_item_id`),
  KEY `fk_pri_prod` (`product_id`),
  CONSTRAINT `fk_pri_pi` FOREIGN KEY (`purchase_item_id`) REFERENCES `purchase_items` (`id`),
  CONSTRAINT `fk_pri_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_pri_ret` FOREIGN KEY (`return_id`) REFERENCES `purchase_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `movement_type` enum('OPENING_STOCK','PURCHASE','SALE','SALE_RETURN','PURCHASE_RETURN','ADJUSTMENT_IN','ADJUSTMENT_OUT') NOT NULL,
  `reference_type` varchar(50) NOT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `reference_no` varchar(60) NOT NULL,
  `quantity` decimal(15,4) NOT NULL COMMENT 'Positive for IN, Negative for OUT',
  `unit_id` int(10) unsigned NOT NULL,
  `base_quantity` decimal(15,4) NOT NULL COMMENT 'Base unit delta (signed)',
  `unit_cost` decimal(15,4) NOT NULL,
  `balance_after` decimal(15,4) NOT NULL COMMENT 'Running product base_stock after movement',
  `user_id` int(10) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sm_prod_date` (`shop_id`,`product_id`,`created_at`),
  KEY `fk_sm_prod` (`product_id`),
  KEY `fk_sm_unit` (`unit_id`),
  KEY `fk_sm_user` (`user_id`),
  CONSTRAINT `fk_sm_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_sm_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sm_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`),
  CONSTRAINT `fk_sm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('1', '1', '1', 'OPENING_STOCK', 'SYSTEM_INIT', '1', 'INIT-STOCK', '500.0000', '1', '500.0000', '500.0000', '500.0000', '1', 'Initial warehouse opening stock setup', '2026-09-03 00:55:24');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('2', '1', '2', 'OPENING_STOCK', 'SYSTEM_INIT', '1', 'INIT-STOCK', '400.0000', '1', '400.0000', '495.0000', '400.0000', '1', 'Initial warehouse opening stock setup', '2026-09-03 00:55:24');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('3', '1', '3', 'OPENING_STOCK', 'SYSTEM_INIT', '1', 'INIT-STOCK', '350.0000', '1', '350.0000', '490.0000', '350.0000', '1', 'Initial warehouse opening stock setup', '2026-09-03 00:55:24');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('4', '1', '4', 'OPENING_STOCK', 'SYSTEM_INIT', '1', 'INIT-STOCK', '300.0000', '1', '300.0000', '495.0000', '300.0000', '1', 'Initial warehouse opening stock setup', '2026-09-03 00:55:24');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('5', '1', '5', 'OPENING_STOCK', 'SYSTEM_INIT', '1', 'INIT-STOCK', '10000.0000', '2', '10000.0000', '92.0000', '10000.0000', '1', 'Initial warehouse opening stock setup', '2026-09-03 00:55:24');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('6', '1', '6', 'OPENING_STOCK', 'SYSTEM_INIT', '1', 'INIT-STOCK', '8000.0000', '2', '8000.0000', '92.0000', '8000.0000', '1', 'Initial warehouse opening stock setup', '2026-09-03 00:55:24');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('7', '1', '7', 'OPENING_STOCK', 'SYSTEM_INIT', '1', 'INIT-STOCK', '7000.0000', '2', '7000.0000', '93.0000', '7000.0000', '1', 'Initial warehouse opening stock setup', '2026-09-03 00:55:24');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('8', '1', '8', 'OPENING_STOCK', 'SYSTEM_INIT', '1', 'INIT-STOCK', '9000.0000', '2', '9000.0000', '90.0000', '9000.0000', '1', 'Initial warehouse opening stock setup', '2026-09-03 00:55:24');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('9', '1', '9', 'OPENING_STOCK', 'SYSTEM_INIT', '1', 'INIT-STOCK', '8500.0000', '2', '8500.0000', '90.0000', '8500.0000', '1', 'Initial warehouse opening stock setup', '2026-09-03 00:55:24');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('10', '1', '10', 'OPENING_STOCK', 'SYSTEM_INIT', '1', 'INIT-STOCK', '5000.0000', '4', '5000.0000', '42.0000', '5000.0000', '1', 'Initial warehouse opening stock setup', '2026-09-03 00:55:24');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('11', '1', '11', 'OPENING_STOCK', 'SYSTEM_INIT', '1', 'INIT-STOCK', '6000.0000', '4', '6000.0000', '28.0000', '6000.0000', '1', 'Initial warehouse opening stock setup', '2026-09-03 00:55:24');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('12', '1', '12', 'OPENING_STOCK', 'SYSTEM_INIT', '1', 'INIT-STOCK', '40000.0000', '5', '40000.0000', '11.5000', '40000.0000', '1', 'Initial warehouse opening stock setup', '2026-09-03 00:55:24');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('13', '1', '13', 'OPENING_STOCK', 'SYSTEM_INIT', '1', 'INIT-STOCK', '25000.0000', '5', '25000.0000', '12.0000', '25000.0000', '1', 'Initial warehouse opening stock setup', '2026-09-03 00:55:24');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('14', '1', '14', 'OPENING_STOCK', 'SYSTEM_INIT', '1', 'INIT-STOCK', '4000.0000', '4', '4000.0000', '140.0000', '4000.0000', '1', 'Initial warehouse opening stock setup', '2026-09-03 00:55:24');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('15', '1', '15', 'OPENING_STOCK', 'SYSTEM_INIT', '1', 'INIT-STOCK', '1500.0000', '2', '1500.0000', '110.0000', '1500.0000', '1', 'Initial warehouse opening stock setup', '2026-09-03 00:55:24');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('19', '1', '16', 'OPENING_STOCK', 'OPENING_STOCK', '16', 'OP-STOCK-16', '10.0000', '1', '10.0000', '70.0000', '10.0000', '1', 'Initial opening stock', '2026-09-03 01:12:22');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('20', '1', '12', 'SALE', 'SALE', '4', 'MT-2026-000001', '-15.0000', '5', '-15.0000', '11.5000', '39985.0000', '1', 'Sales Memo [MT-2026-000001]', '2026-09-03 01:18:26');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('21', '1', '9', 'SALE', 'SALE', '5', 'MT-2026-000002', '-11.0000', '2', '-11.0000', '90.0000', '8489.0000', '1', 'Sales Memo [MT-2026-000002]', '2026-09-03 01:28:51');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('22', '1', '8', 'SALE', 'SALE', '6', 'MT-2026-000003', '-900.0000', '2', '-900.0000', '90.0000', '8100.0000', '1', 'Sales Memo [MT-2026-000003]', '2026-09-03 01:47:16');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('23', '1', '9', 'SALE', 'SALE', '7', 'MT-2026-000004', '-1.0000', '2', '-1.0000', '90.0000', '8488.0000', '1', 'Sales Memo [MT-2026-000004]', '2026-09-03 01:48:17');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('24', '1', '7', 'SALE', 'SALE', '7', 'MT-2026-000004', '-1.0000', '2', '-1.0000', '93.0000', '6999.0000', '1', 'Sales Memo [MT-2026-000004]', '2026-09-03 01:48:17');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('25', '1', '6', 'SALE', 'SALE', '7', 'MT-2026-000004', '-1.0000', '2', '-1.0000', '92.0000', '7999.0000', '1', 'Sales Memo [MT-2026-000004]', '2026-09-03 01:48:17');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('26', '1', '4', 'SALE', 'SALE', '7', 'MT-2026-000004', '-1.0000', '1', '-1.0000', '495.0000', '299.0000', '1', 'Sales Memo [MT-2026-000004]', '2026-09-03 01:48:17');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('27', '1', '3', 'SALE', 'SALE', '7', 'MT-2026-000004', '-1.0000', '1', '-1.0000', '490.0000', '349.0000', '1', 'Sales Memo [MT-2026-000004]', '2026-09-03 01:48:17');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('28', '1', '15', 'SALE', 'SALE', '7', 'MT-2026-000004', '-1.0000', '2', '-1.0000', '110.0000', '1499.0000', '1', 'Sales Memo [MT-2026-000004]', '2026-09-03 01:48:17');
INSERT INTO `stock_movements` (`id`, `shop_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_id`, `base_quantity`, `unit_cost`, `balance_after`, `user_id`, `notes`, `created_at`) VALUES ('29', '1', '10', 'SALE', 'SALE', '7', 'MT-2026-000004', '-1.0000', '4', '-1.0000', '42.0000', '4999.0000', '1', 'Sales Memo [MT-2026-000004]', '2026-09-03 01:48:17');

DROP TABLE IF EXISTS `stock_adjustments`;
CREATE TABLE `stock_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `adjustment_no` varchar(60) NOT NULL,
  `adjustment_date` date NOT NULL,
  `reason_code` enum('damaged','lost','counting_difference','opening_correction','other') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `adjustment_no` (`adjustment_no`),
  KEY `fk_sa_shop` (`shop_id`),
  KEY `fk_sa_user` (`created_by`),
  CONSTRAINT `fk_sa_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sa_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stock_adjustment_items`;
CREATE TABLE `stock_adjustment_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_id` bigint(20) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `adjustment_type` enum('IN','OUT') NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `base_quantity` decimal(15,4) NOT NULL,
  `unit_id` int(10) unsigned NOT NULL,
  `unit_cost` decimal(15,4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_sai_adj` (`adjustment_id`),
  KEY `fk_sai_prod` (`product_id`),
  KEY `fk_sai_unit` (`unit_id`),
  CONSTRAINT `fk_sai_adj` FOREIGN KEY (`adjustment_id`) REFERENCES `stock_adjustments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sai_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_sai_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `quotations`;
CREATE TABLE `quotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `quotation_no` varchar(60) NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `quotation_date` date NOT NULL,
  `valid_until` date NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL,
  `status` enum('draft','sent','accepted','rejected','expired','converted') NOT NULL DEFAULT 'draft',
  `converted_sale_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotation_no` (`quotation_no`),
  KEY `fk_quot_shop` (`shop_id`),
  KEY `fk_quot_cust` (`customer_id`),
  KEY `fk_quot_user` (`created_by`),
  CONSTRAINT `fk_quot_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_quot_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quot_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `quotation_items`;
CREATE TABLE `quotation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint(20) unsigned NOT NULL,
  `item_order` int(10) unsigned NOT NULL DEFAULT 1,
  `product_id` int(10) unsigned NOT NULL,
  `unit_id` int(10) unsigned NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_qi_quot` (`quotation_id`),
  KEY `fk_qi_prod` (`product_id`),
  KEY `fk_qi_unit` (`unit_id`),
  CONSTRAINT `fk_qi_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_qi_quot` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qi_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `record_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_module_rec` (`shop_id`,`module`,`record_id`),
  KEY `fk_audit_user` (`user_id`),
  CONSTRAINT `fk_audit_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;

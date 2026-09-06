-- ============================================================================
-- DATABASE SCHEMA: CONSTRUCTION MATERIALS SHOP MANAGEMENT & ACCOUNTING ERP
-- Database Engine: MySQL 8.0+ / MariaDB 10.5+
-- Storage Engine: InnoDB
-- Character Set: utf8mb4 / utf8mb4_unicode_ci
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS document_sequences;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS stock_adjustment_items;
DROP TABLE IF EXISTS stock_adjustments;
DROP TABLE IF EXISTS sale_payments;
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales_return_items;
DROP TABLE IF EXISTS sales_returns;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS purchase_return_items;
DROP TABLE IF EXISTS purchase_returns;
DROP TABLE IF EXISTS purchase_items;
DROP TABLE IF EXISTS purchase_payments;
DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS quotation_items;
DROP TABLE IF EXISTS quotations;
DROP TABLE IF EXISTS customer_advance_adjustments;
DROP TABLE IF EXISTS customer_advances;
DROP TABLE IF EXISTS customer_payments;
DROP TABLE IF EXISTS customer_ledger;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS supplier_advances;
DROP TABLE IF EXISTS supplier_payments;
DROP TABLE IF EXISTS supplier_ledger;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS financial_transactions;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS expense_categories;
DROP TABLE IF EXISTS incomes;
DROP TABLE IF EXISTS income_categories;
DROP TABLE IF EXISTS bank_accounts;
DROP TABLE IF EXISTS cash_accounts;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS unit_conversions;
DROP TABLE IF EXISTS units;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS shops;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. SHOPS & GLOBAL SETTINGS
-- ----------------------------------------------------------------------------
CREATE TABLE shops (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    proprietor VARCHAR(100) NULL,
    address TEXT NOT NULL,
    phone VARCHAR(30) NOT NULL,
    alt_phone VARCHAR(30) NULL,
    email VARCHAR(100) NULL,
    website VARCHAR(150) NULL,
    invoice_prefix VARCHAR(10) NOT NULL DEFAULT 'MT',
    currency_symbol VARCHAR(10) NOT NULL DEFAULT '৳',
    currency_code VARCHAR(10) NOT NULL DEFAULT 'BDT',
    currency_position ENUM('before', 'after') NOT NULL DEFAULT 'before',
    date_format VARCHAR(20) NOT NULL DEFAULT 'd-m-Y',
    invoice_footer_shop TEXT NULL,
    invoice_footer_customer TEXT NULL,
    logo_url VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value LONGTEXT NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_shop_setting (shop_id, setting_key),
    CONSTRAINT fk_settings_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE document_sequences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    code VARCHAR(50) NOT NULL,
    fiscal_year VARCHAR(10) NOT NULL,
    prefix VARCHAR(20) NOT NULL,
    current_number BIGINT UNSIGNED NOT NULL DEFAULT 0,
    padding INT UNSIGNED NOT NULL DEFAULT 6,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_seq (shop_id, code, fiscal_year),
    CONSTRAINT fk_docseq_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. USERS, ROLES & PERMISSIONS
-- ----------------------------------------------------------------------------
CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_shop_role (shop_id, slug),
    CONSTRAINT fk_roles_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(50) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NULL,
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    theme_preference ENUM('light', 'dark', 'system') NOT NULL DEFAULT 'system',
    last_login_at DATETIME NULL,
    last_login_ip VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_roles (
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. INVENTORY: UNITS, CATEGORIES, BRANDS, PRODUCTS
-- ----------------------------------------------------------------------------
CREATE TABLE units (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    name VARCHAR(50) NOT NULL,
    symbol VARCHAR(20) NOT NULL,
    is_base_unit TINYINT(1) NOT NULL DEFAULT 1,
    decimal_places TINYINT UNSIGNED NOT NULL DEFAULT 2,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_shop_unit (shop_id, symbol),
    CONSTRAINT fk_units_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE unit_conversions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    from_unit_id INT UNSIGNED NOT NULL,
    to_unit_id INT UNSIGNED NOT NULL,
    conversion_factor DECIMAL(15,6) NOT NULL COMMENT 'Multiply from_unit qty by factor to get to_unit qty',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_conversion (shop_id, from_unit_id, to_unit_id),
    CONSTRAINT fk_uc_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_uc_from FOREIGN KEY (from_unit_id) REFERENCES units (id) ON DELETE CASCADE,
    CONSTRAINT fk_uc_to FOREIGN KEY (to_unit_id) REFERENCES units (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_shop_category (shop_id, name),
    CONSTRAINT fk_categories_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE brands (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NULL,
    origin_country VARCHAR(50) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_shop_brand (shop_id, name),
    CONSTRAINT fk_brands_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    brand_id INT UNSIGNED NULL,
    base_unit_id INT UNSIGNED NOT NULL,
    sku VARCHAR(50) NOT NULL,
    name VARCHAR(180) NOT NULL,
    description TEXT NULL,
    purchase_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    selling_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    min_selling_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    current_stock DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    min_stock DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    average_cost DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_shop_sku (shop_id, sku),
    INDEX idx_product_search (shop_id, is_active, name),
    INDEX idx_product_stock (shop_id, current_stock, min_stock),
    CONSTRAINT fk_products_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT,
    CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands (id) ON DELETE SET NULL,
    CONSTRAINT fk_products_unit FOREIGN KEY (base_unit_id) REFERENCES units (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. CUSTOMERS, ADVANCES & RECEIVABLES LEDGER
-- ----------------------------------------------------------------------------
CREATE TABLE customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    customer_code VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    mobile VARCHAR(30) NOT NULL,
    alt_mobile VARCHAR(30) NULL,
    address TEXT NULL,
    customer_type ENUM('retail', 'contractor', 'dealer', 'builder', 'other') NOT NULL DEFAULT 'retail',
    credit_limit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    opening_due DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    opening_advance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    current_due DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    current_advance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive', 'blocked') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_shop_cust_code (shop_id, customer_code),
    INDEX idx_customer_search (shop_id, mobile, name),
    CONSTRAINT fk_customers_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_ledger (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    transaction_date DATE NOT NULL,
    transaction_type ENUM('OPENING_BALANCE', 'SALE', 'PAYMENT', 'SALE_RETURN', 'ADVANCE_ADJUSTMENT', 'DISCOUNT_ADJUSTMENT', 'DUE_CORRECTION') NOT NULL,
    reference_type VARCHAR(50) NOT NULL,
    reference_id BIGINT UNSIGNED NULL,
    reference_no VARCHAR(60) NOT NULL,
    description VARCHAR(255) NOT NULL,
    debit DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Receivable increase (Sales, Debit Notes)',
    credit DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Receivable decrease (Payments, Returns, Advances Applied)',
    running_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Cumulative Current Due',
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cust_ledger_date (shop_id, customer_id, transaction_date),
    CONSTRAINT fk_cledger_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_cledger_cust FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_cledger_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_advances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    advance_no VARCHAR(60) NOT NULL UNIQUE,
    receipt_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    adjusted_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    refunded_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    remaining_balance DECIMAL(15,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    account_type ENUM('cash', 'bank') NOT NULL,
    account_id INT UNSIGNED NOT NULL,
    status ENUM('active', 'fully_adjusted', 'refunded', 'cancelled') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_adv_cust (shop_id, customer_id, status),
    CONSTRAINT fk_cadv_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_cadv_cust FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_cadv_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_advance_adjustments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    advance_id BIGINT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NOT NULL,
    adjustment_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_caa_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_caa_adv FOREIGN KEY (advance_id) REFERENCES customer_advances (id) ON DELETE RESTRICT,
    CONSTRAINT fk_caa_cust FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_caa_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. SUPPLIERS, ADVANCES & PAYABLES LEDGER
-- ----------------------------------------------------------------------------
CREATE TABLE suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    supplier_code VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    company_name VARCHAR(150) NULL,
    mobile VARCHAR(30) NOT NULL,
    address TEXT NULL,
    opening_due DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    opening_advance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    current_due DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    current_advance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_shop_supp_code (shop_id, supplier_code),
    INDEX idx_supplier_search (shop_id, mobile, name),
    CONSTRAINT fk_suppliers_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_ledger (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    transaction_date DATE NOT NULL,
    transaction_type ENUM('OPENING_BALANCE', 'PURCHASE', 'PAYMENT', 'PURCHASE_RETURN', 'ADVANCE_PAYMENT', 'DUE_CORRECTION') NOT NULL,
    reference_type VARCHAR(50) NOT NULL,
    reference_id BIGINT UNSIGNED NULL,
    reference_no VARCHAR(60) NOT NULL,
    description VARCHAR(255) NOT NULL,
    debit DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Payable decrease (Payments, Returns)',
    credit DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Payable increase (Purchases)',
    running_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Cumulative Current Due',
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_supp_ledger_date (shop_id, supplier_id, transaction_date),
    CONSTRAINT fk_sledger_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_sledger_supp FOREIGN KEY (supplier_id) REFERENCES suppliers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_sledger_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_advances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    advance_no VARCHAR(60) NOT NULL UNIQUE,
    payment_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    adjusted_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    refunded_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    remaining_balance DECIMAL(15,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    account_type ENUM('cash', 'bank') NOT NULL,
    account_id INT UNSIGNED NOT NULL,
    status ENUM('active', 'fully_adjusted', 'refunded', 'cancelled') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sadv_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_sadv_supp FOREIGN KEY (supplier_id) REFERENCES suppliers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_sadv_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. CASH & BANK / DIGITAL ACCOUNTS & FINANCIAL TRANSACTIONS
-- ----------------------------------------------------------------------------
CREATE TABLE cash_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    current_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    is_default TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cash_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bank_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    account_title VARCHAR(150) NOT NULL,
    account_type ENUM('bank', 'bkash', 'nagad', 'rocket', 'other') NOT NULL DEFAULT 'bank',
    bank_name VARCHAR(100) NULL,
    account_number VARCHAR(60) NOT NULL,
    branch_name VARCHAR(100) NULL,
    opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    current_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bank_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE financial_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    transaction_no VARCHAR(60) NOT NULL UNIQUE,
    transaction_date DATE NOT NULL,
    account_type ENUM('cash', 'bank') NOT NULL,
    account_id INT UNSIGNED NOT NULL,
    transaction_nature ENUM('INFLOW', 'OUTFLOW') NOT NULL,
    category ENUM('SALE_PAYMENT', 'CUSTOMER_COLLECTION', 'CUSTOMER_ADVANCE', 'PURCHASE_PAYMENT', 'SUPPLIER_PAYMENT', 'EXPENSE', 'INCOME', 'BANK_DEPOSIT', 'BANK_WITHDRAWAL', 'ACCOUNT_TRANSFER', 'OPENING_BALANCE') NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id BIGINT UNSIGNED NULL,
    reference_no VARCHAR(60) NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) NOT NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fin_date (shop_id, transaction_date),
    INDEX idx_fin_acc (shop_id, account_type, account_id),
    CONSTRAINT fk_fintrans_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_fintrans_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. EXPENSES & INCOMES
-- ----------------------------------------------------------------------------
CREATE TABLE expense_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expcat_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    expense_no VARCHAR(60) NOT NULL UNIQUE,
    expense_date DATE NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    account_type ENUM('cash', 'bank') NOT NULL,
    account_id INT UNSIGNED NOT NULL,
    payee_name VARCHAR(150) NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expense_date (shop_id, expense_date),
    CONSTRAINT fk_exp_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_exp_cat FOREIGN KEY (category_id) REFERENCES expense_categories (id) ON DELETE RESTRICT,
    CONSTRAINT fk_exp_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE income_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inccat_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE incomes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    income_no VARCHAR(60) NOT NULL UNIQUE,
    income_date DATE NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    account_type ENUM('cash', 'bank') NOT NULL,
    account_id INT UNSIGNED NOT NULL,
    payer_name VARCHAR(150) NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_income_date (shop_id, income_date),
    CONSTRAINT fk_inc_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_inc_cat FOREIGN KEY (category_id) REFERENCES income_categories (id) ON DELETE RESTRICT,
    CONSTRAINT fk_inc_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. SALES, MEMOS & SALE PAYMENTS
-- ----------------------------------------------------------------------------
CREATE TABLE sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    memo_number VARCHAR(60) NOT NULL UNIQUE,
    customer_id INT UNSIGNED NOT NULL,
    sale_date DATE NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    discount_type ENUM('fixed', 'percentage') NOT NULL DEFAULT 'fixed',
    discount_value DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(15,2) NOT NULL,
    paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    advance_used DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    due_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_cogs DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Snapshot of total Cost of Goods Sold',
    gross_profit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('paid', 'partial', 'due', 'advance_settled') NOT NULL,
    sale_status ENUM('completed', 'cancelled', 'returned', 'partial_return') NOT NULL DEFAULT 'completed',
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sales_date (shop_id, sale_date),
    INDEX idx_sales_cust (shop_id, customer_id),
    CONSTRAINT fk_sales_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_sales_cust FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_sales_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sale_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    shop_id INT UNSIGNED NOT NULL,
    item_order INT UNSIGNED NOT NULL DEFAULT 1,
    product_id INT UNSIGNED NOT NULL,
    unit_id INT UNSIGNED NOT NULL,
    quantity DECIMAL(15,4) NOT NULL,
    base_quantity DECIMAL(15,4) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    unit_cost DECIMAL(15,4) NOT NULL COMMENT 'Cost snapshot at the time of sale for accurate COGS',
    discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(15,2) NOT NULL,
    cogs_total DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_si_sale (sale_id),
    INDEX idx_si_prod (product_id),
    CONSTRAINT fk_si_sale FOREIGN KEY (sale_id) REFERENCES sales (id) ON DELETE CASCADE,
    CONSTRAINT fk_si_prod FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT,
    CONSTRAINT fk_si_unit FOREIGN KEY (unit_id) REFERENCES units (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sale_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    payment_no VARCHAR(60) NOT NULL UNIQUE,
    sale_id BIGINT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    account_type ENUM('cash', 'bank') NOT NULL,
    account_id INT UNSIGNED NOT NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_spay_sale (sale_id),
    CONSTRAINT fk_spay_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_spay_sale FOREIGN KEY (sale_id) REFERENCES sales (id) ON DELETE RESTRICT,
    CONSTRAINT fk_spay_cust FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_spay_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. SALES RETURNS
-- ----------------------------------------------------------------------------
CREATE TABLE sales_returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    return_no VARCHAR(60) NOT NULL UNIQUE,
    sale_id BIGINT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    return_date DATE NOT NULL,
    total_return_amount DECIMAL(15,2) NOT NULL,
    refund_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    advance_credited DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    due_reduced DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    account_type ENUM('cash', 'bank') NULL,
    account_id INT UNSIGNED NULL,
    reason TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sret_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_sret_sale FOREIGN KEY (sale_id) REFERENCES sales (id) ON DELETE RESTRICT,
    CONSTRAINT fk_sret_cust FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_sret_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sales_return_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_id BIGINT UNSIGNED NOT NULL,
    sale_item_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    unit_id INT UNSIGNED NOT NULL,
    quantity DECIMAL(15,4) NOT NULL,
    base_quantity DECIMAL(15,4) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    unit_cost DECIMAL(15,4) NOT NULL,
    line_total DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sri_ret FOREIGN KEY (return_id) REFERENCES sales_returns (id) ON DELETE CASCADE,
    CONSTRAINT fk_sri_si FOREIGN KEY (sale_item_id) REFERENCES sale_items (id) ON DELETE RESTRICT,
    CONSTRAINT fk_sri_prod FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 10. PURCHASES & PURCHASE RETURNS
-- ----------------------------------------------------------------------------
CREATE TABLE purchases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    purchase_number VARCHAR(60) NOT NULL UNIQUE,
    supplier_id INT UNSIGNED NOT NULL,
    purchase_date DATE NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(15,2) NOT NULL,
    paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    due_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('paid', 'partial', 'due') NOT NULL,
    purchase_status ENUM('received', 'cancelled', 'returned') NOT NULL DEFAULT 'received',
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pur_date (shop_id, purchase_date),
    INDEX idx_pur_supp (shop_id, supplier_id),
    CONSTRAINT fk_pur_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_pur_supp FOREIGN KEY (supplier_id) REFERENCES suppliers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pur_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id BIGINT UNSIGNED NOT NULL,
    shop_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    unit_id INT UNSIGNED NOT NULL,
    quantity DECIMAL(15,4) NOT NULL,
    base_quantity DECIMAL(15,4) NOT NULL,
    unit_cost DECIMAL(15,4) NOT NULL,
    line_total DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pi_pur FOREIGN KEY (purchase_id) REFERENCES purchases (id) ON DELETE CASCADE,
    CONSTRAINT fk_pi_prod FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pi_unit FOREIGN KEY (unit_id) REFERENCES units (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    return_no VARCHAR(60) NOT NULL UNIQUE,
    purchase_id BIGINT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    return_date DATE NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    refund_received DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    due_reduced DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    account_type ENUM('cash', 'bank') NULL,
    account_id INT UNSIGNED NULL,
    reason TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pret_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_pret_pur FOREIGN KEY (purchase_id) REFERENCES purchases (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pret_supp FOREIGN KEY (supplier_id) REFERENCES suppliers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pret_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_return_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_id BIGINT UNSIGNED NOT NULL,
    purchase_item_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    unit_id INT UNSIGNED NOT NULL,
    quantity DECIMAL(15,4) NOT NULL,
    base_quantity DECIMAL(15,4) NOT NULL,
    unit_cost DECIMAL(15,4) NOT NULL,
    line_total DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pri_ret FOREIGN KEY (return_id) REFERENCES purchase_returns (id) ON DELETE CASCADE,
    CONSTRAINT fk_pri_pi FOREIGN KEY (purchase_item_id) REFERENCES purchase_items (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pri_prod FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 11. STOCK LEDGER & ADJUSTMENTS
-- ----------------------------------------------------------------------------
CREATE TABLE stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    movement_type ENUM('OPENING_STOCK', 'PURCHASE', 'SALE', 'SALE_RETURN', 'PURCHASE_RETURN', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT') NOT NULL,
    reference_type VARCHAR(50) NOT NULL,
    reference_id BIGINT UNSIGNED NULL,
    reference_no VARCHAR(60) NOT NULL,
    quantity DECIMAL(15,4) NOT NULL COMMENT 'Positive for IN, Negative for OUT',
    unit_id INT UNSIGNED NOT NULL,
    base_quantity DECIMAL(15,4) NOT NULL COMMENT 'Base unit delta (signed)',
    unit_cost DECIMAL(15,4) NOT NULL,
    balance_after DECIMAL(15,4) NOT NULL COMMENT 'Running product base_stock after movement',
    user_id INT UNSIGNED NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sm_prod_date (shop_id, product_id, created_at),
    CONSTRAINT fk_sm_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_sm_prod FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT,
    CONSTRAINT fk_sm_unit FOREIGN KEY (unit_id) REFERENCES units (id) ON DELETE RESTRICT,
    CONSTRAINT fk_sm_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_adjustments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    adjustment_no VARCHAR(60) NOT NULL UNIQUE,
    adjustment_date DATE NOT NULL,
    reason_code ENUM('damaged', 'lost', 'counting_difference', 'opening_correction', 'other') NOT NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sa_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_sa_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_adjustment_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adjustment_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    adjustment_type ENUM('IN', 'OUT') NOT NULL,
    quantity DECIMAL(15,4) NOT NULL,
    base_quantity DECIMAL(15,4) NOT NULL,
    unit_id INT UNSIGNED NOT NULL,
    unit_cost DECIMAL(15,4) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sai_adj FOREIGN KEY (adjustment_id) REFERENCES stock_adjustments (id) ON DELETE CASCADE,
    CONSTRAINT fk_sai_prod FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT,
    CONSTRAINT fk_sai_unit FOREIGN KEY (unit_id) REFERENCES units (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 12. QUOTATIONS
-- ----------------------------------------------------------------------------
CREATE TABLE quotations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    quotation_no VARCHAR(60) NOT NULL UNIQUE,
    customer_id INT UNSIGNED NOT NULL,
    quotation_date DATE NOT NULL,
    valid_until DATE NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(15,2) NOT NULL,
    status ENUM('draft', 'sent', 'accepted', 'rejected', 'expired', 'converted') NOT NULL DEFAULT 'draft',
    converted_sale_id BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_quot_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_quot_cust FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT,
    CONSTRAINT fk_quot_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quotation_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quotation_id BIGINT UNSIGNED NOT NULL,
    item_order INT UNSIGNED NOT NULL DEFAULT 1,
    product_id INT UNSIGNED NOT NULL,
    unit_id INT UNSIGNED NOT NULL,
    quantity DECIMAL(15,4) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_qi_quot FOREIGN KEY (quotation_id) REFERENCES quotations (id) ON DELETE CASCADE,
    CONSTRAINT fk_qi_prod FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT,
    CONSTRAINT fk_qi_unit FOREIGN KEY (unit_id) REFERENCES units (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 13. AUDIT LOGS
-- ----------------------------------------------------------------------------
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    record_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NOT NULL,
    old_data JSON NULL,
    new_data JSON NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_module_rec (shop_id, module, record_id),
    CONSTRAINT fk_audit_shop FOREIGN KEY (shop_id) REFERENCES shops (id) ON DELETE CASCADE,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

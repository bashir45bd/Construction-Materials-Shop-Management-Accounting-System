-- ============================================================================
-- INITIAL SEED DATA: CONSTRUCTION MATERIALS SHOP MANAGEMENT ERP
-- ============================================================================

-- 1. Initial Shop Profile
INSERT INTO shops (id, shop_code, name, proprietor, address, phone, alt_phone, email, website, invoice_prefix, currency_symbol, currency_code, currency_position, date_format, invoice_footer_shop, invoice_footer_customer, is_active)
VALUES (
    1, 
    'SHOP001', 
    'Maruf Traders & Construction Supply', 
    'Md. Maruf Hasan', 
    'Highway Road, Station Bazaar, Habiganj, Sylhet', 
    '01711-000000', 
    '01811-000000', 
    'info@maruftraders.com', 
    'www.maruftraders.com', 
    'MT', 
    '৳', 
    'BDT', 
    'before', 
    'd-m-Y', 
    'Shop Copy - For official accounting and inventory records.', 
    'Customer Copy - Thank you for your business! Goods once sold can be returned within 7 days with valid memo.', 
    1
);

-- 2. Document Sequences (Fiscal Year 2026)
INSERT INTO document_sequences (shop_id, code, fiscal_year, prefix, current_number, padding) VALUES
(1, 'MEMO', '2026', 'MT-2026-', 0, 6),
(1, 'PURCHASE', '2026', 'PUR-2026-', 0, 6),
(1, 'PAYMENT', '2026', 'PAY-2026-', 0, 6),
(1, 'ADVANCE', '2026', 'ADV-2026-', 0, 6),
(1, 'SALE_RETURN', '2026', 'SR-2026-', 0, 6),
(1, 'PURCHASE_RETURN', '2026', 'PR-2026-', 0, 6),
(1, 'QUOTATION', '2026', 'QT-2026-', 0, 6),
(1, 'ADJUSTMENT', '2026', 'ADJ-2026-', 0, 6),
(1, 'EXPENSE', '2026', 'EXP-2026-', 0, 6),
(1, 'INCOME', '2026', 'INC-2026-', 0, 6),
(1, 'TRANSACTION', '2026', 'TXN-2026-', 0, 6);

-- 3. Default Permissions
INSERT INTO permissions (module, slug, name, description) VALUES
('dashboard', 'dashboard.view', 'View Dashboard', 'Access dashboard overview and KPI statistics'),
('sales', 'sales.create', 'Create Sales Memo', 'Create and finalize new sales memos'),
('sales', 'sales.view', 'View Sales Memo', 'View memo history and details'),
('sales', 'sales.edit', 'Edit Sales Memo', 'Modify sales memo draft/details'),
('sales', 'sales.cancel', 'Cancel Sales Memo', 'Void/Cancel finalized sales memos'),
('sales', 'sales.return', 'Process Sales Return', 'Accept customer product returns'),
('purchases', 'purchase.create', 'Create Purchase Voucher', 'Create supplier procurement orders'),
('purchases', 'purchase.view', 'View Purchases', 'View purchase history and vouchers'),
('purchases', 'purchase.edit', 'Edit Purchases', 'Modify purchase voucher details'),
('purchases', 'purchase.return', 'Process Purchase Return', 'Return materials back to supplier'),
('inventory', 'inventory.view', 'View Inventory', 'View product list, stock levels, and valuation'),
('inventory', 'inventory.manage', 'Manage Products', 'Create and edit products, categories, brands, units'),
('inventory', 'inventory.adjust', 'Adjust Stock', 'Perform manual stock adjustments for damage/loss'),
('customers', 'customer.view', 'View Customers', 'Access customer list and profiles'),
('customers', 'customer.manage', 'Manage Customers', 'Create and edit customer records'),
('customers', 'customer.payment', 'Customer Payments', 'Receive due payments from customers'),
('customers', 'customer.advance', 'Customer Advance', 'Accept and manage advance deposits from customers'),
('suppliers', 'supplier.view', 'View Suppliers', 'Access supplier directory and profiles'),
('suppliers', 'supplier.manage', 'Manage Suppliers', 'Create and edit supplier records'),
('suppliers', 'supplier.payment', 'Supplier Payments', 'Pay supplier dues and disbursements'),
('finance', 'finance.view', 'View Finance', 'Access cash registers, bank accounts, and transactions'),
('finance', 'finance.income', 'Manage Income', 'Record non-sales revenue'),
('finance', 'finance.expense', 'Manage Expense', 'Record overhead and operational expenses'),
('reports', 'reports.sales', 'Sales Reports', 'Access sales analytics and customer sales reports'),
('reports', 'reports.purchase', 'Purchase Reports', 'Access procurement analytics and reports'),
('reports', 'reports.stock', 'Stock Reports', 'Access inventory movement and valuation reports'),
('reports', 'reports.due', 'Due & Advance Reports', 'Access customer and supplier due/advance reports'),
('reports', 'reports.profit', 'Profit & Loss Reports', 'Access financial income statement and profit reports'),
('calculators', 'calculators.use', 'Construction Calculators', 'Access civil engineering construction calculators'),
('settings', 'settings.manage', 'Manage Settings', 'Configure shop preferences, print formats, sequences'),
('users', 'users.manage', 'Manage Users & RBAC', 'Create users, assign roles and modify permissions'),
('backup', 'backup.manage', 'Backup & Data Export', 'Download SQL backups and export system data');

-- 4. Roles
INSERT INTO roles (id, shop_id, name, slug, description, is_system) VALUES
(1, 1, 'Super Admin', 'super_admin', 'Unrestricted administrative access to all shop modules and settings', 1),
(2, 1, 'Admin', 'admin', 'Full shop management including sales, purchases, inventory, finance, and reports', 1),
(3, 1, 'Manager', 'manager', 'Operational management including sales, inventory, and customer relations', 0),
(4, 1, 'Salesman', 'salesman', 'Front-desk point of sale, customer creation, and quick memo generation', 0),
(5, 1, 'Accountant', 'accountant', 'Ledger management, due collections, expenses, bank accounts, and finance reports', 0),
(6, 1, 'Stock Manager', 'stock_manager', 'Inventory control, purchases, stock movements, and stock adjustments', 0);

-- Assign all permissions to Super Admin & Admin
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions;

-- Assign Salesman permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE slug IN (
    'dashboard.view', 'sales.create', 'sales.view', 'sales.return', 
    'inventory.view', 'customer.view', 'customer.manage', 'customer.payment', 
    'customer.advance', 'calculators.use'
);

-- Assign Accountant permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE slug IN (
    'dashboard.view', 'sales.view', 'purchase.view', 'inventory.view', 
    'customer.view', 'customer.payment', 'customer.advance', 
    'supplier.view', 'supplier.payment', 'finance.view', 'finance.income', 
    'finance.expense', 'reports.sales', 'reports.purchase', 'reports.due', 
    'reports.profit', 'calculators.use'
);

-- 5. Default Users
-- Default password: 'Password123!' -> hash generated via password_hash('Password123!', PASSWORD_BCRYPT)
INSERT INTO users (id, shop_id, username, email, password_hash, full_name, phone, status, theme_preference) VALUES
(1, 1, 'admin', 'admin@maruftraders.com', '$2y$10$I62f1OxRiHWqgQnLhOHqIuw85rKo9ZiLirV3dO7QgW9MpM4EDvMme', 'System Administrator', '01711000000', 'active', 'system'),
(2, 1, 'salesman', 'sales@maruftraders.com', '$2y$10$I62f1OxRiHWqgQnLhOHqIuw85rKo9ZiLirV3dO7QgW9MpM4EDvMme', 'Karim Ullah (Sales)', '01722000000', 'active', 'light'),
(3, 1, 'accountant', 'accounts@maruftraders.com', '$2y$10$I62f1OxRiHWqgQnLhOHqIuw85rKo9ZiLirV3dO7QgW9MpM4EDvMme', 'Rahim Ahmed (Accounts)', '01733000000', 'active', 'dark');

INSERT INTO user_roles (user_id, role_id) VALUES
(1, 1),
(2, 4),
(3, 5);

-- 6. Initial Units & Conversions
INSERT INTO units (id, shop_id, name, symbol, is_base_unit, decimal_places) VALUES
(1, 1, 'Bag', 'Bag', 1, 0),
(2, 1, 'Kilogram', 'KG', 1, 2),
(3, 1, 'Metric Ton', 'Ton', 0, 3),
(4, 1, 'Cubic Feet', 'CFT', 1, 2),
(5, 1, 'Piece', 'Piece', 1, 0),
(6, 1, 'Bundle', 'Bundle', 0, 0),
(7, 1, 'Box', 'Box', 1, 0),
(8, 1, 'Feet', 'Ft', 1, 2),
(9, 1, 'Meter', 'Mtr', 1, 2),
(10, 1, 'Liter', 'Ltr', 1, 2);

-- Conversion factors: 1 Ton = 1000 KG, 1 Bundle = 100 Piece (approx/standard for hardware/wire)
INSERT INTO unit_conversions (shop_id, from_unit_id, to_unit_id, conversion_factor) VALUES
(1, 3, 2, 1000.000000), -- 1 Ton = 1000 KG
(1, 2, 3, 0.001000),    -- 1 KG = 0.001 Ton
(1, 6, 5, 100.000000);  -- 1 Bundle = 100 Piece

-- 7. Initial Categories
INSERT INTO categories (id, shop_id, name, code, description, is_active) VALUES
(1, 1, 'Cement', 'CAT-CEM', 'Portland Composite and Pozzolana Cement bags', 1),
(2, 1, 'Rod / Rebar (MS Deformed Bar)', 'CAT-ROD', '500W / 400D TMT Rebar steel rods of various diameters', 1),
(3, 1, 'Sand (Balu)', 'CAT-SND', 'Sylhet Sand (Coarse), Local Sand, Filling Sand', 1),
(4, 1, 'Bricks & Blocks', 'CAT-BRK', 'Auto Bricks, 1st Class Gas-Burned Bricks, Picket Bricks, Concrete Blocks', 1),
(5, 1, 'Stone / Aggregate (Khoa)', 'CAT-STN', 'Bholaganj Stone Chips, Crushed Boulder Stone, Brick Khoa', 1),
(6, 1, 'Binding Wire & Nails', 'CAT-WIR', 'GI Binding Wire, Steel Wire, Construction Wire Nails', 1),
(7, 1, 'Pipes & Fittings', 'CAT-PIP', 'PVC Pipes, GI Pipes, CPVC Plumbing & Drainage Fittings', 1),
(8, 1, 'Hardware & Tools', 'CAT-HDW', 'Trowels, Shovels, Headpans, Construction Safety & Tools', 1);

-- 8. Initial Brands
INSERT INTO brands (id, shop_id, name, code, origin_country, is_active) VALUES
(1, 1, 'Shah Cement', 'BRD-SHAH', 'Bangladesh', 1),
(2, 1, 'Bashundhara Cement', 'BRD-BASH', 'Bangladesh', 1),
(3, 1, 'Fresh Cement', 'BRD-FRSH', 'Bangladesh', 1),
(4, 1, 'Crown Cement', 'BRD-CRWN', 'Bangladesh', 1),
(5, 1, 'Seven Rings Cement', 'BRD-7RNG', 'Bangladesh', 1),
(6, 1, 'BSRM Steels', 'BRD-BSRM', 'Bangladesh', 1),
(7, 1, 'AKS (Abul Khair Steel)', 'BRD-AKS', 'Bangladesh', 1),
(8, 1, 'KSRM Rebar', 'BRD-KSRM', 'Bangladesh', 1),
(9, 1, 'GPH Ispat', 'BRD-GPH', 'Bangladesh', 1),
(10, 1, 'Anwar Ispat', 'BRD-ANW', 'Bangladesh', 1),
(11, 1, 'Local / Natural Resource', 'BRD-LOC', 'Bangladesh', 1);

-- 9. Initial Products
INSERT INTO products (id, shop_id, category_id, brand_id, base_unit_id, sku, name, description, purchase_price, selling_price, min_selling_price, current_stock, min_stock, average_cost, is_active) VALUES
(1, 1, 1, 1, 1, 'CEM-SHAH-PCC', 'Shah Cement Special (PCC) 50kg', 'Portland Composite Cement 50kg Bag', 500.00, 540.00, 520.00, 500.0000, 50.0000, 500.0000, 1),
(2, 1, 1, 2, 1, 'CEM-BASH-PCC', 'Bashundhara Cement (PCC) 50kg', 'High-strength cement for structural casting', 495.00, 535.00, 515.00, 400.0000, 40.0000, 495.0000, 1),
(3, 1, 1, 3, 1, 'CEM-FRSH-PCC', 'Fresh Supermix Cement 50kg', 'Portland Composite Cement', 490.00, 530.00, 510.00, 350.0000, 35.0000, 490.0000, 1),
(4, 1, 1, 4, 1, 'CEM-CRWN-PCC', 'Crown Cement PCC 50kg', 'Premium quality composite cement', 495.00, 535.00, 515.00, 300.0000, 30.0000, 495.0000, 1),
(5, 1, 2, 6, 2, 'ROD-BSRM-500W-16MM', 'BSRM Ultima 500W (16mm / 5 Suta)', 'High grade 500W TMT steel rebar per KG', 92.00, 97.00, 95.00, 10000.0000, 1000.0000, 92.0000, 1),
(6, 1, 2, 6, 2, 'ROD-BSRM-500W-12MM', 'BSRM Ultima 500W (12mm / 4 Suta)', 'High grade 500W TMT steel rebar per KG', 92.00, 97.00, 95.00, 8000.0000, 1000.0000, 92.0000, 1),
(7, 1, 2, 6, 2, 'ROD-BSRM-500W-10MM', 'BSRM Ultima 500W (10mm / 3 Suta)', 'High grade 500W TMT steel rebar per KG', 93.00, 98.00, 96.00, 7000.0000, 1000.0000, 93.0000, 1),
(8, 1, 2, 7, 2, 'ROD-AKS-500W-16MM', 'AKS 500W TMT Rebar (16mm)', 'High yield strength thermo-mechanically treated bar', 90.00, 95.00, 93.00, 9000.0000, 1000.0000, 90.0000, 1),
(9, 1, 2, 7, 2, 'ROD-AKS-500W-12MM', 'AKS 500W TMT Rebar (12mm)', 'High yield strength thermo-mechanically treated bar', 90.00, 95.00, 93.00, 8500.0000, 1000.0000, 90.0000, 1),
(10, 1, 3, 11, 4, 'SND-SYL-COARSE', 'Sylhet Coarse Sand (2.5 FM)', 'High FM coarse red sand for RCC casting per CFT', 42.00, 52.00, 48.00, 5000.0000, 500.0000, 42.0000, 1),
(11, 1, 3, 11, 4, 'SND-LOC-MED', 'Local Medium Sand (1.5 FM)', 'Medium sand for plastering and brick masonry per CFT', 28.00, 38.00, 34.00, 6000.0000, 500.0000, 28.0000, 1),
(12, 1, 4, 11, 5, 'BRK-1ST-AUTO', '1st Class Gas Auto Bricks (Standard)', 'Machine-pressed uniform red clay bricks', 11.50, 13.50, 12.80, 40000.0000, 5000.0000, 11.5000, 1),
(13, 1, 4, 11, 5, 'BRK-PCK-BRK', 'Picket Bricks (Hard Clay)', 'Heavy burnt picket bricks for stone-equivalent chips', 12.00, 14.00, 13.20, 25000.0000, 3000.0000, 12.0000, 1),
(14, 1, 5, 11, 4, 'STN-BHL-3_4', 'Bholaganj Black Stone Chips 3/4 Inch', 'Crushed stone aggregate for heavy load RCC casting', 140.00, 165.00, 155.00, 4000.0000, 400.0000, 140.0000, 1),
(15, 1, 6, 11, 2, 'WIR-GI-BIND-20G', 'GI Binding Wire (20 Gauge)', 'Soft galvanized binding wire for rebar tying per KG', 110.00, 135.00, 125.00, 1500.0000, 100.0000, 110.0000, 1);

-- Record Opening Stock Movement for all initial products
INSERT INTO stock_movements (shop_id, product_id, movement_type, reference_type, reference_id, reference_no, quantity, unit_id, base_quantity, unit_cost, balance_after, user_id, notes)
SELECT 1, id, 'OPENING_STOCK', 'SYSTEM_INIT', 1, 'INIT-STOCK', current_stock, base_unit_id, current_stock, average_cost, current_stock, 1, 'Initial warehouse opening stock setup'
FROM products;

-- 10. Sample Customers with Opening Dues / Advances
INSERT INTO customers (id, shop_id, customer_code, name, mobile, address, customer_type, credit_limit, opening_due, opening_advance, current_due, current_advance, status, notes) VALUES
(1, 1, 'CUST-001', 'Abdul Karim & Sons', '01711111111', 'Hospital Road, Habiganj Sadar', 'contractor', 500000.00, 85000.00, 0.00, 85000.00, 0.00, 'active', 'Residential building contractor in Sadar'),
(2, 1, 'CUST-002', 'Rahim Construction Ltd', '01822222222', 'Court Station Road, Habiganj', 'builder', 1000000.00, 0.00, 50000.00, 0.00, 50000.00, 'active', 'Multi-storey commercial builder'),
(3, 1, 'CUST-003', 'Engr. Mahbubur Rahman', '01933333333', 'Shaestanagar, Habiganj', 'retail', 100000.00, 12500.00, 0.00, 12500.00, 0.00, 'active', 'Personal home construction'),
(4, 1, 'CUST-004', 'Al-Amin Developers', '01744444444', 'Madhabpur Highway, Habiganj', 'dealer', 750000.00, 0.00, 0.00, 0.00, 0.00, 'active', 'Sub-dealer and local supplier'),
(5, 1, 'CUST-005', 'Haji Nurul Islam', '01855555555', 'Nabiganj Bazaar, Habiganj', 'retail', 50000.00, 0.00, 20000.00, 0.00, 20000.00, 'active', 'Advance deposited for cement & rod');

-- Customer Ledger Opening Balance entries
INSERT INTO customer_ledger (shop_id, customer_id, transaction_date, transaction_type, reference_type, reference_id, reference_no, description, debit, credit, running_balance, user_id) VALUES
(1, 1, '2026-01-01', 'OPENING_BALANCE', 'OPENING', 1, 'OP-BAL-001', 'Opening receivable balance migrated from previous ledger', 85000.00, 0.00, 85000.00, 1),
(1, 3, '2026-01-01', 'OPENING_BALANCE', 'OPENING', 3, 'OP-BAL-003', 'Opening receivable balance migrated from previous ledger', 12500.00, 0.00, 12500.00, 1);

-- Customer Advance records for opening deposits
INSERT INTO customer_advances (id, shop_id, customer_id, advance_no, receipt_date, amount, adjusted_amount, refunded_amount, remaining_balance, payment_method, account_type, account_id, status, notes, created_by) VALUES
(1, 1, 2, 'ADV-2026-000001', '2026-01-01', 50000.00, 0.00, 0.00, 50000.00, 'Cash', 'cash', 1, 'active', 'Opening advance deposit', 1),
(2, 1, 5, 'ADV-2026-000002', '2026-01-01', 20000.00, 0.00, 0.00, 20000.00, 'bKash', 'bank', 2, 'active', 'Advance received via bKash Merchant', 1);

-- 11. Sample Suppliers with Opening Dues
INSERT INTO suppliers (id, shop_id, supplier_code, name, company_name, mobile, address, opening_due, opening_advance, current_due, current_advance, status, notes) VALUES
(1, 1, 'SUPP-001', 'Shah Cement Regional Agency', 'Shah Cement Industries Ltd', '01712345678', 'South Surma Depot, Sylhet', 250000.00, 0.00, 250000.00, 0.00, 'active', 'Main distributor for Shah Cement'),
(2, 1, 'SUPP-002', 'BSRM Sylhet Regional Depot', 'BSRM Steels Limited', '01812345678', 'Kadamtoli Steel Market, Sylhet', 450000.00, 0.00, 450000.00, 0.00, 'active', 'Primary TMT rebar source'),
(3, 1, 'SUPP-003', 'Surma Auto Bricks Syndicate', 'Surma Auto Bricks Industries', '01912345678', 'Bahubal, Habiganj', 80000.00, 0.00, 80000.00, 0.00, 'active', 'Direct brick kiln supplier'),
(4, 1, 'SUPP-004', 'Jaflong Stone Crushers Hub', 'M/S Jaflong Stone Traders', '01798765432', 'Jaflong, Sylhet', 0.00, 30000.00, 0.00, 30000.00, 'active', 'Advance paid for stone quarry load');

-- Supplier Ledger Opening Balance
INSERT INTO supplier_ledger (shop_id, supplier_id, transaction_date, transaction_type, reference_type, reference_id, reference_no, description, debit, credit, running_balance, user_id) VALUES
(1, 1, '2026-01-01', 'OPENING_BALANCE', 'OPENING', 1, 'SUPP-OP-001', 'Opening payable to Shah Cement distributor', 0.00, 250000.00, 250000.00, 1),
(1, 2, '2026-01-01', 'OPENING_BALANCE', 'OPENING', 2, 'SUPP-OP-002', 'Opening payable to BSRM Steels depot', 0.00, 450000.00, 450000.00, 1),
(1, 3, '2026-01-01', 'OPENING_BALANCE', 'OPENING', 3, 'SUPP-OP-003', 'Opening payable for brick loads', 0.00, 80000.00, 80000.00, 1);

-- 12. Cash & Bank Accounts
INSERT INTO cash_accounts (id, shop_id, account_name, opening_balance, current_balance, is_default, status) VALUES
(1, 1, 'Main Cash Drawer (দোকানের মূল ক্যাশ)', 150000.00, 150000.00, 1, 'active');

INSERT INTO bank_accounts (id, shop_id, account_title, account_type, bank_name, account_number, branch_name, opening_balance, current_balance, status) VALUES
(1, 1, 'Maruf Traders - Islami Bank CD Account', 'bank', 'Islami Bank Bangladesh PLC', '2050123456789012', 'Habiganj Branch', 350000.00, 350000.00, 'active'),
(2, 1, 'Maruf Traders - bKash Merchant Wallet', 'bkash', 'bKash Merchant', '01711000000', 'Digital Wallet', 45000.00, 45000.00, 'active'),
(3, 1, 'Maruf Traders - Nagad Merchant Wallet', 'nagad', 'Nagad Merchant', '01811000000', 'Digital Wallet', 25000.00, 25000.00, 'active');

-- Opening Balance Financial Transactions
INSERT INTO financial_transactions (shop_id, transaction_no, transaction_date, account_type, account_id, transaction_nature, category, reference_type, reference_id, reference_no, amount, balance_after, notes, created_by) VALUES
(1, 'TXN-2026-000001', '2026-01-01', 'cash', 1, 'INFLOW', 'OPENING_BALANCE', 'OPENING', 1, 'OP-CASH-01', 150000.00, 150000.00, 'Shop physical cash opening balance', 1),
(1, 'TXN-2026-000002', '2026-01-01', 'bank', 1, 'INFLOW', 'OPENING_BALANCE', 'OPENING', 1, 'OP-BANK-01', 350000.00, 350000.00, 'Islami Bank opening balance', 1),
(1, 'TXN-2026-000003', '2026-01-01', 'bank', 2, 'INFLOW', 'OPENING_BALANCE', 'OPENING', 2, 'OP-BKASH-01', 45000.00, 45000.00, 'bKash merchant wallet balance', 1),
(1, 'TXN-2026-000004', '2026-01-01', 'bank', 3, 'INFLOW', 'OPENING_BALANCE', 'OPENING', 3, 'OP-NAGAD-01', 25000.00, 25000.00, 'Nagad merchant wallet balance', 1);

-- Keep document sequences ahead of the opening documents inserted above.
UPDATE document_sequences
SET current_number = CASE code
    WHEN 'ADVANCE' THEN 2
    WHEN 'TRANSACTION' THEN 4
    ELSE current_number
END
WHERE shop_id = 1 AND fiscal_year = '2026';

-- 13. Expense & Income Categories
INSERT INTO expense_categories (id, shop_id, name, description, is_active) VALUES
(1, 1, 'Shop Rent (দোকান ও গুদাম ভাড়া)', 'Monthly rent for shop and warehouse premises', 1),
(2, 1, 'Staff Salaries & Allowances (কর্মচারীদের বেতন)', 'Monthly wages and allowances for sales and delivery staff', 1),
(3, 1, 'Electricity & Utilities (বিদ্যুৎ ও ইউটিলিটি বিল)', 'Electricity, water, and municipal utility bills', 1),
(4, 1, 'Transport & Freight (মালামাল পরিবহন ভাড়া)', 'Truck, pickup, and trolley transport costs', 1),
(5, 1, 'Loading & Unloading Labor (লেবার/কুলি খরচ)', 'Labor costs for loading/unloading rod, cement, sand, and bricks', 1),
(6, 1, 'Shop Maintenance & Repairs (মেরামত ও রক্ষণাবেক্ষণ)', 'Hardware, electrical, and structural upkeep of shop', 1),
(7, 1, 'Entertainment & Tea (চা-নাস্তা ও আপ্যায়ন)', 'Customer and official visitor entertainment expenses', 1),
(8, 1, 'Internet & Mobile Bills (ইন্টারনেট ও ফোন বিল)', 'Broadband internet and company phone recharge', 1),
(9, 1, 'Other Miscellaneous Expense (অন্যান্য বিবিধ খরচ)', 'Unforeseen operational overheads', 1);

INSERT INTO income_categories (id, shop_id, name, description, is_active) VALUES
(1, 1, 'Cement Bag Empty Sack Sales (খালি বস্তা বিক্রি)', 'Revenue from selling empty cement paper/poly sacks', 1),
(2, 1, 'Transportation Commission / Markup (পরিবহন কমিশন)', 'Commission earned from arranged customer transports', 1),
(3, 1, 'Scrap & Steel Waste Sales (স্ক্র্যাপ ও ওয়েস্টেজ বিক্রি)', 'Sale of cut rod remnants and metal scrap', 1),
(4, 1, 'Other Operating Income (অন্যান্য আয়)', 'Miscellaneous non-sales revenue', 1);

-- 14. Key Settings
INSERT INTO settings (shop_id, setting_key, setting_value, is_system) VALUES
(1, 'theme_mode', 'system', 1),
(1, 'primary_color', '#1e40af', 1),
(1, 'enable_tax', '0', 0),
(1, 'tax_rate', '0', 0),
(1, 'print_format', 'a4_dual', 1),
(1, 'top_selling_period_days', '30', 1),
(1, 'auto_print_memo', '1', 1),
(1, 'rod_unit_id', '2', 1),
(1, 'cement_unit_id', '1', 1),
(1, 'sand_unit_id', '4', 1),
(1, 'brick_unit_id', '5', 1);

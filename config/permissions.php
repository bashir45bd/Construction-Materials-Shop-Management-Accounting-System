<?php
/**
 * Permissions Matrix Definition
 * Construction Materials Shop Management & Accounting System
 */

return [
    'dashboard' => [
        'name' => 'Dashboard',
        'permissions' => [
            'dashboard.view' => 'View Overview & KPI Analytics'
        ]
    ],
    'sales' => [
        'name' => 'Sales & Memos',
        'permissions' => [
            'sales.create' => 'Create & Print Sales Memos',
            'sales.view'   => 'View Sales History & Invoices',
            'sales.edit'   => 'Edit Sales Memo',
            'sales.cancel' => 'Cancel / Void Sales Memo',
            'sales.return' => 'Accept Sales Return'
        ]
    ],
    'purchases' => [
        'name' => 'Purchases & Procurement',
        'permissions' => [
            'purchase.create' => 'Create Purchase Voucher',
            'purchase.view'   => 'View Purchase History',
            'purchase.edit'   => 'Edit Purchase Voucher',
            'purchase.return' => 'Process Purchase Return to Supplier'
        ]
    ],
    'inventory' => [
        'name' => 'Inventory & Products',
        'permissions' => [
            'inventory.view'   => 'View Product List & Current Stock',
            'inventory.manage' => 'Add/Edit Products, Categories, Brands & Units',
            'inventory.adjust' => 'Perform Stock Adjustments (Damage, Loss, Surplus)'
        ]
    ],
    'customers' => [
        'name' => 'Customers & Receivables',
        'permissions' => [
            'customer.view'    => 'View Customer Directory & Profiles',
            'customer.manage'  => 'Create and Edit Customer Records',
            'customer.payment' => 'Collect Customer Due Payments',
            'customer.advance' => 'Accept & Adjust Customer Advance Deposits'
        ]
    ],
    'suppliers' => [
        'name' => 'Suppliers & Payables',
        'permissions' => [
            'supplier.view'    => 'View Supplier Directory & Profiles',
            'supplier.manage'  => 'Create and Edit Supplier Records',
            'supplier.payment' => 'Disburse Supplier Payments & Advances'
        ]
    ],
    'finance' => [
        'name' => 'Finance, Cash & Banking',
        'permissions' => [
            'finance.view'    => 'View Cash Drawers & Bank/MFS Accounts',
            'finance.income'  => 'Record Non-Sales Incomes',
            'finance.expense' => 'Record Overhead & Operational Expenses'
        ]
    ],
    'reports' => [
        'name' => 'Reports & Analytics',
        'permissions' => [
            'reports.sales'    => 'Access Sales & Customer Sales Reports',
            'reports.purchase' => 'Access Procurement & Supplier Reports',
            'reports.stock'    => 'Access Stock Movement & Valuation Reports',
            'reports.due'      => 'Access Customer & Supplier Due/Advance Statements',
            'reports.profit'   => 'Access Profit & Loss (Income Statement) Reports'
        ]
    ],
    'calculators' => [
        'name' => 'Construction Calculators',
        'permissions' => [
            'calculators.use' => 'Use Rod, Cement, Sand, Brick & Concrete Calculators'
        ]
    ],
    'settings' => [
        'name' => 'Settings & Administration',
        'permissions' => [
            'settings.manage' => 'Configure Shop Information, Memos & Preferences',
            'users.manage'    => 'Manage Users, Roles & RBAC Matrix',
            'backup.manage'   => 'Generate System Backups & Import Data'
        ]
    ]
];

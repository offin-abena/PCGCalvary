<?php

/**
 * Maps receipts/payments categories to account treatment for
 * Income & Expenditure vs Statement of Financial Position.
 */
return [
    'revenue_income' => [
        'Tithe',
        'Offering',
        'Donation',
        'Pledge',
        'Fundraising',
        'Offertory',
        'Thanksgiving',
        'Welfare Dues',
        'Welfare Contributions',
        'Special Offerings',
        'Interest Received',
        'Interest Income',
        'Collections',
        'Harvest',
        'Anniversary Offerings',
        'Mission Offerings',
        'Sunday School Offerings',
        'Men Fellowship Dues',
        'Women Fellowship Dues',
        'Youth Dues',
        'Rent Income',
        'Hall Hire',
    ],

    'capital_receipts' => [
        'Building Fund',
        'Asset Donations',
        'Capital Donations',
        'Loans Received',
        'Sale of Fixed Assets',
        'Project Donations',
        'Seed Money',
        'Equipment Donations',
        'Vehicle Donations',
    ],

    'revenue_expenditure' => [
        'Salaries',
        'Utilities',
        'Maintenance',
        'Outreach',
        'Supplies',
        'Events',
        'Allowances',
        'Transport',
        'Repairs',
        'Stationery',
        'Welfare Expenses',
        'Administrative Expenses',
        'Honorarium',
        'Insurance',
        'Communication',
        'Printing',
        'Cleaning',
        'Security',
        'Programs',
        'Ministry Expenses',
        'Missions',
        'Refreshments',
        'Professional Fees',
        'Bank Charges',
        'Subscriptions',
    ],

    'capital_payments' => [
        'Land Purchase',
        'Building Construction',
        'Building Project',
        'Vehicles',
        'Sound Equipment',
        'Computers',
        'Equipment Purchase',
        'Fixed Asset Purchase',
        'Furniture Purchase',
        'Renovation',
        'Generator',
        'PA System',
    ],

    'excluded_non_operating' => [
        'Opening Cash Balance',
        'Opening Bank Balance',
        'Transfer Between Accounts',
        'Account Transfer',
        'Inter Account Transfer',
        'Cash Transfer',
        'Bank Transfer',
    ],

    'bank_name_keywords' => [
        'bank',
        'gcb',
        'ecobank',
        'stanbic',
        'fidelity',
        'zenith',
        'cal bank',
        'savings',
        'current account',
    ],

    'depreciation_rates' => [
        'Non Current Assets' => 0.10,
        'Equipment & Furniture' => 0.10,
        'Furniture' => 0.10,
        'Computers' => 0.20,
        'Sound Equipment' => 0.20,
        'Equipment' => 0.20,
        'Vehicles' => 0.25,
        'Land & Buildings' => 0.02,
        'Other Assets' => 0.10,
    ],

    'non_current_asset_categories' => [
        'Non Current Assets',
        'Land & Buildings',
        'Equipment & Furniture',
        'Investments',
        'Other Assets',
    ],

    'cash_asset_categories' => [
        'Current Assets',
        'Cash & Bank',
        'Cash',
        'Bank',
    ],

    'receivable_asset_categories' => [
        'Current Assets',
        'Accounts Receivable',
    ],

    'fixed_asset_categories' => [
        'Non Current Assets',
        'Land & Buildings',
        'Equipment & Furniture',
        'Investments',
        'Other Assets',
    ],

    'fund_categories' => [
        'Funds',
    ],

    'liability_asset_categories' => [
        'Current Liabilities',
    ],

    'depreciable_asset_categories' => [
        'Non Current Assets',
        'Land & Buildings',
        'Equipment & Furniture',
        'Investments',
        'Other Assets',
    ],

    'unusual_amount_multiplier' => 3.0,
];

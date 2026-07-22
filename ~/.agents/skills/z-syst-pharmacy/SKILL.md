---
name: z-syst-pharmacy
description: "Z-Syst Pharmacy Management System - Comprehensive rules for building a professional pharmacy management system using Laravel. Covers products, stock, purchases, sales, invoices, prescriptions, finance, reports, and SaaS expansion."
---

# Z-Syst Pharmacy Management System

Comprehensive development rules for building a professional pharmacy management system using Laravel.

## Core Principles

### Ultimate Goal
- Build a **professional pharmacy system** ready for real-world use
- Strong management of: Products, Stock, Purchases, Sales, Prescriptions, Finance, and Reports
- Suitable for a real pharmacy first, then expandable to SaaS / multi-branch

### Development Order (CRITICAL)
1. **Products** - Foundation
2. **Stock** - Foundation
3. **Purchases** - Operations
4. **Sales** - Operations
5. **Invoices** - Operations
6. **Prescriptions** - Pharmacy-specific
7. **Finance** - Accounting
8. **Reports** - Analytics
9. **Analytics** - Intelligence
10. **SaaS Features** - Expansion

## Engineering Rules

### 1. Database First
- Design migrations before ANY business logic
- Key tables: `stock_batches`, `stock_movements`, `purchase_orders`, `invoices`, `prescriptions`, `financial_transactions`
- Add `company_id` and `branch_id` to important tables
- Index fields: `barcode`, `sku`, `status`, `expiry_date`, `company_id`, `branch_id`

### 2. Service Layer (MUST)
- Business logic in **Services**, NOT Controllers
- Controllers only: validate → call service → return response
- Each module: Service, FormRequest, Resource, Policy

### 3. Stock Management
- FEFO (First Expiry, First Out) for dispensing
- Every stock change logged in `stock_movements`
- No negative stock (unless justified)
- Track batch numbers + expiry dates always

### 4. Traceability
- All financial transactions auditable
- Sales/Purchases/Returns must reflect in stock AND finance
- Soft deletes on critical records

### 5. Testing
- Core tests: stock movement, sale, purchase receipt, return
- Feature tests for workflows
- Real behavior over mocks

## What to AVOID
- ❌ UI before logic is clear
- ❌ Features without DB support
- ❌ Business logic in Controllers
- ❌ Ignoring stock/finance integrity
- ❌ Over-engineering early

## Module Structure
```
app/Modules/{Module}/
├── Controllers/{Module}Controller.php
├── Models/{Module}.php
├── Services/{Module}Service.php
├── Requests/Store{Module}Request.php
├── Resources/{Module}Resource.php
├── Policies/{Module}Policy.php
└── routes/api.php
```

## Final Rule
When working on Z-Syst Pharmacy:
1. Start from database
2. Follow development order (Products → Stock → Purchases → Sales → Invoices → Prescriptions → Finance → Reports → SaaS)
3. Keep logic in Services
4. Ensure traceability
5. Keep it simple for real pharmacy use first

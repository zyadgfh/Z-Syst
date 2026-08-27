# API Documentation: Recall, Traceability & Accounting

> **Base URL:** `https://your-domain.com/api/v1`  
> **Auth:** Sanctum Bearer Token (`Authorization: Bearer {token}`)  
> **Tenant:** All endpoints require the `X-Business-Id` header (handled by `business.context` middleware)  
> **Rate Limit:** 20 requests/minute (applied by `throttle:20,1` middleware)

---

## Table of Contents

1. [Authentication & Tenancy](#1-authentication--tenancy)
2. [Accounting — Double-Entry Bookkeeping](#2-accounting--double-entry-bookkeeping)
   - [Accounts](#21-accounts)
   - [Journal Entries](#22-journal-entries)
   - [Financial Reports](#23-financial-reports)
3. [Traceability & Recall — API Endpoints](#3-traceability--recall--api-endpoints)
   - [Batch Lots](#31-batch-lots)
   - [Recalls](#32-recalls)
   - [Recall Workflow — Quarantine / Release / Dispose](#33-recall-workflow--quarantine--release--dispose)
   - [Traceability Logs & Detection](#34-traceability-logs--detection)
   - [Batch Expiry Monitoring](#35-batch-expiry-monitoring)
4. [Traceability & Recall — Admin Web Routes](#4-traceability--recall--admin-web-routes)
5. [Artisan Commands](#5-artisan-commands)
6. [Error Handling](#6-error-handling)
7. [Data Models Reference](#7-data-models-reference)

---

## 1. Authentication & Tenancy

All API endpoints live behind `auth:sanctum` and `business.context` middleware.

```
Authorization: Bearer {sanctum_token}
X-Business-Id: {business_id}
Content-Type: application/json
```

### Tenant Isolation

Every query is scoped to the authenticated user's `business_id`. Cross-business access returns `403 Forbidden`.

---

## 2. Accounting — Double-Entry Bookkeeping

> **Prefix:** `/accounting`  
> **Service:** `DoubleEntryService`  
> **Tables:** `account_types`, `accounts`, `journal_entries`, `journal_entry_lines`, `general_ledger`, `fiscal_periods`

### 2.1 Accounts

#### `GET /accounting/accounts`

List all chart-of-accounts for the current business.

**Response `200 OK`:**
```json
{
  "message": "Data fetched successfully.",
  "data": [
    {
      "id": 1,
      "business_id": 1,
      "account_type_id": 1,
      "code": "1000",
      "name": "Cash",
      "description": "Cash on hand",
      "opening_balance": 0,
      "is_active": true,
      "account_type": {
        "id": 1,
        "name": "asset",
        "is_debit_positive": true
      }
    }
  ]
}
```

---

#### `POST /accounting/accounts`

Create a new account in the chart of accounts.

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `account_type_id` | integer | ✅ | ID of the account type (`asset`, `liability`, `equity`, `revenue`, `expense`) |
| `code` | string(20) | ✅ | Unique account code (e.g. `"1000"`, `"4100"`) |
| `name` | string(100) | ✅ | Account name |
| `description` | string | — | Optional description |
| `opening_balance` | number | — | Initial balance (≥ 0) |

**Response `201 Created`:**
```json
{
  "message": "Account created successfully.",
  "data": {
    "id": 21,
    "code": "4100",
    "name": "Sales Revenue",
    "account_type": { "id": 4, "name": "revenue", "is_debit_positive": false }
  }
}
```

**Errors:** `422` Validation failure | `403` Forbidden

---

#### `GET /accounting/accounts/{id}`

Show a single account with its current balance.

**Response `200 OK`:**
```json
{
  "message": "Data fetched successfully.",
  "data": {
    "id": 1,
    "code": "1000",
    "name": "Cash",
    "current_balance": 15420.00,
    "account_type": { "id": 1, "name": "asset" }
  }
}
```

**Errors:** `404` Not found

---

### 2.2 Journal Entries

#### `GET /accounting/journal-entries`

List journal entries with optional filters and pagination.

**Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `status` | string | Filter by status: `draft`, `posted`, `voided` |
| `from` | date | Filter start date (use with `to`) |
| `to` | date | Filter end date (use with `from`) |
| `search` | string | Search by `entry_number` or `description` |
| `per_page` | integer | Results per page (default: 20) |

**Response `200 OK`:**
```json
{
  "message": "Data fetched successfully.",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "entry_number": "JE-20260001",
        "entry_date": "2026-08-25",
        "description": "Record cash sale",
        "status": "posted",
        "lines": [
          { "account_id": 1, "debit": 500.00, "credit": 0, "account": { "code": "1000", "name": "Cash" } },
          { "account_id": 10, "debit": 0, "credit": 500.00, "account": { "code": "4100", "name": "Sales Revenue" } }
        ],
        "creator": { "id": 1, "name": "Admin" },
        "poster": { "id": 1, "name": "Admin" }
      }
    ],
    "total": 1
  }
}
```

---

#### `POST /accounting/journal-entries`

Create a new journal entry (status: `draft`). Entry number is auto-generated sequentially.

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `description` | string(500) | ✅ | Entry description |
| `entry_date` | date | ✅ | Transaction date |
| `notes` | string | — | Optional notes |
| `branch_id` | integer | — | Optional branch reference |
| `reference_type` | string(100) | — | Polymorphic reference type |
| `reference_id` | integer | — | Polymorphic reference ID |
| `lines` | array | ✅ | Minimum 2 lines (min: 2) |
| `lines.*.account_id` | integer | ✅ | Account ID (must exist) |
| `lines.*.debit` | number | — | Debit amount (≥ 0) |
| `lines.*.credit` | number | — | Credit amount (≥ 0) |
| `lines.*.description` | string(500) | — | Line description |

> ⚠️ **Validation:** Total debits must equal total credits (tolerance: 0.01). Unbalanced entries throw `BusinessRuleException` with code `UNBALANCED_ENTRY`.

**Response `201 Created`:**
```json
{
  "message": "Journal entry created successfully.",
  "data": {
    "id": 5,
    "entry_number": "JE-20260005",
    "status": "draft",
    "lines": [ ... ]
  }
}
```

---

#### `GET /accounting/journal-entries/{id}`

Show a single journal entry with all lines and account details.

**Response `200 OK`:**
```json
{
  "message": "Data fetched successfully.",
  "data": {
    "id": 5,
    "entry_number": "JE-20260005",
    "description": "Purchase inventory on credit",
    "status": "draft",
    "lines": [
      { "account_id": 5, "debit": 1000.00, "credit": 0, "description": "Inventory", "account": { "code": "1200", "name": "Inventory" } },
      { "account_id": 15, "debit": 0, "credit": 1000.00, "description": "Accounts Payable", "account": { "code": "2100", "name": "Accounts Payable" } }
    ]
  }
}
```

---

#### `POST /accounting/journal-entries/{id}/post`

Post a draft journal entry. Moves it to `posted` status and updates the general ledger.

**Validations:**
- Entry must be in `draft` status → `422` `ONLY_DRAFT_POSTABLE`
- Entry must be balanced → `422` `UNBALANCED_ENTRY`

**Response `200 OK`:**
```json
{
  "message": "Journal entry posted successfully.",
  "data": {
    "id": 5,
    "entry_number": "JE-20260005",
    "status": "posted",
    "posted_at": "2026-08-25T14:30:00.000000Z"
  }
}
```

---

#### `POST /accounting/journal-entries/{id}/void`

Void a posted journal entry. Creates a reversal entry and marks the original as `voided`.

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `reason` | string(500) | — | Reason for voiding |

**Response `200 OK`:**
```json
{
  "message": "Journal entry voided successfully.",
  "data": {
    "voided_entry": {
      "id": 5,
      "status": "voided",
      "entry_number": "JE-20260005"
    },
    "reversal_entry": {
      "id": 6,
      "entry_number": "JE-20260006",
      "description": "VOID: Purchase inventory on credit — Error in amount",
      "status": "posted",
      "lines": [ ... ]
    }
  }
}
```

> ⚠️ The reversal entry is automatically posted. The debit/credit amounts from the original entry are **swapped**.

---

### 2.3 Financial Reports

#### `GET /accounting/trial-balance`

Generate a trial balance (all accounts with their debit/credit balances).

**Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `as_of_date` | date | Balance as of this date (default: today) |

**Response `200 OK`:**
```json
{
  "message": "Data fetched successfully.",
  "data": {
    "accounts": [
      {
        "account_id": 1,
        "code": "1000",
        "name": "Cash",
        "type": "asset",
        "opening_balance": 0,
        "total_debit": 15000.00,
        "total_credit": 5000.00,
        "balance": 10000.00,
        "balance_type": "debit"
      },
      {
        "account_id": 10,
        "code": "4100",
        "name": "Sales Revenue",
        "type": "revenue",
        "opening_balance": 0,
        "total_debit": 0,
        "total_credit": 15000.00,
        "balance": -15000.00,
        "balance_type": "credit"
      }
    ],
    "total_debit": 10000.00,
    "total_credit": 10000.00,
    "is_balanced": true,
    "as_of_date": "2026-08-25"
  }
}
```

> ✅ `is_balanced` should always be `true` for a correct ledger.

---

#### `GET /accounting/income-statement`

Generate an Income Statement (Profit & Loss) for a date range.

**Query Parameters:**

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `from` | date | ✅ | Period start date |
| `to` | date | ✅ | Period end date (≥ `from`) |

**Response `200 OK`:**
```json
{
  "message": "Data fetched successfully.",
  "data": {
    "period": { "from": "2026-01-01", "to": "2026-08-25" },
    "revenue": {
      "items": [
        { "account_id": 10, "code": "4100", "name": "Sales Revenue", "balance": 50000.00 },
        { "account_id": 11, "code": "4200", "name": "Service Revenue", "balance": 12000.00 }
      ],
      "total": 62000.00
    },
    "total_revenue": 62000.00,
    "expenses": {
      "items": [
        { "account_id": 20, "code": "5100", "name": "Cost of Goods Sold", "balance": 25000.00 },
        { "account_id": 21, "code": "5200", "name": "Rent Expense", "balance": 6000.00 }
      ],
      "total": 31000.00
    },
    "total_expenses": 31000.00,
    "net_income": 31000.00
  }
}
```

---

#### `GET /accounting/balance-sheet`

Generate a Balance Sheet as of a specific date.

**Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `as_of_date` | date | Balance date (default: today) |

**Response `200 OK`:**
```json
{
  "message": "Data fetched successfully.",
  "data": {
    "as_of_date": "2026-08-25",
    "assets": {
      "items": [
        { "account_id": 1, "code": "1000", "name": "Cash", "balance": 10000.00 },
        { "account_id": 2, "code": "1200", "name": "Inventory", "balance": 5000.00 }
      ],
      "total": 15000.00
    },
    "total_assets": 15000.00,
    "liabilities": {
      "items": [
        { "account_id": 15, "code": "2100", "name": "Accounts Payable", "balance": 3000.00 }
      ],
      "total": 3000.00
    },
    "total_liabilities": 3000.00,
    "equity": {
      "items": [
        { "account_id": 18, "code": "3000", "name": "Owner's Equity", "balance": 12000.00 }
      ],
      "total": 12000.00
    },
    "total_equity": 12000.00,
    "is_balanced": true
  }
}
```

> ✅ `is_balanced` verifies `Assets = Liabilities + Equity`.

---

#### `GET /accounting/general-ledger/{accountId}`

View the general ledger (all transactions) for a specific account.

**Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `from` | date | Filter from date |
| `to` | date | Filter to date (≥ `from`) |

**Response `200 OK`:**
```json
{
  "message": "Data fetched successfully.",
  "data": {
    "account": { "id": 1, "code": "1000", "name": "Cash", "type": "asset" },
    "opening_balance": 5000.00,
    "entries": [
      {
        "id": 1,
        "journal_entry_id": 1,
        "transaction_date": "2026-08-25",
        "debit": 500.00,
        "credit": 0,
        "balance": 5500.00,
        "journal_entry": { "entry_number": "JE-20260001", "description": "Cash sale" }
      }
    ],
    "closing_balance": 5500.00
  }
}
```

---

## 3. Traceability & Recall — API Endpoints

> **Prefix:** `/traceability`  
> **Service:** `TraceabilityService`  
> **Tables:** `batch_lots`, `recall_events`, `recall_affected_batches`, `traceability_logs`

### 3.1 Batch Lots

#### `GET /traceability/batch-lots`

List all batch/lot records for the business.

**Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `product_id` | integer | Filter by product ID |
| `days` | integer | Filter by creation window (1–365) |

**Response `200 OK`:**
```json
{
  "message": "Data fetched successfully.",
  "data": [
    {
      "id": 1,
      "batch_number": "BATCH-2026-001",
      "lot_number": "LOT-A1",
      "product_id": 5,
      "product": { "id": 5, "name": "Amoxicillin 500mg" },
      "quantity": 200,
      "status": "active",
      "manufacture_date": "2026-01-15",
      "expiry_date": "2028-01-15",
      "supplier_name": "PharmaCorp",
      "recall_date": null,
      "is_quarantined": false,
      "created_at": "2026-08-01T10:00:00.000000Z"
    }
  ]
}
```

---

### 3.2 Recalls

#### `GET /traceability/recalls`

List all recall events for the business.

**Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `status` | string | `active`, `resolved`, or `all` (default: all) |

**Response `200 OK`:**
```json
{
  "message": "Data fetched successfully.",
  "data": [
    {
      "id": 1,
      "reason": "Contamination detected in batch",
      "status": "active",
      "description": "Microbial contamination found during QC",
      "batch_lot_number": "BATCH-2026-001",
      "product_id": 5,
      "product": { "id": 5, "name": "Amoxicillin 500mg" },
      "user": { "id": 1, "name": "Admin" },
      "initiated_at": "2026-08-25T09:00:00.000000Z",
      "resolved_at": null,
      "duration_days": null,
      "affected_batches_count": 3,
      "total_quantity_affected": 600
    }
  ]
}
```

---

#### `POST /traceability/recalls`

Initiate a new recall. Automatically detects and links affected batches. Dispatches email/SMS notifications to affected customers.

**Request Body (validated by `InitiateRecallRequest`):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `product_id` | integer | — | Product ID (filters affected batch detection) |
| `batch_lot_number` | string(255) | — | Specific batch/lot number to recall |
| `reason` | string(255) | ✅ | Reason for the recall |
| `description` | string(2000) | — | Detailed description |

> **Note:** `business_id` and `user_id` are set automatically from the authenticated user.

**Response `201 Created`:**
```json
{
  "message": "Recall initiated successfully.",
  "data": {
    "id": 1,
    "reason": "Contamination detected",
    "status": "active",
    "product": { "id": 5, "name": "Amoxicillin 500mg" },
    "user": { "id": 1, "name": "Admin" },
    "initiated_at": "2026-08-25T09:00:00.000000Z",
    "affected_batches_count": 2,
    "total_quantity_affected": 400
  }
}
```

**Side Effects:**
- All matching active batches are linked via `recall_affected_batches` with `quarantine_status: pending`
- Each linked batch has its `recall_date` set
- A queued job `NotifyRecallAffectedCustomers` is dispatched to send email/SMS to customers who purchased the recalled product

---

#### `POST /traceability/recalls/{recall}/resolve`

Mark a recall as resolved.

**Response `200 OK`:**
```json
{
  "message": "Recall resolved successfully.",
  "data": {
    "id": 1,
    "status": "resolved",
    "resolved_at": "2026-08-26T12:00:00.000000Z"
  }
}
```

---

### 3.3 Recall Workflow — Quarantine / Release / Dispose

These endpoints manage the lifecycle of affected batches within a recall.

#### `GET /traceability/recalls/{recall}/summary`

Get a full recall summary with per-batch quarantine status breakdown.

**Response `200 OK`:**
```json
{
  "recall": {
    "id": 1,
    "reason": "Contamination detected",
    "status": "active",
    "initiated_at": "2026-08-25T09:00:00.000000Z",
    "resolved_at": null,
    "product_name": "Amoxicillin 500mg"
  },
  "affected_batches": [
    {
      "id": 10,
      "batch_number": "BATCH-2026-001",
      "lot_number": "LOT-A1",
      "product_name": "Amoxicillin 500mg",
      "quantity": 200,
      "expiry_date": "2028-01-15T00:00:00.000000Z",
      "quarantine_status": "quarantined",
      "quarantined_at": "2026-08-25T10:00:00.000000Z",
      "quantity_affected": 200
    },
    {
      "id": 11,
      "batch_number": "BATCH-2026-002",
      "lot_number": "LOT-B2",
      "product_name": "Amoxicillin 500mg",
      "quantity": 150,
      "expiry_date": "2027-06-01T00:00:00.000000Z",
      "quarantine_status": "pending",
      "quarantined_at": null,
      "quantity_affected": 150
    }
  ],
  "summary": {
    "total_batches": 2,
    "total_quantity_affected": 350,
    "quarantined_count": 1,
    "released_count": 0,
    "disposed_count": 0,
    "pending_count": 1
  }
}
```

---

#### `POST /traceability/recalls/{recall}/quarantine`

Quarantine an affected batch. Sets the batch status to `quarantined` and creates a traceability log entry.

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `batch_lot_id` | integer | ✅ | ID of the batch lot to quarantine |
| `notes` | string(1000) | — | Quarantine notes |

**Response `200 OK`:**
```json
{
  "message": "Batch quarantined successfully."
}
```

**Side Effects:**
- Pivot `quarantine_status` → `quarantined`, `quarantined_at` set
- Batch `status` → `quarantined`
- Traceability log entry created (type: `recall`)

---

#### `POST /traceability/recalls/{recall}/release`

Release a batch from quarantine back to active status.

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `batch_lot_id` | integer | ✅ | ID of the batch lot to release |

**Response `200 OK`:**
```json
{
  "message": "Batch released from quarantine."
}
```

**Side Effects:**
- Pivot `quarantine_status` → `released`, `resolved_at` set
- Batch `status` → `active`

---

#### `POST /traceability/recalls/{recall}/dispose`

Dispose of a batch. Sets quantity to 0 and status to `disposed`. Creates a traceability log entry.

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `batch_lot_id` | integer | ✅ | ID of the batch lot to dispose |
| `notes` | string(1000) | — | Disposal notes |

**Response `200 OK`:**
```json
{
  "message": "Batch disposed successfully."
}
```

**Side Effects:**
- Pivot `quarantine_status` → `disposed`, `resolved_at` set
- Batch `quantity` → `0`, `status` → `disposed`
- Traceability log entry created (type: `recall`)

---

### 3.4 Traceability Logs & Detection

#### `GET /traceability/traceability`

Query traceability logs for a product, optionally filtered by batch number and date range.

**Query Parameters:**

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `product_id` | integer | ✅ | Product to trace |
| `batch_lot_number` | string | — | Filter by specific batch/lot |
| `from_date` | date | — | Start of date range |
| `to_date` | date | — | End of date range (≥ `from_date`) |

**Response `200 OK`:**
```json
{
  "message": "Data fetched successfully.",
  "data": [
    {
      "id": 1,
      "type": "transfer",
      "type_label": "Warehouse Transfer",
      "product_id": 5,
      "batch_lot_number": "BATCH-2026-001",
      "quantity": 100,
      "from_warehouse": "Main Warehouse",
      "to_warehouse": "Branch Store",
      "user": "Admin",
      "notes": "Stock transfer #42",
      "created_at": "2026-08-20T14:00:00.000000Z"
    }
  ]
}
```

> **Traceability types:** `transfer`, `sale`, `purchase`, `adjustment`, `recall`

---

#### `POST /traceability/detect-affected`

Pre-scan to detect which batches would be affected by a recall scenario (without actually initiating one).

**Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `product_id` | integer | Filter by product |
| `batch_lot_number` | string | Filter by specific batch/lot number |

**Response `200 OK`:**
```json
{
  "message": "Data fetched successfully.",
  "data": [
    { "id": 10, "batch_number": "BATCH-2026-001", "product_name": "Amoxicillin 500mg", "quantity": 200, "status": "active" },
    { "id": 11, "batch_number": "BATCH-2026-002", "product_name": "Amoxicillin 500mg", "quantity": 150, "status": "active" }
  ],
  "total": 2
}
```

---

### 3.5 Batch Expiry Monitoring

#### `GET /traceability/expiring-batches`

List batches expiring within a given window.

**Query Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `days` | integer | Window in days (1–365, default: 30) |

**Response `200 OK`:**
```json
{
  "message": "Data fetched successfully.",
  "data": {
    "total_expiring_soon": 5,
    "batches": [
      {
        "id": 8,
        "identifier": "BATCH-2026-008",
        "product_name": "Paracetamol 250mg",
        "expiry_date": "2026-09-10T00:00:00.000000Z",
        "days_until_expiry": 16,
        "batch_number": "BATCH-2026-008",
        "lot_number": "LOT-P1"
      }
    ]
  }
}
```

---

#### `GET /traceability/expired-batches`

List all expired batches.

**Response `200 OK`:**
```json
{
  "message": "Data fetched successfully.",
  "data": {
    "total_expired": 3,
    "batches": [
      {
        "id": 3,
        "identifier": "BATCH-2026-003",
        "product_name": "Ibuprofen 400mg",
        "expiry_date": "2026-07-01T00:00:00.000000Z",
        "days_since_expiry": 55,
        "batch_number": "BATCH-2026-003",
        "lot_number": "LOT-I3",
        "is_recalled": false
      }
    ]
  }
}
```

---

## 4. Traceability & Recall — Admin Web Routes

> **Prefix:** `/admin/traceability`  
> **Middleware:** `auth`, `admin`, `clerk.auth`, plus permission-based middleware  
> **Permissions:** `traceability-read`, `traceability-create`, `traceability-update`, `traceability-delete`

These routes serve Blade views and return JSON for AJAX operations. They mirror the API endpoints above but are scoped to the admin panel.

| Method | URI | Controller Method | Permission | Description |
|--------|-----|-------------------|------------|-------------|
| `GET` | `/admin/traceability` | `index` | `traceability-read` | Dashboard view |
| `GET` | `/admin/traceability/batch-lots` | `batchLots` | `traceability-read` | List batch lots (paginated, searchable) |
| `POST` | `/admin/traceability/batch-lots` | `createBatchLot` | `traceability-create` | Create batch lot |
| `PUT` | `/admin/traceability/batch-lots/{batchLot}` | `updateBatchLot` | `traceability-update` | Update batch lot |
| `DELETE` | `/admin/traceability/batch-lots/{batchLot}` | `destroyBatchLot` | `traceability-delete` | Delete batch lot |
| `GET` | `/admin/traceability/recalls` | `recalls` | `traceability-read` | List recalls |
| `POST` | `/admin/traceability/recalls` | `initiateRecall` | `traceability-create` | Initiate recall |
| `POST` | `/admin/traceability/recalls/{recall}/resolve` | `resolveRecall` | `traceability-update` | Resolve recall |
| `DELETE` | `/admin/traceability/recalls/{recall}` | `destroyRecall` | `traceability-delete` | Delete recall |
| `GET` | `/admin/traceability/recalls/{recall}/summary` | `recallSummary` | — | Recall summary JSON |
| `POST` | `/admin/traceability/recalls/{recall}/quarantine` | `quarantineBatch` | — | Quarantine batch |
| `POST` | `/admin/traceability/recalls/{recall}/release` | `releaseBatch` | — | Release batch |
| `POST` | `/admin/traceability/recalls/{recall}/dispose` | `disposeBatch` | — | Dispose batch |
| `GET` | `/admin/traceability/detect-affected` | `detectAffectedBatches` | — | Pre-scan affected batches |
| `GET` | `/admin/traceability/product-traceability` | `getProductTraceability` | — | Full traceability chain |
| `GET` | `/admin/traceability/statistics` | `statistics` | — | Traceability stats |
| `GET` | `/admin/traceability/recall-statistics` | `recallStatistics` | — | Recall stats |
| `GET` | `/admin/traceability/expiring-batches` | `expiringBatches` | — | Expiring batches |
| `GET` | `/admin/traceability/expired-batches` | `expiredBatches` | — | Expired batches |

---

## 5. Artisan Commands

### `php artisan accounting:initialize`

Seeds account types, chart of accounts, and fiscal periods for existing businesses.

**Options:**

| Flag | Description |
|------|-------------|
| `--business={id}` | Initialize only a specific business |
| `--force` | Re-seed even if accounts already exist |

**Example:**
```bash
# Initialize all businesses
php artisan accounting:initialize

# Initialize a specific business with force re-seed
php artisan accounting:initialize --business=3 --force
```

**Output:**
```
📊 Initializing bookkeeping system...
  → Seeding account types...
  ✅ Account types seeded (Asset, Liability, Equity, Revenue, Expense).
  → Found 4 business(es).
  → Seeding chart of accounts for Business #1 (PharmaCorp)...
  ✅ Business #1 — 20 accounts created, fiscal period 'FY2026' initialized.
  ...
🎉 Bookkeeping initialization complete: 4 initialized, 0 skipped.
```

**Default Chart of Accounts (20 accounts):**

| Code | Name | Type |
|------|------|------|
| 1000 | Cash | Asset |
| 1100 | Bank Account | Asset |
| 1200 | Accounts Receivable | Asset |
| 1300 | Inventory | Asset |
| 1400 | Prepaid Expenses | Asset |
| 2000 | Accounts Payable | Liability |
| 2100 | Accrued Expenses | Liability |
| 2200 | Sales Tax Payable | Liability |
| 2300 | Loans Payable | Liability |
| 2400 | Unearned Revenue | Liability |
| 3000 | Owner's Equity | Equity |
| 3100 | Retained Earnings | Equity |
| 3200 | Current Year Earnings | Equity |
| 4100 | Sales Revenue | Revenue |
| 4200 | Service Revenue | Revenue |
| 4300 | Interest Income | Revenue |
| 5100 | Cost of Goods Sold | Expense |
| 5200 | Rent Expense | Expense |
| 5300 | Utilities Expense | Expense |
| 5400 | Salaries Expense | Expense |

---

## 6. Error Handling

All errors follow the standard Laravel error format:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "reason": ["Recall reason is required"],
    "batch_lot_id": ["The selected batch lot id is invalid."]
  }
}
```

### Business Rule Exceptions

Custom `BusinessRuleException` errors for accounting:

| Code | HTTP | Message |
|------|------|---------|
| `UNBALANCED_ENTRY` | 422 | Journal entry debits must equal credits |
| `ONLY_DRAFT_POSTABLE` | 422 | Only draft entries can be posted |
| `ONLY_POSTED_VOIDABLE` | 422 | Only posted entries can be voided |

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| `200` | Success |
| `201` | Created |
| `403` | Forbidden (tenant isolation or missing permission) |
| `404` | Not found |
| `422` | Validation error |
| `500` | Server error |

---

## 7. Data Models Reference

### `Account`

| Column | Type | Notes |
|--------|------|-------|
| `id` | integer | Primary key |
| `business_id` | integer | Tenant scope |
| `account_type_id` | integer | FK → `account_types` |
| `code` | string(20) | Unique per business |
| `name` | string(100) | Display name |
| `description` | string | Optional |
| `opening_balance` | decimal(15,2) | Starting balance |
| `is_active` | boolean | Default: `true` |

### `JournalEntry`

| Column | Type | Notes |
|--------|------|-------|
| `id` | integer | Primary key |
| `business_id` | integer | Tenant scope |
| `entry_number` | string | Auto-generated (e.g. `JE-20260001`) |
| `entry_date` | date | Transaction date |
| `description` | string | Entry description |
| `status` | enum | `draft`, `posted`, `voided` |
| `reference_type` | string | Polymorphic type |
| `reference_id` | integer | Polymorphic ID |
| `created_by` | integer | FK → `users` |
| `posted_by` | integer | FK → `users` |
| `posted_at` | timestamp | When posted |

### `BatchLot`

| Column | Type | Notes |
|--------|------|-------|
| `id` | integer | Primary key |
| `business_id` | integer | Tenant scope |
| `product_id` | integer | FK → `products` |
| `batch_number` | string | Batch identifier |
| `lot_number` | string | Lot identifier |
| `quantity` | integer | Current quantity |
| `status` | string | `active`, `quarantined`, `disposed`, `expired` |
| `manufacture_date` | date | When manufactured |
| `expiry_date` | date | When expires |
| `supplier_name` | string | Supplier name |
| `recall_date` | timestamp | When recalled (nullable) |

### `RecallEvent`

| Column | Type | Notes |
|--------|------|-------|
| `id` | integer | Primary key |
| `business_id` | integer | Tenant scope |
| `product_id` | integer | FK → `products` (nullable) |
| `batch_lot_number` | string | Specific batch (nullable) |
| `reason` | string(255) | Recall reason |
| `description` | string | Detailed description |
| `status` | string | `active`, `resolved` |
| `user_id` | integer | FK → `users` (initiator) |
| `initiated_at` | timestamp | When initiated |
| `resolved_at` | timestamp | When resolved |

### `recall_affected_batches` (Pivot)

| Column | Type | Notes |
|--------|------|-------|
| `recall_event_id` | integer | FK → `recall_events` |
| `batch_lot_id` | integer | FK → `batch_lots` |
| `quarantine_status` | string | `pending`, `quarantined`, `released`, `disposed` |
| `quantity_affected` | integer | Quantity affected |
| `quarantined_at` | timestamp | When quarantined |
| `resolved_at` | timestamp | When released/disposed |
| `notes` | text | Notes |

### `TraceabilityLog`

| Column | Type | Notes |
|--------|------|-------|
| `id` | integer | Primary key |
| `business_id` | integer | Tenant scope |
| `product_id` | integer | FK → `products` |
| `batch_lot_number` | string | Batch/lot reference |
| `type` | string | `transfer`, `sale`, `purchase`, `adjustment`, `recall` |
| `quantity` | integer | Quantity involved |
| `from_warehouse_id` | integer | FK → `warehouses` (nullable) |
| `to_warehouse_id` | integer | FK → `warehouses` (nullable) |
| `user_id` | integer | FK → `users` |
| `notes` | text | Description of the event |

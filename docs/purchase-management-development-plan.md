# Z-Syst Pharmacy - Advanced Purchase Management System Development Plan

## 📋 Executive Summary

This document outlines the complete development plan for transforming the basic purchase system into a comprehensive, professional-grade purchase management system following pharmacy industry standards and clean architecture principles.

---

## 🎯 Current State Analysis

### Existing Components
- ✅ `purchases` table - Basic purchase records
- ✅ `purchase_details` table - Purchase line items
- ✅ `purchase_returns` table - Return handling
- ✅ Basic Models (Purchase, PurchaseDetails, PurchaseReturn)
- ✅ Basic API Controllers
- ✅ Supplier data in `parties` table

### Limitations
- ❌ No Purchase Orders (PO) system
- ❌ No Goods Received Notes (GRN)
- ❌ No advanced supplier management
- ❌ No payment tracking
- ❌ No approval workflows
- ❌ No budget management
- ❌ No quality checks
- ❌ No advanced reporting

---

## 🚀 Development Plan (10 Major Systems)

### Phase 1: Purchase Orders System (PO) ⭐⭐⭐⭐⭐

**Status:** 🔄 IN PROGRESS

#### Deliverables:
- ✅ `purchase_orders` table
- ✅ `purchase_order_items` table
- ✅ `PurchaseOrder` model
- ✅ `PurchaseOrderItem` model
- ✅ `PurchaseOrderService`
- ⏳ `PurchaseOrderController` (Admin)
- ⏳ `PurchaseOrderController` (API)
- ⏳ `PurchaseOrderRequest`
- ⏳ `PurchaseOrderResource`
- ⏳ Routes
- ⏳ Views
- ⏳ Tests

#### Features:
- Draft, Sent, Accepted, Partially Received, Received, Cancelled, Rejected statuses
- Priority levels (Low, Normal, High, Urgent)
- Expected vs Actual delivery dates
- Multi-item support
- Approval workflow
- PO to Purchase conversion
- Auto-PO number generation

#### Status Flow:
```
Draft → Sent → Accepted → Partially Received → Received
                ↓
              Rejected
                ↓
              Cancelled
```

---

### Phase 2: GRN (Goods Received Note) System ⭐⭐⭐⭐⭐

#### Database Schema:
```sql
goods_received_notes
- id, purchase_order_id, supplier_id
- grn_number, received_date, location
- received_by, verified_by, verified_at
- notes, status, soft_deletes, timestamps

grn_items
- id, grn_id, product_id
- ordered_quantity, received_quantity
- accepted_quantity, rejected_quantity
- batch_number, expiry_date
- purchase_price, notes, timestamps

quality_checks
- id, grn_item_id, checker_id
- check_date, quality_status
- defects, damage_quantity
- temperature, humidity
- notes, photos, timestamps
```

#### Features:
- Separate GRN from PO
- Quality inspection
- Batch number assignment
- Expiry date entry
- Quantity verification
- Partial/damaged goods tracking
- Signature capture
- GRN to Stock integration

#### Deliverables:
- GRN models
- GRN service
- GRN controllers
- GRN views
- Quality check system
- Tests

---

### Phase 3: Advanced Supplier Management ⭐⭐⭐⭐⭐

#### Database Schema:
```sql
suppliers (dedicated table)
- id, business_id, branch_id
- company_name, contact_person, email, phone
- address, tax_id, license_number
- rating, performance_score
- payment_terms, credit_limit
- contract_start, contract_end
- is_active, notes, timestamps, soft_deletes

supplier_ratings
- id, supplier_id, business_id
- rating (1-5), category (delivery, quality, price)
- review, rated_by, rated_at, timestamps

supplier_contracts
- id, supplier_id, business_id
- contract_number, start_date, end_date
- terms, conditions, file_path
- status, signed_by, signed_at, timestamps

supplier_performance
- id, supplier_id, business_id
- on_time_delivery_rate, quality_score
- price_competitiveness, responsiveness
- total_orders, total_disputes
- calculated_at, timestamps
```

#### Features:
- Comprehensive supplier profiles
- Rating system (1-5 stars)
- Performance tracking
- Contract management
- Payment terms configuration
- Credit limit monitoring
- Multi-branch assignment
- Performance analytics

#### Deliverables:
- Supplier models
- Supplier service
- Supplier controllers
- Rating system
- Contract management
- Performance tracking
- Tests

---

### Phase 4: Supplier Payment Tracking ⭐⭐⭐⭐⭐

#### Database Schema:
```sql
supplier_payments
- id, supplier_id, business_id, branch_id
- payment_number, payment_date, payment_method
- amount, reference, bank_reference
- status, notes
- created_by, approved_by, approved_at
- timestamps, soft_deletes

supplier_invoices
- id, supplier_id, business_id
- invoice_number, invoice_date, due_date
- amount, tax, discount, total
- status, paid_amount, balance
- file_path, notes, timestamps

payment_schedules
- id, supplier_id, business_id
- scheduled_date, amount, status
- paid_date, payment_method
- notes, reminders_sent, timestamps

aging_reports
- id, supplier_id, business_id
- report_date, period_30, period_60
- period_90, period_90_plus, total
- generated_at, timestamps
```

#### Features:
- Payment reconciliation
- Outstanding balance tracking
- Payment schedules
- Credit limit monitoring
- Aging reports (30/60/90/90+ days)
- Payment reminders
- Bank integration
- Invoice management

#### Deliverables:
- Payment models
- Payment service
- Payment controllers
- Invoice management
- Aging reports
- Payment schedules
- Tests

---

### Phase 5: Credit/Debit Notes System ⭐⭐⭐⭐

#### Database Schema:
```sql
supplier_credits
- id, supplier_id, business_id
- credit_number, credit_date, type
- amount, reason, reference
- status, approved_by, approved_at
- notes, file_path
- timestamps, soft_deletes

supplier_debits
- id, supplier_id, business_id
- debit_number, debit_date, type
- amount, reason, reference
- status, approved_by, approved_at
- notes, file_path
- timestamps, soft_deletes

credit_debit_items
- id, parent_id, parent_type (credit/debit)
- product_id, quantity, unit_price
- amount, reason, notes, timestamps
```

#### Features:
- Credit notes from suppliers
- Debit notes to suppliers
- Price adjustments
- Quantity adjustments
- Tax adjustments
- Reason tracking
- Approval workflow
- Integration with stock

#### Deliverables:
- Credit/Debit models
- Credit/Debit service
- Credit/Debit controllers
- Approval workflow
- Integration with purchases
- Tests

---

### Phase 6: Approval Workflow System ⭐⭐⭐⭐

#### Database Schema:
```sql
approval_workflows
- id, type, entity_id, business_id
- current_step, status
- created_by, updated_by
- timestamps, soft_deletes

approval_steps
- id, workflow_id, step_number
- approver_id, approver_role
- status, approved_at, notes
- timestamps

approval_templates
- id, business_id, type
- name, description, steps_config
- is_active, is_default
- created_by, updated_by
- timestamps
```

#### Features:
- Multi-level approval
- Approval chains
- Approval history
- Rejection reasons
- Delegation of approval
- Notification system
- Template-based workflows
- Dynamic approvers

#### Deliverables:
- Workflow models
- Workflow service
- Workflow controllers
- Template system
- Notification integration
- Tests

---

### Phase 7: Budget Management ⭐⭐⭐

#### Database Schema:
```sql
purchase_budgets
- id, business_id, branch_id
- category_id, period, budget_amount
- spent_amount, remaining_amount
- start_date, end_date, status
- created_by, approved_by
- timestamps, soft_deletes

budget_alerts
- id, budget_id, business_id
- alert_type, threshold, alert_sent_at
- resolved_at, notes, timestamps

budget_transactions
- id, budget_id, purchase_id
- amount, transaction_date
- reference, notes, timestamps
```

#### Features:
- Purchase budget per category
- Budget vs Actual tracking
- Budget alerts
- Budget approval
- Periodic budgeting
- Budget variance analysis
- Multi-level budgets

#### Deliverables:
- Budget models
- Budget service
- Budget controllers
- Alert system
- Transaction tracking
- Tests

---

### Phase 8: Quality Checks System ⭐⭐⭐⭐

#### Database Schema:
```sql
quality_checks
- id, grn_item_id, checker_id
- check_date, quality_status
- defects, damage_quantity
- temperature, humidity
- notes, photos, timestamps

quality_standards
- id, business_id, category_id
- temperature_min, temperature_max
- humidity_min, humidity_max
- acceptable_defects, criteria
- is_active, timestamps

quality_reports
- id, business_id, branch_id
- report_date, supplier_id
- total_checks, passed, failed
- pass_rate, notes, timestamps
```

#### Features:
- Quality inspection standards
- Temperature/humidity tracking
- Defect documentation
- Photo evidence
- Pass/Fail criteria
- Quality reports
- Supplier quality scoring

#### Deliverables:
- Quality models
- Quality service
- Quality controllers
- Standards system
- Reporting
- Tests

---

### Phase 9: Advanced Purchase Reports ⭐⭐⭐⭐

#### Report Types:
1. **Purchase Analytics Dashboard**
   - Real-time purchase metrics
   - Category-wise analysis
   - Supplier performance
   - Cost trends

2. **Supplier Performance Reports**
   - Delivery time analysis
   - Quality scores
   - Price competitiveness
   - Overall rating

3. **Price Comparison Reports**
   - Price history per product
   - Supplier price comparison
   - Market price analysis
   - Variance analysis

4. **Purchase Trend Analysis**
   - Monthly/Quarterly/Annual trends
   - Seasonal patterns
   - Growth analysis
   - Forecasting

5. **Cost Variance Reports**
   - Budget vs Actual
   - Expected vs Actual
   - Variance reasons
   - Improvement suggestions

6. **Aging Supplier Reports**
   - 30/60/90/90+ days
   - Outstanding balance
   - Credit utilization
   - Risk assessment

7. **Forecast Reports**
   - Demand forecasting
   - Stock predictions
   - Purchase requirements
   - Budget requirements

#### Deliverables:
- Report models
- Report service
- Report controllers
- Dashboard components
- Data visualization
- Export functionality
- Tests

---

### Phase 10: Integration & Documentation ⭐⭐⭐⭐⭐

#### Integration Points:
1. **Purchase ↔ PO Integration**
   - PO to Purchase conversion
   - Purchase to PO linking
   - Status synchronization

2. **PO ↔ GRN Integration**
   - PO to GRN conversion
   - GRN to Stock update
   - Quantity tracking

3. **GRN ↔ Stock Integration**
   - Automatic stock update
   - Batch assignment
   - Expiry tracking

4. **Supplier ↔ All Systems**
   - Supplier data sync
   - Performance data aggregation
   - Contract enforcement

5. **Payment ↔ Purchase Integration**
   - Payment to Purchase linking
   - Balance updates
   - Invoice reconciliation

#### Documentation:
- API documentation
- User guides
- Admin guides
- Integration guides
- Troubleshooting guides

---

## 📊 Implementation Timeline

### Week 1-2: Phase 1 (PO System)
- ✅ Database schema
- ✅ Models
- ✅ Service
- ⏳ Controllers
- ⏳ Tests
- ⏳ Documentation

### Week 3-4: Phase 2 (GRN System)
- Database schema
- Models
- Service
- Controllers
- Quality checks
- Tests

### Week 5-6: Phase 3 (Supplier Management)
- Database schema
- Models
- Service
- Controllers
- Rating system
- Tests

### Week 7-8: Phase 4 (Payment Tracking)
- Database schema
- Models
- Service
- Controllers
- Aging reports
- Tests

### Week 9-10: Phase 5-6 (Credits & Approvals)
- Database schema
- Models
- Service
- Controllers
- Workflow engine
- Tests

### Week 11-12: Phase 7-8 (Budget & Quality)
- Database schema
- Models
- Service
- Controllers
- Reporting
- Tests

### Week 13-14: Phase 9-10 (Reports & Integration)
- Report system
- Dashboard
- Integration logic
- Documentation
- Final testing

---

## 🎯 Success Criteria

### Functional Requirements
- ✅ Complete PO lifecycle
- ✅ GRN quality checks
- ✅ Supplier performance tracking
- ✅ Payment reconciliation
- ✅ Approval workflows
- ✅ Budget monitoring
- ✅ Advanced reporting

### Non-Functional Requirements
- ✅ Performance: < 200ms response time
- ✅ Security: RBAC, data isolation
- ✅ Reliability: 99.9% uptime
- ✅ Scalability: Multi-tenant support
- ✅ Maintainability: Clean code, tests

### Business Requirements
- ✅ Improved purchase efficiency
- ✅ Better supplier relationships
- ✅ Cost control
- ✅ Quality assurance
- ✅ Compliance readiness

---

## 📝 Next Steps

### Immediate Actions:
1. ✅ Complete PO system (Phase 1)
2. ⏳ Start GRN system (Phase 2)
3. ⏳ Plan supplier management (Phase 3)
4. ⏳ Set up payment tracking (Phase 4)

### Priority Order:
1. **PO System** - Foundation
2. **GRN System** - Quality control
3. **Supplier Management** - Relationship management
4. **Payment Tracking** - Financial control
5. **Approval Workflow** - Process control
6. **Budget Management** - Planning
7. **Quality Checks** - Assurance
8. **Advanced Reports** - Analytics

---

## 🎉 Expected Outcomes

### Business Impact:
- **50% faster** purchase processing
- **30% reduction** in purchase errors
- **40% improvement** in supplier performance
- **25% cost savings** through better negotiation
- **100% compliance** with quality standards

### Technical Impact:
- **Professional-grade** purchase system
- **Scalable** architecture
- **Maintainable** codebase
- **Tested** functionality
- **Documented** processes

---

**Document Version:** 1.0.0  
**Last Updated:** 2026-08-07  
**Status:** ACTIVE  
**Total Systems:** 10  
**Current Phase:** 1 (PO System - In Progress)

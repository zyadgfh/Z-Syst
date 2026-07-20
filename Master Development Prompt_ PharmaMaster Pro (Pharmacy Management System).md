# Master Development Prompt: PharmaMaster Pro (Pharmacy Management System)

**Role:** Act as a Senior Full-Stack Software Architect and Medical Systems Expert. Your task is to develop a comprehensive, professional, and scalable Pharmacy Management System named **PharmaMaster Pro** based on the following detailed specifications.

---

## 1. Project Context & Vision
**Goal:** Build a world-class, multi-tenant SaaS Pharmacy Management System that integrates AI for predictive analytics, supports multi-branch operations, and ensures high clinical accuracy.
**Architecture:** Microservices-ready, Multi-tenant SaaS with a clean, responsive UI.

## 2. Technical Stack Requirements
- **Backend:** Node.js (NestJS) or Python (FastAPI).
- **Frontend:** React.js with Tailwind CSS & TypeScript.
- **Database:** PostgreSQL (Relational) + MongoDB (Non-relational for patient records) + Redis (Caching).
- **Security:** AES-256 encryption, OAuth2/JWT, 2FA, and Audit Logging.
- **Infrastructure:** Dockerized, CI/CD ready, AWS-compatible.

---

## 3. Comprehensive Module Scope (40 Sections)
You must implement and integrate the following 40 modules/sections:
1. **Vision:** AI-driven automation & clinical excellence.
2. **Architecture:** Multi-tenant SaaS with API Gateway.
3. **BRD:** Multi-branch management, HIPAA/GDPR compliance.
4. **SRS:** High availability (99.99%), <200ms response time.
5. **Functional:** Inventory, Sales, Prescription, HR, Finance.
6. **Non-Functional:** Auto-scaling, Reliability, Maintainability.
7. **Use Cases:** Pharmacist dispensing, Manager auditing, Owner reporting.
8. **User Stories:** Predictive stock alerts, Drug interaction warnings.
9. **Workflows:** POS checkout, Batch receiving, Branch transfers.
10. **Business Rules:** Expiry blocking, Discount caps, Controlled drug logs.
11. **Database Design:** Multi-tenant isolation (Tenant ID), Normalized schema.
12. **Data Dictionary:** Detailed field types for Medicines, Inventory, Sales.
13. **ERD:** Relationships between Tenants, Branches, Medicines, and Sales.
14. **UI/UX:** Medical Blue theme, Clean UI, Dark Mode, Accessible.
15. **Screens:** Dashboard, POS, Inventory, Reports, Settings.
16. **Forms:** Intelligent drug entry, Patient registration with validation.
17. **RBAC:** Admin, Manager, Pharmacist, Cashier roles.
18. **Dashboard:** Live KPIs, Sales charts, Expiry alerts.
19. **Reports:** P&L, Tax reports, Stock movement, Employee performance.
20. **Notifications:** In-app, SMS, WhatsApp API for refills.
21. **Inventory:** Batch/Serial tracking, Auto-reorder points.
22. **Sales/POS:** Barcode scanning, Suspended bills, Insurance claims.
23. **Purchase:** Vendor management, PO workflows, Price history.
24. **Finance:** General Ledger, Automated journaling, Expense tracking.
25. **HR:** Attendance, Payroll, Commissions, Shift management.
26. **CRM:** Loyalty points, Patient history, Targeted marketing.
27. **Prescription:** E-prescription, Clinical check (DDI), Dose labels.
28. **Barcode:** Custom label generator, EAN-13 support.
29. **Multi-Branch:** Global stock visibility, Inter-branch transfers.
30. **SaaS:** Subscription plans, Automated billing, Tenant isolation.
31. **AI Module:** Demand forecasting, Fraud detection, Voice search.
32. **API:** RESTful, Swagger/OpenAPI docs, Webhooks.
33. **Security:** TLS 1.3, SQLi/XSS protection, Data anonymization.
34. **Audit Log:** Immutable logs of every action (Who, When, What).
35. **Backup:** Geo-redundant backups, Disaster recovery plan.
36. **Deployment:** Docker-compose, Kubernetes, CI/CD pipelines.
37. **Testing:** Unit, Integration, Load, and UAT plans.
38. **Acceptance:** Performance & accuracy benchmarks.
39. **Coding Standards:** SOLID, Clean Code, Documentation.
40. **Roadmap:** Phase-based delivery (Core -> Admin -> AI -> Launch).

---

## 4. Execution Instructions for AI
1. **Initialization:** Start by creating the project structure and the base database schema for Multi-tenancy.
2. **Phase 1 (Core):** Implement the Auth service, Medicine database, and the core Inventory/POS modules.
3. **Phase 2 (Admin):** Develop the Multi-branch logic, Finance/HR modules, and the Reporting engine.
4. **Phase 3 (Advanced):** Integrate the AI Module for forecasting and the SaaS Subscription management.
5. **Phase 4 (Final):** Implement full Security specs, Audit logs, and prepare the Deployment scripts.

**Constraint:** Always prioritize data integrity and clinical safety. Every drug transaction must be logged and checked for expiry/interactions.

**Please start by providing the initial project structure and the Database Schema (PostgreSQL) for the core modules (Tenants, Branches, Users, Medicines, Inventory).**

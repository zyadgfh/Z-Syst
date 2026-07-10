# Z-Syst Module

## Phase 1 scope
- Drug directory search
- Drug alternative relationships
- Inventory movement tracking
- Online/offline sync heartbeat
- Procurement order drafting and tracking
- Supplier and customer management
- POS sales recording
- Loyalty point transactions and CRM foundation
- Inventory item-level batch tracking
- API-first integration for future analytics and mobile sync modules

## Current endpoints
- GET /api/zsyst/drugs
- POST /api/zsyst/drugs
- GET /api/zsyst/drugs/{drug}
- GET /api/zsyst/inventory
- POST /api/zsyst/inventory
- GET /api/zsyst/procurement/orders
- POST /api/zsyst/procurement/orders
- GET /api/zsyst/suppliers
- POST /api/zsyst/suppliers
- GET /api/zsyst/customers
- POST /api/zsyst/customers
- GET /api/zsyst/pos/sales
- POST /api/zsyst/pos/sales
- GET /api/zsyst/loyalty/transactions
- POST /api/zsyst/loyalty/transactions
- POST /api/zsyst/sync/heartbeat
- GET /api/zsyst/sync/status/{deviceId}

Legacy alias endpoints are also available under /api/z-syst/*.

## Notes
- The module is API-first and ready for further expansion into CRM, loyalty, and analytics workflows.
- The current responses use a consistent payload shape for easier frontend integration.
- A simple dashboard endpoint is available at /zsyst with summary counts and recent activity.

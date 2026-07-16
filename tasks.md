# نظام إدارة الصيدليات SaaS - المواصفات الشاملة المتكاملة

## نظرة عامة على المشروع

نظام إدارة صيدليات SaaS متكامل متعدد المستأجرين (Multi-Tenant) يعمل على الويب وتطبيقات الموبايل وتطبيق سطح المكتب للويندوز، مشابه لأنظمة PharmaSyst و Ibn Sina Pharma. النظام يسمح لأصحاب الصيدليات بالاشتراك في خطط وإدارة عمليات الصيدلية بالكامل.

## البنية التقنية الحالية

### الواجهة الخلفية (Backend)
- Framework: Laravel 10/11 (PHP 8.2+)
- Database: MySQL/MariaDB
- Authentication: Laravel Sanctum + Spatie Permission (RBAC)
- Queue System: Redis + Bull Queue
- Real-time: Socket.io / Laravel Echo
- Modules: nwidart/laravel-modules (Multi-module architecture)
- Storage: Local + Firebase Storage + AWS S3 compatible
- Excel: Maatwebsite Excel
- PDF: Dompdf / TCPDF
- Notifications: Firebase Cloud Messaging (FCM)

### الواجهة الخلفية الثانوية (Microservice)
- Runtime: Node.js + TypeScript
- ORM: Prisma
- Server: Express.js
- Cache: Redis + ioredis
- Logging: Winston
- Validation: Zod + express-validator

### الواجهة الأمامية (Web Dashboard)
- Template Engine: Blade + Bootstrap 5
- CSS: Tailwind CSS + Custom SCSS
- JavaScript: jQuery + Alpine.js
- Charts: ApexCharts / Chart.js
- Build Tool: Vite + Laravel Mix
- DataTables: jQuery DataTables

### تطبيق الموبايل
- Framework: Flutter (Dart)
- State Management: Provider
- Architecture: MVC + Repository Pattern
- PDF: printing + pdf packages
- Thermal Printing: esc_pos_utils
- Barcode: barcode_widget

### تطبيق سطح المكتب للويندوز (جديد)
- Framework: Electron + React
- Packaging: electron-builder
- Auto-Update: electron-updater
- Native Modules: node-hid (للباركود), node-printer (للطباعة)
- Database: SQLite (للعمل offline) + Sync مع الـ Backend
- Local Storage: electron-store
- System Tray: electron-traywindow
- Notifications: electron-notifications
- Window Management: electron-window-state
- Hardware Integration: Serial Port (للطابعات الحرارية والباركود)
- Background Sync: Workbox
- Security: electron-builder + code signing

## الموديولات والميزات الحالية

### نظام Multi-Tenancy
- Business/Store management
- Plan subscription system
- Business categories
- Trial periods
- Plan upgrades/downgrades

### إدارة المستخدمين
- Admin (Super Admin)
- Business Owners
- Staff with roles & permissions
- User roles (Spatie)

### إدارة المنتجات
- Products CRUD
- Categories & Subcategories
- Manufacturers
- Medicine Types
- Units & Box Sizes
- Tax rates
- Bulk upload (Excel)
- Barcode generation

### المخزون
- Stock management
- Stock alerts (low stock)
- Expiry date tracking
- Batch numbers
- Stock movements

### المشتريات
- Purchase orders
- Purchase returns
- Supplier management
- Purchase reports

### المبيعات
- POS (Point of Sale)
- Sales invoices
- Sales returns
- Customer management (Parties)
- Due/credit management

### الإدارة المالية
- Income tracking
- Expense tracking
- Income/Expense categories
- Profit & Loss reports
- Due collection

### التقارير والتحليلات
- Sales reports
- Purchase reports
- Stock reports
- Expiry reports
- Loss/Profit reports
- Due reports
- Dashboard analytics

### وسائل الدفع المدعومة

#### وسائل الدفع الإلكترونية الحالية
- Stripe
- PayPal
- Razorpay
- Paystack
- Flutterwave
- Mollie
- Mercado
- Instamojo
- SSLCommerz
- Tap
- Thawani
- PhonePe
- Paytm
- Toyyibpay
- Manual payments

#### وسائل الدفع المحلية الجديدة (مصر)
- Vodafone Cash (محافظ فودافون كاش)
- Orange Cash (محافظ أورانج كاش)
- Etisalat Cash (محافظ اتصالات كاش)
- InstaPay (إنستا باي)
- نقدي (كاش) - Cash on Delivery
- أجل (آجل) - Buy Now Pay Later

#### تفاصيل التكامل مع وسائل الدفع المحلية

##### Vodafone Cash
- API Integration مع Vodafone Cash API
- دعم USSD Push للمدفوعات
- QR Code للدفع
- Webhook للمعاملات
- إشعارات فورية عبر التطبيق (ليس SMS)
- سجل المعاملات الكامل
- دعم الاسترجاع (Refunds)

##### Orange Cash
- تكامل مع Orange Cash API
- دفع عبر QR Code
- إشعارات Push Notification
- سجل المعاملات
- دعم الاسترجاع
- تقارير يومية

##### Etisalat Cash
- API Integration
- USSD Push
- QR Code payments
- إشعارات عبر التطبيق
- سجل كامل
- دعم الاسترجاع

##### InstaPay
- تكامل مع InstaPay API
- QR Code scanning
- Instant transfers
- إشعارات فورية
- سجل المعاملات
- تقارير مفصلة

##### نقدي (كاش)
- تسجيل الدفع النقدي
- إدارة الصندوق (Cash Drawer)
- تقارير يومية للصندوق
- تسوية الصندوق
- طباعة الإيصال النقدي
- سجل الدفعات النقدية

##### أجل (BNPL - Buy Now Pay Later)
- نظام التقسيط
- إدارة العقود الآجلة
- جدولة الدفعات
- تتبع المستحقات
- تنبيهات الاستحقاق (عبر التطبيق)
- تقارير الآجل
- إدارة المتأخرات
- دعم الضمانات

### التواصل والإشعارات
- Email notifications
- WhatsApp invoices
- Push notifications (FCM)
- In-app notifications
- Desktop notifications (تطبيق الويندوز)

## متطلبات تطبيق سطح المكتب للويندوز

### الميزات الأساسية
- واجهة مستخدم حديثة وسلسة
- العمل Offline مع مزامنة عند الاتصال
- دعم الطابعات الحرارية (58mm, 80mm)
- دعم قارئات الباركود (USB, Bluetooth)
- دعم درج النقود (Cash Drawer)
- دعم شاشة العرض الثانية (Customer Display)
- طباعة الفواتير والإيصالات
- إدارة متعددة الفروع
- تقارير شاملة
- نسخ احتياطي تلقائي
- تحديث تلقائي
- نظام الترخيص

### المتطلبات التقنية
- Windows 10/11 (64-bit)
- Minimum RAM: 4GB
- Recommended RAM: 8GB
- Disk Space: 500MB
- .NET Framework 4.7.2 or higher
- Internet connection for sync

### التكامل مع الأجهزة
- طابعات حرارية (Epson, Bixolon, Star)
- قارئات باركود (Honeywell, Zebra, Datalogic)
- درج النقود
- شاشة العرض الثانية
- ميزان إلكتروني
- قارئ البطاقات (اختياري)

### ميزات الأمان
- تشفير البيانات محلياً
- تشفير الاتصالات (TLS 1.3)
- مصادقة ثنائية (2FA)
- قفل التطبيق تلقائياً
- سجل النشاطات (Audit Trail)
- صلاحيات المستخدمين
- نسخ احتياطي مشفر

## الميزات المطلوبة للتطوير

### المرحلة الأولى - أولوية عالية

#### نظام POS متقدم
- Fast barcode scanning (USB/Bluetooth)
- Multi-counter support
- Hold/Resume sales
- Quick customer selection
- Discount types (%, fixed, item-wise)
- Multiple payment methods per sale
- Receipt customization
- Thermal printer support (58mm, 80mm)
- Cash drawer integration
- Customer display (second screen)
- Offline mode with sync
- دعم الدفع المختلط (كاش + محافظ إلكترونية)
- دعم الدفع الآجل مع جدولة الدفعات
- طباعة فواتير مبسطة ومفصلة

#### إدارة الوصفات الطبية
- Digital prescription upload
- Prescription validation
- Doctor database
- Prescription history
- Refill reminders (عبر التطبيق)
- Controlled drugs tracking
- Prescription analytics

#### المخزون المتقدم
- Multi-warehouse support
- Stock transfers between warehouses
- Stock adjustments
- Stocktaking/inventory audit
- FIFO/FEFO/LIFO costing methods
- Automatic reorder points
- Supplier lead time tracking
- ABC analysis

#### برنامج ولاء العملاء
- Points system
- Membership tiers
- Birthday rewards (عبر التطبيق)
- Referral program
- Gift cards
- Coupons & vouchers
- Cashback offers

### المرحلة الثانية - أولوية متوسطة

#### التكامل مع التجارة الإلكترونية
- Online store for pharmacy
- Product catalog online
- Shopping cart
- Delivery management
- Order tracking
- Prescription upload for ordering
- Delivery partner integration

#### بوابة الموردين
- Supplier self-service portal
- Purchase order submission
- Invoice submission
- Payment tracking
- Performance rating
- Document management

#### التقارير المتقدمة و BI
- Custom report builder
- Scheduled reports (email)
- Export to Excel/PDF/CSV
- Visual dashboards
- KPI tracking
- Forecasting (ML-based)
- Drug interaction alerts

#### الامتثال التنظيمي
- DSC (Drug License) management
- Schedule H/H1/X drug tracking
- Batch recall system
- Temperature monitoring (cold chain)
- Audit trails
- Government reporting formats
- VAT/GST compliance

#### إدارة الفروع المتعددة
- Centralized management
- Branch-wise inventory
- Inter-branch transfers
- Consolidated reports
- Branch-wise pricing
- Branch permissions

### المرحلة الثالثة - أولوية منخفضة

#### الذكاء الاصطناعي والأتمتة
- Demand forecasting
- Smart reorder suggestions
- Price optimization
- Customer behavior analysis
- Chatbot for customers (عبر التطبيق)
- Voice-based POS
- OCR for prescriptions

#### الصيدلة عن بعد
- Video consultations
- Chat with pharmacist (عبر التطبيق)
- Appointment booking
- Prescription e-signature
- Medicine delivery tracking

#### التكاملات
- Accounting software (QuickBooks, Xero)
- ERP systems
- Insurance companies
- Government health portals
- Payment aggregators
- Shipping companies

## متطلبات الأمان

### المصادقة والتفويض
- Multi-factor authentication (2FA/MFA)
- JWT tokens with refresh
- Session management
- IP whitelisting for admin
- Login attempt limiting
- Password policies (complexity, expiry)
- OAuth2 integration (Google, Facebook)
- Biometric login (mobile & desktop)

### أمان البيانات
- Encryption at rest (AES-256)
- Encryption in transit (TLS 1.3)
- PCI DSS compliance for payments
- GDPR compliance
- Data anonymization
- Audit logs for all actions
- Backup automation (daily)
- Disaster recovery plan

### أمان التطبيق
- CSRF protection
- XSS prevention
- SQL injection prevention
- Rate limiting
- Input validation (server + client)
- File upload validation
- Security headers (Helmet)
- Regular security audits
- Dependency vulnerability scanning

## متطلبات الأداء

### Backend
- Response time: أقل من 200ms لاستدعاءات API
- Database query optimization (indexes, caching)
- Redis caching for frequent data
- Queue system for heavy tasks
- CDN for static assets
- Database sharding for multi-tenancy
- Horizontal scaling support

### Frontend
- Page load time: أقل من 2 ثوانٍ
- Lazy loading for images/components
- Code splitting
- Bundle optimization
- Service worker for offline support
- PWA capabilities

### Mobile App
- App size: أقل من 50MB
- Cold start: أقل من 3 ثوانٍ
- Smooth animations (60 FPS)
- Offline-first architecture
- Background sync
- Push notification reliability

### Desktop App
- Startup time: أقل من 5 ثوانٍ
- Memory usage: أقل من 500MB
- Smooth UI (60 FPS)
- Offline capability with local database
- Background sync when online
- Auto-update mechanism
- Minimal system resource usage

## متطلبات UI/UX

### مبادئ التصميم
- Modern, clean interface
- Responsive design (mobile-first)
- RTL support (Arabic)
- Multi-language support (i18n)
- Dark/Light mode
- Accessibility (WCAG 2.1 AA)
- Consistent design system
- Intuitive navigation

### Branding
- Customizable themes per tenant
- Logo upload
- Color scheme customization
- Email templates customization
- Receipt customization

## مبادئ تصميم قاعدة البيانات

- Normalized schema (3NF)
- Proper indexing strategy
- Soft deletes for important data
- Audit columns (created_by, updated_by, created_at, updated_at)
- Tenant isolation (business_id)
- Soft multi-tenancy
- Data archiving strategy
- Backup strategy

## متطلبات الاختبار

### هرم الاختبار
- Unit Tests (PHPUnit, Jest) - 70% coverage
- Integration Tests - 20% coverage
- E2E Tests (Playwright, Flutter Test, Electron Test) - 10% coverage
- Performance Tests (k6, JMeter)
- Security Tests (OWASP ZAP)
- Load Testing

### سيناريوهات الاختبار
- User registration & login
- CRUD operations
- Payment flows (جميع وسائل الدفع)
- Report generation
- API endpoints
- Mobile app flows
- Desktop app flows
- Multi-tenant isolation
- Concurrent users
- Offline mode sync

## النشر والـ DevOps

### البنية التحتية
- Docker containers
- Kubernetes orchestration
- CI/CD pipeline (GitHub Actions/GitLab CI)
- Auto-scaling
- Load balancer
- Database replication
- Multi-region deployment
- Blue-green deployment

### المراقبة
- Application monitoring (New Relic/Datadog)
- Error tracking (Sentry)
- Log aggregation (ELK Stack)
- Uptime monitoring
- Performance metrics
- Alert system

### النسخ الاحتياطي والاستعادة
- Daily automated backups
- Point-in-time recovery
- Off-site backup storage
- Backup testing
- RTO: أقل من 4 ساعات
- RPO: أقل من ساعة

## مواصفات تطبيق الموبايل

### الميزات
- Dashboard with KPIs
- Product management
- Sales & POS
- Purchase management
- Stock management
- Reports & analytics
- Customer management
- Due collection
- Barcode scanning
- Thermal printing
- Offline mode
- Push notifications
- Multi-language
- Biometric login

### دعم المنصات
- Android (min SDK 21)
- iOS (min iOS 13)
- Tablets support
- Responsive layouts

## مواصفات تطبيق الويندوز

### الميزات
- Full POS system
- Inventory management
- Customer management
- Reports & analytics
- Barcode scanning
- Thermal printing
- Cash drawer management
- Multi-branch support
- Offline mode with sync
- Auto-update
- System tray integration
- Keyboard shortcuts
- Multi-monitor support

### دعم المنصات
- Windows 10 (64-bit)
- Windows 11 (64-bit)
- Windows Server 2016+
- High DPI support

## معايير تصميم API

### RESTful API
- RESTful conventions
- Versioning (v1, v2)
- JSON:API specification
- Pagination (cursor-based)
- Filtering & sorting
- Rate limiting
- API documentation (Swagger/OpenAPI)
- API keys management

### تنسيق الاستجابة
```json
{
  "success": true,
  "data": {},
  "message": "Operation successful",
  "meta": {
    "pagination": {},
    "timestamp": ""
  }
}

Git Workflow
Git Flow branching strategy
Feature branches
Pull request reviews
Conventional commits
Semantic versioning
Automated changelog
معايير الكود
PSR-12 for PHP
ESLint for JavaScript
Dart formatter for Flutter
TypeScript for Electron
Pre-commit hooks
Code review mandatory
Automated code quality checks
متطلبات التوثيق
أنواع التوثيق
API Documentation (Swagger)
User Manual
Admin Guide
Developer Guide
Deployment Guide
Database Schema
Architecture Diagrams
Video Tutorials
FAQ
Troubleshooting Guide
Desktop App User Guide
Hardware Integration Guide
متطلبات الأعمال
تحقيق الدخل
Subscription plans (Monthly/Yearly)
Pay-per-use features
Add-ons marketplace
White-label solution
API access tiers
التحليلات
User acquisition tracking
Churn rate analysis
Revenue metrics (MRR, ARR)
Feature usage analytics
Customer satisfaction (NPS)
الديون التقنية التي يجب معالجتها
القضايا الحالية
Code duplication across modules
Inconsistent error handling
Missing comprehensive tests
Documentation gaps
Performance bottlenecks
Security vulnerabilities
Outdated dependencies
Hardcoded configurations
Missing API versioning
Incomplete mobile app features
Desktop app development needed
الجدول الزمني للتطوير
المرحلة 1 (الشهور 1-2): الأساس
Fix critical bugs
Security audit & fixes
Performance optimization
Complete POS system
Mobile app completion
Desktop app initial setup
المرحلة 2 (الشهور 3-4): الميزات الأساسية
Prescription management
Advanced inventory
Customer loyalty
Enhanced reporting
Local payment gateways integration (Vodafone Cash, Orange Cash, Etisalat Cash, InstaPay)
Cash and credit payment system
Desktop app core features
المرحلة 3 (الشهور 5-6): الميزات المتقدمة
E-commerce integration
Multi-branch support
AI features
Third-party integrations
Desktop app hardware integration
Offline mode for desktop
المرحلة 4 (الشهور 7-8): التوسع والتحسين
Performance tuning
Load testing
Documentation
Marketing launch
Desktop app beta testing
Desktop app release
مقاييس النجاح
KPIs التقنية
99.9% uptime
أقل من 200ms API response time
أقل من 2 ثوانٍ page load time
90%+ test coverage
Zero critical security issues
Desktop app crash rate أقل من 0.1%
KPIs الأعمال
1000+ active pharmacies in Year 1
95% customer satisfaction
أقل من 5% monthly churn
30% MoM growth
Positive unit economics
500+ desktop app users in first 6 months
إرشادات التعاون
عند تطوير الميزات
Understand the existing architecture first
Follow established patterns and conventions
Write tests before/with code
Document all changes
Consider security implications
Optimize for performance
Ensure mobile compatibility
Ensure desktop compatibility
Test with real data
Get code review
Update documentation
قائمة التحقق من جودة الكود
Follows PSR-12 / ESLint / Dart formatter / TypeScript
No code duplication (DRY principle)
Proper error handling
Input validation
Security best practices
Performance optimized
Well-commented
Tests included
Documentation updated
No hardcoded values
Cross-platform compatibility verified
القيود الحرجة
يجب توفرها
Multi-tenant architecture
Data isolation between tenants
PCI DSS compliance for payments
GDPR compliance
HIPAA considerations for health data
Arabic language support (RTL)
Mobile-first design
Offline capabilities
Desktop app compatibility
Local payment gateways support (Vodafone Cash, Orange Cash, Etisalat Cash, InstaPay, Cash, Credit)
يجب تجنبها
Breaking changes without migration
Hardcoded credentials
Unencrypted sensitive data
SQL injection vulnerabilities
XSS vulnerabilities
Performance degradation
Breaking existing APIs
Data loss scenarios
SMS-based notifications or verifications (ممنوع تماماً)
الدعم والصيانة
المهام المستمرة
Regular security updates
Dependency updates
Bug fixes
Performance monitoring
User feedback implementation
Feature enhancements
Backup verification
Disaster recovery testing
Desktop app updates
Hardware compatibility updates
المخرجات النهائية
عند اكتمال المشروع
Fully functional web application
Mobile apps (Android + iOS)
Desktop app (Windows)
API documentation
User manuals
Admin guides
Developer guides
Deployment scripts
Monitoring setup
Backup system
Training materials
Source code with full history
Hardware integration documentation
فرص الابتكار
التحسينات المستقبلية
AI-powered drug interaction checker
Voice-activated POS
AR for medicine information
Blockchain for drug traceability
IoT integration (smart fridges)
Predictive analytics
Automated compliance reporting
Telemedicine integration
Insurance claim automation
Supply chain optimization
Desktop app advanced features
Enhanced local payment integrations
إجراءات فورية
الأولوية 1 (هذا الأسبوع)
Review and understand existing codebase
Identify critical bugs
Security audit
Performance baseline
Set up development environment
Plan desktop app architecture
الأولوية 2 (هذا الشهر)
Fix critical issues
Complete POS system
Finish mobile app
Implement missing tests
Update documentation
Start desktop app development
Integrate local payment gateways
أنظمة مرجعية
دراسة هذه الأنظمة للإلهام:
PharmaSyst (Lebanon)
Ibn Sina Pharma (Jordan)
API Pharmacy (UK)
NowPatient (USA)
PillPack (Amazon)
MedExpress
HealthWarehouse
The Pharmacy (UAE)
قائمة التحقق من ضمان الجودة
قبل اعتبار أي ميزة مكتملة:
All tests passing
Code reviewed by peer
Documentation updated
Performance tested
Security reviewed
Mobile tested
Desktop tested
Cross-browser tested
Accessibility tested
User acceptance tested
Deployed to staging
Monitoring configured
Backup verified
Hardware integration tested (for desktop)
Payment gateway tested
مهمتك
أنت الآن المطور الرئيسي لنظام SaaS لإدارة الصيدليات. أهدافك هي:
Understand the existing architecture completely
Identify gaps and areas for improvement
Implement missing features with best practices
Optimize performance and security
Document everything thoroughly
Test comprehensively
Deploy with confidence
Maintain with excellence
Develop Windows desktop application
Integrate local payment gateways (Vodafone Cash, Orange Cash, Etisalat Cash, InstaPay, Cash, Credit)
Avoid any SMS-based functionality completely
تذكر: هذا النظام سيتعامل مع بيانات صحية حساسة ومعاملات مالية. الجودة والأمان والموثوقية ليست قابلة للتفاوض.
ابدأ بتحليل الكود الحالي وإنشاء خطة عمل مفصلة.
ملاحظات للمطور
The project uses a modular architecture (nwidart/laravel-modules)
Multi-tenancy is implemented at the database level (business_id)
The mobile app uses Provider for state management
Backend has both Laravel and Node.js microservices
Redis is used for caching and queues
Socket.io for real-time features
Multiple payment gateways integrated
Desktop app will use Electron framework
Local payment gateways need special attention (Egypt market)
Cash and credit payment systems require robust tracking
NO SMS functionality should be implemented - use app notifications, email, or WhatsApp instead
حظاً موفقاً! ابنِ شيئاً مذهلاً!
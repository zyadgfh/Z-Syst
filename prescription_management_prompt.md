# 🏥 نظام إدارة الوصفات الطبية - الملف الشامل
## Prescription Management System - Complete Package

> **الإصدار:** 3.0  
> **التاريخ:** 27 يوليو 2026  
> **اللغة المختارة:** Node.js + TypeScript + Express  
> **قاعدة البيانات:** PostgreSQL  
> **OCR:** Tesseract.js + Google Vision API (fallback)  
> **الاستهداف:** السوق المصري (EDA, MOHP, قانون 151/2020)

---

# 📋 الجزء الأول: البرومبت الشامل (The Prompt)

## 🎭 الدور (Role)
تصرف كـ:
- مدير منتجات أول (Senior Product Manager)
- مهندس برمجيات رئيسي (Lead Software Architect)
- خبير تجربة مستخدم (UX Expert)
- مستشار امتثال صحي (Health Compliance Consultant)

## 🎯 الهدف
تصميم وحدة "إدارة الوصفات الطبية" للسوق المصري مع الامتثال الكامل لـ:
- هيئة الدواء المصرية (EDA) ونظام "تراك"
- وزارة الصحة والسكان (MOHP)
- قانون حماية البيانات رقم 151/2020
- منظومة الفاتورة الإلكترونية

## 📌 المتطلبات الوظيفية
1. **رفع ومعالجة الوصفات:** OCR مع دعم العربية
2. **الربط بالمبيعات:** سلة مشتريات + مخزون + بدائل مكافئة
3. **لوحة الصيدلي:** مراجعة + موافقة/رفض + تنبيهات
4. **الامتثال:** تراك، جدول الأدوية، الفاتورة الإلكترونية

---

# 💻 الجزء الثاني: الكود البرمجي الكامل (The Code)

## 📁 هيكل المشروع (Project Structure)

```
prescription-system/
├── src/
│   ├── config/
│   │   ├── database.ts
│   │   └── ocr.ts
│   ├── controllers/
│   │   ├── prescription.controller.ts
│   │   └── sales.controller.ts
│   ├── services/
│   │   ├── ocr.service.ts
│   │   ├── prescription.service.ts
│   │   ├── sales.service.ts
│   │   └── track-and-trace.service.ts
│   ├── models/
│   │   ├── patient.model.ts
│   │   ├── prescription.model.ts
│   │   ├── medicine.model.ts
│   │   ├── order.model.ts
│   │   └── audit-log.model.ts
│   ├── middlewares/
│   │   ├── auth.middleware.ts
│   │   └── validation.middleware.ts
│   ├── routes/
│   │   └── prescription.routes.ts
│   ├── utils/
│   │   ├── encryption.ts
│   │   └── egyptian-compliance.ts
│   └── app.ts
├── database/
│   └── schema.sql
├── package.json
├── tsconfig.json
└── .env.example
```

---

## 📄 1. ملف `package.json`

```json
{
  "name": "prescription-management-system",
  "version": "1.0.0",
  "description": "نظام إدارة الوصفات الطبية - السوق المصري",
  "main": "dist/app.js",
  "scripts": {
    "dev": "ts-node-dev --respawn src/app.ts",
    "build": "tsc",
    "start": "node dist/app.js",
    "migrate": "ts-node src/database/migrate.ts"
  },
  "dependencies": {
    "express": "^4.18.2",
    "typescript": "^5.3.3",
    "pg": "^8.11.3",
    "typeorm": "^0.3.19",
    "tesseract.js": "^5.0.4",
    "@google-cloud/vision": "^4.0.2",
    "multer": "^1.4.5-lts.1",
    "sharp": "^0.33.2",
    "bcrypt": "^5.1.1",
    "jsonwebtoken": "^9.0.2",
    "axios": "^1.6.5",
    "zod": "^3.22.4",
    "dotenv": "^16.3.1",
    "helmet": "^7.1.0",
    "cors": "^2.8.5",
    "morgan": "^1.10.0",
    "reflect-metadata": "^0.2.1"
  },
  "devDependencies": {
    "@types/express": "^4.17.21",
    "@types/node": "^20.11.5",
    "@types/multer": "^1.4.11",
    "@types/bcrypt": "^5.0.2",
    "@types/jsonwebtoken": "^9.0.5",
    "ts-node-dev": "^2.0.0"
  }
}
```

---

## 📄 2. ملف `.env.example`

```env
# Server
PORT=3000
NODE_ENV=development

# Database (PostgreSQL)
DB_HOST=localhost
DB_PORT=5432
DB_USERNAME=postgres
DB_PASSWORD=your_password
DB_NAME=prescription_db

# OCR Services
GOOGLE_VISION_API_KEY=your_google_vision_key
TESSERACT_LANG=ara+eng

# Egyptian Compliance
EDA_TRACK_API_URL=https://api.eda.gov.eg/track
EDA_API_KEY=your_eda_api_key
MOHP_API_URL=https://api.mohp.gov.eg
MOHP_API_KEY=your_mohp_api_key

# Encryption
JWT_SECRET=your_super_secret_jwt_key
ENCRYPTION_KEY=your_32_char_encryption_key

# File Upload
UPLOAD_DIR=./uploads
MAX_FILE_SIZE=10485760
```

---

## 📄 3. ملف `database/schema.sql`

```sql
-- ============================================
-- قاعدة البيانات - نظام إدارة الوصفات الطبية
-- ============================================

-- جدول المرضى (Patients)
CREATE TABLE patients (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    national_id VARCHAR(14) UNIQUE NOT NULL, -- الرقم القومي المصري
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    encrypted_data TEXT, -- بيانات مشفرة حسب قانون 151/2020
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الأطباء (Doctors)
CREATE TABLE doctors (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    license_number VARCHAR(50) UNIQUE NOT NULL, -- رقم ترخيص النقابة
    syndicate_id VARCHAR(50), -- رقم النقابة
    specialization VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الأدوية (Medicines)
CREATE TABLE medicines (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name_ar VARCHAR(255) NOT NULL, -- الاسم التجاري بالعربية
    name_en VARCHAR(255), -- الاسم بالإنجليزية
    scientific_name VARCHAR(255) NOT NULL, -- الاسم العلمي
    barcode VARCHAR(50),
    eda_code VARCHAR(50), -- كود هيئة الدواء المصرية
    schedule_category INT CHECK (schedule_category BETWEEN 0 AND 5), -- جدول 0-5
    requires_prescription BOOLEAN DEFAULT true,
    price_egp DECIMAL(10, 2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    manufacturer VARCHAR(255),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول البدائل المكافئة (Generic Alternatives)
CREATE TABLE medicine_alternatives (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    original_medicine_id UUID REFERENCES medicines(id),
    alternative_medicine_id UUID REFERENCES medicines(id),
    equivalence_percentage DECIMAL(5, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الوصفات الطبية (Prescriptions)
CREATE TABLE prescriptions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    prescription_number VARCHAR(50) UNIQUE NOT NULL,
    patient_id UUID REFERENCES patients(id),
    doctor_id UUID REFERENCES doctors(id),
    issue_date DATE NOT NULL,
    expiry_date DATE NOT NULL, -- عادة 6 أشهر في مصر
    image_url TEXT NOT NULL,
    ocr_raw_text TEXT,
    status VARCHAR(50) DEFAULT 'pending_review',
    -- الحالات: pending_review, approved, rejected, preparing, ready, completed
    reviewed_by UUID REFERENCES users(id),
    reviewed_at TIMESTAMP,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول عناصر الوصفة (Prescription Items)
CREATE TABLE prescription_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    prescription_id UUID REFERENCES prescriptions(id) ON DELETE CASCADE,
    medicine_id UUID REFERENCES medicines(id),
    medicine_name_extracted VARCHAR(255), -- الاسم المستخرج من OCR
    dosage VARCHAR(100), -- الجرعة
    frequency VARCHAR(100), -- عدد المرات
    duration VARCHAR(100), -- المدة
    quantity INT NOT NULL,
    notes TEXT,
    is_approved BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول المستخدمين (Users - Pharmacists/Admins)
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL, -- admin, pharmacist, cashier
    pharmacist_license VARCHAR(50), -- ترخيص الصيدلي
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الطلبات/المبيعات (Sales Orders)
CREATE TABLE sales_orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_number VARCHAR(50) UNIQUE NOT NULL,
    prescription_id UUID REFERENCES prescriptions(id),
    patient_id UUID REFERENCES patients(id),
    pharmacist_id UUID REFERENCES users(id),
    subtotal DECIMAL(10, 2) NOT NULL,
    discount DECIMAL(10, 2) DEFAULT 0,
    insurance_coverage DECIMAL(10, 2) DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50), -- cash, card, insurance
    status VARCHAR(50) DEFAULT 'pending',
    -- pending, paid, dispensed, cancelled
    invoice_number VARCHAR(100), -- رقم الفاتورة الإلكترونية
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول عناصر الطلب (Order Items)
CREATE TABLE order_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID REFERENCES sales_orders(id) ON DELETE CASCADE,
    medicine_id UUID REFERENCES medicines(id),
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    is_alternative BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول التأمين (Insurance Claims)
CREATE TABLE insurance_claims (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID REFERENCES sales_orders(id),
    insurance_provider VARCHAR(100), -- ميدغلف، أكسا، ميتلايف، إلخ
    policy_number VARCHAR(100),
    claim_amount DECIMAL(10, 2),
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول نظام تراك (Track & Trace - EDA)
CREATE TABLE track_and_trace_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID REFERENCES sales_orders(id),
    medicine_id UUID REFERENCES medicines(id),
    serial_number VARCHAR(100) NOT NULL, -- كود التشغيل
    batch_number VARCHAR(100),
    eda_response JSONB,
    sync_status VARCHAR(50) DEFAULT 'pending',
    -- pending, synced, failed
    retry_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول سجل التدقيق (Audit Logs)
CREATE TABLE audit_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(id),
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50), -- prescription, order, patient
    entity_id UUID,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الفواتير الإلكترونية (E-Invoices)
CREATE TABLE e_invoices (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID REFERENCES sales_orders(id),
    invoice_number VARCHAR(100) UNIQUE NOT NULL,
    tax_authority_response JSONB,
    status VARCHAR(50), -- draft, submitted, accepted, rejected
    submitted_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- الفهارس للأداء (Indexes)
CREATE INDEX idx_prescriptions_patient ON prescriptions(patient_id);
CREATE INDEX idx_prescriptions_status ON prescriptions(status);
CREATE INDEX idx_orders_prescription ON sales_orders(prescription_id);
CREATE INDEX idx_medicines_barcode ON medicines(barcode);
CREATE INDEX idx_medicines_eda_code ON medicines(eda_code);
CREATE INDEX idx_audit_logs_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX idx_track_trace_sync ON track_and_trace_logs(sync_status);

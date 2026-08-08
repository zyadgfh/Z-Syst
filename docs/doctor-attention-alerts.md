# Z-Syst Pharmacy - Doctor Attention Alerts System Documentation

## 📋 Overview

The Doctor Attention Alerts System is a proactive notification system that monitors referring doctors' activity and automatically sends alerts when doctors show a significant drop in referrals or have been inactive for too long. This helps medical representatives focus their outreach efforts on doctors who need attention most.

---

## 🎯 Key Capabilities

### 1. Automatic Activity Monitoring
- Tracks referral counts per doctor
- Monitors prescription activity
- Calculates baseline referral rates
- Detects significant drops in activity

### 2. Attention Score Calculation
- Calculates 0-100 attention score
- Considers referral decline percentage
- Factors in days inactive
- Updates scores daily

### 3. Alert Generation
- Automatic alert creation when thresholds exceeded
- Multiple alert types: referral drop, inactivity, critical
- Severity levels: low, medium, high, critical
- Configurable alert frequency

### 4. Notification System
- Push notifications (enabled by default)
- Email notifications (optional)
- SMS notifications (optional)
- Role-based recipient selection

### 5. Action Tracking
- Mark alerts as read
- Track actions taken
- Record action details
- Timestamp for all actions

---

## 🗄️ Database Schema

### Doctor Activities Table
```php
Schema::create('doctor_activities', function (Blueprint $table) {
    $table->id();
    $table->foreignId('doctor_id')->constrained('parties')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('medical_rep_id')->nullable()->constrained('users')->nullOnDelete();
    
    $table->date('activity_date');
    $table->integer('referral_count')->default(0);
    $table->decimal('referral_amount', 10, 2)->default(0);
    $table->integer('prescription_count')->default(0);
    $table->json('details')->nullable();
    
    $table->timestamps();
});
```

### Doctor Attention Scores Table
```php
Schema::create('doctor_attention_scores', function (Blueprint $table) {
    $table->id();
    $table->foreignId('doctor_id')->constrained('parties')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    
    $table->date('calculated_date');
    $table->decimal('attention_score', 5, 2)->default(100);
    $table->decimal('decline_percentage', 5, 2)->default(0);
    $table->integer('days_inactive')->default(0);
    $table->date('last_referral_date')->nullable();
    
    $table->decimal('baseline_referrals', 8, 2)->default(0);
    $table->integer('current_period_referrals')->default(0);
    $table->integer('previous_period_referrals')->default(0);
    
    $table->string('status')->default('active');
    $table->text('alert_reason')->nullable();
    
    $table->timestamps();
});
```

### Doctor Attention Alerts Table
```php
Schema::create('doctor_attention_alerts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('doctor_id')->constrained('parties')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('medical_rep_id')->nullable()->constrained('users')->nullOnDelete();
    
    $table->string('alert_type');
    $table->string('severity');
    $table->text('message');
    $table->json('details')->nullable();
    
    $table->boolean('is_sent')->default(false);
    $table->timestamp('sent_at')->nullable();
    $table->boolean('is_read')->default(false);
    $table->timestamp('read_at')->nullable();
    
    $table->boolean('action_taken')->default(false);
    $table->text('action_details')->nullable();
    $table->timestamp('action_taken_at')->nullable();
    
    $table->timestamps();
    $table->softDeletes();
});
```

### Doctor Attention Settings Table
```php
Schema::create('doctor_attention_settings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    
    // Referral drop thresholds
    $table->decimal('referral_drop_threshold', 5, 2)->default(30);
    $table->integer('referral_drop_period_days')->default(30);
    
    // Inactivity thresholds
    $table->integer('inactivity_threshold_days')->default(14);
    $table->integer('critical_inactivity_days')->default(30);
    
    // Attention score thresholds
    $table->decimal('attention_score_warning', 5, 2)->default(60);
    $table->decimal('attention_score_critical', 5, 2)->default(30);
    
    // Notification settings
    $table->boolean('enable_push_notifications')->default(true);
    $table->boolean('enable_email_notifications')->default(false);
    $table->boolean('enable_sms_notifications')->default(false);
    
    // Notification recipients
    $table->json('notify_roles')->nullable();
    $table->json('notify_users')->nullable();
    
    // Frequency
    $table->integer('alert_frequency_hours')->default(24);
    
    $table->timestamps();
});
```

---

## 📊 Models

### DoctorActivity Model
**File:** `app/Models/DoctorActivity.php`

**Key Relationships:**
- `doctor()`: BelongsTo Party
- `business()`: BelongsTo Business
- `branch()`: BelongsTo Branch
- `medicalRep()`: BelongsTo User

**Scopes:**
- `forBusiness()`: Filter by business
- `forBranch()`: Filter by branch
- `forDoctor()`: Filter by doctor
- `inPeriod()`: Filter by date range
- `recent()`: Get recent activities

### DoctorAttentionScore Model
**File:** `app/Models/DoctorAttentionScore.php`

**Status Constants:**
- `STATUS_ACTIVE`: Active (score > 60)
- `STATUS_NEEDS_ATTENTION`: Needs attention (score 30-60)
- `STATUS_CRITICAL`: Critical (score < 30)

**Key Methods:**
- `needsAttention()`: Check if needs attention
- `isCritical()`: Check if critical
- `hasLowScore()`: Check if score < 60
- `hasSignificantDecline()`: Check if decline > 30%
- `isInactiveTooLong()`: Check if inactive > 14 days
- `getUrgencyLevel()`: Get urgency (low/medium/high/critical)

**Scopes:**
- `needingAttention()`: Filter doctors needing attention
- `critical()`: Filter critical doctors
- `latest()`: Get latest scores

### DoctorAttentionAlert Model
**File:** `app/Models/DoctorAttentionAlert.php`

**Alert Types:**
- `TYPE_REFERRAL_DROP`: Referral count dropped significantly
- `TYPE_INACTIVITY`: Doctor inactive for threshold days
- `TYPE_CRITICAL`: Critical attention score

**Severity Levels:**
- `SEVERITY_LOW`: Low priority
- `SEVERITY_MEDIUM`: Medium priority
- `SEVERITY_HIGH`: High priority
- `SEVERITY_CRITICAL`: Critical priority

**Key Methods:**
- `markAsSent()`: Mark notification as sent
- `markAsRead()`: Mark as read
- `markAsActionTaken()`: Mark action as taken

**Scopes:**
- `unsent()`: Filter unsent alerts
- `unread()`: Filter unread alerts
- `requiresAction()`: Filter alerts needing action

### DoctorAttentionSettings Model
**File:** `app/Models/DoctorAttentionSettings.php`

**Default Settings:**
- Referral drop threshold: 30%
- Inactivity threshold: 14 days
- Critical inactivity: 30 days
- Attention score warning: 60
- Attention score critical: 30
- Alert frequency: 24 hours

**Key Method:**
- `getDefaults()`: Get or create default settings

---

## 🎨 Services

### DoctorAttentionService
**File:** `app/Services/DoctorAttentionService.php`

**Key Methods:**
- `calculateScoresForBusiness()`: Calculate scores for all doctors
- `calculateScoreForDoctor()`: Calculate score for specific doctor
- `generateAlert()`: Generate alert when needed
- `recordActivity()`: Record doctor activity
- `getDoctorsNeedingAttention()`: Get doctors needing attention
- `getCriticalDoctors()`: Get critical doctors
- `getUnreadAlerts()`: Get unread alerts for user
- `markAlertAsActionTaken()`: Mark alert as action taken
- `getStatistics()`: Get attention statistics

**Attention Score Calculation:**
```
Base Score: 100
- Decline Deduction: (decline_percentage * 2) max 50 points
- Inactivity Deduction: (days_inactive * 2) max 50 points
Final Score: max(0, min(100, calculated_score))
```

---

## 🌐 Remaining Implementation

### 1. Controller
**File:** `app/Http/Controllers/Admin/DoctorAttentionController.php`

**Required Endpoints:**
- GET `/admin/doctor-attention` - Dashboard
- GET `/admin/doctor-attention/needing-attention` - Doctors needing attention
- GET `/admin/doctor-attention/critical` - Critical doctors
- GET `/admin/doctor-attention/alerts` - Alert list
- POST `/admin/doctor-attention/alerts/{id}/read` - Mark as read
- POST `/admin/doctor-attention/alerts/{id}/action` - Mark action taken
- GET `/admin/doctor-attention/statistics` - Statistics
- PUT `/admin/doctor-attention/settings` - Update settings

### 2. API Controller
**File:** `app/Http/Controllers/Api/DoctorAttentionController.php`

Same endpoints as admin controller for API access.

### 3. Resources
**Files:**
- `DoctorAttentionScoreResource`
- `DoctorAttentionAlertResource`
- `DoctorActivityResource`

### 4. Views
**Files:**
- `resources/views/admin/doctor-attention/dashboard.blade.php`
- `resources/views/admin/doctor-attention/needing-attention.blade.php`
- `resources/views/admin/doctor-attention/alerts.blade.php`

### 5. Routes
**Add to:** `routes/admin.php` and `routes/api.php`

### 6. Scheduled Task
**File:** `app/Console/Commands/CalculateDoctorAttentionScores.php`

**Schedule:** Daily at midnight
```php
$schedule->command('doctor-attention:calculate')->daily();
```

### 7. WhatsApp Integration
**Add to:** DoctorAttentionService

**Required:**
- WhatsApp API integration
- Send message directly from interface
- Track WhatsApp communication

### 8. Calling Integration
**Add to:** Views

**Required:**
- Click-to-call functionality
- Call logging
- Call tracking

---

## 📝 Usage Examples

### Record Doctor Activity
```php
$service = new DoctorAttentionService();
$activity = $service->recordActivity([
    'doctor_id' => 1,
    'business_id' => 1,
    'branch_id' => 1,
    'medical_rep_id' => 5,
    'activity_date' => now(),
    'referral_count' => 5,
    'referral_amount' => 500.00,
    'prescription_count' => 10,
]);
```

### Calculate Attention Scores
```php
$service = new DoctorAttentionService();
$service->calculateScoresForBusiness($businessId);
```

### Get Doctors Needing Attention
```php
$service = new DoctorAttentionService();
$doctors = $service->getDoctorsNeedingAttention($businessId);
```

### Mark Alert as Action Taken
```php
$service = new DoctorAttentionService();
$service->markAlertAsActionTaken($alertId, 'Called doctor - scheduled visit');
```

---

## 🧪 Testing

### Required Tests
- Activity recording
- Score calculation
- Alert generation
- Notification sending
- Status transitions
- Scopes functionality
- Statistics calculation

---

## 🎯 Integration Points

### Party Model Integration
Added relationships:
- `activities()`: HasMany DoctorActivity
- `attentionScores()`: HasMany DoctorAttentionScore
- `attentionAlerts()`: HasMany DoctorAttentionAlert
- `latestAttentionScore()`: HasOne latest score

### Notification System
- Push notifications (TODO)
- Email notifications (TODO)
- SMS notifications (TODO)

### Communication
- WhatsApp integration (TODO)
- Click-to-call (TODO)

---

## 🚀 Next Steps

### Immediate (Priority 1)
1. ✅ Database schema
2. ✅ Models
3. ✅ Service layer
4. ⏳ Controllers
5. ⏳ Resources
6. ⏳ Routes
7. ⏳ Views

### Short-term (Priority 2)
8. ⏳ Scheduled task
9. ⏳ API endpoints
10. ⏳ Dashboard views

### Long-term (Priority 3)
11. ⏳ WhatsApp integration
12. ⏳ Calling integration
13. ⏳ Email notifications
14. ⏳ SMS notifications
15. ⏳ Tests

---

## 📊 Expected Outcomes

### Business Impact
- **Proactive outreach**: Reach doctors before they churn
- **Data-driven decisions**: Focus on high-impact doctors
- **Time savings**: Automate monitoring
- **Improved relationships**: Timely follow-ups

### Technical Impact
- **Scalable**: Handles hundreds of doctors
- **Configurable**: Customizable thresholds
- **Extensible**: Easy to add new alert types
- **Reliable**: Scheduled automated calculations

---

**Document Version:** 1.0.0  
**Last Updated:** 2026-08-07  
**Status:** IN PROGRESS (40% Complete)  
**Completed:** Database, Models, Service  
**Remaining:** Controllers, Views, Routes, Integration

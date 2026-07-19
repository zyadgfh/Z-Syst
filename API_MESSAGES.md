# واجهة برمجة التطبيقات - نظام المراسلة الداخلي

## endpoints الجديدة للرسائل

### 1. عرض الرسائل
```
GET /api/v1/messages
```

**Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| type | string | فلترة حسب النوع (stock_refill, prescription_ready, low_stock, purchase_request, general) |
| unread | boolean | عرض غير المقروءة فقط |
| per_page | integer | عدد النتائج لكل صفحة (default: 25) |

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "type": "stock_refill",
      "subject": "طلب تعبئة مخزون",
      "content": "تم طلب تعبئة المخزون للمنتجات التالية: 3 منتج",
      "metadata": {
        "products": [...],
        "priority": "normal"
      },
      "is_read": false,
      "type_label": "طلب تعبئة مخزون",
      "sender": {...},
      "recipient": {...}
    }
  ]
}
```

### 2. إنشاء رسالة
```
POST /api/v1/messages
```

**Body:**
```json
{
  "recipient_id": 2,
  "branch_id": 1,
  "type": "stock_refill",
  "subject": "طلب تعبئة مخزون",
  "content": "نص الرسالة",
  "metadata": {}
}
```

### 3. إرسال طلب تعبئة مخزون
```
POST /api/v1/messages/stock-refill
```

**Body:**
```json
{
  "recipient_id": 2,
  "branch_id": 1,
  "products": [
    {
      "product_id": 1,
      "quantity": 100
    }
  ]
}
```

### 4. عدد الرسائل غير المقروءة
```
GET /api/v1/messages/unread-count
```

**Response:**
```json
{
  "unread_count": 5
}
```

### 5. الرد على رسالة
```
POST /api/v1/messages/{id}/reply
```

**Body:**
```json
{
  "content": "نص الرد"
}
```

---

## أنواع الرسائل المدعومة

| النوع | العربي | الوصف |
|-------|--------|-------|
| `stock_refill` | طلب تعبئة مخزون | طلب تعبئة منتجات من مخزن آخر |
| `prescription_ready` | وصفة جاهزة | إشعار بأن وصفة جاهزة للصرف |
| `low_stock` | مخزون منخفض | تنبيه بانخفاض المخزون |
| `purchase_request` | طلب مشتريات | طلب شراء منتجات من المورد |
| `general` | عام | رسائل عامة أخرى |

---

## مثال على الاستخدام في Frontend

```javascript
// جلب الرسائل غير المقروءة
const { data } = await api.get('/api/v1/messages/unread-count');
console.log(`لديك ${data.unread_count} رسائل جديدة`);

// إرسال طلب تعبئة
await api.post('/api/v1/messages/stock-refill', {
  recipient_id: managerId,
  branch_id: currentBranch,
  products: [{ product_id: 1, quantity: 50 }]
});
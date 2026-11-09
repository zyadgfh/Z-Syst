# أوفلاين يزامن عند عودة النت — صفر تكلفة

> طلبك: "كل شيء أونلاين لكن يدعم أن يكون أوفلاين ثم يزامن عند عودة النت" + "اوفلاين ثم يزامن"

## الفكرة (صفر تكلفة)
- **أونلاين أولاً:** النظام يعمل طبيعي عبر Supabase
- **أوفلاين للـ POS فقط (المرحلة 1):** عند فصل النت، البيع يُحفظ في IndexedDB ثم يزامن
- **صفر سيرفر:** كل شيء في المتصفح (Service Worker + IndexedDB) — لا VPS

## ما الذي يعمل أوفلاين؟
- **يعمل:** البيع (إضافة سلة + دفع نقدي + طباعة) — الأهم
- **لا يعمل أوفلاين في البداية:** التقارير الثقيلة، إضافة منتج جديد، إدارة موردين — تحتاج نت
- **لماذا؟** البيع هو ما يوقف الصيدلية لو فصل النت — التقارير تنتظر

## التنفيذ (صفر تكلفة)

### 1) Service Worker (يخزن الصفحات)
```javascript
// public/sw.js — يخزن /pos و /api/products للـ أوفلاين
self.addEventListener('fetch', (e) => {
  if (e.request.url.includes('/api/products')) {
    e.respondWith(caches.match(e.request).then(r => r || fetch(e.request).then(res => {
      caches.open('z-syst').then(c => c.put(e.request, res.clone()));
      return res;
    })));
  }
});
```

### 2) IndexedDB للفواتير الأوفلاين
```javascript
// عند البيع بدون نت
async function sellOffline(cart) {
  const tx = { id: Date.now(), cart, date: new Date().toISOString(), synced: false };
  await db.put('offline_sales', tx); // Dexie.js مجاني
  localStorage.setItem('offline_queue', JSON.stringify([...queue, tx]));
  alert('تم الحفظ أوفلاين — سيزامن عند عودة النت');
}

// عند عودة النت
window.addEventListener('online', async () => {
  const queue = JSON.parse(localStorage.getItem('offline_queue') || '[]');
  for (const tx of queue) {
    await fetch('/api/sales', { method: 'POST', body: JSON.stringify(tx.cart) });
  }
  localStorage.removeItem('offline_queue');
  alert('تمت المزامنة: ' + queue.length + ' فاتورة');
});
```

### 3) تخزين المنتجات للأوفلاين
```javascript
// عند كل فتح للـ POS أونلاين، خزن 3000 منتج في IndexedDB
async function cacheProducts() {
  const res = await fetch('/api/products?per_page=3000');
  const data = await res.json();
  await db.put('cached_products', { id: 'all', data: data.data, at: Date.now() });
}
// عند الأوفلاين: اقرأ من IndexedDB بدل API
```

## الأداء والتكلفة
- **التخزين:** IndexedDB مجاني في المتصفح — 50MB لكل صيدلية → يكفي 3000 منتج
- **المزامنة:** عند عودة النت، 1 فاتورة = 1 طلب API → 100 فاتورة أوفلاين = 100 طلب → مجاني
- **التكلفة:** 0 (لا Storage، لا Edge Function)

## ماذا لو الصيدلي باع 100 فاتورة أوفلاين؟
- **الاحتمال:** نادر — النت يفصل ساعة أو ساعتين
- **الحل:** عند المزامنة، نرسل دفعات (batch) 20 فاتورة كل مرة لتجنب تجاوز Rate Limit
- **التعارض:** لو باع نفس المنتج مرتين أوفلاين والكمية نفدت — عند المزامنة ننبه "كمية غير كافية لـ Panadol"

## خطة مرحلية واقعية (صفر تكلفة)
- **الأسبوع 1:** أوفلاين للـ POS فقط (بيع + طباعة)
- **الشهر 2:** أضف أوفلاين للبحث والباركود
- **بعد 50 صيدلية:** أضف أوفلاين للتقارير اليومية

## كيف تختبر؟
1. افتح POS → افصل الواي فاي → ابحث بباركود → بيع → يجب أن يحفظ
2. أعد النت → يجب أن تظهر "تمت المزامنة: 1 فاتورة" → تحقق في `sales` أن الفاتورة وصلت

## الخلاصة
- **المرحلة 1:** POS أوفلاين فقط — يكفي 90% من الحالات — صفر تكلفة
- **لا تحاول "كل شيء أوفلاين" من اليوم الأول** — سيؤخر الإطلاق شهور
- **المكتبة:** Dexie.js (IndexedDB) + Service Worker — مجانية ومستقرة

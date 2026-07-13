# My Bee Cashier — منع تكرار الطلب في المطبخ (طاولة + محلي)

> **الغرض:** ملف واحد للمطوّر Flutter **الكاشير** فقط.  
> **التاريخ:** 2026-07-13  
> **المشكلة:** طلب على طاولة يظهر بالمطبخ مرتين — واحد **محلي** وواحد **سفري**.

---

## 1. السبب (باختصار)

المطبخ يقرأ من **مصدرين**:

| API | جدول DB | يظهر بالمطبخ |
|-----|---------|--------------|
| `POST /api/new-order` | `table_orders` | `source: local` — **محلي** |
| `POST /api/stor-sales-invoice` | `transactions` | `source: pos` — **سفري** |

إرسال `order_type` (رقم نوع الخدمة = محلي) **لا يمنع** ظهور الفاتورة كطلب سفري في المطبخ.  
التكرار يحصل لأن الكاشير ينشئ **السجلين معاً** بدون ربط صحيح.

**الحل:** تدفق API صحيح + حقول ربط على الفاتورة. **لا يحتاج المطبخ فلتر خاص.**

---

## 2. التدفق الصحيح من الكاشير

### أ) طاولة + محلي (Dine-in)

```
1. POST /api/new-order     ← للمطبخ + إدارة الطاولة
2. POST /api/stor-sales-invoice  ← للفاتورة/المحاسبة فقط (السيرفر لا يبث للمطبخ إذا مربوط)
```

### ب) سفري (Takeaway) — بدون طاولة

```
POST /api/stor-sales-invoice فقط
(بدون table_id وبدون table_order_id)
```

---

## 3. الحقول المطلوبة

### `POST /api/new-order` (طاولة + محلي)

| حقل | مطلوب | ملاحظة |
|-----|--------|--------|
| `table_id` | ✅ | معرّف الطاولة |
| `items[]` | ✅ | الأصناف كاملة |
| `created_by` / `user_id` | ✅ | معرّف الموظف |
| `order_type` | ✅ | **رقم** `TypesOfService` (مثلاً `1` = محلي) |
| `establishment_id` | ✅ | الفرع |
| `note` | ❌ | اختياري |

**احفظ من الرد:**

```json
{
  "status": true,
  "order_id": 207,
  "order_no": "ORD000207"
}
```

→ `order_id` = **`table_order_id`** للخطوة التالية.

---

### `POST /api/stor-sales-invoice` (بعد new-order — نفس الطلب)

| حقل | مطلوب | ملاحظة |
|-----|--------|--------|
| `table_id` | ✅ | **نفس** الطاولة |
| `table_order_id` | ✅ | **`order_id` من رد new-order** |
| `order_type` | ✅ | **نفس** رقم محلي |
| `items[]` | ✅ | نفس الأصناف |
| `user_id` | ✅ | نفس الموظف |
| `establishment_id` | ✅ | نفس الفرع |
| `total_paid`, `total_tax`, ... | ✅ | كما هو اليوم |

> ⚠️ **`order_type` وحده لا يكفي.** لازم `table_id` + `table_order_id` على الفاتورة.

---

## 4. مثال Dart (منطق الحفظ)

```dart
Future<void> saveTableOrder({
  required int tableId,
  required int dineInServiceTypeId, // TypesOfService id لـ «محلي»
  required List<Map<String, dynamic>> items,
  required int userId,
  required int establishmentId,
  // ... totals, payments, etc.
}) async {
  // ── 1) طلب الطاولة → المطبخ ──
  final orderRes = await api.post('/api/new-order', body: {
    'table_id': tableId,
    'order_type': dineInServiceTypeId,
    'created_by': userId,
    'establishment_id': establishmentId,
    'items': items,
    // note, payments إن وُجدت...
  });

  final tableOrderId = orderRes['order_id'] as int;
  if (tableOrderId == null) throw Exception('new-order: missing order_id');

  // ── 2) الفاتورة → محاسبة (مع ربط — لا تكرار مطبخ) ──
  await api.post('/api/stor-sales-invoice', body: {
    'table_id': tableId,
    'table_order_id': tableOrderId, // ← الأهم
    'order_type': dineInServiceTypeId,
    'user_id': userId,
    'establishment_id': establishmentId,
    'items': items,
    // payments, totals, invoice_number, device_id...
  });
}
```

---

## 5. سفري — بدون تغيير كبير

```dart
Future<void> saveTakeawayOrder({...}) async {
  await api.post('/api/stor-sales-invoice', body: {
    // لا table_id
    // لا table_order_id
    'order_type': takeawayServiceTypeId, // رقم «سفري»
    'user_id': userId,
    'establishment_id': establishmentId,
    'items': items,
    ...
  });
}
```

---

## 6. أخطاء شائعة — تجنّبها

| ❌ خطأ | النتيجة |
|--------|---------|
| `stor-sales-invoice` فقط مع طاولة | مطبخ سفري أو ناقص |
| `new-order` فقط بدون فاتورة | محاسبة ناقصة |
| `order_type` محلي بدون `table_id` على الفاتورة | **تكرار** محلي + سفري |
| عدم إرسال `table_order_id` على الفاتورة | **تكرار** |
| استدعاء `new-order` مرتين لنفس الطلب | طلبات مكررة |

---

## 7. المطبخ (ملاحظة صغيرة — اختياري)

**لا فلتر على محلي/سفري.** فقط تأكد أن القائمة تُدمَج بـ `kitchen_key`:

```dart
String kitchenKey(Map<String, dynamic> order) =>
    (order['kitchen_key'] as String?) ??
    '${order['source'] ?? 'local'}:${order['id']}';
```

لا تستخدم `id` وحده — نفس الرقم قد يوجد في `local` و `pos`.

---

## 8. Checklist قبول (كاشير)

- [ ] طاولة + محلي → `new-order` ثم `stor-sales-invoice`
- [ ] `table_order_id` على الفاتورة = `order_id` من `new-order`
- [ ] `table_id` على الفاتورة = نفس الطاولة
- [ ] سفري → فاتورة واحدة بدون `table_id`
- [ ] بعد الحفظ: المطبخ يعرض **بطاقة واحدة** محلي فقط

---

## 9. برومبت جاهز لـ Cursor (انسخه كما هو)

```
أنت مطوّر Flutter لتطبيق My Bee Cashier (POS).

المشكلة:
عند إنشاء طلب على طاولة (نوع خدمة محلي)، المطبخ يعرض طلبين:
- واحد محلي (من table_orders / new-order)
- واحد سفري (من transactions / stor-sales-invoice)

السبب:
الكاشير ينشئ السجلين بدون ربط. order_type (رقم TypesOfService) لا يمنع بث الفاتورة للمطبخ.

المطلوب — عدّل منطق حفظ الطلب في الكاشير فقط:

1) طاولة + محلي (Dine-in):
   - أولاً: POST /api/new-order مع table_id, order_type (رقم محلي), items, created_by, establishment_id
   - احفظ order_id من الرد كـ tableOrderId
   - ثانياً: POST /api/stor-sales-invoice مع:
     - table_id (نفس الطاولة)
     - table_order_id = tableOrderId (من رد new-order) — إلزامي
     - order_type (نفس رقم محلي)
     - items وباقي حقول الفاتورة كما هي

2) سفري (Takeaway):
   - POST /api/stor-sales-invoice فقط
   - بدون table_id وبدون table_order_id

3) لا تستدعِ new-order لطلب سفري.
4) لا تعتمد على order_type وحده لمنع التكرار.
5) لا تغيّر شاشة المطبخ — المطبخ يستخدم kitchen_key فقط.

ابحث في المشروع عن:
- استدعاءات new-order و stor-sales-invoice
- شاشة اختيار الطاولة ونوع الخدمة (محلي/سفري)
- تأكد أن table_order_id يُرسل على الفاتورة بعد new-order

بعد التعديل: طلب طاولة + محلي = بطاقة واحدة في المطبخ (محلي فقط).
```

---

**مرجع باكند:** `Modules/Sales/Http/Controllers/SellApiController.php` — لا يبث `kitchen:order:created` للفاتورة المربوطة بـ `table_id` / `table_order_id`.

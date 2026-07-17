# My Bee Kitchen — تجهيز الطلب كامل دفعة واحدة

> **للمطوّر Flutter (المطبخ):** مرّر هذا الملف لـ Cursor.  
> **التاريخ:** 2026-07-11  
> **Endpoint جديد:** `POST /api/update-order-status`

---

## 1. الفرق بين الـ APIs

| API                                 | الاستخدام                   | ماذا يحدّث                                                                                                          |
| ----------------------------------- | --------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| `POST /api/update-item-status`      | تجهيز **صنف واحد**          | السطر الرئيسي **+ كل الكومبو والموديفاير التابعين له** → `prepared`؛ الطلب يصبح `prepared` تلقائياً لما يخلصوا الكل |
| **`POST /api/update-order-status`** | تجهيز **الطلب كامل**        | كل الأسطر `inpreparation` + `order_status` → `prepared` **دفعة واحدة**                                              |
| `POST /api/update-orders/{id}`      | **النادل** (مثلاً `served`) | رأس الطلب فقط — **لا يستخدم للمطبخ**                                                                                |

> **تجهيز صنف واحد (`update-item-status`):** عند الضغط على صنف رئيسي، السيرفر يحدّث **نفس المجموعة** المعروضة في المطبخ (الرئيسي + موديفاير + كومبو). الرد يتضمن `updated_line_ids` بكل الأسطر التي أصبحت `prepared`. لا حاجة لاستدعاء API منفصل لكل إضافة.

---

## 2. الطلب

```http
POST https://{tenant_id}.mybeesystem.net/api/update-order-status
Authorization: Bearer {company-login-token}
Content-Type: application/json
Accept: application/json
```

### Body

```json
{
    "order_id": 1001,
    "order_type": "local",
    "status": "prepared"
}
```

| حقل          | مطلوب | القيم                                                |
| ------------ | ----- | ---------------------------------------------------- |
| `order_id`   | نعم   | معرّف الطلب (`table_orders.id` أو `transactions.id`) |
| `order_type` | نعم   | `local` = طاولة/ويتر — `pos` = كاشير/سفري            |
| `status`     | نعم   | حالياً: **`prepared` فقط**                           |

### متى تستخدم `order_type`

| المصدر في المطبخ            | `order_type` |
| --------------------------- | ------------ |
| طلب طاولة (فيها `table_id`) | `local`      |
| فاتورة POS / سفري           | `pos`        |

استخدم `source` / `kitchen_key` من `kitchen:sync` إن وُجد:

```dart
final orderType = order['source'] == 'pos' ? 'pos' : 'local';
```

---

## 3. الاستجابة الناجحة — `200`

```json
{
    "status": true,
    "message": "Order marked as prepared",
    "order_id": 1001,
    "order_type": "local",
    "source": "local",
    "kitchen_key": "local:1001",
    "order_status": "prepared",
    "updated_lines": 5
}
```

| حقل             | المعنى                                    |
| --------------- | ----------------------------------------- |
| `updated_lines` | عدد أسطر الأصناف التي أصبحت `prepared`    |
| `kitchen_key`   | المفتاح لحذف الطلب من قائمة المطبخ محلياً |

---

## 4. أخطاء

| HTTP  | السبب                     | الرسالة                               |
| ----- | ------------------------- | ------------------------------------- |
| `404` | طلب غير موجود             | `Order not found`                     |
| `422` | الطلب ليس `inpreparation` | `Order is not in preparation`         |
| `422` | validation                | حقول ناقصة أو `status` غير `prepared` |

---

## 5. ماذا يحدث على السيرفر

1. كل الأسطر في الطلب بحالة `line_status = inpreparation` → `prepared`
2. `order_status` على الطلب → `prepared`
3. **Socket مطبخ:** `kitchen:order:removed` (السبب: `completed`) — الطلب يخرج من شاشة التحضير
4. **طلبات طاولة (`local`):**
    - `order:status_changed` للنادل
    - `order:updated` لتفاصيل الطاولة
    - `establishment_order.updated` للكاشير/POS

---

## 6. تكامل Flutter — زر «جاهز كامل»

```dart
Future<void> markOrderPrepared({
  required int orderId,
  required String orderType, // local | pos
  required String token,
  required String tenantBaseUrl,
}) async {
  final res = await dio.post(
    '$tenantBaseUrl/api/update-order-status',
    data: {
      'order_id': orderId,
      'order_type': orderType,
      'status': 'prepared',
    },
    options: Options(headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    }),
  );

  final kitchenKey = res.data['kitchen_key'] as String?;
  if (kitchenKey != null) {
    orders.removeWhere((o) => o.kitchenKey == kitchenKey);
  }
}
```

### بعد الطلب

- **لا تستدعي** `update-item-status` لكل صنف إذا ضغط المستخدم «جاهز كامل»
- انتظر `kitchen:order:removed` أو احذف محلياً بـ `kitchen_key` من الرد
- زر «صنف جاهز» يبقى على `update-item-status` كما هو

---

## 7. مثال cURL

```bash
curl -X POST "https://test1.mybeesystem.net/api/update-order-status" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"order_id":1001,"order_type":"local","status":"prepared"}'
```

---

## 8. نشر على السيرفر

```bash
cd /var/www/mybeeCompany

git pull

# لا migration جديدة لهذا التعديل — كود فقط
php artisan route:clear
php artisan config:clear
php artisan cache:clear

# تأكد أن المسار موجود
php artisan route:list --path=update-order-status
```

**لا حاجة** لإعادة تشغيل socket-server لهذا التعديل (يستخدم نفس بث المطبخ الموجود).

### تحقق سريع

```bash
# يجب أن يظهر:
# POST api/update-order-status ... OrderController@updateOrderStatus
php artisan route:list --path=update-order-status
```

---

## 9. قائمة تحقق

- [ ] زر «جاهز كامل» يستدعي `update-order-status` وليس حلقة `update-item-status`
- [ ] `order_type` صحيح (`local` / `pos`)
- [ ] حذف من UI بـ `kitchen_key`
- [ ] لا تستدعي API إذا `order_status` ليس `inpreparation`

---

## 10. ملف Backend

| ملف                                                            | التغيير                     |
| -------------------------------------------------------------- | --------------------------- |
| `Modules/Reservation/Http/Controllers/Api/OrderController.php` | `updateOrderStatus()`       |
| `Modules/Reservation/routes/Api/order.php`                     | `POST /update-order-status` |

**مرتبط:** `docs/flutter-kitchen-socket-ar.md`, `docs/flutter-waiter-backend-updates-2026-07-ar.md`

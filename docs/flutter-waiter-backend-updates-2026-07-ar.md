# My Bee Waiter / Kitchen / POS — تحديثات Backend (يوليو 2026)

> **للمطوّر Flutter:** مرّر هذا الملف لـ Cursor عند تحديث تطبيقات النادل، المطبخ، الكاشير، أو POS.  
> **آخر تحديث:** 2026-07-11

---

## 1. ملخص التغييرات

| # | الموضوع | التأثير على Flutter |
|---|---------|---------------------|
| 1 | **إعادة فتح طلب مخدوم** | `POST /api/new-order` يحدّث نفس `order_id` — لا تنشئ طلباً جديداً محلياً |
| 2 | **ملاحظات الطلب والأصناف** | أرسل/اقرأ `note` على الطلب وكل صنف |
| 3 | **مطبخ — مفتاح فريد** | استخدم `kitchen_key` بدل `id` فقط |
| 4 | **توكن Socket بدون انتهاء** | نفس `company-login` token لعدة تطبيقات؛ لا تعيد login من كل تطبيق |
| 5 | **كومبو POS** | `option_id` قد يكون `item_id` — السيرفر يحلّه تلقائياً |

---

## 2. طلب مخدوم + إضافة أصناف (مهم)

### السلوك القديم (خطأ)

```
طاولة فيها طلب order_status = served
→ POST /api/new-order
→ السيرفر يلغي القديم وينشئ order_id جديد
```

### السلوك الجديد (صحيح)

```
طاولة فيها طلب served (أو prepared / completed)
→ POST /api/new-order مع items كاملة (القديم + الجديد)
→ نفس order_id
→ order_status يعود inpreparation
→ payment_status يعود due (إلا إذا أرسلت payments)
→ الطاولة تصبح notAvailable (table_status = 2)
→ Socket: establishment_order.updated (نفس id)
→ المطبخ: kitchen:order:created (نفس id لكن عاد للمطبخ)
```

### ما يجب على Flutter

1. **لا تفرّغ `order_id` المحلي** بعد `served` إذا ما زال الزبون على الطاولة وتريد إضافة أصناف.
2. عند الحفظ أرسل **قائمة items كاملة** (السيرفر يستبدل الأسطر كلها).
3. توقّع في الرد:

```json
{
  "status": true,
  "order_id": 45,
  "order_no": "ORD000045"
}
```

`order_id` **نفس الرقم** قبل وبعد الإضافة.

4. بعد `served`، `GET /api/tables/{id}` قد يعيد الطلب بحالة `served` (لم يعد يُخفى من `activeOrder`).

### حالات `order_status`

| الحالة | المعنى |
|--------|--------|
| `inpreparation` | قيد التحضير — يظهر في المطبخ |
| `prepared` | جاهز — يُعاد فتحه عند إضافة أصناف |
| `served` | مُخدّم — يُعاد فتحه عند إضافة أصناف |
| `completed` | مكتمل — يُعاد فتحه عند إضافة أصناف |
| `canceled` | ملغي — لا يُدمج |

---

## 3. POST `/api/new-order`

### حقول الطلب

| حقل | مطلوب | ملاحظة |
|-----|--------|--------|
| `table_id` | نعم | |
| `items[]` | نعم | قائمة كاملة عند التحديث |
| `items[].note` | لا | ملاحظة الصنف |
| `note` | لا | ملاحظة الطلب |
| `created_by` | نعم | معرّف الموظف |
| `payments` | لا | إن وُجدت → دفع |
| `order_type` | لا | افتراضي `1` |

### مثال item

```json
{
  "product_id": 292,
  "quantity": 2,
  "price": 18,
  "price_with_tax": 20.7,
  "note": "بدون بصل",
  "order_item_modifiers": [],
  "order_item_combos": []
}
```

### الاستجابة — قائمة الطلبات

`GET /api/orders` و `GET /api/tables/{id}` و `GET /api/establishment-orders/{id}`:

- الطلب: `note` أو `description`
- كل صنف رئيسي: `note`

---

## 4. المطبخ — Socket.IO

### مفتاح الطلب (إلزامي من الآن)

كل طلب في `kitchen:sync` و `kitchen:order:*` يحتوي:

```json
{
  "id": 42,
  "source": "local",
  "kitchen_key": "local:42",
  "order_type": "محلي",
  "items": []
}
```

| source | المعنى |
|--------|--------|
| `local` | طلب طاولة (ويتر) |
| `pos` | فاتورة كاشير |

**نفس الرقم `id` قد يوجد في local و pos — لا تدمج بـ `id` وحده.**

### طلب طاولة من الكاشير — لا تكرار في المطبخ

عند بيع على طاولة من الكاشير:

1. **المطبخ يعرض طلباً واحداً فقط** (`source: local` / محلي) عبر `POST /api/new-order`.
2. **`POST /api/stor-sales-invoice`** يبقى للمحاسبة/الفاتورة — السيرفر **لا يبث** للمطبخ إذا الطلب مربوط بطاولة.
3. **`order_type` وحده لا يكفي** — هو رقم `TypesOfService` (مثلاً `1` = محلي). لازم أيضاً:

| حقل | على `new-order` | على `stor-sales-invoice` |
|-----|-----------------|--------------------------|
| `table_id` | **نعم** | **نعم** (مهم) |
| `table_order_id` | لا | **نعم** — `order_id` من رد `new-order` |
| `order_type` | نعم | نعم (نفس الرقم) |

**مطبخ Flutter:** لا فلتر خاص — استخدم `kitchen_key` فقط (`local:42` وليس `id` وحده).

**كاشير Flutter:** لا تعتمد على `order_type` لمنع التكرار — أرسل `table_id` + `table_order_id` على الفاتورة.

طلب **سفري حقيقي** (بدون طاولة) يبقى `source: pos` كما هو.

### دمج القائمة في Dart

```dart
String kitchenKey(Map<String, dynamic> order) {
  return (order['kitchen_key'] as String?) ??
      '${order['source'] ?? 'local'}:${order['id']}';
}

void onOrderUpdated(Map<String, dynamic> order) {
  final key = kitchenKey(order);
  final i = orders.indexWhere((o) => kitchenKey(o) == key);
  if (i >= 0) {
    orders[i] = order;
  } else {
    orders.add(order);
  }
}

void onOrderRemoved(Map<String, dynamic> raw) {
  final key = (raw['kitchen_key'] as String?) ??
      '${raw['source'] ?? 'local'}:${raw['order_id']}';
  orders.removeWhere((o) => kitchenKey(o) == key);
}
```

### أحداث الإزالة

`kitchen:order:removed` يتضمن الآن:

```json
{
  "order_id": 42,
  "source": "local",
  "kitchen_key": "local:42",
  "reason": "completed"
}
```

---

## 5. توكن Socket و company-login

### المشكلة السابقة

- تسجيل دخول من تطبيق ثانٍ كان يلغي التوكن → `INVALID_TOKEN` وفصل Socket.
- كاش التحقق كان 2 دقيقة فقط.

### الحل

```http
POST https://my-bee.info/api/company-login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "***",
  "tenant_id": "test1",
  "client_type": "waiter"
}
```

| client_type | التطبيق |
|-------------|---------|
| `waiter` | النادل |
| `kitchen` | المطبخ |
| `cashier` | الكاشير |
| `pos` | POS |

**قواعد مهمة:**

1. **نفس التوكن** يمكن استخدامه في عدة تطبيقات — لا تعيد `company-login` من كل تطبيق إلا عند الحاجة.
2. التوكن **بدون انتهاء** افتراضياً (`expires_at: null`).
3. في Socket auth أرسل:

```dart
.setAuth({
  'token': token,        // بدون كلمة Bearer
  'tenant_id': tenantId,
  'client_type': 'waiter',
  'employee_id': employeeId,
  'establishment_id': establishmentId,
})
```

4. إذا فصل Socket بـ `INVALID_TOKEN` → `company-login` مرة واحدة ثم أعد الاتصال.

---

## 6. Socket — النادل (ملخص أحداث)

| حدث | متى |
|-----|-----|
| `table:updated` | تغيير حالة طاولة |
| `order:updated` | تحديث طلب (بما فيه إعادة فتح مخدوم) |
| `order:created` | طلب جديد فعلاً على طاولة فارغة |
| `order:finished` | served / canceled |

راجع `docs/flutter-waiter-socket-ar.md` للتفاصيل.

---

## 7. POS — طلبات الطاولات

| حدث | متى |
|-----|-----|
| `establishment_order.created` | طلب **جديد** (`order_id` جديد) |
| `establishment_order.updated` | إضافة أصناف أو إعادة فتح مخدوم (**نفس id**) |
| `establishment_order.closed` | served / canceled / completed |
| `establishment_order.cancelled` | إلغاء |

راجع `docs/flutter-pos-table-orders-socket-ar.md`.

---

## 8. نشر على السيرفر (Deploy)

### 8.1 Laravel

```bash
cd /var/www/mybeeCompany   # أو مسار المشروع عندك

git pull

# migrations جديدة (ملاحظات الأصناف + أي migration معلّق)
php artisan tenants:migrate

php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 8.2 متغيرات `.env` (Laravel)

```env
SOCKET_BROADCAST_URL=http://127.0.0.1:3001/broadcast
SOCKET_INTERNAL_SECRET=نفس_القيمة_في_socket-server

# توكنات company-login — بدون انتهاء، بدون إلغاء عند login جديد
COMPANY_API_TOKEN_EXPIRATION_MINUTES=
COMPANY_API_REVOKE_PREVIOUS=false
```

### 8.3 Socket Server

```bash
cd /var/www/mybeeCompany/socket-server   # أو مسار socket-server

npm install

# .env
# SOCKET_VERIFY_CACHE_MS=86400000
# SOCKET_RATE_LIMIT_PER_MINUTE=120
# SOCKET_INTERNAL_SECRET=نفس Laravel

pm2 restart mybee-socket --update-env
pm2 logs mybee-socket --lines 50
```

### 8.4 Apache / Nginx

- لا تغيير على `/socket.io/` proxy إن كان يعمل.
- `/ws` للشاشات يبقى كما هو.

### 8.5 اختبار سريع بعد النشر

```bash
# 1) توكن
curl -s -X POST https://my-bee.info/api/company-login \
  -H "Content-Type: application/json" \
  -d '{"email":"...","password":"...","tenant_id":"test1","client_type":"waiter"}'

# 2) تحقق
curl -s -H "Authorization: Bearer TOKEN" https://my-bee.info/api/verify-token

# 3) Socket polling
curl -s "https://test1.my-bee.info/socket.io/?EIO=4&transport=polling"
```

**سيناريو طاولة مخدومة:**

1. أنهِ طلباً → `order_status = served`
2. أضف صنفاً عبر `POST /api/new-order`
3. تأكد: نفس `order_id`، `order_status = inpreparation`
4. المطبخ: يظهر الطلب مرة واحدة بـ `kitchen_key = local:{id}`

---

## 9. قائمة تحقق Flutter

- [ ] دمج مطبخ بـ `kitchen_key` وليس `id` فقط
- [ ] بعد `served` الإضافة ترسل لنفس الطلب ولا تتوقع `order_id` جديد
- [ ] `items` كاملة عند كل حفظ
- [ ] `note` على الطلب وكل صنف
- [ ] توكن واحد مشترك + `client_type` في Socket auth
- [ ] `establishment_order.updated` عند إعادة فتح مخدوم (ليس `created`)

---

## 10. ملفات Backend المتأثرة

| ملف | التغيير |
|-----|---------|
| `Modules/Reservation/Http/Controllers/Api/OrderController.php` | إعادة فتح مخدوم، ملاحظات |
| `Modules/Reservation/Support/KitchenOrderPayload.php` | `kitchen_key`, `source` |
| `Modules/Reservation/Services/KitchenBroadcastService.php` | إزالة/تحديث بالمفتاح |
| `Modules/Reservation/Models/Table.php` | `activeOrder` يشمل المخدوم |
| `app/Http/Controllers/Api/CompanyAuthController.php` | `company-login` |
| `app/Services/CompanyTokenService.php` | توكنات دائمة متعددة التطبيقات |
| `socket-server/index.js` | كاش توكن أطول، rate limit |

---

**أسئلة؟** راجع أيضاً:

- `docs/flutter-waiter-socket-ar.md`
- `docs/flutter-kitchen-socket-ar.md`
- `docs/flutter-pos-table-orders-socket-ar.md`
- `docs/socket-multi-app-token-fix-ar.md`

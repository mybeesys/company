# دليل مطوّر Flutter — Socket.IO (تطبيق المطبخ MyBee Kitchen)

**الإصدار:** 1.0  
**التاريخ:** 2026-05-31  
**يتوافق مع:** `socket-server/index.js` + `KitchenBroadcastService`

---

## 1. ملخص سريع

| البند | القيمة |
|--------|--------|
| البروتوكول | Socket.IO v4 (`socket_io_client`) |
| عنوان الاتصال | **نفس scheme الـ REST** — `https://{tenant_id}.my-bee.info` **بدون** `:3001` وبدون `/socket.io/` في الـ URL |
| المسار | `/socket.io/` |
| Transports | `['websocket', 'polling']` |
| Namespace | `/` (افتراضي) |
| REST fallback | `GET /api/kitchen-orders` |

---

## 2. المصادقة والاتصال

نفس REST:

| Header / auth | مطلوب |
|---------------|--------|
| `auth.token` | Bearer token **بدون** كلمة `Bearer` |
| `auth.tenant_id` | معرّف الشركة |
| `auth.establishment_id` | رقم الفرع |
| أو header `Establishment-Id` | نفس قيمة الفرع |

```dart
import 'package:socket_io_client/socket_io_client.dart' as IO;

IO.Socket connectKitchenSocket({
  required String tenantId,
  required String token,
  required int establishmentId,
  List<int>? categoryIds,
}) {
  final url = 'https://$tenantId.my-bee.info';

  final socket = IO.io(
    url,
    IO.OptionBuilder()
        .setPath('/socket.io/')
        .setTransports(['websocket', 'polling'])
        .enableReconnection()
        .setAuth({
          'token': token,
          'tenant_id': tenantId,
          'establishment_id': establishmentId,
        })
        .setExtraHeaders({
          'Establishment-Id': '$establishmentId',
        })
        .build(),
  );

  socket.onConnect((_) {
    socket.emit('kitchen:join', {
      'establishment_id': establishmentId,
      if (categoryIds != null && categoryIds.isNotEmpty)
        'category_ids': categoryIds,
    });
  });

  return socket;
}
```

### أخطاء الاتصال

| الرسالة | الإجراء |
|---------|---------|
| `timeout` | غالباً Apache لا يوجّه `/socket.io/` أو استخدم `https` إن REST على https — راجع §12 |
| `UNAUTHORIZED` / `INVALID_TOKEN` | token منتهي أو `Bearer ` زائدة — أعد login |
| `ESTABLISHMENT_REQUIRED` | أرسل `establishment_id` في `kitchen:join` |

```dart
// ❌ خطأ — يسبب timeout أو 404
IO.io('http://test1.my-bee.info/socket.io/', ...);

// ✅ صح
IO.io('https://test1.my-bee.info', IO.OptionBuilder().setPath('/socket.io/')...);
```

---

## 3. أحداث Client → Server

### `kitchen:join` (إلزامي بعد الاتصال)

```json
{
  "establishment_id": 3,
  "category_ids": [9, 10]
}
```

**رد ack:**

```json
{ "ok": true, "room": "kitchen:establishment:3", "count": 12 }
```

**يرسل السيرفر فوراً:** `kitchen:sync` (لقطة الطلبات مفلترة حسب `category_ids`).

> **فلترة الكاتوغري (realtime):**
> - عند إرسال `category_ids` في `kitchen:join`، العميل **لا يدخل** الغرفة العامة `kitchen:establishment:{id}` (تستقبل طلبات كاملة بدون فلتر).
> - كاتوغري واحدة → غرفة `kitchen:establishment:{id}:category:{cat}`
> - عدة كاتوغريات (مثل `[9, 18]`) → غرفة مجمّعة `kitchen:establishment:{id}:categories:9,18` مع `items` مفلترة لاتحاد الكاتوغريات فقط.
> - أحداث `kitchen:order:*` تُبث مفلترة — لا يجب أن يظهر صنف من كاتوغري خارج المحدد (مثل سندويش عند اختيار حلو + عصير).
> - عند تغيير `category_ids` أرسل `kitchen:leave` ثم `kitchen:join` من جديد (السيرفر يُفرّغ الغرف القديمة تلقائياً أيضاً).

### `kitchen:leave` (اختياري)

```json
{ "establishment_id": 3 }
```

---

## 4. أحداث Server → Client

كل رسالة تحتوي عادة:

```json
{
  "event_id": "uuid",
  "schema_version": 1,
  "event": "kitchen:order:updated",
  "timestamp": "2026-05-31T12:00:00.000000Z",
  "updated_at": "2026-05-31T12:00:00.000000Z",
  "establishment_id": 3
}
```

### 4.1 `kitchen:sync`

```json
{
  "event": "kitchen:sync",
  "establishment_id": 3,
  "orders": [ /* نفس GET /api/kitchen-orders */ ]
}
```

**العميل:** استبدل القائمة كاملة أو دمج مع REST للتأكد.

**فلترة إضافية (موصى بها في Flutter):** حتى مع السيرفر، طبّق فلتراً محلياً على `order.items` حسب `category_ids` المختارة — احتياط إذا وصل حدث قديم قبل إعادة الاتصال.

```dart
List<KitchenItem> filterItems(List<KitchenItem> items, List<int> categoryIds) {
  if (categoryIds.isEmpty) return items;
  final allowed = categoryIds.toSet();
  return items.where((i) => allowed.contains(i.categoryId)).toList();
}
```

---

### 4.2 `kitchen:order:created`

```json
{
  "event": "kitchen:order:created",
  "establishment_id": 3,
  "order": { "id": 1001, "items": [], "order_status": "inpreparation", ... }
}
```

**العميل:** أضف للقائمة إن لم يوجد `id`.

---

### 4.3 `kitchen:order:updated`

```json
{
  "event": "kitchen:order:updated",
  "establishment_id": 3,
  "order": { /* طلب كامل محدّث */ }
}
```

**العميل:** استبدل الطلب بنفس `id` — `order.items` يحتوي فقط أصناف كاتوغريك المحددة (عند `category_ids` في join).

> إذا اخترت **عدة كاتوغريات** `[9, 18]`، كل حدث `kitchen:order:updated` يأتي بـ `items` مفلترة لـ **9 و 18 معاً** — لا دمج يدوي من أحداث منفصلة.

يُبث بعد:
- `POST /api/update-item-status`
- أي تحديث من نادل / POS / جهاز مطبخ آخر

---

### 4.4 `kitchen:item:status_changed` (خفيف + يتبعه `kitchen:order:updated`)

```json
{
  "event": "kitchen:item:status_changed",
  "establishment_id": 3,
  "order_id": 1001,
  "item_id": 501,
  "status": "prepared",
  "order_status": "inpreparation",
  "order_type": "محلي"
}
```

---

### 4.5 `kitchen:order:removed`

```json
{
  "event": "kitchen:order:removed",
  "establishment_id": 3,
  "order_id": 1001,
  "reason": "completed"
}
```

| `reason` | متى |
|----------|-----|
| `completed` | كل الأصناف `prepared` أو `order_status` لم يعد `inpreparation` |
| `cancelled` | إلغاء |
| `archived` | حالات أخرى |

**العميل:** احذف الطلب من القائمة.

---

## 5. شكل الطلب (مطابق REST)

نفس `GET /api/kitchen-orders` — لا محوّل إضافي.

| حقل | ملاحظة |
|-----|--------|
| `order_status` | في المطبخ النشط: `inpreparation` |
| `items[].status` | `inpreparation`, `prepared`, … |
| `order_type` | `محلي` أو `سفري` |

**Normalize الصنف (على العميل):**

```dart
String normalizeItemStatus(String? s) =>
    (s ?? '').toLowerCase().replaceAll(RegExp(r'[_\-\s]'), '');
// prepared → منجز
```

---

## 6. دمج مع الشاشة

```dart
void setupKitchenListeners(IO.Socket socket, void Function(List orders) onOrders) {
  socket.on('kitchen:sync', (raw) {
    final orders = (raw as Map)['orders'] as List;
    onOrders(List.from(orders));
  });

  socket.on('kitchen:order:created', (raw) {
    final order = (raw as Map)['order'];
    // append if id not exists + filter by visible categories
  });

  socket.on('kitchen:order:updated', (raw) {
    final order = (raw as Map)['order'];
    // replace by id
  });

  socket.on('kitchen:order:removed', (raw) {
    final id = (raw as Map)['order_id'];
    // remove by id
  });

  socket.on('kitchen:item:status_changed', (raw) {
    // optional: patch single item, then wait for kitchen:order:updated
  });
}
```

### Idempotency

```dart
final _seen = <String>{};
bool isDuplicate(Map payload) {
  final id = payload['event_id'] as String?;
  if (id == null) return false;
  if (_seen.contains(id)) return true;
  _seen.add(id);
  if (_seen.length > 300) _seen.remove(_seen.first);
  return false;
}
```

### إعادة الاتصال

```dart
socket.onReconnect((_) {
  socket.emit('kitchen:join', {
    'establishment_id': establishmentId,
    'category_ids': visibleCategoryIds,
  });
});
```

---

## 7. REST (fallback)

| Method | Endpoint |
|--------|----------|
| GET | `/api/kitchen-orders?establishment_id=3&category_ids[]=9` |
| POST | `/api/update-item-status` body: `{ item_id, order_type: local\|pos }` |

عند انقطاع Socket: أبقِ polling أو `GET` بعد إعادة الاتصال.

---

## 8. جدول المحفّزات

| الحدث في النظام | Socket |
|-----------------|--------|
| طلب جديد (طاولة / POS) | `kitchen:order:created` |
| تعليم صنف prepared | `kitchen:item:status_changed` + `kitchen:order:updated` |
| اكتمال الطلب (`prepared` / إلغاء / إنهاء) | `kitchen:order:removed` |
| اتصال + join | `kitchen:sync` |

---

## 9. Checklist قبول

- [ ] طلب POS جديد يظهر خلال &lt; 2 ث
- [ ] جهاز A يعلّم صنفًا → جهاز B يتحدث
- [ ] اكتمال الطلب → `kitchen:order:removed`
- [ ] token غير صالح → فشل الاتصال
- [ ] إعادة Wi‑Fi → `kitchen:sync` يعيد الحالة
- [ ] فلترة التصنيف محلياً تعمل مع الطلب الكامل

---

## 10. إجابات المواصفة (§11)

1. **URL:** نفس دومين REST + Apache proxy `/socket.io/` → `:3001`
2. **Namespace:** `/`
3. **Token:** `auth.token` (+ اختياري `Establishment-Id` header)
4. **`kitchen:sync`:** يُرسل تلقائياً بعد `kitchen:join`؛ REST يبقى fallback
5. **حالات DB:** `order_status`: `inpreparation`, `prepared`, `served`, `canceled` — `items[].status`: `inpreparation`, `prepared`
6. **`kitchen:order:removed`:** عند خروج الطلب من طابور المطبخ (`order_status` ≠ `inpreparation`)

---

## 11. مراجع Laravel

- `Modules/Reservation/Services/KitchenBroadcastService.php`
- `Modules/Reservation/Support/KitchenOrderPayload.php`
- `Modules/Reservation/Http/Controllers/Api/OrderController.php`

## 12) تشخيص timeout (من السيرفر)

```bash
curl http://127.0.0.1:3001/health
curl -I "http://test1.my-bee.info/socket.io/?EIO=4&transport=polling"
curl -Ik "https://test1.my-bee.info/socket.io/?EIO=4&transport=polling"
```

| نتيجة curl | المعنى |
|------------|--------|
| health OK + socket 404 | أضف ProxyPass في `laravel.conf` |
| http timeout لكن https OK | Flutter يجب أن يستخدم **https** |

---

**جهة الاتصال (باك إند):** فريق MyBee Company

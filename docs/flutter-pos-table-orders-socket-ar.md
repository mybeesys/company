# دليل مطوّر Flutter — Socket.IO (طلبات الطاولات / My Bee POS)

**الإصدار:** 1.0  
**التاريخ:** 2026-05-31  
**التطبيق:** My Bee POS  
**يتوافق مع:** نفس خادم Socket (النادل + المطبخ + POS)

---

## 1. ملخص

| البند | القيمة |
|--------|--------|
| URL | `https://{tenant_id}.my-bee.info` |
| Path | `/socket.io/` |
| Transports | `['websocket', 'polling']` |
| الغرفة | `establishment:{establishment_id}` |
| REST | `GET /api/establishment-orders/{id}` |

**الهدف:** تحديث شاشة «طلبات الطاولات» فوراً + **طباعة مطبخ** عند `created` / `updated` (منطق الطباعة في التطبيق).

---

## 2. الاتصال

```dart
import 'package:socket_io_client/socket_io_client.dart' as IO;

IO.Socket connectPosTableOrdersSocket({
  required String tenantId,
  required String token,
  required int establishmentId,
  int? deviceId,
}) {
  final socket = IO.io(
    'https://$tenantId.my-bee.info',
    IO.OptionBuilder()
        .setPath('/socket.io/')
        .setTransports(['websocket', 'polling'])
        .enableReconnection()
        .setAuth({
          'token': token,
          'tenant_id': tenantId,
          'establishment_id': establishmentId,
          if (deviceId != null) 'device_id': deviceId,
        })
        .setExtraHeaders({
          'Establishment-Id': '$establishmentId',
        })
        .build(),
  );

  socket.onConnect((_) {
    socket.emit('join_establishment', {
      'establishment_id': establishmentId,
    });
  });

  return socket;
}
```

| مصدر الحقل | المفتاح في التطبيق |
|------------|-------------------|
| Token | `PreferencesKeys.companyToken` |
| Tenant | `PreferencesKeys.tenantId` |
| فرع | `PreferencesKeys.establishmentId` |
| جهاز | `PreferencesKeys.deviceId` (اختياري) |

**مهم:** لا تضف `/socket.io/` في الـ host — استخدم `.setPath('/socket.io/')`.

---

## 3. أحداث Client → Server

### `join_establishment` (بعد الاتصال)

```json
{ "establishment_id": 3 }
```

**رد ack:**

```json
{ "ok": true, "room": "establishment:3", "count": 5 }
```

**يرسل السيرفر:** `establishment_orders.sync` (لقطة أولية).

> عند الاتصال مع `establishment_id` في `auth` يُضاف العميل تلقائياً لغرفة `establishment:{id}`.

---

## 4. أحداث Server → Client

### 4.1 `establishment_orders.sync` (عند join)

```json
{
  "event": "establishment_orders.sync",
  "establishment_id": 3,
  "orders": [ /* نفس GET establishment-orders */ ]
}
```

---

### 4.2 `establishment_order.created`

طلب جديد (`POST /api/new-order` بدون `order_id`).

```json
{
  "event": "establishment_order.created",
  "establishment_id": 3,
  "order": { "id": 45, "items": [], "table_name": "T-12", ... }
}
```

**العميل:**

- أضف/حدّث في القائمة
- **اطبع مطبخ لكل** `order.items`

---

### 4.3 `establishment_order.updated`

إضافة أصناف لطلب موجود أو تغيير حالة.

```json
{
  "event": "establishment_order.updated",
  "establishment_id": 3,
  "order": { /* طلب كامل محدّث */ }
}
```

**العميل:**

- استبدل الطلب بنفس `id`
- **اطبع فقط الأسطر الجديدة** (تتبع `items[].line_id`)

---

### 4.4 `establishment_order.cancelled`

```json
{
  "event": "establishment_order.cancelled",
  "establishment_id": 3,
  "order_id": 45
}
```

احذف من القائمة — **لا طباعة**.

---

### 4.5 `establishment_order.closed`

إنهاء / `served` / `canceled` / `completed`.

```json
{
  "event": "establishment_order.closed",
  "establishment_id": 3,
  "order_id": 45
}
```

احذف أو انقل للمكتمل — **لا طباعة**.

---

## 5. شكل `order` (مطابق REST)

نفس `GET /api/establishment-orders/{establishment_id}`:

| حقل | ملاحظة |
|-----|--------|
| `id` | معرّف الطلب |
| `table_id` | نص |
| `table_name` | اسم الطاولة للمطبخ |
| `order_status` | مثلاً `inpreparation`, `prepared`, `served` |
| `waiter_name` | اسم النادل |
| `establishment_id` | الفرع |
| `items[].id` | معرّف السطر |
| `items[].line_id` | فريد — `{orderId}-{lineId}-{index}` |
| `items[].order_item_modifiers` | |
| `items[].order_item_combos` | |

**بصمة احتياطية إن لم يوجد `line_id`:**

```dart
String lineFingerprint(Map item, int orderId) =>
    '$orderId-${item['product_id']}-${item['quantity']}-...';
```

---

## 6. دمج في `table_orders_socket_service.dart`

```dart
void setupPosOrderListeners(IO.Socket socket, TableOrdersController ctrl) {
  socket.on('establishment_orders.sync', (raw) {
    final orders = (raw as Map)['orders'] as List;
    ctrl.replaceAll(orders);
  });

  socket.on('establishment_order.created', (raw) {
    final order = (raw as Map)['order'];
    ctrl.upsertOrder(order);
    KitchenPrintService.printAllItems(order); // حسب إعداداتك
  });

  socket.on('establishment_order.updated', (raw) {
    final order = (raw as Map)['order'];
    ctrl.upsertOrder(order);
    KitchenPrintService.printNewItemsOnly(order);
  });

  socket.on('establishment_order.cancelled', (raw) {
    ctrl.removeOrder((raw as Map)['order_id']);
  });

  socket.on('establishment_order.closed', (raw) {
    ctrl.removeOrder((raw as Map)['order_id']);
  });
}
```

### Idempotency

```dart
final _seen = <String>{};
bool isDuplicate(Map p) {
  final id = p['event_id'] as String?;
  if (id == null) return false;
  if (_seen.contains(id)) return true;
  _seen.add(id);
  return false;
}
```

### إعادة الاتصال

```dart
socket.onReconnect((_) {
  socket.emit('join_establishment', {'establishment_id': establishmentId});
});
```

---

## 7. جدول المحفّزات (الباك إند)

| العملية REST | حدث Socket |
|--------------|------------|
| `POST /api/new-order` (جديد) | `establishment_order.created` |
| `POST /api/new-order` (إضافة لطلب) | `establishment_order.updated` |
| `POST /api/cancel-order` | `establishment_order.cancelled` |
| `POST /api/update-orders/{id}` → served/… | `establishment_order.closed` |

---

## 8. عزل الفروع

- فرع **3** لا يستقبل أحداث فرع **5**
- الغرفة: `establishment:3` فقط

---

## 9. Checklist قبول

- [ ] اتصال بتوكن صالح + `establishment_id`
- [ ] طلب جديد من الويتر → `created` خلال &lt; 2 ث + طباعة مطبخ
- [ ] إضافة صنف → `updated` + طباعة السطر الجديد فقط
- [ ] إلغاء → `cancelled`
- [ ] إنهاء → `closed`
- [ ] `order` يطابق `GET /api/establishment-orders/{id}`
- [ ] `line_id` فريد لكل سطر

---

## 10. تشخيص

```bash
curl http://127.0.0.1:3001/health
curl -Ik "https://test1.my-bee.info/socket.io/?EIO=4&transport=polling"
```

| خطأ | الحل |
|-----|------|
| `timeout` | `https` + Apache proxy `/socket.io/` |
| `INVALID_TOKEN` | token جديد + `pm2 restart` بعد `git pull` |

---

## 11. مراجع Laravel

- `Modules/Reservation/Services/EstablishmentOrdersBroadcastService.php`
- `Modules/Reservation/Support/EstablishmentOrderPayload.php`
- `socket-server/index.js` — `join_establishment`, `pos_orders`

**ملف العميل:** `lib/core/realtime/table_orders_socket_service.dart`

---

**جهة الاتصال (باك إند):** فريق MyBee Company

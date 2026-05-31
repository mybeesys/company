# دليل مطوّر Flutter — Socket.IO (تطبيق النادل MyBee Waiter)

**الإصدار:** 2.0  
**التاريخ:** 2026-05-31  
**يتوافق مع:** خادم `socket-server/index.js` + Laravel `RealtimeBroadcastService`

---

## 1. ملخص سريع

| البند | القيمة |
|--------|--------|
| البروتوكول | Socket.IO v4 (`socket_io_client` ^2.x مع Engine.IO v4) |
| عنوان الاتصال | `https://{tenant_id}.my-bee.info` **بدون منفذ** (انظر §7.1) |
| المسار | `/socket.io/` |
| ⚠️ لا تستخدم `:0` أو `:3001` في الإنتاج إن وُجد Nginx proxy |
| Transports | `['websocket', 'polling']` |
| المصادقة | `auth` في handshake (انظر §3) |
| REST | يبقى للتحميل الأول؛ Socket للتحديثات اللاحقة |

---

## 2. تسلسل الاتصال في التطبيق

```
1. POST /api/company-login  → token + tenant_id
2. POST /api/employee-login  → employee_id, timecard_id (+ establishment_id)
3. io.connect(tenant URL, auth: {...})
4. استقبال connected (schema_version)
5. emit sync:tables أو GET /api/get-tables
6. عند فتح تفاصيل طاولة: emit join:table { table_id }
7. عند الخروج: emit leave:table { table_id }
```

---

## 3. الاتصال (Dart)

```dart
import 'package:socket_io_client/socket_io_client.dart' as IO;

IO.Socket connectWaiterSocket({
  required String tenantId,
  required String token,
  int? employeeId,
  int? timecardId,
  int? establishmentId,
  bool useHttps = true,
}) {
  final scheme = useHttps ? 'https' : 'http';
  final url = '$scheme://$tenantId.my-bee.info';

  return IO.io(
    url,
    IO.OptionBuilder()
        .setPath('/socket.io/')
        .setTransports(['websocket', 'polling'])
        .enableReconnection()
        .setAuth({
          'token': token, // بدون كلمة Bearer
          'tenant_id': tenantId,
          if (employeeId != null) 'employee_id': employeeId,
          if (timecardId != null) 'timecard_id': timecardId,
          if (establishmentId != null) 'establishment_id': establishmentId,
        })
        .build(),
  );
}
```

### أخطاء الاتصال (`connect_error`)

| رسالة السيرفر | إجراء التطبيق |
|---------------|----------------|
| `UNAUTHORIZED` | لا token — إعادة company-login |
| `INVALID_TOKEN` | token منتهي، أو `Bearer ` في auth، أو السيرفر لم يسحب آخر كود — جرّب `curl` في §12 |
| `timeout` | Apache / https — نفس §12 في `flutter-kitchen-socket-ar.md` |
| `TENANT_REQUIRED` | أرسل `tenant_id` في `auth` أو استخدم subdomain صحيح |
| `RATE_LIMITED` | انتظر وأعد المحاولة |
| `MAINTENANCE` | عرض شاشة صيانة |

---

## 4. أحداث السيرفر → العميل (استمع بأسماء الحدث كما هي)

كل رسالة تحتوي عادة:

```json
{
  "event_id": "uuid",
  "schema_version": 1,
  "event": "table:updated",
  "timestamp": "2026-05-31T14:30:00.000000Z",
  "data": { }
}
```

### 4.1 `connected` (فور الاتصال)

```json
{
  "schema_version": 1,
  "tenant_id": "acme",
  "establishment_id": 9,
  "server_time": "..."
}
```

### 4.2 `tables:snapshot`

نفس عناصر `GET /api/get-tables` → `data` مصفوفة.

```dart
socket.on('tables:snapshot', (raw) {
  final payload = raw as Map<String, dynamic>;
  final list = payload['data'] as List;
  // دمج في TablesBloc
});
```

### 4.3 `table:updated`

عنصر واحد بنفس شكل عنصر في `get-tables`:

| حقل | ملاحظة |
|-----|--------|
| `status` | `available` \| `reserved` \| `notAvailable` — أي قيمة ≠ `available` تعني غير متاحة في UI |
| `order_status` | `inpreparation` \| `prepared` \| `served` \| null |
| `current_order_id` | معرّف الطلب النشط |
| `assigned_waiter_id` | للفلترة حسب النادل |

```dart
socket.on('table:updated', (raw) {
  final table = (raw as Map)['data'];
  // tablesBloc.applyTablePatch(table);
});
```

### 4.4 `order:created`

```json
{
  "table_id": 12,
  "order_id": 501,
  "order_status": "inpreparation",
  "created_by": 45
}
```

يُرسل معه عادة `table:updated` و `order:updated` — يمكن الاعتماد على `order:updated` للتفاصيل.

### 4.5 `order:updated`

نفس شكل `GET /api/tables/{tableId}`:

```json
{
  "table_id": 12,
  "data": {
    "table": { },
    "reservation": null,
    "order": { "items": [] }
  }
}
```

إذا أُغلق الطلب: `"order": null`.

### 4.6 `order:status_changed` (خفيف)

```json
{
  "table_id": 12,
  "order_id": 501,
  "status": "open",
  "order_status": "prepared"
}
```

حدّث بطاقة الطاولة + شاشة التفاصيل إن `table_id` مطابق.

### 4.7 `reservation:updated` (اختياري)

نفس كائن `reservation` داخل `table:updated`.

### 4.8 أحداث قديمة (React فقط — لا تعتمد عليها في Flutter الجديد)

- `TableUpdated` — شكل قديم `{ table_id, table_code, ... }`

---

## 5. أحداث العميل → السيرفر

```dart
// بعد فتح شاشة الطاولة
socket.emit('join:table', {'table_id': 12});
// يرد ack + قد يرسل order:updated فوراً

socket.emit('leave:table', {'table_id': 12});

socket.emit('sync:tables', {});
// يرد tables:snapshot

socket.emit('ping', {});
socket.on('pong', (data) { /* server_time */ });
```

---

## 6. دمج مع BLoC (مقترح)

### TablesBloc

- عند `table:updated`: ابحث عن `id` في القائمة واستبدل العنصر.
- عند `tables:snapshot`: استبدل القائمة كاملة.
- عند `order:status_changed`: حدّث `order_status` و `current_order_id` إن وُجد.
- احتفظ بـ `TablesRefresh` من REST عند فشل Socket أو بعد `reconnect`.

### OrdersBloc / شاشة التفاصيل

- بعد `join:table` استمع لـ `order:updated` و `order:status_changed`.
- فلتر بـ `payload['table_id'] == currentTableId`.
- تجاهل التكرار: خزّن آخر `event_id` في Set (حد أقصى 200).

```dart
final _seen = <String>{};

bool isDuplicate(Map payload) {
  final id = payload['event_id'] as String?;
  if (id == null) return false;
  if (_seen.contains(id)) return true;
  _seen.add(id);
  if (_seen.length > 200) _seen.remove(_seen.first);
  return false;
}
```

---

## 7. إعدادات البيئة (AppConfig)

### 7.1 بناء الـ URL (مهم — سبب خطأ `:0`)

السوكت على السيرفر يعمل على **3001 داخلياً**، لكن تطبيق Flutter يتصل عبر **نفس دومين الـ API** على 443/80 بعد Nginx.

```dart
/// إنتاج: بدون منفذ أبداً
String socketUrl(String tenantId, {bool useHttps = true}) {
  final scheme = useHttps ? 'https' : 'http';
  return '$scheme://$tenantId.my-bee.info';
}

// ❌ خطأ — يسبب: test1.my-bee.info:0
// '$host:${socketPort}'  عندما socketPort = 0 أو null

// ❌ خطأ في الإنتاج (إلا للتجربة المباشرة على IP)
// 'https://test1.my-bee.info:3001'
```

| البيئة | URL للاتصال |
|--------|-------------|
| إنتاج (مع Nginx) | `https://test1.my-bee.info` |
| تجربة مباشرة على Node | `http://IP_SERVER:3001` فقط للاختبار |

### 7.2 Nginx (الباك / DevOps)

بدون توجيه `/socket.io/` → Laravel يرجع **404** (هذا ما يظهر في Flutter).

راجع: `docs/nginx-socket-proxy.example.conf`

**Android:** للتطوير على `http` فعّل cleartext في `networkSecurityConfig`.

---

## 8. إعادة الاتصال

```dart
socket.onReconnect((_) {
  socket.emit('sync:tables', {});
  if (openTableId != null) {
    socket.emit('join:table', {'table_id': openTableId});
  }
});
```

---

## 9. جدول المحفّزات (متى تتوقع أحداثاً)

| الحدث في المطعم | أحداث Socket |
|-----------------|--------------|
| طلب جديد | `order:created`, `table:updated`, `order:updated` |
| المطبخ: جاهز | `order:status_changed` (`prepared`), `table:updated` |
| نادل: served | `order:status_changed` (`served`), `table:updated` |
| إضافة أصناف | `order:updated` |
| إغلاق طاولة | `table:updated` |
| تغيير حجز | `table:updated` |

---

## 10. Checklist قبول

- [ ] اتصال بـ token صالح على `{tenant}.my-bee.info`
- [ ] رفض بدون token (`UNAUTHORIZED`)
- [ ] تغيير من المطبخ يظهر خلال &lt; 2 ثانية
- [ ] `join:table` يحدّث التفاصيل دون إعادة فتح الشاشة
- [ ] `order_status`: `inpreparation`, `prepared`, `served`
- [ ] إعادة الاتصال + `sync:tables`
- [ ] تجاهل `event_id` المكرر

---

## 11. إجابات أسئلة المواصفة (للفريق)

1. **الدومين:** نفس دومين الـ tenant (`https://{tenant}.my-bee.info`)، منفذ Socket منفصل (3001) خلف reverse proxy إلى `/socket.io/`.
2. **الـ token:** نفس Bearer token الـ REST؛ يُتحقق منه عبر `/api/verify-token`.
3. **النطاق:** غرفة `tenant:{id}` لكل النوادل؛ غرفة `establishment:{id}` عند إرسال `establishment_id` في auth.
4. **أسماء الأحداث:** `table:updated` (بنقطتين) — موحّد مع المواصفة.
5. **حجم payload:** `order:updated` كامل مثل REST؛ لا حد حالياً.

---

## 12. مرجع الملفات في Laravel

- خادم Node: `socket-server/index.js`
- بث من Laravel: `Modules/Reservation/Services/RealtimeBroadcastService.php`
- شكل البيانات: `Modules/Reservation/Support/TableRealtimePayload.php`

## 13) تشخيص سريع (INVALID_TOKEN / timeout)

```bash
# على السيرفر
curl http://127.0.0.1:3001/health
pm2 logs mybee-socket --lines 30

# تحقق التوكن (استبدل TOKEN)
curl -i -H "Authorization: Bearer TOKEN" \
  -H "X-Socket-Secret: YOUR_SECRET" \
  -H "X-Tenant-Id: test1" \
  "http://127.0.0.1/api/internal/realtime/verify-token"

curl -I "https://test1.my-bee.info/socket.io/?EIO=4&transport=polling"
```

`internal verify` يجب **200**. Socket path يجب **ليس 404**.

---

**جهة الاتصال (باك إند):** فريق MyBee Company

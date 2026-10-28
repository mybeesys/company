# My Bee Flutter — خطأ Socket `:0` و HTTP 503

> **الغرض:** ملف لمطوّر Flutter (كاشير / نادل / مطبخ / POS).  
> **التاريخ:** 2026-10-26  
> **الخطأ:** الاتصال يذهب إلى المنفذ `:0` فيرجع `503` ولا يتم ترقية WebSocket.

---

## Cursor — ابدأ من هنا

```text
Read docs/flutter-socket-port-zero-503-ar.md and fix the Socket.IO client URL.
Do not append a port in production. Use https://{tenant}.mybeesystem.net
with .setPath('/socket.io/'). Do not change REST or event names.
```

### Checklist

- [ ] استخدم `realtime` من `POST /api/company-login` أو `GET /api/realtime-config` (لا تبنِ المنفذ يدوياً)
- [ ] `IO.io(realtime.socket_url, …)` — مثال: `https://test1.mybeesystem.net` بدون `:0`
- [ ] `.setPath(realtime.socket_path)` — عادة `/socket.io/`
- [ ] تجاهل `socket_port` إذا كانت `null`؛ **لا** تستخدم `0` ولا `:3001` في الإنتاج
- [ ] Transports: `['websocket', 'polling']`
- [ ] بعد الإصلاح لا يظهر `:0` في لوج الاتصال

---

## 1) الخطأ

```text
WebSocketException: Connection to
'https://test1.mybeesystem.net:0/socket.io/?EIO=4&transport=websocket'
was not upgraded to websocket, HTTP status code: 503
```

المشكلة في عنوان التطبيق، مو في أحداث السوكيت.

| ما يظهر في اللوج | المعنى |
|------------------|--------|
| `:0` | المنفذ صفر. لا يوجد شيء يستمع عليه |
| `503` | البروكسي ما قدر يحوّل الطلب إلى خادم السوكيت |
| `was not upgraded to websocket` | طلب الترقية فشل قبل ما يبدأ Socket.IO |

---

## 2) ليش صار؟

خادم السوكيت يشتغل داخلياً على المنفذ `3001`. Nginx يمرّره على نفس دومين الـ API عبر `443`. التطبيق يتصل بالدومين فقط.

الكود الحالي يبني العنوان هكذا:

```dart
// ❌ لما socketPort = 0 أو null يصير :0
'https://$host:$socketPort'
```

النتيجة:

```text
https://test1.mybeesystem.net:0/socket.io/
```

المنفذ `3001` للسيرفر فقط، مو لتطبيق Flutter في الإنتاج.

---

## 3.1) مصدر العنوان من الـ API (مُفضّل)

بعد `POST /api/company-login` (أو `GET /api/realtime-config` مع Bearer token):

```json
{
  "realtime": {
    "socket_url": "https://test1.mybeesystem.net",
    "socket_path": "/socket.io/",
    "socket_port": null,
    "transports": ["websocket", "polling"],
    "schema_version": 1
  }
}
```

```dart
final rt = loginJson['realtime'] as Map<String, dynamic>;
final url = rt['socket_url'] as String; // لا تضف منفذاً
final path = rt['socket_path'] as String? ?? '/socket.io/';

IO.io(
  url,
  IO.OptionBuilder()
      .setPath(path)
      .setTransports(List<String>.from(rt['transports'] ?? ['websocket', 'polling']))
      …
);
```

---

## 3.2) الحل اليدوي (إن لم تُحمَّل realtime بعد)

```dart
import 'package:socket_io_client/socket_io_client.dart' as IO;

String socketBaseUrl(String tenantId) {
  final rest = ApiConstants.baseUrl; // مثال: https://test1.mybeesystem.net/api
  final uri = Uri.parse(rest);
  return '${uri.scheme}://${uri.host}'; // https://test1.mybeesystem.net
}

IO.Socket connectSocket({
  required String tenantId,
  required String token,
  required int establishmentId,
}) {
  return IO.io(
    socketBaseUrl(tenantId),
    IO.OptionBuilder()
        .setPath('/socket.io/')
        .setTransports(['websocket', 'polling'])
        .enableReconnection()
        .setAuth({
          'token': token,
          'tenant_id': tenantId,
          'establishment_id': establishmentId,
        })
        .build(),
  );
}
```

| صح | غلط |
|----|------|
| `https://test1.mybeesystem.net` | `https://test1.mybeesystem.net:0` |
| `.setPath('/socket.io/')` | `https://test1.mybeesystem.net/socket.io` كـ host |
| `https://` | `http://test1.mybeesystem.net` أو `:80` |
| بدون منفذ في الإنتاج | `https://test1.mybeesystem.net:3001` |

`http://IP:3001` للتجربة المباشرة على Node فقط، مو لبناء الإنتاج.

---

## 4) إذا بقي 503 بعد حذف `:0`

وقتها المشكلة على السيرفر: عملية السوكيت واقفة، أو توجيه `/socket.io/` ناقص. هذا مو تعديل Flutter.

```bash
curl http://127.0.0.1:3001/health
curl -Ik "https://test1.mybeesystem.net/socket.io/?EIO=4&transport=polling"
```

الأول لازم ينجح على السيرفر. الثاني لازم ما يرجع `503`.

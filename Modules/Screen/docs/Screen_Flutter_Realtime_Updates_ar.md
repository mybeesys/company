# My Bee — Screen Player & Admin — تحديثات WebSocket (يونيو 2026)

> **للمطوّر Flutter** — Playlist sync + فصل الشاشة  
> **الحالة:** متوافق مع النظام الحي — لا يغيّر `/socket.io/` (مطبخ / ويتر / كاشيير)

---

## 1. ملخص

| الميزة         | الحدث                     | من يرسل                   | من يستقبل                              |
| -------------- | ------------------------- | ------------------------- | -------------------------------------- |
| ربط QR (موجود) | `screen.linked`           | Admin بعد مسح QR          | Player (قناة `screen.pairing.{64hex}`) |
| تحديث Playlist | `screen.playlist.updated` | Laravel (API + Dashboard) | Player (قناة `screen.device.{id}`)     |
| فصل الشاشة     | `screen.unlinked`         | Admin API                 | Player (قناة `screen.device.{id}`)     |

**قاعدة:** بعد الربط الناجح، Player **يبقى متصلاً** على `/ws` ويشترك بقناة الجهاز — لا يقطع WebSocket.

---

## 2. تدفق Player (كامل)

```
1. يولّد pairing_id (64 hex) ويعرض QR
2. WS → subscribe screen.pairing.{pairing_id}
3. يستقبل screen.linked → يحفظ token + device.id + device_channel
4. WS → subscribe screen.device.{device.id}   ← جديد (بدون قطع الاتصال)
5. عند screen.playlist.updated → refetch playlists من REST
6. عند screen.unlinked → مسح token + العودة لشاشة QR
```

---

## 3. WebSocket — قناة الجهاز (بعد الربط)

### 3.1 الاشتراك

```json
{
    "event": "subscribe",
    "channel": "screen.device.12",
    "id": "12"
}
```

| الحقل     | القيمة                                                                          |
| --------- | ------------------------------------------------------------------------------- |
| `channel` | `screen.device.{device_id}` — يأتي أيضاً في `screen.linked` كـ `device_channel` |
| `id`      | نفس `device.id` (رقم موجب)                                                      |

**رد السيرفر:**

```json
{
    "event": "subscribed",
    "channel": "screen.device.12",
    "id": "12"
}
```

### 3.2 `screen.linked` — حقل جديد

```json
{
    "event": "screen.linked",
    "device_channel": "screen.device.12",
    "device": { "id": 12, "code": "SCR-001" },
    "token": "1|...",
    "api_base_url": "https://test1.mybeesystem.net"
}
```

---

## 4. حدث `screen.playlist.updated`

**يُرسل عند:** إنشاء / تعديل / حذف playlist من **Admin API** أو **Dashboard**.

```json
{
    "event": "screen.playlist.updated",
    "channel": "screen.device.12",
    "device_id": 12,
    "tenant_id": "test1",
    "playlist_id": 5,
    "action": "updated",
    "timestamp": "2026-06-12T23:30:00+00:00"
}
```

| `action`  | المعنى                        |
| --------- | ----------------------------- |
| `created` | playlist جديدة مرتبطة بالجهاز |
| `updated` | تعديل (promos، أوقات، أجهزة…) |
| `deleted` | حُذفت playlist                |

**إجراء Player (موصى به):**

```dart
void onPlaylistUpdated(Map<String, dynamic> msg) {
  final playlistId = msg['playlist_id'];
  if (msg['action'] == 'deleted') {
    removePlaylistFromCache(playlistId);
  } else {
    refetchPlaylist(playlistId); // GET /api/v1/screen/player/playlists/{id}
  }
  // أو refetch كامل:
  // GET /api/v1/screen/player/playlists
}
```

> WebSocket = **إشعار فقط** — المحتوى والـ URLs من REST.

---

## 5. حدث `screen.unlinked`

**يُرسل عند:** Admin يفصل الشاشة (API أدناه).

```json
{
    "event": "screen.unlinked",
    "channel": "screen.device.12",
    "device_id": 12,
    "tenant_id": "test1",
    "device": { "id": 12, "code": "SCR-001" },
    "reason": "admin_unlink"
}
```

**إجراء Player:**

```dart
void onUnlinked() {
  clearStoredToken();
  clearDeviceSession();
  navigateToPairingScreen();
  // أعد subscribe لـ screen.pairing.{newPairingId} عند QR جديد
}
```

---

## 6. Admin API — فصل الشاشة

```
POST /api/admin/v1/screen/devices/{device_id}/unlink
```

| البند          | القيمة                                        |
| -------------- | --------------------------------------------- |
| **Host**       | `https://{tenant}.mybeesystem.net`            |
| **Auth**       | `Authorization: Bearer {company_login_token}` |
| **Rate limit** | 30 طلب/دقيقة                                  |

**Body (اختياري):**

```json
{
    "reason": "admin_disconnect"
}
```

**Response 200:**

```json
{
    "message": "تم فصل الشاشة بنجاح.",
    "device": { "id": 12, "code": "SCR-001" },
    "tokens_revoked": 1
}
```

> **لا يحذف** سجل الجهاز — فقط يلغي توken Player ويرسل `screen.unlinked`.

---

## 7. Admin — تسجيل الدخول (بدون تغيير)

```
1. POST /api/company-login  → Bearer token
2. كل /api/admin/v1/screen/* بنفس التوken
```

مسارات Admin (نفس `admin.php`):

| Method   | Path                                                  |
| -------- | ----------------------------------------------------- |
| POST     | `/api/admin/v1/screen/auth/token` — ربط QR            |
| POST     | `/api/admin/v1/screen/devices/{id}/unlink` — **فصل**  |
| GET/POST | `/api/admin/v1/screen/playlists` — CRUD (+ WS تلقائي) |
| GET/POST | `/api/admin/v1/screen/devices`                        |
| GET/POST | `/api/admin/v1/screen/promos`                         |

---

## 8. Player REST (بعد الربط)

| Method | Path                                          |
| ------ | --------------------------------------------- |
| GET    | `/api/v1/screen/player/playlists`             |
| GET    | `/api/v1/screen/player/playlists/{id}`        |
| GET    | `/api/v1/screen/player/playlists/{id}/promos` |

**Auth:** `Authorization: Bearer {token من screen.linked}`

---

## 9. Dart — مثال WS بعد الربط

```dart
import 'dart:convert';
import 'package:web_socket_channel/web_socket_channel.dart';

class ScreenRealtimeSocket {
  WebSocketChannel? _channel;
  String? _deviceChannel;

  void connect(String host) {
    _channel = WebSocketChannel.connect(Uri.parse('ws://$host/ws'));
    _channel!.stream.listen(_onMessage);
  }

  void subscribePairing(String pairingIdHex64) {
    _sendSubscribe('screen.pairing.$pairingIdHex64', pairingIdHex64);
  }

  void subscribeDevice(int deviceId) {
    final id = deviceId.toString();
    _deviceChannel = 'screen.device.$id';
    _sendSubscribe(_deviceChannel!, id);
  }

  void _sendSubscribe(String channel, String id) {
    _channel?.sink.add(jsonEncode({
      'event': 'subscribe',
      'channel': channel,
      'id': id,
    }));
  }

  void _onMessage(dynamic raw) {
    final msg = jsonDecode(raw as String) as Map<String, dynamic>;
    switch (msg['event']) {
      case 'screen.linked':
        final deviceId = msg['device']?['id'] as int?;
        if (deviceId != null) subscribeDevice(deviceId);
        break;
      case 'screen.playlist.updated':
        // refetch REST
        break;
      case 'screen.unlinked':
        // logout player UI
        break;
    }
  }
}
```

---

## 10. Checklist Flutter

- [ ] بعد `screen.linked` → `subscribe` على `device_channel`
- [ ] `screen.playlist.updated` → refetch REST (لا تعتمد على WS للمحتوى)
- [ ] `screen.unlinked` → مسح token + QR
- [ ] Admin unlink يستدعي `POST .../devices/{id}/unlink`
- [ ] Admin playlists CRUD — **لا** حاجة لـ WS من Flutter Admin (السيرفر يبث للـ Player)

---

## 11. Deploy على السيرفر (بعد git pull)

```bash
cd /var/www/mybeeCompany
git pull

# Laravel
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Socket server (مهم — فروع broadcast جديدة)
cd socket-server
npm install
pm2 restart mybee-socket --update-env

# تحقق
curl http://127.0.0.1:3001/health
# يجب أن يظهر: "screen_device_channel_prefix": "screen.device."

php artisan route:list | grep unlink
# admin.v1.screen.devices.unlink
```

**Apache:** لا تغيير — `/ws` موجود.  
**لا** touch `/socket.io/`.

---

## 12. اختبار يدوي

```bash
# 1) Player متصل و subscribe screen.device.12
# 2) من Admin — عدّل playlist مربوط بالجهاز 12
# 3) Player يستقبل screen.playlist.updated

curl -X POST "http://test1.mybeesystem.net/api/admin/v1/screen/devices/12/unlink" \
  -H "Authorization: Bearer COMPANY_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"reason":"test"}'
# Player يستقبل screen.unlinked
```

---

**مرجع:** `Screen_Pairing_Frontend_API.md` — ربط QR  
**Backend:** `ScreenDeviceBroadcastService`, `ScreenPlaylistSyncNotifier`, `ScreenDeviceUnlinkService`

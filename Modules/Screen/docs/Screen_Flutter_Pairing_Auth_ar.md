# My Bee — Screen Pairing & Auth (Flutter / Player + Admin)

> **للمطور (Flutter):** هذا الملف يشرح **التعديلات الجديدة** لربط شاشة العرض.  
> مرّره لـ Cursor عند تطوير تطبيق **Player** أو **Admin**.  
> **آخر تحديث:** يوليو 2026

---

## ملخص سريع

| طريقة الربط  | من يبدأ؟         | آلية التسليم                     | مسار Player          |
| ------------ | ---------------- | -------------------------------- | -------------------- |
| **QR**       | الشاشة تعرض QR   | WebSocket `screen.linked`        | لا REST — انتظر WS   |
| **PIN مؤقت** | الأدمن يولّد PIN | REST `POST player/auth/pair-pin` | يستلم `token` مباشرة |

بعد الربط بأي طريقة:

- خزّن `token` + `device.id` + `device_channel`
- اشترك في WebSocket على `device_channel` لتحديثات Playlist وفصل الجلسة

---

## 1. الربط عبر QR (موجود — بدون تغيير جوهري)

### تدفق Player

```
1. ولّد pairing_id محلياً (64 hex lowercase)
2. اعرض QR = pairing_id
3. WS → subscribe على screen.pairing.{pairing_id}
4. انتظر حدث screen.linked (لا polling)
5. احفظ token + device + device_channel
6. WS → subscribe على device_channel
```

### Admin — مسح QR

```
POST /api/admin/v1/screen/auth/token
Authorization: Bearer {admin_token}
```

```json
{
    "pairing_id": "1a37d81c...64hex",
    "device_code": "SCR-001",
    "establishment_id": 2
}
```

**Response 200:** رسالة نجاح + `device` — **الشاشة لا تقرأ هذا الرد**؛ تعتمد على WebSocket فقط.

تفاصيل WebSocket: راجع `Screen_Pairing_Frontend_API.md` و `Screen_Flutter_Realtime_Updates_ar.md`.

---

## 2. الربط عبر PIN مؤقت (جديد)

### الفكرة

1. **الأدمن** يختار جهازاً ويطلب PIN مؤقت (افتراضي **6 أرقام**، صلاحية **120 ثانية**).
2. يعرض PIN للمشغّل (أو يقرأه له).
3. **Player** يدخل PIN ويستدعي API التحقق.
4. يستلم `token` في الرد مباشرة (لا حاجة لانتظار WebSocket للربط — لكن يُرسل أيضاً `screen.linked` على `device_channel` للتوافق).

### 2.1 Admin — توليد PIN

```
POST /api/admin/v1/screen/devices/{device_id}/pairing-pin
Authorization: Bearer {admin_token}
```

| البند          | القيمة                 |
| -------------- | ---------------------- |
| **Rate limit** | 30 طلب/دقيقة           |
| **Body**       | فارغ `{}` أو بدون body |

**Response 200:**

```json
{
    "message": "تم توليد رمز PIN للربط. أدخله على الشاشة خلال المدة المحددة.",
    "pin": "482913",
    "expires_at": "2026-07-01T12:02:00+00:00",
    "expires_in_seconds": 120,
    "device": {
        "id": 12,
        "code": "SCR-001"
    }
}
```

| الحقل                      | ملاحظة                                                      |
| -------------------------- | ----------------------------------------------------------- |
| `pin`                      | **يُعرض مرة واحدة** للأدمن — لا يُخزَّن في السيرفر كنص صريح |
| `expires_in_seconds`       | من الإعداد `SCREEN_PAIRING_PIN_TTL_SECONDS` (افتراضي 120)   |
| توليد PIN جديد لنفس الجهاز | يلغي أي PIN نشط سابق لذلك الجهاز                            |

**واجهة Admin مقترحة:**

- زر «ربط بـ PIN» بجانب «مسح QR»
- عدّاد تنازلي حتى `expires_at`
- إخفاء PIN بعد الربط أو انتهاء الوقت

### 2.2 Player — التحقق من PIN

```
POST /api/v1/screen/player/auth/pair-pin
```

**بدون مصادقة** — Rate limit: 20 طلب/دقيقة

```json
{
    "pin": "482913"
}
```

| الحقل | النوع  | مطلوب | الوصف                                                      |
| ----- | ------ | ----- | ---------------------------------------------------------- |
| `pin` | string | نعم   | أرقام فقط، الطول = `SCREEN_PAIRING_PIN_LENGTH` (افتراضي 6) |

**Response 200:**

```json
{
    "message": "تم ربط الشاشة بنجاح.",
    "token": "1|xxxxxxxx",
    "token_type": "Bearer",
    "expires_at": "2027-07-01T12:00:00+00:00",
    "device_channel": "screen.device.12",
    "device": {
        "id": 12,
        "code": "SCR-001",
        "establishment_id": 2,
        "establishment_name": "فرع الشمال"
    }
}
```

**أخطاء:**

| HTTP  | السبب                                 |
| ----- | ------------------------------------- |
| `422` | PIN غير صالح، منتهي، أو مستخدم مسبقاً |
| `429` | تجاوز حد الطلبات                      |

### 2.3 تدفق Player (PIN) — كود مقترح

```dart
Future<void> pairWithPin(String pin) async {
  final res = await api.post('/api/v1/screen/player/auth/pair-pin', body: {'pin': pin});
  await secureStorage.write(key: 'screen_token', value: res['token']);
  await secureStorage.write(key: 'device_id', value: res['device']['id'].toString());
  final channel = res['device_channel'] as String;
  socket.subscribe(channel, id: res['device']['id'].toString());
  navigateToPlayerHome();
}
```

### 2.4 شاشة اختيار طريقة الربط (Player)

```
┌─────────────────────────────┐
│   ربط شاشة العرض            │
├─────────────────────────────┤
│  [ عرض QR للمسح من الأدمن ] │
│  ─────────── أو ─────────── │
│  [ إدخال PIN من الأدمن   ] │
└─────────────────────────────┘
```

---

## 3. فصل الشاشة — `devices/{device}/unlink`

يستخدمه **تطبيق الأدمن** لفصل جلسة Player عن الجهاز (مثلاً قبل إعادة الربط أو عند استبدال الجهاز).

### الطلب

```
POST /api/admin/v1/screen/devices/{device_id}/unlink
Authorization: Bearer {admin_token}
Content-Type: application/json
```

```json
{
    "reason": "admin_unlink"
}
```

| الحقل    | النوع  | مطلوب | الوصف                                   |
| -------- | ------ | ----- | --------------------------------------- |
| `reason` | string | لا    | سبب اختياري (يُرسل في WebSocket للشاشة) |

### Response 200

```json
{
    "message": "تم فصل الشاشة بنجاح.",
    "device": {
        "id": 12,
        "code": "SCR-001"
    },
    "tokens_revoked": 1
}
```

### ماذا يحدث في الخلفية؟

1. يُحذف توكن Player (`screen-player-api`) لهذا الجهاز.
2. يُرسل حدث WebSocket **`screen.unlinked`** على قناة `screen.device.{device_id}`.
3. **لا يُحذف** سجل الجهاز من قاعدة البيانات — فقط تُلغى الجلسة.

### ماذا يفعل Player عند `screen.unlinked`؟

```dart
void onScreenUnlinked(Map<String, dynamic> msg) {
  clearToken();
  clearDeviceSession();
  disconnectDeviceChannel();
  showPairingScreen(); // QR أو PIN
}
```

**Payload الحدث:**

```json
{
    "event": "screen.unlinked",
    "channel": "screen.device.12",
    "device_id": 12,
    "device": { "id": 12, "code": "SCR-001" },
    "reason": "admin_unlink"
}
```

### متى يستدعي الأدمن unlink؟

- قبل ربط شاشة جديدة على نفس الجهاز إن بقي توكن قديم.
- عند سرقة/استبدال جهاز العرض.
- من لوحة الأجهزة: زر «فصل الشاشة».

### مثال cURL

```bash
curl -X POST "https://test1.mybeesystem.net/api/admin/v1/screen/devices/12/unlink" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"reason":"admin_unlink"}'
```

---

## 4. مقارنة طرق المصادقة

|                 | QR                    | PIN مؤقت                | `player/auth/token` (قديم)      |
| --------------- | --------------------- | ----------------------- | ------------------------------- |
| **الاستخدام**   | ربط أولي موصى به      | ربط بدون كاميرا         | إعادة دخول بـ `device_code` فقط |
| **يتطلب أدمن**  | نعم (مسح QR)          | نعم (توليد PIN)         | لا                              |
| **مدة PIN/QR**  | pairing_id لمرة واحدة | ~2 دقيقة                | —                               |
| **توكن Player** | عبر WS                | عبر REST (+ WS اختياري) | عبر REST                        |
| **Ability**     | `screen:player`       | `screen:player`         | `screen:player`                 |

> **ملاحظة:** `pin_hash` الثابت على الجهاز + `/api/v1/screen/auth/token` (واجهة إدارية قديمة) **مختلف** عن PIN الربط المؤقت الجديد.

---

## 5. مسارات Admin كاملة (مرجع)

| Method              | Path                                            | الوصف                |
| ------------------- | ----------------------------------------------- | -------------------- |
| POST                | `/api/admin/v1/screen/auth/token`               | ربط QR               |
| POST                | `/api/admin/v1/screen/devices/{id}/pairing-pin` | **جديد** — توليد PIN |
| POST                | `/api/admin/v1/screen/devices/{id}/unlink`      | فصل الشاشة           |
| GET                 | `/api/admin/v1/screen/devices`                  | قائمة الأجهزة        |
| GET/POST/PUT/DELETE | `/api/admin/v1/screen/playlists`                | قوائم التشغيل        |
| GET/POST/PUT/DELETE | `/api/admin/v1/screen/promos`                   | المواد               |

**Auth:** `Authorization: Bearer {token}` — middleware `auth-central` (نفس توكن POS/Admin).

---

## 6. مسارات Player (بعد الربط)

Base: `https://{tenant}/api/v1/screen/player`

| Method | Path                     | Auth                              |
| ------ | ------------------------ | --------------------------------- |
| POST   | `/auth/pair-pin`         | **جديد** — ربط بـ PIN             |
| POST   | `/auth/token`            | إعادة إصدار توكن بـ `device_code` |
| GET    | `/me`                    | Bearer                            |
| GET    | `/playlists`             | Bearer                            |
| GET    | `/playlists/{id}/promos` | Bearer                            |
| POST   | `/auth/revoke`           | Bearer                            |

تفاصيل Player: `Screen_Player_API.md`

---

## 7. WebSocket بعد الربط

| القناة                        | متى                           |
| ----------------------------- | ----------------------------- |
| `screen.pairing.{pairing_id}` | أثناء انتظار QR فقط           |
| `screen.device.{device_id}`   | بعد الربط — Playlist + unlink |

أحداث مهمة:

- `screen.linked` — اكتمل الربط
- `screen.unlinked` — فصل من الأدمن
- `screen.playlist.updated` — تحديث قائمة تشغيل

راجع: `Screen_Flutter_Realtime_Updates_ar.md`

---

## 8. إعدادات السيرفر (.env)

| المتغير                          | افتراضي | الوصف                     |
| -------------------------------- | ------- | ------------------------- |
| `SCREEN_PAIRING_PIN_TTL_SECONDS` | `120`   | مدة صلاحية PIN            |
| `SCREEN_PAIRING_PIN_LENGTH`      | `6`     | عدد أرقام PIN             |
| `SCREEN_API_TOKEN_TTL_DAYS`      | `365`   | مدة توكن Player بعد الربط |

---

## 9. Migration (Tenant)

```bash
php artisan tenants:migrate --path=Modules/Screen/database/migrations/tenant --force
```

جدول جديد: `screen_pairing_pins`

---

## 10. Checklist تطوير Flutter

### Admin App

- [ ] زر توليد PIN → `POST .../devices/{id}/pairing-pin`
- [ ] عدّاد تنازلي لـ `expires_in_seconds`
- [ ] زر فصل → `POST .../devices/{id}/unlink`
- [ ] مسح QR → `POST .../auth/token` (موجود)

### Player App

- [ ] شاشة ربط: QR **أو** إدخال PIN
- [ ] QR: WS subscribe + انتظار `screen.linked`
- [ ] PIN: `POST .../player/auth/pair-pin` + حفظ token
- [ ] بعد الربط: subscribe `device_channel`
- [ ] `screen.unlinked` → مسح الجلسة والعودة للربط

---

## 11. أمثلة cURL سريعة

### توليد PIN (Admin)

```bash
curl -X POST "https://test1.mybeesystem.net/api/admin/v1/screen/devices/12/pairing-pin" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Accept: application/json"
```

### التحقق من PIN (Player)

```bash
curl -X POST "https://test1.mybeesystem.net/api/v1/screen/player/auth/pair-pin" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"pin":"482913"}'
```

---

_My Bee — Screen Module — Pairing QR + PIN + Unlink_

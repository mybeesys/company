# My Bee — Screen Pairing (WebSocket + Admin API)

> **للمطورين:** تطبيق الشاشة (Player) + تطبيق الأدمن (Admin)  
> **آخر تحديث:** مايو 2026

---

## 1. ملخص التدفق

```
[Screen Player]                    [Socket Server]                 [Admin App]
      |                                  |                              |
      | pairing_id في QR (64 hex)        |                              |
      | WS connect → subscribe           |                              |
      |------------------------------->|                              |
      |                                  |  POST auth/token + pairing_id|
      |                                  |<-----------------------------|
      |  event: screen.linked            |                              |
      |<-------------------------------|                              |
      | يحفظ token + tenant              |                              |
      | GET /api/v1/screen/player/...    |                              |
```

| # | قاعدة |
|---|--------|
| 1 | **لا polling** أثناء انتظار الربط — WebSocket فقط |
| 2 | **QR = `pairing_id` خام** (64 hex، lowercase) |
| 3 | **`pairing_id` يُولَّد على الجهاز** ولا يُطلب من API |
| 4 | **`pairing_id` لمرة واحدة** — بعد الربط لا يُعاد استخدامه |
| 5 | الشاشة **لا تعتمد** على response الـ REST — فقط حدث `screen.linked` |

---

## 2. WebSocket (Screen Player)

### 2.1 عنوان الاتصال

| البيئة | URL |
|--------|-----|
| HTTP | `ws://{tenant-host}/ws` |
| HTTPS | `wss://{tenant-host}/ws` |

**مثال:** `wss://test1.my-bee.info/ws`

> في الإنتاج: Nginx يوجّه `/ws` إلى `socket-server` (المنفذ الافتراضي `3001`).  
> متغير Laravel: `SOCKET_BROADCAST_URL=http://127.0.0.1:3001/broadcast`

### 2.2 رسالة الاشتراك (Player → Server)

```json
{
  "event": "subscribe",
  "channel": "screen.pairing.{pairing_id}",
  "id": "{pairing_id}"
}
```

**رد السيرفر (اختياري):**

```json
{
  "event": "subscribed",
  "channel": "screen.pairing.1a37d81c...",
  "id": "1a37d81c..."
}
```

### 2.3 حدث الربط (Server → Player)

**اسم الحدث:** `screen.linked`

```json
{
  "event": "screen.linked",
  "channel": "screen.pairing.1a37d81ce12db8df36bbb2308dc929b92fa10f4f181694217551c7e2786f6ac7",
  "id": "1a37d81ce12db8df36bbb2308dc929b92fa10f4f181694217551c7e2786f6ac7",
  "tenant_id": "test1",
  "token": "1|xxxxxxxxxxxxxxxx",
  "api_base_url": "https://test1.my-bee.info",
  "expires_at": "2027-06-11T10:00:00+00:00",
  "device": {
    "id": 12,
    "code": "SCR-001",
    "establishment_id": 2,
    "establishment_name": "فرع الشمال"
  }
}
```

| الحقل | إلزامي | ملاحظة |
|--------|--------|--------|
| `id` | نعم | **يساوي** `pairing_id` في QR حرفياً |
| `tenant_id` | نعم | slug المستأجر (`test1`) |
| `token` | نعم | Bearer — ability `screen:player` |
| `api_base_url` | موصى به | بدون `/api` |
| `device.code` | موصى به | كود الجهاز في النظام |

---

## 3. Admin API — ربط QR

### Endpoint (نهائي)

```
POST /api/admin/v1/screen/auth/token
```

| البند | القيمة |
|--------|--------|
| **Host** | نطاق المستأجر فقط (`https://test1.my-bee.info`) |
| **Auth** | `Authorization: Bearer {admin_token}` — نفس `auth-central` (POS / Admin) |
| **Rate limit** | 15 طلب/دقيقة |

> **تغيير مسارات Admin:** كل واجهات إدارة الشاشات أصبحت تحت  
> `/api/admin/v1/screen/...`  
> (مثال: `GET /api/admin/v1/screen/devices`)

### Request body

```json
{
  "pairing_id": "1a37d81ce12db8df36bbb2308dc929b92fa10f4f181694217551c7e2786f6ac7",
  "device_code": "SCR-001",
  "establishment_id": 2
}
```

| الحقل | النوع | مطلوب | الوصف |
|--------|--------|--------|--------|
| `pairing_id` | string | نعم | 64 حرف hex (محتوى QR) |
| `device_code` | string | نعم | كود الجهاز — يُنشأ إن لم يوجد |
| `establishment_id` | int | نعم* | الفرع — *إذا عمود `establishment_id` موجود في DB |

### Response `200`

```json
{
  "message": "تم ربط الشاشة بنجاح.",
  "device": {
    "id": 12,
    "code": "SCR-001"
  }
}
```

> **الشاشة لا تقرأ هذا الـ response** — تعتمد على WebSocket فقط.

### أخطاء شائعة

| HTTP | السبب |
|------|--------|
| `401` | توكن أدمن غير صالح |
| `422` | `pairing_id` ليس 64 hex، أو `establishment_id` ناقص |
| `422` | `pairing_id` مستخدم مسبقاً (`screen_pairing_already_used`) |

### مثال cURL

```bash
curl -X POST "https://test1.my-bee.info/api/admin/v1/screen/auth/token" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "pairing_id": "1a37d81ce12db8df36bbb2308dc929b92fa10f4f181694217551c7e2786f6ac7",
    "device_code": "SCR-001",
    "establishment_id": 2
  }'
```

---

## 4. Player API (بعد الربط)

Base: `{api_base_url}/api/v1/screen/player`

| Method | Path | Auth |
|--------|------|------|
| GET | `/playlists` | Bearer `screen:player` |
| GET | `/playlists/{id}` | Bearer |
| GET | `/playlists/{id}/promos` | Bearer |
| GET | `/promos` | Bearer |
| POST | `/auth/revoke` | Bearer |

التفاصيل الكاملة: [Screen_Player_API.md](./Screen_Player_API.md)

---

## 5. مسارات Admin الأخرى (تحديث)

| قبل | بعد |
|-----|-----|
| `/api/admin/dashboard` | `/api/admin/v1/screen/dashboard` |
| `/api/admin/devices` | `/api/admin/v1/screen/devices` |
| `/api/admin/playlists` | `/api/admin/v1/screen/playlists` |
| `/api/admin/promos` | `/api/admin/v1/screen/promos` |

---

## 6. البنية التحتية

| متغير | الوصف |
|--------|--------|
| `SOCKET_BROADCAST_URL` | Laravel → `POST .../broadcast` |
| `SOCKET_INTERNAL_SECRET` | سر داخلي لـ `/broadcast` |
| `SOCKET_PORT` | منفذ socket-server (افتراضي `3001`) |
| `SCREEN_API_TOKEN_TTL_DAYS` | مدة توكن Player |

**تشغيل socket-server:**

```bash
cd socket-server
npm install
npm start
```

**Migration (tenant):**

```bash
php artisan tenants:migrate --path=Modules/Screen/database/migrations/tenant --force
```

---

## 7. Checklist اختبار

- [ ] Player يتصل بـ `wss://{tenant}/ws` ويرسل `subscribe`
- [ ] Admin يمسح QR ويستدعي `POST /api/admin/v1/screen/auth/token`
- [ ] Player يستلم `screen.linked` خلال ثوانٍ
- [ ] `id` في الحدث = QR
- [ ] `pairing_id` مكرر → 422
- [ ] Player يجلب playlists بـ `token`

---

*My Bee — Screen Module*

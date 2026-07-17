# My Bee — Screen Player API (واجهة تشغيل الجهاز)

واجهة برمجة **منفصلة** عن واجهة الإدارة الحالية (`/api/v1/screen/...`).  
مخصّصة لتطبيق الشاشة على الجهاز (Player): تسجيل دخول بـ **كود الجهاز** (`device_code`)، ثم جلب قوائم التشغيل والمواد الإعلانية **التابعة لهذا الجهاز فقط**.

---

## الأساسيات

| البند                 | القيمة                                                                    |
| --------------------- | ------------------------------------------------------------------------- |
| **Base URL**          | `https://{tenant-host}/api/v1/screen/player`                              |
| **المستأجر (Tenant)** | الطلبات تُرسل إلى نطاق المستأجر فقط (مثل `https://test1.mybeesystem.net`) |
| **المصادقة**          | Bearer Token (Laravel Sanctum)                                            |
| **صلاحية التوكن**     | Ability: `screen:player` (مختلف عن توكن الإدارة `screen:api`)             |
| **مدة التوكن**        | حسب إعداد `SCREEN_API_TOKEN_TTL_DAYS` (افتراضي 365 يوماً)                 |
| **Rate limit**        | `POST auth/token` — 20 طلب/دقيقة                                          |

### Headers موصى بها

```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}   ← للمسارات المحمية فقط
```

---

## تدفق العمل (Flow)

```
1. من لوحة التحكم: يُنشأ جهاز ويُعطى كود (code) مثل SCR-001.
2. التطبيق يخزّن محلياً:
   - device_code  ← كود الجهاز (مطلوب للمصادقة)
   - device_id    ← (اختياري) معرّف يولّده التطبيق/الجهاز ويخزّنه عنده — ليس معرّف النظام
3. POST /player/auth/token  ← يرسل device_code (+ device_id اختياري)
4. يستلم token ويخزّنه
5. يستخدم token لطلب:
   - GET /player/playlists
   - GET /player/playlists/{id}/promos
   - GET /player/promos
   ...
```

> **ملاحظة:** واجهة الإدارة القديمة (`/api/v1/screen/auth/token` مع PIN أو QR) **لم تتغيّر** وتبقى للاستخدام الإداري/القديم.

---

## 1. إصدار توكن — `POST /auth/token`

**بدون مصادقة**

### Request body (JSON)

```json
{
    "device_code": "SCR-001",
    "device_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890"
}
```

| الحقل         | النوع  | مطلوب | الوصف                                                                                                                      |
| ------------- | ------ | ----- | -------------------------------------------------------------------------------------------------------------------------- |
| `device_code` | string | نعم   | كود الجهاز كما في لوحة التحكم (`screen_devices.code`) — **التحقق يعتمد عليه فقط**                                          |
| `device_id`   | string | لا    | معرّف يخزّنه التطبيق محلياً (UUID أو hardware id). **لا يُتحقق منه حالياً** — يُعاد في الرد إن أُرسل (للاستخدام المستقبلي) |

### Response `200`

```json
{
    "token": "1|xxxxxxxxxxxxxxxx",
    "token_type": "Bearer",
    "expires_at": "2027-06-11T10:00:00+00:00",
    "device_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "device": {
        "id": 12,
        "code": "SCR-001",
        "establishment_id": 2,
        "establishment_name": "فرع الشمال"
    }
}
```

> `device.id` داخل `device` هو معرّف السجل في النظام (للمرجع).  
> `device_id` في جذر الرد هو نفس القيمة الاختيارية التي أرسلها التطبيق (echo).

### أخطاء

| HTTP  | السبب                               |
| ----- | ----------------------------------- |
| `401` | `device_code` غير موجود أو غير صحيح |
| `422` | حقول ناقصة أو غير صالحة             |
| `429` | تجاوز حد الطلبات (throttle)         |

---

## 2. بيانات الجهاز الحالي — `GET /me`

**يتطلب:** Bearer token (`screen:player`)

### Response `200`

```json
{
    "data": {
        "id": 12,
        "code": "SCR-001",
        "establishment_id": 2,
        "establishment_name": "فرع الشمال"
    }
}
```

---

## 3. إلغاء التوكن — `POST /auth/revoke`

**يتطلب:** Bearer token

### Response `200`

```json
{
    "message": "تم إلغاء التوكن."
}
```

---

## 4. قوائم التشغيل — Playlists

### `GET /playlists`

قوائم التشغيل المرتبطة **بهذا الجهاز فقط**.

```json
{
    "data": [
        {
            "id": 5,
            "name": "قائمة الصباح",
            "screen_orientation": "landscape",
            "days_settings": {
                "days_settings_option": "every_day",
                "start_time": "08:00",
                "transition_seconds": 10
            },
            "promos_count": 4,
            "updated_at": "2026-06-01T12:00:00+00:00"
        }
    ]
}
```

### `GET /playlists/{playlist_id}`

تفاصيل قائمة واحدة (فقط إن كانت مرتبطة بالجهاز).

```json
{
    "data": {
        "id": 5,
        "name": "قائمة الصباح",
        "screen_orientation": "landscape",
        "days_settings": {},
        "selected_promos": [1, 3, 7],
        "created_at": "2026-05-01T08:00:00+00:00",
        "updated_at": "2026-06-01T12:00:00+00:00"
    }
}
```

### `GET /playlists/{playlist_id}/promos`

المواد الإعلانية داخل القائمة بترتيب العرض، مع روابط الملفات.

```json
{
    "data": [
        {
            "id": 1,
            "name": "إعلان صيفي",
            "path": "promos/abc.mp4",
            "media_url": "https://tenant.mybeesystem.net/storage/tenant1/promos/abc.mp4",
            "thumbnail": "promos/abc_thumb.jpg",
            "thumbnail_url": "https://tenant.mybeesystem.net/storage/tenant1/promos/abc_thumb.jpg",
            "created_at": "2026-04-01T10:00:00+00:00",
            "updated_at": "2026-04-01T10:00:00+00:00"
        }
    ]
}
```

| HTTP  | السبب                                        |
| ----- | -------------------------------------------- |
| `404` | القائمة غير موجودة أو غير مرتبطة بهذا الجهاز |

---

## 5. المواد الإعلانية — Promos

### `GET /promos`

كل المواد الإعلانية المستخدمة في **أي** قائمة تشغيل مرتبطة بالجهاز (بدون تكرار).

### `GET /promos/{promo_id}`

مادة واحدة (فقط إن كانت ضمن قوائم الجهاز).

| HTTP  | السبب                                             |
| ----- | ------------------------------------------------- |
| `404` | المادة غير موجودة أو غير مرتبطة بقوائم هذا الجهاز |

---

## الفرق بين واجهة Player وواجهة الإدارة

|                         | **Player** (`/player/...`)                       | **Admin** (`/screen/...`)                   |
| ----------------------- | ------------------------------------------------ | ------------------------------------------- |
| **الغرض**               | تشغيل على الجهاز                                 | إدارة كاملة من التطبيق/اللوحة               |
| **التوكن**              | `screen:player`                                  | `screen:api`                                |
| **طلب التوكن**          | `device_code` (+ `device_id` اختياري من التطبيق) | PIN + `device_code` أو `pairing_token` (QR) |
| **Playlists**           | قراءة فقط، مفلترة بالجهاز                        | CRUD كامل                                   |
| **Promos**              | قراءة فقط، مفلترة بقوائم الجهاز                  | CRUD + رفع ملفات                            |
| **Devices / Dashboard** | غير متاح                                         | متاح                                        |

---

## أمثلة cURL

### إصدار توكن

```bash
curl -X POST "https://test1.mybeesystem.net/api/v1/screen/player/auth/token" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"device_code":"SCR-001","device_id":"my-local-device-uuid"}'
```

### جلب قوائم التشغيل

```bash
curl "https://test1.mybeesystem.net/api/v1/screen/player/playlists" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|YOUR_TOKEN"
```

### جلب مواد قائمة معيّنة

```bash
curl "https://test1.mybeesystem.net/api/v1/screen/player/playlists/5/promos" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|YOUR_TOKEN"
```

---

## توصيات للمطور (Mobile / TV)

1. بعد أول إصدار ناجح للتوكن، خزّن `device_code` و `token` بشكل آمن؛ و`device_id` المحلي إن استخدمته.
2. عند `401` على المسارات المحمية: أعد طلب `POST /player/auth/token` باستخدام `device_code` (المصادقة تعتمد عليه فقط).
3. استخدم `media_url` و `thumbnail_url` لتشغيل/عرض الملفات — لا تبني المسار يدوياً إن أمكن.
4. طبّق منطق الجدولة (`days_settings`) على الجهاز لاختيار القائمة النشطة حسب الوقت/اليوم.
5. لا تستخدم توكن Player على مسارات الإدارة (`/api/v1/screen/promos` POST/DELETE...) — ستحصل على `403`.

---

## ملخص المسارات

| Method | Path                     | Auth | الوصف               |
| ------ | ------------------------ | ---- | ------------------- |
| POST   | `/auth/token`            | لا   | إصدار توكن          |
| POST   | `/auth/revoke`           | نعم  | إلغاء التوكن الحالي |
| GET    | `/me`                    | نعم  | بيانات الجهاز       |
| GET    | `/playlists`             | نعم  | قوائم الجهاز        |
| GET    | `/playlists/{id}`        | نعم  | تفاصيل قائمة        |
| GET    | `/playlists/{id}/promos` | نعم  | مواد القائمة        |
| GET    | `/promos`                | نعم  | كل مواد الجهاز      |
| GET    | `/promos/{id}`           | نعم  | مادة واحدة          |

**المسار الكامل:** `https://{host}/api/v1/screen/player` + المسار أعلاه.

---

_آخر تحديث: يونيو 2026 — My Bee Screen Module_

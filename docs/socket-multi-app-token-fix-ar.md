# إصلاح تعارض Socket — النادل + الكاشير + المطبخ (Live-safe)

**التاريخ:** 2026-06-12  
**الحالة:** إصلاح Backend — **لا يغيّر** `/socket.io/` ولا `/ws` للشاشات

---

## 1. التشخيص (إجابات فريق الباك)

| السؤال | الجawab |
|--------|---------|
| هل socket-server يقتصر على اتصال واحد لكل token؟ | **لا** — لا يوجد `activeSocketsByToken` في `socket-server/index.js` |
| هل company-login يلغي التوkenات السابقة؟ | **الأرجح نعم** — `$user->tokens()->delete()` بدون اسم يلغي **كل** توkenات Sanctum |
| هل verify-token يختلف عن REST؟ | **لا** — نفس `SanctumBearerValidator`؛ Socket يستدعي `/api/verify-token` |
| سبب INVALID_TOKEN المتبادل؟ | تطبيق ثانٍ يسجّل `company-login` → توken جديد → توken التطبيق الأول **محذوف من DB** |

**REST يعمل** لأن `auth-central` يخزّن `Cache::has($token)` 24 ساعة — Socket **لا** يستخدم هذا الـ cache.

---

## 2. الإصلاح (Laravel)

### 2.1 خدمة جديدة: `App\Services\CompanyTokenService`

تصدر توken **لكل تطبيق** (`client_type`) بدون حذف توkenات التطبيقات الأخرى:

| client_type | اسم التوken في DB |
|-------------|-------------------|
| `waiter` | `company-api:waiter` |
| `cashier` | `company-api:cashier` |
| `kitchen` | `company-api:kitchen` |
| `pos` | `company-api:pos` |

### 2.2 تعديل `company-login` على السيرفر

**ابحث عن الملف:**

```bash
grep -rn "company-login\|tokens()->delete\|createToken" \
  /var/www/mybeeCompany/app \
  /var/www/mybeeCompany/Modules \
  --include="*.php"
```

**استبدل (مثال):**

```php
// ❌ قبل — يلغي النادل عند دخول الكاشier
$user->tokens()->delete();
$token = $user->createToken('company-api');

// ✅ بعد
use App\Services\CompanyTokenService;

$token = app(CompanyTokenService::class)->issue(
    $user,
    $request->input('client_type'),  // waiter | cashier | kitchen | pos
    $request->input('device_id'),    // اختياري
);
```

**Response** (بدون تغيير للتطبيقات):

```json
{
  "token": "462|...",
  "tenant_id": "test1"
}
```

---

## 3. Flutter — `client_type` (موصى به)

### company-login

```json
{
  "email": "...",
  "password": "...",
  "client_type": "waiter"
}
```

| التطبيق | client_type |
|---------|-------------|
| MyBee Waiter | `waiter` |
| MyBee Cashier | `cashier` |
| MyBee Kitchen | `kitchen` |
| MyBee POS | `pos` |

### Socket.IO auth (نفس التطبيق)

```dart
.setAuth({
  'token': companyToken,
  'tenant_id': tenantId,
  'client_type': 'waiter',  // ← جديد
  'employee_id': employeeId,
  'establishment_id': establishmentId,
})
```

> **بدون `client_type`:** كلا التطبيقين يستخدمان `company-api:default` — **ما زال** تعارض محتمل.

---

## 4. تحسينات socket-server (آمنة للـ Live)

| التحسين | الغرض |
|---------|--------|
| **Cache تحقق التوken** (120 ثانية) | تقليل ضغط verify + استقرار reconnect |
| **Rate limit بـ client_type** | النادل والكاشier لا يشاركان نفس عداد rate limit |
| **لا حد اتصال واحد** | عدة sockets لنفس التوken مسموحة |

---

## 5. Deploy (بعد git pull)

```bash
cd /var/www/mybeeCompany
git pull

php artisan config:clear
php artisan cache:clear

# عدّل company-login يدوياً (§2.2) إن لم يكن في git

cd socket-server
npm install
pm2 restart mybee-socket --update-env
```

**Apache:** لا تغيير.

---

## 6. اختبار القبول

```bash
# 1) company-login للنادل (client_type=waiter) → TOKEN_W
# 2) company-login للكاشier (client_type=cashier) → TOKEN_C
# 3) verify كلاهما
curl -i -H "Authorization: Bearer TOKEN_W" http://mybeesystem.net/api/verify-token
curl -i -H "Authorization: Bearer TOKEN_C" http://mybeesystem.net/api/verify-token
# كلاهما 200

# 4) Socket.IO polling
curl "http://test1.mybeesystem.net/socket.io/?EIO=4&transport=polling"
```

- [ ] Waiter + Cashier Socket **معاً** بدون INVALID_TOKEN
- [ ] `/ws` للشاشات لا يزال 101
- [ ] `/socket.io/` للمطبخ لا يزال يعمل

---

## 7. ما **لم** يتغيّر (Live)

- مسارات `/socket.io/` و `/ws`
- أحداث المطبخ / الويتر / POS
- Screen pairing / playlist WS
- REST APIs

---

**الملفات:** `app/Services/CompanyTokenService.php`, `socket-server/index.js`

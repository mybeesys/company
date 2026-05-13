# تقرير: نظام الإشعارات الفورية (Socket.io) — MyBee

> مرجع تقني للفريق — مبني على `mybee-socket-server.txt` وربط Laravel الحالي (وحدة الحجوزات).

## الهدف

خادم **mybee-socket-server** (Node.js + Socket.io) يعمل كوسيط **للدفع الفوري** من Laravel إلى الواجهة (React): Laravel يرسل حدثاً عبر **HTTP POST**، والخادم يوزعه على العملاء المتصلين حسب **المستأجر (tenant)**.

## المكوّنات

| الطبقة | التقنية |
|--------|---------|
| الخلفية | Laravel (مع tenancy) |
| محرك الوقت الفعلي | Node.js + Socket.io على المنفذ **3001** |
| الواجهة | React (اتصال مباشر بالسوكت) |
| التشغيل على السيرفر | PM2 |

## إعداد الخادم (Node)

- **المسار على السيرفر:** `/var/www/mybee-socket-server`
- **الملف الرئيسي:** `index.js`
- **المنفذ:** `3001`
- **اعتماديات مذكورة:** express, socket.io, http, body-parser

### أوامر PM2

| الإجراء | الأمر |
|---------|--------|
| التشغيل | `pm2 start index.js --name "mybee-socket"` |
| السجلات | `pm2 logs mybee-socket` |
| الحالة | `pm2 status` |
| إعادة التشغيل | `pm2 restart mybee-socket` |

## الشبكة والأمان (AWS)

- فتح **TCP 3001** في Security Group (قواعد دخول Inbound).
- في الوثيقة الأصلية: المصدر `0.0.0.0/0` للسماح لعملاء React الخارجيين بالاتصال.
- مثال عنوان للواجهة: `http://52.203.236.150:3001`
- Laravel على نفس الجهاز غالباً يرسل إلى `http://127.0.0.1:3001/broadcast`

## Laravel — المرسل

- **الطريقة:** `POST` إلى `http://<عنوان-خادم-السوكت>:3001/broadcast`
- **شكل الحمولة (JSON):**

```json
{
  "tenant_id": "معرف_المستأجر",
  "event": "TableUpdated",
  "data": {
    "table_id": 1,
    "table_code": "T10",
    "transaction_ref_no": "INV-001"
  }
}
```

### ربط موجود في المشروع

في `Modules/Reservation/Http/Controllers/Api/OrderController.php` الدالة `broadcastTableUpdate` ترسل إلى `http://127.0.0.1:3001/broadcast` مع:

- `tenant_id` من `tenancy()->tenant->id`
- `event` = `TableUpdated`
- `data`: `table_id`, `table_code`, `transaction_ref_no`

أي ميزة جديدة تحتاج تحديثاً فورياً للطاولات يمكنها إعادة استخدام نفس الـ endpoint ونفس شكل الـ JSON، أو توسيع `data` بالتنسيق مع فريق Node/React.

## React — المستقبل

- الاتصال بعنوان خادم Socket.io العام (حسب البيئة).
- **الانضمام لغرفة (room)** حسب **tenant** (من الـ hostname أو خاصية في التطبيق)، كما في `TreeTableComponent.js` في الوثيقة الأصلية.
- عند استقبال الحدث: مثلاً استدعاء `refreshTree()` وعرض تنبيه (SweetAlert2) مع دعم AR/EN.
- في أدوات المطوّر (F12): التحقق من اتصال ناجح؛ رسالة مرجعية: **Connected to Socket Server**.

## التشخيص السريع

1. **هل Node يعمل؟** تنفيذ `pm2 status` على السيرفر.
2. **هل Laravel يُرسل؟** مراجعة `storage/logs/laravel.log` (أخطاء اتصال أو timeout).
3. **هل المتصفح متصل؟** تبويب Console في أدوات المطوّر.
4. **اختبار يدوي (من السيرفر):**

```bash
curl -X POST http://127.0.0.1:3001/broadcast -H "Content-Type: application/json" -d "{\"tenant_id\": \"test1\", \"event\": \"TableUpdated\", \"data\": {\"table_code\":\"Test\"}}"
```

## توصيات للفريق

- **إعدادات:** تفضيل متغير بيئة مثل `SOCKET_BROADCAST_URL` بدل تثبيت `127.0.0.1` في الكود، مع قيم مختلفة للتطوير والإنتاج عند الحاجة.
- **الاتساق:** اسم الحدث `TableUpdated` وحقول `data` يجب أن يتفق عليها Laravel و Node و React.
- **الأمان:** فتح المنفذ للعالم يزيد سهولة الربط ومعه مسؤولية تأمين واجهة البث (مثلاً مفتاح سري لـ `/broadcast` إن أضيف لاحقاً في خادم Node).

## المراجع داخل المستودع

- الوثيقة الأصلية القصيرة: `mybee-socket-server.txt` (جذر المشروع أو المجلد الأب حسب مسار النسخة لديكم).
- مثال إرسال من Laravel: `Modules/Reservation/Http/Controllers/Api/OrderController.php` — `broadcastTableUpdate`.

---

*آخر تحديث للملف: 2026-05-13*

# My Bee Cashier (Flutter) — إضافة واختيار العميل

> **الغرض:** ملف واحد لمطوّر Flutter **الكاشير**. مرّره لـ Cursor ونفّذ كل بند.  
> **التاريخ:** 2026-10-08  
> **المشكلة:** بعد إضافة عميل من الكاشير أحياناً لا يظهر، وأحياناً يظهر مكرراً، وأحياناً لا يُربَط بالفاتورة. الداشبورد سليم.  
> **السيرفر:** قائمة/إنشاء العميل صارا آمنين ومتسقين. طبّق هذا الملف حتى لا يكرّر التطبيق العميل محلياً ولا يضيّع `id`.

---

## Cursor — ابدأ من هنا

```text
Read docs/flutter-cashier-add-customer-ar.md and implement:
1) List customers from GET /api/clients (parse data[])
2) Create with POST /api/contact-save using client_name (name also accepted)
3) After create, read id from data.id OR root id — never invent a local id
4) Upsert into the in-memory list by id (replace if exists, do not append a second row)
5) Select that id on the invoice as customer_id integer
6) Disable the save button until the request finishes (no double POST)
7) Walk-in / no customer: send customer_id null or omit it — never 0 or ""
Follow every checklist item.
```

### Checklist

- [ ] `GET /api/clients` عند فتح شاشة اختيار العميل / بعد الإضافة
- [ ] العناصر من `response['data']` (مصفوفة)
- [ ] العرض من `name` (يوجد أيضاً `client_name` بنفس القيمة)
- [ ] أظهر `id` كـ `int` فقط
- [ ] `POST /api/contact-save` بجسم فيه `client_name` على الأقل
- [ ] عطّل زر الحفظ أثناء الطلب
- [ ] بعد النجاح: `id = body['data']?['id'] ?? body['id']`
- [ ] إن `id` فارغ: أعد `GET /api/clients` وابحث بالاسم/الجوال بدل إضافة صف مؤقت
- [ ] حدّث القائمة بـ upsert على `id` — **ممنوع** append أعمى
- [ ] اختر العميل فوراً: `selectedCustomerId = id`
- [ ] عند حفظ الفاتورة: `"customer_id": 12` رقم صحيح، ليس كائن العميل كاملاً
- [ ] بدون عميل: `null` أو احذف الحقل — ليس `0` ولا `""`
- [ ] لا تفلتَر القائمة على `point_of_sale_client == 1` فقط ثم تخفي العملاء الجدد؛ السيرفر يضع `point_of_sale_client = 1` عند الإنشاء من الكاشير
- [ ] لا تستخدم `GET /api/clients` القديم مع كسر إن فشل عنصر واحد — السيرفر لم يعد ينهار بسبب العنوان

---

## 1) القائمة

```http
GET /api/clients
GET /api/clients?q=أحمد
```

```json
{
  "data": [
    {
      "id": 12,
      "name": "أحمد",
      "client_name": "أحمد",
      "business_type": "customer",
      "mobile_number": "0500000000",
      "phone_number": null,
      "point_of_sale_client": 1,
      "email": null,
      "tax_number": null,
      "status": "active",
      "street_name": "",
      "city": "",
      "state": "",
      "postal_code": "",
      "building_number": "",
      "country": ""
    }
  ]
}
```

- الافتراضي: العملاء **النشطون** فقط.
- `q` / `search`: اختياري للبحث بالاسم أو الجوال.
- `country` نص أو `""` — لن يكون `" - "` ولن يكسر القائمة.

---

## 2) الإضافة

```http
POST /api/contact-save
Content-Type: application/json
```

```json
{
  "client_name": "أحمد",
  "mobile_number": "0500000000",
  "phone_number": null,
  "email": null,
  "tax_number": null
}
```

- `client_name` مطلوب. `name` مقبول كبديل.
- لا حاجة لـ `business_type` (الافتراضي `customer`).
- لا حاجة لـ `point_of_sale_client` (السيرفر يضبطه 1).

نجاح إنشاء جديد: **201**. إن وُجد نفس الجوال/الهاتف مسبقاً: **200** ونفس العميل (بدون تكرار في قاعدة البيانات).

```json
{
  "id": 12,
  "name": "أحمد",
  "client_name": "أحمد",
  "business_type": "customer",
  "mobile_number": "0500000000",
  "point_of_sale_client": 1,
  "status": "active",
  "data": { "id": 12, "name": "أحمد" },
  "created": true
}
```

اقرأ المعرف هكذا:

```dart
final id = (body['data']?['id'] ?? body['id']) as num?;
```

`created: false` يعني أُعيد عميل موجود — تعامل معه كنجاح واختره، لا تعرض خطأ ولا تضف صفاً ثانياً.

---

## 3) الربط بالفاتورة

```http
POST /api/stor-sales-invoice
```

```json
{
  "customer_id": 12,
  "establishment_id": 1
}
```

مقبول أيضاً: `contact_id` أو `client_id`. **غير مقبول:** كائن `{"id":12,"name":"..."}` كقيمة الحقل إن استطعت تجنّبه؛ السيرفر يقرأ `id` من الكائن إن وصل بالخطأ، لكن أرسل رقماً.

---

## 4) لماذا كان يظهر مكرر / يختفي؟

| سبب | إصلاح السيرفر | إصلاح الكاشير |
|--|--|--|
| إنشاء بدون `business_type` → لا يظهر في `GET /api/clients` | الافتراضي `customer` | أرسل `client_name` فقط يكفي |
| `point_of_sale_client` صفر والكاشير يفلتر POS | يُضبط 1 | لا تخفِ إن الحقل 0 لعملاء آخرين من الداشبورد إن أردت إظهار الكل النشط |
| ضغط حفظ مرتين | نفس الجوال يُعاد دون صف جديد | عطّل الزر |
| القائمة المحلية + استجابة الإنشاء + إعادة الجلب | شكل `id` موحّد int | upsert حسب `id` |
| `customer_id: ""` على الفاتورة | يُحفظ `null` | أرسل `null` أو احذف الحقل |
| قائمة `GET /api/clients` تنهار على عنوان بلا دولة | `country` آمن | أعد المحاولة عند 500 القديمة ثم اعتمد القائمة الجديدة |

---

## 5) ما لا تفعله

- لا تنشئ `id` محلي سالب/مؤقت ثم تبقيه بعد فشل الحفظ.
- لا تدمج قائمتين (كاش + سيرفر) بدون مفتاح `id`.
- لا ترسل `business_type: "supplier"` من كاشير المبيعات.
- لا تعتمد على ترتيب الحقول في JSON؛ اعتمد `id`.

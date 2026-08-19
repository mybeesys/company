# My Bee Cashier (Flutter) — طرق الدفع حسب الفرع + الاستهلاك الداخلي

> **الغرض:** دليل تنفيذ للمطوّر Flutter (الكاشير) ولـ **Cursor**.  
> **التاريخ:** 2026-08-12  
> **النطاق:** تغييرات Backend تنعكس على تطبيق الكاشير فقط.  
> **لا تغيّر** واجهات المطبخ/الشاشات إلا إذا كانت تستهلك نفس `payment-methods` أو `stor-sales-invoice`.

---

## Cursor — ابدأ من هنا

انسخ هذا البرومبت في Cursor داخل مشروع Flutter:

```text
Read docs/flutter-cashier-branch-payments-internal-consumption-ar.md and implement ALL Flutter changes for:
1) Branch-scoped payment methods API
2) Internal consumption / staff meals invoice purpose
Follow every checklist item. Do not keep using global payment method IDs.
Keep existing sell invoice flow for purpose=standard.
```

### Checklist للمطور / Cursor

- [ ] استدعاء `GET /api/payment-methods?establishment_id={branchId}` عند فتح الشيفت أو تغيير الفرع
- [ ] عرض القائمة من الرد فقط (لا كاش ثابت عالمي)
- [ ] عند البيع: `payments[].method_id` = **`id` من رد الفرع** (ليس id جدول payment_methods القديم)
- [ ] دعم `-1` للنقد فقط كـ fallback (السيرفر يحوّله لطريقة `cash` على نفس الفرع إن وُجدت)
- [ ] شاشة/زر استهلاك داخلي (وجبات عاملين…) يرسل `purpose: "internal_consumption"`
- [ ] في الاستهلاك الداخلي: **لا ترسل payments** (أو تجاهلها)، ولا تحتسبها كمبيعات
- [ ] معالجة أخطاء 422 الخاصة بالإعدادات الناقصة
- [ ] إعادة جلب طرق الدفع عند تبديل `establishment_id`

---

## 1) طرق الدفع حسب الفرع (مهم جداً)

### قبل

`GET /api/payment-methods` كان يعيد **كل** طرق الدفع العامة من الجدول العالمي.

### بعد (الحالي)

المصدر: جدول الفرع `est_establishment_payment_accounts`.  
كل فرع له قائمة وطرق وحسابات محاسبية خاصة به (تُضبط من ويب: تعديل الفرع → تاب **طرق الدفع عند الكاشير**).

### API

```http
GET /api/payment-methods?establishment_id=1
```

| Query | مطلوب | وصف |
|-------|--------|------|
| `establishment_id` | ✅ | معرّف الفرع الحالي للكاشير |

#### نجاح `200`

```json
{
  "data": [
    {
      "id": 12,
      "name_ar": "نقداً",
      "name_en": "cash",
      "description_en": null,
      "description_ar": null,
      "active": 1
    },
    {
      "id": 13,
      "name_ar": "بطاقة",
      "name_en": "card",
      "description_en": null,
      "description_ar": null,
      "active": 1
    }
  ]
}
```

> **`id` هنا = معرّف صف الفرع** (`est_establishment_payment_accounts.id`).  
> هذا هو الرقم الذي يجب إرساله في `payments[].method_id` عند إنشاء فاتورة البيع.

#### فشل بدون فرع `422`

```json
{
  "message": "معرّف الفرع مطلوب لجلب طرق الدفع."
}
```

#### قائمة فارغة `200` + `data: []`

يعني الفرع بلا طرق مربوطة بحساب. أظهر رسالة للمستخدم: يجب ضبط التاب من لوحة التحكم قبل البيع.

### أين تُستخدم في البيع

`POST /api/stor-sales-invoice` — مصفوفة الدفع كما هي تقريباً، مع تغيير معنى `method_id`:

```json
{
  "establishment_id": 1,
  "user_id": 5,
  "shift_id": "....",
  "status": "final",
  "items": [],
  "payments": [
    {
      "method_id": 12,
      "amount": 50
    }
  ]
}
```

| حقل | مطلوب | ملاحظة |
|-----|--------|--------|
| `payments[].method_id` | ✅ للبيع العادي | **id من API الفرع** |
| `payments[].method_id = -1` | اختياري | يُفسَّر كـ نقد الفرع (`payment_method_key = cash`) إن وُجد |
| `payments[].amount` | ✅ | المبلغ |

#### أخطاء شائعة `422`

| رسالة / معنى | السبب | ماذا يفعل Flutter |
|--------------|--------|-------------------|
| طريقة الدفع غير مربوطة بحساب لهذا الفرع | `method_id` لا يخص هذا الفرع أو بلا `account_id` | أعد جلب الطرق لهذا الفرع؛ لا تستخدم ids قديمة مخزّنة |
| طريقة غير مسموحة | id لا يتبع الفرع | نفس المعالجة |

### قواعد كاش محلي في التطبيق

1. **لا** تخزّن قائمة طرق دفع عامة لكل الشركة مرة واحدة وتستخدمها لكل الفروع.
2. مفتاح الكاش المقترح: `payment_methods_branch_{establishmentId}`.
3. عند تغيير الفرع أو بعد تسجيل الدخول: invalidate + refetch.
4. اعرض `name_ar` / `name_en` حسب لغة التطبيق من رد API (الأسماء أصبحت قابلة للتخصيص لكل فرع).

---

## 2) الاستهلاك الداخلي (مصروف — ليس مبيعات)

> **محدّث 2026-08-19:** التنفيذ الكامل للكاشير (أنواع المصروف، تكلفة المخزون، منع الخصم، القيد) في  
> [`docs/flutter-cashier-internal-consumption-ar.md`](flutter-cashier-internal-consumption-ar.md).  
> لا تعتمد على الأمثلة القديمة في هذا القسم (`purpose` فقط بدون نوع، أو أسعار بيع).

حالات مثل: وجبات العاملين، ضيافة داخلية، استهلاك من المخزون بدون بيع للزبون.

### السلوك في Backend

| الجانب | البيع العادي (`standard`) | الاستهلاك الداخلي |
|--------|---------------------------|-------------------|
| `type` | `sell` | `sell` (نفس النوع) |
| `purpose` | `standard` أو فارغ | `internal_consumption` |
| المخزون | ينقص | ينقص بالتكلفة |
| إيراد مبيعات / ضريبة / ذمم | نعم | **لا** |
| القيد | إيراد + ضريبة + … (+ COGS إن وُجد) | مدين **مصروف الفرع** + دائن **المخزون** بالتكلفة |
| `payments` | مطلوبة عادة | **غير مستخدمة** — لا ترسلها |
| الداشبورد (مبيعات) | تدخل | **مستبعدة** من مبيعات؛ تظهر كمصروف/استهلاك داخلي |

### إعداد الفرع (ويب — ليس Flutter)

`/establishment/{id}/edit` → تاب **مصروفات الاستهلاك الداخلي للكاشير**  
يجب اختيار حساب مصروف تفصيلي للفرع (أو فرع أب). بدون هذا الإعداد الـ API يرجع `422`.

### API — نفس endpoint البيع

```http
POST /api/stor-sales-invoice
```

#### مثال جسم الطلب

```json
{
  "id": "local-uuid-or-int",
  "purpose": "internal_consumption",
  "establishment_id": 1,
  "device_id": 3,
  "user_id": 5,
  "customer_id": null,
  "created_at": "2026-08-12T14:30:00",
  "invoice_number": "IC-001",
  "status": "final",
  "note": "وجبات عاملين",
  "discount_value": 0,
  "total_before_discount": 0,
  "total_after_discount": 0,
  "total_tax": 0,
  "total_paid": 0,
  "shift_id": "optional-shift",
  "items": [
    {
      "product_id": 44,
      "quantity": 2,
      "price": 0,
      "price_after_discount": 0,
      "discount_amount": 0
    }
  ]
}
```

> الأسعار يمكن إرسالها 0 أو بسعر القائمة — **القيد المحاسبي يعتمد التكلفة** وليس سعر البيع.  
> المهم هو `purpose` + الأصناف + الفرع + `status` معتمد (`final` / `approved`).

#### قيم `purpose` المقبولة

| قيمة | المعنى |
|------|--------|
| `internal_consumption` | ✅ المفضّلة |
| `staff_meals` | ✅ alias مقبول (يُطبَّع إلى `internal_consumption`) |
| `standard` / فارغ / غير معروف | بيع عادي |

#### لا ترسل في الاستهلاك الداخلي

- `payments` (السيرفر يتجاوز مسار الدفع)
- منطق تحصيل نقد/بطاقة/تطبيقات
- احتساب الفاتورة ضمن تقارير/شاشات **المبيعات** في التطبيق

#### أخطاء `422` مهمة

| `code` / رسالة | السبب | UX مقترح |
|----------------|--------|----------|
| `internal_consumption_expense_account_required` | لم يُضبط حساب المصروف للفرع | «اضبط مصروف الاستهلاك الداخلي من إعدادات الفرع» |
| `internal_consumption_failed` + رسالة تكلفة/مخزون | تعذّر التكلفة أو حساب المخزون | أظهر `message` كما هي |
| كمية غير كافية | نفس فحوصات المخزون للبيع | نفس رسائل نقص المخزون الحالية |

#### نجاح `200`

```json
{ "message": "Added successfully" }
```

---

## 3) تمييز الواجهة في Flutter (مقترح تنفيذ)

### أ) البيع العادي

1. جلب طرق الدفع للفرع.
2. اختيار طريقة/طرق + مبالغ.
3. `POST stor-sales-invoice` **بدون** `purpose` أو مع `"purpose":"standard"`.
4. أرسل `payments`.

### ب) استهلاك داخلي

1. زر/تبويب منفصل: «استهلاك داخلي» / «وجبات عاملين».
2. اختيار أصناف + كميات (ونفس فرع الشيفت).
3. طلب واحد:

```json
{ "purpose": "internal_consumption", "...": "باقي الحقول", "items": [] }
```

4. لا شاشة دفع. لا تخلط الفاتورة مع قائمة فواتير المبيعات إن كان عندك فلتر محلي — فلتر على `purpose` إن أرجعها السيرفر لاحقاً، أو لا تعرضها كمبيعات.

### نموذج بيانات مقترح

```dart
enum InvoicePurpose { standard, internalConsumption }

String? toApi(InvoicePurpose p) => switch (p) {
  InvoicePurpose.standard => 'standard', // أو null
  InvoicePurpose.internalConsumption => 'internal_consumption',
};
```

```dart
class BranchPaymentMethod {
  final int id; // ← method_id في payments
  final String nameAr;
  final String nameEn;
}
```

---

## 4) ملخص سريع للمراجعة

| الموضوع | المطلوب من Flutter |
|---------|-------------------|
| طرق الدفع | Query `establishment_id` إلزامي |
| `method_id` | id صف الفرع من API الجديد |
| كاش قديم عالمي | احذفه / لا تعتمد عليه |
| استهلاك داخلي | `purpose=internal_consumption` على نفس sell API |
| دفعات مع استهلاك داخلي | لا |
| إعداد المصروف | ويب لكل فرع — وإلا 422 |

---

## 5) اختبار يدوي سريع

1. فرع A: أضف نقد + بطاقة من ويب. فرع B: نقد فقط.
2. من التطبيق على A: يجب أن ترى طريقتين. على B: واحدة.
3. بيع عادي على A بـ `method_id` من القائمة → نجاح.
4. نفس `method_id` الخاص بـ A على فاتورة فرع B → يجب 422.
5. ضبط حساب مصروف الاستهلاك لفرع A.
6. أرسل فاتورة `purpose=internal_consumption` بأصناف لها تكلفة ومخزون → نجاح، المخزون ينقص، لا تظهر ضمن مبيعات الداشبورد.

---

## 6) ملاحظات Backend (للمرجعية فقط)

- Migrations مستأجر:  
  - `2026_08_11_150000_create_est_establishment_payment_accounts_table`  
  - `2026_08_11_151000_add_names_to_est_establishment_payment_accounts_table`  
  - `2026_08_12_020000_add_internal_consumption_expense_account_id_to_est_establishments_table`  
  - `2026_08_12_020100_add_purpose_to_transactions_table`
- مسار البيع: `SellApiController` + `PosSalesInvoiceMapper` (`purpose`) + `EstablishmentPaymentAccountResolver`.
- القيد: `AccountingUtil::postInternalConsumptionJournal`.

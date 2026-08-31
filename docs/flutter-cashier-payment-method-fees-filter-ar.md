# My Bee Cashier (Flutter) — فلتر رسوم طرق الدفع (التعديلات الجديدة)

> **الغرض:** ملف واحد لمطوّر Flutter **الكاشير** بعد إضافة حقول الحساب والضريبة لرسوم طرق الدفع.  
> **التاريخ:** 2026-08-31  
> **مرجع API الكامل:** `docs/flutter-cashier-payment-method-fees-ar.md`  
> **مرجع رسوم الخدمة (للمقارنة):** `docs/flutter-cashier-service-fees-ar.md`

---

## Cursor — ابدأ من هنا

```text
Read docs/flutter-cashier-payment-method-fees-filter-ar.md and implement:
1) Keep GET /api/payment-methods?establishment_id={branchId}
2) Parse new additive fields on each fee: calculation_method, calculated_on, taxable
3) Apply fees ONLY when user selects a payment method — NO auto-apply rules
4) Do NOT reuse service-fees auto_apply_type / applied_service_fee_ids for payment method fees
5) Ignore missing new fields (backward compatible with old cached API responses)
Follow every checklist item.
```

### Checklist

- [ ] الاستدعاء ما زال: `GET /api/payment-methods?establishment_id={branchId}`
- [ ] بدون `establishment_id` → `422` (لا تغيّر)
- [ ] `fees[]` قد تكون فارغة — هذا طبيعي (مثلاً النقد)
- [ ] طبّق **فقط** رسوم الطريقة المختارة (`payments[].method_id`)
- [ ] **لا** تستخدم `auto_apply_type` أو `applied_service_fee_ids` لرسوم طرق الدفع
- [ ] اقرأ `calculation_method` / `calculated_on` / `taxable` من كل رسم
- [ ] إن غابت الحقول الجديدة: اعتبر `calculation_method = "0"` و `taxable = false`
- [ ] استهلاك داخلي: لا طرق دفع، لا رسوم
- [ ] أعد جلب الطرق عند تبديل الفرع

---

## 1) الفرق الجوهري عن رسوم الخدمة

| | رسوم الخدمة | رسوم طريقة الدفع |
|---|-------------|------------------|
| API | `GET /api/service-fees` | مضمّنة في `GET /api/payment-methods` → `fees[]` |
| التطبيق | اختيار صريح `applied_service_fee_ids[]` + قواعد auto-apply اختيارية | **عند اختيار طريقة الدفع فقط** |
| قواعد auto-apply | نعم (نوع طعام، ضيوف، وقت، …) | **لا — ممنوع** |
| إرسال في POST البيع | `applied_service_fee_ids` | **لا ترسل** — احسب محلياً |

> **مهم:** «تلقائياً» هنا تعني *بمجرد اختيار طريقة الدفع* — وليس قواعد التطبيق التلقائي لرسوم الخدمة.

---

## 2) فلتر الفرع (إلزامي)

```http
GET /api/payment-methods?establishment_id=3
```

| الحالة | النتيجة |
|--------|---------|
| `establishment_id` مفقود أو ≤ 0 | `422` |
| فرع بدون طرق | `200` + `data: []` |
| فرع له طرق | `200` + طرق ذلك الفرع فقط |

**ممنوع:** استخدام `id` طريقة دفع من فرع A أثناء العمل على فرع B.

---

## 3) فلتر الرسوم داخل كل طريقة

الـ API يُرجع **الرسوم النشطة فقط** (`is_active = true`).

| قاعدة | التطبيق |
|-------|---------|
| `fees` مفقود | اعتبر `[]` |
| `fees` فارغ | لا رسوم — لا تُظهر سطر رسوم |
| عدة رسوم | اجمعها كلها للطريقة المختارة |
| تغيير طريقة الدفع | أعد الحساب من **السعر الأساسي** (لا تراكم) |
| عدة طرق دفع في فاتورة واحدة | اجمع رسوم كل `method_id` المختار |

---

## 4) الحقول الجديدة (إضافية — لا تكسر القديم)

| حقل | نوع | افتراضي إن غاب | معنى |
|-----|-----|----------------|------|
| `calculation_method` | `"0"` \| `"1"` | `"0"` | `0` = قبل الضريبة — `1` = بعد الضريبة (شامل) |
| `calculated_on` | `"before_tax"` \| `"after_tax"` | `"before_tax"` | مرادف أوضح لـ `calculation_method` |
| `taxable` | bool | `false` | إن `true` أضف ضريبة على مبلغ الرسم نفسه |

### أساس النسبة المئوية

| `calculated_on` | `applies_to = item` | `applies_to = order` |
|-----------------|---------------------|----------------------|
| `before_tax` | نسبة من `lineNet` | نسبة من `orderNet` |
| `after_tax` | نسبة من `lineGross` | نسبة من `orderGross` |

### ضريبة الرسم (`taxable = true`)

- **على السطر:** `feeTax += lineFee × (lineTaxRate / 100)`
- **على الطلب:** `feeTax += orderFee × (productVat / orderNet)` إن `orderNet > 0`

---

## 5) ما لا تفعله

| ممنوع | السبب |
|--------|--------|
| فلترة الرسوم بـ `auto_apply_type` | غير موجودة لرسوم طرق الدفع |
| إرسال `fee.id` في `payments[].method_id` | `method_id` = `id` الطريقة |
| تطبيق رسوم خدمة كرسوم طريقة دفع | نظامان منفصلان |
| الإبقاء على سعر صنف معدّل بعد تغيير طريقة الدفع | تراكم خاطئ |

---

## 6) Backend (مرجعية)

| ملف | دور |
|-----|-----|
| `Modules/Establishment/.../payment-method-fee-row.blade.php` | إعدادات الويب |
| `Modules/General/Transformers/PaymentMethodFeeResource.php` | شكل API |
| `Modules/Establishment/Models/PaymentMethodFee.php` | الحقول والحساب |
| `2026_08_31_120000_add_calculation_fields_to_est_payment_method_fees_table.php` | migration |

# My Bee Cashier (Flutter) — رسوم الخدمة حسب الفرع

> **الغرض:** ملف واحد لمطوّر Flutter **الكاشير**. مرّره لـ Cursor.  
> **التاريخ:** 2026-08-19  
> **النطاق:** قائمة رسوم الخدمة للفرع + تطبيقها على فاتورة **البيع** وتخزين الرسم المختار.  
> **لا يغيّر مفهوم البيع:** بدون إرسال رسوم تبقى الفاتورة كما هي اليوم.  
> **ليس** رسوم طرق الدفع (`fees` داخل `payment-methods`). هذا كتالوج مستقل.

---

## Cursor — ابدأ من هنا

```text
Read docs/flutter-cashier-service-fees-ar.md and implement:
1) Fetch branch service fees: GET /api/service-fees?establishment_id={branchId}
2) Do NOT use legacy GET /api/serviceFees (old global product catalog)
3) When the cashier selects a fee, add it to the invoice using the same math as web
4) On store, send applied_service_fee_ids (and optional service_fees[]) so the backend stores which fee was applied
5) If no fee is selected, omit those fields — normal sale is unchanged
6) Internal consumption: no service fees
Follow every checklist item.
```

### Checklist

- [ ] `GET /api/service-fees?establishment_id={branchId}` عند فتح الشيفت / تبديل الفرع
- [ ] **لا** تستخدم `GET /api/serviceFees` (كتالوج المنتجات القديم)
- [ ] اعرض `name_ar` / `name_en` وأضف الرسم للسلة حسب `applies_to` و`is_percent` و`calculated_on`
- [ ] عند اختيار رسم: أضفه للمجموع (ومن ضريبته إن `taxable`)
- [ ] عند الحفظ: أرسل `applied_service_fee_ids: [id]` — هذا هو معرّف الرسم المخزَّن
- [ ] `payments` تغطي الإجمالي **بعد** رسم الخدمة
- [ ] بدون اختيار: لا ترسل `applied_service_fee_ids` أصلاً
- [ ] استهلاك داخلي: تجاهل رسوم الخدمة بالكامل
- [ ] لا تخلط مع `payment-methods[].fees`

---

## 1) ماذا يعني رسم الخدمة؟

رسم يُضاف على **فاتورة المبيعات** (نسبة أو مبلغ، على الطلب أو على كل صنف). مصدره إعدادات الفرع في الويب (كتالوج الكاشير → رسوم الخدمة)، **نفس محرّك الويب** `InvoiceServiceFeeCalculator`.

| | بيع عادي بدون رسم | بيع مع رسم مختار |
|--|-------------------|------------------|
| `type` / `purpose` | `sell` / `standard` | كما هو — ما زالت فاتورة مبيعات |
| أصناف + ضريبة منتجات | نعم | نعم (بدون تغيير) |
| رسم الخدمة | 0 | يُحسب ويُخزَّن مع `id` الرسم |
| دفع | مبلغ المنتجات | مبلغ المنتجات **+ الرسم + ضريبة الرسم** |

الاستهلاك الداخلي: **ممنوع** رسم خدمة (مثل الويب).

---

## 2) قائمة الرسوم — API

```http
GET /api/service-fees?establishment_id=1
```

| Query | مطلوب | وصف |
|-------|--------|------|
| `establishment_id` | ✅ | فرع الشيفت |

بدون فرع → `422` + `code: establishment_id_required`.  
قائمة فارغة → `200` + `data: []`.

### نجاح `200`

```json
{
  "data": [
    {
      "id": 3,
      "name_ar": "خدمة طاولة",
      "name_en": "Table service",
      "amount": 10,
      "service_fee_type": "1",
      "is_percent": true,
      "application_type": "1",
      "applies_to": "order",
      "calculation_method": "0",
      "calculated_on": "before_tax",
      "taxable": false,
      "is_active": true,
      "auto_apply_type": "",
      "auto_apply": "always",
      "dining_type_ids": [],
      "guest_count": null,
      "cashier_payment_method_id": null,
      "from_date": null,
      "to_date": null,
      "sort_order": 0,
      "fee_type_label_ar": "نسبة مئوية",
      "fee_type_label_en": "Percentage",
      "application_label_ar": "إجمالي الطلب",
      "application_label_en": "Whole order",
      "calculation_method_label_ar": "الإجمالي قبل الضريبة",
      "calculation_method_label_en": "Subtotal before tax",
      "auto_apply_label_ar": "متاح دائماً (يختاره الكاشير)",
      "auto_apply_label_en": "Always available (cashier selects)"
    }
  ]
}
```

`id` من هذا الرد هو الذي يُرسل عند الحفظ ويُخزَّن على الفاتورة.

### حقول الحساب (نفس الويب)

| حقل | قيم | المعنى |
|-----|------|--------|
| `service_fee_type` / `is_percent` | `"0"` مبلغ / `"1"` نسبة | مبلغ ثابت أو ٪ |
| `application_type` / `applies_to` | `"0"` `item` / `"1"` `order` | كل صنف أو إجمالي الطلب |
| `calculation_method` / `calculated_on` | `"0"` `before_tax` / `"1"` `after_tax` | أساس النسبة |
| `taxable` | bool | إن true تُضاف ضريبة على **مبلغ الرسم** (ليس على المنتجات مرة أخرى) |
| `amount` | number | القيمة أو النسبة |

### التطبيق التلقائي في الواجهة فقط

الويب يضع صح تلقائياً حسب القواعد؛ الكاشير يختار ثم **يرسل ids**. السيرفر يطبّق ما أُرسل، ولا يضيف رسوماً من تلقاء نفسه إن حُذفت الحقول.

| `auto_apply` | متى تعلّم الرسم مختاراً في الواجهة |
|--------------|-------------------------------------|
| `always` | متاح؛ يمكن اختياره يدوياً (الويب يعلّمه إن لم يُضبط شرط) |
| `dining` | إذا `dining_type_id` الحالي ضمن `dining_type_ids` |
| `guest_count` | إذا عدد الضيوف ≥ `guest_count` |
| `payment_method` | إذا `payments[].method_id` = `cashier_payment_method_id` (نفس id من `payment-methods`) |
| `time_slot` | إذا وقت الفاتورة بين `from_date` و`to_date` |

بعد أن يختار المستخدم (أو تُطبَّق القاعدة في الواجهة) أرسل الـ ids. لا تعتمد على السيرفر ليخمّن الاختيار في مسار POS.

أعد الجلب عند تبديل الفرع. كاش مقترح: `service_fees_branch_{establishmentId}`.

---

## 3) طريقة الحساب (انسخ الويب)

ترتيب الفاتورة **لا يخلط** صافي المنتجات مع الرسم:

1. أسطر الأصناف → صافي بعد خصم السطر، بدون VAT الرسم.
2. خصم الفاتورة → صافي المنتجات بعد الخصم + VAT المنتجات.
3. رسم الخدمة على تلك النتيجة:
   - **٪ على الطلب قبل الضريبة** → من صافي المنتجات بعد خصم الفاتورة
   - **٪ على الطلب بعد الضريبة** → من إجمالي المنتجات بعد الخصم والضريبة
   - **مبلغ ثابت على الطلب** → `amount` مرة واحدة
   - **٪ على الصنف** → من صافي السطر (أو إجمالي السطر إن بعد الضريبة)
   - **مبلغ ثابت على الصنف** → `amount × الكمية`
4. إن `taxable`: VAT على مبلغ الرسم فقط (صنف: نسبة سطر الصنف؛ طلب: متوسط مرجّح لضريبة المنتجات).

```text
إجمالي التحصيل = إجمالي المنتجات بعد الضريبة + رسم الخدمة + ضريبة الرسم
```

لا تضف رسم الخدمة داخل سعر الصنف. اعرضه بنداً مستقلاً (مثل الويب).

---

## 4) الحفظ — نفس `stor-sales-invoice`

فاتورة البيع كما هي. أضف فقط تعريف الرسم المختار.

```http
POST /api/stor-sales-invoice
```

```json
{
  "purpose": "standard",
  "establishment_id": 1,
  "user_id": 5,
  "invoice_type": "cash",
  "status": "final",
  "created_at": "2026-08-19T14:30:00",
  "discount_value": 0,
  "total_before_discount": 100,
  "total_after_discount": 100,
  "total_tax": 15,
  "total_paid": 125,
  "service_fee_amount": 10,
  "service_fee_tax": 0,
  "applied_service_fee_ids": [3],
  "items": [],
  "payments": [
    { "method_id": 12, "amount": 125 }
  ]
}
```

| حقل | مطلوب عند اختيار رسم | ملاحظة |
|-----|----------------------|--------|
| `applied_service_fee_ids` | ✅ | مصفوفة `id` من API الفرع — **هذا ما يُخزَّن** |
| `service_fees` | بديل | `[{ "id": 3 }]` أو `[3]` — نفس المعنى |
| `service_fee_amount` | إن كان `total_paid` يشمل الرسم | حتى لا يُحسب الرسم مرتين (مثل الويب) |
| `service_fee_tax` | إن شمل `total_tax` ضريبة الرسم | يُطرح ثم يُعاد الحساب على السيرفر |
| `guest_count` / `dining_type_id` | اختياري | للواجهة؛ السيرفر يعتمد ids المرسلة |

السيرفر:

1. يحسب الرسم من جديد من الكتالوج + الأسطر (لا يثق بمبلغ العميل إلا للطرح من الإجمالي).
2. يخزّن `service_fee_amount` و`service_fee_tax` و`service_fees_payload` (فيه `id` + الاسم + المبالغ).
3. `final_total` = منتجات + رسم + ضريبة الرسم.
4. `tax_amount` = VAT المنتجات + ضريبة الرسم.
5. صافي المنتجات (`totalAfterDiscount`) **لا** يتغيّر.

### بدون رسم (البيع الحالي)

لا ترسل `applied_service_fee_ids` ولا `service_fees`. لا يُضاف شيء. لا يتغيّر مفهوم البيع.

إرسال `applied_service_fee_ids: []` يعني: المستخدم أزال كل الرسوم عمداً.

### استهلاك داخلي

لا رسوم خدمة. لا ترسل ids.

---

## 5) القيد

ما زال قيد **مبيعات**. مبلغ الرسم يدخل ضمن `final_total` (مدين التحصيل / دائن المبيعات والصافي مشتق من الإجمالي − الضريبة كما في مسار الكاشير الحالي). لا مسار محاسبي منفصل ولا إيراد إضافي من نوع آخر.

---

## 6) فرق مهم عن APIs أخرى

| API | ماذا يفعل |
|-----|-----------|
| `GET /api/service-fees?establishment_id=` | ✅ رسوم خدمة **الفرع** للكاشير |
| `GET /api/serviceFees` | ❌ كتالوج منتجات قديم — لا تستخدمه للكاشير |
| `GET /api/payment-methods` → `fees[]` | رسوم **طريقة الدفع** عند اختيار نقد/بطاقة — موضوع آخر |

---

## 7) اختبار يدوي

1. ويب: أضف رسم فرع (مثلاً 10٪ على الطلب قبل الضريبة) وفعّله لهذا الفرع.
2. التطبيق: القائمة تُظهر الاسم والتفاصيل.
3. بيع بدون اختيار الرسم → نفس الإجمالي السابق.
4. اختيار الرسم → يزيد الإجمالي؛ الحفظ ينجح؛ في الويب تظهر الفاتورة مع اسم الرسم والمبلغ.
5. استهلاك داخلي → لا تظهر/لا تُرسل رسوم الخدمة.
6. `payments[].amount` = الإجمالي بعد الرسم.

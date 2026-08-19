# My Bee Cashier (Flutter) — المصروف الداخلي (استهلاك داخلي)

> **الغرض:** ملف واحد لمطوّر Flutter **الكاشير**. مرّره لـ Cursor.  
> **التاريخ:** 2026-08-19  
> **النطاق:** أنواع المصروف + تكلفة المخزون + حفظ فاتورة المصروف (ليست مبيعات).  
> **مرجع سابق (طرق الدفع):** `docs/flutter-cashier-branch-payments-internal-consumption-ar.md` — قسم الاستهلاك هناك **قديم**؛ اعتمد هذا الملف.

---

## Cursor — ابدأ من هنا

```text
Read docs/flutter-cashier-internal-consumption-ar.md and implement:
1) Fetch internal expense types for the current branch
2) Preview inventory costs (same engine as web: average / FIFO / LIFO)
3) When an expense type is selected, the document is an EXPENSE invoice: no revenue, VAT, discount, coupon, payments, or payment-method fees
4) Store via POST /api/stor-sales-invoice with purpose=internal_consumption AND internal_consumption_type_id
5) Do not break the normal sales invoice flow
Follow every checklist item.
```

### Checklist

- [ ] `GET /api/internal-consumption-types?establishment_id={branchId}` عند فتح وضع المصروف / تبديل الفرع
- [ ] عرض `name_ar` / `name_en` حسب لغة التطبيق
- [ ] عند اختيار نوع: الفاتورة **مصروف فقط** — ليست فاتورة مبيعات
- [ ] أسعار الأسطر من `POST /api/invoice-inventory-costs` (لا سعر البيع)
- [ ] تعطيل الخصم والكوبون والضريبة وطرق الدفع ورسوم طرق الدفع
- [ ] الحفظ: `purpose: "internal_consumption"` + `internal_consumption_type_id` + أصناف + فرع — **بدون** `payments`
- [ ] `discount_value = 0` و `total_tax = 0` دائماً في هذا الوضع
- [ ] إعادة جلب الأنواع والتكلفة عند تبديل الفرع أو الكمية أو الوحدة
- [ ] معالجة `422` بالـ `code` المعروض أدناه
- [ ] البيع العادي (`purpose=standard` وبدون نوع مصروف) كما هو

---

## 1) ماذا يعني «مصروف داخلي»؟

نفس شاشة السلة تقريباً، لكن عند اختيار **نوع مصروف** (وجبات عاملين، ضيافة، هدر داخلي…):

| | فاتورة مبيعات | فاتورة مصروف داخلي |
|--|----------------|---------------------|
| `purpose` | `standard` | `internal_consumption` |
| إيراد مبيعات | نعم | **لا** |
| ضريبة / خصم / كوبون | نعم | **ممنوع** |
| طرق دفع + رسومها | نعم | **لا — لا شاشة دفع** |
| سعر السطر | سعر البيع | **تكلفة المخزون** (متوسط / FIFO / LIFO) |
| المخزون | ينقص | ينقص |
| القيد | إيراد + ضريبة + تحصيل + COGS | مدين حساب النوع + دائن المخزون بالتكلفة |

اختيار النوع = تحويل المستند إلى مصروف. لا تخلطه مع قائمة المبيعات في الداشبورد.

---

## 2) أنواع المصروف — API

```http
GET /api/internal-consumption-types?establishment_id=1
```

| Query | مطلوب | وصف |
|-------|--------|------|
| `establishment_id` | ✅ | فرع الشيفت الحالي |

الحقول القديمة (`id`, `name_ar`, `name_en`, `value_type`, `value`, `is_active`) **باقية**. أضفنا تفاصيل ولم نحذف شيئاً.

### نجاح `200`

```json
{
  "data": [
    {
      "id": 3,
      "name_ar": "وجبات عاملين",
      "name_en": "Staff meals",
      "value_type": "cost",
      "value": null,
      "is_active": true,
      "type_key": "staff_meals",
      "sort_order": 0,
      "value_type_label_ar": "تكلفة المخزون",
      "value_type_label_en": "Inventory cost",
      "calculation_hint_ar": "أسعار الأصناف ومبلغ القيد من تكلفة المخزون الحالية (متوسط / FIFO / LIFO). لا إيراد مبيعات ولا ضريبة ولا خصم.",
      "calculation_hint_en": "Line prices and the journal charge use current inventory cost (average / FIFO / LIFO). No sales revenue, VAT, or discount.",
      "charge_uses": "cost",
      "prices_use": "inventory_cost",
      "allows_discount": false,
      "allows_tax": false,
      "allows_payments": false,
      "allows_payment_method_fees": false
    }
  ]
}
```

### `value_type` — كيف تُحسب القيمة

هذا يضبط **مبلغ التحميل في القيد** بعد حفظ الفاتورة. أسعار الأسطر **دائماً** تكلفة مخزون.

| `value_type` | `value` | أسعار الأصناف في التطبيق | مبلغ القيد (السيرفر) |
|--------------|---------|---------------------------|----------------------|
| `cost` | `null` | تكلفة المخزون | = COGS (نفس التكلفة) |
| `percent` | مثلاً `50` | تكلفة المخزون | `value%` × إجمالي الفاتورة (بعد تسعيرها بالتكلفة) |
| `fixed` | مثلاً `25` | تكلفة المخزون | مبلغ ثابت `value` |

اعرض `calculation_hint_*` تحت اختيار النوع. لا تحسب القيد على الجهاز؛ السيرفر يقيّده عند الحفظ.

`id` من هذا الرد هو `internal_consumption_type_id` عند الحفظ.

### أخطاء

| حالة | كود | HTTP |
|------|-----|------|
| بدون فرع | `establishment_id_required` | `422` |
| لا أنواع لهذا الفرع | — | `200` + `data: []` → أظهر: اضبط الأنواع من ويب (إعدادات مصروف الكاشير) |

أعد الجلب عند تبديل `establishment_id`. مفتاح كاش مقترح: `ic_types_branch_{establishmentId}`.

---

## 3) تكلفة المخزون — نفس محرّك الويب

الويب يستدعي `POST invoice-inventory-costs` ويضع `unit_cost` في سعر السطر (حقل للقراءة فقط، بلا خصم ولا ضريبة).

التطبيق يفعل **نفس الشيء**:

```http
POST /api/invoice-inventory-costs
```

```json
{
  "establishment_id": 1,
  "lines": [
    { "product_id": 44, "qty": 2, "unit_id": 10 },
    { "product_id": 88, "qty": 1, "unit_id": null }
  ]
}
```

| حقل السطر | مطلوب | ملاحظة |
|-----------|--------|--------|
| `product_id` | ✅ منطقياً | 0 يُرجع تكلفة 0 |
| `qty` | ✅ | كمية السطر (نفس وحدة البيع) |
| `unit_id` | اختياري | إن وُجد تُحوَّل الكمية لوحدة الأساس قبل FIFO/LIFO |

### نجاح `200`

```json
{
  "data": [
    {
      "product_id": 44,
      "qty": 2,
      "qty_base": 2,
      "unit_cost": 3.5,
      "total_cost": 7
    }
  ],
  "method": "fifo",
  "engine_active": true,
  "method_label_ar": "FIFO (أول دخول أول خروج)",
  "method_label_en": "FIFO (First in, first out)"
}
```

| حقل | معنى |
|-----|------|
| `data[i].unit_cost` | **سعر الوحدة** الذي تضعه في السطر `i` (نفس ترتيب `lines`) |
| `data[i].total_cost` | `unit_cost × qty` حسب المحرّك |
| `method` | `average` / `fifo` / `lifo` / `product_card` |
| `engine_active` | `false` ⇒ التكلفة من بطاقة الصنف (مثل الويب عندما محرّك الطبقات غير مفعّل) |

`product_card` = لا طبقات FIFO/LIFO؛ استخدم الرقم الراجع كما هو.

### قواعد العرض في السلة

1. استدعِ المعاينة عند: اختيار النوع، إضافة/حذف صنف، تغيير كمية أو وحدة، تبديل الفرع.
2. debounce ~200ms (مثل الويب).
3. `items[i].price` = `data[i].unit_cost` (وحافظ على نفس الترتيب: الصنف ثم المعدّلات ثم خيارات الكومبو تحت كل صنف إن أرسلتها في `lines`).
4. اجعل حقل السعر **للقراءة فقط**.
5. اجعل الخصم 0 ومقفلاً. اجعل الضريبة 0 ومقفلة.
6. إجمالي الفاتورة = مجموع `total_cost` (بدون ضريبة وبدون خصم).
7. لا تضف رسوم طرق الدفع.

FIFO/LIFO **تسلسلي** على نفس الصنف عبر الأسطر: لا تعِد ترتيب `lines` بين المعاينة والحفظ.

بدون فرع → `422` + `code: establishment_id_required`.

---

## 4) الحفظ — نفس endpoint البيع

```http
POST /api/stor-sales-invoice
```

السيرفر إن وُجد `internal_consumption_type_id` **أو** `purpose=internal_consumption`:

1. يفرض نوع مصروف (وإلا `422`).
2. يرفض الخصم والكوبون (`422` + `internal_consumption_discount_not_allowed`).
3. يعيد تسعير الأسطر بتكلفة المخزون (لا تعتمد على سعر البيع الذي أرسلته).
4. يصفر الضريبة والخصم والدفعات.
5. ينقص المخزون بمحرّك التكلفة.
6. يرحّل قيد المصروف (بدون إيراد).

أرسل النوع + الغرض صراحة حتى لو السيرفر يفهم أحدهما.

### مثال

```json
{
  "id": "local-uuid-or-int",
  "purpose": "internal_consumption",
  "internal_consumption_type_id": 3,
  "establishment_id": 1,
  "device_id": 3,
  "user_id": 5,
  "customer_id": null,
  "created_at": "2026-08-19T14:30:00",
  "invoice_number": "IC-001",
  "status": "final",
  "note": "وجبات عاملين",
  "discount_value": 0,
  "total_tax": 0,
  "total_before_discount": 7,
  "total_after_discount": 7,
  "total_paid": 7,
  "shift_id": "optional-shift",
  "items": [
    {
      "product_id": 44,
      "quantity": 2,
      "unit_id": 10,
      "price": 3.5,
      "price_after_discount": 3.5,
      "price_with_tax": 3.5,
      "discount_amount": 0,
      "tax_id": null,
      "tax_value": 0
    }
  ]
}
```

لا ترسل `payments` (أو أرسل `[]`). السيرفر يتجاهلها في هذا الوضع.

| حقل | مصروف داخلي |
|-----|-------------|
| `purpose` | `internal_consumption` (أو `staff_meals`) |
| `internal_consumption_type_id` | ✅ `id` من API الأنواع |
| `payments` | لا |
| `discount_value` / كوبون / خصم سطر | يجب 0 — وإلا 422 |
| `total_tax` | 0 |
| `customer_id` | اختياري (غالباً `null`) |
| `status` | `final` أو `approved` حتى يُرحَّل القيد (`draft` يخزّن بدون قيد) |

أسعار الطلب تُستبدل على السيرفر بالتكلفة الحالية؛ أرسل التكلفة من خطوة المعاينة ليطابق العرض ما سيُحفظ.

### قيم `purpose`

| قيمة | المعنى |
|------|--------|
| `internal_consumption` | ✅ المفضّلة |
| `staff_meals` | ✅ alias |
| `standard` / فارغ | بيع عادي — **إلا** إذا أُرسل `internal_consumption_type_id` فعندها تُعتبر مصروف |

---

## 5) القيد المحاسبي (لا يُبنى على الجهاز)

بعد الحفظ المعتمد، السيرفر يرحّل قيداً متوازناً:

1. **مدين** حساب النوع (حساب التحصيل/المصروف المربوط من إعدادات الويب) = مبلغ التحميل حسب `value_type`.
2. **دائن** حساب المخزون = COGS من محرّك التكلفة (متوسط / FIFO / LIFO، أو تكلفة البطاقة إن المحرّك غير مفعّل).
3. إن اختلف التحميل عن COGS (`percent` / `fixed`): سطر موازنة على **نفس حساب النوع** حتى يبقى القيد متوازناً.
4. **لا** إيراد مبيعات، **لا** ضريبة مخرجات، **لا** ذمم عميل، **لا** طريقة دفع.

صافي حركة المخزون = التكلفة. المستند مصروف/استهلاك، ليس فاتورة بيع.

لا تحسب قيوداً محلياً ولا تعرض أرباحاً على هذه الفاتورة.

---

## 6) واجهة الكاشير (مطلوب)

1. زر/وضع: «مصروف داخلي» (وليس «فاتورة مبيعات»).
2. قائمة أنواع من API الفرع. بدون اختيار نوع: لا حفظ.
3. بعد الاختيار:
   - أخفِ الدفع بالكامل (نقد / بطاقة / آجل / رسوم).
   - اقفل الخصم والكوبون والضريبة واضبطها 0.
   - أظهر تلميح: الأسعار = تكلفة المخزون، لا خصم ولا ضريبة ولا إيراد.
   - أظهر `calculation_hint_*` و`method_label_*` إن رغبت.
4. حدّث التكاليف من API عند تغيّر السلة.
5. عند الحفظ أرسل الحقول في القسم 4.
6. لا تضع الفاتورة ضمن ملخص **مبيعات** الشيفت.

البيع العادي: لا ترسل `internal_consumption_type_id` ولا `purpose` (أو `standard`).

---

## 7) أخطاء `422` مهمة

| `code` | السبب | UX |
|--------|--------|-----|
| `internal_consumption_type_required` | وُضع وضع المصروف بدون نوع | «اختر نوع المصروف الداخلي» |
| `internal_consumption_discount_not_allowed` | خصم أو كوبون أو خصم سطر | امنع الخصم في الواجهة قبل الإرسال |
| `internal_consumption_type_account_required` | النوع بلا حساب | «اضبط حساب النوع من إعدادات الفرع» |
| `internal_consumption_failed` | تكلفة / مخزون / قيد | اعرض `message` |
| كمية غير كافية | نفس فحص البيع | نفس رسالة النقص |
| `establishment_id_required` | أنواع أو تكلفة بدون فرع | أرسل فرع الشيفت |

نجاح الحفظ `200`:

```json
{ "message": "Added successfully" }
```

---

## 8) ملخص سريع

| الموضوع | المطلوب من Flutter |
|---------|-------------------|
| الأنواع | `GET internal-consumption-types?establishment_id=` |
| التكلفة | `POST invoice-inventory-costs` — `unit_cost` سعر السطر |
| الحفظ | نفس `stor-sales-invoice` + `purpose` + `internal_consumption_type_id` |
| خصم / ضريبة / دفع / رسوم دفع | ممنوعة |
| إيراد | لا — مصروف فقط |
| القيد | السيرفر فقط |

---

## 9) اختبار يدوي

1. ويب: فرع فيه نوع مصروف مربوط بحساب، ومستودعات + مصروف داخلي مفعّلان، وأصناف لها تكلفة ومخزون.
2. التطبيق: جلب الأنواع لذلك الفرع — تظهر الأسماء والتفاصيل.
3. اختر النوع → السلة بالتكلفة، الدفع والخصم مخفيان.
4. غيّر الكمية → التكلفة تتحدث (FIFO قد يتغيّر بين الوحدات).
5. حاول خصماً → الواجهة تمنع؛ إن أُرسل للسيرفر → 422.
6. احفظ → نجاح، المخزون ينقص، لا قيد إيراد، القيد مدين النوع / دائن المخزون.
7. بيع عادي بعدها ما زال يطلب طرق الدفع ويعمل كالسابق.

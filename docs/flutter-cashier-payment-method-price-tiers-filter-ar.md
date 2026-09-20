# My Bee Cashier (Flutter) — فلتر تسعيرات طرق الدفع + إيقاف الرسوم مؤقتاً

> **الغرض:** ملف واحد لمطوّر Flutter **الكاشير** بعد ربط التسعيرات بطرق الدفع وإيقاف رسوم طرق الدفع مؤقتاً.  
> **التاريخ:** 2026-09-20  
> **مرجع رسوم طرق الدفع (قديم — معطّل مؤقتاً):** `docs/flutter-cashier-payment-method-fees-ar.md`  
> **مرجع فلتر الرسوم السابق:** `docs/flutter-cashier-payment-method-fees-filter-ar.md`

---

## Cursor — ابدأ من هنا

```text
Read docs/flutter-cashier-payment-method-price-tiers-filter-ar.md and implement:
1) Keep GET /api/payment-methods?establishment_id={branchId}
2) Parse additive field price_tier_id on each payment method (nullable)
3) fees[] may be empty while fees are temporarily disabled — do NOT apply payment-method fees
4) On product catalog / product detail: parse additive price_tiers[] on each product
5) When user selects a payment method:
   - if method.price_tier_id is null → keep catalog base price (price / pricewithTax) unchanged
   - if method.price_tier_id is set → for each line, if product.price_tiers contains that tier → use that tier price; else keep base price
6) Switching payment method must recompute from catalog base (no stacking)
7) Internal consumption: no payment methods / no tier override
Follow every checklist item.
```

### Checklist

- [ ] الاستدعاء ما زال: `GET /api/payment-methods?establishment_id={branchId}`
- [ ] بدون `establishment_id` → `422` (لا تغيّر)
- [ ] اقرأ `price_tier_id` من كل طريقة دفع (`null` أو رقم)
- [ ] `fees[]` قد تكون **فارغة دائماً** حالياً — تجاهل تطبيق الرسوم مؤقتاً
- [ ] منتجات الكاشير تتضمن `price_tiers: [{ price_tier_id, price, pricewithTax }]`
- [ ] عند اختيار طريقة دفع: طبّق تسعيرة الطريقة على الأسطر التي لديها سعر لهذه التسعيرة فقط
- [ ] منتج بدون تسعيرة مطابقة → يبقى على السعر الأساسي
- [ ] طريقة دفع بدون `price_tier_id` → لا تغيير على الأسعار
- [ ] تغيير طريقة الدفع يعيد الحساب من السعر الأساسي (لا تراكم)
- [ ] استهلاك داخلي: لا طرق دفع، لا تسعيرة
- [ ] أعد جلب الطرق عند تبديل الفرع

---

## 1) ما تغيّر باختصار

| الموضوع | قبل | الآن |
|--------|-----|------|
| رسوم طريقة الدفع | تُطبَّق من `fees[]` | **موقوفة مؤقتاً** — الـ API يعيد `fees: []` |
| تسعيرة طريقة الدفع | غير موجودة | حقل جديد `price_tier_id` على كل طريقة |
| أسعار المنتج حسب التسعيرة | غير مُرجَعة للكاشير | `price_tiers[]` على المنتج |

> **مهم:** لا تحذف كود الرسوم من التطبيق إن وُجد — فقط **لا تطبّقه** طالما `fees` فارغ / حتى يُعاد تفعيل الرسوم من السيرفر.

---

## 2) طرق الدفع — الحقل الجديد

```http
GET /api/payment-methods?establishment_id=3
```

مثال استجابة (مختصر):

```json
{
  "data": [
    {
      "id": 10,
      "name_ar": "نقداً",
      "name_en": "Cash",
      "payment_method_key": "cash",
      "price_tier_id": null,
      "fees": []
    },
    {
      "id": 11,
      "name_ar": "بطاقة",
      "name_en": "Card",
      "payment_method_key": "card",
      "price_tier_id": 4,
      "fees": []
    }
  ]
}
```

| حقل | نوع | معنى |
|-----|-----|------|
| `price_tier_id` | `int?` | تسعيرة مربوطة بطريقة الدفع. `null` = لا تسعيرة |
| `fees` | `array` | حالياً فارغ (الرسوم موقوفة مؤقتاً على السيرفر) |

**توافق خلفي:** إن غاب `price_tier_id` من كاش قديم → اعتبره `null`.

---

## 3) أسعار المنتج حسب التسعيرة

من API المنتجات (نفس مسار كتالوج الكاشير الحالي)، كل منتج يتضمن حقلاً إضافياً:

```json
{
  "id": 55,
  "price": 28.7,
  "pricewithTax": 33.0,
  "price_tiers": [
    { "price_tier_id": 4, "price": 26.09, "pricewithTax": 30.0 },
    { "price_tier_id": 7, "price": 30.43, "pricewithTax": 35.0 }
  ]
}
```

| حقل | معنى |
|-----|------|
| `price` / `pricewithTax` | السعر الأساسي (الكتالوج) — **بدون** تسعيرة |
| `price_tiers[].price` | سعر التسعيرة **قبل الضريبة** |
| `price_tiers[].pricewithTax` | سعر التسعيرة **شامل الضريبة** |

- إن كان `price_tiers` مفقوداً أو `[]` → المنتج ليس له أسعار تسعيرة.
- استخدم نفس منطق العرض الحالي (`price` vs `pricewithTax`) حسب إعداد الضريبة في التطبيق.

---

## 4) قاعدة التطبيق عند اختيار طريقة الدفع

```text
base = product.price / product.pricewithTax   (حسب وضع الضريبة عندك)
tierId = selectedPaymentMethod.price_tier_id

if tierId == null:
    unit = base
else:
    match = product.price_tiers.firstWhere(t => t.price_tier_id == tierId, orElse: null)
    unit = match != null ? match.price(/withTax) : base
```

### ترتيب الطبقات (عند عودة الرسوم لاحقاً)

```text
catalog base → (optional) price-tier override → (optional) payment-method item fees
```

حالياً الطبقة الوسطى فقط فعّالة؛ الرسوم متوقفة.

### سلوك مطلوب

1. احفظ السعر الأساسي لكل سطر عند إضافة المنتج (من الكتالوج).
2. عند تغيير طريقة الدفع: أعد كل سطر من **الأساس** ثم طبّق التسعيرة إن وُجدت.
3. لا تُكدّس تسعيرة فوق تسعيرة سابقة.
4. تعديل يدوي لسعر السطر من الكاشير: قرار المنتج عندكم — الموصى به: يصبح أساساً جديداً لهذا السطر حتى يُعاد اختيار المنتج.

---

## 5) إعداد السيرفر (مرجع فقط — ليس على Flutter)

| مفتاح | قيمة افتراضية | أثر |
|-------|----------------|-----|
| `CASHIER_PAYMENT_METHOD_FEES_ENABLED` | `false` | يخفي تبويب الرسوم ويُفرّغ `fees` في API |

إعادة تفعيل الرسوم لاحقاً: ضع القيمة `true` ثم أعد نشر الإعداد.

شاشة الربط في الويب:  
`/settings/cashier-payment-methods` → تبويب **التسعيرات** لكل طريقة دفع (من `/priceTier`).

---

## 6) حالات اختبار سريعة

| # | الحالة | النتيجة المتوقعة |
|---|--------|------------------|
| 1 | طريقة بدون `price_tier_id` | الأسعار الأساسية كما هي |
| 2 | طريقة بتسعيرة + منتج له سعر للتسعيرة | سعر التسعيرة على السطر |
| 3 | طريقة بتسعيرة + منتج بلا سعر لهذه التسعيرة | السعر الأساسي |
| 4 | تبديل من بطاقة (تسعيرة) → نقد (بدون) | رجوع للسعر الأساسي |
| 5 | `fees` فارغ | لا رسوم على الإجمالي |
| 6 | استهلاك داخلي | لا تسعيرة / لا طرق دفع |

---

## 7) ما لا تفعله

- لا تستخدم `auto_apply_type` أو منطق رسوم الخدمة لتسعيرات طرق الدفع.
- لا تفترض أن كل المنتجات لها `price_tiers`.
- لا تكسر العملاء القدامى: الحقول الجديدة **additive**.
- لا ترسل `price_tier_id` في POST البيع إلا إذا طُلب لاحقاً صراحةً — احسب السعر محلياً كما السعر الأساسي اليوم.

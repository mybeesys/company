# My Bee Cashier (Flutter) — رسوم طرق الدفع حسب الفرع

> **الغرض:** ملف واحد لمطوّر Flutter **الكاشير**. مرّره لـ Cursor.  
> **التاريخ:** 2026-08-19  
> **النطاق:** `GET /api/payment-methods` فقط — حقول إضافية، بدون كسر العقد الحالي.  
> **مرجع سابق:** `docs/flutter-cashier-branch-payments-internal-consumption-ar.md` (الفرع + `method_id` + الاستهلاك الداخلي).

---

## Cursor — ابدأ من هنا

```text
Read docs/flutter-cashier-payment-method-fees-ar.md and implement:
1) Keep GET /api/payment-methods?establishment_id={branchId}
2) Parse the new additive `fees` array on each method
3) Apply fees when a payment method is selected (item vs order)
4) Do NOT break existing payments[].method_id flow
5) Internal consumption: ignore payment method fees
Follow every checklist item.
```

### Checklist

- [ ] ما زال الاستدعاء: `GET /api/payment-methods?establishment_id={branchId}`
- [ ] الحقول القديمة كما هي: `id`, `name_ar`, `name_en`, `active` — لا تعتمد على حذف أي منها
- [ ] اقرأ `fees` (مصفوفة؛ قد تكون `[]`)
- [ ] عند اختيار طريقة دفع: طبّق رسومها على السلة
- [ ] `payments[].method_id` = `id` من نفس الرد (صف الفرع) كما قبل
- [ ] لا ترسل الرسوم كطريقة دفع منفصلة
- [ ] استهلاك داخلي: لا دفع، لا رسوم طريقة دفع
- [ ] أعد الجلب عند تبديل الفرع (الرسوم تختلف مع الطرق)

---

## 1) ماذا تغيّر؟

نفس الـ API. نفس الفلتر بالفرع. **أضفنا حقولاً ولم نحذف شيئاً.**

| حقل | قبل | بعد |
|-----|-----|-----|
| `id` / `name_ar` / `name_en` / `description_*` / `active` | موجود | كما هو |
| `payment_method_key` | غير موجود | اختياري: `cash` / `card` / `delivery_apps` / … |
| `fees` | غير موجود | مصفوفة الرسوم **النشطة فقط** |

بدون فرع → `422` كما قبل. قائمة فارغة → `200` + `data: []`.

```http
GET /api/payment-methods?establishment_id=1
```

### نجاح `200`

```json
{
  "data": [
    {
      "id": 12,
      "name_ar": "نقداً",
      "name_en": "cash",
      "description_en": null,
      "description_ar": null,
      "active": 1,
      "payment_method_key": "cash",
      "fees": []
    },
    {
      "id": 13,
      "name_ar": "بطاقة",
      "name_en": "card",
      "description_en": null,
      "description_ar": null,
      "active": 1,
      "payment_method_key": "card",
      "fees": [
        {
          "id": 4,
          "name_ar": "عمولة شبكة",
          "name_en": "Card network fee",
          "fee_type": "1",
          "is_percent": true,
          "amount": 2.5,
          "application_type": "1",
          "applies_to": "order",
          "is_active": true,
          "sort_order": 0
        }
      ]
    }
  ]
}
```

> **`id` ما زال = صف الفرع** (`est_establishment_payment_accounts.id`) → هذا `payments[].method_id`.

---

## 2) شكل الرسم

| حقل | نوع | معنى |
|-----|-----|------|
| `id` | int | معرّف الرسم (عرض فقط) |
| `name_ar` / `name_en` | string | اسم يظهر للزبون/الكاشير |
| `fee_type` | `"0"` \| `"1"` | `0` مبلغ ثابت — `1` نسبة مئوية |
| `is_percent` | bool | اختصار لـ `fee_type == "1"` |
| `amount` | number | إن نسبة: `2.5` = 2.5٪. إن مبلغ: القيمة بالعملة |
| `application_type` | `"0"` \| `"1"` | `0` على كل منتج — `1` على إجمالي الطلب |
| `applies_to` | `"item"` \| `"order"` | اختصار أوضح من `application_type` |
| `is_active` | bool | الـ API يرجع النشط فقط؛ القيمة دائماً `true` |
| `sort_order` | int | ترتيب العرض |

استخدم `is_percent` + `applies_to` في الكود. الحقول `"0"`/`"1"` للتوافق مع الويب.

---

## 3) كيف تُحسب (نفس منطق الويب)

`orderNet` = مجموع `(سعر الوحدة الأساسي × الكمية)` **قبل** إضافة رسوم طريقة الدفع.

### أ) `applies_to = item`

يُضاف على **سعر كل صنف** ثم يُعاد حساب السطر:

| `is_percent` | المعادلة لكل سطر |
|--------------|-------------------|
| `false` | `extra += amount × qty` |
| `true` | `extra += lineNet × (amount / 100)` |

سعر الوحدة المعروض ≈ `baseUnit + extraPerUnit`.

### ب) `applies_to = order`

يُحسب على **إجمالي الفاتورة** (`orderNet`) ويُضاف للمجموع النهائي فقط (لا يغيّر سعر السطر):

| `is_percent` | المعادلة |
|--------------|----------|
| `false` | `extra = amount` |
| `true` | `extra = orderNet × (amount / 100)` |

قرّب كل نتيجة إلى منزلتين.

### عدة طرق دفع في نفس الفاتورة

اجمع رسوم **كل** `method_id` مختار له `fees`.  
إن دُفع نقد + بطاقة: نقد غالباً `fees: []`، البطاقة تطبّق عمولتها.

لا تطبّق الرسم مرتين لنفس الطريقة.

### استهلاك داخلي

`purpose = internal_consumption` → **لا** طرق دفع، **لا** رسوم طريقة دفع.

---

## 4) ماذا ترسل عند البيع؟

**لا تغيّر** `POST /api/stor-sales-invoice`.

1. احسب الرسوم محلياً.
2. أدخل رسم المنتج في أسعار/أسطر `items`.
3. أدخل رسم الطلب في `total_before_discount` / `total_paid` كالمعتاد.
4. `payments[]` تبقى:

```json
{
  "establishment_id": 1,
  "payments": [
    { "method_id": 13, "amount": 102.5 }
  ]
}
```

`amount` هنا = ما يدفعه الزبون **بعد** الرسوم.  
لا ترسل `fees` في جسم الفاتورة. لا تستخدم `fee.id` كـ `method_id`.

---

## 5) Dart — نموذج + حساب

```dart
class BranchPaymentMethod {
  final int id;
  final String nameAr;
  final String nameEn;
  final String? paymentMethodKey;
  final List<PaymentMethodFee> fees;

  const BranchPaymentMethod({
    required this.id,
    required this.nameAr,
    required this.nameEn,
    this.paymentMethodKey,
    this.fees = const [],
  });

  factory BranchPaymentMethod.fromJson(Map<String, dynamic> json) {
    return BranchPaymentMethod(
      id: json['id'] as int,
      nameAr: (json['name_ar'] ?? '') as String,
      nameEn: (json['name_en'] ?? '') as String,
      paymentMethodKey: json['payment_method_key'] as String?,
      fees: (json['fees'] as List? ?? [])
          .map((e) => PaymentMethodFee.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList(),
    );
  }
}

class PaymentMethodFee {
  final int id;
  final String nameAr;
  final String nameEn;
  final bool isPercent;
  final double amount;
  final bool appliesToItem; // true = item, false = order

  const PaymentMethodFee({
    required this.id,
    required this.nameAr,
    required this.nameEn,
    required this.isPercent,
    required this.amount,
    required this.appliesToItem,
  });

  factory PaymentMethodFee.fromJson(Map<String, dynamic> json) {
    final appliesTo = json['applies_to'] as String?
        ?? ((json['application_type']?.toString() == '0') ? 'item' : 'order');
    return PaymentMethodFee(
      id: json['id'] as int,
      nameAr: (json['name_ar'] ?? '') as String,
      nameEn: (json['name_en'] ?? '') as String,
      isPercent: json['is_percent'] == true || json['fee_type']?.toString() == '1',
      amount: (json['amount'] as num?)?.toDouble() ?? 0,
      appliesToItem: appliesTo == 'item',
    );
  }
}

double round2(double v) => (v * 100).round() / 100;

/// lines: كل عنصر {qty, net} حيث net = سعر أساسي × كمية (قبل رسم الطريقة)
double computePaymentMethodFees({
  required List<PaymentMethodFee> fees,
  required List<({double qty, double net})> lines,
}) {
  final orderNet = round2(lines.fold<double>(0, (s, l) => s + l.net));
  var total = 0.0;

  for (final fee in fees) {
    if (fee.appliesToItem) {
      for (final line in lines) {
        if (line.qty <= 0 || line.net <= 0) continue;
        total += fee.isPercent
            ? round2(line.net * (fee.amount / 100))
            : round2(fee.amount * line.qty);
      }
    } else {
      total += fee.isPercent
          ? round2(orderNet * (fee.amount / 100))
          : round2(fee.amount);
    }
  }

  return round2(total);
}
```

عند تغيير طريقة الدفع: أعد الحساب من **السعر الأساسي** للصنف (لا تراكِم الرسم فوق سعر سبق تعديله).

---

## 6) واجهة مقترحة

- تحت المجموع: سطر «رسوم طريقة الدفع» يظهر فقط إذا المجموع > 0.
- الاسم من `name_ar` / `name_en`.
- نقد بدون رسوم: أخفِ السطر.
- لا تكسر شاشة الدفع الحالية إن `fees` مفقود (عاملها `[]`) — توافق مع كاش قديم قبل التحديث.

---

## 7) ما لا تفعله

| ممنوع | السبب |
|--------|--------|
| الاعتماد على قائمة طرق دفع عالمية بدون `establishment_id` | كل فرع له طرق ورسوم |
| استخدام `fee.id` في `payments[].method_id` | `method_id` = `id` الطريقة |
| تجاهل الحدث/القائمة لأن `fees` فارغة | النقد غالباً بلا رسوم وهذا صحيح |
| تطبيق الرسوم على الاستهلاك الداخلي | لا يوجد دفع |

---

## 8) اختبار يدوي

1. فرع A: بطاقة بعمولة 2.5٪ على الطلب + نقد بلا رسوم.
2. من التطبيق على A: البطاقة فيها `fees.length == 1`، النقد `fees: []`.
3. سلة 100 → اختيار بطاقة → الزبون يدفع 102.50.
4. نفس السلة نقداً → 100 بدون سطر رسوم.
5. فرع B بدون هذه الطريقة: لا تظهر، ولا تستخدم `id` فرع A.
6. استهلاك داخلي: لا رسوم.

---

## 9) Backend (مرجعية — لا تعدّل من Flutter)

- `Modules/General/Http/Controllers/PaymentMethodsApiController.php`
- `Modules/General/Transformers/PaymentMethodsResource.php`
- جدول الرسوم: `est_payment_method_fees` (مضبوط من ويب: إعدادات الكاشير → تاب الرسوم على طريقة الدفع)

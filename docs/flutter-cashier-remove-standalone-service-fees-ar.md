# My Bee Cashier (Flutter) — إلغاء رسوم الخدمة المنفصلة

> **الغرض:** ملف واحد لمطوّر Flutter **الكاشير**. مرّره لـ Cursor ونفّذ كل بند.  
> **التاريخ:** 2026-10-07  
> **المشكلة:** شاشة تأكيد الطلب ما زالت تعرض قسم **رسوم الخدمة** (مثل «عمولة جاهز») منفصلاً عن طريقة الدفع.  
> **المطلوب:** الرسوم تُطبَّق **فقط** من طريقة الدفع. لا مختار رسوم، لا شريحة عمولة بجانب «نقداً / جاهز».  
> **القيود المحاسبية على السيرفر جاهزة.** لا تغيّر شكل `payments[].method_id`.

---

## Cursor — ابدأ من هنا

```text
Read docs/flutter-cashier-remove-standalone-service-fees-ar.md and implement:
1) Remove the confirmation-screen “رسوم الخدمة / Service fees” section entirely
2) Do not call GET /api/service-fees on the cashier confirmation / checkout flow
3) Do not send applied_service_fee_ids or service_fees[] for payment-method fees
4) Keep GET /api/payment-methods?establishment_id={branchId} and parse service_fees[]
5) Apply those fees automatically when that payment method is selected
6) Honor calculated_on (before_tax / after_tax), taxable, fee_direction, show_on_invoice
7) Internal consumption: no fees
Follow every checklist item.
```

### Checklist

- [ ] احذف ويدجت/سكشن **رسوم الخدمة** من شاشة تأكيد الطلب (الشاشة الصفراء في الصورة)
- [ ] لا تجلب `GET /api/service-fees` لهذه الشاشة
- [ ] لا تعرض شريحة «عمولة جاهز» أو أي رسم كخيار مستقل بجانب طرق الدفع
- [ ] اقرأ `service_fees[]` من كل طريقة في `GET /api/payment-methods?establishment_id=`
- [ ] اختيار «طلبات جاهز / آجل» يطبّق رسوم تلك الطريقة فوراً
- [ ] تبديل نقد ↔ جاهز يعيد الحساب من الصفر (لا تراكم)
- [ ] `increases_customer_total === false` أو `fee_direction === "PAID"`: لا تزد ما يدفعه العميل
- [ ] `show_on_invoice === false`: لا تعرض بند الرسم على ملخص الكاشير / المعاينة / الطباعة
- [ ] `calculated_on === "before_tax"` مقابل `"after_tax"` كما في الجدول أدناه
- [ ] عند الحفظ: `payments[].method_id` فقط؛ لا `applied_service_fee_ids` لرسوم الطريقة
- [ ] استهلاك داخلي: لا طرق دفع، لا رسوم
- [ ] أعد جلب الطرق عند تبديل الفرع

---

## 1) ماذا تلغي؟

| | قبل (خطأ ظاهر في الصورة) | الآن |
|--|--|--|
| قسم «رسوم الخدمة» فوق طريقة الدفع | شريحة «عمولة جاهز» تُختار يدوياً | **محذوف بالكامل** |
| `GET /api/service-fees` | قائمة للكاشير | **لا تُستخدم** في تأكيد الطلب |
| `applied_service_fee_ids` | يُرسل مع الحفظ | **لا ترسله** لرسوم طريقة الدفع |
| مصدر الرسم | المختار المنفصل | `payment-methods[].service_fees` |

السيرفر يستبعد رسوم `auto_apply = payment_method` من `GET /api/service-fees`، ويضع `selectable_on_cashier: false` على `service_fees[]`. حتى لو بقي استدعاء قديم، **لا تعرض** أي عنصر `selectable_on_cashier === false`.

---

## 2) API المعتمد

```http
GET /api/payment-methods?establishment_id=1
```

```json
{
  "id": 22,
  "name_ar": "طلبات جاهز / آجل",
  "service_fees": [
    {
      "id": 8,
      "name_ar": "عمولة جاهز",
      "amount": 20,
      "is_percent": true,
      "applies_to": "order",
      "calculation_method": "0",
      "calculated_on": "before_tax",
      "taxable": true,
      "fee_direction": "PAID",
      "increases_customer_total": false,
      "show_on_invoice": false,
      "auto_apply": "payment_method",
      "selectable_on_cashier": false
    }
  ]
}
```

- `calculation_method`: `"0"` = قبل الضريبة، `"1"` = بعد الضريبة (نفس إعدادات `/settings/service-fees`).
- `calculated_on`: `"before_tax"` | `"after_tax"` — استخدم هذا أوضح للكاشير.

---

## 3) الحساب (مطابقة الإعدادات والويب)

بعد خصم الكوبون على **المنتجات فقط**:

| نسبة؟ | النطاق | `calculated_on` | أساس الرسم قبل ضريبة الرسم |
|--|--|--|--|
| نعم | `order` | `before_tax` | صافي المنتجات بعد الخصم |
| نعم | `order` | `after_tax` | إجمالي المنتجات بعد الخصم **والضريبة** |
| نعم | `item` | `before_tax` | صافي كل سطر |
| نعم | `item` | `after_tax` | إجمالي كل سطر مع الضريبة |
| مبلغ ثابت | `order` | — | المبلغ مرة واحدة |
| مبلغ ثابت | `item` | — | المبلغ × الكمية |

إن `taxable`: ضريبة الرسم = مبلغ الرسم × معدل ضريبة المنتجات (متوسط مرجح = VAT المنتجات ÷ الصافي).

مثال منتجات 100 + VAT 15٪ = 115، رسم 20٪ خاضع:

| إعداد «تُحسب على» | مبلغ الرسم | ضريبة الرسم |
|--|--|--|
| الإجمالي قبل الضريبة (`before_tax`) | 20.00 | 3.00 |
| الإجمالي بعد الضريبة (`after_tax`) | 23.00 | 3.45 |

### محصّل مقابل مدفوع

| | COLLECTED / `increases_customer_total: true` | PAID / `increases_customer_total: false` |
|--|--|--|
| ما يدفعه العميل | منتجات + VAT + الرسم + ضريبة الرسم | منتجات + VAT **فقط** |
| بند على الفاتورة | فقط إن `show_on_invoice === true` | لا يظهر |
| القيد | السيرفر | السيرفر (مصروف العمولة) |

عمولة جاهز النموذجية: **PAID** + `show_on_invoice: false` → الكاشير يعرض 115، لا بند «عمولة جاهز» على الفاتورة، والقيد يُرحَّل على السيرفر.

---

## 4) المعاينة والطباعة على الجهاز

- لا تضف رسم PAID إلى QR / المبلغ المستحق / تقسيم الفاتورة.
- إن `show_on_invoice === false`: اخفِ اسم الرسم من الملخص والمعاينة وإيصال الطباعة حتى لو كان COLLECTED (الإجمالي المحاسبي يبقى على السيرفر).
- إن ظاهر: اعرض **مبلغ الرسم قبل ضريبته**. ضريبة الرسم تُدمج في سطر الضريبة مع ضريبة المنتجات حتى لا تُحسب مرتين.
- السيرفر يطبع/يستعرض بنفس القاعدة في استعراض الفاتورة وطباعتها.

---

## 5) الحفظ

```http
POST /api/stor-sales-invoice
```

```json
{
  "establishment_id": 1,
  "payments": [
    { "method_id": 22, "amount": 115 }
  ]
}
```

- `method_id` = `id` طريقة الدفع (صف الفرع).
- `payments[].amount` / `total_paid` = ما يدفعه **العميل** (بدون رسوم PAID).
- لا ترسل `applied_service_fee_ids` ولا `service_fees` لعمولة جاهز. السيرفر يطبّق من `method_id`.
- إن أُرسلت تلك الحقول بالخطأ لرسم مربوط بطريقة الدفع، السيرفر **يتجاهلها** ما لم تُختر طريقة الدفع نفسها.

---

## 6) ما لا تفعله

- لا تترك قسم رسوم خدمة فارغاً؛ احذفه.
- لا تخلط `fees[]` القديمة مع `service_fees[]`.
- لا تضف عمولة PAID إلى إجمالي العميل.
- لا تعتمد على `docs/flutter-cashier-service-fees-ar.md` لقسم المختار اليدوي — **ملغى للكاشير**.

---

## 7) تحقق سريع

1. نقد بدون `service_fees` → لا قسم رسوم، البيع كما قبل.  
2. جاهز + PAID 20٪ قبل الضريبة → العميل 115، لا شريحة عمولة، لا بند على الإيصال.  
3. نفس الرسم COLLECTED + إظهار على الفاتورة → الإجمالي يزيد، والبند يظهر في المعاينة/الطباعة بمبلغ الرسم بدون ازدواج ضريبة.  
4. نفس الرسم COLLECTED + إخفاء على الفاتورة → الإجمالي يزيد، البند لا يظهر.  
5. تبديل جاهز → نقد يصفّر الرسم.

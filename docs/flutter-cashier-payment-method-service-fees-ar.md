# My Bee Cashier (Flutter) — رسوم طريقة الدفع (عمولة جاهز وغيرها)

> **تحديث 2026-10-07:** احذف قسم رسوم الخدمة المنفصل بالكامل. الملف التنفيذي: `docs/flutter-cashier-remove-standalone-service-fees-ar.md`.

> **الغرض:** ملف واحد لمطوّر Flutter **الكاشير**. مرّره لـ Cursor ونفّذ كل بند.  
> **التاريخ:** 2026-10-06  
> **النطاق:** عند اختيار طريقة دفع مربوطة برسم خدمة (إعداد الفرع: تطبيق تلقائي حسب طريقة الدفع) تُطبَّق الرسوم **تلقائياً**. لا يظهر مختار «رسوم الخدمة» لهذه الرسوم.  
> **المرجع المحاسبي:** مذكرة الرسوم المحصلة / المدفوعة (COLLECTED vs PAID).  
> **لا يكسر:** `payments[].method_id` ولا الاستهلاك الداخلي.

---

## Cursor — ابدأ من هنا

```text
Read docs/flutter-cashier-payment-method-service-fees-ar.md and implement:
1) Keep GET /api/payment-methods?establishment_id={branchId}
2) Parse additive service_fees[] on each payment method (may be empty)
3) When the cashier selects that method, apply those fees automatically — do NOT show a separate fee picker for them
4) COLLECTED fees increase the amount the customer pays; PAID fees do not
5) On store you may omit applied_service_fee_ids for these fees — backend applies them from payments[].method_id
6) Do NOT call GET /api/service-fees and do NOT show a standalone fee picker
7) Internal consumption: ignore all service fees
Follow every checklist item.
```

### Checklist

- [ ] الاستدعاء كما هو: `GET /api/payment-methods?establishment_id={branchId}`
- [ ] اقرأ `service_fees` على كل طريقة (مصفوفة؛ قد تكون `[]`)
- [ ] `fees` القديمة قد تبقى فارغة — **لا تعتمد عليها** لهذه العمولة
- [ ] اختيار «طلبات جاهز / آجل» يطبّق `service_fees` لتلك الطريقة فوراً دون شاشة اختيار رسم
- [ ] إن `increases_customer_total === false` أو `fee_direction === "PAID"`: لا تزد إجمالي العميل؛ يمكن عرض تلميح داخلي للكاشير فقط
- [ ] إن `increases_customer_total === true`: أضف `amount` (+ ضريبة الرسم إن `taxable`) إلى ما يدفعه العميل
- [ ] عند الحفظ: `payments[].method_id` = `id` الطريقة. لا حاجة لإرسال `applied_service_fee_ids` لرسوم الطريقة
- [ ] لا تستدعِ `GET /api/service-fees` ولا تعرض قسم رسوم خدمة منفصل
- [ ] استهلاك داخلي: لا طرق دفع، لا رسوم
- [ ] `payments` تغطي **إجمالي العميل** فقط (منتجات ± خصم ± VAT منتجات ± الرسوم المحصلة فقط)
- [ ] أعد جلب الطرق عند تبديل الفرع

---

## 1) ماذا تغيّر؟

في الإعدادات: رسم «عمولة جاهز» مربوط بـ **طريقة الدفع** + اتجاه محصّل أو مدفوع. الكاشير يختار طريقة الدفع فقط.

| | قبل | الآن |
|--|-----|------|
| اختيار الرسم يدوياً على شاشة التأكيد | مطلوب | **ممنوع** لرسوم مربوطة بطريقة الدفع |
| مصدر الرسم | `GET /api/service-fees` ثم اختيار | `payment-methods[].service_fees` |
| الحفظ | `applied_service_fee_ids` | السيرفر يطبّق من `payments[].method_id` |
| `fees` على طريقة الدفع | رسوم قديمة معطّلة غالباً | اتركها؛ استخدم `service_fees` |

---

## 2) API الطرق

```http
GET /api/payment-methods?establishment_id=1
```

بدون فرع → `422`. قائمة فارغة → `200` + `data: []`.

### حقل جديد على كل طريقة: `service_fees`

```json
{
  "data": [
    {
      "id": 22,
      "name_ar": "طلبات جاهز / آجل",
      "name_en": "Jahz / credit",
      "active": 1,
      "payment_method_key": "delivery_apps",
      "price_tier_id": null,
      "fees": [],
      "service_fees": [
        {
          "id": 8,
          "name_ar": "عمولة جاهز",
          "name_en": "Jahz fees",
          "amount": 20,
          "service_fee_type": "1",
          "is_percent": true,
          "application_type": "1",
          "applies_to": "order",
          "calculation_method": "0",
          "calculated_on": "before_tax",
          "taxable": true,
          "fee_direction": "PAID",
          "increases_customer_total": false,
          "show_on_invoice": false,
          "auto_apply": "payment_method",
          "auto_apply_type": "2"
        }
      ]
    },
    {
      "id": 10,
      "name_ar": "نقداً",
      "name_en": "Cash",
      "fees": [],
      "service_fees": []
    }
  ]
}
```

`id` داخل `service_fees` هو معرف رسم الفرع نفسه (`GET /api/service-fees`).

---

## 3) الحساب على الجهاز (مطابقة الويب)

بعد خصم الكوبون/الفاتورة على **المنتجات فقط**:

| `service_fee_type` / `is_percent` | `applies_to` | `calculated_on` | مبلغ الرسم قبل الضريبة |
|--|--|--|--|
| نسبة | `order` | `before_tax` | صافي المنتجات بعد الخصم × ٪ |
| نسبة | `order` | `after_tax` | إجمالي المنتجات بعد الخصم والضريبة × ٪ |
| نسبة | `item` | `before_tax` | صافي كل سطر × ٪ |
| نسبة | `item` | `after_tax` | إجمالي كل سطر × ٪ |
| مبلغ | `order` | — | المبلغ مرة واحدة |
| مبلغ | `item` | — | المبلغ × الكمية لكل سطر |

إن `taxable`: ضريبة الرسم = مبلغ الرسم × معدل ضريبة المنتجات (متوسط مرجح).

### محصّل مقابل مدفوع

| | `COLLECTED` / `increases_customer_total: true` | `PAID` / `increases_customer_total: false` |
|--|--|--|
| فاتورة العميل | تزيد بالرسم + ضريبة الرسم | **لا تزيد** |
| ماذا يعرض الكاشير للعميل | بنود + إجمالي أعلى | لا تضف للرقم المطلوب من العميل |
| القيد | إيراد رسوم (السيرفر) | مصروف على حساب طريقة الدفع (السيرفر) |

مثال مدفوع (عمولة جاهز 20٪، وجبة 100، بدون خصم، ضريبة 15٪):

- يدفع العميل: `100 + 15 = 115`
- الرسم للمنشأة: `20` + ضريبة مدخلات `3` إن كان خاضعاً — **ليس** على العميل
- لا تستخدم شاشة فيها «رسوم الخدمة 3.48» فوق إجمالي العميل لهذه الحالة

مثال محصّل (نفس النسبة لكنها COLLECTED):

- يدفع العميل: `100 + 15 + 20 + 3 = 138`

`show_on_invoice`: إن false لا تعرض بند الرسم على فاتورة العميل حتى لو كان محصّلاً (يبقى ضمن الإجمالي المحاسبي على السيرفر). على الكاشير: اخفِ البند إن `show_on_invoice === false`.

---

## 4) الحفظ

```http
POST /api/stor-sales-invoice
```

```json
{
  "establishment_id": 1,
  "customer_id": 12,
  "coupon_code": null,
  "total_before_discount": 100,
  "discount_value": 0,
  "total_after_discount": 100,
  "total_tax": 15,
  "total_paid": 115,
  "payments": [
    { "method_id": 22, "amount": 115 }
  ]
}
```

- `method_id` = `id` من `GET /api/payment-methods` (صف الفرع).
- `total_paid` / `payments[].amount` = ما يدفعه **العميل** (بدون رسوم PAID).
- لا ترسل `applied_service_fee_ids` لرسوم الطريقة؛ السيرفر يضيفها من الطريقة.
- إن أرسلت `service_fee_amount` من الجهاز لرسوم محصلة، السيرفر يخصمها من المجموع ثم يعيد الحساب من الكتالوج حتى لا تُضاعف.

رسوماً اختيارية غير مربوطة بطريقة الدفع: ما زال `GET /api/service-fees` + `applied_service_fee_ids`.

---

## 5) ما لا تفعله

- لا تخلط `fees` (رسوم طريقة الدفع القديمة المعطّلة) مع `service_fees`.
- لا تترك زر «عمولة جاهز» يُختار يدوياً إذا كانت الطريقة نفسها تطبّق الرسم.
- لا تضف عمولة PAID إلى QR / المبلغ المستحق / تقسيم الفاتورة على العميل.
- لا تطبّق الرسوم على الاستهلاك الداخلي.

---

## 6) تحقق سريع

1. طريقة نقد بدون `service_fees` → البيع كما قبل.  
2. طريقة جاهز + رسم PAID 20٪ → إجمالي العميل = المنتجات + VAT فقط، والقيد على السيرفر يحمّل المصروف.  
3. نفس الرسم لو حُوّل إلى COLLECTED في الإعدادات → الإجمالي يزيد بالرسم وضريبته تلقائياً عند اختيار نفس الطريقة.  
4. تبديل نقد ↔ جاهز يعيد الحساب من الصفر (لا تراكم).

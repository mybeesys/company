# My Bee — Coupons API — دليل Flutter

> **الجمهور:** مطوّrio Flutter (POS / كاشير / مبيعات)  
> **Base URL:** `https://{tenant-domain}/api`  
> **Auth:** `Authorization: Bearer {token}` — نفس توكن `company-login` أو `employee-login`  
> **Tenant:** الطلبات على subdomain الشركة (مثل `test.localhost:8100`)

---

## 1. ملخص الـ APIs

| Method | Endpoint | الغرض |
|--------|----------|--------|
| `GET` | `/api/v1/coupons/settings` | هل الكوبونات مفعّلة في النظام؟ |
| `GET` | `/api/v1/coupons` | استعراض الكوبونات (فلترة + pagination) |
| `GET` | `/api/v1/coupons/{id}` | تفاصيل كوبون |
| `GET` | `/api/v1/coupons/by-code/{code}` | جلب كوبون بالكود (مسح QR / إدخال يدوي) |
| `POST` | `/api/v1/coupons/validate` | **معاينة** تطبيق الكوبون على السلة (بدون حفظ) |
| `POST` | `/api/stor-sales-invoice` | حفظ الفاتورة مع `coupon_code` (تسجيل الاستخدام) |

---

## 2. تدفق Flutter الموصى به

```
1. GET /v1/coupons/settings
   └─ إذا enabled=false → أخفِ زر الكوبون

2. (اختياري) GET /v1/coupons?available_only=1&establishment_id=X
   └─ عرض قائمة كوبونات متاحة للكاشير

3. المستخدم يدخل الكود أو يختار من القائمة
   └─ POST /v1/coupons/validate  ← معاينة الخصم على السلة الحالية

4. عند تأكيد الفاتورة
   └─ POST /api/stor-sales-invoice مع coupon_code + نفس items والمجاميع
```

**مهم:** استدعِ `validate` **قبل** إتمام البيع لعرض الخصم للمستخدم. عند الحفظ، أرسل `coupon_code` في فاتورة POS — السيرفر يعيد حساب الخصم ويسجّل الاستخدام.

---

## 3. GET `/api/v1/coupons/settings`

### Response `200`

```json
{
  "enabled": true
}
```

| الحقل | الوصف |
|--------|--------|
| `enabled` | `false` إذا أُوقفت الكوبونات من إعدادات الفواتير في Dashboard |

---

## 4. GET `/api/v1/coupons`

### Query parameters

| Parameter | Type | الوصف |
|-----------|------|--------|
| `search` | string | بحث في `name` أو `code` |
| `is_active` | bool | `1` / `0` |
| `establishment_id` | int | كوبونات هذا الفرع (أو الكوبونات العامة بدون فرع) |
| `available_only` | bool | نشط + ضمن التاريخ + لم يُستنفد `coupon_count` |
| `discount_apply_to` | string | `all` \| `product` \| `category` |
| `per_page` | int | افتراضي `20`، أقصى `100` |
| `page` | int | رقم الصفحة |

### مثال

```http
GET /api/v1/coupons?available_only=1&establishment_id=2&per_page=20
Authorization: Bearer 1|xxxx
Accept: application/json
```

### Response `200`

```json
{
  "data": [
    {
      "id": 3,
      "name": "خصم رمضان",
      "code": "RAMADAN10",
      "value_type": "percent",
      "value": 10,
      "discount_apply_to": "all",
      "start_date": "2026-03-01 00:00",
      "end_date": "2026-03-31 23:59",
      "coupon_count": 100,
      "person_use_time_count": 1,
      "apply_to_clients_groups": false,
      "is_active": true,
      "used_count": 12,
      "remaining_uses": 88,
      "establishments": [{ "id": 2, "name": "فرع الرياض" }],
      "products": [],
      "categories": []
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

### حقول مهمة

| الحقل | الوصف |
|--------|--------|
| `value_type` | `fixed` = مبلغ ثابت، `percent` = نسبة |
| `discount_apply_to` | `all` كل الفاتورة، `product` منتجات محددة، `category` تصنيفات |
| `coupon_count` | `0` = استخدام غير محدود |
| `person_use_time_count` | `0` = غير محدود لكل عميل |
| `remaining_uses` | `null` إذا غير محدود |

---

## 5. GET `/api/v1/coupons/{id}`

```http
GET /api/v1/coupons/3
```

### Response `200`

```json
{
  "data": { "...": "نفس شكل عنصر القائمة" }
}
```

### Response `404`

```json
{
  "message": "الكوبون غير موجود",
  "code": "coupon_not_found"
}
```

---

## 6. GET `/api/v1/coupons/by-code/{code}`

```http
GET /api/v1/coupons/by-code/RAMADAN10
```

نفس response تفاصيل الكوبون — مفيد بعد مسح الباركود.

---

## 7. POST `/api/v1/coupons/validate`

**معاينة** الخصم على السلة الحالية — **لا يسجّل استخداماً**.

### Request body

```json
{
  "code": "RAMADAN10",
  "contact_id": 15,
  "establishment_id": 2,
  "taxable_before": 500.00,
  "tax_amount": 75.00,
  "items": [
    {
      "product_id": 101,
      "quantity": 2,
      "unit_price": 200.00,
      "total_before_vat": 400.00
    },
    {
      "product_id": 205,
      "quantity": 1,
      "unit_price": 100.00
    }
  ]
}
```

| الحقل | مطلوب | الوصف |
|--------|--------|--------|
| `code` | نعم | كود الكوبون |
| `contact_id` | نعم | `contact_id` / `customer_id` |
| `establishment_id` | نعم | الفرع |
| `taxable_before` | نعم | إجمالي الخاضع للضريبة **قبل** خصم الكوبون |
| `tax_amount` | نعم | قيمة الضريبة **قبل** خصم الكوبون |
| `items` | نعم | أصناف السلة |
| `items[].product_id` | نعم | معرّف المنتج |
| `items[].quantity` | نعم | الكمية |
| `items[].unit_price` | لا | سعر الوحدة قبل VAT |
| `items[].total_before_vat` | لا | إن وُجد يُستخدم بدل `qty × unit_price` |

### Response `200` — كوبون صالح

```json
{
  "valid": true,
  "data": {
    "coupon": {
      "id": 3,
      "code": "RAMADAN10",
      "name": "خصم رمضان",
      "value_type": "percent",
      "value": 10
    },
    "discount_amount": 50.00,
    "taxable_before": 500.00,
    "taxable_after": 450.00,
    "tax_amount": 67.50,
    "final_total": 517.50
  }
}
```

### Response `422` — كوبون غير صالح

```json
{
  "valid": false,
  "message": "انتهت صلاحية الكوبون",
  "code": "coupon_expired"
}
```

### أكواد الأخطاء (`code`)

| code | المعنى |
|------|--------|
| `coupon_not_found` | الكود غير موجود |
| `coupon_not_active` | الكوبون معطّل |
| `coupon_not_started` | لم يبدأ بعد |
| `coupon_expired` | منتهي الصلاحية |
| `coupon_invalid_establishment` | غير متاح لهذا الفرع |
| `coupon_usage_limit_reached` | استُنفد العدد الكلي |
| `coupon_person_limit_reached` | العميل استخدمه الحد الأقصى |
| `coupon_not_applicable_to_items` | لا ينطبق على أصناف السلة |
| `coupon_disabled` | الكوبونات مغلقة من الإعدادات |

---

## 8. POST `/api/stor-sales-invoice` — استخدام الكوبون في الفاتورة

Endpoint موجود مسبقاً — أُضيف دعم **`coupon_code`**.

### حقل جديد

| الحقل | Type | الوصف |
|--------|------|--------|
| `coupon_code` | string | كود الكوبون (اختياري) |

### السلوك

1. يتحقق من تفعيل الكوبونات في الإعدادات
2. يطبّق نفس منطق Dashboard على `items` + المجاميع
3. يحدّث `discount_amount`, `total_after_discount`, `tax_amount`, `final_total`
4. بعد حفظ الفاتورة (إذا `status != draft`) يسجّل الاستخدام في `sales_coupons_clients`

### مثال (حقول الكوبون فقط)

```json
{
  "coupon_code": "RAMADAN10",
  "customer_id": 15,
  "establishment_id": 2,
  "items": [ "..." ],
  "total_before_discount": 500,
  "total_after_discount": 500,
  "total_tax": 75,
  "total_paid": 575,
  "status": "final"
}
```

### Response خطأ كوبون `422`

```json
{
  "message": "الكوبون لا ينطبق على الأصناف المختارة",
  "code": "coupon_not_applicable_to_items"
}
```

---

## 9. مثال Dart — معاينة كوبون

```dart
Future<CouponPreview?> previewCoupon({
  required String token,
  required String baseUrl,
  required String code,
  required int contactId,
  required int establishmentId,
  required double taxableBefore,
  required double taxAmount,
  required List<CartItem> items,
}) async {
  final uri = Uri.parse('$baseUrl/api/v1/coupons/validate');
  final response = await http.post(
    uri,
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({
      'code': code,
      'contact_id': contactId,
      'establishment_id': establishmentId,
      'taxable_before': taxableBefore,
      'tax_amount': taxAmount,
      'items': items.map((e) => {
        'product_id': e.productId,
        'quantity': e.quantity,
        'unit_price': e.unitPrice,
        'total_before_vat': e.totalBeforeVat,
      }).toList(),
    }),
  );

  final body = jsonDecode(response.body) as Map<String, dynamic>;
  if (response.statusCode == 200 && body['valid'] == true) {
    return CouponPreview.fromJson(body['data'] as Map<String, dynamic>);
  }
  throw CouponException(
    code: body['code'] as String? ?? 'coupon_invalid',
    message: body['message'] as String? ?? 'Invalid coupon',
  );
}
```

---

## 10. مثال Dart — قائمة كوبونات متاحة

```dart
Future<List<Coupon>> fetchAvailableCoupons({
  required String token,
  required String baseUrl,
  required int establishmentId,
}) async {
  final uri = Uri.parse('$baseUrl/api/v1/coupons').replace(queryParameters: {
    'available_only': '1',
    'establishment_id': '$establishmentId',
    'per_page': '50',
  });

  final response = await http.get(uri, headers: {
    'Authorization': 'Bearer $token',
    'Accept': 'application/json',
  });

  if (response.statusCode != 200) throw Exception('Failed to load coupons');
  final json = jsonDecode(response.body) as Map<String, dynamic>;
  final list = json['data'] as List<dynamic>;
  return list.map((e) => Coupon.fromJson(e as Map<String, dynamic>)).toList();
}
```

---

## 11. ملاحظات للتطبيق

1. **`taxable_before` و `tax_amount`** يجب أن يعكسا السلة **قبل** خصم الكوبون (بعد خصومات الأصناف إن وُجدت).
2. للكوبونات من نوع `product` أو `category`، أرسل كل `product_id` في `items`.
3. استدعِ `validate` عند تغيير السلة أو الكود — لا تعتمد على نتيجة قديمة.
4. عند `POST stor-sales-invoice` أرسل **نفس** `coupon_code` — السيرفر يعيد الحساب داخل transaction.
5. `coupon_count = 0` و `person_use_time_count = 0` يعنيان **بدون حد**.

---

## 12. الملفات في Backend

| الملف | الدور |
|--------|--------|
| `Modules/Sales/routes/api.php` | Routes |
| `Modules/Sales/Http/Controllers/Api/CouponApiController.php` | Controller |
| `Modules/Sales/Services/CouponQueryService.php` | فلترة واستعراض |
| `Modules/Sales/Services/ApplyCouponService.php` | التحقق + الحساب + تسجيل الاستخدام |
| `Modules/Sales/Http/Controllers/SellApiController.php` | دعم `coupon_code` في POS |

---

*آخر تحديث: مايو 2026*

# My Bee Waiter — فلتر `assigned_waiter_id` (تغيير الحالة من الكاشير)

> **الغرض:** ملف واحد لمطوّر Flutter **النادل** فقط. مرّره لـ Cursor.  
> **التاريخ:** 2026-08-18  
> **المشكلة:** تغيير حالة الطلب من الكاشير يصل بالسوكت لكن شاشة الويتر لا تتحدث.

الباكند يبث الحدث. الخلل في **فلتر النادل على العميل**.

مرجع السوكت الكامل: `docs/flutter-waiter-socket-ar.md`

---

## 1. السبب

`assigned_waiter_id` للعرض (بطاقاتي / نادل الطاولة) — **ليس لتجاهل أحداث السوكت**.

| ماذا يفعل الكاشير | ماذا يحدث في الـ payload | ماذا يفعل الفلتر الخطأ |
|-------------------|--------------------------|-------------------------|
| `prepared` على طلب أنشأه الكاشير | `assigned_waiter_id` = آيدي الكاشير | `!= employeeId` → **يسقط الحدث** |
| `served` / `canceled` / `completed` | السيرفر يصفّر الحقل في DB | `== null` → **يسقط الحدث** |

النتيجة: الحالة تتغير في السيرفر والكاشير، والويتر يبقى على الحالة القديمة.

---

## 2. القاعدة (إلزامي)

| الحدث | فلتر مسموح | فلتر ممنوع |
|--------|------------|------------|
| `order:status_changed` | `table_id` فقط | `assigned_waiter_id` |
| `order:finished` | `table_id` فقط | `assigned_waiter_id` |
| `order:updated` | `table_id` فقط | `assigned_waiter_id` |
| `order:created` | لا تسقط | `assigned_waiter_id` |
| `table:updated` | طبّق إذا الطاولة **موجودة محلياً** أو `id` معروف | لا تسقط لأن `assigned_waiter_id` null أو ≠ أنا |

بعد تطبيق الحالة: يمكنك إخفاء البطاقة من «طاولاتي» إذا صار `assigned_waiter_id != employeeId`.  
الترتيب: **حدّث أولاً، فلتر العرض ثانياً.**

---

## 3. الأحداث (استمع للاثنين)

يُرسلان من الكاشير / النادل / المطبخ. نفس الحقول تقريباً.

```json
{
  "event_id": "uuid",
  "schema_version": 1,
  "event": "order:status_changed",
  "timestamp": "2026-08-18T15:00:00.000000Z",
  "table_id": 12,
  "order_id": 501,
  "status": "draft",
  "order_status": "prepared",
  "assigned_waiter_id": 45,
  "establishment_id": 3
}
```

| حدث | متى |
|-----|-----|
| `order:status_changed` | أي تغيير `order_status` (بما فيه من الكاشير) |
| `order:finished` | `served` / `canceled` / `completed` — **بالإضافة** إلى `order:status_changed` |

`assigned_waiter_id` هنا للنادل الذي كانت الطاولة عنده **قبل** التصفير. لا تعتمد عليه لقبول/رفض الحدث.

`status` في هذا الحدث = حالة المعاملة (`draft` / `open`) — **ليس** حالة الطاولة. حالة الطلب هي `order_status`.

---

## 4. كود Dart — استبدل الفلتر

```dart
void applyOrderStatusPayload(Map payload) {
  final eventId = payload['event_id'] as String?;
  if (isDuplicate(eventId)) return;

  final tableId = payload['table_id'];
  final orderId = payload['order_id'];
  final orderStatus = payload['order_status'] as String?;
  if (tableId == null || orderStatus == null) return;

  // ✅ حدّث دائماً حسب الطاولة — بدون فلتر نادل
  tablesBloc.patchOrderStatus(
    tableId: tableId,
    orderId: orderId,
    orderStatus: orderStatus,
  );

  if (openTableId == tableId) {
    ordersBloc.patchOrderStatus(orderStatus);
  }
}

socket.on('order:status_changed', (raw) {
  applyOrderStatusPayload(Map<String, dynamic>.from(raw as Map));
});

socket.on('order:finished', (raw) {
  applyOrderStatusPayload(Map<String, dynamic>.from(raw as Map));
});
```

### `table:updated`

```dart
socket.on('table:updated', (raw) {
  final table = Map<String, dynamic>.from((raw as Map)['data'] as Map);

  // ❌ خطأ
  // if (table['assigned_waiter_id'] != currentEmployeeId) return;

  // ✅ طبّق التعديل على القائمة المحلية
  tablesBloc.applyTablePatch(table);

  // فلتر «طاولاتي» للعرض فقط بعد التحديث
  // visibleTables = allTables.where((t) =>
  //   t.assignedWaiterId == currentEmployeeId || t.id == table['id']);
});
```

قيم `table:updated.data.status`: `available` | `reserved` | `notAvailable`  
قيم `order_status`: `inpreparation` | `prepared` | `served` | `canceled` | `completed` | null

بعد `served` من الكاشير قد يأتي:

- `status`: `available`
- `order_status`: `served`
- `assigned_waiter_id`: آيدي النادل السابق (في السوكت) حتى لا يسقط التحديث
- `previous_assigned_waiter_id`: نفس القيمة إن وُجدت

لا تفرّغ `order_id` المحلي بعد `served` إذا الزبون ما زال على الطاولة (إعادة فتح مخدوم).

---

## 5. ماذا احذف من الكود الحالي

ابحث عن أي من هذه وأنِهِ تجاهل الحدث:

```dart
if (assignedWaiterId != currentEmployeeId) return;
if (assignedWaiterId == null) return;
if (payload['assigned_waiter_id'] != employeeId) return;
```

على أحداث: `order:status_changed` / `order:finished` / `order:updated` / `table:updated`

الإبقاء مسموح **فقط** عند بناء قائمة «طاولاتي» من البيانات بعد التحديث.

---

## 6. قائمة تحقق

- [ ] مستمع لـ `order:status_changed` **و** `order:finished`
- [ ] لا `return` بسبب `assigned_waiter_id` على أحداث الحالة
- [ ] كاشير يضع `prepared` → بطاقة الويتر تتحدث خلال ثانيتين
- [ ] كاشير يضع `served` → البطاقة + التفاصيل تتحدث؛ الطاولة قد تصبح `available`
- [ ] طلب أنشأه الكاشير يظهر تحديث حالته لكل نوادل الفرع
- [ ] فلتر «طاولاتي» ما زال يعمل للعرض بعد التحديث
- [ ] إعادة فتح مخدوم: نفس `order_id` (لا تُنشئ طلباً جديداً محلياً)

---

## 7. الباكند (لا تغيّر شيئاً هنا)

تم إصلاح البث في:

- `Modules/Reservation/Services/RealtimeBroadcastService.php`
- `Modules/Reservation/Http/Controllers/Api/OrderController.php`
- `socket-server/index.js` — غرفة `waiter:{employeeId}`

بعد سحب الكود على السيرفر: `pm2 restart mybee-socket --update-env`

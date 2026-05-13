# MyBee — Flutter developer guide (Socket.io only)

> What matters for a Flutter developer: **receiving** events from the Socket.io server. Broadcast **sending** is done by Laravel over HTTP, not from the mobile app.

## What you get

Real-time updates when table state changes (new order, updates, etc.) without constant polling. The server pushes a **`TableUpdated`** event to clients associated with the same **tenant `tenant_id`**.

## What you need

| Item | Value / note |
|------|----------------|
| Socket base URL | From your environment or ops (legacy doc example: `http://52.203.236.150:3001`). **Do not hard-code**; use app configuration. |
| Protocol | Socket.io (not raw WebSocket). |
| Typical Flutter package | [`socket_io_client`](https://pub.dev/packages/socket_io_client) — **align the package version with the server’s `socket.io` version** with the Node team to avoid protocol mismatches. |

## Event and payload (what you code against)

When you listen for **`TableUpdated`**, the payload you receive (as sent by Laravel today) looks like:

```json
{
  "table_id": 1,
  "table_code": "T10",
  "transaction_ref_no": "INV-001"
}
```

- **`table_id`**: Table primary key in the system.  
- **`table_code`**: Table code for display.  
- **`transaction_ref_no`**: Transaction / invoice reference number.  

Use these fields to refresh your table UI or show a notification, then call your REST API if you need more detail.

## Tenant binding

Broadcasts are scoped by **`tenant_id`**. Your app must use the **same tenant** as your API session (same tenant after login / branch selection).

How the client joins the correct room is defined in the Node server’s **`index.js`** (not in this Laravel repo). Confirm with the socket team whether you should:

- Pass `tenant_id` in the **connection query**, or  
- **`emit`** after connect (e.g. `join` / `subscribe` with `tenant_id`).

Do not invent extra event names until Node confirms them.

## Example: connect and listen (Dart — illustrative)

```dart
import 'package:socket_io_client/socket_io_client.dart' as IO;

void connectTableSocket({
  required String baseUrl, // e.g. http://HOST:3001
  required String tenantId,
  required void Function(dynamic data) onTableUpdated,
}) {
  final socket = IO.io(
    baseUrl,
    IO.OptionBuilder()
        .setTransports(['websocket'])
        // If the server reads tenant from query — confirm with Node:
        // .setQuery({'tenant_id': tenantId})
        .enableReconnection()
        .build(),
  );

  socket.onConnect((_) {
    // If joining is via emit — confirm with Node:
    // socket.emit('joinTenant', {'tenant_id': tenantId});
  });

  socket.on('TableUpdated', (data) => onTableUpdated(data));

  socket.onDisconnect((_) {});
}
```

Adjust `setQuery` / `emit` according to what the **`mybee-socket-server`** maintainer specifies.

## Android networking

If the URL is **`http`** rather than **`https`**, you may need cleartext allowances in Android (`networkSecurityConfig` / `usesCleartextTraffic`) depending on your app policy.

## Quick verification

- After connecting, trigger a real order that changes a table for the same tenant and confirm **`TableUpdated`** arrives.  
- If nothing arrives, the usual causes are **wrong room / tenant mismatch** or **wrong host/port**, not only Flutter.

## Summary

| Your responsibility (Flutter) | Not your responsibility in this flow |
|-------------------------------|----------------------------------------|
| Socket.io connection + tenant/room join per Node spec | `POST .../broadcast` (Laravel) |
| Listen for `TableUpdated` and handle `data` | Running PM2 or Laravel |

---

*Aligned with `mybee-socket-server.txt`. For server-side join semantics, coordinate with whoever owns the Node `index.js`.*

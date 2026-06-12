# My Bee — إصلاح WebSocket للشاشات (بدون كسر المطبخ / الويتر / الكاشيير)

> **للباك إند / DevOps** — يونيو 2026

---

## 1. المشكلة

تطبيق الشاشة يتصل بـ `wss://{tenant}/ws` ويحصل على **Connection refused** بينما REST يعمل.

| البند | الحالة |
|--------|--------|
| REST API | ✅ |
| Socket.IO (مطبخ / ويتر / POS) | ✅ عادةً عبر `/socket.io/` |
| WebSocket الشاشات `/ws` | ❌ غالباً Nginx بدون `location /ws` |

---

## 2. مهم: سيرفر واحد — مساران

**لا تغيّر** إعداد المطبخ والويتر. كلهم على **نفس** `socket-server` (منفذ `3001`):

| التطبيق | المسار | البروتوكول |
|---------|--------|------------|
| مطبخ / ويتر / كاشيير | `/socket.io/` | Socket.IO |
| Screen Player (QR) | `/ws` | WebSocket JSON خام |

في الكود: معالج `/ws` **لا يقطع** طلبات `/socket.io/` (لا `socket.destroy()` على مسارات أخرى).

---

## 3. Nginx — أضف `/ws` فقط

```nginx
# موجود — لا تلمسه
location /socket.io/ {
    proxy_pass http://127.0.0.1:3001;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_read_timeout 86400s;
}

# أضف هذا للشاشات
location /ws {
    proxy_pass http://127.0.0.1:3001;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_read_timeout 86400s;
}
```

```bash
sudo nginx -t && sudo systemctl reload nginx
```

---

## 4. socket-server

```bash
cd socket-server
npm install
npm start
```

```bash
curl -s http://127.0.0.1:3001/health
```

**متوقع:**

```json
{
  "ok": true,
  "socket_io_path": "/socket.io/",
  "screen_pairing_ws_path": "/ws",
  "port": 3001
}
```

---

## 5. Laravel `.env`

```env
SOCKET_BROADCAST_URL=http://127.0.0.1:3001/broadcast
SOCKET_INTERNAL_SECRET=your-secret
SOCKET_PORT=3001
```

---

## 6. اختبار WebSocket الشاشة

```bash
curl -i -N \
  -H "Connection: Upgrade" \
  -H "Upgrade: websocket" \
  -H "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==" \
  -H "Sec-WebSocket-Version: 13" \
  https://test1.my-bee.info/ws
```

| النتيجة | المعنى |
|---------|--------|
| `101 Switching Protocols` | ✅ |
| `404` | أضف `location /ws` |
| `502` | socket-server متوقف |
| `Connection refused` | لا listener على 3001 |

---

## 7. Admin pairing (E2E)

```bash
POST https://test1.my-bee.info/api/admin/v1/screen/auth/token
Authorization: Bearer {admin_token}

{
  "pairing_id": "64_hex_from_qr",
  "device_code": "SCR-001",
  "establishment_id": 2
}
```

→ Laravel يبث `screen.linked` على قناة `screen.pairing.{pairing_id}`.

---

## 8. مراجع

| الملف | الوصف |
|--------|--------|
| [Screen_Pairing_Frontend_API.md](./Screen_Pairing_Frontend_API.md) | مواصفات WS + Admin |
| [Screen_Player_API.md](./Screen_Player_API.md) | Player REST |

---

*My Bee — Screen Module*

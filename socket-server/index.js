/**
 * MyBee Waiter — Socket.IO server (Engine.IO v4)
 * Spec: SOCKET_IO_BACKEND_SPEC.md
 */
import { config as loadEnv } from "dotenv";
import { dirname, resolve } from "path";
import { fileURLToPath } from "url";

const __dirname = dirname(fileURLToPath(import.meta.url));
loadEnv({ path: resolve(__dirname, ".env") });
import { createServer } from "http";
import { createHash, randomUUID } from "crypto";
import cors from "cors";
import express from "express";
import { Server } from "socket.io";
import { WebSocketServer } from "ws";

const PORT = Number(process.env.SOCKET_PORT || 3001);
const SOCKET_INTERNAL_SECRET = process.env.SOCKET_INTERNAL_SECRET || "";
const LARAVEL_VERIFY_URL = process.env.LARAVEL_VERIFY_URL || process.env.APP_URL || "http://localhost";
const LARAVEL_INTERNAL_URL = process.env.LARAVEL_INTERNAL_URL || LARAVEL_VERIFY_URL;
const TENANT_URL_TEMPLATE =
  process.env.TENANT_URL_TEMPLATE || "http://{tenant}.localhost";
const SCHEMA_VERSION = 1;
const RATE_LIMIT_PER_MINUTE = Number(process.env.SOCKET_RATE_LIMIT_PER_MINUTE || 120);
const VERIFY_TOKEN_CACHE_MS = Number(process.env.SOCKET_VERIFY_CACHE_MS || 86_400_000);
const SCREEN_PAIRING_CHANNEL_PREFIX = "screen.pairing.";
const SCREEN_DEVICE_CHANNEL_PREFIX = "screen.device.";

/** @type {Map<string, Set<import('ws').WebSocket>>} */
const screenPairingRooms = new Map();

/** @type {Map<string, Set<import('ws').WebSocket>>} */
const screenDeviceRooms = new Map();

function isValidScreenPairingId(id) {
  return typeof id === "string" && /^[a-f0-9]{64}$/.test(id);
}

function isValidScreenDeviceId(id) {
  return typeof id === "string" && /^[1-9]\d*$/.test(id);
}

function joinScreenRoom(map, ws, room) {
  if (!map.has(room)) {
    map.set(room, new Set());
  }
  map.get(room).add(ws);
}

function leaveScreenRoom(map, ws, room) {
  const set = map.get(room);
  if (!set) return;
  set.delete(ws);
  if (set.size === 0) map.delete(room);
}

function broadcastScreenLinked(pairingId, payload) {
  const room = `${SCREEN_PAIRING_CHANNEL_PREFIX}${pairingId}`;
  const clients = screenPairingRooms.get(room);
  const outbound = {
    event: "screen.linked",
    channel: room,
    ...payload,
    id: pairingId,
  };
  const raw = JSON.stringify(outbound);
  let sent = 0;
  if (clients) {
    for (const ws of clients) {
      if (ws.readyState === 1) {
        ws.send(raw);
        sent += 1;
      }
    }
  }
  return { room, sent };
}

function broadcastScreenDeviceEvent(deviceId, payload) {
  const room = `${SCREEN_DEVICE_CHANNEL_PREFIX}${deviceId}`;
  const clients = screenDeviceRooms.get(room);
  const outbound = {
    channel: room,
    device_id: Number(deviceId),
    ...payload,
  };
  const raw = JSON.stringify(outbound);
  let sent = 0;
  if (clients) {
    for (const ws of clients) {
      if (ws.readyState === 1) {
        ws.send(raw);
        sent += 1;
      }
    }
  }
  return { room, sent };
}

const app = express();
app.use(cors({ origin: true }));
app.use(express.json({ limit: "2mb" }));

const httpServer = createServer(app);

const io = new Server(httpServer, {
  path: "/socket.io/",
  cors: { origin: true, credentials: true },
  transports: ["websocket", "polling"],
});

/** @type {Map<string, { count: number, resetAt: number }>} */
const connectionRate = new Map();

/** @type {Map<string, number>} tokenHash → validUntil ms (نجاح التحقق فقط) */
const verifyTokenCache = new Map();

function verifyCacheKey(token) {
  return createHash("sha256").update(token).digest("hex");
}

function isVerifyCacheValid(token) {
  const cleanToken = token.replace(/^Bearer\s+/i, "");
  const cachedUntil = verifyTokenCache.get(verifyCacheKey(cleanToken));
  return Boolean(cachedUntil && cachedUntil > Date.now());
}

function tenantBaseUrl(tenantId) {
  return TENANT_URL_TEMPLATE.replace("{tenant}", tenantId);
}

function extractTenantId(host, authTenantId) {
  if (authTenantId) return String(authTenantId);
  if (!host) return null;
  const hostname = host.split(":")[0];
  const parts = hostname.split(".");
  if (parts.length >= 3 && parts[0] !== "www") {
    return parts[0];
  }
  return null;
}

async function verifyBearerToken(token, tenantId) {
  if (!token) return false;
  const cleanToken = token.replace(/^Bearer\s+/i, "");

  const cacheKey = verifyCacheKey(cleanToken);
  const cachedUntil = verifyTokenCache.get(cacheKey);
  if (cachedUntil && cachedUntil > Date.now()) {
    return true;
  }

  const authHeaders = {
    Authorization: `Bearer ${cleanToken}`,
    Accept: "application/json",
  };

  let valid = false;

  // 1) Central — company-login token (Sanctum على DB المركزي)
  if (LARAVEL_VERIFY_URL) {
    const centralUrl = `${LARAVEL_VERIFY_URL.replace(/\/$/, "")}/api/verify-token`;
    try {
      const res = await fetch(centralUrl, {
        headers: authHeaders,
        signal: AbortSignal.timeout(5000),
      });
      if (res.ok) valid = true;
    } catch (err) {
      console.error("[socket] central verify error:", err.message);
    }
  }

  // 2) Tenant domain — verify-socket-token
  if (!valid && tenantId) {
    const tenantUrl = `${tenantBaseUrl(tenantId).replace(/\/$/, "")}/api/verify-socket-token`;
    try {
      const res = await fetch(tenantUrl, {
        headers: authHeaders,
        signal: AbortSignal.timeout(5000),
      });
      if (res.ok) valid = true;
    } catch (err) {
      console.error("[socket] tenant verify error:", err.message);
    }
  }

  // 3) Internal — عبر دومين tenant (Apache → Laravel، X-Socket-Secret)
  if (!valid && tenantId && SOCKET_INTERNAL_SECRET) {
    const base = tenantBaseUrl(tenantId).replace(/\/$/, "");
    try {
      const res = await fetch(`${base}/api/internal/realtime/verify-token`, {
        headers: {
          ...authHeaders,
          "X-Socket-Secret": SOCKET_INTERNAL_SECRET,
          "X-Tenant-Id": tenantId,
        },
        signal: AbortSignal.timeout(5000),
      });
      if (res.ok) valid = true;
    } catch (err) {
      console.error("[socket] internal verify error:", err.message);
    }
  }

  if (valid) {
    verifyTokenCache.set(cacheKey, Date.now() + VERIFY_TOKEN_CACHE_MS);
  }

  return valid;
}

function checkRateLimit(key) {
  const now = Date.now();
  const entry = connectionRate.get(key) || { count: 0, resetAt: now + 60_000 };
  if (now > entry.resetAt) {
    entry.count = 0;
    entry.resetAt = now + 60_000;
  }
  entry.count += 1;
  connectionRate.set(key, entry);
  return entry.count <= RATE_LIMIT_PER_MINUTE;
}

function wrapLegacyTableUpdated(data) {
  return {
    event_id: randomUUID(),
    schema_version: SCHEMA_VERSION,
    event: "table:updated",
    timestamp: new Date().toISOString(),
    data,
  };
}

function resolveRooms(tenantId, establishmentId, tableId, waiterId) {
  const rooms = [`tenant:${tenantId}`];
  if (establishmentId) rooms.push(`establishment:${establishmentId}`);
  if (tableId) rooms.push(`table:${tableId}`);
  if (waiterId) rooms.push(`waiter:${waiterId}`);
  return rooms;
}

function resolveKitchenRooms(tenantId, establishmentId, categoryIds = []) {
  const rooms = [`tenant:${tenantId}`];
  if (!categoryIds.length) {
    rooms.push(`kitchen:establishment:${establishmentId}`);
  }
  for (const c of categoryIds) {
    rooms.push(`kitchen:establishment:${establishmentId}:category:${c}`);
  }
  return rooms;
}

function categoryIdsFromOrder(order) {
  if (!order?.items?.length) return [];
  const ids = new Set();
  for (const item of order.items) {
    const categoryId = Number(item.category_id);
    if (categoryId) ids.add(categoryId);
  }
  return [...ids];
}

function normalizeCategoryIds(raw) {
  if (raw == null) return [];
  if (Array.isArray(raw)) return raw.map(Number).filter((n) => n > 0);
  if (typeof raw === "number" && raw > 0) return [raw];
  if (typeof raw === "string" && raw.trim()) {
    return raw.split(/[,;\s]+/).map(Number).filter((n) => n > 0);
  }
  return [];
}

function leaveKitchenRooms(socket, establishmentId, categoryIds = []) {
  if (!establishmentId) return;
  socket.leave(`kitchen:establishment:${establishmentId}`);
  for (const c of categoryIds) {
    socket.leave(`kitchen:establishment:${establishmentId}:category:${c}`);
  }
  const multiRoom = socket.data.kitchenMultiCategoryRoom;
  if (multiRoom) {
    socket.leave(multiRoom);
    socket.data.kitchenMultiCategoryRoom = null;
  }
}

function filterOrderItems(order, allowedCategoryIds) {
  if (!order) return null;
  if (!allowedCategoryIds?.length) return order;
  const allowed = new Set(allowedCategoryIds.map(Number).filter(Boolean));
  const items = (order.items || []).filter((item) => allowed.has(Number(item.category_id)));
  if (!items.length) return null;
  return { ...order, items };
}

function filterOrdersForCategories(orders, categoryIds) {
  if (!Array.isArray(orders)) return [];
  if (!categoryIds?.length) return orders;
  return orders
    .map((order) => filterOrderItems(order, categoryIds))
    .filter(Boolean);
}

function broadcastKitchenEvent(establishmentId, eventName, envelope, options = {}) {
  const estRoom = `kitchen:establishment:${establishmentId}`;
  const order = envelope.order ?? null;
  const explicitCategories = normalizeCategoryIds(options.category_ids);
  const itemCategories = explicitCategories.length
    ? explicitCategories
    : categoryIdsFromOrder(order);

  // Full order only for clients in the establishment-wide kitchen room (no category filter).
  io.to(estRoom).emit(eventName, envelope);

  for (const catId of itemCategories) {
    const catRoom = `kitchen:establishment:${establishmentId}:category:${catId}`;
    if (order) {
      const filteredOrder = filterOrderItems(order, [catId]);
      if (!filteredOrder) continue;
      io.to(catRoom).emit(eventName, { ...envelope, order: filteredOrder });
    } else {
      io.to(catRoom).emit(eventName, envelope);
    }
  }

  broadcastToEstablishmentCategoryFilterRooms(establishmentId, eventName, envelope, order);

  return {
    establishment: estRoom,
    categories: itemCategories,
  };
}

function broadcastToEstablishmentCategoryFilterRooms(establishmentId, eventName, envelope, order) {
  const prefix = `kitchen:establishment:${establishmentId}:categories:`;
  const rooms = io.sockets.adapter?.rooms;
  if (!rooms) return;

  for (const room of rooms.keys()) {
    if (!room.startsWith(prefix)) continue;
    const roomCats = normalizeCategoryIds(room.slice(prefix.length));
    if (!roomCats.length) continue;
    if (order) {
      const filteredOrder = filterOrderItems(order, roomCats);
      if (!filteredOrder) continue;
      io.to(room).emit(eventName, { ...envelope, order: filteredOrder });
    } else {
      io.to(room).emit(eventName, envelope);
    }
  }
}

/** My Bee POS — طلبات الطاولات (establishment-orders) */
function resolvePosOrderRooms(_tenantId, establishmentId) {
  return [`establishment:${establishmentId}`];
}

function parseEstablishmentId(socket, body = {}) {
  const auth = socket.handshake.auth || {};
  const headerEst =
    socket.handshake.headers["establishment-id"] ||
    socket.handshake.headers["establishment_id"];
  return Number(
    body.establishment_id ??
      auth.establishment_id ??
      headerEst ??
      socket.data.establishmentId
  );
}

function emitToRooms(eventName, payload, rooms) {
  for (const room of rooms) {
    io.to(room).emit(eventName, payload);
  }
}

async function fetchTablesSnapshot(tenantId, establishmentId) {
  const base = LARAVEL_INTERNAL_URL.replace(/\/$/, "");
  const qs = establishmentId ? `?establishment_id=${establishmentId}` : "";
  const url = `${base}/api/internal/realtime/tables-snapshot${qs}`;
  const res = await fetch(url, {
    headers: {
      Accept: "application/json",
      "X-Socket-Secret": SOCKET_INTERNAL_SECRET,
      "X-Tenant-Id": tenantId,
    },
    signal: AbortSignal.timeout(8000),
  });
  if (!res.ok) throw new Error(`snapshot HTTP ${res.status}`);
  const json = await res.json();
  return json.data ?? json;
}

async function fetchKitchenOrders(tenantId, establishmentId, categoryIds = []) {
  const base = LARAVEL_INTERNAL_URL.replace(/\/$/, "");
  const params = new URLSearchParams();
  params.set("establishment_id", String(establishmentId));
  for (const c of categoryIds) {
    params.append("category_ids[]", String(c));
  }
  const url = `${base}/api/internal/realtime/kitchen-orders?${params.toString()}`;
  const res = await fetch(url, {
    headers: {
      Accept: "application/json",
      "X-Socket-Secret": SOCKET_INTERNAL_SECRET,
      "X-Tenant-Id": tenantId,
    },
    signal: AbortSignal.timeout(10000),
  });
  if (!res.ok) throw new Error(`kitchen orders HTTP ${res.status}`);
  const json = await res.json();
  return json.data ?? json.orders ?? json;
}

async function fetchEstablishmentOrders(tenantId, establishmentId) {
  const base = LARAVEL_INTERNAL_URL.replace(/\/$/, "");
  const url = `${base}/api/internal/realtime/establishment-orders/${establishmentId}`;
  const res = await fetch(url, {
    headers: {
      Accept: "application/json",
      "X-Socket-Secret": SOCKET_INTERNAL_SECRET,
      "X-Tenant-Id": tenantId,
    },
    signal: AbortSignal.timeout(10000),
  });
  if (!res.ok) throw new Error(`establishment orders HTTP ${res.status}`);
  const json = await res.json();
  return json.data ?? json;
}

async function fetchTableOrderDetails(tenantId, tableId) {
  const base = LARAVEL_INTERNAL_URL.replace(/\/$/, "");
  const url = `${base}/api/internal/realtime/tables/${tableId}/order`;
  const res = await fetch(url, {
    headers: {
      Accept: "application/json",
      "X-Socket-Secret": SOCKET_INTERNAL_SECRET,
      "X-Tenant-Id": tenantId,
    },
    signal: AbortSignal.timeout(8000),
  });
  if (!res.ok) throw new Error(`order details HTTP ${res.status}`);
  return res.json();
}

// --- HTTP: health & Laravel broadcast ---

app.get("/health", (_req, res) => {
  res.json({
    ok: true,
    schema_version: SCHEMA_VERSION,
    socket_io_path: "/socket.io/",
    screen_pairing_ws_path: "/ws",
    screen_device_channel_prefix: SCREEN_DEVICE_CHANNEL_PREFIX,
    port: PORT,
  });
});

app.post("/broadcast", (req, res) => {
  if (SOCKET_INTERNAL_SECRET && req.headers["x-socket-secret"] !== SOCKET_INTERNAL_SECRET) {
    return res.status(401).json({ message: "UNAUTHORIZED" });
  }

  const body = req.body || {};
  const tenantId = body.tenant_id;
  const eventName = body.event;
  const payload = body.payload ?? body.data ?? {};
  const establishmentId = body.establishment_id ?? payload.establishment_id;
  const tableId = body.table_id ?? payload.table_id ?? payload.data?.id;
  const waiterId = Number(
    body.assigned_waiter_id ?? body.waiter_id ?? payload.assigned_waiter_id ?? 0
  ) || null;

  if (!tenantId || !eventName) {
    return res.status(422).json({ message: "tenant_id and event are required" });
  }

  if (body.screen_pairing === true || eventName === "screen.linked") {
    const pairingId = String(body.pairing_id || payload.id || "").toLowerCase();
    if (!isValidScreenPairingId(pairingId)) {
      return res.status(422).json({ message: "pairing_id required (64 hex)" });
    }
    const result = broadcastScreenLinked(pairingId, payload);
    return res.json({ ok: true, screen_pairing: true, ...result });
  }

  if (body.screen_device === true) {
    const deviceId = String(body.device_id ?? payload.device_id ?? "").trim();
    if (!isValidScreenDeviceId(deviceId)) {
      return res.status(422).json({ message: "device_id required (positive integer)" });
    }
    const result = broadcastScreenDeviceEvent(deviceId, {
      event: eventName,
      tenant_id: tenantId,
      ...payload,
    });
    return res.json({ ok: true, screen_device: true, ...result });
  }

  const isKitchen = body.kitchen === true || String(eventName).startsWith("kitchen:");
  const isPosOrders =
    body.pos_orders === true || String(eventName).startsWith("establishment_order.");

  if (isPosOrders) {
    const estId = Number(establishmentId ?? payload.establishment_id);
    if (!estId) {
      return res.status(422).json({ message: "establishment_id required for POS order events" });
    }
    const envelope = payload.event_id
      ? payload
      : {
          event_id: randomUUID(),
          schema_version: SCHEMA_VERSION,
          ...payload,
          event: payload.event || eventName,
          timestamp: payload.timestamp || new Date().toISOString(),
        };
    const rooms = resolvePosOrderRooms(tenantId, estId);
    emitToRooms(eventName, envelope, rooms);
    return res.json({ ok: true, rooms, pos_orders: true });
  }

  if (isKitchen) {
    const estId = Number(establishmentId ?? payload.establishment_id);
    if (!estId) {
      return res.status(422).json({ message: "establishment_id required for kitchen events" });
    }
    const envelope = payload.event_id
      ? payload
      : {
          event_id: randomUUID(),
          schema_version: SCHEMA_VERSION,
          ...payload,
          event: payload.event || eventName,
          timestamp: payload.timestamp || new Date().toISOString(),
        };
    const rooms = broadcastKitchenEvent(estId, eventName, envelope, {
      category_ids: body.category_ids || categoryIdsFromOrder(envelope.order),
    });
    return res.json({ ok: true, rooms, kitchen: true });
  }

  // Waiter / table events — never kitchen category-filter these.
  const rooms = resolveRooms(tenantId, establishmentId, tableId, waiterId);

  // Legacy React event
  if (eventName === "TableUpdated" || eventName === "table-reserved") {
    const legacy = payload.data ?? payload;
    emitToRooms("TableUpdated", legacy, [`tenant:${tenantId}`]);
    emitToRooms("table:updated", wrapLegacyTableUpdated(legacy), rooms);
    return res.json({ ok: true, legacy: true });
  }

  const envelope = payload.event_id
    ? payload
    : {
        event_id: randomUUID(),
        schema_version: SCHEMA_VERSION,
        ...payload,
        event: payload.event || eventName,
        timestamp: payload.timestamp || new Date().toISOString(),
      };

  emitToRooms(eventName, envelope, rooms);

  return res.json({ ok: true, rooms });
});

// --- Screen Player — raw WebSocket /ws (QR pairing, no auth) ---
// Same HTTP server + port as Socket.IO (kitchen / waiter / POS). Only handle /ws here;
// do NOT destroy other upgrade requests — Socket.IO owns /socket.io/.

const screenWss = new WebSocketServer({ noServer: true });

httpServer.on("upgrade", (request, socket, head) => {
  const pathname = new URL(request.url || "/", `http://${request.headers.host}`).pathname;
  if (pathname !== "/ws" && pathname !== "/ws/") {
    return;
  }
  screenWss.handleUpgrade(request, socket, head, (ws) => {
    screenWss.emit("connection", ws, request);
  });
});

screenWss.on("connection", (ws) => {
  let joinedRoom = null;
  let joinedRoomMap = null;

  ws.on("message", (raw) => {
    try {
      const msg = JSON.parse(String(raw));
      if (msg.event !== "subscribe") return;

      const channel = String(msg.channel || "");

      if (channel.startsWith(SCREEN_PAIRING_CHANNEL_PREFIX)) {
        const pairingId = String(msg.id || channel.slice(SCREEN_PAIRING_CHANNEL_PREFIX.length)).toLowerCase();
        if (!isValidScreenPairingId(pairingId)) return;

        const room = `${SCREEN_PAIRING_CHANNEL_PREFIX}${pairingId}`;
        if (joinedRoom && joinedRoomMap) leaveScreenRoom(joinedRoomMap, ws, joinedRoom);
        joinScreenRoom(screenPairingRooms, ws, room);
        joinedRoom = room;
        joinedRoomMap = screenPairingRooms;

        ws.send(
          JSON.stringify({
            event: "subscribed",
            channel: room,
            id: pairingId,
          })
        );
        return;
      }

      if (channel.startsWith(SCREEN_DEVICE_CHANNEL_PREFIX)) {
        const deviceId = String(msg.id || channel.slice(SCREEN_DEVICE_CHANNEL_PREFIX.length)).trim();
        if (!isValidScreenDeviceId(deviceId)) return;

        const room = `${SCREEN_DEVICE_CHANNEL_PREFIX}${deviceId}`;
        if (joinedRoom && joinedRoomMap) leaveScreenRoom(joinedRoomMap, ws, joinedRoom);
        joinScreenRoom(screenDeviceRooms, ws, room);
        joinedRoom = room;
        joinedRoomMap = screenDeviceRooms;

        ws.send(
          JSON.stringify({
            event: "subscribed",
            channel: room,
            id: deviceId,
          })
        );
      }
    } catch {
      // ignore malformed frames
    }
  });

  ws.on("close", () => {
    if (joinedRoom && joinedRoomMap) leaveScreenRoom(joinedRoomMap, ws, joinedRoom);
  });
});

// --- Socket.IO auth ---

io.use(async (socket, next) => {
  const auth = socket.handshake.auth || {};
  const token = (auth.token || "").replace(/^Bearer\s+/i, "");
  const tenantId = extractTenantId(socket.handshake.headers.host, auth.tenant_id);

  if (!token) {
    return next(new Error("UNAUTHORIZED"));
  }
  if (!tenantId) {
    return next(new Error("TENANT_REQUIRED"));
  }

  const clientType = String(auth.client_type || auth.app_type || "default").toLowerCase();
  const tokenCacheValid = isVerifyCacheValid(token);
  const rateKey = `${tenantId}:${verifyCacheKey(token)}`;

  if (!tokenCacheValid && !checkRateLimit(rateKey)) {
    return next(new Error("RATE_LIMITED"));
  }

  const valid = tokenCacheValid || (await verifyBearerToken(token, tenantId));
  if (!valid) {
    return next(new Error("INVALID_TOKEN"));
  }

  socket.data.tenantId = tenantId;
  socket.data.clientType = clientType;
  socket.data.employeeId = auth.employee_id ? Number(auth.employee_id) : null;
  socket.data.timecardId = auth.timecard_id ? Number(auth.timecard_id) : null;
  const headerEst =
    socket.handshake.headers["establishment-id"] ||
    socket.handshake.headers["establishment_id"];
  socket.data.establishmentId = auth.establishment_id
    ? Number(auth.establishment_id)
    : headerEst
      ? Number(headerEst)
      : null;
  socket.data.deviceId = auth.device_id ? Number(auth.device_id) : null;

  next();
});

io.on("connection", (socket) => {
  const { tenantId, establishmentId, employeeId } = socket.data;

  socket.join(`tenant:${tenantId}`);
  if (establishmentId) {
    socket.join(`establishment:${establishmentId}`);
  }
  if (employeeId) {
    socket.join(`waiter:${employeeId}`);
  }

  socket.emit("connected", {
    schema_version: SCHEMA_VERSION,
    tenant_id: tenantId,
    establishment_id: establishmentId,
    server_time: new Date().toISOString(),
  });

  socket.on("join:table", async (body, ack) => {
    const tableId = Number(body?.table_id);
    if (!tableId) {
      ack?.({ ok: false, message: "table_id required" });
      return;
    }
    socket.join(`table:${tableId}`);
    try {
      const details = await fetchTableOrderDetails(tenantId, tableId);
      const envelope = {
        event_id: randomUUID(),
        schema_version: SCHEMA_VERSION,
        event: "order:updated",
        timestamp: new Date().toISOString(),
        table_id: tableId,
        data: details,
      };
      socket.emit("order:updated", envelope);
      ack?.({ ok: true, joined: `table:${tableId}` });
    } catch (e) {
      ack?.({ ok: true, joined: `table:${tableId}`, snapshot: false, error: e.message });
    }
  });

  socket.on("leave:table", (body, ack) => {
    const tableId = Number(body?.table_id);
    if (tableId) socket.leave(`table:${tableId}`);
    ack?.({ ok: true });
  });

  socket.on("sync:tables", async (_body, ack) => {
    try {
      const tables = await fetchTablesSnapshot(tenantId, socket.data.establishmentId);
      const envelope = {
        event_id: randomUUID(),
        schema_version: SCHEMA_VERSION,
        event: "tables:snapshot",
        timestamp: new Date().toISOString(),
        data: tables,
      };
      socket.emit("tables:snapshot", envelope);
      ack?.({ ok: true, count: Array.isArray(tables) ? tables.length : 0 });
    } catch (e) {
      ack?.({ ok: false, message: e.message });
    }
  });

  socket.on("ping", (_body, ack) => {
    const pong = {
      event: "pong",
      server_time: new Date().toISOString(),
    };
    socket.emit("pong", pong);
    ack?.(pong);
  });

  socket.on("kitchen:join", async (body, ack) => {
    const estId = parseEstablishmentId(socket, body);
    if (!estId) {
      ack?.({ ok: false, code: "ESTABLISHMENT_REQUIRED", message: "establishment_id required" });
      return;
    }
    const categoryIds = normalizeCategoryIds(body?.category_ids);

    const prevEst = socket.data.kitchenEstablishmentId;
    const prevCats = socket.data.kitchenCategoryIds || [];
    if (prevEst) {
      leaveKitchenRooms(socket, prevEst, prevCats);
    }

    socket.data.kitchenEstablishmentId = estId;
    socket.data.kitchenCategoryIds = categoryIds;

    if (categoryIds.length === 0) {
      socket.join(`kitchen:establishment:${estId}`);
    } else {
      // Must not stay in the establishment-wide room — it receives unfiltered orders.
      socket.leave(`kitchen:establishment:${estId}`);
      if (categoryIds.length === 1) {
        socket.join(`kitchen:establishment:${estId}:category:${categoryIds[0]}`);
      } else {
        const multiKey = [...categoryIds].sort((a, b) => a - b).join(",");
        const multiRoom = `kitchen:establishment:${estId}:categories:${multiKey}`;
        socket.join(multiRoom);
        socket.data.kitchenMultiCategoryRoom = multiRoom;
      }
    }
    try {
      const ordersRaw = await fetchKitchenOrders(tenantId, estId, categoryIds);
      const orders = filterOrdersForCategories(ordersRaw, categoryIds);
      socket.emit("kitchen:sync", {
        event_id: randomUUID(),
        schema_version: SCHEMA_VERSION,
        event: "kitchen:sync",
        timestamp: new Date().toISOString(),
        establishment_id: estId,
        category_ids: categoryIds,
        orders,
      });
      ack?.({
        ok: true,
        room:
          categoryIds.length === 0
            ? `kitchen:establishment:${estId}`
            : categoryIds.length === 1
              ? `kitchen:establishment:${estId}:category:${categoryIds[0]}`
              : socket.data.kitchenMultiCategoryRoom,
        category_ids: categoryIds,
        count: orders.length,
      });
    } catch (e) {
      ack?.({
        ok: true,
        room: `kitchen:establishment:${estId}`,
        category_ids: categoryIds,
        sync: false,
        error: e.message,
      });
    }
  });

  socket.on("kitchen:leave", (body, ack) => {
    const estId = parseEstablishmentId(socket, body) || socket.data.kitchenEstablishmentId;
    const categoryIds = socket.data.kitchenCategoryIds || [];
    if (estId) {
      leaveKitchenRooms(socket, estId, categoryIds);
    }
    socket.data.kitchenCategoryIds = [];
    socket.data.kitchenEstablishmentId = null;
    ack?.({ ok: true });
  });

  socket.on("join_establishment", async (body, ack) => {
    const estId = parseEstablishmentId(socket, body);
    if (!estId) {
      ack?.({ ok: false, code: "ESTABLISHMENT_REQUIRED" });
      return;
    }
    socket.join(`establishment:${estId}`);
    socket.data.posEstablishmentId = estId;
    try {
      const orders = await fetchEstablishmentOrders(tenantId, estId);
      socket.emit("establishment_orders.sync", {
        event_id: randomUUID(),
        schema_version: SCHEMA_VERSION,
        event: "establishment_orders.sync",
        timestamp: new Date().toISOString(),
        establishment_id: estId,
        orders,
      });
      ack?.({ ok: true, room: `establishment:${estId}`, count: orders.length });
    } catch (e) {
      ack?.({ ok: true, room: `establishment:${estId}`, sync: false, error: e.message });
    }
  });

  socket.on("disconnect", () => {
    // rooms cleaned automatically
  });
});

httpServer.listen(PORT, () => {
  console.log(
    `MyBee Socket.IO listening on :${PORT} (schema v${SCHEMA_VERSION}, waiter + kitchen + POS orders + screen /ws pairing)`
  );
});

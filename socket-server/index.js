/**
 * MyBee Waiter — Socket.IO server (Engine.IO v4)
 * Spec: SOCKET_IO_BACKEND_SPEC.md
 */
import "dotenv/config";
import { createServer } from "http";
import { randomUUID } from "crypto";
import cors from "cors";
import express from "express";
import { Server } from "socket.io";

const PORT = Number(process.env.SOCKET_PORT || 3001);
const SOCKET_INTERNAL_SECRET = process.env.SOCKET_INTERNAL_SECRET || "";
const LARAVEL_VERIFY_URL = process.env.LARAVEL_VERIFY_URL || process.env.APP_URL || "http://localhost";
const LARAVEL_INTERNAL_URL = process.env.LARAVEL_INTERNAL_URL || LARAVEL_VERIFY_URL;
const TENANT_URL_TEMPLATE =
  process.env.TENANT_URL_TEMPLATE || "http://{tenant}.localhost";
const SCHEMA_VERSION = 1;
const RATE_LIMIT_PER_MINUTE = Number(process.env.SOCKET_RATE_LIMIT_PER_MINUTE || 30);

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
  const base = tenantId ? tenantBaseUrl(tenantId) : LARAVEL_VERIFY_URL;
  const url = `${base.replace(/\/$/, "")}/api/verify-token`;
  try {
    const res = await fetch(url, {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      },
      signal: AbortSignal.timeout(5000),
    });
    return res.ok;
  } catch {
    return false;
  }
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

function resolveRooms(tenantId, establishmentId, tableId) {
  const rooms = [`tenant:${tenantId}`];
  if (establishmentId) rooms.push(`establishment:${establishmentId}`);
  if (tableId) rooms.push(`table:${tableId}`);
  return rooms;
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
  res.json({ ok: true, schema_version: SCHEMA_VERSION });
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

  if (!tenantId || !eventName) {
    return res.status(422).json({ message: "tenant_id and event are required" });
  }

  const rooms = resolveRooms(tenantId, establishmentId, tableId);

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

  const rateKey = `${tenantId}:${auth.employee_id || "anon"}`;
  if (!checkRateLimit(rateKey)) {
    return next(new Error("RATE_LIMITED"));
  }

  const valid = await verifyBearerToken(token, tenantId);
  if (!valid) {
    return next(new Error("INVALID_TOKEN"));
  }

  socket.data.tenantId = tenantId;
  socket.data.employeeId = auth.employee_id ? Number(auth.employee_id) : null;
  socket.data.timecardId = auth.timecard_id ? Number(auth.timecard_id) : null;
  socket.data.establishmentId = auth.establishment_id
    ? Number(auth.establishment_id)
    : null;

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

  socket.on("disconnect", () => {
    // rooms cleaned automatically
  });
});

httpServer.listen(PORT, () => {
  console.log(`MyBee Socket.IO listening on :${PORT} (schema v${SCHEMA_VERSION})`);
});

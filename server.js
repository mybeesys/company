import { createServer } from "http";
import { Server } from "socket.io";

const httpServer = createServer();

const io = new Server(httpServer, {
  cors: {
    origin: "*",
  },
});

/**
 * Namespace حسب اسم التيننت
 * مثال:
 *  /tenant1
 *  /tenant2
 */
io.of(/^\/[a-zA-Z0-9_-]+$/).on("connection", (socket) => {
  const tenant = socket.nsp.name.replace("/", "");

  console.log("🟢 Connected tenant:", tenant);

  socket.on("order:create", (data) => {
    console.log("📦 Order from tenant:", tenant, data);

     socket.nsp.emit("table-reserved", {
      table_id: data.table_id,
      message: "تم حجز طاولة جديدة",
      order_id: data.order_id,
    });
  });

  socket.on("disconnect", () => {
    console.log("🔴 Disconnected tenant:", tenant);
  });
});

httpServer.listen(3000, () => {
  console.log("🚀 Socket.IO running on port 3000");
});

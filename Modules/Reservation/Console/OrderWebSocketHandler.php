<?php

namespace Modules\Reservation\Console;

use Illuminate\Console\Command;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\OrderWebSocket;

class OrderWebSocketHandler extends Command
{
    protected $signature = 'websocket:serve';
    protected $description = 'Run the WebSocket server';

    public function handle()
    {
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new OrderWebSocket()
                )
            ),
            6001
        );

        $this->info("WebSocket server started on port 6001");
        $server->run();
    }
}

<?php

namespace Tests\Unit;

use App\Support\RealtimeClientConfig;
use Illuminate\Http\Request;
use Tests\TestCase;

class RealtimeClientConfigTest extends TestCase
{
    public function test_never_builds_port_zero_in_socket_url(): void
    {
        $request = Request::create('https://test1.mybeesystem.net/api/company-login', 'POST', server: [
            'HTTPS' => 'on',
            'SERVER_PORT' => '0',
            'HTTP_HOST' => 'test1.mybeesystem.net',
        ]);

        $config = RealtimeClientConfig::forRequest($request);

        $this->assertSame('https://test1.mybeesystem.net', $config['socket_url']);
        $this->assertNull($config['socket_port']);
        $this->assertStringNotContainsString(':0', $config['socket_url']);
    }
}

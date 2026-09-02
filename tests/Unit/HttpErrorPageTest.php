<?php

namespace Tests\Unit;

use App\Support\HttpErrorPage;
use Illuminate\Auth\Access\AuthorizationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HttpErrorPageTest extends TestCase
{
    public function test_treats_framework_and_app_denials_as_generic(): void
    {
        foreach ([
            '',
            'Forbidden',
            'This action is unauthorized.',
            'ليست لديك صلاحية لتنفيذ هذا الإجراء.',
            'You do not have permission to perform this action.',
        ] as $message) {
            $this->assertTrue(
                HttpErrorPage::isGenericForbiddenMessage($message),
                "Expected generic denial for [{$message}]"
            );
        }
    }

    public function test_keeps_domain_specific_denial_messages(): void
    {
        $this->assertFalse(HttpErrorPage::isGenericForbiddenMessage(
            'ليست لديك صلاحية للوصول إلى هذا الإجراء ضمن ZATCA.'
        ));
    }

    public function test_reads_http_status_from_http_exceptions(): void
    {
        $this->assertSame(403, HttpErrorPage::statusCode(new HttpException(403), 500));
        $this->assertSame(403, HttpErrorPage::statusCode(new AuthorizationException, 403));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Validation\ValidationException;
use Modules\Accounting\Exceptions\AccountingPostingException;
use Modules\Sales\Support\CashierApiError;
use RuntimeException;
use Tests\TestCase;

final class CashierApiErrorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ar');
    }

    public function test_customer_account_missing_returns_422_arabic_without_stack(): void
    {
        $response = CashierApiError::toResponse(new AccountingPostingException('customer_account_missing'));

        $this->assertSame(422, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertSame('customer_account_missing', $payload['code']);
        $this->assertSame(
            'لا يمكن إتمام الفاتورة: هذا العميل غير مربوط بحساب محاسبي. اربط الحساب من بطاقة العميل ثم أعد المحاولة.',
            $payload['message']
        );
        $this->assertStringNotContainsString('Stack trace', (string) $response->getContent());
        $this->assertStringNotContainsString('AccountingUtil.php', (string) $response->getContent());
    }

    public function test_legacy_english_runtime_message_is_mapped_to_422_arabic(): void
    {
        $response = CashierApiError::toResponse(new RuntimeException(
            'Customer account is missing for sell (cash). Please link an accounting account to the customer.'
        ));

        $this->assertSame(422, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertSame('customer_account_missing', $payload['code']);
        $this->assertStringContainsString('غير مربوط بحساب محاسبي', $payload['message']);
    }

    public function test_unexpected_exception_is_500_without_stack(): void
    {
        $response = CashierApiError::toResponse(new \Exception('secret internals'));

        $this->assertSame(500, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertSame('تعذر إتمام الفاتورة. حاول مرة أخرى أو تواصل مع الدعم.', $payload['message']);
        $this->assertArrayNotHasKey('code', $payload);
        $this->assertStringNotContainsString('secret internals', (string) $response->getContent());
    }

    public function test_validation_exception_returns_first_arabic_or_original_message(): void
    {
        $response = CashierApiError::toResponse(ValidationException::withMessages([
            'client_id' => ['هذا العميل غير مربوط بحساب محاسبي. اربط الحساب من بطاقة العميل قبل إتمام فاتورة البيع.'],
        ]));

        $this->assertSame(422, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertSame('validation_error', $payload['code']);
        $this->assertStringContainsString('غير مربوط بحساب محاسبي', $payload['message']);
    }
}

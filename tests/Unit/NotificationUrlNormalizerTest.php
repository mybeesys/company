<?php

namespace Tests\Unit;

use Modules\General\Support\NotificationUrlNormalizer;
use PHPUnit\Framework\TestCase;

class NotificationUrlNormalizerTest extends TestCase
{
    public function test_maps_legacy_journal_entry_path(): void
    {
        $this->assertSame(
            'https://arora.mybeesystem.net/journal-entry-index',
            NotificationUrlNormalizer::normalize('https://arora.mybeesystem.net/journal-entry')
        );
    }

    public function test_keeps_valid_journal_show_paths(): void
    {
        $url = 'https://arora.mybeesystem.net/journal-entry-show/12';
        $this->assertSame($url, NotificationUrlNormalizer::normalize($url));
    }

    public function test_null_stays_null(): void
    {
        $this->assertNull(NotificationUrlNormalizer::normalize(null));
        $this->assertNull(NotificationUrlNormalizer::normalize(''));
    }
}

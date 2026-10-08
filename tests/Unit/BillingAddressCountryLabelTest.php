<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\ClientsAndSuppliers\Models\BillingAddress;
use Modules\General\Models\Country;
use PHPUnit\Framework\TestCase;

final class BillingAddressCountryLabelTest extends TestCase
{
    public function test_empty_when_country_relation_is_missing(): void
    {
        $address = new BillingAddress;
        $address->setRelation('country_', null);

        $this->assertSame('', $address->countryLabel());
    }

    public function test_joins_english_and_arabic_names(): void
    {
        $country = new Country;
        $country->name_en = 'Saudi Arabia';
        $country->name_ar = 'السعودية';

        $address = new BillingAddress;
        $address->setRelation('country_', $country);

        $this->assertSame('Saudi Arabia - السعودية', $address->countryLabel());
    }
}

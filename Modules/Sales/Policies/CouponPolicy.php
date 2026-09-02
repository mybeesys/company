<?php

namespace Modules\Sales\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Sales\Support\SalesAccess;
use Modules\Sales\Support\SalesPermissions;

class CouponPolicy
{
    use HandlesAuthorization;

    public function viewAny(): bool
    {
        return SalesAccess::can([SalesPermissions::COUPONS_SHOW, SalesPermissions::COUPON_SHOW]);
    }

    public function view(): bool
    {
        return SalesAccess::can([SalesPermissions::COUPON_SHOW, SalesPermissions::COUPONS_SHOW]);
    }

    public function create(): bool
    {
        return SalesAccess::can(SalesPermissions::COUPON_CREATE);
    }

    public function update(): bool
    {
        return SalesAccess::can(SalesPermissions::COUPON_UPDATE);
    }

    public function delete(): bool
    {
        return SalesAccess::can(SalesPermissions::COUPON_DELETE);
    }

    public function restore(): bool
    {
        return SalesAccess::can(SalesPermissions::COUPON_UPDATE);
    }

    public function forceDelete(): bool
    {
        return SalesAccess::can(SalesPermissions::COUPON_DELETE);
    }
}

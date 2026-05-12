<?php

namespace App\Helpers;

/**
 * Grantor (مانح): franchise with product_permission "absolute" uses the full HQ catalog.
 * Grantee (ممنوح): "request" limits lists to franchise_product_permissions + own franchise rows.
 */
final class FranchiseProductCatalog
{
    public static function restrictsToGrantedProductsOnly(?object $user): bool
    {
        if (! $user || ! $user->franchise_id) {
            return false;
        }

        return ($user->franchise?->product_permission ?? 'absolute') !== 'absolute';
    }
}

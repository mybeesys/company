<?php

namespace Modules\Reservation\Support;

class OrderLineParent
{
    /**
     * سطر رئيسي (ليس موديفاير/كومبو). parent_id قد يأتي null أو 0 أو "0".
     */
    public static function isRoot($line): bool
    {
        $parentId = is_object($line) ? ($line->parent_id ?? null) : ($line['parent_id'] ?? null);

        return $parentId === null || $parentId === '' || (int) $parentId === 0;
    }
}

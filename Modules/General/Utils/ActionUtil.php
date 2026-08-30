<?php

namespace Modules\General\Utils;

use Illuminate\Support\Facades\Auth;
use Modules\General\Models\Actions;

class ActionUtil
{
    public function saveOrUpdateAction($type, $name, $action)
    {
        $userId = Auth::id();
        $existing = Actions::where('user_id', $userId)->where('type', $type)->first();

        if ($action === null || $action === '') {
            return $existing;
        }

        if (! $existing) {
            return Actions::create([
                'user_id' => $userId,
                'type' => $type,
                'action' => $action,
                'name' => $name,
            ]);
        }

        $existing->update([
            'action' => $action,
        ]);

        return $existing;
    }
}

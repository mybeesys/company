<?php

namespace Modules\General\Utils;

use Illuminate\Support\Facades\Auth;
use Modules\General\Models\Actions;


class ActionUtil
{


    public function saveOrUpdateAction($type, $name, $action)
    {
        $Latest_event = Actions::where('user_id', Auth::user()->id)->where('type', $type)->first();

        if (!$Latest_event) {
            $Latest_event =   Actions::create([
                'user_id' => Auth::user()->id,
                'type' => $type,
                'action' => $action,
                'name' => $name,
            ]);
        } else {
             $Latest_event->update([
                'action' => $action
            ]);
        }

        return $Latest_event;
    }



}
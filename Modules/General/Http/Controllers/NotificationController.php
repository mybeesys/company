<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:notifications,id', Rule::exists('notifications','id')] 
        ]);
        DatabaseNotification::find($validated['id'])->delete();
        return response()->json([
            'data' => auth()->user()->unreadNotifications->count()
        ]);
    }

    public function fetchNotification()
    {
        $user = auth()->user();

        $notifications = $user->notifications;
        return response()->json([
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'No Title',
                    'body' => $notification->data['body'] ?? '',
                    'icon' => $notification->data['icon'] ?? 'ki-notification',
                    'created_at' => $notification->created_at->diffForHumans(),
                    'read_at' => $notification->read_at
                ];
            }),
            'unread_count' => $user->unreadNotifications->count(),
            'all_count' => $user->notifications->count(),
        ]);
    }
}

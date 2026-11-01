<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Validation\Rule;
use Modules\General\Services\Notifications\NotificationCatalog;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = $user->notifications()->latest();

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        if ($request->filled('module')) {
            $module = (string) $request->input('module');
            $query->where('data->module', $module);
        }

        $notifications = $query->paginate(30)->withQueryString();
        $modules = collect(NotificationCatalog::all())->pluck('module')->unique()->values();

        $moduleCounts = ['__all' => $user->notifications()->count()];
        foreach ($modules as $module) {
            $moduleCounts[$module] = $user->notifications()->where('data->module', $module)->count();
        }
        $unreadTotal = $user->unreadNotifications()->count();

        return view('general::notifications.index', compact('notifications', 'modules', 'moduleCounts', 'unreadTotal'));
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json([
            'unread_count' => 0,
        ]);
    }

    public function markAsRead(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'uuid'],
        ]);

        $notification = auth()->user()->notifications()->where('id', $validated['id'])->firstOrFail();
        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json([
            'unread_count' => auth()->user()->unreadNotifications()->count(),
            'url' => $notification->data['url'] ?? null,
        ]);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', Rule::exists('notifications', 'id')],
        ]);

        $notification = auth()->user()->notifications()->where('id', $validated['id'])->firstOrFail();
        $notification->delete();

        return response()->json([
            'data' => auth()->user()->unreadNotifications()->count(),
            'unread_count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }

    public function fetchNotification()
    {
        $user = auth()->user();
        $notifications = $user->notifications()->latest()->limit(20)->get();

        return response()->json([
            'notifications' => $notifications->map(function (DatabaseNotification $notification) {
                $data = $notification->data ?? [];

                return [
                    'id' => $notification->id,
                    'title' => $data['title'] ?? __('general.notifications'),
                    'body' => $data['body'] ?? '',
                    'icon' => $data['icon'] ?? 'ki-notification',
                    'url' => $data['url'] ?? null,
                    'module' => $data['module'] ?? 'system',
                    'severity' => $data['severity'] ?? 'info',
                    'created_at' => $notification->created_at?->diffForHumans(),
                    'read_at' => $notification->read_at,
                ];
            })->values(),
            'unread_count' => $user->unreadNotifications()->count(),
            'all_count' => $user->notifications()->count(),
        ]);
    }
}

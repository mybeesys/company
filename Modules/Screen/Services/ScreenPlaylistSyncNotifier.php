<?php

namespace Modules\Screen\Services;

use Modules\Screen\Models\Playlist;

class ScreenPlaylistSyncNotifier
{
    public function __construct(
        protected ScreenDeviceBroadcastService $broadcast
    ) {}

    public function created(Playlist $playlist): void
    {
        $this->notifyLinkedDevices($playlist, 'created');
    }

    public function updated(Playlist $playlist): void
    {
        $this->notifyLinkedDevices($playlist, 'updated');
    }

    /**
     * @param  list<int>  $deviceIds
     */
    public function deleted(int $playlistId, array $deviceIds): void
    {
        if ($deviceIds === []) {
            return;
        }

        $payload = [
            'playlist_id' => $playlistId,
            'action' => 'deleted',
            'timestamp' => now()->toIso8601String(),
        ];

        foreach ($deviceIds as $deviceId) {
            $this->broadcast->toDevice((int) $deviceId, 'screen.playlist.updated', $payload);
        }
    }

    protected function notifyLinkedDevices(Playlist $playlist, string $action): void
    {
        $deviceIds = $playlist->devices()->pluck('screen_devices.id')->all();
        if ($deviceIds === []) {
            return;
        }

        $payload = [
            'playlist_id' => $playlist->id,
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
        ];

        foreach ($deviceIds as $deviceId) {
            $this->broadcast->toDevice((int) $deviceId, 'screen.playlist.updated', $payload);
        }
    }
}

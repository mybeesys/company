<?php

namespace Modules\Screen\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Modules\Establishment\Models\Establishment;
use Modules\Screen\Models\Device;
use Modules\Screen\Models\Playlist;
use Modules\Screen\Models\Promo;

class ScreenMainApiController extends Controller
{
    /**
     * بيانات لوحة الشاشات (نفس ما يُمرَّر لصفحة /main) بصيغة JSON.
     */
    public function dashboard(): JsonResponse
    {
        $tenantId = tenancy()->tenant->id;
        $base = 'storage/tenant'.$tenantId.'/';

        $promos = Promo::query()->orderByDesc('id')->get()->map(function (Promo $p) use ($base) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'path' => $p->path,
                'media_url' => $p->path ? asset($base.$p->path) : null,
                'thumbnail' => $p->thumbnail,
                'thumbnail_url' => $p->thumbnail ? asset($base.$p->thumbnail) : null,
            ];
        });

        $playlistsCount = Playlist::count();
        $establishments = Establishment::active()->notMain()->select('id', 'name')->orderBy('name')->get();

        $hasEstablishmentColumn = Schema::hasColumn('screen_devices', 'establishment_id');
        $devicesQuery = Device::query()->orderBy('code');
        if ($hasEstablishmentColumn) {
            $devicesQuery->with('establishment:id,name');
        }
        $devices = $devicesQuery->get();

        $devicesPayload = $devices->map(function (Device $d) use ($hasEstablishmentColumn) {
            $row = ['id' => $d->id, 'code' => $d->code];
            if ($hasEstablishmentColumn) {
                $row['establishment_id'] = $d->establishment_id;
                $row['establishment_name'] = $d->establishment?->name;
            }

            return $row;
        });

        return response()->json([
            'data' => [
                'promos' => $promos,
                'playlists_count' => $playlistsCount,
                'establishments' => $establishments,
                'devices' => $devicesPayload,
                'has_establishment_column_on_devices' => $hasEstablishmentColumn,
            ],
        ]);
    }
}

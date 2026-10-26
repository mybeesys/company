<?php

namespace Modules\Reservation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\TenantPublicImage;
use Modules\General\Models\Setting;

class MenuCoverController extends Controller
{
    public function show()
    {
        $relative = (string) (Setting::where('key', 'menu_cover_image')->value('value') ?? '');
        $path = $relative !== '' ? TenantPublicImage::locate($relative) : null;
        if ($path === null) {
            abort(404);
        }

        $mime = TenantPublicImage::detectedMime($path) ?? 'image/jpeg';
        $response = response()->file($path);
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('Cache-Control', 'public, max-age=86400');

        return $response;
    }
}

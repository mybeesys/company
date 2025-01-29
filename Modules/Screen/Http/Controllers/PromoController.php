<?php

namespace Modules\Screen\Http\Controllers;

use App\Http\Controllers\Controller;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use File;
use Illuminate\Http\Request;
use Modules\Screen\Classes\PromoTable;
use Modules\Screen\Models\Promo;

class PromoController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $promos = Promo::all();
            return PromoTable::getPromoIndexTable($promos);
        }
    }

    public function playlistIndex(Request $request)
    {
        if ($request->ajax()) {
            $promos = Promo::all();
            return PromoTable::getPlaylistPromoTable($promos);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'promo' => ['required', 'mimes:jpeg,png,mp4', 'max:120000'],
        ]);
        $file = $validated['promo'];
        $fullName = $file->getClientOriginalName();
        $fileName = pathinfo($fullName)['filename'];
        $promoName = $this->storePromoMedia($file, null, 'promos');

        $thumbnailPath = null;
        if ($file->getClientOriginalExtension() === 'mp4') {

            $videoPath = public_path('storage/tenant' . tenancy()->tenant->id . '/' . $promoName);

            $thumbnailName = 'thumbnails/' . time() . '.jpg';

            $thumbnailPath = public_path('storage/tenant' . tenancy()->tenant->id . '/' . $thumbnailName);

            $this->generateVideoThumbnail($videoPath, $thumbnailPath);
            $thumbnailPath = $thumbnailName;
        } else {
            $thumbnailPath = $this->storePromoMedia($file, null, 'thumbnails');
        }

        Promo::create(['name' => $fileName, 'path' => $promoName, 'thumbnail' => $thumbnailPath]);
        return response()->json(['message' => __('employee::responses.operation_success')]);
    }

    public function storePromoMedia($image, $oldPromo = null, $folder)
    {
        $oldPath = public_path('storage/tenant' . tenancy()->tenant->id . '/' . $oldPromo);

        if (File::exists($oldPath)) {
            File::delete($oldPath);
        }
        $promoName = $folder . '/' . time() . '.' . $image->extension();
        $image->storeAs('', $promoName, 'public');
        return $promoName;
    }

    public static function generateVideoThumbnail($videoPath, $thumbnailPath)
    {
        if (!file_exists($videoPath)) {
            throw new \Exception("Video file not found: $videoPath");
        }

        if (!file_exists(dirname($thumbnailPath))) {
            mkdir(dirname($thumbnailPath), 0777, true);
        }

        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
            'ffprobe.binaries' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),
        ]);
        $video = $ffmpeg->open($videoPath);
        $video->frame(TimeCode::fromSeconds(1))->save($thumbnailPath);
    }

    public function update(Request $request, Promo $promo)
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $promo->update(['name' => $validated['name']]);

        return response()->json(['message' => __('employee::responses.updated_successfully', ['name' => __('screen::fields.promo')])]);
    }

    public function destroy(Promo $promo)
    {
        $file = public_path('storage/tenant' . tenancy()->tenant->id . '/' . $promo->path);

        if (File::exists($file)) {
            File::delete($file);
        }
        $delete = $promo->delete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('screen::fields.promo')])]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}

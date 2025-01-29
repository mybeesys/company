<?php


namespace Modules\Screen\Services;

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use File;
use Modules\Screen\Models\Promo;

class PromoActions
{

    public function storePromo($data)
    {
        $file = $data['promo'];
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
}
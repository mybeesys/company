<?php

namespace Modules\Screen\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Screen\Models\Promo;
use Modules\Screen\Services\PromoActions;

class ScreenPromoApiController extends Controller
{
    protected function promoPublicUrl(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        return asset('storage/tenant'.tenancy()->tenant->id.'/'.$relativePath);
    }

    public function index(): JsonResponse
    {
        $data = Promo::query()->orderByDesc('id')->get()->map(fn (Promo $p) => $this->serializePromo($p));

        return response()->json(['data' => $data]);
    }

    public function show(Promo $promo): JsonResponse
    {
        return response()->json(['data' => $this->serializePromo($promo)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'promo' => ['required', 'file', 'mimes:jpeg,jpg,png,mp4,mov,avi,webm,mkv', 'max:120000'],
        ]);
        try {
            (new PromoActions)->storePromo($validated);

            return response()->json(['message' => __('employee::responses.operation_success')], 201);
        } catch (\Throwable $e) {
            \Log::error('api promo store failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function update(Request $request, Promo $promo): JsonResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $promo->update(['name' => $validated['name']]);

        return response()->json([
            'message' => __('employee::responses.updated_successfully', ['name' => __('screen::fields.promo')]),
            'data' => $this->serializePromo($promo->fresh()),
        ]);
    }

    public function destroy(Promo $promo): JsonResponse
    {
        $file = public_path('storage/tenant'.tenancy()->tenant->id.'/'.$promo->path);
        if (File::exists($file)) {
            File::delete($file);
        }
        if ($promo->thumbnail) {
            $thumb = public_path('storage/tenant'.tenancy()->tenant->id.'/'.$promo->thumbnail);
            if (File::exists($thumb)) {
                File::delete($thumb);
            }
        }
        $promo->delete();

        return response()->json([
            'message' => __('employee::responses.deleted_successfully', ['name' => __('screen::fields.promo')]),
        ]);
    }

    protected function serializePromo(Promo $promo): array
    {
        return [
            'id' => $promo->id,
            'name' => $promo->name,
            'path' => $promo->path,
            'media_url' => $this->promoPublicUrl($promo->path),
            'thumbnail' => $promo->thumbnail,
            'thumbnail_url' => $this->promoPublicUrl($promo->thumbnail),
            'created_at' => $promo->created_at?->toIso8601String(),
            'updated_at' => $promo->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace Modules\Screen\Http\Controllers\Api\Concerns;

use Modules\Screen\Models\Promo;

trait SerializesScreenPromo
{
    protected function promoStorageBase(): string
    {
        return 'storage/tenant'.tenancy()->tenant->id.'/';
    }

    protected function promoPublicUrl(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        return asset($this->promoStorageBase().$relativePath);
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

@php
    $locale = app()->getLocale();
    $productName = $locale === 'ar' ? ($product->name_ar ?? '') : ($product->name_en ?? '');
    $subcategoryName = $product->subcategory
        ? ($locale === 'ar' ? ($product->subcategory->name_ar ?? '') : ($product->subcategory->name_en ?? ''))
        : '';
    $detailTplId = 'product-detail-tpl-' . $product->id . '-' . ($tplSuffix ?? 'main');
    $imageUrl = $product->image ? asset($product->image) : asset('menuplacholder.jpg');
    $hasCombo = (bool) $product->group_combo || ($product->combos && $product->combos->isNotEmpty());
@endphp

<div class="pm-card-wrap{{ !empty($compact) ? ' pm-card-wrap--compact' : '' }}">
    <article
        class="pm-card product-card card border-0 ms-reveal{{ !empty($compact) ? ' pm-card--compact' : '' }}"
        role="button"
        tabindex="0"
        data-product-detail-tpl="{{ $detailTplId }}"
        data-product-name="{{ e($productName) }}"
        aria-label="{{ $productName }} — @lang('reservation::lang.menu_product_view_details')"
        @include('reservation::order.partials.product-allergen-data-attr', ['product' => $product])
    >
        <div class="pm-card__media product-card-image-wrap">
            <img src="{{ $imageUrl }}" class="pm-card__image w-100" alt="{{ $productName }}" loading="lazy">
            <div class="pm-card__media-overlay" aria-hidden="true"></div>
            <div class="pm-card__badges">
                @if ($product->promot_upsell)
                    <span class="pm-badge pm-badge--popular">@lang('reservation::lang.menu_product_badge_popular')</span>
                @endif
                @if ($hasCombo)
                    <span class="pm-badge pm-badge--combo">@lang('reservation::lang.menu_product_badge_combo')</span>
                @endif
                @if ($product->sold_by_weight)
                    <span class="pm-badge pm-badge--weight">@lang('reservation::lang.menu_product_badge_weight')</span>
                @endif
            </div>
            <span class="pm-card__tap-hint" aria-hidden="true">
                <i class="fa-solid fa-circle-info"></i>
            </span>
        </div>

        <div class="pm-card__body card-body">
            @if ($subcategoryName !== '')
                <span class="pm-card__subcategory">{{ $subcategoryName }}</span>
            @endif

            <h6 class="pm-card__title card-title fw-bold">{{ $productName }}</h6>

            <div class="pm-card__footer">
                <span class="pm-card__tax">@lang('reservation::lang.menu_product_includes_tax')</span>
                <span class="pm-card__price fw-bold">
                    {{ number_format((float) $product->price_with_tax, 2) }}
                    <span class="sar-currency">@lang('general::lang.currency')</span>
                </span>
            </div>
        </div>
    </article>

    <template id="{{ $detailTplId }}">
        @include('reservation::order.partials.product-menu-detail-content', ['product' => $product])
    </template>
</div>

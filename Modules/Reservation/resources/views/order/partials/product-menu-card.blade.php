@php
    $locale = app()->getLocale();
    $productName = $locale === 'ar' ? ($product->name_ar ?? '') : ($product->name_en ?? '');
    $productDesc = trim($locale === 'ar' ? ($product->description_ar ?? '') : ($product->description_en ?? ''));
    $subcategoryName = $product->subcategory
        ? ($locale === 'ar' ? ($product->subcategory->name_ar ?? '') : ($product->subcategory->name_en ?? ''))
        : '';
    $detailTplId = 'product-detail-tpl-' . $product->id . '-' . ($tplSuffix ?? 'main');
    $imageUrl = $product->image ? asset($product->image) : asset('menuplacholder.jpg');
    $modifiersCount = $product->modifiers?->count() ?? 0;
    $hasCombo = (bool) $product->group_combo || ($product->combos && $product->combos->isNotEmpty());
    $hasMeta = (!empty($product->preparation_time) && is_numeric($product->preparation_time))
        || (!empty($product->calories) && is_numeric($product->calories))
        || $modifiersCount > 0;
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

        <div class="pm-card__body card-body p-3">
            <span class="pm-card__subcategory{{ $subcategoryName === '' ? ' pm-card__slot--empty' : '' }}">{{ $subcategoryName }}</span>

            <h6 class="pm-card__title card-title mb-1 fw-bold">{{ $productName }}</h6>

            <div class="pm-card__allergen-slot">
                @include('reservation::order.partials.product-allergen-icons-row', [
                    'product' => $product,
                    'allergenTplSuffix' => $tplSuffix ?? 'main',
                    'iconsSize' => $iconsSize ?? '',
                ])
            </div>

            <p class="pm-card__desc text-muted product-card-desc mb-2{{ $productDesc === '' ? ' pm-card__slot--empty' : '' }}">{{ $productDesc }}</p>

            <div class="pm-card__meta{{ !$hasMeta ? ' pm-card__slot--empty' : '' }}">
                @if (!empty($product->preparation_time) && is_numeric($product->preparation_time))
                    <span class="pm-meta-pill" title="@lang('reservation::lang.menu_product_prep_time')">
                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                        {{ (int) $product->preparation_time }} @lang('reservation::lang.menu_product_minutes')
                    </span>
                @endif
                @if (!empty($product->calories) && is_numeric($product->calories))
                    <span class="pm-meta-pill">
                        <i class="fa-solid fa-fire-flame-curved" aria-hidden="true"></i>
                        {{ (int) $product->calories }} CAL
                    </span>
                @endif
                @if ($modifiersCount > 0)
                    <span class="pm-meta-pill">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        +{{ $modifiersCount }}
                    </span>
                @endif
            </div>

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

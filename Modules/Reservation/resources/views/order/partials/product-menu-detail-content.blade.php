@php
    $locale = app()->getLocale();
    $productName = $locale === 'ar' ? ($product->name_ar ?? '') : ($product->name_en ?? '');
    $productDesc = trim($locale === 'ar' ? ($product->description_ar ?? '') : ($product->description_en ?? ''));
    $subcategoryName = $product->subcategory
        ? ($locale === 'ar' ? ($product->subcategory->name_ar ?? '') : ($product->subcategory->name_en ?? ''))
        : '';
    $unitName = optional($product->unitTransfers->first())->unit1;
    $modifiersCount = $product->modifiers?->count() ?? 0;
    $hasCombo = (bool) $product->group_combo || ($product->combos && $product->combos->isNotEmpty());
    $imageUrl = $product->image ? asset($product->image) : asset('menuplacholder.jpg');
@endphp

<div class="pm-detail">
    <div class="pm-detail__hero">
        <img src="{{ $imageUrl }}" alt="{{ $productName }}" class="pm-detail__image">
        <div class="pm-detail__hero-fade"></div>
        <div class="pm-detail__badges">
            @if ($product->promot_upsell)
                <span class="pm-badge pm-badge--popular">@lang('reservation::lang.menu_product_badge_popular')</span>
            @endif
            @if ($hasCombo)
                <span class="pm-badge pm-badge--combo">@lang('reservation::lang.menu_product_badge_combo')</span>
            @endif
            @if ($product->linked_combo)
                <span class="pm-badge pm-badge--combo">@lang('reservation::lang.menu_product_badge_linked_combo')</span>
            @endif
            @if ($product->sold_by_weight)
                <span class="pm-badge pm-badge--weight">@lang('reservation::lang.menu_product_badge_weight')</span>
            @endif
            @if ($product->type === 'variable')
                <span class="pm-badge pm-badge--variable">@lang('reservation::lang.menu_product_badge_variable')</span>
            @endif
        </div>
    </div>

    <div class="pm-detail__body">
        @if ($subcategoryName !== '')
            <span class="pm-detail__subcategory">{{ $subcategoryName }}</span>
        @endif
        <h2 class="pm-detail__title">{{ $productName }}</h2>

        <div class="pm-detail__chips">
            @if (!empty($product->preparation_time) && is_numeric($product->preparation_time))
                <span class="pm-chip"><i class="fa-regular fa-clock"></i> {{ (int) $product->preparation_time }} @lang('reservation::lang.menu_product_minutes')</span>
            @endif
            @if (!empty($product->calories) && is_numeric($product->calories))
                <span class="pm-chip"><i class="fa-solid fa-fire-flame-curved"></i> {{ (int) $product->calories }} CAL</span>
            @endif
            @if ($modifiersCount > 0)
                <span class="pm-chip"><i class="fa-solid fa-plus"></i> @lang('reservation::lang.menu_product_modifiers_count', ['count' => $modifiersCount])</span>
            @endif
            @if ($unitName)
                <span class="pm-chip"><i class="fa-solid fa-scale-balanced"></i> {{ $unitName }}</span>
            @endif
        </div>

        <p class="pm-detail__desc">{{ $productDesc !== '' ? $productDesc : __('reservation::lang.menu_product_no_description') }}</p>

        @if ($product->combos && $product->combos->isNotEmpty())
            <div class="pm-detail__section">
                <h3 class="pm-detail__section-title">@lang('reservation::lang.menu_product_combo_section')</h3>
                <ul class="pm-detail__list">
                    @foreach ($product->combos as $combo)
                        <li>{{ $locale === 'ar' ? ($combo->name_ar ?? $combo->name_en) : ($combo->name_en ?? $combo->name_ar) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($modifiersCount > 0)
            <div class="pm-detail__section">
                <h3 class="pm-detail__section-title">@lang('reservation::lang.menu_product_modifiers_section')</h3>
                <ul class="pm-detail__list pm-detail__list--modifiers">
                    @foreach ($product->modifiers as $modifier)
                        @php
                            $modItem = $modifier->modifierItem;
                            $modName = $modItem
                                ? ($locale === 'ar' ? ($modItem->name_ar ?? $modItem->name_en) : ($modItem->name_en ?? $modItem->name_ar))
                                : null;
                        @endphp
                        @if ($modName)
                            <li>{{ $modName }}</li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="pm-detail__allergens">
            @include('reservation::order.partials.product-allergens', ['product' => $product])
        </div>
    </div>

    <div class="pm-detail__footer">
        <div>
            <div class="pm-detail__price">
                {{ number_format((float) $product->price_with_tax, 2) }}
                <span class="sar-currency">@lang('general::lang.currency')</span>
            </div>
            <div class="pm-detail__tax-hint">@lang('reservation::lang.menu_product_includes_tax')</div>
        </div>
    </div>
</div>

@php
    $rawAllergens = $product->allergens ?? [];
    if (! is_array($rawAllergens)) {
        $rawAllergens = [];
    }
    $allergenKeys = [];
    foreach ($rawAllergens as $entry) {
        if (is_array($entry) && isset($entry['value'])) {
            $allergenKeys[] = (string) $entry['value'];
        } elseif (is_string($entry)) {
            $allergenKeys[] = $entry;
        }
    }
    $allergenKeys = array_values(array_unique(array_filter($allergenKeys)));
    $productDisplayName = app()->getLocale() == 'ar' ? ($product->name_ar ?? '') : ($product->name_en ?? '');
    $tplSuffix = $allergenTplSuffix ?? 'main';
    $tplId = 'allergen-tpl-' . $product->id . '-' . $tplSuffix;
    $allergenBtnSizeClass = ($size ?? '') === 'sm' ? ' product-allergen-float-btn--sm' : '';
@endphp

@if (count($allergenKeys) > 0)
    <template id="{{ $tplId }}">
        @include('reservation::order.partials.product-allergens', ['product' => $product])
    </template>
    <button
        type="button"
        class="product-allergen-float-btn{{ $allergenBtnSizeClass }}"
        data-bs-toggle="modal"
        data-bs-target="#modalProductAllergens"
        data-allergen-template="{{ $tplId }}"
        data-product-name="{{ e($productDisplayName) }}"
        title="@lang('general::lang.product_allergens_open')"
        aria-label="@lang('general::lang.product_allergens_open')"
    >
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
    </button>
@endif

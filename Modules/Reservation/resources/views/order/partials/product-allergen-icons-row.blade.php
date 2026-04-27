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
    $tplSuffix = $allergenTplSuffix ?? 'main';
    $tplId = 'allergen-tpl-' . $product->id . '-' . $tplSuffix;
    $productDisplayName = app()->getLocale() == 'ar' ? ($product->name_ar ?? '') : ($product->name_en ?? '');

    $iconMap = [
        'eggs' => 'fa-solid fa-egg',
        'milk' => 'fa-solid fa-bottle-droplet',
        'fish' => 'fa-solid fa-fish',
        'crustaceans' => 'fa-solid fa-shrimp',
        'tree_nuts' => 'fa-solid fa-tree',
        'peanuts' => 'fa-solid fa-bowl-food',
        'wheat' => 'fa-solid fa-wheat-awn',
        'soybeans' => 'fa-solid fa-bottle-water',
        'sesame' => 'fa-solid fa-mortar-pestle',
        'mustard' => 'fa-solid fa-jar',
        'celery' => 'fa-solid fa-carrot',
        'lupin' => 'fa-solid fa-seedling',
        'molluscs' => 'fa-solid fa-water',
        'sulphites' => 'fa-solid fa-flask',
    ];
@endphp

@if (count($allergenKeys) > 0)
    <template id="{{ $tplId }}">
        @include('reservation::order.partials.product-allergens', ['product' => $product])
    </template>
    <div class="product-allergen-icons-row {{ ($iconsSize ?? '') === 'sm' ? 'product-allergen-icons-row--sm' : '' }}" role="presentation">
        @foreach ($allergenKeys as $key)
            @php
                $iconClass = $iconMap[$key] ?? 'fa-solid fa-circle-dot';
                $langKey = 'general::lang.allergen_' . $key;
                $label = \Illuminate\Support\Facades\Lang::has($langKey)
                    ? __($langKey)
                    : ucfirst(str_replace('_', ' ', $key));
            @endphp
            <button
                type="button"
                class="product-allergen-icon-btn"
                data-bs-toggle="modal"
                data-bs-target="#modalProductAllergens"
                data-allergen-template="{{ $tplId }}"
                data-product-name="{{ e($productDisplayName) }}"
                title="{{ $label }}"
                aria-label="{{ $label }}"
            >
                <i class="{{ $iconClass }}" aria-hidden="true"></i>
            </button>
        @endforeach
    </div>
@endif

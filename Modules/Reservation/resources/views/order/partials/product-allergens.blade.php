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
    <div class="product-allergens" role="region" aria-label="@lang('general::lang.product_allergens_a11y')">
        <div class="product-allergens-head">
            <span class="product-allergens-badge" aria-hidden="true">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </span>
            <div class="product-allergens-head-text">
                <span class="product-allergens-title">@lang('general::lang.product_allergens_title')</span>
                <span class="product-allergens-hint">@lang('general::lang.product_allergens_hint')</span>
            </div>
        </div>
        <div class="product-allergen-chips">
            @foreach ($allergenKeys as $key)
                @php
                    $iconClass = $iconMap[$key] ?? 'fa-solid fa-circle-dot';
                    $langKey = 'general::lang.allergen_' . $key;
                    $label = \Illuminate\Support\Facades\Lang::has($langKey)
                        ? __($langKey)
                        : ucfirst(str_replace('_', ' ', $key));
                @endphp
                <span class="product-allergen-chip">
                    <i class="{{ $iconClass }} product-allergen-chip-icon" aria-hidden="true"></i>
                    <span>{{ $label }}</span>
                </span>
            @endforeach
        </div>
    </div>
@endif

@php
    $filterKeys = [
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

<div class="menu-allergen-filter mt-2 mb-1 px-1" id="menuAllergenFilter">
    <button
        type="button"
        class="menu-allergen-filter-toggle btn btn-sm w-100 d-flex align-items-center gap-2 collapsed"
        data-bs-toggle="collapse"
        data-bs-target="#menuAllergenFilterCollapse"
        aria-expanded="false"
        aria-controls="menuAllergenFilterCollapse"
        aria-label="@lang('reservation::lang.menu_allergen_filter_toggle_aria')"
    >
        <i class="fa-solid fa-filter" aria-hidden="true"></i>
        <span class="menu-allergen-filter-toggle-text flex-grow-1 text-start">@lang('reservation::lang.menu_allergen_filter_toggle')</span>
        <span class="badge rounded-pill bg-secondary menu-allergen-filter-badge d-none" id="allergenFilterActiveBadge" aria-hidden="true">0</span>
        <i class="bi bi-chevron-down menu-allergen-filter-chevron flex-shrink-0" aria-hidden="true"></i>
    </button>

    <div class="collapse pt-2" id="menuAllergenFilterCollapse">
        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-1">
            <span class="menu-allergen-filter-label small fw-semibold text-muted">@lang('reservation::lang.menu_allergen_avoid_hint')</span>
            <button type="button" class="btn btn-link btn-sm p-0 menu-allergen-filter-clear d-none" id="allergenFilterClear" aria-controls="menuAllergenFilter">
                @lang('reservation::lang.menu_allergen_clear')
            </button>
        </div>
        <div class="menu-allergen-filter-chips custom-scroll d-flex flex-wrap gap-2 pb-1" role="group" aria-label="@lang('reservation::lang.menu_allergen_avoid_a11y')">
            @foreach ($filterKeys as $key => $iconClass)
                @php
                    $langKey = 'general::lang.allergen_' . $key;
                    $label = __($langKey);
                @endphp
                <button
                    type="button"
                    class="allergen-filter-chip btn btn-sm d-inline-flex align-items-center gap-1 flex-shrink-0"
                    data-allergen-filter="{{ $key }}"
                    aria-pressed="false"
                    title="{{ $label }}"
                >
                    <i class="{{ $iconClass }}" aria-hidden="true"></i>
                    <span class="allergen-filter-chip-label">{{ $label }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>

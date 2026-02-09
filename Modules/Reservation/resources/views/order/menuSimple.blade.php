<!DOCTYPE html>
@php
    $local = app()->currentLocale();
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
    $rtl_files = $local == 'ar' ? '.rtl' : '';
@endphp
<html lang="{{ $local }}" direction="{{ $dir }}" dir="{{ $dir }}" style="direction: {{ $dir }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $establishment->name }} - @lang('general::lang.menu')</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #ebb81e;
            --bg-color: #f9f9f9;
            --card-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg-color);
            margin: 0; padding: 0;
        }

        .welcome-screen {
            position: fixed; inset: 0; z-index: 9999;
            background-size: cover; background-position: center;
            display: flex; align-items: center; justify-content: center;
            transition: transform 0.6s ease-in-out;
        }

        .welcome-screen.hidden { transform: translateY(-100%); }

        .category-scroll {
            display: flex; overflow-x: auto; white-space: nowrap;
            padding: 15px; gap: 15px; background: #fff;
            position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .category-item {
            display: flex; flex-direction: column; align-items: center;
            text-decoration: none; color: #333; min-width: 80px;
        }

        .category-img {
            width: 65px; height: 65px; border-radius: 50%;
            object-fit: cover; border: 2px solid transparent;
            transition: 0.3s; margin-bottom: 5px;
            box-shadow: var(--card-shadow);
        }

        .category-item.active .category-img { border-color: var(--primary-color); transform: scale(1.1); }

        .product-card {
            background: #fff; border-radius: 15px; overflow: hidden;
            transition: 0.3s; height: 100%; border: 1px solid #eee;
            position: relative;
        }

        .product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }

        .product-image { width: 100%; height: 160px; object-fit: cover; }

        .allergen-btn {
            position: absolute; top: 10px; left: 10px;
            background: rgba(255,255,255,0.9); border: none;
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #d9534f; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            cursor: pointer; z-index: 10;
        }

        .price-tag { color: var(--primary-color); font-weight: 800; font-size: 1.1rem; }

        .btn-group .btn.active { background-color: var(--primary-color); color: #fff; border-color: var(--primary-color); }

        .allergen-item-popup {
            display: flex; align-items: center; padding: 12px;
            background: #fdfaf0; border-radius: 10px; margin-bottom: 8px;
            border-right: 4px solid var(--primary-color);
        }

        .allergen-item-popup i { font-size: 1.5rem; margin-inline-end: 15px; color: #856404; }

        .hidden { display: none !important; }

        @media (max-width: 768px) {
            .grid-4 { grid-template-columns: repeat(2, 1fr) !important; }
        }
    </style>
</head>

<body>
    <div class="welcome-screen" id="welcomeScreen" style="background-image: url('{{ asset('11.jpeg') }}');">
        <div class="text-center text-white p-4" style="background: rgba(0,0,0,0.4); border-radius: 20px; backdrop-filter: blur(5px);">
            <img src="{{ config('app.domain') . '/storage/' . $company->logo }}" class="rounded-circle mb-3 shadow-lg" width="100">
            <h2 class="fw-bold">@lang('general::lang.welcome_to') {{ $company->name }}</h2>
            <button class="btn btn-warning btn-lg rounded-pill px-5 mt-3 fw-bold" onclick="startMenu()">@lang('general::lang.start_now')</button>
        </div>
    </div>

    <div id="mainContent" class="hidden">
        <div class="category-scroll no-scrollbar">
            @foreach ($categories as $category)
                <a href="#category-{{ $category->id }}" class="category-item" id="nav-category-{{ $category->id }}">
                    <img src="{{ asset($category->products->first()->image ?? 'default.jpg') }}" class="category-img">
                    <span class="small fw-bold">{{ $local == 'ar' ? $category->name_ar : $category->name_en }}</span>
                </a>
            @endforeach
        </div>

        <div class="container-fluid py-4">
            <div class="row mb-4 px-2">
                <div class="col-8">
                    <div class="input-group shadow-sm rounded-pill bg-white px-3">
                        <span class="input-group-text bg-transparent border-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="searchInput" class="form-control border-0 shadow-none bg-transparent" placeholder="@lang('general::lang.search_placeholder')">
                    </div>
                </div>
                <div class="col-4 d-flex justify-content-end">
                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                        <button class="btn btn-white btn-sm px-3 active" data-view="grid-4"><i class="bi bi-grid-3x3-gap"></i></button>
                        <button class="btn btn-white btn-sm px-3" data-view="grid-2"><i class="bi bi-grid"></i></button>
                    </div>
                </div>
            </div>

            <div id="noResults" class="text-center py-5 hidden">
                <i class="bi bi-search text-muted fs-1"></i>
                <p class="mt-2 text-muted">@lang('general::lang.no_results')</p>
            </div>

            @foreach ($categories as $category)
                @if ($category->products->count() > 0)
                    <section id="category-{{ $category->id }}" class="category-section mb-5">
                        <h4 class="fw-bold mb-4 px-2">{{ $local == 'ar' ? $category->name_ar : $category->name_en }}</h4>
                        <div class="products-wrapper grid-4 d-grid" style="grid-template-columns: repeat(4, 1fr); gap: 15px;">
                            @foreach ($category->products as $product)
                                <div class="product-card p-0">
                                    @if($product->allergens)
                                        <button class="allergen-btn" onclick="showAllergens('{{ json_encode($product->allergens) }}')">
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                        </button>
                                    @endif
                                    <img src="{{ asset($product->image) }}" class="product-image">
                                    <div class="p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="fw-bold mb-0 card-title">{{ $local == 'ar' ? $product->name_ar : $product->name_en }}</h6>
                                            @if($product->calories)
                                                <span class="badge bg-light text-dark rounded-pill fw-normal"><i class="bi bi-fire text-danger"></i> {{ (int)$product->calories }}</span>
                                            @endif
                                        </div>
                                        <p class="text-muted mb-3 small" style="height: 35px; overflow: hidden; line-height: 1.2;">
                                            {{ $local == 'ar' ? $product->description_ar : $product->description_en }}
                                        </p>
                                        <div class="price-tag">
                                            {{ number_format($product->price_with_tax, 2) }} <small style="font-size: 0.6em">@lang('general::lang.currency')</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    </div>

    <div class="modal fade" id="allergenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h6 class="fw-bold mb-0">@lang('general::lang.allergens') | مسببات الحساسية</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="allergenBody"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const allergenIcons = {
            'eggs': 'fa-solid fa-egg', 'milk': 'fa-solid fa-glass-water',
            'fish': 'fa-solid fa-fish', 'crustaceans': 'fa-solid fa-shrimp',
            'tree_nuts': 'fa-solid fa-seedling', 'peanuts': 'fa-solid fa-nut-bolt',
            'wheat': 'fa-solid fa-wheat-awn', 'soybeans': 'fa-solid fa-clover',
            'sesame': 'fa-solid fa-leaf', 'mustard': 'fa-solid fa-bottle-droplet'
        };

        function startMenu() {
            $('#welcomeScreen').addClass('hidden');
            $('#mainContent').removeClass('hidden');
        }

        function showAllergens(data) {
            let allergens = JSON.parse(data);
            if (typeof allergens === 'string') allergens = JSON.parse(allergens);
            
            let html = '';
            allergens.forEach(item => {
                let icon = allergenIcons[item.value] || 'fa-solid fa-circle-exclamation';
                html += `
                    <div class="allergen-item-popup">
                        <i class="${icon}"></i>
                        <span class="fw-bold small">${item.label}</span>
                    </div>`;
            });
            $('#allergenBody').html(html);
            new bootstrap.Modal('#allergenModal').show();
        }

        $(document).ready(function() {
            $('#searchInput').on('keyup', function() {
                let value = $(this).val().toLowerCase();
                let hasResults = false;

                $('.product-card').each(function() {
                    let match = $(this).find('.card-title').text().toLowerCase().includes(value);
                    $(this).toggle(match);
                    if(match) hasResults = true;
                });

                $('.category-section').each(function() {
                    $(this).toggle($(this).find('.product-card:visible').length > 0);
                });

                $('#noResults').toggleClass('hidden', hasResults);
            });

            $('[data-view]').click(function() {
                let view = $(this).data('view');
                $('.products-wrapper').css('grid-template-columns', view === 'grid-2' ? 'repeat(2, 1fr)' : 'repeat(4, 1fr)');
                $('[data-view]').removeClass('active');
                $(this).addClass('active');
            });
        });
    </script>
</body>
</html>
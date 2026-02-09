<!DOCTYPE html>
@php
    $local = app()->currentLocale();
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
    $rtl_files = $local == 'ar' ? '.rtl' : '';
@endphp
<html lang="{{ $local }}" direction="{{ $dir }}" dir="{{ $dir }}" style="direction: {{ $dir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $establishment->name }} - @lang('general::lang.menu')</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-accent: #ebb81e;
            --main-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --radius-lg: 20px;
            --radius-md: 15px;
        }

        body.dark-mode {
            --main-bg: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--main-bg);
            color: var(--text-main);
            overflow-x: hidden;
        }

        .navbar-custom {
            height: 65px;
            background: var(--card-bg);
            position: fixed;
            top: 0; width: 100%; z-index: 2000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .welcome-screen {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.8s cubic-bezier(0.86, 0, 0.07, 1);
        }

        .welcome-screen.hide { transform: translateY(-100%); }

        .category-item {
            min-width: 85px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }

        .category-img-box {
            width: 70px; height: 70px;
            border-radius: 50%;
            padding: 3px;
            background: var(--card-bg);
            border: 2px solid transparent;
            margin-bottom: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .category-img-box img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

        .category-item.active .category-img-box {
            border-color: var(--primary-accent);
            transform: scale(1.1);
        }

        .category-item.active span { color: var(--primary-accent); font-weight: 800; }

        .product-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            border: none;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            margin-bottom: 20px;
        }

        .product-img { height: 180px; width: 100%; object-fit: cover; }

        .allergen-trigger {
            width: 35px; height: 35px;
            background: #fff3cd;
            color: #856404;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            transition: 0.3s;
        }

        .allergen-trigger:hover { background: #ffeeba; }

        .allergen-badge {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 10px;
        }

        .allergen-badge i { font-size: 20px; color: var(--primary-accent); margin-inline-end: 15px; }

        .price-text {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--primary-accent);
        }

        .sticky-top-custom {
            position: sticky;
            top: 65px;
            z-index: 1000;
            background: var(--main-bg);
            padding: 15px 0;
        }

        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body>

    <div class="welcome-screen" id="welcomeScreen" style="background-image: url('{{ asset('11.jpeg') }}');">
        <div class="text-center text-white p-4">
            <img src="{{ config('app.domain') . '/storage/' . $company->logo }}" class="rounded-circle mb-4 shadow-lg" width="120">
            <h1 class="fw-bold">@lang('general::lang.welcome_to')</h1>
            <h2 class="mb-4">{{ $company->name }}</h2>
            <button class="btn btn-warning btn-lg rounded-pill px-5 fw-bold" id="startBtn">@lang('general::lang.start_now')</button>
        </div>
    </div>

    <nav class="navbar-custom px-3 d-flex align-items-center justify-content-between">
        <div id="themeToggle" class="btn btn-light rounded-circle shadow-sm"><i class="fas fa-moon"></i></div>
        <img src="{{ config('app.domain') . '/storage/' . $company->logo }}" height="45">
        <a href="{{ route('set_locale', ['locale' => session('locale') == 'ar' ? 'en' : 'ar']) }}" class="btn btn-light rounded-pill btn-sm fw-bold">
            {{ session('locale') == 'ar' ? 'English' : 'عربي' }}
        </a>
    </nav>

    <div id="menuContent" style="display:none; padding-top: 65px;">
        <div class="sticky-top-custom">
            <div class="d-flex overflow-auto gap-3 px-3 no-scrollbar">
                @foreach ($categories as $category)
                    <a href="#category-{{ $category->id }}" class="category-item">
                        <div class="category-img-box">
                            <img src="{{ asset($category->products->first()->image ?? 'default.jpg') }}">
                        </div>
                        <span class="small d-block text-nowrap">{{ $local == 'ar' ? $category->name_ar : $category->name_en }}</span>
                    </a>
                @endforeach
            </div>
            
            <div class="px-3 mt-3">
                <div class="input-group bg-white rounded-pill px-3 py-1 shadow-sm">
                    <span class="input-group-text bg-transparent border-0"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control border-0 bg-transparent shadow-none" placeholder="@lang('general::lang.search_placeholder')">
                </div>
            </div>
        </div>

        <div class="container-fluid mt-4">
            @foreach ($categories as $category)
                @if ($category->products->count() > 0)
                    <section id="category-{{ $category->id }}" class="category-section mb-5">
                        <h4 class="fw-bold px-3 mb-4">{{ $local == 'ar' ? $category->name_ar : $category->name_en }}</h4>
                        <div class="row px-2">
                            @foreach ($category->products as $product)
                                <div class="col-lg-3 col-md-4 col-6 mb-4">
                                    <div class="product-card">
                                        <img src="{{ asset($product->image) }}" class="product-img">
                                        <div class="p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <h6 class="fw-bold mb-0">{{ $local == 'ar' ? $product->name_ar : $product->name_en }}</h6>
                                                @if($product->calories)
                                                    <span class="small text-muted"><i class="bi bi-fire text-danger"></i> {{ (int)$product->calories }}</span>
                                                @endif
                                            </div>
                                            <p class="text-muted small mb-3 text-truncate-2" style="font-size: 0.75rem; height: 32px;">
                                                {{ $local == 'ar' ? $product->description_ar : $product->description_en }}
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="price-text">{{ number_format($product->price_with_tax, 2) }} <small style="font-size: 10px">@lang('general::lang.currency')</small></span>
                                                @if($product->allergens)
                                                    <button class="allergen-trigger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#allergenModal" 
                                                            data-allergens='@json($product->allergens)'>
                                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                                    </button>
                                                @endif
                                            </div>
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
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 25px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold mb-0">@lang('general::lang.allergens')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-4">@lang('general::lang.allergen_notice')</p>
                    <div id="allergenList"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const allergenIcons = {
            'eggs': 'fa-solid fa-egg',
            'milk': 'fa-solid fa-glass-water',
            'fish': 'fa-solid fa-fish',
            'crustaceans': 'fa-solid fa-shrimp',
            'tree_nuts': 'fa-solid fa-seedling',
            'peanuts': 'fa-solid fa-nut-bolt',
            'wheat': 'fa-solid fa-wheat-awn',
            'soybeans': 'fa-solid fa-clover',
            'sesame': 'fa-solid fa-leaf',
            'mustard': 'fa-solid fa-bottle-droplet',
            'celery': 'fa-solid fa-herb',
            'lupin': 'fa-solid fa-sunflower',
            'molluscs': 'fa-solid fa-shell',
            'sulphites': 'fa-solid fa-flask-vial'
        };

        $(document).ready(function() {
            $('#startBtn').click(function() {
                $('#welcomeScreen').addClass('hide');
                $('#menuContent').show();
            });

            $('#themeToggle').click(function() {
                $('body').toggleClass('dark-mode');
                $(this).find('i').toggleClass('fa-moon fa-sun');
            });

            $('.allergen-trigger').click(function() {
                let allergens = $(this).data('allergens');
                if (typeof allergens === 'string') allergens = JSON.parse(allergens);
                
                let html = '';
                allergens.forEach(item => {
                    let iconClass = allergenIcons[item.value] || 'fa-solid fa-circle-exclamation';
                    html += `
                        <div class="allergen-badge">
                            <i class="${iconClass}"></i>
                            <span class="fw-bold">${item.label}</span>
                        </div>
                    `;
                });
                $('#allergenList').html(html);
            });

            $('#searchInput').on('keyup', function() {
                let val = $(this).val().toLowerCase();
                $('.product-card').each(function() {
                    let match = $(this).text().toLowerCase().includes(val);
                    $(this).closest('.col-lg-3').toggle(match);
                });
            });

            $(window).scroll(function() {
                let scrollPos = $(document).scrollTop() + 200;
                $('.category-section').each(function() {
                    if (scrollPos >= $(this).offset().top && scrollPos <= ($(this).offset().top + $(this).outerHeight())) {
                        let id = $(this).attr('id');
                        $('.category-item').removeClass('active');
                        $(`a[href="#${id}"]`).addClass('active');
                    }
                });
            });
        });
    </script>
</body>
</html>
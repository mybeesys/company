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
            --secondary-accent: #4a6fa5;
            --main-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.08);
            --radius-lg: 18px;
            --radius-md: 12px;
        }

        body.dark-mode {
            --main-bg: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --glass-bg: rgba(30, 41, 59, 0.9);
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.3);
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--main-bg);
            color: var(--text-main);
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        .navbar-custom {
            height: 60px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            position: fixed;
            top: 0; width: 100%; z-index: 2000;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .welcome-screen {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: cover;
            background-position: center;
            transition: transform 0.8s cubic-bezier(0.86, 0, 0.07, 1);
        }

        .welcome-screen::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.8));
        }

        .welcome-screen.hide { transform: translateY(-100%); }

        .profile-header {
            position: relative;
            padding-top: 60px;
        }

        .cover-image {
            height: 260px;
            background-size: cover;
            background-position: center;
            border-bottom-left-radius: 40px;
            border-bottom-right-radius: 40px;
        }

        .logo-container {
            width: 110px; height: 110px;
            background: var(--card-bg);
            border-radius: 30px;
            padding: 5px;
            box-shadow: var(--shadow-md);
            margin: -55px auto 15px;
            position: relative; z-index: 10;
        }

        .logo-container img { width: 100%; height: 100%; object-fit: contain; border-radius: 25px; }

        .category-card {
            min-width: 100px;
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: 10px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 1px solid transparent;
            box-shadow: var(--shadow-sm);
        }

        .category-card.active {
            background: var(--primary-accent);
            transform: translateY(-5px);
        }

        .category-card.active .category-title { color: #fff; }

        .product-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            border: none;
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .product-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }

        .product-img-wrapper { position: relative; height: 180px; }
        .product-img-wrapper img { width: 100%; height: 100%; object-fit: cover; }

        .allergen-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px; height: 28px;
            background: rgba(235, 184, 30, 0.1);
            border-radius: 50%;
            margin-right: 5px;
            font-size: 14px;
            cursor: pointer;
        }

        .price-badge {
            background: var(--secondary-accent);
            color: #fff;
            padding: 4px 12px;
            border-radius: 10px;
            font-weight: 700;
        }

        .search-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 5px 15px;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .sticky-categories {
            position: sticky;
            top: 60px;
            z-index: 1040;
            background: var(--main-bg);
            padding: 15px 0;
        }

        @media (max-width: 768px) {
            .cover-image { height: 180px; }
            .products-wrapper.grid-4 { grid-template-columns: repeat(2, 1fr) !important; gap: 12px !important; }
            .logo-container { width: 90px; height: 90px; margin-top: -45px; }
        }
    </style>
</head>
<body class="light-mode">

    <div class="welcome-screen" id="welcomeScreen" style="background-image: url('{{ asset('11.jpeg') }}');">
        <div class="position-relative z-2 text-center text-white p-4">
            <img src="{{ config('app.domain') . '/storage/' . $company->logo }}" class="rounded-circle mb-4 shadow-lg" width="130" height="130">
            <h1 class="fw-bold mb-2">@lang('general::lang.welcome_to') {{ $company->name }}</h1>
            <p class="opacity-75 mb-4">@lang('general::lang.get_ready_for_experience')</p>
            <button class="btn btn-warning btn-lg rounded-pill px-5 fw-bold" id="startBtn">@lang('general::lang.start_now')</button>
        </div>
    </div>

    <nav class="navbar-custom px-3 d-flex align-items-center justify-content-between">
        <div id="themeToggle" class="btn btn-light rounded-circle shadow-sm"><i class="fas fa-moon"></i></div>
        <div class="delivery-status">
            <span class="badge rounded-pill bg-success px-3 py-2">@lang('general::lang.open')</span>
        </div>
        <a href="{{ route('set_locale', ['locale' => session('locale') == 'ar' ? 'en' : 'ar']) }}" class="btn btn-light rounded-circle shadow-sm">
            <img src="/assets/media/flags/{{ session('locale') == 'ar' ? 'saudi-arabia.svg' : 'united-states.svg' }}" width="20">
        </a>
    </nav>

    <div class="menu-content" id="menuContent">
        <header class="profile-header">
            <div class="cover-image" style="background-image: url('{{ asset('11.jpeg') }}');"></div>
            <div class="logo-container">
                <img src="{{ config('app.domain') . '/storage/' . $company->logo }}" alt="{{ $company->name }}">
            </div>
            <div class="text-center px-3">
                <h2 class="fw-bold mb-1">{{ $title }}</h2>
                <p class="text-muted small">{{ $subTitle }}</p>
                <div class="d-flex justify-content-center gap-3 mt-2">
                    <a href="#" class="text-success fs-4"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" class="text-danger fs-4"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-primary fs-4"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </header>

        <main class="container-fluid mt-4">
            <div class="sticky-categories">
                <div class="d-flex overflow-auto gap-2 px-2 pb-2 no-scrollbar" style="-ms-overflow-style: none; scrollbar-width: none;">
                    @foreach ($categories as $category)
                        <a href="#category-{{ $category->id }}" class="category-card shadow-sm">
                            <span class="category-title d-block fw-bold small text-nowrap">
                                {{ $local == 'ar' ? $category->name_ar : $category->name_en }}
                            </span>
                        </a>
                    @endforeach
                </div>

                <div class="px-3 mt-3">
                    <div class="search-container d-flex align-items-center">
                        <i class="bi bi-search text-muted me-2"></i>
                        <input type="text" id="searchInput" class="form-control border-0 bg-transparent shadow-none" placeholder="@lang('general::lang.search_placeholder')">
                        <div class="btn-group ms-2">
                            <button class="btn btn-sm btn-light rounded-pill" data-view="grid-2"><i class="bi bi-grid"></i></button>
                            <button class="btn btn-sm btn-light rounded-pill active" data-view="grid-4"><i class="bi bi-grid-3x3-gap"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="no-results mt-5 text-center" id="noResults" style="display:none;">
                <i class="bi bi-search fs-1 text-muted"></i>
                <h4 class="mt-3">@lang('general::lang.no_results')</h4>
            </div>

            @foreach ($categories as $category)
                @if ($category->products->count() > 0)
                    <section id="category-{{ $category->id }}" class="category-section mt-5 px-2">
                        <h4 class="fw-bold mb-4 border-start border-warning border-4 ps-2">
                            {{ $local == 'ar' ? $category->name_ar : $category->name_en }}
                        </h4>
                        <div class="products-wrapper grid-4" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                            @foreach ($category->products as $product)
                                <div class="product-card">
                                    <div class="product-img-wrapper">
                                        <img src="{{ asset($product->image) }}" alt="{{ $product->name_ar }}">
                                        @if($product->calories)
                                            <span class="position-absolute top-0 end-0 m-2 badge bg-dark bg-opacity-50 rounded-pill">
                                                {{ (int)$product->calories }} <small>Kcal</small>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="p-3">
                                        <h6 class="fw-bold mb-1 card-title">{{ $local == 'ar' ? $product->name_ar : $product->name_en }}</h6>
                                        <p class="text-muted small mb-2 text-truncate-2" style="height: 38px;">
                                            {{ $local == 'ar' ? $product->description_ar : $product->description_en }}
                                        </p>
                                        
                                        @if($product->allergens)
                                            <div class="mb-3 d-flex flex-wrap gap-1">
                                                @php $allergens = is_array($product->allergens) ? $product->allergens : json_decode($product->allergens, true); @endphp
                                                @if($allergens)
                                                    @foreach($allergens as $allergen)
                                                        <span class="allergen-icon" title="{{ $allergen['label'] ?? '' }}">
                                                            {{ $allergen['icon'] ?? '⚠️' }}
                                                        </span>
                                                    @endforeach
                                                @endif
                                            </div>
                                        @endif

                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <span class="price-badge">{{ number_format($product->price_with_tax, 2) }} <small>@lang('general::lang.currency')</small></span>
                                            <button class="btn btn-warning btn-sm rounded-circle"><i class="bi bi-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Welcome Screen
            $('#startBtn').click(function() {
                $('#welcomeScreen').addClass('hide');
                setTimeout(() => { $('#menuContent').fadeIn(); }, 400);
            });

            // Theme Toggle
            $('#themeToggle').click(function() {
                $('body').toggleClass('dark-mode');
                const icon = $(this).find('i');
                icon.toggleClass('fa-moon fa-sun');
            });

            // Search Functionality
            $('#searchInput').on('keyup', function() {
                let val = $(this).val().toLowerCase().trim();
                let results = false;
                
                $('.product-card').each(function() {
                    let text = $(this).find('.card-title').text().toLowerCase();
                    let match = text.includes(val);
                    $(this).toggle(match);
                    if(match) results = true;
                });

                $('.category-section').each(function() {
                    let hasProducts = $(this).find('.product-card:visible').length > 0;
                    $(this).toggle(hasProducts);
                });

                $('#noResults').toggle(!results);
            });

            // View Switcher
            $('[data-view]').click(function() {
                let view = $(this).data('view');
                $('.products-wrapper').css('grid-template-columns', view === 'grid-2' ? 'repeat(2, 1fr)' : 'repeat(4, 1fr)');
                $('[data-view]').removeClass('active');
                $(this).addClass('active');
            });

            // Simple Scroll Spy for categories
            $(window).scroll(function() {
                let scrollPos = $(document).scrollTop() + 150;
                $('.category-section').each(function() {
                    let top = $(this).offset().top;
                    let bottom = top + $(this).outerHeight();
                    if (scrollPos >= top && scrollPos <= bottom) {
                        let id = $(this).attr('id');
                        $('.category-card').removeClass('active');
                        $(`a[href="#${id}"]`).addClass('active');
                    }
                });
            });
        });
    </script>
</body>
</html>
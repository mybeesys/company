<!DOCTYPE html>

@php
    $local = app()->currentLocale();
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
    $rtl_files = $local == 'ar' ? '.rtl' : '';
    $menu_placement_x = $local == 'ar' ? 'right-start' : 'left-start';
    $menu_placement_y = $local == 'ar' ? 'bottom-start' : 'bottom-end';
@endphp
<html lang="{{ $local }}" direction="{{ $dir }}" dir="{{ $dir }}"
    style="direction: {{ $dir }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $establishment->name }} - @lang('reservation::lang.menu')</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="/assets/media/logos/1-14.png" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        :root {
            --primary-color: #ffffff;
            --secondary-color: #ffffff;
            --text-color: #333333;
            --bg-color: #ffffff;
            --card-bg: #ffffff;
            --success-color: #28a745;
            --switch-bg: #ccc;
            --switch-checked: #f9f9f9;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --navbar-height: 35px;
            --border-color: #e0e0e0;
            --muted-text: #666666;
            --icon-bg: #f7fbff;
            --icon-color: #00a7d3;
            --category-bg: #f5f7fa;
            --category-text: #4a6fa5;
            --category-border: #d3dce6;
            --search-bg: #ffffff;
            --search-border: #d3dce6;
            --category-card-bg: #ffffff;
            --category-card-text: #ffffff;
            --category-overlay: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent);
            --restaurant-name: #333333;
            --restaurant-desc: #666666;
            --product-card-bg: #ffffff;
            --product-title: #333333;
            --product-price: #28a745;
        }

        body.dark-mode {
            --bg-color: #1a1a2e;
            --text-color: #e0e0e0;
            --card-bg: #2d3748;
            --success-color: #38a169;
            --switch-bg: #4a5568;
            --switch-checked: #4a5568;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
            --border-color: #4a5568;
            --muted-text: #a0aec0;
            --icon-bg: #2d3748;
            --icon-color: #63b3ed;
            --category-bg: #2d3748;
            --category-text: #e0e0e0;
            --category-border: #4a5568;
            --search-bg: #2d3748;
            --search-border: #4a5568;
            --category-card-bg: #2d3748;
            --category-card-text: #e0e0e0;
            --category-overlay: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            --restaurant-name: #e0e0e0;
            --restaurant-desc: #a0aec0;
            --product-card-bg: #2d3748;
            --product-title: #e0e0e0;
            --product-price: #68d391;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            color: var(--text-color);
            transition: background-color 0.3s, color 0.3s;
        }

        .main_page_nav_and_event_image {
            position: relative;
            margin-bottom: 1px;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--text-color);
            height: var(--navbar-height);
            box-shadow: var(--shadow);
            border-radius: 0 0 8px 8px;
            display: flex;
            align-items: center;
        }

        .navbar-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 0 10px;
            height: 100%;
        }

        .left,
        .right {
            display: flex;
            align-items: center;
            height: 100%;
        }

        .right {
            justify-content: flex-end;
        }

        .title {
            text-align: center;
            flex-grow: 1;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .delivery-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 100%;
        }

        .delivery-status h5 {
            margin: 0;
            font-weight: 500;
            font-size: 14px;
            color: var(--text-color);
        }

        .merchant_opening_status {
            background-color: var(--success-color);
            padding: 2px 10px;
            border-radius: 15px;
            font-weight: 500;
            font-size: 13px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            animation: pulse 2s infinite;
            color: white;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
            }

            70% {
                box-shadow: 0 0 0 5px rgba(40, 167, 69, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }

        .switch-container {
            display: flex;
            align-items: center;
            gap: 6px;
            height: 100%;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 20px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--switch-bg);
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 14px;
            width: 14px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background-color: var(--switch-checked);
        }

        input:checked+.slider:before {
            transform: translateX(20px);
        }

        .switch-label {
            color: var(--text-color);
            font-size: 12px;
        }

        .language-btn {
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
            color: var(--text-color);
            padding: 5px 12px;
            border-radius: 15px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            height: 25px;
        }

        .language-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-color);
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
        }

        @media (max-width: 768px) {
            .navbar-inner {
                position: relative;
                padding: 0 5px;
            }

            .menu-toggle {
                display: block;
            }

            .left,
            .title,
            .right {
                position: static;
                width: auto;
            }

            .left {
                order: 1;
                flex: 1;
                justify-content: flex-start;
            }

            .title {
                order: 2;
                flex: 2;
                justify-content: center;
            }

            .right {
                order: 3;
                flex: 1;
                justify-content: flex-end;
            }

            .delivery-status {
                flex-direction: row;
                gap: 5px;
            }

            .delivery-status h5 {
                font-size: 13px;
            }

            .merchant_opening_status {
                font-size: 12px;
                padding: 2px 8px;
            }

            .switch-label {
                display: none;
            }

            .language-btn span {
                display: none;
            }

            .language-btn i {
                margin: 0;
            }
        }

        @media (max-width: 480px) {
            .delivery-status h5 {
                display: none;
            }
        }

        .content {
            position: relative;
        }

        .profile-bx {
            position: relative;
            width: 100%;
            margin-bottom: 20px;
        }

        .contact-background-main {
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            position: relative;
            height: 350px;
            border-radius: 0 0 15px 15px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .profile-content {
            height: 100%;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.5));
        }

        .center-info-outer {
            position: absolute;
            bottom: -50px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            z-index: 10;
        }

        .author {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            border: 5px solid var(--card-bg);
            box-shadow: var(--shadow);
            background: var(--card-bg);
        }

        .author img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .restaurant-info {
            text-align: center;
            margin-top: 10px;
            padding: 0 15px;
        }

        .restaurant-name {
            font-size: 24px;
            font-weight: 700;
            margin: 0px 0 5px;
            color: var(--restaurant-name);
        }

        .restaurant-description {
            font-size: 16px;
            color: var(--restaurant-desc);
            margin-bottom: 10px;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
        }

        .action-btn {
            padding: 10px 20px;
            border-radius: 25px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .primary-btn {
            background: #4a6fa5;
            color: white;
        }

        .secondary-btn {
            background: #f1f1f1;
            color: #333;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .menu-section {
            margin: 30px 15px;
            padding: 20px;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
            color: var(--text-color);
        }

        .menu-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .menu-item {
            background: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s;
            box-shadow: var(--shadow);
        }

        .menu-item:hover {
            transform: translateY(-5px);
        }

        .item-image {
            height: 160px;
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .menu-item:hover .item-image img {
            transform: scale(1.05);
        }

        .item-info {
            padding: 15px;
        }

        .item-name {
            font-weight: 600;
            margin: 0 0 8px;
            color: var(--text-color);
        }

        .item-description {
            color: var(--muted-text);
            font-size: 14px;
            margin: 0 0 5px;
        }

        .icon-list {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }

        .icon-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .icon-item i {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .products-wrapper.grid-1 {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
        }

        .products-wrapper.grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
        }

        .products-wrapper.grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
        }

        @media (max-width: 768px) {
            .products-wrapper.grid-4 {
                grid-template-columns: repeat(2, 1fr);
            }

            .categories-container {
                padding: 0px 0px;
            }
        }

        .categories-container {
            padding: 0px 80px;
        }

        @media (max-width: 576px) {

            .products-wrapper.grid-4,
            .products-wrapper.grid-2 {
                grid-template-columns: repeat(1, 1fr);
            }

            .categories-container {
                padding: 0px 0px;
            }
        }

        .category-link {
            background-color: var(--category-bg);
            color: var(--category-text);
            border: 1px solid var(--category-border);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .category-link:hover,
        .category-link.active {
            background-color: #4a6fa5;
            color: #fff;
            box-shadow: 0 3px 8px rgba(74, 111, 165, 0.3);
            transform: translateY(-2px);
        }

        .custom-scroll::-webkit-scrollbar {
            height: 6px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background-color: #4a6fa5;
            border-radius: 10px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            background: #e9eef5;
        }

        body.dark-mode .custom-scroll::-webkit-scrollbar-track {
            background: #2d3748;
        }

        .view-btn {
            background-color: var(--category-bg);
            color: var(--category-text);
            padding: 8px 15px;
            font-size: 18px;
            transition: all 0.3s ease;
            border: 1px solid var(--category-border);
        }

        .view-btn:hover,
        .view-btn.active {
            background-color: #4a6fa5;
            color: #fff;
        }

        .search-input {
            background-color: var(--search-bg);
            border: 1px solid var(--search-border);
            color: var(--text-color);
        }

        .search-input:focus {
            border-color: #4a6fa5;
            box-shadow: 0 0 8px rgba(74, 111, 165, 0.3);
            outline: none;
            background-color: var(--search-bg);
            color: var(--text-color);
        }

        .category-card {
            display: block;
            position: relative;
            width: 150px;
            height: 80px;
            border-radius: 12px;
            overflow: hidden;
            flex-shrink: 0;
            text-decoration: none;
            transition: transform 0.3s ease;
            box-shadow: var(--shadow);
        }

        .category-card:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 8px rgba(74, 111, 165, 0.3);
        }

        .category-card .image-wrapper {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .category-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .category-card .overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--category-overlay);
        }

        .category-card .category-title {
            position: absolute;
            bottom: 10px;
            left: 0;
            width: 100%;
            text-align: center;
            color: var(--category-card-text);
            font-size: 16px;
            font-weight: bold;
            padding: 0 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .icon-menu-container {
            gap: 25px;
            padding: 15px 0;
        }

        .icon-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 70px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.25s ease;
        }

        .icon-item:hover {
            transform: translateY(-4px);
        }

        .icon-circle {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background-color: var(--icon-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow);
            margin-bottom: 6px;
            transition: background-color 0.3s ease;
        }

        .icon-circle i {
            font-size: 22px;
            color: var(--icon-color);
        }

        .icon-item:hover .icon-circle {
            background-color: var(--icon-bg);
            filter: brightness(0.9);
        }

        .icon-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--icon-color);
            margin: 0;
            line-height: 1.2;
            white-space: normal;
            text-align: center;
            max-width: 70px;
            min-height: 28px;
        }

        .categories-bar {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
        }

        .product-card {
            background-color: var(--product-card-bg);
            box-shadow: var(--shadow);
            border-radius: 13px;
            overflow: hidden;
        }

        .card-title {
            color: var(--product-title);
        }

        .text-muted {
            color: var(--muted-text) !important;
        }

        .text-success {
            color: var(--product-price) !important;
        }

        .form-control {
            background-color: var(--search-bg);
            border-color: var(--search-border);
            color: var(--text-color);
        }

        .form-control:focus {
            background-color: var(--search-bg);
            border-color: #4a6fa5;
            color: var(--text-color);
        }
    </style>

</head>

<body>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <div class="main_page_nav_and_event_image">
        <div class="navbar navbar-style-1">
            <div class="navbar-inner">
                <div class="left">
                    <div class="switch-container">
                        <label class="switch">
                            <input type="checkbox" id="switchForDark">
                            <span class="slider"></span>
                        </label>
                        <span class="switch-label">@lang('reservation::lang.night_mode')</span>
                    </div>
                </div>

                <div class="title with-shadow withStatuses">
                    <div class="delivery-status">
                        <h5>@lang('reservation::lang.place'):</h5>
                        <h4>
                            <span class="label label-success merchant_opening_status">@lang('reservation::lang.open')</span>
                        </h4>
                    </div>
                </div>

                <div class="right">
                    <a href="{{ route('set_locale', ['locale' => session('locale') == 'ar' ? 'en' : 'ar']) }}"
                        class="language-btn country-flag language-selection" id="languageButton">
                        <img class=" rounded-1 ms-2" style="width: 65px;"
                            src="/assets/media/flags/{{ session('locale') == 'ar' ? 'saudi-arabia.svg' : 'united-states.svg' }}"
                            alt="Language Flag" />
                    </a>

                    {{-- <button class="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button> --}}
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="profile-bx">
            <div class="contact-background-main"
                style="background-image: url('{{ asset('11.jpeg') }}');">
                <div class="profile-content"></div>
            </div>

            <div class="center-info-outer">
                <div class="author">
                    <img src="{{ asset($company->logo) }}" alt="{{ $company->name }}">
                </div>
            </div>
        </div>

        <div class="restaurant-info " style="margin-top: 55px">

            <div class="">
                <div class="row">
                    <div class="col-sm-4 col-md-4">
                        <h1 class="restaurant-name">{{ $title }}</h1>
                        <p class="restaurant-description">{{ $subTitle }}</p>
                        <div class="icon-list">
                            <div class="icon-item">
                                <i class="fab fa-whatsapp" style="color: #25D366;"></i>
                            </div>
                            <div class="icon-item">
                                <i class="fab fa-instagram" style="color: #E1306C;"></i>
                            </div>
                            <div class="icon-item">
                                <i class="fab fa-snapchat-ghost" style="color: #FFFC00;"></i>
                            </div>
                            <div class="icon-item">
                                <i class="fab fa-twitter" style="color: #1DA1F2;"></i>
                            </div>
                            <div class="icon-item">
                                <i class="fab fa-facebook-f" style="color: #3b5998;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4 col-md-2">
                    </div>
                    <div class="col-sm-4 col-md-6">
                        <div class="icon-menu-container d-flex flex-wrap justify-content-center">
                            @php
                                $items = [
                                    ['icon' => 'fa-solid fa-utensils', 'title' => __('reservation::lang.todays_menu')],
                                    ['icon' => 'fa-solid fa-location-dot', 'title' => __('reservation::lang.location')],
                                    ['icon' => 'fa-solid fa-book-open', 'title' => __('reservation::lang.smart_menu')],
                                    [
                                        'icon' => 'fa-solid fa-drumstick-bite',
                                        'title' => __('reservation::lang.allergy_info'),
                                    ],
                                    ['icon' => 'fa-solid fa-image', 'title' => __('reservation::lang.photos')],
                                    ['icon' => 'fa-solid fa-comment-dots', 'title' => __('reservation::lang.feedback')],
                                    ['icon' => 'fa-solid fa-circle-info', 'title' => __('reservation::lang.info')],
                                ];
                            @endphp

                            @foreach ($items as $item)
                                <div class="icon-item text-center">
                                    <div class="icon-circle">
                                        <i class="{{ $item['icon'] }}"></i>
                                    </div>
                                    <p class="icon-title">{{ $item['title'] }}</p>
                                </div>
                            @endforeach
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <div class="categories-container" style="margin-top: 40px;">

            <div class="container-fluid my-15" style="">

                <div class="categories-bar sticky-top pt-1" style="z-index: 1050;">

                    <div class="d-flex flex-nowrap overflow-auto custom-scroll py-1" style="gap: 12px;">
                        @foreach ($categories as $category)
                            @php
                                $firstProduct = $category->products->first();
                                $imageUrl =
                                    $firstProduct && $firstProduct->image
                                        ? asset('src/media/demo/2600x1200/101.jpeg')
                                        : asset('menuplacholder.jpg');
                            @endphp

                            <a href="#category-{{ $category->id }}" class="category-card">
                                <div class="image-wrapper">
                                    <img src="{{ $imageUrl }}"
                                        alt="                              {{ app()->currentLocale() == 'ar' ? $category->name_ar : $category->name_en }}
">
                                    <div class="overlay"></div>
                                    <h5 class="category-title">
                                        {{ app()->currentLocale() == 'ar' ? $category->name_ar : $category->name_en }}

                                    </h5>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-between align-items-center my-1 flex-wrap" style="gap: 10px;">

                        <div class="flex-grow-1 position-relative" style="max-width: 85%;">
                            <input type="text" id="searchInput" class="form-control search-input"
                                placeholder="@lang('reservation::lang.search_placeholder')"
                                style="
                    border-radius: 10px;
                    padding: 5px 45px 5px 15px;
                    font-size: 15px;
                    box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
                ">
                            <i class="bi bi-search position-absolute"
                                style="right: 15px; top: 50%; transform: translateY(-50%); font-size: 18px; color: #4a6fa5;"></i>
                        </div>

                        <div class="btn-group shadow-sm rounded-pill my-1" role="group" style="overflow: hidden;">
                            <button type="button" class="btn view-btn" data-view="grid-2"
                                title="@lang('reservation::lang.grid_view_2')">
                                <i class="bi bi-grid-3x3-gap-fill"></i>
                            </button>
                            <button type="button" class="btn view-btn" data-view="grid-4"
                                title="@lang('reservation::lang.grid_view_4')">
                                <i class="bi bi-grid-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>

                @foreach ($categories as $category)
                    @if ($category->products->count() > 0)
                        <div id="category-{{ $category->id }}" class="my-5 px-2 category-section">
                            <h5 class="fw-bold mb-3"><i class="fa-solid fa-utensils"></i>
                                {{ app()->currentLocale() == 'ar' ? $category->name_ar : $category->name_en }}
                            </h5>

                            <div class="products-wrapper grid-4" style="display: grid; gap: 20px;">
                                @foreach ($category->products as $product)
                                    <div class="product-card card border-0 p-2">
                                        <img src="{{ asset('src/media/demo/2600x1200/101.jpeg') }}"
                                            class="card-img-top rounded" alt="{{ $product->name_ar }}"
                                            style="height: 200px; object-fit: cover;">
                                        <div class="card-body p-2">

                                            <h6 class="card-title mb-1 fw-bold">
                                                {{ app()->currentLocale() == 'ar' ? $product->name_ar : $product->name_en }}
                                            </h6>
                                            <p class="text-muted mb-2"
                                                style="margin: 0;font-weight: 400;min-height: 25px; white-space: nowrap;overflow: hidden;text-overflow: ellipsis;">
                                                {{ app()->currentLocale() == 'ar' ? $product->description_ar : $product->description_en }}
                                                @if (!empty($product->calories) && is_numeric($product->calories))
                                                    (@lang('reservation::lang.calories') {{ (int) $product->calories }})
                                                @endif
                                            </p>

                                            <div class="fw-bold text-success" style="font-size: 16px;">
                                                {{ number_format($product->price_with_tax, 2) }} @lang('reservation::lang.currency')
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <script>
            $('#searchInput').on('keyup', function() {
                let value = $(this).val().toLowerCase();
                $('.product-card').filter(function() {
                    $(this).toggle($(this).find('.card-title').text().toLowerCase().indexOf(value) > -1);
                });
            });

            $('.btn-group button').on('click', function() {
                let view = $(this).data('view');
                $('.products-wrapper').removeClass('grid-1 grid-2 grid-4').addClass(view);
            });
        </script>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"
        integrity="sha512-fKcyo0o+5m6fypWn+0n0n0x5f+7l7z+J0Uitc5Y+JyzE5pytXGlA5nyp5jQ17p9pQ1vKaA8kqk0/1LD4GfpJYQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        const switchElement = document.getElementById('switchForDark');

        switchElement.addEventListener('change', function() {
            document.body.classList.toggle('dark-mode', this.checked);
            localStorage.setItem('darkMode', this.checked);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const darkMode = localStorage.getItem('darkMode') === 'true';
            switchElement.checked = darkMode;
            document.body.classList.toggle('dark-mode', darkMode);

            const currentLang = localStorage.getItem('language') || 'ar';
            updateLanguageButton(currentLang);
        });

        function updateLanguageButton(lang) {
            const languageText = document.getElementById('languageText');
            const languageButton = document.getElementById('languageButton');

            if (lang === 'ar') {
                languageText.textContent = 'English';
                languageButton.setAttribute('data-id', 'en');
            } else {
                languageText.textContent = 'العربية';
                languageButton.setAttribute('data-id', 'ar');
            }
        }

        const languageButton = document.getElementById('languageButton');
        languageButton.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';

            const currentLang = localStorage.getItem('language') || 'ar';
            const newLang = currentLang === 'ar' ? 'en' : 'ar';

            localStorage.setItem('language', newLang);

            setTimeout(() => {
                window.location.reload();
            }, 200);
        });

        const menuToggle = document.querySelector('.menu-toggle');
        menuToggle.addEventListener('click', function() {
            alert('@lang('reservation::lang.side_menu_opening')');
        });
    </script>
</body>

</html>

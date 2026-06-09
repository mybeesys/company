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
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $establishment->name }} - @lang('general::lang.menu')</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/emran-alhaddad/Saudi-Riyal-Font@v1.1.1/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @if ($dir === 'rtl')
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    @else
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif
    <link rel="shortcut icon" href="/assets/media/logos/1-14.png" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="/assets/css/brand-theme.css" rel="stylesheet" type="text/css" />
    <link href="/assets/css/menu-simple-pro.css" rel="stylesheet" type="text/css" />

    <style>
        :root {
            --brand-primary: #e9b71f;
            --brand-primary-rgb: 233, 183, 31;
            --brand-accent: #c99a19;
            --brand-accent-deep: #946f11;
            --brand-accent-light: #f0c94a;
            --brand-surface: #fdf8e8;
            --brand-surface-2: #fff4cc;
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
            --icon-bg: var(--brand-surface);
            --icon-color: var(--brand-accent-deep);
            --category-bg: var(--brand-surface);
            --category-text: var(--brand-accent-deep);
            --category-border: #eed592;
            --search-bg: #ffffff;
            --search-border: #e8d9a8;
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
            --icon-color: var(--brand-accent-light);
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

        .sar-currency {
            font-family: 'saudi_riyal', 'Tajawal', sans-serif;
            font-variant-numeric: tabular-nums;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            color: var(--text-color);
            transition: background-color 0.3s, color 0.3s;
        }

        .menu-simple-body {
            text-align: start;
        }

        /* —— RTL layout fixes (Bootstrap RTL + dir on body) —— */
        [dir="rtl"] .slider:before {
            left: auto;
            right: 3px;
        }

        [dir="rtl"] .switch input:checked+.slider:before {
            transform: translateX(-20px);
        }

        .main_page_nav_and_event_image {
            position: relative;
            margin-bottom: 1px;
        }

        .navbar {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.92), rgba(255, 249, 232, 0.92));
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: var(--text-color);
            min-height: 46px;
            box-shadow: 0 8px 26px rgba(15, 23, 42, 0.08);
            border-radius: 0 0 14px 14px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
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
            gap: 10px;
            height: 100%;
        }

        .delivery-status h5 {
            margin: 0;
            font-weight: 500;
            font-size: 14px;
            color: var(--text-color);
        }

        .merchant_opening_status {
            padding: 5px 12px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 13px;
            color: white;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.15);
            animation: pillFloat 2.8s ease-in-out infinite;
            letter-spacing: .2px;
        }

        .merchant_opening_status.is-open {
            background: linear-gradient(135deg, #16a34a, #22c55e);
        }

        .merchant_opening_status.is-closed {
            background: linear-gradient(135deg, #ef4444, #f97316);
        }

        .merchant_opening_status.is-unknown {
            background: linear-gradient(135deg, #64748b, #94a3b8);
        }

        .opening-hours-hint {
            font-size: 11px;
            color: var(--muted-text);
            white-space: nowrap;
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @keyframes pillFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-2px); }
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


      .icon-item.disabled-icon {
        opacity: 0.3;
        cursor: not-allowed;
        filter: grayscale(100%);
    }
    .icon-list a {
        text-decoration: none;
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


        @media (max-width: 768px) {

            .products-wrapper.grid-2 {
                display: grid;
                grid-template-columns: repeat(1, 1fr);
            }

            .products-wrapper.grid-4 {
                grid-template-columns: repeat(2, 1fr);
            }

            .navbar-inner {
                position: relative;
                padding: 0 5px;
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
                padding-top: 8px;
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
            .products-wrapper.grid-2 {
                display: grid;
                grid-template-columns: repeat(1, 1fr);
            }

            .products-wrapper.grid-4 {
                grid-template-columns: repeat(2, 1fr);
            }

            .delivery-status h5 {
                display: none;
            }

            .card-img-top {
                /* padding: 0px 0px; */
                height: 119px !important;
            }
        }

        .theme-toggle {
            width: 30px;
            height: 30px;
            background: linear-gradient(145deg, #f8fafc, #e2e8f0);
            border-radius: 12px;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.12);
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            position: relative;
            transition: all 0.4s ease;
            margin-inline-end: 10px;
        }

        .theme-toggle:hover {
            transform: translateY(-1px) scale(1.04);
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.16);
        }

        .theme-toggle i {
            font-size: 20px;
            position: absolute;
            transition: all 0.5s ease;
            color: var(--icon-color);
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
            background: var(--brand-accent);
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
            gap: 0px;
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
            .products-wrapper.grid-2 {
                display: grid;
                grid-template-columns: repeat(1, 1fr);
            }

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

            .products-wrapper.grid-2 {
                display: grid;
                grid-template-columns: repeat(1, 1fr);
            }

            .products-wrapper.grid-4 {
                grid-template-columns: repeat(2, 1fr);
            }

            .categories-container {
                padding: 0px 0px;
            }

            .card-img-top {
                /* padding: 0px 0px; */
                height: 119px !important;
            }
        }

        @media (max-width: 768px) {

            /* .products-wrapper.grid-4,
            .products-wrapper.grid-2 {
                grid-template-columns: repeat(1, 1fr);
            } */

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
            background-color: var(--brand-accent);
            color: #fff;
            box-shadow: 0 3px 8px rgba(var(--brand-primary-rgb), 0.3);
            transform: translateY(-2px);
        }

        .custom-scroll::-webkit-scrollbar {
            height: 6px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background-color: var(--brand-accent);
            border-radius: 10px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            background: var(--brand-surface);
        }

        body.dark-mode .custom-scroll::-webkit-scrollbar-track {
            background: #2d3748;
        }

        .view-btn {
            background-color: var(--category-bg);
            color: var(--category-text);
            padding: 3px 14px;
            font-size: 18px;
            transition: all 0.3s ease;
            /* border: 1px solid var(--category-border); */
        }

        .view-btn:hover,
        .view-btn.active {
            background-color: var(--brand-accent);
            color: #fff;
        }

        .search-input {
            background-color: var(--search-bg);
            /* border: 1px solid var(--search-border); */
            color: var(--text-color);
        }

        .search-input:focus {
            border-color: var(--brand-accent);
            /* box-shadow: 0 0 8px rgba(var(--brand-primary-rgb), 0.3); */
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
            box-shadow: 0 3px 8px rgba(var(--brand-primary-rgb), 0.3);
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
            border-color: var(--brand-accent);
            color: var(--text-color);
        }

        .language-switch {
            width: 34px;
            height: 34px;
            background: linear-gradient(145deg, #f8fafc, #e2e8f0);
            border-radius: 12px;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.12);
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.25s ease;
            cursor: pointer;
            margin-left: 10px;
        }

        .language-switch:hover {
            transform: translateY(-1px) scale(1.04);
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.16);
        }

        .language-switch img {
            width: 24px;
            height: 24px;
            border-radius: 7px;
        }

        body.dark-mode .navbar {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.88), rgba(15, 23, 42, 0.9));
            border-bottom-color: rgba(148, 163, 184, 0.25);
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.32);
        }

        body.dark-mode .theme-toggle,
        body.dark-mode .language-switch {
            background: linear-gradient(145deg, #334155, #1e293b);
        }

        .no-results {
            text-align: center;
            padding: 40px 20px;
            display: none;
        }

        .no-results i {
            font-size: 60px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .no-results h4 {
            color: var(--text-color);
            margin-bottom: 10px;
        }

        .no-results p {
            color: var(--muted-text);
        }

        .category-section.hidden {
            display: none !important;
        }

        .product-card.hidden {
            display: none !important;
        }


        .welcome-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background-color: #1a1a2e;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            z-index: 9999;
            transition: opacity 0.8s ease-in-out;
        }

        .welcome-screen::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.55);
            z-index: 1;
        }

        .welcome-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: #fff;
        }

        .welcome-content img {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.6);
            margin-bottom: 20px;
            animation: bounce 1.5s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        .welcome-content h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .welcome-content p {
            font-size: 18px;
            margin-bottom: 25px;
        }

        .start-btn {
            background-color: #ebb81e;
            color: white;
            border: none;
            padding: 12px 35px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }

        .start-btn:hover {
            background-color: #ebb81e;
            transform: scale(1.05);
        }

        .welcome-screen.hide {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .menu-content {
            display: none;
            /* padding: 20px; */
            animation: fadeIn 1s ease-in-out;
        }

        .menu-content.show {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* —— Hero: full cover + content below image —— */
        .menu-hero-wrap {
            position: relative;
            margin-bottom: 1.25rem;
        }

        .menu-hero-cover {
            width: 100%;
            min-height: clamp(240px, 46vh, 520px);
            border-radius: 0 0 24px 24px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            box-shadow: var(--shadow);
        }

        .menu-hero-band {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 16px 8px;
            position: relative;
            z-index: 2;
        }

        .menu-hero-logo-wrap {
            display: flex;
            justify-content: center;
            margin-top: -52px;
            margin-bottom: 0.35rem;
        }

        .menu-hero-logo-wrap .author {
            width: 104px;
            height: 104px;
            border-width: 5px;
        }

        .menu-hero-text {
            text-align: center;
            margin-bottom: 0.25rem;
        }

        [dir="rtl"] .menu-hero-text {
            direction: rtl;
        }

        .menu-hero-text .restaurant-name {
            font-size: clamp(1.35rem, 3.5vw, 1.85rem);
            line-height: 1.25;
            margin-bottom: 0.35rem;
        }

        .menu-hero-text .restaurant-description {
            font-size: clamp(0.95rem, 2.5vw, 1.05rem);
            margin-bottom: 0;
            color: var(--restaurant-desc);
            line-height: 1.45;
        }

        .menu-hero-social-band {
            padding: 0.65rem 0 0.15rem;
        }

        .menu-hero-social-band .icon-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 12px 14px;
        }

        .menu-quick-actions {
            margin-top: 1.1rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px 14px;
            padding-bottom: 0.25rem;
        }

        .menu-action-btn {
            border: none;
            background: linear-gradient(145deg, var(--brand-surface), var(--brand-surface-2));
            border-radius: 16px;
            padding: 12px 14px 8px;
            min-width: 82px;
            box-shadow: 0 6px 18px rgba(var(--brand-primary-rgb), 0.16);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            color: var(--text-color);
        }

        .menu-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 26px rgba(var(--brand-primary-rgb), 0.26);
        }

        .menu-action-btn .icon-circle {
            margin-bottom: 4px;
        }

        .menu-action-btn .icon-title {
            font-size: 11px;
            line-height: 1.2;
        }

        @media (max-width: 767.98px) {
            .menu-action-btn {
                background: var(--card-bg);
                border-radius: 14px;
                padding: 10px 12px 6px;
                min-width: 74px;
                box-shadow: var(--shadow);
            }

            .menu-hero-logo-wrap .author {
                width: 92px;
                height: 92px;
            }
        }

        body.dark-mode .menu-action-btn {
            background: linear-gradient(145deg, #2d3748, #1a202c);
        }

        /* Search: icon respects RTL */
        .search-input-wrap {
            position: relative;
        }

        .search-input-wrap .search-input-icon {
            position: absolute;
            inset-inline-end: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: var(--brand-accent);
            pointer-events: none;
        }

        body.dark-mode .search-input-wrap .search-input-icon {
            color: var(--brand-accent-light);
        }

        .search-input-wrap .form-control {
            padding-inline-start: 15px;
            padding-inline-end: 44px;
        }

        /* Feature modals — location, feedback, info */
        .menu-feature-modal {
            border: none !important;
            border-radius: 22px !important;
            overflow: hidden;
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.18) !important;
        }

        body.dark-mode .menu-feature-modal {
            box-shadow: 0 28px 70px rgba(0, 0, 0, 0.45) !important;
        }

        .menu-feature-modal__hero {
            position: relative;
            text-align: center;
            padding: 1.35rem 1.25rem 1.1rem;
            background: linear-gradient(135deg, var(--brand-accent-deep) 0%, var(--brand-accent) 42%, var(--brand-primary) 100%);
            color: #1e2129;
        }

        .menu-feature-modal__hero .btn-close {
            position: absolute;
            top: 0.85rem;
            inset-inline-end: 0.85rem;
            opacity: 0.75;
        }

        .menu-feature-modal__icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 0.75rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
            background: rgba(255, 255, 255, 0.88);
            color: var(--brand-accent-deep);
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.12);
        }

        .menu-feature-modal__title {
            margin: 0 0 0.35rem;
            font-size: 1.15rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .menu-feature-modal__intro {
            margin: 0 auto;
            max-width: 28rem;
            font-size: 0.88rem;
            line-height: 1.6;
            opacity: 0.92;
        }

        .menu-feature-modal__body {
            padding: 1.15rem 1.25rem 1.35rem;
        }

        .menu-location-hint {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--brand-accent-deep);
            background: rgba(var(--brand-primary-rgb), 0.12);
            border: 1px solid rgba(var(--brand-primary-rgb), 0.22);
            border-radius: 999px;
            padding: 0.4rem 0.85rem;
            margin-top: 0.65rem;
        }

        body.dark-mode .menu-location-hint {
            color: var(--brand-accent-light);
        }

        .menu-location-panel {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 0.9rem;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
        }

        .menu-location-label {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: var(--muted-text);
            margin-bottom: 0.35rem;
        }

        .menu-location-address {
            display: flex;
            align-items: flex-start;
            gap: 0.55rem;
            font-size: 0.92rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.85rem;
            line-height: 1.5;
        }

        .menu-location-address i {
            color: var(--brand-accent);
            margin-top: 0.15rem;
        }

        .menu-location-map-frame {
            border-radius: 14px;
            overflow: hidden;
            border: 2px solid rgba(var(--brand-primary-rgb), 0.22);
            box-shadow: 0 10px 28px rgba(var(--brand-primary-rgb), 0.12);
        }

        .menu-location-open-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 700;
            border-radius: 12px;
            padding: 0.7rem 1rem;
        }

        .menu-feedback-stars-panel {
            background: linear-gradient(145deg, var(--brand-surface), #fffdf8);
            border: 1px solid rgba(var(--brand-primary-rgb), 0.2);
            border-radius: 16px;
            padding: 1rem 0.75rem 0.85rem;
            margin-bottom: 1rem;
            box-shadow: 0 8px 22px rgba(var(--brand-primary-rgb), 0.08);
        }

        body.dark-mode .menu-feedback-stars-panel {
            background: linear-gradient(145deg, #2d2819, #2d3748);
            border-color: rgba(var(--brand-primary-rgb), 0.28);
        }

        .menu-feedback-stars-label {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--muted-text);
            margin-bottom: 0.35rem;
            text-align: center;
        }

        .menu-feedback-textarea {
            border-radius: 14px !important;
            border-color: var(--search-border) !important;
            background: var(--search-bg) !important;
            color: var(--text-color) !important;
            min-height: 108px;
            resize: vertical;
        }

        .menu-feedback-textarea:focus {
            border-color: var(--brand-accent) !important;
            box-shadow: 0 0 0 0.2rem rgba(var(--brand-primary-rgb), 0.18) !important;
        }

        .menu-feedback-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 700;
            border-radius: 12px;
            padding: 0.72rem 1rem;
        }

        /* Feedback stars + hints */
        .feedback-star {
            transition: transform 0.15s ease, filter 0.15s ease;
            line-height: 1;
            text-decoration-line: none;
        }

        .feedback-star:hover {
            transform: scale(1.15);
            filter: drop-shadow(0 2px 6px rgba(255, 193, 7, 0.45));
        }

        .feedback-hint {
            min-height: 1.25rem;
            font-size: 0.875rem;
        }

        .feedback-hint.is-invalid {
            color: #dc3545;
        }

        .feedback-hint.is-ok {
            color: var(--success-color);
        }

        /* Thank-you celebration modal */
        .menu-thanks-modal {
            border-radius: 24px !important;
            overflow: hidden;
            background: linear-gradient(160deg, #fff9f0 0%, #ffffff 45%, var(--brand-surface) 100%);
            box-shadow: 0 24px 60px rgba(var(--brand-primary-rgb), 0.18);
        }

        body.dark-mode .menu-thanks-modal {
            background: linear-gradient(160deg, #2d3748 0%, #1a202c 50%, #2d2819 100%);
        }

        .menu-thanks-icon {
            width: 88px;
            height: 88px;
            margin: 0 auto;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.25rem;
            color: #e85d75;
            animation: thanksPop 0.55s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        body.dark-mode .menu-thanks-icon {
            background: linear-gradient(135deg, var(--brand-accent) 0%, var(--brand-accent-deep) 100%);
            color: #1e2129;
        }

        @keyframes thanksPop {
            from {
                transform: scale(0.3);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .menu-thanks-sparkle {
            font-size: 1.5rem;
            animation: sparkle 1.2s ease-in-out infinite;
        }

        @keyframes sparkle {

            0%,
            100% {
                opacity: 0.5;
                transform: rotate(-6deg) scale(1);
            }

            50% {
                opacity: 1;
                transform: rotate(6deg) scale(1.08);
            }
        }

        .menu-thanks-btn {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-accent));
            border: none;
            color: #1e2129;
            font-weight: 600;
            box-shadow: 0 8px 22px rgba(var(--brand-primary-rgb), 0.35);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .menu-thanks-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(var(--brand-primary-rgb), 0.4);
            color: #1e2129;
        }

        .menu-empty-state {
            max-width: 420px;
            margin: 2rem auto;
            padding: 2rem 1.5rem;
            border-radius: 20px;
            background: linear-gradient(145deg, #fffdf8, var(--brand-surface));
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        body.dark-mode .menu-empty-state {
            background: linear-gradient(145deg, #2d3748, #1a202c);
            border-color: var(--border-color);
        }

        .menu-empty-state .empty-icon {
            font-size: 3rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        /* Company info modal — polished card UI */
        .company-info-modal {
            border-radius: 22px !important;
            overflow: hidden;
            border: none !important;
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.2) !important;
        }

        body.dark-mode .company-info-modal {
            box-shadow: 0 28px 70px rgba(0, 0, 0, 0.45) !important;
        }

        .company-info-hero {
            position: relative;
            padding: 2rem 1.5rem 1.35rem;
            text-align: center;
            background: linear-gradient(135deg, var(--brand-accent-deep) 0%, var(--brand-accent) 45%, var(--brand-primary) 100%);
            color: #1e2129;
        }

        .company-info-hero .company-info-intro {
            margin: 0.5rem auto 0;
            max-width: 24rem;
            font-size: 0.88rem;
            line-height: 1.55;
            opacity: 0.9;
        }

        .company-info-hero::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 28px;
            background: linear-gradient(to bottom, transparent, var(--card-bg));
            pointer-events: none;
        }

        .company-info-logo {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.85);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.25);
            margin-bottom: 0.75rem;
            position: relative;
            z-index: 1;
        }

        .company-info-hero h4 {
            margin: 0;
            font-weight: 700;
            font-size: 1.35rem;
            position: relative;
            z-index: 1;
        }

        .company-info-hero p {
            margin: 0.35rem 0 0;
            opacity: 0.92;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }

        .company-info-body {
            padding: 1.25rem 1.25rem 1.5rem;
            margin-top: -8px;
            position: relative;
            z-index: 2;
        }

        .company-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 12px;
        }

        .company-info-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 14px 16px;
            border-radius: 16px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-inline-start: 3px solid rgba(var(--brand-primary-rgb), 0.55);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .company-info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(var(--brand-primary-rgb), 0.12);
            border-inline-start-color: var(--brand-primary);
        }

        .company-info-item .ci-icon {
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, var(--brand-surface), var(--brand-surface-2));
            color: var(--brand-accent-deep);
            font-size: 1.1rem;
        }

        body.dark-mode .company-info-item .ci-icon {
            background: linear-gradient(145deg, #3d3218, #2d2819);
            color: var(--brand-accent-light);
        }

        .company-info-item .ci-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--muted-text);
            margin-bottom: 0.2rem;
        }

        .company-info-item .ci-value {
            font-weight: 600;
            font-size: 0.98rem;
            color: var(--text-color);
            word-break: break-word;
        }

        .company-info-item .ci-value a {
            color: var(--brand-accent-deep);
            text-decoration: none;
        }

        .company-info-item .ci-value a:hover {
            text-decoration: underline;
        }

        body.dark-mode .company-info-item .ci-value a {
            color: var(--brand-accent-light);
        }

        .company-info-wide {
            grid-column: 1 / -1;
        }

        .product-card-desc {
            margin: 0;
            font-weight: 400;
            min-height: 2.6em;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
            overflow: hidden;
            white-space: normal;
            word-break: break-word;
        }

        /* Product allergens (EU-style disclosure strip) */
        .product-allergens {
            margin-top: 0.65rem;
            padding: 0.7rem 0.8rem 0.75rem;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(254, 243, 199, 0.45), rgba(255, 247, 237, 0.65));
            border: 1px solid rgba(245, 158, 11, 0.32);
            border-inline-start: 4px solid #f59e0b;
            box-shadow: 0 2px 10px rgba(245, 158, 11, 0.08);
        }

        body.dark-mode .product-allergens {
            background: linear-gradient(135deg, rgba(120, 53, 15, 0.35), rgba(30, 27, 19, 0.55));
            border-color: rgba(251, 191, 36, 0.28);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
        }

        .product-allergens-head {
            display: flex;
            align-items: flex-start;
            gap: 0.55rem;
            margin-bottom: 0.45rem;
        }

        .product-allergens-badge {
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(245, 158, 11, 0.2);
            color: #b45309;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }

        body.dark-mode .product-allergens-badge {
            background: rgba(251, 191, 36, 0.15);
            color: #fcd34d;
        }

        .product-allergens-head-text {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            min-width: 0;
        }

        .product-allergens-title {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #b45309;
            line-height: 1.2;
        }

        body.dark-mode .product-allergens-title {
            color: #fcd34d;
        }

        .product-allergens-hint {
            font-size: 0.68rem;
            line-height: 1.35;
            color: #92400e;
            opacity: 0.92;
        }

        body.dark-mode .product-allergens-hint {
            color: #fde68a;
            opacity: 0.88;
        }

        .product-allergen-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .product-allergen-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.28rem 0.6rem 0.28rem 0.5rem;
            font-size: 0.74rem;
            font-weight: 600;
            line-height: 1.2;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(245, 158, 11, 0.28);
            color: #78350f;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        body.dark-mode .product-allergen-chip {
            background: rgba(15, 23, 42, 0.75);
            border-color: rgba(251, 191, 36, 0.22);
            color: #fef3c7;
        }

        .product-allergen-chip-icon {
            font-size: 0.72rem;
            opacity: 0.9;
            color: #d97706;
        }

        body.dark-mode .product-allergen-chip-icon {
            color: #fbbf24;
        }

        .product-card-image-wrap {
            position: relative;
            display: block;
        }

        .product-card-image-wrap > img {
            display: block;
            width: 100%;
        }

        .product-allergen-float-btn {
            position: absolute;
            inset-block-start: 8px;
            inset-inline-end: 8px;
            width: 38px;
            height: 38px;
            padding: 0;
            border: none;
            border-radius: 50%;
            background: linear-gradient(145deg, rgba(254, 243, 199, 0.98), rgba(251, 191, 36, 0.98));
            color: #92400e;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .product-allergen-float-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 6px 22px rgba(245, 158, 11, 0.45);
            color: #78350f;
        }

        .product-allergen-float-btn:focus-visible {
            outline: 2px solid #f59e0b;
            outline-offset: 2px;
        }

        .product-allergen-float-btn--sm {
            width: 32px;
            height: 32px;
            inset-block-start: 6px;
            inset-inline-end: 6px;
            font-size: 0.85rem;
        }

        .product-allergen-icons-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            align-items: center;
            margin: 0.15rem 0 0.4rem;
            min-height: 0;
        }

        .product-allergen-icons-row--sm {
            gap: 0.25rem;
            margin: 0.1rem 0 0.25rem;
        }

        .product-allergen-icon-btn {
            flex-shrink: 0;
            width: 2rem;
            height: 2rem;
            padding: 0;
            border: none;
            border-radius: 10px;
            background: linear-gradient(145deg, rgba(254, 243, 199, 0.95), rgba(251, 191, 36, 0.35));
            color: #92400e;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .product-allergen-icons-row--sm .product-allergen-icon-btn {
            width: 1.65rem;
            height: 1.65rem;
            border-radius: 8px;
        }

        .product-allergen-icons-row--sm .product-allergen-icon-btn i {
            font-size: 0.72rem;
        }

        .product-allergen-icon-btn:hover {
            transform: scale(1.06);
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.35);
        }

        .product-allergen-icon-btn:focus-visible {
            outline: 2px solid #f59e0b;
            outline-offset: 2px;
        }

        .product-card-cal {
            font-size: 0.8125rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .menu-allergen-filter-label {
            color: var(--muted-text) !important;
        }

        .menu-allergen-filter {
            width: 100%;
            max-width: 100%;
        }

        .menu-allergen-filter-toggle {
            border: 1px solid var(--category-border);
            background: var(--search-bg);
            color: var(--text-color);
            border-radius: 12px;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .menu-allergen-filter-toggle:hover {
            border-color: var(--brand-accent);
            color: var(--text-color);
        }

        .menu-allergen-filter-toggle:not(.collapsed) {
            border-color: var(--brand-accent);
            box-shadow: 0 2px 10px rgba(var(--brand-primary-rgb), 0.12);
        }

        .menu-allergen-filter-toggle .fa-filter {
            color: var(--brand-accent);
            opacity: 0.9;
        }

        .menu-allergen-filter-chevron {
            transition: transform 0.25s ease;
            font-size: 1rem;
            color: var(--muted-text);
        }

        .menu-allergen-filter-toggle.collapsed .menu-allergen-filter-chevron {
            transform: rotate(0deg);
        }

        .menu-allergen-filter-toggle:not(.collapsed) .menu-allergen-filter-chevron {
            transform: rotate(-180deg);
        }

        .menu-allergen-filter-badge {
            font-size: 0.65rem;
            min-width: 1.15rem;
        }

        body.dark-mode .menu-allergen-filter-toggle {
            background: var(--search-bg);
            border-color: var(--search-border);
            color: var(--text-color);
        }

        body.dark-mode .menu-allergen-filter-toggle:not(.collapsed) {
            border-color: var(--brand-accent-light);
        }

        body.dark-mode .menu-allergen-filter-toggle .fa-filter {
            color: var(--brand-accent-light);
        }

        .menu-allergen-filter-chips {
            width: 100%;
            max-width: 100%;
            flex-wrap: wrap;
            row-gap: 0.5rem;
            -webkit-overflow-scrolling: touch;
        }

        .allergen-filter-chip {
            border-radius: 999px;
            border: 1px solid var(--category-border);
            background: var(--search-bg);
            color: var(--text-color);
            font-weight: 600;
            line-height: 1.2;
            max-width: 100%;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .allergen-filter-chip:hover {
            border-color: var(--brand-accent);
            box-shadow: 0 2px 8px rgba(var(--brand-primary-rgb), 0.15);
        }

        .allergen-filter-chip.active {
            background: var(--brand-accent);
            color: #fff;
            border-color: var(--brand-accent);
            box-shadow: 0 2px 10px rgba(var(--brand-primary-rgb), 0.35);
        }

        .allergen-filter-chip.active i {
            color: #fff;
        }

        .allergen-filter-chip-label {
            max-width: 9rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 0.7rem;
        }

        @media (max-width: 575.98px) {
            .allergen-filter-chip-label {
                max-width: 5.5rem;
                font-size: 0.65rem;
            }

            .allergen-filter-chip {
                padding-inline: 0.45rem;
                font-size: 0.75rem;
            }
        }

        body.dark-mode .allergen-filter-chip {
            background: var(--search-bg);
            border-color: var(--search-border);
        }

        body.dark-mode .allergen-filter-chip.active {
            background: var(--brand-accent-light);
            color: #1a202c;
            border-color: var(--brand-accent-light);
        }

        body.dark-mode .allergen-filter-chip.active i {
            color: #1a202c;
        }

        .menu-allergen-filter-clear {
            font-size: 0.8rem;
            text-decoration: none;
        }

        .menu-allergen-filter-clear:hover {
            text-decoration: underline;
        }

        #modalProductAllergens .product-allergens {
            margin-top: 0;
        }

        #modalProductAllergens .modal-body {
            padding-top: 0.5rem;
        }

        .menu-modal-placeholder {
            text-align: center;
            padding: 0.35rem 0.5rem 0.25rem;
        }

        .menu-modal-placeholder__icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: var(--brand-accent-deep);
            background: linear-gradient(145deg, var(--brand-surface), var(--brand-surface-2));
            box-shadow: 0 10px 28px rgba(var(--brand-primary-rgb), 0.18);
        }

        body.dark-mode .menu-modal-placeholder__icon {
            color: var(--brand-accent-light);
            background: linear-gradient(145deg, #3d3218, #2d2819);
        }

        .menu-modal-placeholder__title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--text-color);
            margin: 0 0 0.65rem;
            line-height: 1.45;
        }

        .menu-modal-placeholder__text {
            font-size: 0.95rem;
            line-height: 1.7;
            color: var(--muted-text);
            margin: 0 auto 0.85rem;
            max-width: 26rem;
        }

        .menu-modal-placeholder__hint {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--brand-accent-deep);
            background: rgba(var(--brand-primary-rgb), 0.12);
            border: 1px solid rgba(var(--brand-primary-rgb), 0.22);
            border-radius: 999px;
            padding: 0.45rem 0.9rem;
            margin: 0;
            line-height: 1.4;
        }

        body.dark-mode .menu-modal-placeholder__hint {
            color: var(--brand-accent-light);
            background: rgba(var(--brand-primary-rgb), 0.14);
            border-color: rgba(var(--brand-primary-rgb), 0.28);
        }

        .menu-modal-placeholder__social {
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px dashed var(--border-color);
        }

        .menu-modal-placeholder__social-label {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--muted-text);
            margin-bottom: 0.65rem;
        }

        .menu-modal-placeholder__social .icon-list {
            gap: 10px 12px;
        }
    </style>

</head>

<body class="menu-simple-body menu-pro" dir="{{ $dir }}">

    @php
        $coverPath = $socialLinks['menu_cover_image'] ?? null;
        $menuCoverUrl = $coverPath
            ? tenant_public_storage_url_for_db_path((string) $coverPath)
            : asset('11.jpeg');
        if ($coverPath && preg_match('/-(\d+)\.[A-Za-z0-9]+$/', (string) $coverPath, $coverVer)) {
            $menuCoverUrl .= (str_contains($menuCoverUrl, '?') ? '&' : '?') . 'v=' . $coverVer[1];
        }
        $mapEmbedUrl = null;
        if (!empty($mapLat) && !empty($mapLng)) {
            $d = 0.02;
            $mapEmbedUrl =
                'https://www.openstreetmap.org/export/embed.html?bbox=' .
                ($mapLng - $d) .
                ',' .
                ($mapLat - $d) .
                ',' .
                ($mapLng + $d) .
                ',' .
                ($mapLat + $d) .
                '&layer=mapnik&marker=' .
                $mapLat .
                ',' .
                $mapLng;
        }
    @endphp

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


    @php
        $openStatus = data_get($openingState, 'status', 'unknown');
        $openLabel =
            $openStatus === 'open'
                ? __('general::lang.open')
                : ($openStatus === 'closed'
                    ? __('general::lang.closed')
                    : (app()->currentLocale() === 'ar' ? 'غير محدد' : 'N/A'));
        $openClass = $openStatus === 'open' ? 'is-open' : ($openStatus === 'closed' ? 'is-closed' : 'is-unknown');
        $openHours = data_get($openingState, 'hours_text');
    @endphp
    <div class="main_page_nav_and_event_image ">
        <div class="navbar navbar-style-1 px-3">
            <div class="navbar-inner">
                <div class="left">

                    <div class="theme-toggle" id="themeToggle">
                        <i class="sun-icon fas fa-sun"></i>
                        <i class="moon-icon fas fa-moon"></i>
                    </div>

                </div>

                <div class="title with-shadow withStatuses">
                    <div class="delivery-status">
                        <h5>@lang('general::lang.place'):</h5>
                        <span class="merchant_opening_status {{ $openClass }}">{{ $openLabel }}</span>
                        @if (!empty($openHours))
                            <span class="opening-hours-hint" title="{{ $openHours }}">{{ $openHours }}</span>
                        @endif
                    </div>
                </div>

                <div class="right">

                    <a href="{{ route('set_locale', ['locale' => session('locale') == 'ar' ? 'en' : 'ar']) }}"
                        class="language-switch">
                        <img src="/assets/media/flags/{{ session('locale') == 'ar' ? 'saudi-arabia.svg' : 'united-states.svg' }}"
                            alt="Language" />
                    </a>

                </div>
            </div>
        </div>
    </div>


    <div class="welcome-screen" id="welcomeScreen" style="background-image: url('{{ $menuCoverUrl }}');">
        <div class="welcome-content">
            <img src="{{ $companyLogoUrl }}" alt=" ">
            <h1>@lang('general::lang.welcome_to') {{ $company->name }}</h1>
            <p>@lang('general::lang.get_ready_for_experience')</p>
            <button class="start-btn" id="startBtn">@lang('general::lang.start_now')</button>
        </div>
    </div>

    @php
        $menuQuickItems = [
            ['key' => 'todays_menu', 'icon' => 'fa-solid fa-utensils', 'title' => __('general::lang.todays_menu'), 'modal' => 'modalTodaysMenu'],
            ['key' => 'location', 'icon' => 'fa-solid fa-location-dot', 'title' => __('general::lang.location'), 'modal' => 'modalLocation'],
            ['key' => 'smart_menu', 'icon' => 'fa-solid fa-book-open', 'title' => __('general::lang.smart_menu'), 'modal' => 'modalSmartMenu'],
            ['key' => 'allergy_info', 'icon' => 'fa-solid fa-drumstick-bite', 'title' => __('general::lang.allergy_info'), 'modal' => 'modalAllergy'],
            ['key' => 'photos', 'icon' => 'fa-solid fa-image', 'title' => __('general::lang.photos'), 'modal' => 'modalPhotos'],
            ['key' => 'feedback', 'icon' => 'fa-solid fa-comment-dots', 'title' => __('general::lang.feedback'), 'modal' => 'modalFeedback'],
            ['key' => 'info', 'icon' => 'fa-solid fa-circle-info', 'title' => __('general::lang.info'), 'modal' => 'modalCompanyInfo'],
        ];
    @endphp

    <div class="content menu-content" id="menuContent">
        <div class="menu-hero-wrap">
            <div class="menu-hero-cover" style="background-image: url('{{ $menuCoverUrl }}');"></div>
            <div class="menu-hero-band">
                <div class="menu-hero-logo-wrap">
                <div class="author">
                        <img src="{{ $companyLogoUrl }}" alt="{{ $company->name }}">
                </div>
            </div>
                <div class="menu-hero-text px-2">
                        <h1 class="restaurant-name">{{ $title }}</h1>
                        <p class="restaurant-description">{{ $subTitle }}</p>
                </div>
                <div class="menu-hero-social-band">
                        <div class="icon-list">
                            @if (!empty($socialLinks['social_whatsapp']))
                            <a href="{{ $socialLinks['social_whatsapp'] }}" target="_blank" rel="noopener noreferrer" class="icon-item" style="width: 48px;" aria-label="WhatsApp"><i class="fab fa-whatsapp" style="color: #25D366;"></i></a>
                            @endif
                            @if (!empty($socialLinks['social_instagram']))
                            <a href="{{ $socialLinks['social_instagram'] }}" target="_blank" rel="noopener noreferrer" class="icon-item" style="width: 48px;" aria-label="Instagram"><i class="fab fa-instagram" style="color: #E1306C;"></i></a>
                            @endif
                            @if (!empty($socialLinks['social_snapchat']))
                            <a href="{{ $socialLinks['social_snapchat'] }}" target="_blank" rel="noopener noreferrer" class="icon-item" style="width: 48px;" aria-label="Snapchat"><i class="fab fa-snapchat-ghost" style="color: #FFFC00;"></i></a>
                            @endif
                            @if (!empty($socialLinks['social_x']))
                            <a href="{{ $socialLinks['social_x'] }}" target="_blank" rel="noopener noreferrer" class="icon-item" style="width: 48px;" aria-label="X"><i class="fab fa-twitter" style="color: #1DA1F2;"></i></a>
                            @endif
                            @if (!empty($socialLinks['social_facebook']))
                            <a href="{{ $socialLinks['social_facebook'] }}" target="_blank" rel="noopener noreferrer" class="icon-item" style="width: 48px;" aria-label="Facebook"><i class="fab fa-facebook-f" style="color: #3b5998;"></i></a>
                            @endif
                        </div>
                    </div>
                <div class="menu-quick-actions">
                    @foreach ($menuQuickItems as $item)
                        @if ($menuSectionFlags[$item['key']] ?? true)
                            <button type="button" class="menu-action-btn text-center" data-bs-toggle="modal" data-bs-target="#{{ $item['modal'] }}">
                                <div class="icon-circle mx-auto"><i class="{{ $item['icon'] }}"></i></div>
                                <p class="icon-title mb-0">{{ $item['title'] }}</p>
                            </button>
                        @endif
                            @endforeach
                    </div>
                </div>
            </div>

        <div class="categories-container" style="margin-top: 28px;">

            <div class="container-fluid my-15" style="">

                @if ($categories->isEmpty())
                    <div class="menu-empty-state text-center" role="status">
                        <div class="empty-icon"><i class="fa-solid fa-basket-shopping"></i></div>
                        <h5 class="fw-bold mb-2" style="color: var(--restaurant-name);">@lang('reservation::lang.menu_empty_title')</h5>
                        <p class="text-muted small mb-0">@lang('reservation::lang.menu_empty_hint')</p>
                    </div>
                @else
                <div class="categories-bar sticky-top pt-1" style="z-index: 1050;">

                        <div class="d-flex flex-nowrap overflow-auto custom-scroll py-1 categories-strip" style="gap: 12px;">
                        @foreach ($categories as $category)
                            @php
                                $firstProduct = $category->products->first();
                                $imageUrl =
                                    $firstProduct && $firstProduct->image
                                        ? asset($firstProduct->image)
                                        : asset('menuplacholder.jpg');
                            @endphp

                            <a href="#category-{{ $category->id }}" class="category-card">
                                <div class="image-wrapper">
                                    <img src="{{ $imageUrl }}"
                                        alt="{{ app()->currentLocale() == 'ar' ? $category->name_ar : $category->name_en }}">
                                    <div class="overlay"></div>
                                    <h5 class="category-title">
                                        {{ app()->currentLocale() == 'ar' ? $category->name_ar : $category->name_en }}
                                    </h5>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-between align-items-center my-1 flex-wrap" style="gap: 10px;">

                            <div class="flex-grow-1 search-input-wrap" style="max-width: 85%;">
                            <input type="text" id="searchInput" class="form-control search-input"
                                placeholder="@lang('general::lang.search_placeholder')"
                                style="
                    border-radius: 10px;
                        padding-top: 5px;
                        padding-bottom: 5px;
                    font-size: 15px;
                    box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
                ">
                                    <i class="bi bi-search search-input-icon" aria-hidden="true"></i>
                        </div>

                        <div class="btn-group shadow-sm rounded-pill my-1" role="group" style="overflow: hidden;">
                            <button type="button" class="btn view-btn" data-view="grid-2"
                                title="@lang('general::lang.grid_view_2')">
                                <i class="bi bi-grid-3x3-gap-fill"></i>
                            </button>
                            <button type="button" class="btn view-btn" data-view="grid-4"
                                title="@lang('general::lang.grid_view_4')">
                                <i class="bi bi-grid-fill"></i>
                            </button>
                        </div>
                    </div>

                </div>

                <div class="no-results" id="noResults">
                    <i class="fas fa-search"></i>
                    <h4>@lang('general::lang.no_results')</h4>
                    <p>@lang('general::lang.try_different_keywords')</p>
                </div>

                @foreach ($categories as $category)
                    @if ($category->products->count() > 0)
                        <div id="category-{{ $category->id }}" class="my-5 px-2 category-section ms-reveal">
                            <h5 class="fw-bold mb-3"><i class="fa-solid fa-utensils"></i>
                                {{ app()->currentLocale() == 'ar' ? $category->name_ar : $category->name_en }}
                            </h5>

                            <div class="products-wrapper grid-4" style="display: grid; gap: 20px;">
                                @foreach ($category->products as $product)
                                    @include('reservation::order.partials.product-menu-card', [
                                        'product' => $product,
                                        'tplSuffix' => 'main',
                                    ])
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
                @endif
            </div>
        </div>



    </div>

    {{-- Modals --}}
    <div class="modal fade" id="modalProductAllergens" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalProductAllergensTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalProductAllergensBody"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalProductDetail" tabindex="-1" aria-labelledby="modalProductDetailTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable pm-detail-modal">
            <div class="modal-content border-0 pm-detail-modal__shell">
                <button type="button" class="btn-close pm-detail-modal__close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body p-0" id="modalProductDetailBody" role="document"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTodaysMenu" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">@lang('reservation::lang.modal_todays_title')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    @if ($customMenu)
                        <p class="text-muted small mb-3">
                            {{ app()->currentLocale() == 'ar' ? $customMenu->name_ar : $customMenu->name_en }}
                        </p>
                    @endif
                    @forelse ($categories as $category)
                        @if ($category->products->count() > 0)
                            <h6 class="fw-bold mt-3 mb-2">{{ app()->currentLocale() == 'ar' ? $category->name_ar : $category->name_en }}</h6>
                            <div class="row g-3">
                                @foreach ($category->products as $product)
                                    <div class="col-6 col-md-4 col-lg-3 todays-product-card" @include('reservation::order.partials.product-allergen-data-attr', ['product' => $product])>
                                        @include('reservation::order.partials.product-menu-card', [
                                            'product' => $product,
                                            'tplSuffix' => 'todays',
                                            'iconsSize' => 'sm',
                                            'compact' => true,
                                        ])
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @empty
                        <p class="text-muted text-center py-5">@lang('general::lang.no_results')</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalLocation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content menu-feature-modal menu-location-modal">
                <div class="menu-feature-modal__hero">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="menu-feature-modal__icon" aria-hidden="true">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <h5 class="menu-feature-modal__title">@lang('reservation::lang.modal_location_title')</h5>
                    <p class="menu-feature-modal__intro">@lang('reservation::lang.modal_location_heading')</p>
                    <span class="menu-location-hint">
                        <i class="fa-regular fa-compass" aria-hidden="true"></i>
                        @lang('reservation::lang.map_pick_hint')
                    </span>
                </div>
                <div class="menu-feature-modal__body">
                    <div class="menu-location-panel">
                        @if (isset($menuEstablishments) && is_iterable($menuEstablishments) && count($menuEstablishments) > 1)
                            <div class="mb-3">
                                <div class="menu-location-label">@lang('reservation::lang.modal_location_branch')</div>
                                <select class="form-select" id="menuLocationEstSelect">
                                    @foreach ($menuEstablishments as $estRow)
                                        @php
                                            $label = $local == 'ar' ? ($estRow->name ?? '') : ($estRow->name_en ?? $estRow->name ?? '');
                                        @endphp
                                        <option value="{{ (int) ($estRow->id ?? 0) }}"
                                            {{ (int) ($estRow->id ?? 0) === (int) ($establishment_id ?? 0) ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        @if (!empty($mapLabel))
                            <div class="menu-location-address" id="menuLocationAddress">
                                <i class="fa-solid fa-map-pin" aria-hidden="true"></i>
                                <span id="menuLocationAddressText">{{ $mapLabel }}</span>
                            </div>
                        @else
                            <div class="menu-location-address d-none" id="menuLocationAddress">
                                <i class="fa-solid fa-map-pin" aria-hidden="true"></i>
                                <span id="menuLocationAddressText"></span>
                            </div>
                        @endif
                        @if ($mapEmbedUrl)
                            <div class="ratio ratio-16x9 menu-location-map-frame mb-3">
                                <iframe id="menuLocationMapFrame" src="{{ $mapEmbedUrl }}" style="border:0;" loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>
                            <a id="menuLocationOpenMapsBtn" class="btn btn-primary menu-location-open-btn w-100" target="_blank" rel="noopener"
                                href="https://www.google.com/maps?q={{ $mapLat }},{{ $mapLng }}">
                                <i class="fa-brands fa-google" aria-hidden="true"></i>
                                @lang('reservation::lang.modal_open_maps')
                            </a>
                        @else
                            <p class="text-muted mb-0 text-center py-3">@lang('general::lang.no_results')</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $menuEstLocations = is_array($menuToken->est_locations ?? null) ? $menuToken->est_locations : [];
        $menuEstLocations = array_map(function ($row) {
            if (! is_array($row)) {
                return null;
            }
            return [
                'map_lat' => $row['map_lat'] ?? null,
                'map_lng' => $row['map_lng'] ?? null,
                'map_label' => $row['map_label'] ?? null,
            ];
        }, $menuEstLocations);
    @endphp
    <script>
        (function() {
            const estLocations = @json($menuEstLocations);

            const select = document.getElementById('menuLocationEstSelect');
            const frame = document.getElementById('menuLocationMapFrame');
            const btn = document.getElementById('menuLocationOpenMapsBtn');

            if (!select || !frame || !btn) return;

            const buildEmbedUrl = (lat, lng) => {
                const d = 0.02;
                const bbox = `${(Number(lng) - d)},${(Number(lat) - d)},${(Number(lng) + d)},${(Number(lat) + d)}`;
                return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${Number(lat)},${Number(lng)}`;
            };

            const addressWrap = document.getElementById('menuLocationAddress');
            const addressText = document.getElementById('menuLocationAddressText');

            const applyLocation = (estId) => {
                const row = estLocations[String(estId)] || estLocations[Number(estId)] || null;
                const lat = row && row.map_lat != null ? row.map_lat : null;
                const lng = row && row.map_lng != null ? row.map_lng : null;
                if (lat == null || lng == null || String(lat).trim() === '' || String(lng).trim() === '') {
                    return;
                }
                frame.src = buildEmbedUrl(lat, lng);
                btn.href = `https://www.google.com/maps?q=${encodeURIComponent(lat)},${encodeURIComponent(lng)}`;
                if (addressWrap && addressText && row && row.map_label) {
                    addressText.textContent = row.map_label;
                    addressWrap.classList.remove('d-none');
                }
            };

            applyLocation(select.value);
            select.addEventListener('change', (e) => applyLocation(e.target.value));
        })();
    </script>

    <div class="modal fade" id="modalSmartMenu" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">@lang('reservation::lang.modal_smart_title')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2 pb-4">
                    <div class="menu-modal-placeholder">
                        <div class="menu-modal-placeholder__icon" aria-hidden="true">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                        </div>
                        <h6 class="menu-modal-placeholder__title">@lang('reservation::lang.modal_smart_heading')</h6>
                        <p class="menu-modal-placeholder__text">@lang('reservation::lang.modal_smart_body')</p>
                        <p class="menu-modal-placeholder__hint">
                            <i class="fa-regular fa-clock" aria-hidden="true"></i>
                            @lang('reservation::lang.modal_smart_hint')
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAllergy" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">@lang('general::lang.allergy_info')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 class="fw-bold mb-1">@lang('reservation::lang.menu_allergen_modal_section_title')</h6>
                    <p class="small text-muted mb-3">@lang('reservation::lang.menu_allergen_modal_section_intro')</p>
                    @include('reservation::order.partials.menu-allergen-filter')

                    <hr class="my-4 opacity-25">
                    @if ($allergyDocumentUrl)
                        @php $ext = strtolower(pathinfo($menuToken->allergy_document_path ?? '', PATHINFO_EXTENSION)); @endphp
                        @if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']))
                            <img src="{{ $allergyDocumentUrl }}" class="img-fluid rounded shadow-sm" alt="">
                        @else
                            <iframe src="{{ $allergyDocumentUrl }}" class="w-100 rounded shadow-sm" style="min-height: 420px;"></iframe>
                        @endif
                        <a href="{{ $allergyDocumentUrl }}" target="_blank" class="btn btn-outline-primary w-100 mt-3">@lang('reservation::lang.allergy_download')</a>
                    @else
                        <p class="text-muted mb-0">@lang('general::lang.no_results')</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPhotos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">@lang('reservation::lang.modal_photos_title')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2 pb-4">
                    <div class="menu-modal-placeholder">
                        <div class="menu-modal-placeholder__icon" aria-hidden="true">
                            <i class="fa-solid fa-camera-retro"></i>
                        </div>
                        <h6 class="menu-modal-placeholder__title">@lang('reservation::lang.modal_photos_heading')</h6>
                        <p class="menu-modal-placeholder__text">@lang('reservation::lang.modal_photos_body')</p>
                        <p class="menu-modal-placeholder__hint">
                            <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                            @lang('reservation::lang.modal_photos_hint')
                        </p>
                        @php
                            $hasSocialForPhotos = ! empty($socialLinks['social_whatsapp'])
                                || ! empty($socialLinks['social_instagram'])
                                || ! empty($socialLinks['social_snapchat'])
                                || ! empty($socialLinks['social_x'])
                                || ! empty($socialLinks['social_facebook']);
                        @endphp
                        @if ($hasSocialForPhotos)
                            <div class="menu-modal-placeholder__social">
                                <div class="menu-modal-placeholder__social-label">@lang('reservation::lang.modal_photos_follow')</div>
                                <div class="icon-list justify-content-center">
                                    @if (!empty($socialLinks['social_whatsapp']))
                                        <a href="{{ $socialLinks['social_whatsapp'] }}" target="_blank" rel="noopener noreferrer" class="icon-item" style="width: 44px;" aria-label="WhatsApp"><i class="fab fa-whatsapp" style="color: #25D366;"></i></a>
                                    @endif
                                    @if (!empty($socialLinks['social_instagram']))
                                        <a href="{{ $socialLinks['social_instagram'] }}" target="_blank" rel="noopener noreferrer" class="icon-item" style="width: 44px;" aria-label="Instagram"><i class="fab fa-instagram" style="color: #E1306C;"></i></a>
                                    @endif
                                    @if (!empty($socialLinks['social_snapchat']))
                                        <a href="{{ $socialLinks['social_snapchat'] }}" target="_blank" rel="noopener noreferrer" class="icon-item" style="width: 44px;" aria-label="Snapchat"><i class="fab fa-snapchat-ghost" style="color: #FFFC00;"></i></a>
                                    @endif
                                    @if (!empty($socialLinks['social_x']))
                                        <a href="{{ $socialLinks['social_x'] }}" target="_blank" rel="noopener noreferrer" class="icon-item" style="width: 44px;" aria-label="X"><i class="fab fa-twitter" style="color: #1DA1F2;"></i></a>
                                    @endif
                                    @if (!empty($socialLinks['social_facebook']))
                                        <a href="{{ $socialLinks['social_facebook'] }}" target="_blank" rel="noopener noreferrer" class="icon-item" style="width: 44px;" aria-label="Facebook"><i class="fab fa-facebook-f" style="color: #3b5998;"></i></a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalFeedback" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content menu-feature-modal menu-feedback-modal">
                <div class="menu-feature-modal__hero">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="menu-feature-modal__icon" aria-hidden="true">
                        <i class="fa-solid fa-heart"></i>
                    </div>
                    <h5 class="menu-feature-modal__title">@lang('reservation::lang.modal_feedback_title')</h5>
                    <p class="menu-feature-modal__intro">@lang('reservation::lang.modal_feedback_intro')</p>
                </div>
                <div class="menu-feature-modal__body">
                    <div class="menu-feedback-stars-panel">
                        <div class="menu-feedback-stars-label">@lang('reservation::lang.modal_feedback_stars_label')</div>
                        <div class="text-center" id="feedbackStars">
                            @for ($s = 1; $s <= 5; $s++)
                                <button type="button" class="btn btn-link p-1 feedback-star fs-1 text-warning" data-star="{{ $s }}" aria-label="star {{ $s }}">☆</button>
                            @endfor
                        </div>
                        <input type="hidden" id="feedbackStarsValue" value="0">
                        <div id="feedbackHint" class="feedback-hint text-center mt-2 mb-0" role="alert"></div>
                    </div>
                    <textarea id="feedbackComment" class="form-control menu-feedback-textarea" rows="3" placeholder="@lang('reservation::lang.modal_feedback_comment')"></textarea>
                    <button type="button" class="btn btn-primary menu-feedback-submit w-100 mt-3" id="feedbackSubmitBtn">
                        <i class="fa-regular fa-paper-plane" aria-hidden="true"></i>
                        @lang('reservation::lang.modal_feedback_send')
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalFeedbackThanks" tabindex="-1" aria-hidden="true" data-bs-backdrop="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content menu-thanks-modal border-0">
                <div class="modal-body text-center py-4 px-4">
                    <div class="menu-thanks-icon mb-2">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="menu-thanks-sparkle mb-1" aria-hidden="true">✦ ✦ ✦</div>
                    <h4 class="fw-bold mb-2" style="color: var(--restaurant-name);">@lang('reservation::lang.modal_feedback_thanks_title')</h4>
                    <p class="text-muted mb-1" style="font-size: 1.05rem;">@lang('reservation::lang.modal_feedback_thanks')</p>
                    <p class="small text-muted mb-4">@lang('reservation::lang.modal_feedback_thanks_sub')</p>
                    <button type="button" class="btn menu-thanks-btn rounded-pill px-5 py-2" data-bs-dismiss="modal">@lang('reservation::lang.modal_feedback_ok')</button>
                </div>
            </div>
        </div>
    </div>

    @php
        $companyWebsiteRaw = trim((string) ($company->website ?? ''));
        $companyWebsiteHref =
            $companyWebsiteRaw !== ''
                ? (preg_match('#^https?://#i', $companyWebsiteRaw) ? $companyWebsiteRaw : 'https://' . ltrim($companyWebsiteRaw, '/'))
                : '';
        $companyPhoneRaw = trim((string) ($company->phone ?? ''));
        $companyPhoneHref = $companyPhoneRaw !== '' ? preg_replace('/\s+/', '', 'tel:' . $companyPhoneRaw) : '';
    @endphp

    <div class="modal fade" id="modalCompanyInfo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content company-info-modal menu-feature-modal">
                <div class="company-info-hero menu-feature-modal__hero">
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                    <img src="{{ $companyLogoUrl }}" alt="" class="company-info-logo">
                    <h4 class="menu-feature-modal__title mb-1">{{ $company->name ?? '—' }}</h4>
                    <p class="mb-0">@lang('reservation::lang.modal_info_subtitle')</p>
                    <p class="company-info-intro">@lang('reservation::lang.modal_info_intro')</p>
                </div>
                <div class="modal-body company-info-body pt-0 menu-feature-modal__body">
                    <div class="company-info-grid">
                        <div class="company-info-item">
                            <div class="ci-icon"><i class="fa-solid fa-phone"></i></div>
                            <div>
                                <div class="ci-label">@lang('establishment::fields.phone')</div>
                                <div class="ci-value">
                                    @if ($companyPhoneHref !== '')
                                        <a href="{{ $companyPhoneHref }}">{{ $company->phone }}</a>
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="company-info-item">
                            <div class="ci-icon"><i class="fa-solid fa-globe"></i></div>
                            <div>
                                <div class="ci-label">@lang('establishment::fields.website')</div>
                                <div class="ci-value">
                                    @if ($companyWebsiteHref !== '')
                                        <a href="{{ $companyWebsiteHref }}" target="_blank" rel="noopener noreferrer">{{ $companyWebsiteRaw }}</a>
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="company-info-item">
                            <div class="ci-icon"><i class="fa-solid fa-file-invoice"></i></div>
                            <div>
                                <div class="ci-label">@lang('establishment::fields.tax_name')</div>
                                <div class="ci-value">{{ $company->tax_name ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="company-info-item">
                            <div class="ci-icon"><i class="fa-solid fa-hashtag"></i></div>
                            <div>
                                <div class="ci-label">@lang('establishment::fields.tax_number')</div>
                                <div class="ci-value">{{ $company->tax_number ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="company-info-item company-info-wide">
                            <div class="ci-icon"><i class="fa-solid fa-location-dot"></i></div>
                            <div>
                                <div class="ci-label">@lang('establishment::fields.national_address')</div>
                                <div class="ci-value">{{ $company->national_address ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            const allergenModalEl = document.getElementById('modalProductAllergens');
            if (!allergenModalEl) return;
            allergenModalEl.addEventListener('show.bs.modal', function(e) {
                const btn = e.relatedTarget;
                if (!btn || !btn.getAttribute('data-allergen-template')) return;
                const tplId = btn.getAttribute('data-allergen-template');
                const tpl = document.getElementById(tplId);
                const body = document.getElementById('modalProductAllergensBody');
                const title = document.getElementById('modalProductAllergensTitle');
                if (title) {
                    title.textContent = btn.getAttribute('data-product-name') || '';
                }
                if (!body) return;
                body.replaceChildren();
                if (tpl && tpl.content) {
                    body.appendChild(document.importNode(tpl.content, true));
                }
            });
            allergenModalEl.addEventListener('hidden.bs.modal', function() {
                const body = document.getElementById('modalProductAllergensBody');
                if (body) body.replaceChildren();
            });
        })();

        (function() {
            const detailModalEl = document.getElementById('modalProductDetail');
            if (!detailModalEl || typeof bootstrap === 'undefined') return;

            const detailBody = document.getElementById('modalProductDetailBody');
            let detailModal = null;

            function getDetailModal() {
                if (!detailModal) {
                    detailModal = bootstrap.Modal.getOrCreateInstance(detailModalEl);
                }
                return detailModal;
            }

            function openProductDetail(card) {
                const tplId = card.getAttribute('data-product-detail-tpl');
                const tpl = tplId ? document.getElementById(tplId) : null;
                if (!detailBody || !tpl || !tpl.content) return;
                detailBody.replaceChildren();
                detailBody.appendChild(document.importNode(tpl.content, true));
                getDetailModal().show();
            }

            document.addEventListener('click', function(e) {
                if (e.target.closest('.product-allergen-icon-btn')) return;
                const card = e.target.closest('.pm-card.product-card');
                if (!card || card.classList.contains('hidden')) return;
                if (!card.hasAttribute('data-product-detail-tpl')) return;
                openProductDetail(card);
            });

            document.addEventListener('keydown', function(e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                const card = e.target.closest('.pm-card.product-card');
                if (!card || document.activeElement !== card) return;
                e.preventDefault();
                openProductDetail(card);
            });

            detailModalEl.addEventListener('hidden.bs.modal', function() {
                if (detailBody) detailBody.replaceChildren();
            });
        })();
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"
        integrity="sha512-fKcyo0o+5m6fypWn+0n0n0x5f+7l7z+J0Uitc5Y+JyzE5pytXGlA5nyp5jQ17p9pQ1vKaA8kqk0/1LD4GfpJYQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        const startBtn = document.getElementById('startBtn');
        const welcomeScreen = document.getElementById('welcomeScreen');
        const menuContent = document.getElementById('menuContent');

        startBtn.addEventListener('click', () => {
            welcomeScreen.classList.add('hide');
            setTimeout(() => {
                menuContent.classList.add('show');
                document.dispatchEvent(new CustomEvent('menuSimple:opened'));
            }, 650);
        });
    </script>
    <script>
        (function () {
            'use strict';
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.querySelectorAll('.ms-reveal').forEach((el) => el.classList.add('ms-visible'));
                return;
            }

            const revealObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) return;
                        entry.target.classList.add('ms-visible');
                        revealObserver.unobserve(entry.target);
                    });
                },
                { root: null, rootMargin: '0px 0px -6% 0px', threshold: 0.12 }
            );

            function bindReveal() {
                document.querySelectorAll('.ms-reveal:not(.ms-visible)').forEach((el) => revealObserver.observe(el));
            }

            bindReveal();
            document.addEventListener('menuSimple:opened', () => {
                setTimeout(bindReveal, 120);
            });
        })();
    </script>
    <script>
        const themeToggle = document.getElementById("themeToggle");
        const body = document.body;

        if (localStorage.getItem("theme") === "dark") {
            body.classList.add("dark-mode");
        } else {
            body.classList.add("light-mode");
        }

        themeToggle.addEventListener("click", () => {
            body.classList.toggle("dark-mode");
            body.classList.toggle("light-mode");

            if (body.classList.contains("dark-mode")) {
                localStorage.setItem("theme", "dark");
            } else {
                localStorage.setItem("theme", "light");
            }
        });


            const languageButton = document.getElementById('languageButton');
        if (languageButton) {
        languageButton.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            const currentLang = localStorage.getItem('language') || 'ar';
            const newLang = currentLang === 'ar' ? 'en' : 'ar';
            localStorage.setItem('language', newLang);
                setTimeout(() => window.location.reload(), 200);
        });
        }

        const menuToggle = document.querySelector('.menu-toggle');
        if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            alert('@lang('general::lang.side_menu_opening')');
        });
        }
    </script>

    <script>
        (function() {
            const feedbackUrl = @json(route('reservation.menuSimple.feedback', ['token' => $feedbackToken]));
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const msgPickStars = @json(__('reservation::lang.modal_feedback_pick_stars'));
            const msgErrGeneric = @json(__('reservation::lang.modal_feedback_error_generic'));
            const elFeedback = document.getElementById('modalFeedback');
            const elThanks = document.getElementById('modalFeedbackThanks');
            const hintEl = document.getElementById('feedbackHint');

            function setFeedbackHint(text, kind) {
                if (!hintEl) return;
                hintEl.textContent = text || '';
                hintEl.classList.remove('is-invalid', 'is-ok');
                if (kind === 'error') hintEl.classList.add('is-invalid');
                if (kind === 'ok') hintEl.classList.add('is-ok');
            }

            if (elFeedback) {
                elFeedback.addEventListener('shown.bs.modal', () => setFeedbackHint('', null));
            }

            let selectedStar = 0;
            document.querySelectorAll('.feedback-star').forEach((btn) => {
                btn.addEventListener('click', () => {
                    selectedStar = parseInt(btn.getAttribute('data-star'), 10);
                    document.getElementById('feedbackStarsValue').value = String(selectedStar);
                    document.querySelectorAll('.feedback-star').forEach((b, i) => {
                        b.textContent = i < selectedStar ? '★' : '☆';
                    });
                    setFeedbackHint('', null);
                });
            });

            const submitBtn = document.getElementById('feedbackSubmitBtn');
            if (submitBtn) {
                submitBtn.addEventListener('click', async () => {
                    const stars = parseInt(document.getElementById('feedbackStarsValue').value, 10);
                    if (!stars) {
                        setFeedbackHint(msgPickStars, 'error');
                        return;
                    }
                    setFeedbackHint('', null);
                    const comment = document.getElementById('feedbackComment').value;
                    submitBtn.disabled = true;
                    try {
                        const res = await fetch(feedbackUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ stars, comment }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            setFeedbackHint(data.message || msgErrGeneric, 'error');
                            return;
                        }
                        const fbModal = bootstrap.Modal.getInstance(elFeedback);
                        const resetFeedbackForm = () => {
                            document.getElementById('feedbackComment').value = '';
                            document.getElementById('feedbackStarsValue').value = '0';
                            selectedStar = 0;
                            document.querySelectorAll('.feedback-star').forEach((b) => {
                                b.textContent = '☆';
                            });
                            setFeedbackHint('', null);
                        };
                        if (fbModal) {
                            elFeedback.addEventListener(
                                'hidden.bs.modal',
                                function onFeedbackClosed() {
                                    elFeedback.removeEventListener('hidden.bs.modal', onFeedbackClosed);
                                    resetFeedbackForm();
                                    bootstrap.Modal.getOrCreateInstance(elThanks).show();
                                },
                                { once: true }
                            );
                            fbModal.hide();
                        } else {
                            resetFeedbackForm();
                            bootstrap.Modal.getOrCreateInstance(elThanks).show();
                        }
                    } catch (e) {
                        setFeedbackHint(msgErrGeneric, 'error');
                    } finally {
                        submitBtn.disabled = false;
                    }
                });
            }
        })();
    </script>


    <script>
        $(document).ready(function() {
            const $search = $('#searchInput');

            function normAllergenKey(k) {
                return String(k == null ? '' : k)
                    .toLowerCase()
                    .trim();
            }

            function productAllergenKeysFromCard($card) {
                let parsed = $card.data('allergenKeys');
                if (parsed === undefined || parsed === '') {
                    const raw = $card.attr('data-allergen-keys');
                    if (!raw || raw === '[]') {
                        return [];
                    }
                    try {
                        parsed = JSON.parse(raw);
                    } catch (e) {
                        return [];
                    }
                }
                if (!Array.isArray(parsed)) {
                    return [];
                }
                const out = [];
                parsed.forEach(function(item) {
                    const n = normAllergenKey(item);
                    if (n && out.indexOf(n) === -1) {
                        out.push(n);
                    }
                });
                return out;
            }

            function getAvoidedAllergenKeys() {
                const out = [];
                $('.allergen-filter-chip.active').each(function() {
                    const k = normAllergenKey($(this).attr('data-allergen-filter'));
                    if (k) {
                        out.push(k);
                    }
                });
                return out;
            }

            function updateAllergenFilterIndicators() {
                const n = $('.allergen-filter-chip.active').length;
                $('#allergenFilterClear').toggleClass('d-none', n === 0);
                const $badge = $('#allergenFilterActiveBadge');
                if (n === 0) {
                    $badge.addClass('d-none').text('0');
                } else {
                    $badge.removeClass('d-none').text(String(n));
                }
            }

            function expandAllergenFilterOnDesktop() {
                /* Allergen filter is only inside the allergy modal (no collapsible strip). */
            }

            function applyMenuFilters() {
                const searchVal = ($search.val() || '').toLowerCase().trim();
                const avoided = getAvoidedAllergenKeys();
                let anyVisible = false;

                $('.product-card').each(function() {
                    const $c = $(this);
                    const keys = productAllergenKeysFromCard($c);
                    const blockedByAllergen = avoided.some(function(a) {
                        return keys.indexOf(a) !== -1;
                    });
                    const matchesSearch =
                        searchVal === '' ||
                        $c.find('.card-title').text().toLowerCase().includes(searchVal);
                    const show = !blockedByAllergen && matchesSearch;
                    $c.toggleClass('hidden', !show);
                    if (show) {
                        anyVisible = true;
                    }
                });

                $('.category-section').each(function() {
                    const hasVisibleProducts = $(this).find('.product-card:not(.hidden)').length > 0;
                    $(this).toggleClass('hidden', !hasVisibleProducts);
                });

                const totalMainCards = $('.product-card').length;
                $('#noResults').toggle(!anyVisible && totalMainCards > 0);

                $('.todays-product-card').each(function() {
                    const $c = $(this);
                    const keys = productAllergenKeysFromCard($c);
                    const blockedByAllergen = avoided.some(function(a) {
                        return keys.indexOf(a) !== -1;
                    });
                    $c.toggleClass('hidden', blockedByAllergen);
                });

                updateAllergenFilterIndicators();
            }

            if ($search.length) {
                $search.on('keyup', function() {
                    applyMenuFilters();
                });
            }

            $(document).on('click', '.allergen-filter-chip', function() {
                const $btn = $(this);
                const on = !$btn.hasClass('active');
                $btn.toggleClass('active', on);
                $btn.attr('aria-pressed', on ? 'true' : 'false');
                applyMenuFilters();
            });

            $('#allergenFilterClear').on('click', function() {
                $('.allergen-filter-chip').removeClass('active').attr('aria-pressed', 'false');
                applyMenuFilters();
            });

            $('.btn-group button').on('click', function() {
                let view = $(this).data('view');
                $('.products-wrapper').removeClass('grid-1 grid-2 grid-4').addClass(view);
                $('.btn-group button').removeClass('active');
                $(this).addClass('active');
            });

            expandAllergenFilterOnDesktop();
            applyMenuFilters();
        });
    </script>
</body>

</html>

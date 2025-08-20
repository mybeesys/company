<!-- {{-- @extends('layouts.menu')
@section('css')
    <style>
      .title {
        font-size: 16px;
        font-weight: bold; /* Bold the "Lounge" text */
        color: #343a40; /* Dark gray color */
        padding-left: 10px;
      }
      .card-body {
        padding: 1rem!important; /* Adjust as needed, e.g., 0.5rem, 2rem, etc. */
      }
      .custom-btn {
          border: 1px solid #d1b5f9;
          background-color: transparent;
          color: #d1b5f9;
          padding: 5px 15px;
          font-size: 14px;
          border-radius: 5px;
          cursor: pointer;
          transition: all 0.3s ease;
        }

        .custom-btn:hover {
          background-color: #f4ebff;
          color: #7a41c5;
          border-color: #7a41c5;
        }
        .order-header {
          background-color: #f4f4ff;
          text-align: center;
          padding: 20px;
          font-weight: bold;
          color: #5a4fcf;
          font-size: 1.5rem;
        }
        .order-summary {
          background-color: #f9f9f9;
          padding: 15px;
          border-radius: 8px;
        }
        .btn-place-order {
          background-color: #d1b5f9;
          border-color: #d1b5f9;
          color: white;
        }
        .btn-place-order:hover {
          background-color: #bba0e0;
          border-color: #bba0e0;
        }
        .quantity-controls input {
          max-width: 60px;
          text-align: center;
        }
        .custom-tabs .nav-link {
          border: none; /* Remove default border */
          color: #6c757d; /* Inactive tab color */
          padding: 0.5rem 1rem; /* Adjust spacing */
          position: relative;
        }

        .custom-tabs .nav-link.active {
          color: #7b2cf1; /* Active tab color */
          font-weight: bold;
        }

        .custom-tabs .nav-link.active::after {
          content: '';
          display: block;
          width: 100%;
          height: 2px;
          background-color: #7b2cf1; /* Line color */
          position: absolute;
          bottom: -2px; /* Adjust line placement */
          left: 0;
        }

        .custom-tabs {
          border-bottom: 1px solid #dee2e6; /* Light border under tabs */
        }
      </style>
@stop
@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')

      <div id="root" type="menuSimple"
      info="{{json_encode($info)}}"
list-url="{{json_encode(route('order.products'))}}"
blank-url ='/assets/media/svg/files/blank-image.svg'
dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"
></div>

@endsection --}} -->


<style>
        :root {
            --primary-color: #e0d9d9;
            --secondary-color: #e0d9d9;
            --text-color: #333;
            --bg-color: #f8f9fa;
            --success-color: #28a745;
            --switch-bg: #ccc;
            --switch-checked: #f9f9f9;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --navbar-height: 35px;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            color: var(--text-color);
            transition: background-color 0.3s;
        }

        body.dark-mode {
            --bg-color: #1a1a2e;
            --text-color: #f8f9fa;
            --primary-color: #e0d9d9;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .main_page_nav_and_event_image {
            position: relative;
            margin-bottom: 20px;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: rgb(36, 36, 36);
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

        .left, .right {
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
        }

        .merchant_opening_status {
            background-color: var(--success-color);
            padding: 2px 10px;
            border-radius: 15px;
            font-weight: 500;
            font-size: 13px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
            70% { box-shadow: 0 0 0 5px rgba(40, 167, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
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

        input:checked + .slider {
            background-color: var(--switch-checked);
        }

        input:checked + .slider:before {
            transform: translateX(20px);
        }

        .switch-label {
            color: rgb(58, 57, 57);
            font-size: 12px;
        }

        .language-btn {
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
            color: rgb(36, 35, 35);
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
            color: white;
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

            .left, .title, .right {
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
    </style>

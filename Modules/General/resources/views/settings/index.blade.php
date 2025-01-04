@extends('layouts.app')

@section('title', __('menuItemLang.general_setting'))
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }

        .fa-folder:before {
            color: #17c653 !important;

        }

        #accounts_tree_container>ul {
            text-align: justify !important;

        }

        .jstree-container-ul .jstree-children {
            text-align: justify !important;
        }

        .jstree-default .jstree-search {
            font-style: oblique !important;
            color: #1b84ff !important;
            font-weight: 700 !important;
        }

        .swal2-popup {
            width: 58em !important;
            /* max-width: 0% !important; */
        }

        .jstree-default .jstree-clicked {
            background: #beebff2e !important;
            border-radius: 8px !important;
            box-shadow: none !important;
        }

        .jstree-default .jstree-anchor .jstree-hovered {
            background: #beebff2e !important;
            border-radius: 8px !important;
            box-shadow: none !important;
        }

        .btn.btn-secondary.show:hover {
            background-color: transparent !important;
        }

        .select-custom {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-color: #f3f4f6;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 10px;
            font-size: 14px;
            color: #333;
        }
    </style>


@stop

@section('content')


    <div class="container">
        <div class="row my-6">
            <div class="col-6">
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <h1> @lang('menuItemLang.general_setting')</h1>

                </div>
            </div>
            <div class="col-6" style="justify-content: end;display: flex;">

            </div>
        </div>
    </div>




    <div class="container">
        <div class="row">

                @foreach ($cards as $card)
                <div class="col-3">

                <a href="{{route($card['route'])}}" class="link">
                    <label
                        class="btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 mb-5 px-4"
                        data-kt-button="true">
                        <input class="btn-check" type="radio" name="method" value="0">
                        <i class="{{$card['icon']}}  fs-2hx mb-2 pe-0"></i>
                        <span class="fs-7 fw-bold d-block">{{$card['name']}}</span>
                    </label>
                </a>
            </div>

                @endforeach




        </div>
    </div>



@stop

@section('script')

<script>
    document.querySelectorAll('.link').forEach(function(link) {
        link.addEventListener('click', function(event) {
            if (event.target.tagName !== 'INPUT') {
                event.preventDefault();
                window.location.href = this.href;
            }
        });
    });
</script>
@endsection

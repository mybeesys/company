@extends('layouts.app')

@section('title', __('accounting::lang.journalEntry'))
@section('css')


@stop
@section('content')

    <div class="container">
        <div class="row" @if (app()->getLocale() == 'en') dir="rtl" @endif>
            <div class="col-6">
                <div class="d-flex align-items-center  gap-2 gap-lg-3">
                    <a href="{{ action('Modules\Accounting\Http\Controllers\JournalEntryController@create') }}"
                        class="btn btn-flex btn-primary h-40px fs-7 fw-bold create-journal-entry-link">
                        @lang('accounting::lang.add_journalEntry')
                    </a>
                </div>
            </div>
            <div class="col-6" style="justify-content: end;display: flex;">
                <h1> @lang('accounting::lang.journalEntry')</h1>
            </div>
        </div>
    </div>







@stop

@section('script')
    <script type="text/javascript">
        $(document).on('click', 'a.create-journal-entry-link', function(e) {
            window.location.href = $(this).attr('href');
        });
    </script>
@stop

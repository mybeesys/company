@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees_working_hours'))

@section('content')
    <x-form.input-div class="mb-10 w-25">
        <x-form.input class="form-control form-control-solid" :label="__('employee::general.period')" name="kt_daterangepicker_4" />
    </x-form.input-div>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr class="fw-bold fs-6 text-gray-800">
                    <th>Name</th>
                    <th>Position</th>
                    <th>Office</th>
                    <th>Age</th>
                    <th>Start date</th>
                    <th>Salary</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tiger Nixon</td>
                    <td>System Architect</td>
                    <td>Edinburgh</td>
                    <td>61</td>
                    <td>2011/04/25</td>
                    <td>$320,800</td>
                </tr>
                <tr>
                    <td>Garrett Winters</td>
                    <td>Accountant</td>
                    <td>Tokyo</td>
                    <td>63</td>
                    <td>2011/07/25</td>
                    <td>$170,750</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection

@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script type="text/javascript" src="vfs_fonts.js"></script>
    <script>
        const dir = "{{ session()->get('locale') == 'ar' ? 'left' : 'right' }}"
        moment.updateLocale('en', {
            week: {
                dow: 6
            }
        });
        var start = moment().startOf('week');
        var end = moment().endOf('week');

        function cb(start, end) {
            $("#kt_daterangepicker_4").html(start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY"));
        }

        $("#kt_daterangepicker_4").daterangepicker({
            startDate: start,
            endDate: end,
            opens: dir,
            ranges: {
                "{{ __('employee::general.after_days', ['days' => '5']) }}": [moment(), moment().add(5, "days")],
                "{{ __('employee::general.after_days', ['days' => '7']) }}": [moment(), moment().add(7, "days")],
                "{{ __('employee::general.after_days', ['days' => '10']) }}": [moment(), moment().add(10, "days")],
                "{{ __('employee::general.this_month') }}": [moment().startOf("month"), moment().endOf("month")],
            },
            locale: {
                customRangeLabel: "{{ __('employee::general.custom_range') }}"
            }
        }, cb);
    </script>
@endsection

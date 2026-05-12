<script src="{{ url('/js/general.js') }}"></script>
<script src="{{ url('/js/date-picker.js') }}"></script>
<script src="/assets/plugins/global/plugins.bundle.js"></script>
<script src="/assets/js/scripts.bundle.js"></script>
<script src="/assets/js/dataTables.js"></script>
<script src="/assets/js/dataTables.bootstrap4.js"></script>
<script src="/assets/plugins/custom/fullcalendar/fullcalendar.bundle.js"></script>
<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
<script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
<script src="https://cdn.amcharts.com/lib/5/radar.js"></script>
<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
<script src="https://cdn.amcharts.com/lib/5/map.js"></script>
<script src="https://cdn.amcharts.com/lib/5/geodata/worldLow.js"></script>
<script src="https://cdn.amcharts.com/lib/5/geodata/continentsLow.js"></script>
<script src="https://cdn.amcharts.com/lib/5/geodata/usaLow.js"></script>
<script src="https://cdn.amcharts.com/lib/5/geodata/worldTimeZonesLow.js"></script>
<script src="https://cdn.amcharts.com/lib/5/geodata/worldTimeZoneAreasLow.js"></script>
<script src="/assets/plugins/custom/datatables/datatables.bundle.js"></script>
<script src="/assets/js/widgets.bundle.js"></script>
<script src="/assets/js/custom/widgets.js"></script>
<script src="/assets/js/custom/apps/chat/chat.js"></script>
<script src="/assets/js/custom/utilities/modals/upgrade-plan.js"></script>
<script src="/assets/js/custom/utilities/modals/users-search.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="/assets/plugins/custom/formrepeater/formrepeater.bundle.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<script src="https://cdn.jsdelivr.net/npm/moment/min/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script>
    window.csrfToken = '{{ csrf_token() }}';
    var hostUrl = "/assets/";
    const loader = document.getElementById("initial-loader");
    $(window).on("load", function() {
        loader.remove();
    });
    toastr.options = {
        "closeButton": false,
        "debug": false,
        "newestOnTop": true,
        "progressBar": false,
        "positionClass": "toastr-top-right",
        "preventDuplicates": true,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
    if ("{{ session('success') }}") {
        toastr.success("{{ session('success') }}");
    }
    if ("{{ session('error') }}") {
        toastr.error("{{ session('error') }}");
    }

    $('#kt_app_sidebar_toggle').on('click', function() {
        $(this).data('kt-app-sidebar-minimize');

        const myTimeout = setTimeout(function() {
            const sidebarState = $('#kt_app_body').attr('data-kt-app-sidebar-minimize');
            let status;
            if (sidebarState) {
                status = false;
            } else {
                status = true;
            }
            ajaxRequest("{{ route('store-sidebar-status') }}", "POST", {
                state: status
            }, false, false, false);
        }, 300);
    });

    $(document).on('click', '.js-receipt-delete-submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var btn = $(this);
        var form = btn.closest('form');
        if (!form.length) {
            return;
        }
        var title = btn.attr('data-swal-title') || '';
        var text = btn.attr('data-swal-text') || '';
        var confirmTxt = btn.attr('data-swal-confirm') || @json(__('messages.yes'));
        var cancelTxt = btn.attr('data-swal-cancel') || @json(__('messages.cancel'));
        var rtl = document.documentElement.getAttribute('dir') === 'rtl';

        var submitForm = function() {
            if (form[0]) {
                form[0].submit();
            }
        };

        if (typeof Swal === 'undefined') {
            if (window.confirm(text || title)) {
                submitForm();
            }
            return;
        }

        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            iconColor: '#f1416c',
            showCancelButton: true,
            focusCancel: true,
            allowOutsideClick: false,
            buttonsStyling: false,
            reverseButtons: rtl,
            confirmButtonText: confirmTxt,
            cancelButtonText: cancelTxt,
            customClass: {
                popup: 'rounded-4 shadow-lg',
                title: 'fw-bold text-gray-900 fs-3',
                htmlContainer: 'text-gray-700 fs-6 text-start',
                actions: 'gap-2 mt-4',
                confirmButton: 'btn btn-danger fw-semibold px-6 py-2 rounded-2',
                cancelButton: 'btn btn-light btn-active-light-primary fw-semibold px-6 py-2 rounded-2'
            },
            padding: '1.75rem',
            width: '28rem'
        }).then(function(result) {
            if (result.isConfirmed) {
                submitForm();
            }
        });
    });
</script>

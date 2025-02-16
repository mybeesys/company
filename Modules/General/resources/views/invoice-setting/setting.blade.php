<div class="col-6" style="justify-content: end;display: flex;">
    <div class="btn-group dropend">

        <button type="button" style="background: transparent;border-radius: 6px;" class="btn  dropdown-toggle px-0"
            data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-cog" style="font-size: 1.4rem; color: #c59a00;"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-left" role="menu" style=" width: max-content;padding: 10px;"
            style="padding: 8px 15px;">
            <li class="mb-5" style="text-align: justify;">
                <span class="card-label fw-bold fs-6 mb-1">@lang('messages.settings')</span>
            </li>
            <li>
                <div class="form-check form-switch mt-5"
                    style="display: flex; justify-content: space-between; gap: 37px;">
                    <input class="form-check-input" type="checkbox" id="toggleCost_center">
                    <label class="form-check-label ml-4" for="toggleCost_center">@lang('accounting::lang.Enable Cost Center')</label>
                </div>
            </li>

            <li>
                <div class="form-check form-switch mt-5"
                    style="display: flex; justify-content: space-between; gap: 37px;">
                    <input class="form-check-input" type="checkbox" id="toggleStorehouse">
                    <label class="form-check-label ml-4" for="toggleStorehouse">@lang('sales::lang.toggleStorehouse')</label>
                </div>
            </li>

            <li>
                <div class="form-check form-switch mt-5"
                    style="display: flex; justify-content: space-between; gap: 37px;">
                    <input class="form-check-input" type="checkbox" id="toggleDelegates">
                    <label class="form-check-label ml-4" for="toggleDelegates">@lang('sales::lang.toggleDelegates')</label>
                </div>
            </li>
        </ul>
    </div>

</div>

<script>
    $(document).ready(function() {
    $.ajax({
        url: "{{ route('invoice-settings-get') }}",
        type: "GET",
        success: function (response) {
            if (response.success) {
                $('#toggleCost_center').prop('checked', response.data.cost_center);
                $('#toggleStorehouse').prop('checked', response.data.storehouse);
                $('#toggleDelegates').prop('checked', response.data.delegates);

                toggleVisibility("#toggleCost_center", "#dev-costCenter");
                toggleVisibility("#toggleStorehouse", "#div-storehouse");
                toggleVisibility("#toggleDelegates", "#div-Delegates");
            }
        }
    });

    $(".form-check-input").on("change", function () {
        let key = $(this).attr("id");
        let value = $(this).prop("checked") ? 1 : 0;

        $.ajax({
            url: "{{ route('invoice-settings-update') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                key: key,
                value: value
            },
            success: function (response) {
                if (response.success) {
                    console.log("Setting updated successfully!");
                }
            }
        });

        toggleVisibility("#" + key, getTargetDiv(key));
    });

    function toggleVisibility(checkbox, targetDiv) {
        if ($(checkbox).is(":checked")) {
            $(targetDiv).show();
        } else {
            $(targetDiv).hide();
        }
    }

    function getTargetDiv(key) {
        switch (key) {
            case "toggleCost_center":
                return "#dev-costCenter";
            case "toggleStorehouse":
                return "#div-storehouse";
            case "toggleDelegates":
                return "#div-Delegates";
            default:
                return "";
        }
    }
});

</script>

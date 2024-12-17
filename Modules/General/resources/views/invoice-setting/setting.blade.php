<div class="col-6" style="justify-content: end;display: flex;">
    <div class="btn-group dropend">

        <button type="button" style="background: transparent;border-radius: 6px;"
            class="btn  dropdown-toggle px-0" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-cog" style="font-size: 1.4rem; color: #c59a00;"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-left" role="menu"
            style=" width: max-content;padding: 10px;" style="padding: 8px 15px;">
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

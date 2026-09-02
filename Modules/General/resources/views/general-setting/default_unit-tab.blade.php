<div class="tab-pane fade show" id="defaultUnit-tab" role="tabpanel">
    <div class="container">
        <form id="update-default-unit"
            method="POST"
            action="{{ route('update-unit') }}">
            @csrf
            @method('POST')

            <div class="row my-5">
                <div class="col-4 mb-5">
                    <div class="d-flex flex-column" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                        <label class="fs-6 fw-semibold mb-2 required">
                            {{ __('general::general.Default Unit') }}
                        </label>
                    </div>

                    <input
                        type="text"
                        name="unit1"
                        class="form-control form-select"
                        value="{{ $unit->unit1 ?? '' }}"
                        required />

                    <input
                        type="hidden"
                        name="unit_transfer_id"
                        value="{{ $unit->id ?? '' }}" />

                </div>
            </div>

            <div class="separator d-flex flex-center m-5">
                <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
            </div>

            @dashboardcan(\Modules\General\Support\SettingPermissions::for('default unit', 'update'))
            <button type="submit" class="btn btn-primary w-200px" style="border-radius: 6px;">
                {{ __('messages.save') }}
            </button>
            @enddashboardcan
        </form>
    </div>
</div>
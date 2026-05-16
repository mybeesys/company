@extends('layouts.app')

@section('title', __('expense::lang.create_heading'))

@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }

        .no-spin::-webkit-outer-spin-button,
        .no-spin::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .no-spin[type="number"] {
            -moz-appearance: textfield;
            appearance: textfield;
        }
    </style>
@endsection

@section('content')
    <form id="expense-create-form" method="post" action="{{ route('expenses.manage.store') }}" enctype="multipart/form-data"
        @if ($defaultDebitId) onsubmit="return confirm(@json(__('expense::lang.confirm_create')))" @endif>
        @csrf

        <div class="container">
            <div class="row py-2">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                        <h1 class="fs-2 fw-bold mb-0">@lang('expense::lang.create_heading')</h1>
                    </div>
                </div>
                <div class="col-6 d-flex" style="justify-content: flex-end">
                    <button type="submit" style="border-radius: 6px;width: 29%;" class="btn btn-bg-primary text-white"
                        @unless ($defaultDebitId) disabled @endunless>
                        @lang('messages.save')
                    </button>
                </div>
            </div>
        </div>

        <div class="separator d-flex flex-center my-5">
            <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
        </div>

        @if ($duplicateDefaults ?? null)
            <div class="container mb-5">
                <div class="alert alert-primary py-3 mb-0">@lang('expense::lang.duplicate_prefill_hint')</div>
            </div>
        @endif

        @if (!$defaultDebitId)
            <div class="container mb-5">
                <div class="alert alert-danger mb-0">@lang('expense::lang.default_expense_account_missing')</div>
            </div>
        @endif

        <div class="container pb-12 pt-2">
            @include('expense::manage.create.partials.expense-primary')
            @include('expense::manage.create.partials.expense-secondary')
        </div>
    </form>
@endsection

@section('script')
    @parent
    <script>
        function fmtMoney(v) {
            const n = Number(v);
            if (!Number.isFinite(n)) return '—';
            return n.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function hasTaxSelected() {
            const v = $('#tax_id').val();
            return v !== null && v !== undefined && String(v).length > 0;
        }

        function toggleTaxUi() {
            const wrap = document.getElementById('tax_preview_row');
            const incCb = document.getElementById('amt_inc_tax');
            if (!wrap || !incCb) return;
            const hasTax = hasTaxSelected();
            incCb.disabled = !hasTax;
            if (!hasTax) {
                incCb.checked = false;
            }
            wrap.classList.toggle('d-none', !hasTax);
            recalcTaxPreview();
        }

        function recalcTaxPreview() {
            const amt = parseFloat(document.getElementById('exp_amount')?.value || '0') || 0;
            const inc = document.getElementById('amt_inc_tax')?.checked;
            const pct = parseFloat($('#tax_id option:selected').attr('data-percent') || '0') || 0;
            const netEl = document.getElementById('net_preview');
            const taxEl = document.getElementById('tax_preview');
            const grossEl = document.getElementById('gross_preview');
            if (!netEl || !taxEl || !grossEl) return;
            if (!hasTaxSelected() || !pct || amt <= 0) {
                netEl.textContent = '—';
                taxEl.textContent = '—';
                grossEl.textContent = '—';
                return;
            }
            if (inc) {
                const tax = amt - (amt / (1 + pct / 100));
                const net = amt - tax;
                netEl.textContent = fmtMoney(net);
                taxEl.textContent = fmtMoney(tax);
                grossEl.textContent = fmtMoney(amt);
            } else {
                const net = amt;
                const tax = net * (pct / 100);
                const gross = net + tax;
                netEl.textContent = fmtMoney(net);
                taxEl.textContent = fmtMoney(tax);
                grossEl.textContent = fmtMoney(gross);
            }
        }

        $(function() {
            const dir = document.documentElement.getAttribute('dir') === 'rtl' ? 'rtl' : 'ltr';
            $('#exp_credit_account, #exp_category_id').select2({
                width: '100%',
                dir: dir,
            });
            $('#tax_id').select2({
                width: '100%',
                dir: dir,
                allowClear: true,
                placeholder: @json(__('expense::lang.tax_option_none')),
            });
            $('#tax_id').on('change select2:select select2:clear', toggleTaxUi);
            $('#amt_inc_tax').on('change', recalcTaxPreview);
            $('#exp_amount').on('input', recalcTaxPreview);
            toggleTaxUi();

            const zone = document.getElementById('kt_expense_upload_attachments');
            const fileInput = document.getElementById('expense_attachments_input');
            const hint = document.getElementById('expense_upload_instructions');
            if (zone && fileInput && hint) {
                const defaultHint = hint.textContent.trim();
                zone.addEventListener('click', function(e) {
                    e.preventDefault();
                    fileInput.click();
                });
                zone.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        fileInput.click();
                    }
                });
                fileInput.addEventListener('change', function() {
                    if (fileInput.files && fileInput.files.length > 0) {
                        hint.textContent = Array.from(fileInput.files).map(function(f) {
                            return f.name;
                        }).join(', ');
                    } else {
                        hint.textContent = defaultHint;
                    }
                });
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(ev) {
                    zone.addEventListener(ev, function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                    });
                });
                zone.addEventListener('dragover', function() {
                    zone.classList.add('border-primary');
                });
                zone.addEventListener('dragleave', function() {
                    zone.classList.remove('border-primary');
                });
                zone.addEventListener('drop', function(e) {
                    zone.classList.remove('border-primary');
                    const files = e.dataTransfer && e.dataTransfer.files;
                    if (!files || !files.length) return;
                    try {
                        const dt = new DataTransfer();
                        Array.from(files).forEach(function(f) {
                            dt.items.add(f);
                        });
                        fileInput.files = dt.files;
                    } catch (err) {
                        return;
                    }
                    fileInput.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                });
            }

            @if ($duplicateDefaults ?? null)
                (function() {
                    const d = @json($duplicateDefaults);
                    $('#exp_credit_account').val(String(d.credit_accounting_account_id)).trigger('change');
                    $('#exp_category_id').val(String(d.expense_category_id)).trigger('change');
                    $('#expense_date').val(d.date);
                    $('#exp_amount').val(Number(d.amount));
                    if (d.tax_id) {
                        $('#tax_id').val(String(d.tax_id)).trigger('change');
                    } else {
                        $('#tax_id').val(null).trigger('change');
                    }
                    $('#amt_inc_tax').prop('checked', !!d.amount_includes_tax);
                    const desc = document.getElementById('expense_description');
                    if (desc) {
                        desc.value = d.description || '';
                    }
                    toggleTaxUi();
                })();
            @endif
        });

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
                e.preventDefault();
                document.getElementById('expense-create-form')?.requestSubmit();
            }
        });
    </script>
@endsection

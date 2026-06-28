@extends('layouts.app')

@section('title', __('accounting::lang.import_opening_balance'))

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-6">
            <div>
                <h1 class="mb-2">@lang('accounting::lang.import_opening_balance')</h1>
                <div class="text-muted">@lang('accounting::lang.import_opening_balance_hint')</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('journal-entry-import') }}" class="btn btn-light">@lang('accounting::lang.import_journal_entries')</a>
                <a href="{{ route('journal-entry-index') }}" class="btn btn-light">@lang('accounting::lang.back')</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title fw-bold">@lang('accounting::lang.import_opening_balance_instructions_title')</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">@lang('accounting::lang.import_opening_balance_instructions_body')</p>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>@lang('accounting::lang.import_journal_column')</th>
                                <th>@lang('accounting::lang.import_journal_description')</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>@lang('accounting::lang.account')</td><td>الاسم</td></tr>
                            <tr><td>@lang('accounting::lang.gl_code')</td><td>الكود</td></tr>
                            <tr><td>@lang('accounting::lang.debit')</td><td>مدين (SAR) / الرصيد قبل</td></tr>
                            <tr><td>@lang('accounting::lang.credit')</td><td>دائن (SAR)</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if (empty($preview))
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title fw-bold">@lang('accounting::lang.import_opening_balance_upload_title')</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('opening-balance-import-preview') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-4 mb-4">
                            <div class="col-md-4">
                                <label class="form-label required">@lang('accounting::lang.journalEntry_date')</label>
                                <input type="date" name="operation_date" class="form-control"
                                    value="{{ old('operation_date', '2024-12-31') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">@lang('accounting::lang.ref_number')</label>
                                <input type="text" name="ref_number" class="form-control"
                                    value="{{ old('ref_number') }}" placeholder="OPENING-20241231">
                                <div class="form-text">@lang('accounting::lang.ref_number_note')</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">@lang('accounting::lang.additionalNotes')</label>
                                <input type="text" name="additionalNotes" class="form-control"
                                    value="{{ old('additionalNotes') }}">
                            </div>
                        </div>
                        <div class="mb-4" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                            <div class="dropzone dz-clickable" style="padding: 8px 1.75rem;" id="kt_import_opening_dropzone">
                                <div class="dz-message needsclick">
                                    <i class="ki-outline ki-file-up fs-2hx text-primary mx-2"></i>
                                    <div class="ms-4" style="text-align: justify">
                                        <h3 class="dfs-5 fw-bold text-gray-900 mb-1 fs-6">@lang('accounting::lang.import_opening_balance_upload_title')</h3>
                                        <span id="importOpeningUploadInstructions" class="fw-semibold fs-6 text-muted">
                                            @lang('accounting::lang.import_journal_upload_hint')
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <input type="file" id="importOpeningFileInput" name="file" style="display: none;" accept=".xlsx,.xls" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="ki-outline ki-eye fs-4 me-2"></i>
                            @lang('accounting::lang.import_journal_preview_button')
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title fw-bold">@lang('accounting::lang.import_journal_preview_title')</h3>
                </div>
                <div class="card-body">
                    <div class="mb-4"><strong>@lang('accounting::lang.file'):</strong> {{ $preview['original_name'] ?? '' }}</div>
                    <div class="mb-4">
                        <strong>@lang('accounting::lang.journalEntry_date'):</strong> {{ $preview['operation_date'] ?? '' }}
                        @if (! empty($preview['ref_number']))
                            | <strong>@lang('accounting::lang.ref_number'):</strong> {{ $preview['ref_number'] }}
                        @endif
                    </div>
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="border rounded p-4 h-100">
                                <div class="text-muted fs-7">@lang('accounting::lang.import_journal_lines_count')</div>
                                <div class="fs-2 fw-bold">{{ $preview['lines_count'] ?? 0 }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-4 h-100">
                                <div class="text-muted fs-7">@lang('accounting::lang.debit')</div>
                                <div class="fs-4 fw-bold">{{ number_format((float) ($preview['debit_total'] ?? 0), 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-4 h-100">
                                <div class="text-muted fs-7">@lang('accounting::lang.credit')</div>
                                <div class="fs-4 fw-bold">{{ number_format((float) ($preview['credit_total'] ?? 0), 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-4 h-100">
                                <div class="text-muted fs-7">@lang('accounting::lang.import_journal_missing_accounts_count')</div>
                                <div class="fs-2 fw-bold text-warning">{{ count($preview['missing_gl_codes'] ?? []) }}</div>
                            </div>
                        </div>
                    </div>

                    @if (! empty($preview['missing_gl_codes']))
                        <div class="alert alert-warning">
                            <strong>@lang('accounting::lang.import_journal_missing_gl_codes_title')</strong>
                            <div class="mt-2">{{ implode(', ', array_slice($preview['missing_gl_codes'], 0, 30)) }}</div>
                        </div>
                    @endif

                    @if (! empty($preview['parse_errors']))
                        <div class="alert alert-danger">
                            <strong>@lang('accounting::lang.import_journal_parse_errors_title')</strong>
                            <ul class="mb-0 mt-2">
                                @foreach (array_slice($preview['parse_errors'], 0, 10) as $error)
                                    <li>
                                        @lang('accounting::lang.import_opening_balance_error_line', [
                                            'row' => $error['row'],
                                            'message' => __('accounting::lang.import_opening_balance_error_'.$error['message']),
                                        ])
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (! empty($preview['sample_lines']))
                        <h4 class="fw-bold mb-3">@lang('accounting::lang.import_journal_sample_title')</h4>
                        <div class="table-responsive">
                            <table class="table table-row-dashed">
                                <thead>
                                    <tr>
                                        <th>@lang('accounting::lang.gl_code')</th>
                                        <th>@lang('accounting::lang.account')</th>
                                        <th>@lang('accounting::lang.debit')</th>
                                        <th>@lang('accounting::lang.credit')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($preview['sample_lines'] as $line)
                                        <tr>
                                            <td>{{ $line['gl_code'] }}</td>
                                            <td>{{ $line['account_name'] }}</td>
                                            <td>{{ $line['debit'] !== '0.00' ? $line['debit'] : '—' }}</td>
                                            <td>{{ $line['credit'] !== '0.00' ? $line['credit'] : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="alert alert-info mt-4">
                        @lang('accounting::lang.import_opening_balance_single_entry_note')
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body d-flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('opening-balance-import-process') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary"
                            @if (! empty($preview['missing_gl_codes']) || ! empty($preview['parse_errors'])) disabled @endif>
                            <i class="ki-outline ki-arrow-up fs-4 me-2"></i>
                            @lang('accounting::lang.import_opening_balance_confirm_button')
                        </button>
                    </form>
                    <form method="POST" action="{{ route('opening-balance-import-cancel') }}">
                        @csrf
                        <button type="submit" class="btn btn-light">@lang('accounting::lang.cancel')</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('script')
    @parent
    <script>
        (function() {
            const dropzone = document.getElementById('kt_import_opening_dropzone');
            const input = document.getElementById('importOpeningFileInput');
            const label = document.getElementById('importOpeningUploadInstructions');
            if (!dropzone || !input || !label) return;

            dropzone.addEventListener('click', function() { input.click(); });
            input.addEventListener('change', function(e) {
                const files = e.target.files || [];
                label.textContent = files.length > 0
                    ? Array.from(files).map(f => f.name).join(', ')
                    : @json(__('accounting::lang.import_journal_upload_hint'));
            });
        })();
    </script>
@endsection

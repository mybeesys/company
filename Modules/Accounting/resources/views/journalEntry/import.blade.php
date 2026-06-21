@extends('layouts.app')

@section('title', __('accounting::lang.import_journal_entries'))

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-6">
            <div>
                <h1 class="mb-2">@lang('accounting::lang.import_journal_entries')</h1>
                <div class="text-muted">
                    @lang('accounting::lang.import_journal_entries_hint')
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
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
                <h3 class="card-title fw-bold">@lang('accounting::lang.import_journal_instructions_title')</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">@lang('accounting::lang.import_journal_instructions_body')</p>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead class="bg-light">
                            <tr class="fw-semibold text-gray-700">
                                <th>@lang('accounting::lang.import_journal_column')</th>
                                <th>@lang('accounting::lang.import_journal_required')</th>
                                <th>@lang('accounting::lang.import_journal_description')</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>@lang('accounting::lang.operation_date')</td>
                                <td><span class="badge badge-light-danger">@lang('accounting::lang.yes')</span></td>
                                <td>01/01/2025</td>
                            </tr>
                            <tr>
                                <td>@lang('accounting::lang.journal_entry_no')</td>
                                <td><span class="badge badge-light-danger">@lang('accounting::lang.yes')</span></td>
                                <td>2020</td>
                            </tr>
                            <tr>
                                <td>@lang('accounting::lang.account')</td>
                                <td><span class="badge badge-light-warning">@lang('accounting::lang.optional')</span></td>
                                <td>@lang('accounting::lang.import_journal_account_name_hint')</td>
                            </tr>
                            <tr>
                                <td>GL / @lang('accounting::lang.gl_code')</td>
                                <td><span class="badge badge-light-danger">@lang('accounting::lang.yes')</span></td>
                                <td>120103</td>
                            </tr>
                            <tr>
                                <td>@lang('accounting::lang.line_description')</td>
                                <td><span class="badge badge-light-warning">@lang('accounting::lang.optional')</span></td>
                                <td>@lang('accounting::lang.import_journal_line_note_hint')</td>
                            </tr>
                            <tr>
                                <td>@lang('accounting::lang.debit') / @lang('accounting::lang.credit')</td>
                                <td><span class="badge badge-light-danger">@lang('accounting::lang.yes')</span></td>
                                <td>@lang('accounting::lang.import_journal_amount_hint')</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if (empty($preview))
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title fw-bold">@lang('accounting::lang.import_journal_upload_title')</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('journal-entry-import-preview') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                            <div class="dropzone dz-clickable" style="padding: 8px 1.75rem;" id="kt_import_journal_dropzone">
                                <div class="dz-message needsclick">
                                    <i class="ki-outline ki-file-up fs-2hx text-primary mx-2"></i>
                                    <div class="ms-4" style="text-align: justify">
                                        <h3 class="dfs-5 fw-bold text-gray-900 mb-1 fs-6">@lang('accounting::lang.import_journal_upload_title')</h3>
                                        <span id="importJournalUploadInstructions" class="fw-semibold fs-6 text-muted">
                                            @lang('accounting::lang.import_journal_upload_hint')
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <input type="file" id="importJournalFileInput" name="file" style="display: none;" accept=".xlsx,.xls" required>
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
                    <div class="mb-4">
                        <strong>@lang('accounting::lang.file'):</strong> {{ $preview['original_name'] ?? '' }}
                    </div>
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="border rounded p-4 h-100">
                                <div class="text-muted fs-7">@lang('accounting::lang.journals_count')</div>
                                <div class="fs-2 fw-bold">{{ $preview['entries_count'] ?? 0 }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-4 h-100">
                                <div class="text-muted fs-7">@lang('accounting::lang.import_journal_lines_count')</div>
                                <div class="fs-2 fw-bold">{{ $preview['lines_count'] ?? 0 }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-4 h-100">
                                <div class="text-muted fs-7">@lang('accounting::lang.import_journal_parse_errors_count')</div>
                                <div class="fs-2 fw-bold text-danger">{{ count($preview['parse_errors'] ?? []) }}</div>
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
                            @if (count($preview['missing_gl_codes']) > 30)
                                <div class="mt-2 text-muted">@lang('accounting::lang.import_journal_and_more', ['count' => count($preview['missing_gl_codes']) - 30])</div>
                            @endif
                            <div class="mt-3">
                                <a href="{{ route('tree-of-accounts-import') }}" class="btn btn-sm btn-light-primary">
                                    @lang('accounting::lang.import_tree_of_accounts')
                                </a>
                            </div>
                        </div>
                    @endif

                    @if (! empty($preview['duplicate_refs']))
                        <div class="alert alert-info">
                            <strong>@lang('accounting::lang.import_journal_duplicate_refs_title')</strong>
                            <div class="mt-2">{{ implode(', ', array_slice($preview['duplicate_refs'], 0, 20)) }}</div>
                            @if (count($preview['duplicate_refs']) > 20)
                                <div class="mt-2 text-muted">@lang('accounting::lang.import_journal_and_more', ['count' => count($preview['duplicate_refs']) - 20])</div>
                            @endif
                        </div>
                    @endif

                    @if (! empty($preview['parse_errors']))
                        <div class="alert alert-danger">
                            <strong>@lang('accounting::lang.import_journal_parse_errors_title')</strong>
                            <ul class="mb-0 mt-2">
                                @foreach (array_slice($preview['parse_errors'], 0, 10) as $error)
                                    <li>
                                        @lang('accounting::lang.import_journal_error_line', [
                                            'ref' => $error['ref_no'] ?? '-',
                                            'row' => $error['row'],
                                            'message' => __('accounting::lang.import_journal_error_'.$error['message']),
                                        ])
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (! empty($preview['sample_entries']))
                        <h4 class="fw-bold mb-3">@lang('accounting::lang.import_journal_sample_title')</h4>
                        <div class="table-responsive">
                            <table class="table table-row-dashed">
                                <thead>
                                    <tr>
                                        <th>@lang('accounting::lang.journal_entry_no')</th>
                                        <th>@lang('accounting::lang.operation_date')</th>
                                        <th>@lang('accounting::lang.import_journal_lines_count')</th>
                                        <th>@lang('accounting::lang.line_description')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($preview['sample_entries'] as $entry)
                                        <tr>
                                            <td>{{ $entry['ref_no'] }}</td>
                                            <td>{{ $entry['operation_date'] }}</td>
                                            <td>{{ count($entry['lines']) }}</td>
                                            <td>{{ $entry['note'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body d-flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('journal-entry-import-process') }}">
                        @csrf
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="skip_duplicates" id="skip_duplicates" value="1" checked>
                            <label class="form-check-label" for="skip_duplicates">
                                @lang('accounting::lang.import_journal_skip_duplicates')
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary"
                            @if (! empty($preview['missing_gl_codes'])) disabled @endif>
                            <i class="ki-outline ki-arrow-up fs-4 me-2"></i>
                            @lang('accounting::lang.import_journal_confirm_button')
                        </button>
                    </form>
                    <form method="POST" action="{{ route('journal-entry-import-cancel') }}">
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
            const dropzone = document.getElementById('kt_import_journal_dropzone');
            const input = document.getElementById('importJournalFileInput');
            const label = document.getElementById('importJournalUploadInstructions');
            if (!dropzone || !input || !label) return;

            dropzone.addEventListener('click', function() {
                input.click();
            });

            input.addEventListener('change', function(e) {
                const files = e.target.files || [];
                label.textContent = files.length > 0
                    ? Array.from(files).map(f => f.name).join(', ')
                    : @json(__('accounting::lang.import_journal_upload_hint'));
            });

            const prevent = (e) => { e.preventDefault(); e.stopPropagation(); };
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
                dropzone.addEventListener(evt, prevent, false);
            });
            dropzone.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                if (!dt || !dt.files || dt.files.length === 0) return;
                input.files = dt.files;
                label.textContent = Array.from(dt.files).map(f => f.name).join(', ');
            });
        })();
    </script>
@endsection

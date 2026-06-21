@extends('layouts.app')

@section('title', __('accounting::lang.import_tree_of_accounts'))

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-6">
            <div>
                <h1 class="mb-2">@lang('accounting::lang.import_tree_of_accounts')</h1>
                <div class="text-muted">
                    استيراد الدليل المحاسبي حسب بنية الشجرة (حسابات رئيسية وفرعية).
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('tree-of-accounts-import-template') }}" class="btn btn-light-danger">
                    <i class="ki-outline ki-file-down fs-4 me-2"></i>
                    تحميل قالب Excel
                </a>
                <form method="POST" action="{{ route('tree-of-accounts-repair-gl-codes') }}" class="d-inline"
                    onsubmit="return confirm(@json(__('accounting::lang.repair_gl_codes_confirm')));">
                    @csrf
                    <button type="submit" class="btn btn-light-primary">
                        <i class="ki-outline ki-wrench fs-4 me-2"></i>
                        @lang('accounting::lang.repair_gl_codes_button')
                    </button>
                </form>
                <a href="{{ route('tree-of-accounts') }}" class="btn btn-light">@lang('accounting::lang.back')</a>
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
                <h3 class="card-title fw-bold">تعليمات الاستيراد</h3>
            </div>
            <div class="card-body">
                <div class="mb-4 text-muted">
                    القالب يحتوي مثال جاهز في أول سطرين. اتركهم كما هم أو احذفهم بعد أن تفهم البنية.
                </div>

                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead class="bg-light">
                            <tr class="fw-semibold text-gray-700">
                                <th style="width: 180px;">الحقل</th>
                                <th style="width: 120px;">النوع</th>
                                <th style="width: 120px;">إلزامي؟</th>
                                <th>الشرح</th>
                                <th style="width: 180px;">مثال</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>gl_code</code></td>
                                <td>نص/رقم</td>
                                <td><span class="badge badge-light-danger">إلزامي</span></td>
                                <td>رقم الحساب (GL Code) ويجب أن يكون <span class="fw-semibold">فريد</span>.</td>
                                <td><code>1101</code></td>
                            </tr>
                            <tr>
                                <td><code>name_ar</code></td>
                                <td>نص</td>
                                <td><span class="badge badge-light-danger">إلزامي</span></td>
                                <td>اسم الحساب باللغة العربية.</td>
                                <td>النقدية بالصندوق</td>
                            </tr>
                            <tr>
                                <td><code>name_en</code></td>
                                <td>نص</td>
                                <td><span class="badge badge-light-danger">إلزامي</span></td>
                                <td>اسم الحساب باللغة الإنجليزية.</td>
                                <td>Cash on hand</td>
                            </tr>
                            <tr>
                                <td><code>account_primary_type</code></td>
                                <td>نص (قيمة من قائمة)</td>
                                <td><span class="badge badge-light-danger">إلزامي</span></td>
                                <td>
                                    نوع الحساب الرئيسي. القيم المقبولة عادة:
                                    <code>asset</code>, <code>liabilities</code>, <code>equity</code>, <code>income</code>, <code>expenses</code>, <code>analytical_accounts</code>.
                                </td>
                                <td><code>asset</code></td>
                            </tr>
                            <tr>
                                <td><code>parent_gl_code</code></td>
                                <td>نص/رقم</td>
                                <td><span class="badge badge-light-warning">اختياري</span></td>
                                <td>
                                    رقم حساب الأب (لإنشاء الحساب كفرعي). اتركه فارغاً للحسابات الرئيسية.
                                </td>
                                <td><code>111</code></td>
                            </tr>
                            <tr>
                                <td><code>status</code></td>
                                <td>نص</td>
                                <td><span class="badge badge-light-warning">اختياري</span></td>
                                <td>حالة الحساب: <code>active</code> أو <code>inactive</code>. الافتراضي: <code>active</code>.</td>
                                <td><code>active</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title fw-bold">رفع ملف الدليل المحاسبي</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('tree-of-accounts-import-store') }}" enctype="multipart/form-data">
                    @csrf
                    @if (\Modules\Accounting\Models\AccountingAccount::exists())
                        <div class="alert alert-warning mb-4">
                            @lang('accounting::lang.import_tree_accounts_replace_hint')
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="replace_existing" id="replace_existing" value="1">
                            <label class="form-check-label" for="replace_existing">
                                @lang('accounting::lang.import_tree_accounts_replace_label')
                            </label>
                        </div>
                    @endif
                    <div class="mb-4" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                        <div class="dropzone dz-clickable" style="padding: 8px 1.75rem;" id="kt_import_tree_accounts_dropzone">
                            <div class="dz-message needsclick">
                                <i class="ki-outline ki-file-up fs-2hx text-primary mx-2"></i>
                                <div class="ms-4" style="text-align: justify">
                                    <h3 class="dfs-5 fw-bold text-gray-900 mb-1 fs-6">رفع ملف Excel</h3>
                                    <span id="importUploadInstructions" class="fw-semibold fs-6 text-muted">
                                        اسحب الملف هنا أو اضغط للاختيار (xlsx / xls)
                                    </span>
                                </div>
                            </div>
                        </div>
                        <input type="file" id="importFileInput" name="file" style="display: none;" accept=".xlsx,.xls" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="ki-outline ki-arrow-up fs-4 me-2"></i>
                        استيراد الدليل المحاسبي
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    @parent
    <script>
        (function() {
            const dropzone = document.getElementById('kt_import_tree_accounts_dropzone');
            const input = document.getElementById('importFileInput');
            const label = document.getElementById('importUploadInstructions');

            if (!dropzone || !input || !label) return;

            dropzone.addEventListener('click', function() {
                input.click();
            });

            input.addEventListener('change', function(e) {
                const files = e.target.files || [];
                if (files.length > 0) {
                    const names = Array.from(files).map(f => f.name);
                    label.textContent = names.join(', ');
                } else {
                    label.textContent = 'اسحب الملف هنا أو اضغط للاختيار (xlsx / xls)';
                }
            });

            const prevent = (e) => { e.preventDefault(); e.stopPropagation(); };
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
                dropzone.addEventListener(evt, prevent, false);
            });
            dropzone.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                if (!dt || !dt.files || dt.files.length === 0) return;
                input.files = dt.files;
                const names = Array.from(dt.files).map(f => f.name);
                label.textContent = names.join(', ');
            });
        })();
    </script>
@endsection


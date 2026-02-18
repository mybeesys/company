@extends('layouts.app')

@section('title', 'إدارة الممنوحين')

@section('content')
<div class="card card-flush">
    <div class="card-header pt-8">
        <div class="card-title">
            <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
                <li class="nav-item">
                    <a class="nav-link text-active-primary active filter-tab" data-view="all" href="#">الكل</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-active-warning filter-tab" data-view="new_no_contract" href="#">جديدة (بلا عقد)</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-active-success filter-tab" data-view="active_contracts" href="#">عقود فعالة</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-active-danger filter-tab" data-view="expired_contracts" href="#">عقود منتهية</a>
                </li>
            </ul>
        </div>
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" onclick="addCompanyModal()">
                <i class="fa fa-plus"></i> إضافة ممنوح جديد
            </button>
        </div>
    </div>

    <div class="card-body">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="companies_table">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th>الممنوح</th>
                    <th>المدينة</th>
                    <th>الرقم الضريبي</th>
                    <th>الحالة</th>
                    <th>الجوال</th>
                    <th class="text-end">العمليات</th>
                </tr>
            </thead>
        </table>
    </div>
</div>


<div class="modal fade" id="kt_modal_add_company" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <form class="form" action="#" id="kt_modal_add_company_form">
                @csrf
                <input type="hidden" name="company_id" id="company_id">
                <div class="modal-header">
                    <h2 class="fw-bold" id="modal_title">إضافة ممنوح جديد</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i>
                    </div>
                </div>
                <div class="modal-body py-10 px-lg-17">
                    <div class="row g-9 mb-7">
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">الاسم بالعربي</label>
                            <input type="text" class="form-control form-control-solid" name="name_ar" id="name_ar" required />
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">الاسم بالإنجليزي</label>
                            <input type="text" class="form-control form-control-solid" name="name_en" id="name_en" required />
                        </div>
                    </div>
                    <div class="row g-9 mb-7">
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">الرقم الضريبي</label>
                            <input type="text" class="form-control form-control-solid" name="vat_no" id="vat_no" />
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">رقم الجوال</label>
                            <input type="text" class="form-control form-control-solid" name="mobile" id="mobile" />
                        </div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">البريد الإلكتروني</label>
                        <input type="email" class="form-control form-control-solid" name="email" id="email" />
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" id="kt_modal_add_company_submit" class="btn btn-primary">
                        <span class="indicator-label">حفظ البيانات</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    let currentView = 'all';
    let table = $('#companies_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('franchise.companies.index') }}",
            data: function(d) { d.view_type = currentView; }
        },
        columns: [
            { data: 'name_ar', name: 'name_ar' },
            { data: 'city', name: 'city' },
            { data: 'vat_no', name: 'vat_no' },
            { data: 'status_label', name: 'status_label', searchable: false },
            { data: 'mobile', name: 'mobile' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    // تبديل العرض عند الضغط على التبويب
    $('.filter-tab').on('click', function(e) {
        e.preventDefault();
        $('.nav-link').removeClass('active');
        $(this).addClass('active');
        currentView = $(this).data('view');
        table.draw();
    });
    // دالة فتح المودال للإضافة
function addCompanyModal() {
    $('#kt_modal_add_company_form')[0].reset();
    $('#company_id').val('');
    $('#modal_title').text('إضافة ممنوح جديد');
    $('#kt_modal_add_company').modal('show');
}

// دالة فتح المودال للتعديل (تعبئة البيانات تلقائياً)
function editCompany(id) {
    $.get(`/franchise/companies/${id}`, function(data) {
        $('#company_id').val(data.id);
        $('#name_ar').val(data.name_ar);
        $('#name_en').val(data.name_en);
        $('#vat_no').val(data.vat_no);
        $('#mobile').val(data.mobile);
        $('#email').val(data.email);
        $('#modal_title').text('تعديل بيانات الممنوح');
        $('#kt_modal_add_company').modal('show');
    });
}

// تنفيذ الحفظ (Add/Update) عبر AJAX
$('#kt_modal_add_company_form').on('submit', function(e) {
    e.preventDefault();
    let id = $('#company_id').val();
    let url = id ? `/franchise/companies/${id}` : '/franchise/companies';
    let method = id ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        method: method,
        data: $(this).serialize(),
        success: function(res) {
            if(res.success) {
                Swal.fire("تم الحفظ!", res.message, "success");
                $('#kt_modal_add_company').modal('hide');
                table.draw(); // إعادة تحميل جدول الداتا تيبل
            }
        },
        error: function(err) {
            Swal.fire("خطأ!", "تأكد من البيانات المدخلة (الرقم الضريبي مكرر مثلاً)", "error");
        }
    });
});
</script>
@endsection
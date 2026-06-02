@extends(request()->boolean('embed') ? 'layouts.embed' : 'layouts.app')
@section('title', __('franchise::lang.branches_mgmt'))

@section('content')
<div class="card card-flush shadow-sm">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3 class="fw-bold">{{ __('franchise::lang.branches_mgmt') }}</h3>
        </div>
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" onclick="addBranchModal()">
                <i class="ki-outline ki-plus fs-2"></i> {{ __('franchise::lang.add_new_branch') }}
            </button>
        </div>
    </div>
    <div class="card-body py-4">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="branches_table">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th class="text-start">{{ __('franchise::lang.branch_code') }}</th>
                        <th class="text-start">{{ __('franchise::lang.branch_name') }}</th>
                        <th class="text-start">{{ __('franchise::lang.franchisee') }}</th>
                        <th class="text-start">{{ __('franchise::lang.city_region') }}</th>
                        <th class="text-start">{{ __('franchise::lang.status') }}</th>
                        <th class="text-end min-w-100px">{{ __('franchise::lang.actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="kt_modal_branch" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <form id="branch_form" class="modal-content" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="branch_id" id="branch_id">

            <div class="modal-header">
                <h2 class="fw-bold" id="modal_title">{{ __('franchise::lang.add_new_branch') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>

            <div class="modal-body py-10 px-lg-17">
                <div class="row row-cols-1 row-cols-sm-2 mb-8">
                    <div class="col fv-row">
                        <label class="required fs-6 fw-semibold mb-2 d-block text-start">{{ __('franchise::lang.franchisee') }}</label>
                        <select name="franchise_id" id="franchise_id" class="form-select form-select-solid" data-control="select2" data-placeholder="{{ __('franchise::lang.select') }}" data-dropdown-parent="#kt_modal_branch">
                            <option></option>
                            @foreach ($franchises as $f)
                                <option value="{{ $f->id }}">{{ $f->name_ar }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col fv-row mt-5 mt-sm-0">
                        <label class="required fs-6 fw-semibold mb-2 d-block text-start">{{ __('franchise::lang.branch_code') }}</label>
                        <input type="text" class="form-control form-control-solid" name="code" id="code" required />
                    </div>
                </div>

                <div class="row row-cols-1 row-cols-sm-2 mb-8">
                    <div class="col fv-row">
                        <label class="required fs-6 fw-semibold mb-2 d-block text-start">{{ __('franchise::lang.name_ar') }}</label>
                        <input type="text" class="form-control form-control-solid" name="name" id="name" required />
                    </div>
                    <div class="col fv-row mt-5 mt-sm-0">
                        <label class="required fs-6 fw-semibold mb-2 d-block text-start">{{ __('franchise::lang.name_en') }}</label>
                        <input type="text" class="form-control form-control-solid" name="name_en" id="name_en" required />
                    </div>
                </div>

                <div class="row mb-8">
                    <div class="col-md-4 fv-row">
                        <label class="required fs-6 fw-semibold mb-2 d-block text-start">{{ __('franchise::lang.city') }}</label>
                        <input type="text" class="form-control form-control-solid" name="city" id="city" required />
                    </div>
                    <div class="col-md-4 fv-row mt-5 mt-md-0">
                        <label class="fs-6 fw-semibold mb-2 d-block text-start">{{ __('franchise::lang.region') }}</label>
                        <input type="text" class="form-control form-control-solid" name="region" id="region" />
                    </div>
                    <div class="col-md-4 fv-row mt-5 mt-md-0">
                        <label class="fs-6 fw-semibold mb-2 d-block text-start">{{ __('franchise::lang.theme') }}</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-solid h-40px" name="theme" id="theme" value="#0095E8" />
                        </div>
                    </div>
                </div>

                <div class="fv-row mb-8">
                    <label class="fs-6 fw-semibold mb-2 d-block text-start">{{ __('franchise::lang.address') }}</label>
                    <textarea class="form-control form-control-solid" name="address" id="address" rows="3"></textarea>
                </div>

                <div class="row row-cols-1 row-cols-sm-2 mb-8">
                    <div class="col fv-row">
                        <label class="fs-6 fw-semibold mb-2 d-block text-start">{{ __('franchise::lang.contact') }}</label>
                        <input type="text" class="form-control form-control-solid" name="contact_details" id="contact_details" />
                    </div>
                    {{-- <div class="col fv-row mt-5 mt-sm-0">
                        <label class="fs-6 fw-semibold mb-2 d-block text-start">{{ __('franchise::lang.logo') }}</label>
                        <input type="file" class="form-control form-control-solid" name="logo" accept="image/*" />
                    </div> --}}
                </div>
            </div>

            <div class="modal-footer flex-center">
                <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('franchise::lang.cancel') }}</button>
                <button type="submit" id="kt_modal_branch_submit" class="btn btn-primary">
                    <span class="indicator-label">{{ __('franchise::lang.save') }}</span>
                    <span class="indicator-progress">{{ __('franchise::lang.wait') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script')
    <script>
        let table = $('#branches_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('franchise.branches.index') }}",
            columns: [{
                    data: 'code'
                },
                {
                    data: 'name'
                },
                {
                    data: 'franchise_name'
                },
                {
                    data: 'location_info',
                    name: 'city'
                },
                {
                    data: 'status_label',
                    name: 'is_active'
                },
                {
                    data: 'actions',
                    className: 'text-end',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [
                [0, 'desc']
            ],

        });

        function addBranchModal() {
            $('#modal_title').text('إضافة فرع جديد');
            $('#branch_id').val('');
            $('#branch_form')[0].reset();
            $('#franchise_id').val(null).trigger('change');
            $('#kt_modal_branch').modal('show');
        }

        function editBranch(id) {
            $.get("{{ url('franchise/branches') }}/" + id + "/edit", function(data) {
                if (data) {
                    $('#modal_title').text('تعديل فرع: ' + data.name);
                    $('#branch_id').val(data.id);
                    $('#code').val(data.code);
                    $('#name').val(data.name);
                    $('#name_en').val(data.name_en);
                    $('#city').val(data.city);
                    $('#region').val(data.region);
                    $('#address').val(data.address);
                    $('#contact_details').val(data.contact_details);
                    $('#theme').val(data.theme || '#0095E8');
                    $('#franchise_id').val(data.franchise_id).trigger('change');
                    $('#kt_modal_branch').modal('show');
                }
            }).fail(function() {
                Swal.fire({
                    text: "خطأ في جلب البيانات",
                    icon: "error",
                    confirmButtonText: "موافق"
                });
            });
        }

        $('#branch_form').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#kt_modal_branch_submit');
            btn.attr('data-kt-indicator', 'on').prop('disabled', true);

            let formData = new FormData(this);
            let id = $('#branch_id').val();
            let url = id ? "{{ url('franchise/branches') }}/" + id : "{{ route('franchise.branches.store') }}";

            if (id) {
                formData.append('_method', 'PUT');
            }

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    btn.removeAttr('data-kt-indicator').prop('disabled', false);
                    $('#kt_modal_branch').modal('hide');
                    table.ajax.reload();
                    Swal.fire({
                        text: "تم حفظ البيانات بنجاح",
                        icon: "success",
                        confirmButtonText: "موافق"
                    });
                },
                error: function(err) {
                    btn.removeAttr('data-kt-indicator').prop('disabled', false);
                    let msg = err.responseJSON?.message || "حدث خطأ أثناء الحفظ";
                    Swal.fire({
                        text: msg,
                        icon: "error",
                        confirmButtonText: "موافق"
                    });
                }
            });
        });

        function deleteBranch(id) {
            Swal.fire({
                text: "هل أنت متأكد من حذف هذا الفرع؟",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "نعم، احذف",
                cancelButtonText: "إلغاء",
                customClass: {
                    confirmButton: "btn btn-danger",
                    cancelButton: "btn btn-light"
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('franchise/branches') }}/" + id,
                        method: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function() {
                            table.ajax.reload();
                            Swal.fire({
                                text: "تم الحذف بنجاح",
                                icon: "success",
                                confirmButtonText: "موافق"
                            });
                        }
                    });
                }
            });
        }
    </script>
@endsection

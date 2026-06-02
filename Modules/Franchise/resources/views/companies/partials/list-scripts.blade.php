<script>
    let currentView = 'all';
    let companiesTable = null;

    function initCompaniesTable() {
        if (companiesTable || !document.getElementById('companies_table')) {
            return;
        }

        companiesTable = window.companiesTable = $('#companies_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('franchise.companies.index') }}",
                data: function (d) {
                    d.view_type = currentView;
                }
            },
            columns: [
                { data: 'name_ar' },
                { data: 'city' },
                { data: 'vat_no' },
                { data: 'status_label' },
                { data: 'mobile' },
                { data: 'actions', orderable: false, searchable: false }
            ],
            drawCallback: function () {
                KTMenu.createInstances();
            }
        });

        $('#franchise-hub-companies-wrap .filter-tab').on('click', function (e) {
            e.preventDefault();
            $('#franchise-hub-companies-wrap .filter-tab').removeClass('active');
            $(this).addClass('active');
            currentView = $(this).data('view');
            companiesTable.draw();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('franchise-hub-companies-wrap') && !document.getElementById('franchise-hub-companies-wrap').classList.contains('d-none')) {
            initCompaniesTable();
        }

        window.initFranchiseCompaniesTable = initCompaniesTable;
    });

    function addCompanyModal() {
        $('#kt_modal_add_company_form')[0].reset();
        $('#company_id').val('');
        $('#city, #account, #product_permission').val(null).trigger('change');
        $('#modal_title').text("{{ __('franchise::lang.add_new') }}");
        $('#kt_modal_add_company').modal('show');
    }

    function editCompany(id) {
        $.get(`/franchise/companies/${id}`, function (data) {
            $('#company_id').val(data.id);
            $('#name_ar').val(data.name_ar);
            $('#name_en').val(data.name_en);
            $('#vat_no').val(data.vat_no);
            $('#mobile').val(data.mobile);
            $('#email').val(data.email);
            $('#street').val(data.street);
            $('#tel').val(data.tel);
            $('#national_address').val(data.national_address);
            $('#city').val(data.city).trigger('change');
            $('#account').val(data.account).trigger('change');
            $('#product_permission').val(data.product_permission).trigger('change');
            $('#modal_title').text("{{ __('franchise::lang.edit') }}");
            $('#kt_modal_add_company').modal('show');
        });
    }

    $('#kt_modal_add_company_form').on('submit', function (e) {
        e.preventDefault();
        const btn = document.querySelector('#kt_modal_add_company_submit');
        btn.setAttribute('data-kt-indicator', 'on');
        btn.disabled = true;

        let id = $('#company_id').val();
        let url = id ? `/franchise/companies/${id}` : "{{ route('franchise.companies.store') }}";

        $.ajax({
            url: url,
            method: 'POST',
            data: $(this).serialize() + (id ? '&_method=PUT' : ''),
            success: function (res) {
                btn.removeAttribute('data-kt-indicator');
                btn.disabled = false;
                $('#kt_modal_add_company').modal('hide');
                if (companiesTable) {
                    companiesTable.draw(false);
                }
                Swal.fire({
                    text: res.message,
                    icon: 'success',
                    confirmButtonText: "{{ __('franchise::lang.ok') }}",
                    customClass: { confirmButton: 'btn btn-primary' }
                });
            },
            error: function (err) {
                btn.removeAttribute('data-kt-indicator');
                btn.disabled = false;
                let errorMessage = "{{ __('franchise::lang.error') }}";
                if (err.responseJSON && err.responseJSON.message) {
                    errorMessage = err.responseJSON.message;
                }
                Swal.fire({
                    text: errorMessage,
                    icon: 'error',
                    confirmButtonText: "{{ __('franchise::lang.ok') }}",
                    customClass: { confirmButton: 'btn btn-primary' }
                });
            }
        });
    });
</script>

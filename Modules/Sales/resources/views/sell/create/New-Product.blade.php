<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">@lang('sales::lang.add_new_product')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addProductForm">
                    @csrf
                    <div class="row">
                        <!-- Column 1 -->
                        <div class="col-md-6">
                            <!-- Arabic Name -->
                            <div class="mb-3">
                                <label for="name_ar" class="form-label">@lang('sales::fields.name_ar') *</label>
                                <input type="text" class="form-control" id="name_ar" name="name_ar" required maxlength="255">
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label for="category_id" class="form-label">@lang('sales::fields.category')</label>
                                <select class="form-control" id="category_id" name="category_id">
                                    <option value="">@lang('sales::lang.select_category')</option>
                                    <!-- Categories would be populated dynamically -->
                                </select>
                            </div>

                            <!-- Price -->
                            <div class="mb-3">
                                <label for="price" class="form-label">@lang('sales::fields.price')</label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" min="0">
                            </div>

                            <!-- Order -->
                            <div class="mb-3">
                                <label for="order" class="form-label">@lang('sales::fields.order')</label>
                                <input type="number" class="form-control" id="order" name="order">
                            </div>
                        </div>

                        <!-- Column 2 -->
                        <div class="col-md-6">
                            <!-- English Name -->
                            <div class="mb-3">
                                <label for="name_en" class="form-label">@lang('sales::fields.name_en') *</label>
                                <input type="text" class="form-control" id="name_en" name="name_en" required maxlength="255">
                            </div>

                            <!-- Subcategory -->
                            <div class="mb-3">
                                <label for="subcategory_id" class="form-label">@lang('sales::fields.subcategory')</label>
                                <select class="form-control" id="subcategory_id" name="subcategory_id" disabled>
                                    <option value="">@lang('sales::lang.select_subcategory')</option>
                                    <!-- Subcategories would be populated dynamically -->
                                </select>
                            </div>

                            <!-- Cost -->
                            <div class="mb-3">
                                <label for="cost" class="form-label">@lang('sales::fields.cost')</label>
                                <input type="number" step="0.01" class="form-control" id="cost" name="cost" min="0">
                            </div>

                            <!-- Unit -->
                            <div class="mb-3">
                                <label for="unit1" class="form-label">@lang('sales::fields.unit')</label>
                                <input type="text" class="form-control" id="unit1" name="unit1" maxlength="255">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('sales::lang.close')</button>
                  <button type="button" class="btn btn-primary" id="saveProductBtn">@lang('sales::lang.save')</button>  </div>
                </form>
            </div>
        </div>
    </div>
</div>


{{-- <script>
$(document).ready(function() {
    // تعبئة قائمة الفئات
    $.ajax({
        url: "{{ route('categoryList') }}",
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            $('#category_id').empty();
            $('#category_id').append('<option value="">@lang('sales::lang.select_category')</option>');
            $.each(response, function(key, value) {
                $('#category_id').append('<option value="'+value.id+'">'+value.name_ar+' - '+value.name_en+'</option>');
            });
        }
    });

    // عند تغيير الفئة الرئيسية
    $('#category_id').change(function() {
        var categoryId = $(this).val();
        if(categoryId) {
            $('#subcategory_id').prop('disabled', false);

            // جلب التصنيفات الفرعية
            $.ajax({
                url: "{{ route('subcategoryList', '') }}/"+categoryId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#subcategory_id').empty();
                    $('#subcategory_id').append('<option value="">@lang('sales::lang.select_subcategory')</option>');
                    $.each(response, function(key, value) {
                        $('#subcategory_id').append('<option value="'+value.id+'">'+value.name_ar+' - '+value.name_en+'</option>');
                    });
                }
            });
        } else {
            $('#subcategory_id').prop('disabled', true);
            $('#subcategory_id').empty();
            $('#subcategory_id').append('<option value="">@lang('sales::lang.select_subcategory')</option>');
        }
    });
});
</script> --}}

<script>
$(document).ready(function() {
    // تعبئة قائمة الفئات الرئيسية
    $.ajax({
        url: "{{ route('categoryList') }}",
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            $('#category_id').empty();
            $('#category_id').append('<option value="">@lang('sales::lang.select_category')</option>');

            // فلترة العناصر التي ليست فارغة ولها بيانات
            const validCategories = response.filter(item => item.data && item.data.id && !item.data.empty);

            $.each(validCategories, function(index, category) {
                $('#category_id').append(
                    `<option value="${category.data.id}">
                        ${category.data.name_ar} - ${category.data.name_en}
                    </option>`
                );
            });
        },
        error: function(xhr) {
            console.error('Error loading categories:', xhr.responseText);
        }
    });

    // عند تغيير الفئة الرئيسية
    $('#category_id').change(function() {
        var categoryId = $(this).val();
        if(categoryId) {
            $('#subcategory_id').prop('disabled', false);

            // جلب التصنيفات الفرعية من الاستجابة الأصلية
            $.ajax({
                url: "{{ route('categoryList') }}",
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    // البحث عن الفئة المحددة
                    const selectedCategory = response.find(cat =>
                        cat.data && cat.data.id == categoryId && !cat.data.empty
                    );

                    $('#subcategory_id').empty();
                    $('#subcategory_id').append('<option value="">@lang('sales::lang.select_subcategory')</option>');

                    if(selectedCategory && selectedCategory.children) {
                        // فلترة العناصر الفرعية التي ليست فارغة ولها بيانات
                        const validSubcategories = selectedCategory.children.filter(
                            child => child.data && child.data.id && !child.data.empty
                        );

                        $.each(validSubcategories, function(index, subcategory) {
                            $('#subcategory_id').append(
                                `<option value="${subcategory.data.id}">
                                    ${subcategory.data.name_ar} - ${subcategory.data.name_en}
                                </option>`
                            );
                        });
                    }
                },
                error: function(xhr) {
                    console.error('Error loading subcategories:', xhr.responseText);
                }
            });
        } else {
            $('#subcategory_id').prop('disabled', true);
            $('#subcategory_id').empty();
            $('#subcategory_id').append('<option value="">@lang('sales::lang.select_subcategory')</option>');
        }
    });

$('#saveProductBtn').click(function(e) {
    e.preventDefault();
        // تعطيل الزر أثناء الحفظ لمنع الضغط المتكرر
        $('#saveProductBtn').prop('disabled', true);

        // جمع بيانات النموذج
        let formData = {
            name_ar: $('#name_ar').val(),
            name_en: $('#name_en').val(),
            category_id: $('#category_id').val(),
            subcategory_id: $('#subcategory_id').val(),
            price: $('#price').val(),
            cost: $('#cost').val(),
            order: $('#order').val(),
            unit1: $('#unit1').val()
        };

        // إرسال البيانات
        $.ajax({
            url: "{{ route('productFastSave') }}",
            type: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                // إظهار رسالة نجاح
                toastr.success('تم حفظ المنتج بنجاح');

                // إغلاق المودال بعد الحفظ
                $('#addProductModal').modal('hide');

                // إعادة تعبئة البيانات إذا لزم الأمر
                // refreshProductList();
            },
            error: function(xhr) {
                // إظهار رسالة خطأ
                let errors = xhr.responseJSON.errors;
                $.each(errors, function(key, value) {
                    toastr.error(value[0]);
                });
            },
            complete: function() {
                // إعادة تمكين زر الحفظ
                $('#saveProductBtn').prop('disabled', false);
            }
        });
    });
});
</script>

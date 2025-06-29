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
                                <input type="text" class="form-control" id="name_ar" name="name_ar" required
                                    maxlength="255">
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
                                <input type="number" step="0.01" class="form-control" id="price" name="price"
                                    min="0">
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
                                <input type="text" class="form-control" id="name_en" name="name_en" required
                                    maxlength="255">
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
                                <input type="number" step="0.01" class="form-control" id="cost" name="cost"
                                    min="0">
                            </div>

                            <!-- Unit -->
                            <div class="mb-3">
                                <label for="unit1" class="form-label">@lang('sales::fields.unit')</label>
                                <input type="text" class="form-control" id="unit1" name="unit1"
                                    maxlength="255">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">@lang('sales::lang.close')</button>
                        <button type="button" class="btn btn-primary" id="saveProductBtn">@lang('sales::lang.save')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



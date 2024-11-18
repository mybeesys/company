import { useEffect } from "react";
import AsyncSelectComponent from "../../comp/AsyncSelectComponent";


const ProductInventoryVendor = ({ translations, currentObject, onBasicChange, dir }) => {

    return (
        <div class="card-body" dir={dir}>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="unit_id" class="col-form-label">{translations.vendor}</label>
                        <AsyncSelectComponent
                            field="vendor"
                            dir={dir}
                            searchUrl="searchVendors"
                            currentObject={currentObject.vendor}
                            onBasicChange={onBasicChange} />
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="unit_id" class="col-form-label">{translations.unit}</label>
                        <AsyncSelectComponent
                            field="vendor_unit"
                            dir={dir}
                            searchUrl="searchUnits"
                            currentObject={currentObject.vendor_unit}
                            onBasicChange={onBasicChange} />
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="primary_vendor_default_quantity" class="col-form-label">{translations.defaultQuantity}</label>
                        <input type="number" min="0" step=".01" class="form-control" id="primary_vendor_default_quantity" value={!!currentObject.primary_vendor_default_quantity ? currentObject.primary_vendor_default_quantity : ''}
                            onChange={(e) => onBasicChange('primary_vendor_default_quantity', e.target.value)}
                             />
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="primary_vendor_default_price" class="col-form-label">{translations.defaultPrice}</label>
                        <input type="number" min="0" step=".01" class="form-control" id="primary_vendor_default_price" value={!!currentObject.primary_vendor_default_price ? currentObject.primary_vendor_default_price : ''}
                            onChange={(e) => onBasicChange('primary_vendor_default_price', e.target.value)}
                             />
                    </div>
                </div>
            </div>
        </div>
    );
}
export default ProductInventoryVendor;
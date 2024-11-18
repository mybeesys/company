import { useEffect, useState } from "react";
import { getName } from "../../lang/Utils";
import AsyncSelectComponent from "../../comp/AsyncSelectComponent";


const ProductInventoryBasicInfo = ({ translations, currentObject, onBasicChange, dir }) => {

    return (
        <div class="card-body" dir={dir}>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="threshold" class="col-form-label">{translations.threshold}</label>
                        <input type="number" min="0" step=".01" class="form-control" id="threshold" value={!!currentObject.threshold ? currentObject.threshold : ''}
                            onChange={(e) => onBasicChange('threshold', e.target.value)}
                            required />
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="unit_id" class="col-form-label">{translations.inventoryUOM}</label>
                        <AsyncSelectComponent
                            field="unit"
                            dir={dir}
                            searchUrl="searchUnits"
                            currentObject={currentObject.unit}
                            onBasicChange={onBasicChange} />
                    </div>
                </div>
            </div>
        </div>
    );
}
export default ProductInventoryBasicInfo;
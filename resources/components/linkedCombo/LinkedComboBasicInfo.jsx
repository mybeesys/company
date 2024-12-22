import { useEffect, useState } from "react";



const LinkedComboBasicInfo = ({ translations, currentObject, onBasicChange, dir }) => {

    return (
        <div class="card-body" dir={dir}>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="name_ar" class="col-form-label">{translations.name_ar}</label>
                        <input type="text" class="form-control form-control-solid custom-height" id="name_ar" value={currentObject.name_ar}
                            onChange={(e) => onBasicChange('name_ar', e.target.value)} required></input>
                    </div>
                    <div class="col-6">
                        <label for="name_en" class="col-form-label">{translations.name_en}</label>
                        <input type="text" class="form-control form-control-solid custom-height" id="name_en" value={currentObject.name_en}
                            onChange={(e) => onBasicChange('name_en', e.target.value)} required></input>
                    </div>
                </div>
            </div>
            <div class="form-group">
          <div class="row">
            <div class="col-12">
              <label for="price" class="col-form-label">{translations.price}</label>
              <input type="number" min="0" step=".01" class="form-control form-control-solid custom-height" id="price" value={!!currentObject.price ? currentObject.price : ''}
                onChange={(e) => onBasicChange('price', e.target.value)}
                required></input>
            </div>
          </div>
        </div>
        <div class="form-group">
          <div class="row">
            <div class="col-12">
              <label for="barcode" class="col-form-label">{translations.barcode}</label>
              <input type="text" class="form-control form-control-solid custom-height" id="barcode" value={!!currentObject.barcode ? currentObject.barcode : ''}
                onChange={(e) => onBasicChange('barcode', e.target.value)}
                ></input>
            </div>
          </div>
        </div>
        <div class="d-flex align-items-center pt-3">
                <label class="fs-6 fw-semibold mb-2 me-3 "
                    style={{width: "150px"}}>{translations.active}</label>
                <div class="form-check">
                    <input type="checkbox" style={{border: "1px solid #9f9f9f"}}
                        class="form-check-input my-2"
                        id="active" checked={!!currentObject.active == 1 ? true : false}
                        onChange={(e) => onBasicChange('active', e.target.checked ?  1 : 0)}/>
                </div>
            </div>
        </div>
    );
}
export default LinkedComboBasicInfo;
import { useEffect, useState } from "react";



const LinkedComboBasicInfo = ({ translations, currentObject, onBasicChange, dir }) => {

    return (
        <div class="card-body" dir={dir}>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="name_ar" class="col-form-label">{translations.name_ar}</label>
                        <input type="text" class="form-control" id="name_ar" value={currentObject.name_ar}
                            onChange={(e) => onBasicChange('name_ar', e.target.value)} required></input>
                    </div>
                    <div class="col-6">
                        <label for="name_en" class="col-form-label">{translations.name_en}</label>
                        <input type="text" class="form-control" id="name_en" value={currentObject.name_en}
                            onChange={(e) => onBasicChange('name_en', e.target.value)} required></input>
                    </div>
                </div>
            </div>
            <div class="form-group">
          <div class="row">
            <div class="col-12">
              <label for="price" class="col-form-label">{translations.price}</label>
              <input type="number" min="0" step=".01" class="form-control" id="price" value={!!currentObject.price ? currentObject.price : ''}
                onChange={(e) => onBasicChange('price', e.target.value)}
                required></input>
            </div>
          </div>
        </div>
        <div class="form-group">
          <div class="row">
            <div class="col-12">
              <label for="barcode" class="col-form-label">{translations.barcode}</label>
              <input type="text" class="form-control" id="barcode" value={!!currentObject.barcode ? currentObject.barcode : ''}
                onChange={(e) => onBasicChange('barcode', e.target.value)}
                ></input>
            </div>
          </div>
        </div>
            <div class="form-group" style={{ paddingtop: '5px' }}>
                <div class="col-12">
                    <label class="col-form-label col-4" >
                        <div class="row">
                            <div class="col-2">
                                <input type="checkbox" class="form-check-input" id="active" checked={!!currentObject.active == 1 ? true : false}
                                    onChange={(e) => onBasicChange('active', e.target.checked ?  1 : 0)}
                                />
                            </div>
                            <div class=" container col-10">{translations.active}</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    );
}
export default LinkedComboBasicInfo;
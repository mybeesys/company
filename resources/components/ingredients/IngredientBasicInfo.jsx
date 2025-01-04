import React, { useEffect, useState } from 'react';
import { InputSwitch } from 'primereact/inputswitch';
const IngredientBasicInfo = ({dir, translations , units , vendors , currentObject , onBasicChange}) => {

  return (
    <div class="card-body" dir={dir}>
        <div class="d-flex  align-items-center pt-3">
            <label class="fs-6 fw-semibold mb-2 me-3 "
                style={{width: "150px"}}>{translations.active}</label>
            <div class="form-check form-switch">
                {/* <input type="checkbox" style={{border: "1px solid #9f9f9f"}}
                    class="form-check-input" role="switch"
                    id="active" checked={!!currentObject.active ? currentObject.active : false}
                    onChange={(e) => handleChange('active', e.target.checked)}/> */}
                <InputSwitch checked={!!currentObject.active ? !!currentObject.active : false} 
                    onChange={(e) => handleChange('active', e.value)} />
            </div>
        </div>
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
                <div class="col-6">
                    <label for="cost" class="col-form-label">{translations.cost}</label>
                    <input type="number" min="0" step=".01" class="form-control form-control-solid custom-height" id="amount" value={currentObject.cost}
                        onChange={(e) => onBasicChange('cost', e.target.value)} required></input>
                </div>
                
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <div class="col-6">
                    <label for="SKU" class="col-form-label">{translations.SKU}</label>
                    <input type="text" class="form-control form-control-solid custom-height" id="name_ar" value={currentObject.SKU}
                        onChange={(e) => onBasicChange('SKU', e.target.value)}></input>
                </div>
                <div class="col-6">
                    <label for="barcode" class="col-form-label">{translations.barcode}</label>
                    <input type="text" class="form-control form-control-solid custom-height" id="barcode" value={currentObject.barcode}
                        onChange={(e) => onBasicChange('barcode', e.target.value)}></input>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <div class="col-12">
                    <label for="name_ar" class="col-form-label">{translations.vendor}</label>
                    <select class="form-control form-control-solid custom-height selectpicker" value={currentObject.vendor_id} 
                        onChange={(e) => onBasicChange('vendor_id', e.target.value)} >
                        {vendors.map((appType) => (
                            <option key={appType.value} value={appType.value}>
                                {appType.name}
                            </option>
                        ))}
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <div class="col-4">
                    <label for="reorder_point" class="col-form-label">{translations.reorder_point}</label>
                    <input type="number" min="0"  class="form-control form-control-solid custom-height" id="reorder_point" value={currentObject.reorder_point}
                        onChange={(e) => onBasicChange('reorder_point', e.target.value)}></input>
                </div>
                <div class="col-4">
                    <label for="reorder_quantity" class="col-form-label">{translations.reorder_quantity}</label>
                    <input type="number" min="0"  class="form-control form-control-solid custom-height" id="reorder_quantity" value={currentObject.reorder_quantity}
                        onChange={(e) => onBasicChange('reorder_quantity', e.target.value)}></input>
                </div>
                <div class="col-4">
                    <label for="yield_percentage" class="col-form-label">{translations.yield_percentage}</label>
                    <input type="number" min="0" step=".01" class="form-control form-control-solid custom-height" id="yield_percentage" value={currentObject.yield_percentage}
                        onChange={(e) => onBasicChange('yield_percentage', e.target.value)}></input>
                </div>
            </div>
        </div>
        
    </div>
);
}

export default IngredientBasicInfo;
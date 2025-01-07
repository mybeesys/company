import React, { useState, useCallback } from 'react';
import { InputSwitch } from 'primereact/inputswitch';
import axios from 'axios';
import Select from "react-select";
import makeAnimated from 'react-select/animated';
import { getRowName } from '../lang/Utils';

const animatedComponents = makeAnimated();
const ModifierBasicInfo = ({ translations, parentHandlechanges, modifier, visible }) => {
  const rootElement = document.getElementById('root');
  let dir = rootElement.getAttribute('dir');
  const [currentObject, setcurrentObject] = useState(modifier);
  
  const handleChange = async (key, value, option) => {
    let r = { ...currentObject };
    r[key] = value;
    setcurrentObject({ ...r });
    parentHandlechanges({ ...r });
  }

  return (
    <>
      <div class="card-body" dir={dir} style={{display: visible ? 'block' : 'none'}}>
        <div class="d-flex  align-items-center pt-3">
            <label class="fs-6 fw-semibold mb-2 me-3 "
                style={{width: "150px"}}>{translations.active}</label>
            <div class="form-check form-switch">
                <InputSwitch checked={!!currentObject.active ? !!currentObject.active : false} 
                  onChange={(e) => handleChange('active', e.value)} />
            </div>
        </div>  
        <div class="form-group">
          <div class="row">
            <div class="col-6">
              <label for="name_ar" class="col-form-label">{translations.name_ar}</label>
              <input type="text" class="form-control form-control-solid custom-height" id="name_ar" value={!!currentObject.name_ar ? currentObject.name_ar : ''}
                onChange={(e) => handleChange('name_ar', e.target.value)} required></input>
            </div>
            <div class="col-6">
              <label for="name_en" class="col-form-label">{translations.name_en}</label>
              <input type="text" class="form-control form-control-solid custom-height" id="name_en" value={!!currentObject.name_en ? currentObject.name_en : ''}
                onChange={(e) => handleChange('name_en', e.target.value)} required></input>
            </div>
          </div>
        </div>
        <div class="form-group">
          <div class="row">
            <div class="col-6">
              <label for="SKU" class="col-form-label" >{translations.SKU}</label>
              <input type="text" class="form-control form-control-solid custom-height" id="SKU" 
                value={!!currentObject.SKU ? currentObject.SKU : ''}
                placeholder="00000"
                pattern="^\d{5}$"
                onChange={(e) => handleChange('SKU', e.target.value)}
                ></input>
            </div>
            <div class="col-6">
              <label for="barcode" class="col-form-label">{translations.barcode}</label>
              <input type="text" class="form-control form-control-solid custom-height" id="barcode" value={!!currentObject.barcode ? currentObject.barcode : ''}
                onChange={(e) => handleChange('barcode', e.target.value)}
                ></input>
            </div>
          </div>
        </div>
        <div class="form-group">
          <div class="row">
            <div class="col-6">
              <label for="SKU" class="col-form-label">{translations.order}</label>
              <input type="number" class="form-control form-control-solid custom-height" id="class" value={!!currentObject.order ? currentObject.order : ''}
                onChange={(e) => handleChange('order', e.target.value)}
                required></input>
            </div>
          </div>
        </div>
        <div class="form-group" style={{ paddingtop: '5px' }}>
        </div>

      </div>
    </>

  );
};

export default ModifierBasicInfo;


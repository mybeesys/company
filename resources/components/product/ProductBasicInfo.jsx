import React, { useState, useCallback } from 'react';
import { InputSwitch } from 'primereact/inputswitch';
import axios from 'axios';
import Select from "react-select";
import makeAnimated from 'react-select/animated';
import { getRowName } from '../lang/Utils';

const animatedComponents = makeAnimated();
const ProductBasicInfo = ({ translations, parentHandlechanges, currentObject, visible }) => {
  const rootElement = document.getElementById('root');
  const listCategoryurl = JSON.parse(rootElement.getAttribute('listCategory-url'));
  const listSubCategoryurl = JSON.parse(rootElement.getAttribute('listSubCategory-url'));
  let imageurl = rootElement.getAttribute('image-url');
  let dir = rootElement.getAttribute('dir');
  const [categoryOptions, setCategoryOptions] = useState([]);
  const [subcategoryOption, setSubCategoryOptions] = useState([]);

  const fetchCategoryOptions = async () => {
    try {
      let subCategories = [];
      let response = await axios.get(listCategoryurl);
      const categories = response.data.map(category => ({
        label: getRowName (category, dir),  // The text shown in the select options
        value: category.id,    // The value of the selected option
      }));
      if (response.data.length > 0) {
        if (!!!currentObject.category_id){
          currentObject['category_id'] = response.data[0].id;
          currentObject['category'] = categories[0];
        }
        else{
          currentObject['category'] = categories.find(x=>x.value == currentObject.category_id);
        }
        subCategories = await fetchSubCategoryOptions(currentObject.category_id);
        if (subCategories.length > 0){
          currentObject['subcategory_id'] = subCategories[0].value;
          currentObject['subcategory'] = subCategories[0];
        }
        else{
          currentObject['subcategory'] = null;
        }
      }
      setCategoryOptions(categories);
      setSubCategoryOptions(subCategories);
      parentHandlechanges({ ...currentObject });
    } catch (error) {
      console.error("Error fetching options:", error);
    }
  };

  const fetchSubCategoryOptions = async (categoryId) => {
    try {
      const response = await axios.get(listSubCategoryurl + "/" + categoryId);
      const subCategories = response.data.map(subCategory => ({
        label: getRowName (subCategory, dir),  // The text shown in the select options
        value: subCategory.id,    // The value of the selected option
      }));
      return subCategories;
    } catch (error) {
      console.error("Error fetching options:", error);
    }
  };


  const handleChange = async (key, value, option) => {
    let r = { ...currentObject };
    r[key] = value;
    if (key == "category_id") {
      r['category'] = option;
      const subCategories = await fetchSubCategoryOptions(value);
      r['subcategory_id'] = subCategories.length > 0 ? subCategories[0].value : null;
      r['subcategory'] = subCategories.length > 0 ? subCategories[0] : null;
      setSubCategoryOptions(subCategories);
    }
    parentHandlechanges({ ...r });
  }
  // Clean up object URLs to avoid memory leaks
  React.useEffect(() => {
    fetchCategoryOptions(); // Trigger the fetch
  }, []);

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
        <div class="d-flex  align-items-center pt-3">
            <label class="fs-6 fw-semibold mb-2 me-3 "
                style={{width: "150px"}}>{translations.forSell}</label>
            <div class="form-check form-switch">
                <InputSwitch checked={!!currentObject.for_sell ? !!currentObject.for_sell : false} 
                  onChange={(e) => handleChange('for_sell', e.value)} />
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
              <label for="name_ar" class="col-form-label">{translations.category}</label>
              <Select
                    id="category_id"
                    isMulti={false}
                    options={categoryOptions}
                    closeMenuOnSelect={true}
                    components={animatedComponents}
                    value={currentObject.category}
                    onChange={val => handleChange('category_id', val.value, val)}
                    menuPortalTarget={document.body} 
                    styles={{ menuPortal: base => ({ ...base, zIndex: 100000 }) }}
                />
            </div>
            <div class="col-6">
              <label for="name_ar" class="col-form-label">{translations.subcategory}</label>
              <Select
                    id="subcategory_id"
                    isMulti={false}
                    options={subcategoryOption}
                    closeMenuOnSelect={true}
                    components={animatedComponents}
                    value={currentObject.subcategory}
                    onChange={val => handleChange('subcategory_id', val.value, val)}
                    menuPortalTarget={document.body} 
                    styles={{ menuPortal: base => ({ ...base, zIndex: 100000 }) }}
                />
            </div>
          </div>
        </div>
        <div class="form-group">
          <div class="row">
            <div class="col-6">
              <label for="name_ar" class="col-form-label">{translations.deacription_ar}</label>
              <textarea type="text" class="form-control form-control-solid" id="description_ar" value={!!currentObject.description_ar ? currentObject.description_ar : ''}
                onChange={(e) => handleChange('description_ar', e.target.value)}
              ></textarea>
            </div>
            <div class="col-6">
              <label for="name_en" class="col-form-label">{translations.deacription_en}</label>
              <textarea type="text" class="form-control form-control-solid" id="description_en" value={!!currentObject.description_en ? currentObject.description_en : ''}
                onChange={(e) => handleChange('description_en', e.target.value)}
              ></textarea>
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
                ></input>
            </div>
            <div class="col-6">
              <label for="barcode" class="col-form-label">{translations.commissions}</label>
              <input type="number" min="0" class="form-control form-control-solid custom-height" id="commissions" value={!!currentObject.commissions ? product.commissions : ''}
                onChange={(e) => handleChange('commissions', e.target.value)}
              ></input>
            </div>
          </div>
        </div>
        <div class="form-group" style={{ paddingtop: '5px' }}>
        </div>

      </div>
    </>

  );
};

export default ProductBasicInfo;


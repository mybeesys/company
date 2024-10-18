import React, { useState, useCallback } from 'react';
import ReactDOM from 'react-dom/client';
import axios from 'axios';



const ProductBasicInfo = ({ translations, parentHandlechanges, product, visible }) => {
  const rootElement = document.getElementById('product-root');
  const listCategoryurl = JSON.parse(rootElement.getAttribute('listCategory-url'));
  const listSubCategoryurl = JSON.parse(rootElement.getAttribute('listSubCategory-url'));
  let imageurl = rootElement.getAttribute('image-url');
  let dir = rootElement.getAttribute('dir');
  const [currentObject, setcurrentObject] = useState(product);
  const [categoryOptions, setCategoryOptions] = useState([]);
  const [subcategoryOption, setSubCategoryOptions] = useState([]);



  const fetchCategoryOptions = async () => {
    try {
      let subCategories = [];
      const response = await axios.get(listCategoryurl);
      if (response.data.length > 0) {
        if (!!!currentObject.category_id)
          currentObject['category_id'] = response.data[0].id;
        subCategories = await fetchSubCategoryOptions(currentObject.category_id);
        if (subCategories.length > 0)
          currentObject['subcategory_id'] = subCategories[0].id;
      }
      setCategoryOptions(response.data);
      setSubCategoryOptions(subCategories);
      setcurrentObject({ ...currentObject });
      parentHandlechanges({ ...currentObject });
    } catch (error) {
      console.error("Error fetching options:", error);
    }
  };

  const fetchSubCategoryOptions = async (categoryId) => {
    try {
      const response = await axios.get(listSubCategoryurl + "/" + categoryId);
      return response.data;
    } catch (error) {
      console.error("Error fetching options:", error);
    }
  };


  const handleChange = async (key, value) => {
    let r = { ...currentObject };
    r[key] = value;

    if (key == "category_id") {
      const subCategories = await fetchSubCategoryOptions(value);
      r['subcategory_id'] = subCategories.length > 0 ? subCategories[0].id : null;

      setSubCategoryOptions(subCategories);
    }
    setcurrentObject({ ...r });
    parentHandlechanges({ ...r });
    console.log(r);
  }
  // Clean up object URLs to avoid memory leaks
  React.useEffect(() => {
    fetchCategoryOptions(); // Trigger the fetch

  }, []);

  return (
    <>
      <div class="card-body" dir={dir} style={{display: visible ? 'block' : 'none'}}>
        <div class="form-group">
          <div class="row">
            <div class="col-6">
              <label for="name_ar" class="col-form-label">{translations.name_ar}</label>
              <input type="text" class="form-control" id="name_ar" value={!!currentObject.name_ar ? currentObject.name_ar : ''}
                onChange={(e) => handleChange('name_ar', e.target.value)} required></input>
            </div>
            <div class="col-6">
              <label for="name_en" class="col-form-label">{translations.name_en}</label>
              <input type="text" class="form-control" id="name_en" value={!!currentObject.name_en ? currentObject.name_en : ''}
                onChange={(e) => handleChange('name_en', e.target.value)} required></input>
            </div>
          </div>
        </div>
        <div class="form-group">
          <div class="row">
            <div class="col-6">
              <label for="name_ar" class="col-form-label">{translations.category}</label>
              <select class="form-control selectpicker" value={!!currentObject.category_id ? currentObject.category_id : ''} onChange={(e) => handleChange('category_id', e.target.value)} >
                {categoryOptions.map((option) => (
                  <option key={option.id} value={option.id}>
                    {dir == "rtl" ? option.name_ar : option.name_en}
                  </option>
                ))}
              </select>
            </div>
            <div class="col-6">
              <label for="name_ar" class="col-form-label">{translations.subcategory}</label>
              <select class="form-control selectpicker" value={!!currentObject.subcategory_id ? currentObject.subcategory_id : ''} onChange={(e) => handleChange('subcategory_id', e.target.value)} >
                {subcategoryOption.map((option) => (
                  <option key={option.id} value={option.id}>
                    {dir == "rtl" ? option.name_ar : option.name_en}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </div>
        <div class="form-group">
          <div class="row">
            <div class="col-6">
              <label for="name_ar" class="col-form-label">{translations.deacription_ar}</label>
              <textarea type="text" class="form-control" id="description_ar" value={!!currentObject.description_ar ? currentObject.description_ar : ''}
                onChange={(e) => handleChange('description_ar', e.target.value)}
              ></textarea>
            </div>
            <div class="col-6">
              <label for="name_en" class="col-form-label">{translations.deacription_en}</label>
              <textarea type="text" class="form-control" id="description_en" value={!!currentObject.description_en ? currentObject.description_en : ''}
                onChange={(e) => handleChange('description_en', e.target.value)}
              ></textarea>
            </div>
          </div>
        </div>
        <div class="form-group">
          <div class="row">
            <div class="col-6">
              <label for="price" class="col-form-label">{translations.price}</label>
              <input type="number" min="0" step=".01" class="form-control" id="price" value={!!currentObject.price ? currentObject.price : ''}
                onChange={(e) => handleChange('price', e.target.value)}
                required></input>
            </div>
            <div class="col-6">
              <label for="cost" class="col-form-label">{translations.cost}</label>
              <input type="number" min="0" step=".01" class="form-control" id="cost" value={!!currentObject.cost ? currentObject.cost : ''}
                onChange={(e) => handleChange('cost', e.target.value)}
                required></input>
            </div>
          </div>
        </div>
        <div class="form-group">
          <div class="row">
            <div class="col-6">
              <label for="SKU" class="col-form-label">{translations.SKU}</label>
              <input type="text" class="form-control" id="SKU" value={!!currentObject.SKU ? currentObject.SKU : ''}
                onChange={(e) => handleChange('SKU', e.target.value)}
                required></input>
            </div>
            <div class="col-6">
              <label for="barcode" class="col-form-label">{translations.barcode}</label>
              <input type="text" class="form-control" id="barcode" value={!!currentObject.barcode ? currentObject.barcode : ''}
                onChange={(e) => handleChange('barcode', e.target.value)}
                required></input>
            </div>
          </div>
        </div>
        <div class="form-group">
          <div class="row">
            <div class="col-6">
              <label for="SKU" class="col-form-label">{translations.order}</label>
              <input type="number" class="form-control" id="class" value={!!currentObject.order ? currentObject.order : ''}
                onChange={(e) => handleChange('order', e.target.value)}
                required></input>
            </div>
            <div class="col-6">
              <label for="barcode" class="col-form-label">{translations.commissions}</label>
              <input type="number" min="0" class="form-control" id="commissions" value={!!currentObject.commissions ? product.commissions : ''}
                onChange={(e) => handleChange('commissions', e.target.value)}
              ></input>
            </div>
          </div>
        </div>
        <div class="form-group" style={{ paddingtop: '5px' }}>
          <div class="col-12">
            <label class="col-form-label col-4" >
              <div class="row">
                <div class="col-2">
                  <input type="checkbox" class="form-check-input" id="active" checked={!!currentObject.active ? currentObject.active : false}
                    onChange={(e) => handleChange('active', e.target.checked)}
                  />
                </div>
                <div class=" container col-10">{translations.active}</div>
              </div>
            </label>
          </div>
        </div>
        <div class="form-group" style={{ paddingtop: '5px' }}>
          <div class="col-12">
            <label class="col-form-label col-4">
              <div class="row">
                <div class="col-2">
                  <input type="checkbox" class="form-check-input" id="sold_by_weight" checked={currentObject.sold_by_weight}
                    onChange={(e) => handleChange('sold_by_weight', e.target.checked)}
                  />
                </div>
                <div class=" container col-10">{translations.SoldByWeight}</div>
              </div>
            </label>
          </div>
        </div>
        <div class="form-group" style={{ paddingtop: '5px' }}>
          <div class="col-12">
            <label class="col-form-label col-4">
              <div class="row">
                <div class="col-2">
                  <input type="checkbox" class="form-check-input" id="track_serial_number" checked={currentObject.track_serial_number}
                    onChange={(e) => handleChange('track_serial_number', e.target.checked)}
                  />
                </div>
                <div class="col-10">{translations.TrackSerialNumber}</div>
              </div>
            </label>
          </div>
        </div>
        <div class="form-group" style={{ paddingtop: '5px' }}>
        </div>

      </div>
    </>

  );
};

export default ProductBasicInfo;


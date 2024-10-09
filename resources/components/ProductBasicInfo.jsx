import React , { useState, useCallback  } from 'react';
import ReactDOM from 'react-dom/client';
import { useDropzone } from 'react-dropzone';
import axios from 'axios';
import { SketchPicker, BlockPicker } from "react-color";


const ProductBasicInfo = ({translations}) => {
    const rootElement = document.getElementById('product-root');
    const producturl = JSON.parse(rootElement.getAttribute('product-url'));
    const listCategoryurl = JSON.parse(rootElement.getAttribute('listCategory-url'));
    const listSubCategoryurl = JSON.parse(rootElement.getAttribute('listSubCategory-url'));
    let product = JSON.parse(rootElement.getAttribute('product'));
    let  imageurl = rootElement.getAttribute('image-url');
    let dir = rootElement.getAttribute('dir');
    const [currentObject, setcurrentObject] = useState(product); 
    const [categoryOptions, setCategoryOptions] = useState([]); 
    const [subcategoryOption, setSubCategoryOptions] = useState([]); 
    const [files, setFiles] = useState([]);
    const [imageSrc, setimageSrc] = useState(imageurl);
      //creating state to store our color and also set color using onChange event for sketch picker
  const [sketchPickerColor, setSketchPickerColor] = useState({
    r: "241",
    g: "112",
    b: "19",
    a: "1",
  });
  // destructuring rgba from state
  const { r, g, b, a } = sketchPickerColor;

  //creating state to store our color and also set color using onChange event for block picker
  const [blockPickerColor, setBlockPickerColor] = useState("#37d67a");



    const onDrop = useCallback((acceptedFiles) => {
        const mappedFiles = acceptedFiles.map((file) => 
          Object.assign(file, {
            preview: URL.createObjectURL(file)
          })
        );
        setFiles((prevFiles) => [...prevFiles, ...mappedFiles]);
        setimageSrc(mappedFiles[0].preview);
      }, []);
    
      const { getRootProps, getInputProps } = useDropzone({
        onDrop,
        accept: 'image/*',
        multiple: false
      });

      const deleteImage =() =>
      {
        setFiles([]);
        setimageSrc(imageurl);
      }
       // Handle color change from the picker
  const handleColorChange = (newColor) => {
    setColor(newColor);
  };

  // Handle color change from the text input
  const handleInputChange = (e) => {
    const newColor = e.target.value;
    if (/^#[0-9A-F]{6}$/i.test(newColor)) { // Simple hex code validation
      setColor(newColor);
    }
  };

  const fetchCategoryOptions = async () => {
    try {
      const response = await axios.get(listCategoryurl)
      const data = await response.data;
      setCategoryOptions(data); // Update options state
    } catch (error) {
      console.error("Error fetching options:", error);
    }
  };

  const fetchSubCategoryOptions = async (categoryId) => {
    try {
      const response = await axios.get(listSubCategoryurl+"/"+categoryId);
      const data = await response.data;
      setSubCategoryOptions(data); // Update options state
    } catch (error) {
      console.error("Error fetching options:", error);
    }
  };


const handleChange = (key , value) =>
{
    let r = {...currentObject};
    r[key] = value;
    if(key == "category_id")
    {
       fetchSubCategoryOptions(value);   
       r["subcategory_id"] = subcategoryOption[0].id;
    }
    setcurrentObject({...r});
}
  // Clean up object URLs to avoid memory leaks
  React.useEffect(() => {
    fetchCategoryOptions(); // Trigger the fetch

    if(!!currentObject.category_id)
      fetchSubCategoryOptions(currentObject.category_id);   

    return () => files.forEach(file => URL.revokeObjectURL(file.preview));
  }, [files]);
      
    return (
      <>
            <div class="card-body" dir={dir}>
                <div class="form-group">
                    <div class="row">
                        <div class="col-6">
                            <label for="name_ar" class="col-form-label">{translations.name_ar}</label>
                            <input type="text" class="form-control" id="name_ar" value={!!currentObject.name_ar ? currentObject.name_ar : ''} 
                               onChange={(e) => handleChange('name_ar', e.target.value)}  required></input>
                        </div>
                        <div class="col-6">
                            <label for="name_en" class="col-form-label">{translations.name_en}</label>
                            <input type="text" class="form-control" id="name_en" value={!!currentObject.name_en ? currentObject.name_en : ''} 
                               onChange={(e) => handleChange('name_en', e.target.value)}  required></input>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <div class="col-6">
                          <label for="name_ar" class="col-form-label">{translations.category}</label>
                          <select class="form-control selectpicker" value={!!currentObject.category_id ? currentObject.category_id : ''}  onChange={(e) => handleChange('category_id', e.target.value)} >
                                    {categoryOptions.map((option) => (
                                        <option key={option.id} value={option.id}>
                                           {dir=="rtl"? option.name_ar : option.name_en }
                                        </option>
                                        ))}
                          </select>
                        </div>
                        <div class="col-6">
                          <label for="name_ar" class="col-form-label">{translations.subcategory}</label>
                          <select class="form-control selectpicker" value={!!currentObject.subcategory_id ? currentObject.subcategory_id : ''} onChange={(e) => handleChange('subcategory_id', e.target.value)} >
                               {subcategoryOption.map((option) => (
                                        <option key={option.id} value={option.id}>
                                            {dir=="rtl"? option.name_ar : option.name_en }
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
                            onChange={(e) => handleChange('deacription_ar', e.target.value)}
                                    required></textarea>
                        </div>
                        <div class="col-6">
                            <label for="name_en" class="col-form-label">{translations.deacription_en}</label>
                            <textarea type="text" class="form-control" id="description_en" value={!!currentObject.description_en ? currentObject.description_en : ''} 
                                    onChange={(e) => handleChange('description_en', e.target.value)}
                                    required></textarea>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                  <div class="row">
                    <div class="col-6">
                    <label for="price" class="col-form-label">{translations.price}</label>
                    <input type="number"  min="0" class="form-control" id="price" value={!!currentObject.price ? currentObject.price : ''} 
                            onChange={(e) => handleChange('price', e.target.value)}
                            required></input>
                   </div>
                   <div class="col-6">
                    <label for="cost" class="col-form-label">{translations.cost}</label>
                    <input type="number"  min="0" class="form-control" id="cost" value={!!currentObject.cost ? currentObject.cost : ''} 
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
                    <label for="SKU" class="col-form-label">{translations.class}</label>
                    <input type="text" class="form-control" id="class" value={!!currentObject.class ? currentObject.class : ''} 
                            onChange={(e) => handleChange('class', e.target.value)}
                            required></input>
                   </div>
                   <div class="col-6">
                    <label for="barcode" class="col-form-label">{translations.commissions}</label>
                    <input  type="number"  min="0" class="form-control" id="commissions" value={!!currentObject.commissions ? product.commissions : ''} 
                          onChange={(e) => handleChange('commissions', e.target.value)}
                          required></input>
                   </div>
                </div>
                </div>
                <div class="form-group" style={{paddingtop: '5px'}}>
                <div class="col-12">
                      <label class="col-form-label col-4" >
                      <div class="row">
                        <div class="col-2">
                            <input type="checkbox" class="form-check-input" id="active" checked={!!currentObject.active ? currentObject.active : false }
                            onChange={(e) => handleChange('active', e.target.checked)}
                          />
                        </div>
                        <div class=" container col-10">{translations.active}</div>
                    </div>
                     </label>
                 </div>
                 </div>
                 <div class="form-group" style={{paddingtop: '5px'}}>
                <div class="col-12">
                      <label class="col-form-label col-4">
                      <div class="row">
                        <div class="col-2">
                            <input type="checkbox" class="form-check-input" id="sold_by_weight" checked={currentObject.sold_by_weight }
                            onChange={(e) => handleChange('sold_by_weight', e.target.checked)}
                          />
                        </div>
                        <div class=" container col-10">{translations.SoldByWeight}</div>
                    </div>
                     </label>
                 </div>
                 </div>
                 <div class="form-group" style={{paddingtop: '5px'}}>
                 <div class="col-12">
                      <label class="col-form-label col-4">
                      <div class="row">
                        <div class="col-2">
                            <input type="checkbox" class="form-check-input" id="track_serial_number" checked={ currentObject.track_serial_number}
                            onChange={(e) => handleChange('track_serial_number', e.target.checked)}
                         />
                        </div>
                        <div class="col-10">{translations.TrackSerialNumber}</div>
                    </div>
                     </label>
                 </div>
                 </div>
                 <div class="form-group" style={{paddingtop: '5px'}}>
                 <div class="row">
                 <div class="col-6">
                    <div class="image-input image-input-empty image-input-outline mb-3 mx-auto text-center" data-kt-image-input="true"
                    style={{maxWidth: '180px' , position: 'relative'}}>
                    <div {...getRootProps({ className: 'image-input-wrapper w-150px h-150px mx-auto' })}>
                        <input {...getInputProps()}  />
                        <img src= {imageSrc} style={{maxWidth: '180px' , position: 'relative'}} />
                    </div>
                    <label className="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" onClick={deleteImage}
                        style={{position: 'absolute' , top: '10px' , right: '10px'}} data-kt-image-input-action="change" data-bs-toggle="tooltip">
                        <i class="ki-outline ki-cross fs-7"></i>
                    </label>
                    </div>
                </div>
                <div class="col-6">
                    <div>
                    <div className="blockpicker">
                      <div
                        style={{
                          backgroundColor: `${blockPickerColor}`,
                          width: 100,
                          height: 50,
                          border: "2px solid white",
                        }}
                      ></div>
                      {/* Block Picker from react-color and handling color on onChange event */}
                      <BlockPicker
                        color={blockPickerColor}
                        onChange={(color) => {
                          setBlockPickerColor(color.hex);
                        }}
                      />
                    </div>
                    </div>
                    </div>
              </div>
            </div>
        </div>      
     </>

    );
  };
  
  export default ProductBasicInfo;


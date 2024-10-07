import React , { useState, useCallback  } from 'react';
import ReactDOM from 'react-dom/client';
import { useDropzone } from 'react-dropzone';
import axios from 'axios';


const ProductComponent = () => {
    const rootElement = document.getElementById('product-root');
    const producturl = JSON.parse(rootElement.getAttribute('product-url'));
    let product = JSON.parse(rootElement.getAttribute('product'));
    let  imageurl = rootElement.getAttribute('image-url');
    let  localizationurl = JSON.parse(rootElement.getAttribute('localization-url'));
    let dir = rootElement.getAttribute('dir');
    const [files, setFiles] = useState([]);
    const [imageSrc, setimageSrc] = useState(imageurl);
    const [translations, setTranslations] = useState({});


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

  // Clean up object URLs to avoid memory leaks
  React.useEffect(() => {
    axios.get(localizationurl)
      .then(response => {
        setTranslations(response.data);
      })
      .catch(error => {
        console.error('Error fetching translations', error);
      });

    return () => files.forEach(file => URL.revokeObjectURL(file.preview));
  }, [files]);
      
    return (
      <div>
         <div class="row">
            <div class="col-3">
            <div class="card mb-5 mb-xl-8">
            </div>
            </div>
            <div class="col-9">
            <div className="card mb-5 mb-xl-8">
             <div class="card-body" dir={dir}>
                <div class="form-group">
                    <div class="row">
                        <div class="col-6">
                            <label for="name_ar" class="col-form-label">{translations.name_ar}</label>
                            <input type="text" class="form-control" id="name_ar" value={!!product.name_ar ? product.name_ar : ''} 
                                    required></input>
                        </div>
                        <div class="col-6">
                            <label for="name_en" class="col-form-label">{translations.name_en}</label>
                            <input type="text" class="form-control" id="name_en" value={!!product.name_en ? product.name_en : ''} 
                                    required></input>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row">
                        <div class="col-6">
                            <label for="name_ar" class="col-form-label">{translations.deacription_ar}</label>
                            <textarea type="text" class="form-control" id="description_ar" value={!!product.description_ar ? product.description_ar : ''} 
                                    required></textarea>
                        </div>
                        <div class="col-6">
                            <label for="name_en" class="col-form-label">{translations.deacription_en}</label>
                            <textarea type="text" class="form-control" id="description_en" value={!!product.description_en ? product.description_en : ''} 
                                    required></textarea>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                  <div class="row">
                    <div class="col-6">
                    <label for="price" class="col-form-label">{translations.price}</label>
                    <input type="number"  min="0" class="form-control" id="price" value={!!product.price ? product.price : ''} 
                             required></input>
                   </div>
                   <div class="col-6">
                    <label for="cost" class="col-form-label">{translations.cost}</label>
                    <input type="number"  min="0" class="form-control" id="cost" value={!!product.cost ? product.cost : ''} 
                              required></input>
                   </div>
                </div>
                </div>
                <div class="form-group">
                  <div class="row">
                    <div class="col-6">
                    <label for="SKU" class="col-form-label">{translations.SKU}</label>
                    <input type="text" class="form-control" id="SKU" value={!!product.SKU ? product.SKU : ''} 
                              required></input>
                   </div>
                   <div class="col-6">
                    <label for="barcode" class="col-form-label">{translations.barcode}</label>
                    <input type="text" class="form-control" id="barcode" value={!!product.barcode ? product.barcode : ''} 
                           required></input>
                   </div>
                </div>
                </div>
                <div class="form-group">
                  <div class="row">
                    <div class="col-6">
                    <label for="SKU" class="col-form-label">{translations.class}</label>
                    <input type="text" class="form-control" id="class" value={!!product.class ? product.class : ''} 
                              required></input>
                   </div>
                   <div class="col-6">
                    <label for="barcode" class="col-form-label">{translations.commissions}</label>
                    <input  type="number"  min="0" class="form-control" id="commissions" value={!!product.commissions ? product.commissions : ''} 
                           required></input>
                   </div>
                </div>
                </div>
                <div class="form-group" style={{paddingtop: '5px'}}>
                <div class="col-12">
                      <label class="col-form-label col-4" >
                      <div class="row">
                        <div class="col-2">
                            <input type="checkbox" class="form-check-input" id="active" checked={(product.active  && product.active== 1) ? true : false }
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
                            <input type="checkbox" class="form-check-input" id="sold_by_weight" checked={(product.sold_by_weight  && product.sold_by_weight== 1) ? true : false }
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
                            <input type="checkbox" class="form-check-input" id="track_serial_number" checked={(product.track_serial_number  && product.track_serial_number== 1) ? true : false }
                          />
                        </div>
                        <div class="col-10">{translations.TrackSerialNumber}</div>
                    </div>
                     </label>
                 </div>
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
             </div>
            </div>
         </div>
         </div>
         </div>
    );
  };
  
  export default ProductComponent;
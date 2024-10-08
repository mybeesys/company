import React , { useState, useCallback  } from 'react';
import ReactDOM from 'react-dom/client';
import { useDropzone } from 'react-dropzone';
import ProductBasicInfo from "./ProductBasicInfo";
import axios from 'axios';


const ProductComponent = () => {
    const rootElement = document.getElementById('product-root');
    let  localizationurl = JSON.parse(rootElement.getAttribute('localization-url'));
    let dir = rootElement.getAttribute('dir');
    const [translations, setTranslations] = useState({});
    const [basicInfo, setBasicInfo] = useState(true);
    const [advancedInfo, setAdvancedInfo] = useState(false);
    const [printInfo, setPrintInfo] = useState(false);
    const [modifiers, setModifiers] = useState(false);
    const [recipe, setRecipe] = useState(false);
    const [inventory, setInventory] = useState(false);

  // Clean up object URLs to avoid memory leaks
  React.useEffect(() => {
    axios.get(localizationurl)
      .then(response => {
        setTranslations(response.data);
      })
      .catch(error => {
        console.error('Error fetching translations', error);
      });
  }, []);
      

  const handleChange =(key , value) =>
  {
    console.log(basicInfo);
     setBasicInfo(!basicInfo)
  }

    return (
      <div>
         <div class="row">
            <div class="col-3">
            <div class="card mb-5 mb-xl-8" style={{minHeight: "100vh" , display : "flex" , flexDirection : "column" , padding:"12px"}}>
              <div className="row product-side-menu">
                <div class="col-12">
                <label class="col-form-label">
                      <div class="row">
                        <div class="col-2">
                            <input type="checkbox" class="form-check-input"  checked={basicInfo}
                                onChange={(e)=> handleChange('basicInfo', e.target.checked)} />
                        </div>
                        <div class="col-10">{translations.basicInfo}</div>
                       </div>
                 </label>
                </div>
              </div>
              <div className="row product-side-menu">
                <div class="col-12">
                <label class="col-form-label">
                      <div class="row">
                        <div class="col-2">
                            <input type="checkbox" class="form-check-input"  checked={advancedInfo}
                                onChange={()=>function(){setAdvancedInfo(!advancedInfo)}} />
                        </div>
                        <div class="col-10">{translations.advancedSettings}</div>
                       </div>
                 </label>
                </div>
              </div>
              <div className="row product-side-menu">
                <div class="col-12">
                <label class="col-form-label">
                      <div class="row">
                        <div class="col-2">
                            <input type="checkbox" class="form-check-input"  checked={printInfo}
                                onChange={()=>function(){setPrintInfo(!printInfo)}} />
                        </div>
                        <div class="col-10">{translations.displayprintOptions}</div>
                       </div>
                 </label>
                </div>
              </div>
              <div className="row product-side-menu">
                <div class="col-12">
                <label class="col-form-label">
                      <div class="row">
                        <div class="col-3">
                            <input type="checkbox" class="form-check-input"  checked={inventory}
                                onChange={()=>function(){setInventory(!inventory)}} />
                        </div>
                        <div class="col-9">{translations.inventory}</div>
                       </div>
                 </label>
                </div>
              </div>
              <div className="row product-side-menu">
                <div class="col-12">
                <label class="col-form-label">
                      <div class="row">
                        <div class="col-3">
                            <input type="checkbox" class="form-check-input"  checked={recipe}
                                onChange={()=>function(){setRecipe(!recipe)}} />
                        </div>
                        <div class="col-9">{translations.recipe}</div>
                       </div>
                 </label>
                </div>
              </div>
              <div className="row product-side-menu">
                <div class="col-12">
                <label class="col-form-label">
                      <div class="row">
                        <div class="col-3">
                            <input type="checkbox" class="form-check-input"  checked={modifiers}
                                onChange={()=>function(){setModifiers(!modifiers)}} />
                        </div>
                        <div class="col-9">{translations.modifiers}</div>
                       </div>
                 </label>
                </div>
              </div>
            </div>
            </div>
            <div class="col-9">
            <div className="card mb-5 mb-xl-8">
              {
                basicInfo?
                   <ProductBasicInfo translations={translations} ></ProductBasicInfo>
                 :<></>
              }
            </div>
         </div>
         </div>
         </div>
    );
  };
  
  export default ProductComponent;
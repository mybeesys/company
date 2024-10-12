import React , { useState, useCallback  } from 'react';
import ProductBasicInfo from "./ProductBasicInfo";
import axios from 'axios';
import { Button } from 'primereact/button';


const ProductComponent = () => {
    const rootElement = document.getElementById('product-root');
    const producturl = JSON.parse(rootElement.getAttribute('product-url'));
    const categoryurl = JSON.parse(rootElement.getAttribute('category-url'));
    let  localizationurl = JSON.parse(rootElement.getAttribute('localization-url'));
    let dir = rootElement.getAttribute('dir');
    const [translations, setTranslations] = useState({});
    const [basicInfo, setBasicInfo] = useState(true);
    const [advancedInfo, setAdvancedInfo] = useState(false);
    const [printInfo, setPrintInfo] = useState(false);
    const [modifiers, setModifiers] = useState(false);
    const [recipe, setRecipe] = useState(false);
    const [inventory, setInventory] = useState(false);
    let product = JSON.parse(rootElement.getAttribute('product'));
    const [currentObject, setcurrentObject] = useState(product); 


  const parentHandlechanges=(childproduct) =>
  {
    setcurrentObject({...childproduct});
  }

  const clicksubmit=() =>
  {
    let btnSubmit = document.getElementById("btnSubmit");
    btnSubmit.click();
  }

  const saveChanges = async() =>
  {
    try{
    let r = {...currentObject};
    r["active"]? r["active"] = 1 : r["active"] = 0;
    r["track_serial_number"]? r["track_serial_number"] = 1 : r["track_serial_number"] = 0;
    r["sold_by_weight"]? r["sold_by_weight"] = 1 : r["sold_by_weight"] = 0;
    const response = await axios.post(producturl, r, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    console.log(response.data.message);
    console.log(categoryurl);
    if(response.data.message == "Done")
      {
        window.location.href = categoryurl;
      }
    }catch (error) {
      console.error('There was an error adding the product!', error);
  }
  }

  const cancel=() =>
  {
    window.location.href = categoryurl;
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
  }, []);
      

  const handleChange =(key , value) =>
  {
    console.log(basicInfo);
     setBasicInfo(!basicInfo)
  }

  return (
      <div>
        <div class="row" style={{padding:"5px"}}>
        <div class="col-9"></div>
        <div class="col-2">
            <Button type="submit" variant="primary" className="btn btn-primary"
            onClick={clicksubmit}
            >{translations.savechanges}</Button>
          </div>
        <div class="col-1">
           <Button variant="secondary" onClick={cancel} className="btn btn-flex">{translations.cancel}</Button>
          </div>
        </div>
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
                   <ProductBasicInfo translations={translations} parentHandlechanges={parentHandlechanges} product={currentObject} saveChanges={saveChanges}></ProductBasicInfo>
                 :<></>
              }
            </div>
         </div>
         </div>
         </div>
    );
  };
  
  export default ProductComponent;
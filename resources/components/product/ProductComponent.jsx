import React , { useState, useCallback  } from 'react';
import ProductBasicInfo from "./ProductBasicInfo";
import axios from 'axios';
import { Button } from 'primereact/button';

const defaultMenu= [
  { key : 'basicInfo' , visible : true },
  { key : 'advancedInfo' , visible : false },
  { key : 'printInfo' , visible : false },
  { key : 'modifiers' , visible : false },
  { key : 'recipe' , visible : false },
  { key : 'inventory' , visible : false }
];
const ProductComponent = () => {
    const rootElement = document.getElementById('product-root');
    const producturl = JSON.parse(rootElement.getAttribute('product-url'));
    const categoryurl = JSON.parse(rootElement.getAttribute('category-url'));
    let  localizationurl = JSON.parse(rootElement.getAttribute('localization-url'));
    let dir = rootElement.getAttribute('dir');
    const [translations, setTranslations] = useState({});
    const [menu, setMenu] = useState(defaultMenu);
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
      

  const handleChange =(index , value) =>
  {
    let currentMenu = [...menu];
    currentMenu[index].visible = value;
    setMenu([...currentMenu]);
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
            {menu.map((m, index) => (
              <div className="row product-side-menu">
                <div class="col-12">
                  <label class="col-form-label col-12">
                    <div class="row">
                      <div class="col-2">
                        <input type="checkbox" class="form-check-input" checked={m.visible}
                          onChange={(e) => handleChange(index, e.target.checked)} />
                      </div>
                      <div class="col-10">{translations[m.key]}</div>
                    </div>
                  </label>
                </div>
              </div>
            ))}
            </div>
            </div>
            <div class="col-9">
            <div className="card mb-5 mb-xl-8">
              {
                menu[0].visible?
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
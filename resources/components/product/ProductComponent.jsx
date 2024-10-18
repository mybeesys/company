import React, { useState, useCallback } from 'react';
import ProductBasicInfo from "./ProductBasicInfo";
import ProductDisplay from "./ProductDisplay";
import ProductAttributes from "./ProductAttributes";
import ProductModifier from './ProductModifier';
import axios from 'axios';
import SweetAlert2 from 'react-sweetalert2';
import { Button } from 'primereact/button';


const ProductComponent = () => {
  const rootElement = document.getElementById('product-root');
  const producturl = JSON.parse(rootElement.getAttribute('product-url'));
  const categoryurl = JSON.parse(rootElement.getAttribute('category-url'));
  let localizationurl = JSON.parse(rootElement.getAttribute('localization-url'));
  const modifierClassUrl = JSON.parse(rootElement.getAttribute('listModifier-url'));
  let getProductMatrix = JSON.parse(rootElement.getAttribute('getProductMatrix-url'));
  let listAttributeUrl = JSON.parse(rootElement.getAttribute('listAttribute-url'));
  let dir = rootElement.getAttribute('dir');
  const [AttributesTree, setAttributesTree] = useState([]);
  const [translations, setTranslations] = useState({});
  let product = JSON.parse(rootElement.getAttribute('product'));
  const [currentObject, setcurrentObject] = useState(product);
  const [productMatrix, setProductMatrix] = useState(product.attributeMatrix);
  const [defaultMenu , setdefaultMenu] =useState( [
    { key: 'basicInfo', visible: true },
    { key: 'printInfo', visible: false },
    { key: 'advancedInfo', visible: false },
    { key: 'modifiers', visible: false },
    { key: 'recipe', visible: false },
    { key: 'inventory', visible: false }
  ]);
  const [menu, setMenu] = useState(defaultMenu);
  const [showAlert, setShowAlert] = useState(false);
  const [currentModifiers, setcurrentModifiers] = useState(!!product.modifiers ? product.modifiers : []);
  const [disableSubmitButton , setSubmitdisableButton] = useState(false);

  const parentHandlechanges = (childproduct) => {
    setcurrentObject({ ...childproduct });
  }

  const clickSubmit =() =>{
    let btnSubmit = document.getElementById("btnMainSubmit");
    btnSubmit.click();
  }

  const handleMainSubmit = (event) => {
    event.preventDefault();
    event.stopPropagation();
    const form = event.currentTarget;
    if (form.checkValidity() === false) {

      var menu = [...defaultMenu]
      menu[0].visible = true;
      setMenu([...menu]);

      form.classList.add('was-validated');
      return;
    }
    else {
      saveChanges();
    }
  }

  const saveChanges = async () => {
    try {
      setSubmitdisableButton(true);
      let r = { ...currentObject };
      r["active"] ? r["active"] = 1 : r["active"] = 0;
      r["track_serial_number"] ? r["track_serial_number"] = 1 : r["track_serial_number"] = 0;
      r["sold_by_weight"] ? r["sold_by_weight"] = 1 : r["sold_by_weight"] = 0;
      r["modifiers"] = [...currentModifiers];
      var matrixResult = productMatrix.filter((object) => object.deleted != 1);
      r["attributeMatrix"] = [...matrixResult];

      const response = await axios.post(producturl, r, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      if (response.data.message == "Done") {
        window.location.href = categoryurl;
      }
      else
      {
        setShowAlert(true);
        Swal.fire({
            show: showAlert,
            title: 'Error',
            text: translations.technicalerror ,
            icon: "error",
            timer: 2000,
            showCancelButton: false,
            showConfirmButton: false,
           }).then(() => {
            setShowAlert(false); // Reset the state after alert is dismissed
          });
      }
    } catch (error) {
      setShowAlert(true);
      Swal.fire({
          show: showAlert,
          title: 'Error',
          text: translations.technicalerror ,
          icon: "error",
          timer: 2000,
          showCancelButton: false,
          showConfirmButton: false,
         }).then(() => {
          setShowAlert(false); // Reset the state after alert is dismissed
        });
      console.error('There was an error adding the product!', error);
    }

    setSubmitdisableButton(false);
  }

  const cancel = () => {
    window.location.href = categoryurl;
  }

  const getMatrix = async () => {
    const response = await axios.get(getProductMatrix + '/' + currentObject.id)
    setProductMatrix(response.data);
  }


  const getAttributes = async () => {
    const response = await axios.get(listAttributeUrl);
    setAttributesTree(response.data);
  }

  // Clean up object URLs to avoid memory leaks
  React.useEffect(() => {
    getMatrix();
    getAttributes();
    axios.get(localizationurl)
      .then(response => {
        setTranslations(response.data);
      })
      .catch(error => {
        console.error('Error fetching translations', error);
      });
  }, []);


  const handleChange = (index, value) => {
    let currentMenu = [...menu];
    currentMenu[index].visible = value;
    setMenu([...currentMenu]);
  }

  const handleGenerateMatrix = (newMatrix) => {
    setProductMatrix([...newMatrix]);
  }

  const handleActiveDeactiveMatrix =(id) => {
    var editedMatrix = [...productMatrix];
    var index = 0;
    for (let i = 0; i < editedMatrix.length; i++) {
      if (editedMatrix[i].id == id) {
        break;
      }
      index = index + 1;
    }
     if(editedMatrix[index]['deleted']  == 1)
      editedMatrix[index]['deleted'] = 0;
     else
       editedMatrix[index]['deleted'] = 1;
     
       setProductMatrix([...editedMatrix]); 
  }

  const handleProdMatrixChange = (currentKey, editingRow) => {
    var editedMatrix = [...productMatrix];
    var index = 0;

    for (let i = 0; i < editedMatrix.length; i++) {
      if (editedMatrix[i].id == currentKey) {
        break;
      }
      index = index + 1;
    }
    for (var key in editingRow) {
      editedMatrix[index][key] = editingRow[key];
    }
    setProductMatrix([...editedMatrix]);
  }

  const handleModifierChange = (modifierId, key, value) => {
    let modifier = {
      active: 0,
      required: 0,
      default: 0,
      min_modifiers: 0,
      max_modifiers: 0,
      display_order: 0,
      button_display: 0,
      modifier_display: 0,
      product_id: currentObject.id,
      modifier_id: modifierId
    };
    let m = currentModifiers.filter(m => m.modifier_id == modifierId);
    if (!!m && !!m.length) {
      modifier = m[0];
      modifier[key] = value;
    }
    else {
      modifier[key] = value;
      currentModifiers.push(modifier);
    }
    setcurrentModifiers([...currentModifiers]);
  }

  const handleSelectAll = (allModifiers) => {
    let modifier = {
      active: 1,
      required: 0,
      default: 0,
      min_modifiers: 0,
      max_modifiers: 0,
      display_order: 0,
      button_display: 0,
      modifier_display: 0,
      product_id: currentObject.id,
    };
    allModifiers.forEach(m => {
      if (currentModifiers.filter(x => x.modifier_id == m.data.id).length == 0) {
        modifier.modifier_id = m.data.id;
        currentModifiers.push({ ...modifier });
      }
    });
    setcurrentModifiers([...currentModifiers]);
  }


  return (
    <div>
       <SweetAlert2 />
      <div class="row" style={{ padding: "5px" }}>
        <div class="col-9"></div>
        <div class="col-2">
          <Button variant="primary" className="btn btn-primary"
            onClick={clickSubmit}  disabled={disableSubmitButton} 
          >{translations.savechanges}</Button>
        </div>
        <div class="col-1">
          <Button variant="secondary" onClick={cancel} className="btn btn-flex">{translations.cancel}</Button>
        </div>
      </div>
      <div class="row">
        <div class="col-3">
          <div class="card mb-5 mb-xl-8" style={{ minHeight: "100vh", display: "flex", flexDirection: "column", padding: "12px" }}>
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
          <form noValidate validated={true} class="needs-validation" onSubmit={handleMainSubmit}>
            {
              
                <ProductBasicInfo visible={menu[0].visible} translations={translations} parentHandlechanges={parentHandlechanges} product={currentObject} saveChanges={saveChanges}></ProductBasicInfo>

            }
            {
              menu[1].visible ?
                <ProductDisplay translations={translations} parentHandlechanges={parentHandlechanges} product={currentObject} saveChanges={saveChanges}></ProductDisplay>
                : <></>
            }
            {
              menu[2].visible ?
                <ProductAttributes 
                translations={translations}
                parentHandlechanges={parentHandlechanges}
                product={currentObject}
                saveChanges={saveChanges}
                productMatrix={productMatrix}
                AttributesTree={AttributesTree}
                onChange={handleProdMatrixChange}
                onActiveDeactiveMatrix={handleActiveDeactiveMatrix}
                onGenerate={handleGenerateMatrix}
                />
                : <></>
            }
            {
              menu[3].visible ?
                <ProductModifier
                  translations={translations}
                  productId={currentObject.id}
                  productModifiers={currentModifiers}
                  urlList={modifierClassUrl}
                  onChange={handleModifierChange}
                  onSelectAll={handleSelectAll} />
                : <></>
            }
            <input type="submit" id="btnMainSubmit" hidden></input>
              </form>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProductComponent;
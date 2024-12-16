import React, { useState, useCallback } from 'react';
import ProductBasicInfo from "./ProductBasicInfo";
import ProductDisplay from "./ProductDisplay";
import ProductAttributes from "./ProductAttributes";
import ProductModifier from './ProductModifier';
import ProductRecipe from './ProductRecipe';
import axios from 'axios';
import SweetAlert2 from 'react-sweetalert2';
import { Button } from 'primereact/button';
import ProductCombo from './ProductCombo';
import ProductLinkedCombo from './ProductLinkedCombo';
import UnitTransferProduct from './UnitTransferProduct';


const ProductComponent1 = ({ translations, dir }) => {
  const rootElement = document.getElementById('root');
  const producturl = JSON.parse(rootElement.getAttribute('product-url'));
  const categoryurl = JSON.parse(rootElement.getAttribute('category-url'));
  const modifierClassUrl = JSON.parse(rootElement.getAttribute('listModifier-url'));
  let getProductMatrix = JSON.parse(rootElement.getAttribute('getProductMatrix-url'));
  let listAttributeUrl = JSON.parse(rootElement.getAttribute('listAttribute-url'));
  let listRecipeUrl = JSON.parse(rootElement.getAttribute('listRecipe-url'));
  let ingredientProductUrl = JSON.parse(rootElement.getAttribute('ingredientProductUrl-url'));
  let product = JSON.parse(rootElement.getAttribute('product'));
  const [AttributesTree, setAttributesTree] = useState([{data:{}}, {data:{}}]);
  const [currentObject, setcurrentObject] = useState(product);
  const [productMatrix, setProductMatrix] = useState(!!product.attributeMatrix?product.attributeMatrix : [{}] );
  const [units, setUnits] = useState([]);
  const [productUnit, setProductUnit] = useState();
  const [unitTransfer, setUnitTransfers] = useState(product.unitTransfer);
  const [currentTab, setCurrentTab] = useState(1);
  const [defaultMenu, setdefaultMenu] = useState([
    { key: 'basicInfo', visible: true },
    { key: 'printInfo', visible: false },
    { key: 'modifiers', visible: false },
    { key: 'recipe', visible: false },
    { key: 'groupCombo', visible: false },
    { key: 'linkedCombo', visible: false },
    { key: 'inventory', visible: false },
    { key: 'Unit', visible: false },
    { key: 'advancedInfo', visible: false },
  ]);
  const [menu, setMenu] = useState(defaultMenu);
  const [recipe, setRecipe] = useState([]);
  const [ingredientTree, setIngredientTree] = useState([]);
  const [categories, setCategories] = useState([]);
  const [showAlert, setShowAlert] = useState(false);
  const [currentModifiers, setcurrentModifiers] = useState(!!product.modifiers ? product.modifiers : []);
  const [disableSubmitButton, setSubmitdisableButton] = useState(false);
  const [productLOVs, setProductLOVs] = useState({ productForComboLOV: [], linkedComboPromptLOV: [], linkedComboLOV: [] });

  const parentHandlechanges = (childproduct) => {
    setcurrentObject({ ...childproduct });
  }

  const clickSubmit = () => {
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
  const onComboChange = (key, value) => {
    currentObject[key] = value;
    setcurrentObject({ ...currentObject });
    return {
      message: "Done"
    }
  }
  const saveChanges = async () => {
    try {
      if (!validCombo()) return;
      setSubmitdisableButton(true);
      let r = { ...currentObject };
      r["active"] ? r["active"] = 1 : r["active"] = 0;
      r["track_serial_number"] ? r["track_serial_number"] = 1 : r["track_serial_number"] = 0;
      r["sold_by_weight"] ? r["sold_by_weight"] = 1 : r["sold_by_weight"] = 0;
      r["prep_recipe"] ? r["prep_recipe"] = 1 : r["prep_recipe"] = 0;
      r["modifiers"] = [...currentModifiers];
      var matrixResult = productMatrix.filter((object) => object.deleted != 1);
      r["attributeMatrix"] = [...matrixResult];
      let recipe1 = recipe.filter((object) => object.id != -100);
      r["recipe"] = [...recipe1];
      let transfer = unitTransfer.filter((object) => object.id != -100);

      if (!!productUnit) {
        if (!!!productUnit.id)
          transfer.push({ id: 0, unit1: productUnit.unit1, unit2: -100, transfer: -100, primary: -100 });
        else
          transfer.push(productUnit);//{ id: 0 , unit1: productUnit , unit2: -100 , transfer: -100 , primary :-100});  
      }

      const sortedItems = [...transfer].sort((a, b) => a.id - b.id);
      r["transfer"] = [...sortedItems];

      const response = await axios.post(producturl, r, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      if (response.data.message == "Done") {
        window.location.href = categoryurl;
      }
      else {
        setShowAlert(true);
        Swal.fire({
          show: showAlert,
          title: 'Error',
          text: translations.technicalerror,
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
        text: translations.technicalerror,
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


  const getName = (name_en, name_ar) => {
    if (dir == 'ltr')
      return name_en;
    else
      return name_ar
  }

  const getProductLOVs = async () => {
    const response = await axios.get('/productLOVs/' + product.id);
    const products = response.data.product.map(e => { return { label: getName(e.name_en, e.name_ar), value: e.id } });
    const linkedComboPrompts = response.data.prompt.map(e => { return { label: translations[e.name], value: e.value } });
    const linkedCombos = response.data.linkedCombo.slice(0, response.data.linkedCombo.length - 1).
      map(e => {
        return {
          label: getName(e.data.name_en, e.data.name_ar),
          value: e.data.id,
          combos: e.data.combos
        }
      });

    const category = response.data.category;
    setCategories(category);

    const ingredient = response.data.ingredient.map(e => { return { label: getName(e.name_en, e.name_ar), value: e.id + e.type, cost: e.cost } });
    setIngredientTree(ingredient);
    const recipe = response.data.recipe.map(e => { return { id: e.newid, quantity: e.quantity, cost: null, newid: e.newid } });
    recipe.push({ id: -100, quantity: null, cost: null, newid: null });
    setRecipe(recipe);

    const matrix = response.data.matrix;
    setProductMatrix(matrix);

    const attribute = response.data.attribute;
    setAttributesTree(attribute);

    const units = response.data.unitTransfer;
    const unitsResult = units.map(e => { return { label: e.unit1, value: e.id } });
    setUnits(unitsResult);

    let mainUnit = units.find(function (element) {
      return element.unit2 == null;
    });

    setProductUnit(mainUnit)

    const unitTransfers = response.data.unitTransfer;
    const unitTransfersResult = unitTransfers.length > 0 ? unitTransfers.filter(e => e.unit2 != null).map(e => {
      return { id: e.id, transfer: e.transfer, unit1: e.unit1, unit2: e.unit2, primary: e.primary, newid: e.newid }
    }) : [];
    unitTransfersResult.push({ id: -100, unit1: null, unit2: null, primary: false, transfer: null, newid: null });
    setUnitTransfers(unitTransfersResult);

    setProductLOVs({
      "productForComboLOV": products,
      "linkedComboPromptLOV": linkedComboPrompts,
      "linkedComboLOV": linkedCombos,
      "recipe": recipe,
      "ingredient": ingredient,
      "attribute": attribute,
      "matrix": matrix
    });
  }


  // Clean up object URLs to avoid memory leaks
  React.useEffect(() => {
    getProductLOVs();
  }, []);


  const handleChange = (index, value) => {
    let currentMenu = [...menu];
    currentMenu[index].visible = value;
    setMenu([...currentMenu]);
  }

  const parentHandleRecipe = (resultrecipe) => {
    setRecipe([...resultrecipe]);
  }

  const parentHandleTransfer = (result) => {
    setUnitTransfers([...result]);
  }

  const handleGenerateMatrix = (newMatrix) => {
    setProductMatrix([...newMatrix]);
  }

  const handleActiveDeactiveMatrix = (id) => {
    var editedMatrix = [...productMatrix];
    var index = 0;
    for (let i = 0; i < editedMatrix.length; i++) {
      if (editedMatrix[i].id == id) {
        break;
      }
      index = index + 1;
    }
    if (editedMatrix[index]['deleted'] == 1)
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

  const validCombo = () => {
    if (!!currentObject.set_price &&
      !!currentObject.combos && !!currentObject.combos.length) {
      const totalPrice = currentObject.combos.reduce((sum, item) => sum + (!!item.price ? parseFloat(item.price) : 0), 0);
      if (totalPrice != currentObject.price) {
        setShowAlert(true);
        Swal.fire({
          show: showAlert,
          title: 'Error',
          text: translations.ComboPriceError,
          icon: "error",
          timer: 4000,
          showCancelButton: false,
          showConfirmButton: false,
        }).then(() => {
          setShowAlert(false); // Reset the state after alert is dismissed
        });
        return false;
      }
    }
    if (!!currentObject.group_combo && !!currentObject.linked_combo && currentObject.group_combo == currentObject.linked_combo) {
      setShowAlert(true);
      Swal.fire({
        show: showAlert,
        title: 'Error',
        text: translations.groupComboAndLinkedComboSelected,
        icon: "error",
        timer: 4000,
        showCancelButton: false,
        showConfirmButton: false,
      }).then(() => {
        setShowAlert(false); // Reset the state after alert is dismissed
      });
      return false;
    }
    return true;
  }

  const handleMainUnit = (value) => {
    setProductUnit(value);
  }


  return (
    <div>
      <SweetAlert2 />
      <div class="container">
            <div class="row">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                        <h1>{`${translations.Add} ${translations.product}`}</h1>

                    </div>
                </div>
                <div class="col-6" style={{"justify-content": "end", "display": "flex"}}>
                    <div class="flex-center" style={{"display": "flex"}}>
                        <button onClick={clickSubmit} disabled={disableSubmitButton} class="btn btn-primary mx-2"
                            style={{"width": "12rem"}}>{translations.savechanges}</button>

                    </div>

                </div>
            </div>
        </div>


        <div class="separator d-flex flex-center my-6">
            <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
        </div>
      <div class="row">
      <form noValidate validated={true} class="needs-validation" onSubmit={handleMainSubmit}>
        <div class="container">
          <div class="row">
            <div class="col-sm">

              <div class="card" data-section="contact" style={{"border": "0", "box-shadow": "none"}}>
                <div class="container">
                  <ProductBasicInfo 
                  visible={menu[0].visible} 
                  translations={translations} 
                  parentHandlechanges={parentHandlechanges} 
                  product={currentObject} 
                  saveChanges={saveChanges} 
                  category ={categories}/>
                </div>
              </div>
            </div>
            <div class="col-7">

              <div class="card-toolbar ">
                <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold" role="tablist">
                  {menu.map((m, index) => 
                    { return index == 0 ? <></>:
                    <li class="nav-item" role="presentation">
                      <a id={`${m.key}_tab`} onClick={(e)=>setCurrentTab(index)} class={`nav-link justify-content-center text-active-gray-800 ${currentTab==index ? 'active' : ''}`}
                        data-bs-toggle="tab" role="tab" href={`#${m.key}`} aria-selected="true">
                        {translations[m.key]}
                      </a>
                    </li>}
                  
                  )}
                </ul>
              </div>
              <div class="tab-content">
                <div id="printInfo" class="card-body p-0 tab-pane fade show active" role="tabpanel"
                  aria-labelledby="printInfo_tab">
                  <ProductDisplay 
                    translations={translations} 
                    parentHandlechanges={parentHandlechanges} 
                    product={currentObject} 
                    saveChanges={saveChanges}/>
                </div>
              </div>

              <div class="tab-content ">
                <div id="advancedInfo" class="card-body p-0 tab-pane fade show" role="tabpanel"
                  aria-labelledby="advancedInfo_tab">
                     {<ProductAttributes 
                      translations={translations}
                      parentHandlechanges={parentHandlechanges}
                      product={currentObject}
                      saveChanges={saveChanges}
                      productMatrix={productMatrix}
                      AttributesTree={AttributesTree}
                      onChange={handleProdMatrixChange}
                      onActiveDeactiveMatrix={handleActiveDeactiveMatrix}
                      onGenerate={handleGenerateMatrix}
                      />}
                </div>
              </div>
              <div class="tab-content">
                <div id="modifiers" class="card-body p-0 tab-pane fade show" role="tabpanel" aria-labelledby="modifiers_tab">
                <ProductModifier
                    translations={translations}
                    productId={currentObject.id}
                    productModifiers={currentModifiers}
                    urlList={modifierClassUrl}
                    onChange={handleModifierChange}
                    onSelectAll={handleSelectAll} />
                </div>
              </div>


              <div class="tab-content">
                <div id="recipe" class="card-body p-0 tab-pane fade show " role="tabpanel" aria-labelledby="recipe_tab">
                <ProductRecipe
                    translations={translations}
                    product={currentObject}
                    productRecipe={recipe}
                    ingredientTree={ingredientTree}
                    parentHandleRecipe={parentHandleRecipe}
                    handleChange={parentHandlechanges}
                    dir={dir} />

                </div>
              </div>
              <div class="tab-content">
                <div id="groupCombo" class="card-body p-0 tab-pane fade show " role="tabpanel" aria-labelledby="groupCombo_tab">
                  <ProductCombo
                      translations={translations}
                      product={currentObject}
                      onComboChange={onComboChange}
                      products={productLOVs.productForComboLOV}
                      dir={dir} />

                </div>
              </div>
              <div class="tab-content">
                <div id="linkedCombo" class="card-body p-0 tab-pane fade show " role="tabpanel" aria-labelledby="linkedCombo_tab">
                  <ProductLinkedCombo
                      translations={translations}
                      product={currentObject}
                      onComboChange={onComboChange}
                      pormpts={productLOVs.linkedComboPromptLOV}
                      linkedCombos={productLOVs.linkedComboLOV}
                      products={productLOVs.productForComboLOV}
                      dir={dir} />

                </div>
              </div>
              <div class="tab-content">
                <div id="inventory" class="card-body p-0 tab-pane fade show " role="tabpanel" aria-labelledby="inventory_tab">
                

                </div>
              </div>
              <div class="tab-content">
                <div id="Unit" class="card-body p-0 tab-pane fade show " role="tabpanel" aria-labelledby="Unit_tab">
                  <UnitTransferProduct
                    translations={translations}
                    product={currentObject}
                    unitTransfer={unitTransfer}
                    unitTree={units}
                    parentHandle={parentHandleTransfer}
                    handleMainUnit={handleMainUnit}
                    productUnit ={productUnit}
                    dir={dir} />

                </div>
              </div>
            </div>
          </div>
        </div>
        <input type="submit" id="btnMainSubmit" hidden></input>
              </form>
      </div>
    </div>
  );
};

export default ProductComponent1;
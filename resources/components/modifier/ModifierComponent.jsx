import React, { useState } from 'react';
import axios from 'axios';
import SweetAlert2 from 'react-sweetalert2';
import ModifierBasicInfo from './ModifierBasicInfo';
import ModifierRecipe from './ModifierRecipe';
import ModifierPriceTier from './ModifierPriceTier';
import { getRowName } from '../lang/Utils';
import UnitTransferModifier from './UnitTransferModifier';


const ModifierComponent = ({ translations, dir }) => {
  const rootElement = document.getElementById('root');
  const modifierurl = JSON.parse(rootElement.getAttribute('modifier-url'));
  let modifier = JSON.parse(rootElement.getAttribute('modifier'));
  const [currentObject, setcurrentObject] = useState(modifier);
  const [units, setUnits] = useState([]);
  const [modifierUnit, setModifierUnit] = useState();
  const [unitTransfer, setUnitTransfers] = useState(!!modifier.unitTransfer ? modifier.unitTransfer : []); 
  const [currentTab, setCurrentTab] = useState(1);
  const [defaultMenu, setdefaultMenu] = useState([
    { key: 'basicInfo', visible: true },
    { key: 'priceTier', visible: true },
    { key: 'recipe', visible: true },
    { key: 'Unit', visible: false },
  ]);
  const [menu, setMenu] = useState(defaultMenu);
  //const [ingredientTree, setIngredientTree] = useState([]);
  const [showAlert, setShowAlert] = useState(false);
  const [disableSubmitButton, setSubmitdisableButton] = useState(false);
  const [modifierLOVs, setModifierLOVs] = useState({ modifierClasses : [], taxes: [], ingredient: [] });

  const parentHandlechanges = (childModifier) => {
    setcurrentObject({ ...childModifier });
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
    if (!validModifier()) return;
    else {
      saveChanges();
    }
  }

  const onModifierFieldChange = (key, value) => {
    currentObject[key] = value;
    setcurrentObject({ ...currentObject });
    console.log(`${key} :`, currentObject[key]);
    return {
      message: "Done"
    }
    
  }

  const getErrorMessage = (data) => {
    let res = ''
    for (let index = 0; index < data.length; index++) {
      const element = data[index];
      res += `<div>${translations[element]}</div>`;
    }
    return res;
  }

  const handleUniqueError = (data) => {
    setShowAlert(true);
    Swal.fire({
      show: showAlert,
      title: 'Error',
      html: `<div>${translations.exist}</div>${getErrorMessage(data)}`,
      icon: "error",
      timer: 4000,
      showCancelButton: false,
      showConfirmButton: false,
    }).then(() => {
      setShowAlert(false); // Reset the state after alert is dismissed
    });
  }

  const saveChanges = async () => {
    try {
      console.log(currentObject);
      setSubmitdisableButton(true);
      let r = { ...currentObject };
      r["active"] ? r["active"] = 1 : r["active"] = 0;
      let transfer = [...unitTransfer];

      if (!!modifierUnit) {
        if (!!!modifierUnit.id)
          transfer.push({ id: 0, unit1: modifierUnit.unit1, unit2: -100, transfer: -100, primary: -100 });
        else
          transfer.push(modifierUnit);//{ id: 0 , unit1: productUnit , unit2: -100 , transfer: -100 , primary :-100});  
      }

      const sortedItems = [...transfer].sort((a, b) => a.id - b.id);
      r["transfer"] = [...sortedItems];
      const response = await axios.post(modifierurl, r);
      if (response.data.message == "Done") {
        window.location.href = modifierurl;
      }
      else if (response.data.message == "UNIQUE") {
        handleUniqueError(response.data.data);
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
      console.error('There was an error adding the modifier!', error);
    }
    setSubmitdisableButton(false);
  }


  const getName = (name_en, name_ar) => {
    if (dir == 'ltr')
      return name_en;
    else
      return name_ar
  }

  const getModifierLOVs = async () => {
    const response = await axios.get('/modifierLOVs/' + modifier.id);
    
    const ingredient = response.data.ingredient.map(e => { return { label: getName(e.name_en, e.name_ar), value: e.id + e.type, cost: e.cost } });
    const taxes = response.data.taxes.map(e => { return { label: getRowName(e, dir), value: e.id, default : e.default} });
    const modifierClasses = response.data.modifierClasses.map(e => { return { label: getRowName(e, dir), value: e.id} });

    const units = response.data.unitTransfer;
    const unitsResult = units.map(e => { return { label: e.unit1, value: e.id } });
    setUnits(unitsResult);

    let mainUnit = units.find(function (element) {
      return element.unit2 == null;
    });

    setModifierUnit(mainUnit)

    const unitTransfers = response.data.unitTransfer;
    const unitTransfersResult = unitTransfers.length > 0 ? unitTransfers.filter(e => e.unit2 != null).map(e => {
      return { id: e.id, transfer: e.transfer, unit1: e.unit1, unit2: e.unit2, primary: e.primary, newid: e.newid }
    }) : [];
    setUnitTransfers(unitTransfersResult);

    setModifierLOVs({
      "ingredient": ingredient,
      "taxes" : taxes,
      "modifierClasses" : modifierClasses
    });
  }

  // Clean up object URLs to avoid memory leaks
  React.useEffect(() => {
    getModifierLOVs();
  }, []);


  const handleChange = (index, value) => {
    let currentMenu = [...menu];
    currentMenu[index].visible = value;
    setMenu([...currentMenu]);
  }

  const parentHandleTransfer = (result) => {
    setUnitTransfers([...result]);
  }


  const validModifier = () => {
    let errorMessage = null;
    let valid = true;
    if (!!!modifierUnit || !!!modifierUnit.unit1) {
      valid = false;
      errorMessage = translations.noDefaultUnit;
      document.getElementById("Unit_tab").click();
    }
    if (!valid) {
      setShowAlert(true);
      Swal.fire({
        show: showAlert,
        title: 'Error',
        text: errorMessage,
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
    setModifierUnit(value);
  }

  return (
    <div>
      <SweetAlert2 />
      <div class="container">
        <div class="row">
          <div class="col-6">
            <div class="d-flex align-items-center gap-2 gap-lg-3">
              <h1>{`${translations.Add} ${translations.modifier}`}</h1>

            </div>
          </div>
          <div class="col-6" style={{ "justify-content": "end", "display": "flex" }}>
            <div class="flex-center" style={{ "display": "flex" }}>
              <button onClick={clickSubmit} disabled={disableSubmitButton} class="btn btn-primary mx-2"
                style={{ "width": "12rem" }}>{translations.savechanges}</button>

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

                <div class="card" data-section="contact" style={{ "border": "0", "box-shadow": "none" }}>
                  <div class="container">
                    <ModifierBasicInfo
                      visible={menu[0].visible}
                      translations={translations}
                      onBasicChange={onModifierFieldChange}
                      currentObject={currentObject}
                      lov = {modifierLOVs}
                      saveChanges={saveChanges}
                    />
                  </div>
                </div>
              </div>
              <div class="col-7">

                <div class="card-toolbar ">
                  <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold" role="tablist">
                    {menu.map((m, index) => {
                      return index == 0 ? <></> :
                        <li class="nav-item" role="presentation">
                          <a id={`${m.key}_tab`} onClick={(e) => setCurrentTab(index)} class={`nav-link justify-content-center text-active-gray-800 ${currentTab == index ? 'active' : ''}`}
                            data-bs-toggle="tab" role="tab" href={`#${m.key}`} aria-selected="true">
                            {translations[m.key]}
                          </a>
                        </li>
                    }

                    )}
                  </ul>
                </div>
                <div class="tab-content">
                  <div id="priceTier" class="card-body p-0 tab-pane fade show active" role="tabpanel" aria-labelledby="priceTier_tab">
                    <ModifierPriceTier
                      translations={translations}
                      dir={dir}
                      currentObject={currentObject}
                      lov= {modifierLOVs}
                      onBasicChange={onModifierFieldChange}/>
                  </div>
                </div>
               
                <div class="tab-content">
                  <div id="recipe" class="card-body p-0 tab-pane fade show " role="tabpanel" aria-labelledby="recipe_tab">
                    <ModifierRecipe
                      translations={translations}
                      modifier={currentObject}
                      modifierRecipe={currentObject.recipe}
                      ingredientTree={modifierLOVs.ingredient}
                      onBasicChange={onModifierFieldChange}
                      dir={dir} />

                  </div>
                </div>

                <div class="tab-content">
                  <div id="Unit" class="card-body p-0 tab-pane fade show " role="tabpanel" aria-labelledby="Unit_tab">
                    <UnitTransferModifier
                      translations={translations}
                      modifier={currentObject}
                      unitTransfer={unitTransfer}
                      unitTree={units}
                      parentHandle={parentHandleTransfer}
                      handleMainUnit={handleMainUnit}
                      modifierUnit={modifierUnit}
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

export default ModifierComponent;
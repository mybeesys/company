import React, { useEffect, useState } from 'react';
import SweetAlert2 from 'react-sweetalert2';
import IngredientBasicInfo from './IngredientBasicInfo';
import UnitTransferIngredient from './UnitTransferIngredient';
import axios from 'axios';

const IngredientDetail = ({dir, translations}) => {
  const rootElement = document.getElementById('root');
  let ingredient = JSON.parse(rootElement.getAttribute('ingredient'));
  const [currentObject, setcurrentObject] = useState(ingredient);
  const [defaultMenu, setdefaultMenu] = useState([
    { key: 'basicInfo', visible: true },
    { key: 'Unit', visible: false },
  ]);
  const [disableSubmitButton, setSubmitdisableButton] = useState(false);
  const [menu, setMenu] = useState(defaultMenu);
  const [units, setUnits] = useState([]);
  const [vendor, setVendor] = useState([]);
  const [unitTransfer, setUnitTransfers] = useState([]);

  const handleChange = (index, value) => {
    let currentMenu = [...menu];
    currentMenu[index].visible = value;
    setMenu([...currentMenu]);
  }

  const onBasicChange = (key, value) => {
    currentObject[key] = value;
    setcurrentObject({...currentObject});
  }

  const clickSubmit = () => {
    let btnSubmit = document.getElementById("btnMainSubmit");
    btnSubmit.click();
  }

  const cancel = () => {
    window.location.href = "/ingredient";
  }

  const saveChanges = async () => {
    try {

      setSubmitdisableButton(true);
    
      let r = { ...currentObject };

      r['unit_measurement'] = r['unit_measurement'] ? r['unit_measurement'] : units[0].value ;
      r['active'] = r['active'] ? r['active'] : 0 ;

      let transfer = unitTransfer.filter((object) => object.id != -100);
      r["transfer"] = [...transfer];

      const response = await axios.post('/ingredient', r);
      if (response.data.message == "Done") {
        window.location.href = '/ingredient';
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

  const unitTransferHandle = (result) =>
  {
    setUnitTransfers([...result]);
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

  useEffect(() => {
    const fetchData = async () => {
        const res2 = await axios.get('/unitTypeList');
        const units = res2.data.map(e => { return { label: dir=='rtl'? e.name_ar : e.name_en, name: dir=='rtl'? e.name_ar : e.name_en, value: e.id } });
     
        const res1 = await axios.get('/getUnitsTransferList/ingredient/'+ currentObject.id);
        const unitTransfers =res1.data;
        const unitTransfersResult = unitTransfers.length > 0 ? unitTransfers.map(e => { return { id : e.newid ,  transfer : e.transfer , unit1: e.unit1 , unit2: e.unit2 , primary: e.primary , newid : e.newid }}):[];
        unitTransfersResult.push({id: -100 , unit1 : null , unit2: null , primary : false , transfer:null , newid : null});
      
        const res = await axios.get('/getVendors');
        const vendors = res.data.map(e => { return { name: dir=='rtl'? e.name_ar : e.name_en, value: e.id } });

        setUnitTransfers(unitTransfersResult);
        setVendor(vendors);
        setUnits(units);
      }
      fetchData().catch(console.error);
  }, []);

  return (
    <div>
      <SweetAlert2 />
      <div class="row" style={{ padding: "5px" }}>
        <div class="col-9"></div>
        <div class="col-2">
          <button class="btn btn-primary" onClick={clickSubmit} disabled={disableSubmitButton}>
            {translations.savechanges}
          </button>
        </div>
        <div class="col-1">
          <button class="btn btn-flex" onClick={cancel} >{translations.cancel}</button>
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
                menu[0].visible ?
                  <IngredientBasicInfo
                    translations={translations}
                    currentObject={currentObject}
                    onBasicChange = {onBasicChange}
                    units={units}
                    vendors={vendor}
                    dir ={dir}
                      />
                  : <></>
              }
                {
                menu[1].visible ?
                  <UnitTransferIngredient
                    translations={translations}
                    parentHandle = {unitTransferHandle}
                    unitTree={units}
                    unitTransfer={unitTransfer}
                    dir ={dir}
                      />
                  : <></>
              }
          
          
              <input type="submit" id="btnMainSubmit" hidden></input>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
}

export default IngredientDetail;
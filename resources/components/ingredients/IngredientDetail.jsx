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
  const [showAlert, setShowAlert] = useState(false);
  
  const [menu, setMenu] = useState(defaultMenu);
  const [units, setUnits] = useState([]);
  const [vendor, setVendor] = useState([]);
  const [unitTransfer, setUnitTransfers] = useState([]);
  const [productUnit, setProductUnit] = useState();
  const [currentTab, setCurrentTab] = useState(1);

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

      //r['unit_measurement'] = r['unit_measurement'] ? r['unit_measurement'] : units[0].value ;
      r['active'] = r['active'] ? r['active'] : 0 ;

      let transfer = unitTransfer.filter((object) => object.id != -100);

      if(!!productUnit){
        if(!!!productUnit.id)
          transfer.push({ id: 0 , unit1: productUnit.unit1 , unit2: -100 , transfer: -100 , primary :-100});
        else
          transfer.push(productUnit);//{ id: 0 , unit1: productUnit , unit2: -100 , transfer: -100 , primary :-100});  
      }
         
      const sortedItems = [...transfer].sort((a, b) => a.id - b.id);
      r["transfer"] = [...sortedItems];

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

  const handleMainUnit = (value) =>{
    setProductUnit(value);
   }


   const parentHandleTransfer = (result) => 
    {
      setUnitTransfers([...result]);
    }


  useEffect(() => {
    const fetchData = async () => {
         
        const res = await axios.get('/getVendors');
        const vendors = res.data.map(e => { return { name: dir=='rtl'? e.name_ar : e.name_en, value: e.id } });
        setVendor(vendors);

        const response = await axios.get('/getUnitsTransferList/ingredient/'+ currentObject.id);

        const units =response.data;
        const unitsResult = units.map(e => { return { label: e.unit1 , value: e.id } });
        setUnits(unitsResult);
    
        let mainUnit = units.find(function (element) {
          return element.unit2 == null;
        });
    
        setProductUnit(mainUnit)
        
        const unitTransfers =response.data;
        const unitTransfersResult = unitTransfers.length > 0 ? unitTransfers.filter(e=> e.unit2 != null).map(e => {
              return { id : e.id ,  transfer : e.transfer , unit1: e.unit1 , unit2: e.unit2 , primary: e.primary , newid : e.newid }}):[];
        setUnitTransfers(unitTransfersResult);
        
      }
      fetchData().catch(console.error);
  }, []);

  return (
    <div>
      <SweetAlert2 />
      <div class="container">
        <div class="row">
          <div class="col-6">
            <div class="d-flex align-items-center gap-2 gap-lg-3">
              <h1>{`${translations.Add} ${translations.Ingredient}`}</h1>

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
            <div class="col-5">
                  <IngredientBasicInfo
                    translations={translations}
                    currentObject={currentObject}
                    onBasicChange = {onBasicChange}
                    units={units}
                    vendors={vendor}
                    dir ={dir}
                      />
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
                <div id="discount_qualification" class="card-body p-0 tab-pane fade show active" role="tabpanel"
                  aria-labelledby="discount_qualification_tab">
                  <UnitTransferIngredient
                    translations={translations}
                    unitTransfer={unitTransfer}
                    unitTree={units}
                    parentHandle={parentHandleTransfer}
                    handleMainUnit={handleMainUnit}
                    productUnit ={productUnit}
                    dir={dir} 
                      />
                   </div>
              </div>
          </div>
        <input type="submit" id="btnMainSubmit" hidden></input>
        </div>
        </div>
      </form>
      </div>
    </div>
  );
}

export default IngredientDetail;
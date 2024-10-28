import React, { useEffect, useState } from 'react';
import SweetAlert2 from 'react-sweetalert2';
import CustomMenuTime from './CustomMenuTime';
import CustomMenuProduct from './CustomMenuProduct';
import CustomMenuBasicInfo from './CustomMenuBasicInfo';
import { defaultMenuTime } from './DefaultTimesSlots';


const CustomMenuDetail = ({dir, translations}) => {
  const rootElement = document.getElementById('root');
  let custommenu = JSON.parse(rootElement.getAttribute('custommenu'));
  const [defaultMenu, setdefaultMenu] = useState([
    { key: 'basicInfo', visible: true },
    { key: 'times', visible: false },
    { key: 'products', visible: false },
  ]);
  const [disableSubmitButton, setSubmitdisableButton] = useState(false);
  const [menu, setMenu] = useState(defaultMenu);
  const [currentObject, setcurrentObject] = useState(custommenu);
  const [customMenuDates, setCustomMenuDates] = useState(!!custommenu.dates ? custommenu.dates[0] : defaultMenuTime[0]);
  const [selectedProductKeys, setSelectedProductKeys] = useState(!!custommenu.products ? custommenu.products : []);
  const [showAlert, setShowAlert] = useState(false);
  
  useEffect(() => {
  }, []);

  const saveChanges = async () => {
    try {
      setSubmitdisableButton(true);
      let r = { ...currentObject };
      r["dates"] = {...customMenuDates};
      r["products"] = [...selectedProductKeys];
      const response = await axios.post('/customMenu', r);
      if (response.data.message == "Done") {
        window.location.href = '/customMenu';
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

  const handleChange = (index, value) => {
    let currentMenu = [...menu];
    currentMenu[index].visible = value;
    setMenu([...currentMenu]);
  }

  const clickSubmit = () => {
    let btnSubmit = document.getElementById("btnMainSubmit");
    btnSubmit.click();
  }

  const cancel = () => {
    window.location.href = "/customMenu";
  }

  const onDateTimeChange = (type, key, value, day_no) => {
    if (type == 'T') {
      let index = customMenuDates.times.findIndex(x => x.day_no == day_no);
      customMenuDates.times[index][key] = !!value ? value.toLocaleTimeString('en-US', { hour12: false }) : null;
    }
    else if(type == 'D'){
      customMenuDates[key] = !!value ? `${value.getFullYear()}-${(value.getMonth()+1).toString().padStart(2, '0')}-${value.getDate().toString().padStart(2, '0')}` : null;
    }
    else if(type == 'C'){
      let index = customMenuDates.times.findIndex(x => x.day_no == day_no);
      customMenuDates.times[index][key] = value;
    }
    setCustomMenuDates({...customMenuDates})
  }

  const onBasicChange = (key, value) => {
    currentObject[key] = value;
    setcurrentObject({...currentObject});
  }

  const onProductSelectionChange = (keys) =>{
    let prods = keys.map((x) => { return {product_id : x}});
    setSelectedProductKeys(prods);
  }

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
                  <CustomMenuBasicInfo
                    translations={translations}
                    currentObject={currentObject}
                    onBasicChange = {onBasicChange}
                    dir ={dir}
                      />
                  : <></>
              }
              {
                menu[1].visible ?
                  <CustomMenuTime
                    translations={translations}
                    customMenuDates={customMenuDates}
                    onDateTimeChange = {onDateTimeChange}
                      />
                  : <></>
              }
              {
                menu[2].visible ?
                  <CustomMenuProduct
                    translations={translations}
                    customMenuProducts = {selectedProductKeys} 
                    onProductSelectionChange={onProductSelectionChange}
                    dir={dir}
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
};

export default CustomMenuDetail;
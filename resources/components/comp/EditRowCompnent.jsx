import React, { useEffect, useState } from 'react';
import SweetAlert2 from 'react-sweetalert2';
import axios from 'axios';

const EditRowCompnent = ({dir, translations, defaultMenu, currentObject, apiUrl, afterSubmitUrl, validateObject}) => {
  const [disableSubmitButton, setSubmitdisableButton] = useState(false);
  const [menu, setMenu] = useState([]);
  const [showAlert, setShowAlert] = useState(false);
  
  useEffect(() => {
    if(!!!menu || menu.length ==0)
      setMenu(defaultMenu.map((m)=> {return {key:m.key, visible:m.visible}}));
  }, [defaultMenu]);

  const saveChanges = async () => {
    try {
      setSubmitdisableButton(true);
      let r = { ...currentObject };
      let message = !!validateObject ? validateObject(currentObject) : 'Success';
      if(message!= 'Success'){
        setShowAlert(true);
        Swal.fire({
            show: showAlert,
            title: 'Error',
            text: message ,
            icon: "error",
            timer: 2000,
            showCancelButton: false,
            showConfirmButton: false,
           }).then(() => {
            setShowAlert(false); // Reset the state after alert is dismissed
          });
        return;
      }
      // r["cards"] = r["cards"].map(x=> { return {payment_card_id : x.value} });
      // r["diningTypes"] = r["diningTypes"].map(x=> { return {dining_type_id : x.value} });
      const response = await axios.post(`/${apiUrl}`, r);
      if (response.data.message == "Done") {
        window.location.href = !!afterSubmitUrl ? afterSubmitUrl : `/${apiUrl}`;
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

      // var menu = [...menu]
      // menu[0].visible = true;
      // setMenu([...menu]);

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
    window.location.href = !!afterSubmitUrl ? afterSubmitUrl : `/${apiUrl}`;
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
                menu.map((item, index)=> item.visible ? defaultMenu[index].comp : <></>)
              }
              <input type="submit" id="btnMainSubmit" hidden></input>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
};

export default EditRowCompnent;
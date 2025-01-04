import React, { useEffect, useState } from 'react';
import SweetAlert2 from 'react-sweetalert2';
import axios from 'axios';

const EditRowCompnent = ({ dir, translations, defaultMenu, currentObject, apiUrl, afterSubmitUrl, validateObject, type, handleError }) => {
  const [disableSubmitButton, setSubmitdisableButton] = useState(false);
  const [menu, setMenu] = useState([]);
  const [showAlert, setShowAlert] = useState(false);
  const [currentTab, setCurrentTab] = useState(1);

  useEffect(() => {
    if (!!!menu || menu.length == 0)
      setMenu(defaultMenu);
  }, [defaultMenu, currentObject]);

  const saveChanges = async () => {
    try {
      setSubmitdisableButton(true);
      let r = { ...currentObject };
      let message = !!validateObject ? validateObject(currentObject) : 'Success';
      if (message != 'Success') {
        setShowAlert(true);
        Swal.fire({
          show: showAlert,
          title: 'Error',
          text: message,
          icon: "error",
          timer: 2000,
          showCancelButton: false,
          showConfirmButton: false,
        }).then(() => {
          setShowAlert(false); // Reset the state after alert is dismissed
          setSubmitdisableButton(false);
        });
        return;
      }
      // r["cards"] = r["cards"].map(x=> { return {payment_card_id : x.value} });
      // r["diningTypes"] = r["diningTypes"].map(x=> { return {dining_type_id : x.value} });
      const response = await axios.post(`/${apiUrl}`, r);
      if (response.data.message == "Done") {
        window.location.href = !!afterSubmitUrl ? afterSubmitUrl : `/${apiUrl}`;
      }
      else {
        if(!!!handleError){
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
        else{
          handleError(response.data);
        }
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
      <div class="container">
        <div class="row">
          <div class="col-6">
            <div class="d-flex align-items-center gap-2 gap-lg-3">
              <h1>{!!!translations[type] ? type : `${!!!currentObject.id ? translations.Add : translations.Edit} ${translations[type]}`}</h1>

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
                    {defaultMenu.length >0 ? defaultMenu[0].comp : <></>}
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

                {
                  defaultMenu.map((item, index) => index !=0 ?
                    <div class="tab-content pt-3">
                      <div id={item.key} class="card-body p-0 tab-pane fade show active" role="tabpanel"
                        aria-labelledby={`${item.key}_tab`}>
                          {defaultMenu[index].comp}
                      </div>
                    </div>
                    : <></>)
                }
              </div>
            </div>
          </div>
          <input type="submit" id="btnMainSubmit" hidden></input>
        </form>
      </div>
    </div>
  );
};

export default EditRowCompnent;
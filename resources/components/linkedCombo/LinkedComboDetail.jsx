import React, { useEffect, useState } from 'react';
import SweetAlert2 from 'react-sweetalert2';
import axios from 'axios';
import LinkedComboBasicInfo from './LinkedComboBasicInfo';
import LinkedComboGroup from './LinkedComboGroup';

const LinkedComboDetail = ({dir, translations}) => {
  const rootElement = document.getElementById('root');
  let linkedCombo = JSON.parse(rootElement.getAttribute('linkedCombo'));
  const [defaultMenu, setdefaultMenu] = useState([
    { key: 'basicInfo', visible: true },
    { key: 'groups', visible: true }
  ]);
  const [disableSubmitButton, setSubmitdisableButton] = useState(false);
  const [menu, setMenu] = useState(defaultMenu);
  const [currentObject, setcurrentObject] = useState(linkedCombo);
  const [showAlert, setShowAlert] = useState(false);
  const [productsForCombo, setProductsForCombo] = useState([]);
  const [currentTab, setCurrentTab] = useState(1);
  const getName = (name_en, name_ar) => {
    if (dir == 'ltr')
        return name_en;
    else
        return name_ar
  }
  
  useEffect(() => {
    const fetchData = async () => {
        const response = await axios.get('/productList');
        const pps = response.data.map(e => { return { label: getName(e.name_en, e.name_ar), value: e.id } })
        setProductsForCombo(pps);
    }
    fetchData().catch(console.error);
  }, []);

  const saveChanges = async () => {
    try {
      setSubmitdisableButton(true);
      let r = { ...currentObject };
      // r["cards"] = r["cards"].map(x=> { return {payment_card_id : x.value} });
      // r["diningTypes"] = r["diningTypes"].map(x=> { return {dining_type_id : x.value} });
      const response = await axios.post('/linkedCombo', r);
      if (response.data.message == "Done") {
        window.location.href = '/linkedCombo';
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
    window.location.href = "/linkedCombo";
  }

  const onBasicChange = (key, value) => {
    currentObject[key] = value;
    setcurrentObject({...currentObject});
  }

  const onComboChange = (key, value) => {
    currentObject[key] = value;
    setcurrentObject({ ...currentObject });
    return {
      message: "Done"
    }
  }

  return (
    <div>
      <SweetAlert2 />
      <div class="container">
        <div class="row">
          <div class="col-6">
            <div class="d-flex align-items-center gap-2 gap-lg-3">
              <h1>{`${translations.Add} ${translations.linkedCombo}`}</h1>

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
                  <LinkedComboBasicInfo
                    translations={translations}
                    currentObject={currentObject}
                    onBasicChange = {onBasicChange}
                    dir ={dir}/>
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
                  <LinkedComboGroup
                    translations={translations}
                    dir ={dir}
                    currentObject={currentObject}
                    onComboChange = {onComboChange}
                    products={productsForCombo}
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
};

export default LinkedComboDetail;
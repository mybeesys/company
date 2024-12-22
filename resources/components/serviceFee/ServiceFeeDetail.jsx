import React, { useEffect, useState } from 'react';
import SweetAlert2 from 'react-sweetalert2';
import ServiceFeeBasicInfo from './ServiceFeeBasicInfo';
import ServiceFeeAutoApply from './ServiceFeeAutoApply';
import axios from 'axios';

const ServiceFeeDetail = ({dir, translations}) => {
  const rootElement = document.getElementById('root');
  let serviceFee = JSON.parse(rootElement.getAttribute('serviceFee'));
  const [defaultMenu, setdefaultMenu] = useState([
    { key: 'basicInfo', visible: true },
    { key: 'servic_fee_auto_apply', visible: false }
  ]);
  const [disableSubmitButton, setSubmitdisableButton] = useState(false);
  const [menu, setMenu] = useState(defaultMenu);
  const [currentObject, setcurrentObject] = useState(serviceFee);
  const [showAlert, setShowAlert] = useState(false);

  const [feeTypes, setFeeTypes] = useState([]);
  const [appTypes, setAppTypes] = useState([]);
  const [calcMethods, setCalcMethods] = useState([]);
  const [autoApplyTypes, setAutoApplyTypes] = useState([]);
  const [creditCardTypes, setCreditCardTypes] = useState([]);
  const [paymentCards, setPaymentCards] = useState([]);
  const [diningTypes, setDiningTypes] = useState([]);
  const [currentTab, setCurrentTab] = useState(1);

  const getName = (name_en, name_ar) => {
    if (dir == 'ltr')
        return name_en;
    else
        return name_ar
  }
  
  useEffect(() => {
    const fetchData = async () => {
      const res2 = await axios.get('/serviceFeeTypeValues');
      const lFeeTypes = res2.data.map(e => { return { name: translations[`service_fee_type_${e.name}`], value: e.value } });
      const res3 = await axios.get('/serviceFeeAppTypeValues');
      const lAppTypes = res3.data.map(e => { return { name: translations[`service_fee_app_type_${e.name}`], value: e.value } }); 
      const res4 = await axios.get('/serviceFeeCalcMetheodValues');
      const lCalcMethods = res4.data.map(e => { return { name: translations[`service_fee_calc_method_${e.name}`], value: e.value } });
      const res5 = await axios.get('/serviceFeeAutoApplyValues');
      const lAutoApplyTypes = res5.data.map(e => { return { name: translations[e.name], value: e.value } });
      const res6 = await axios.get('/creditCardTypeValues');
      const lCreditCardTypes = res6.data.map(e => { return { name: translations[e.name], value: e.value } });
      const res7 = await axios.get('/paymentCards');
      const lPaymentCards = res7.data.map(e => { return { label: getName(e.name_en, e.name_ar), value: e.id } })
      const res8 = await axios.get('/diningTypes');
      const lDiningTypes = res8.data.map(e => { return { label: getName(e.name_en, e.name_ar), value: e.id } })
      setAutoApplyTypes(lAutoApplyTypes);
      setCreditCardTypes(lCreditCardTypes);
      setPaymentCards(lPaymentCards);
      setDiningTypes(lDiningTypes);
      setFeeTypes(lFeeTypes);
      setAppTypes(lAppTypes);
      setCalcMethods(lCalcMethods);
    }
    fetchData().catch(console.error);
  }, []);

  const saveChanges = async () => {
    try {
      setSubmitdisableButton(true);
      let r = { ...currentObject };
      const response = await axios.post('/serviceFee', r);
      if (response.data.message == "Done") {
        window.location.href = '/serviceFee';
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
    window.location.href = "/serviceFee";
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

  const toSelectValues = (values, options, key) => {
      return values.map(x=> { return {
        label: !!options.filter(y=> y.value == x[key]).length? options.filter(y=> y.value == x[key])[0].label : null,
        value: x[key]
      }});
  }

  return (
    <div>
      <SweetAlert2 />
      <div class="container">
        <div class="row">
          <div class="col-6">
            <div class="d-flex align-items-center gap-2 gap-lg-3">
              <h1>{`${translations.Add} ${translations.serviceFee}`}</h1>

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
                  <ServiceFeeBasicInfo
                    translations={translations}
                    currentObject={currentObject}
                    onBasicChange = {onBasicChange}
                    feeTypes={feeTypes}
                    appTypes={appTypes}
                    calcMethods={calcMethods}
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
                <div id="servic_fee_auto_apply" class="card-body p-0 tab-pane fade show active" role="tabpanel"
                  aria-labelledby="servic_fee_auto_apply_tab">
                  <ServiceFeeAutoApply
                  translations={translations}
                  currentObject={currentObject}
                  serviceFeeCards={toSelectValues(currentObject["cards"], paymentCards, 'payment_card_id')}
                  serviceFeediningTypes={toSelectValues(currentObject["diningTypes"], diningTypes, 'dining_type_id')}
                  onBasicChange = {onBasicChange}
                  autoApplyTypes = {autoApplyTypes}
                  diningTypes={diningTypes}
                  creditCardTypes={creditCardTypes}
                  paymentCards={paymentCards}
                  dir={dir}
                  />
                   </div>
              </div>

              <div class="tab-content ">
                <div id="products" class="card-body p-0 tab-pane fade show" role="tabpanel"
                  aria-labelledby="products_tab">
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

export default ServiceFeeDetail;
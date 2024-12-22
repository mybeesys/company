import React, { useEffect, useState } from 'react';
import SweetAlert2 from 'react-sweetalert2';
import axios from 'axios';
import DiscountBasicInfo from './DiscountBasicInfo';
import DiscountQualification from './DiscountQualification';
import { defaultMenuTime } from '../comp/DefaultTimesSlots';
import TimeTable from '../comp/TimeTable';

const DiscountDetail = ({dir, translations}) => {
  const rootElement = document.getElementById('root');
  let discount = JSON.parse(rootElement.getAttribute('discount'));
  const [defaultMenu, setdefaultMenu] = useState([
    { key: 'basicInfo', visible: true },
    { key: 'discount_qualification', visible: false },
    { key: 'times', visible: false }
  ]);
  const [disableSubmitButton, setSubmitdisableButton] = useState(false);
  const [menu, setMenu] = useState(defaultMenu);
  const [currentObject, setcurrentObject] = useState(discount);
  const [showAlert, setShowAlert] = useState(false);

  const [discountTypes, setDiscountTypes] = useState([]);
  const [discountFunctions, setDiscountFunctions] = useState([]);
  const [discountQualifications, setDiscountQualifications] = useState([]);
  const [discountQualificationTypes, setDiscountQualificationTypes] = useState([]);
  const [selectedItemKeys, setSelectedItemKeys] = useState(!!discount.items ? discount.items : []);
  const [dates, setDates] = useState(!!discount.dates ? discount.dates[0] : defaultMenuTime[0]);
  const [currentTab, setCurrentTab] = useState(1);
  const getName = (name_en, name_ar) => {
    if (dir == 'ltr')
        return name_en;
    else
        return name_ar
  }
  
  useEffect(() => {
    const fetchData = async () => {
      const res = await axios.get('/discountLovs');
      setDiscountTypes(res.data["discountType"].map(e => { return { name: translations[`discount_${e.name}`], value: e.value } }));
      setDiscountFunctions(res.data["discountFunction"].map(e => { return { name: translations[`discount_${e.name}`], value: e.value } }));
      setDiscountQualifications(res.data["discountQualification"].map(e => { return { name: translations[`discount_qualification_${e.name}`], value: e.value } }));
      setDiscountQualificationTypes(res.data["discountQualificationType"].map(e => { return { name: translations[`discount_qualification_type_${e.name}`], value: e.value } }))
    }
    fetchData().catch(console.error);
  }, []);

  const saveChanges = async () => {
    try {
      setSubmitdisableButton(true);
      let r = { ...currentObject };
      r["items"] = [...selectedItemKeys];
      r["dates"] = {...dates};
      const response = await axios.post('/discount', r);
      if (response.data.message == "Done") {
        window.location.href = '/discount';
      }
      else
      {
        setShowAlert(true);
        Swal.fire({
            show: showAlert,
            title: 'Error',
            text: translations[response.data.message] ,
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
    window.location.href = "/discount";
  }

  const onBasicChange = (key, value) => {
    currentObject[key] = value;
    setcurrentObject({...currentObject});
  }

  const onItemSelectionChange = (keys) =>{
    let items = keys.map((x) => { return {item_id : x}});
    setSelectedItemKeys(items);
  }

  const onDateTimeChange = (type, key, value, day_no) => {
    if (type == 'T') {
      let index = dates.times.findIndex(x => x.day_no == day_no);
      dates.times[index][key] = !!value ? value.toLocaleTimeString('en-US', { hour12: false }) : null;
    }
    else if(type == 'D'){
      dates[key] = !!value ? `${value.getFullYear()}-${(value.getMonth()+1).toString().padStart(2, '0')}-${value.getDate().toString().padStart(2, '0')}` : null;
    }
    else if(type == 'C'){
      let index = dates.times.findIndex(x => x.day_no == day_no);
      dates.times[index][key] = value;
    }
    setDates({...dates})
  }

  return (
    <div>
      <SweetAlert2 />
      <div class="container">
        <div class="row">
          <div class="col-6">
            <div class="d-flex align-items-center gap-2 gap-lg-3">
              <h1>{`${translations.Add} ${translations.discount}`}</h1>

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
                  <DiscountBasicInfo
                    translations={translations}
                    currentObject={currentObject}
                    onBasicChange = {onBasicChange}
                    discountFunctions={discountFunctions}
                    discountTypes={discountTypes}
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
                  <DiscountQualification
                  translations={translations}
                  currentObject={currentObject}
                  discountQualifications={discountQualifications}
                  discountQualificationTypes={discountQualificationTypes}
                  onBasicChange={onBasicChange}
                  onItemSelectionChange = {onItemSelectionChange}
                  selectedItemKeys={selectedItemKeys}
                  dir={dir}/>
                   </div>
              </div>

              <div class="tab-content ">
                <div id="times" class="card-body p-0 tab-pane fade show" role="tabpanel"
                  aria-labelledby="times_tab">
                  <TimeTable
                    translations={translations}
                    dates={dates}
                    onDateTimeChange = {onDateTimeChange}
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

export default DiscountDetail;
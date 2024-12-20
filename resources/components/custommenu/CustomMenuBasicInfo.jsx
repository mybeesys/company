import { useEffect, useState } from "react";



const CustomMenuBasicInfo = ({ translations, currentObject, onBasicChange, dir }) => {

    const [modesStations, setModesStations] = useState({modes:[], stations:[]});
    const [applicationTypeValues, setApplicationTypeValues] = useState([]);

    const getName = (name_en, name_ar) =>{
        if(dir == 'ltr')
            return name_en;
        else
            return name_ar
    }

    useEffect(() => {
        const fetchData = async () => {
        const res2 = await axios.get('/application-type-values');
        const appType = res2.data;
        const res3 = await axios.get('/mode-values');
        const res4 = await axios.get('/stations');
        const ms = {
              modes: res3.data.map(e=> {return  {name:e.name, value:e.value}}), 
              stations: res4.data.map(e=> {
                return  {name: getName(e.name_en, e.name_ar), value:e.id}
            })
        }
        setApplicationTypeValues(appType);
        setModesStations(ms);
      }
      fetchData().catch(console.error);
    }, []);
    

    const getStationsModesSource = () =>{
        return !!currentObject.application_type && currentObject.application_type == "3" ?
                    modesStations.stations : modesStations.modes;
    }

    const getStationsModesValues = (name) =>{
        return !!currentObject.application_type && currentObject.application_type == "3" ?
                  name : translations[name];
    }

    const getStationsModesKey = (name, name_en, name_ar) =>{
        return !!currentObject.application_type && currentObject.application_type == "3" ?
                    'station_id' : 'mode';
    }

    return (
        <div class="card-body" dir={dir}>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="name_ar" class="col-form-label">{translations.name_ar}</label>
                        <input type="text" class="form-control form-control-solid custom-height" id="name_ar" value={currentObject.name_ar}
                            onChange={(e) => onBasicChange('name_ar', e.target.value)} required></input>
                    </div>
                    <div class="col-6">
                        <label for="name_en" class="col-form-label">{translations.name_en}</label>
                        <input type="text" class="form-control form-control-solid custom-height" id="name_en" value={currentObject.name_en}
                            onChange={(e) => onBasicChange('name_en', e.target.value)} required></input>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                <div class="col-6">
                        <label for="name_ar" class="col-form-label">{translations.application_type}</label>
                        <select class="form-control form-control-solid custom-height selectpicker" value={currentObject.application_type} 
                            onChange={(e) => onBasicChange('application_type', e.target.value)} >
                            {applicationTypeValues.map((applicationType) => (
                                <option key={applicationType.value} value={applicationType.value}>
                                    {translations[applicationType.name]}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div class="col-6">
                        <label for="name_ar" class="col-form-label">{translations.mode_station_name}</label>
                        <select class="form-control form-control-solid custom-height selectpicker" value={currentObject[getStationsModesKey()]} 
                            onChange={(e) => onBasicChange(getStationsModesKey(), e.target.value)} >
                            {getStationsModesSource().map((option) => (
                                <option key={option.value} value={option.value}>
                                    {getStationsModesValues(option.name)}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center pt-3">
                <label class="fs-6 fw-semibold mb-2 me-3 "
                    style={{width: "150px"}}>{translations.active}</label>
                <div class="form-check">
                    <input type="checkbox" style={{border: "1px solid #9f9f9f"}}
                        class="form-check-input my-2"
                        id="active" checked={!!currentObject.active == 1 ? true : false}
                        onChange={(e) => onBasicChange('active', e.target.checked ?  1 : 0)}/>
                </div>
            </div>
        </div>
    );
}
export default CustomMenuBasicInfo;
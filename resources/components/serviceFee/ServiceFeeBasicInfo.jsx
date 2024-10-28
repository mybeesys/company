import { useEffect, useState } from "react";



const ServiceFeeBasicInfo = ({ translations, feeTypes, calcMethods, appTypes,   
                            currentObject, onBasicChange, dir }) => {

    

    const getName = (name_en, name_ar) =>{
        if(dir == 'ltr')
            return name_en;
        else
            return name_ar
    }

    useEffect(() => {
        const fetchData = async () => {
            
          }
          fetchData().catch(console.error);
        }, []);

    return (
        <div class="card-body" dir={dir}>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="name_ar" class="col-form-label">{translations.name_ar}</label>
                        <input type="text" class="form-control" id="name_ar" value={currentObject.name_ar}
                            onChange={(e) => onBasicChange('name_ar', e.target.value)} required></input>
                    </div>
                    <div class="col-6">
                        <label for="name_en" class="col-form-label">{translations.name_en}</label>
                        <input type="text" class="form-control" id="name_en" value={currentObject.name_en}
                            onChange={(e) => onBasicChange('name_en', e.target.value)} required></input>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="name_ar" class="col-form-label">{translations.type}</label>
                        <select class="form-control selectpicker" value={currentObject.service_fee_type} 
                            onChange={(e) => onBasicChange('service_fee_type', e.target.value)} >
                            {feeTypes.map((feeType) => (
                                <option key={feeType.value} value={feeType.value}>
                                    {feeType.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div class="col-6">
                        <label for="amount" class="col-form-label">{translations.amount}</label>
                        <input type="text" class="form-control" id="amount" value={currentObject.amount}
                            onChange={(e) => onBasicChange('amount', e.target.value)} required></input>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="name_ar" class="col-form-label">{translations.application_type}</label>
                        <select class="form-control selectpicker" value={currentObject.application_type} 
                            onChange={(e) => onBasicChange('application_type', e.target.value)} >
                            {appTypes.map((appType) => (
                                <option key={appType.value} value={appType.value}>
                                    {appType.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div class="col-6">
                        <label for="name_ar" class="col-form-label">{translations.calculation_method}</label>
                        <select class="form-control selectpicker" value={currentObject.calculation_method} 
                            onChange={(e) => onBasicChange('calculation_method', e.target.value)} >
                            {calcMethods.map((calcMethod) => (
                                <option key={calcMethod.value} value={calcMethod.value}>
                                    {calcMethod.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group" style={{ paddingtop: '5px' }}>
                <div class="row pt-3">
                    <div class="col-6">
                        <label class="col-form-label col-4" >
                            <div class="row">
                                <div class="col-2">
                                    <input type="checkbox" class="form-check-input" id="taxable" checked={!!currentObject.taxable == 1 ? true : false}
                                        onChange={(e) => onBasicChange('taxable', e.target.checked ?  1 : 0)}
                                    />
                                </div>
                                <div class=" container col-10">{translations.taxable}</div>
                            </div>
                        </label>
                    </div>
                    <div class="col-6">
                    <label class="col-form-label col-4" >
                        <div class="row">
                            <div class="col-2">
                                <input type="checkbox" class="form-check-input" id="active" checked={!!currentObject.active == 1 ? true : false}
                                    onChange={(e) => onBasicChange('active', e.target.checked ?  1 : 0)}
                                />
                            </div>
                            <div class=" container col-10">{translations.active}</div>
                        </div>
                    </label>
                </div>
                </div>
            </div>
        </div>
    );
}
export default ServiceFeeBasicInfo;
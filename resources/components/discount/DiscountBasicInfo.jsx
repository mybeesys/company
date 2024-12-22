import { useEffect, useState } from "react";



const DiscountBasicInfo = ({ translations, discountTypes, discountFunctions,
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
                        <label for="function_id" class="col-form-label">{translations.discountFunction}</label>
                        <select id="function_id" class="form-control form-control-solid custom-height selectpicker" value={currentObject.function_id} 
                            onChange={(e) => onBasicChange('function_id', e.target.value)} >
                            {discountFunctions.map((discountFunction) => (
                                <option key={discountFunction.value} value={discountFunction.value}>
                                    {discountFunction.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div class="col-6">
                        <label for="discount_type" class="col-form-label">{translations.type}</label>
                        <select id="discount_type" class="form-control form-control-solid custom-height selectpicker" value={currentObject.discount_type} 
                            onChange={(e) => onBasicChange('discount_type', e.target.value)} >
                            {discountTypes.map((discountType) => (
                                <option key={discountType.value} value={discountType.value}>
                                    {discountType.name}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="amount" class="col-form-label">{translations.amount}</label>
                        <input type="number" class="form-control form-control-solid custom-height" id="amount" value={currentObject.amount}
                            onChange={(e) => onBasicChange('amount', e.target.value)} required></input>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center pt-3">
                <label class="fs-6 fw-semibold mb-2 me-3 "
                    style={{width: "150px"}}>{translations.autoApply}</label>
                <div class="form-check">
                    <input type="checkbox" style={{border: "1px solid #9f9f9f"}}
                        class="form-check-input my-2"
                        id="auto_apply" checked={!!currentObject.auto_apply == 1 ? true : false}
                        onChange={(e) => onBasicChange('auto_apply', e.target.checked ?  1 : 0)}/>
                </div>
            </div>
            <div class="d-flex align-items-center pt-3">
                <label class="fs-6 fw-semibold mb-2 me-3 "
                    style={{width: "150px"}}>{translations.itemLevel}</label>
                <div class="form-check">
                    <input type="checkbox" style={{border: "1px solid #9f9f9f"}}
                        class="form-check-input my-2"
                        id="item_level" checked={!!currentObject.item_level == 1 ? true : false}
                        onChange={(e) => onBasicChange('item_level', e.target.checked ?  1 : 0)}/>
                </div>
            </div>
        </div>
    );
}
export default DiscountBasicInfo;
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
                        <label for="function_id" class="col-form-label">{translations.discountFunction}</label>
                        <select id="function_id" class="form-control selectpicker" value={currentObject.function_id} 
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
                        <select id="discount_type" class="form-control selectpicker" value={currentObject.discount_type} 
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
                        <input type="number" class="form-control" id="amount" value={currentObject.amount}
                            onChange={(e) => onBasicChange('amount', e.target.value)} required></input>
                    </div>
                </div>
            </div>
            <div class="form-group" style={{ paddingtop: '5px' }}>
                <div class="row pt-3">
                    <div class="col-6">
                        <label class="col-form-label col-4" >
                            <div class="row">
                                <div class="col-2">
                                    <input type="checkbox" class="form-check-input" id="auto_apply" checked={!!currentObject.auto_apply == 1 ? true : false}
                                        onChange={(e) => onBasicChange('auto_apply', e.target.checked ?  1 : 0)}
                                    />
                                </div>
                                <div class=" container col-10">{translations.autoApply}</div>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="row pt-3">
                    <div class="col-6">
                        <label class="col-form-label col-4" >
                            <div class="row">
                                <div class="col-2">
                                    <input type="checkbox" class="form-check-input" id="item_level" checked={!!currentObject.item_level == 1 ? true : false}
                                        onChange={(e) => onBasicChange('item_level', e.target.checked ?  1 : 0)}
                                    />
                                </div>
                                <div class=" container col-10">{translations.itemLevel}</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    );
}
export default DiscountBasicInfo;
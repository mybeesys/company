import { useEffect, useState } from "react";
import SelectTree from "../comp/SelectTree";



const DiscountQualification = ({ translations, discountQualifications, discountQualificationTypes,
                            currentObject, selectedItemKeys, onBasicChange, onItemSelectionChange, dir }) => {

    return (
        <div class="card-body" dir={dir}>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="qualification" class="col-form-label">{translations.discount_qualification}</label>
                        <select id="qualification" class="form-control selectpicker" value={currentObject.qualification} 
                            onChange={(e) => {
                                
                                onBasicChange('qualification', e.target.value);
                                onBasicChange('qualification_type', null);
                                onItemSelectionChange([]);
                                }
                            } >
                            {discountQualifications.map((discountQualification) => (
                                <option key={discountQualification.value} value={discountQualification.value}>
                                    {discountQualification.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    {!!!currentObject.qualification ? <></> 
                    :
                    currentObject.qualification== "1" ? 
                    <div class="col-6">
                        <label for="qualification_type" class="col-form-label">{translations.discount_qualification_type}</label>
                        <select id="qualification_type" class="form-control selectpicker" value={currentObject.qualification_type} 
                            onChange={(e) => {
                                onBasicChange('qualification_type', e.target.value)
                                onItemSelectionChange([]);
                                }
                            } >
                            <option value="-1" disabled selected={!!!currentObject.qualification_type}></option>    
                            {discountQualificationTypes.map((discountQualificationType) => (
                                <option key={discountQualificationType.value} value={discountQualificationType.value}>
                                    {discountQualificationType.name}
                                </option>
                            ))}
                        </select>
                    </div> : <></>}
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-12">
                        {currentObject.qualification_type == "2" ?
                        <SelectTree
                            translations={translations}
                            title={translations.modifiers}
                            selectedItems={selectedItemKeys}
                            itemType="modifier"
                            itemKey="item_id"
                            itemsUrl="/modifierClassList"
                            onItemSelectionChange={onItemSelectionChange}
                        /> 
                        : currentObject.qualification_type == "4" ? 
                        <SelectTree
                            translations={translations}
                            title={translations.products}
                            selectedItems={selectedItemKeys}
                            itemType="product"
                            itemKey="item_id"
                            itemsUrl="/categories"
                            onItemSelectionChange={onItemSelectionChange}
                        /> 
                        : currentObject.qualification_type == "3" ? 
                        <SelectTree
                            translations={translations}
                            title={translations.modifierClasses}
                            selectedItems={selectedItemKeys}
                            itemType="modifierClass"
                            itemKey="item_id"
                            itemsUrl="/modifierClasses"
                            onItemSelectionChange={onItemSelectionChange}
                        /> :
                        <></>}
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <label for="required_product_count" class="col-form-label">{translations.discount_product_count}</label>
                        <input type="text" class="form-control" id="required_product_count" value={currentObject.required_product_count}
                            onChange={(e) => onBasicChange('required_product_count', e.target.value)}></input>
                    </div>
                    <div class="col-6">
                        <label for="minimum_amount" class="col-form-label">{translations.discount_minimum_amount}</label>
                        <input type="text" class="form-control" id="minimum_amount" value={currentObject.minimum_amount}
                            onChange={(e) => onBasicChange('minimum_amount', e.target.value)}></input>
                    </div>
                </div>
            </div>
        </div>
    );
}
export default DiscountQualification;
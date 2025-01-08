import { useEffect, useRef, useState } from "react";
import TreeTableEditorLocal from "../comp/TreeTableEditorLocal";
import Select from "react-select";
import makeAnimated from 'react-select/animated';
import { getRowName } from '../lang/Utils';

const animatedComponents = makeAnimated();
const ModifierPriceTier = ({ translations, dir, currentObject, onBasicChange, lov }) => {
    const timeoutRef = useRef(null);

    useEffect(() => {
        if (lov.taxes.length > 0) {
            if (!!!currentObject.tax_id) {                
                const taxIndex = lov.taxes.findIndex(x=>!!x.default && x.default ==1);
                currentObject['tax'] = taxIndex == -1 ? lov.taxes[0] : lov.taxes[taxIndex];
                currentObject['tax_id'] = taxIndex == -1 ? lov.taxes[0].value : lov.taxes[taxIndex].value;
            }
            else {
                currentObject['tax'] = lov.taxes.find(x => x.value == currentObject.tax_id);
            }
            onBasicChange('tax_id', currentObject['tax_id']);
            onBasicChange('tax', currentObject['tax']);
        }
      }, [lov]);


    const handleDelete = (row) =>{
        let index = currentObject.price_tiers.findIndex(x=>x.id == row.id);
        currentObject.price_tiers.splice(index, 1); // Removes 1 element at index 2
        onBasicChange("price_tiers", currentObject.price_tiers);
        return { message : 'Done'};
    }

    const onPriceChange = (key, value, option) =>{
        const tax_id = key == 'tax_id' ? value : currentObject.tax_id;
        const price = key == 'price' ? value : currentObject.price;
        onBasicChange(key, value);
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
            console.log("Previous timeout canceled.");
        }
        timeoutRef.current = setTimeout(() =>axios.get(`${window.location.origin}/priceWithTax?tax_id=${!!tax_id? tax_id : ''}&price=${!!price ? price : ''}`)
            .then(response => {
                    currentObject.price_with_tax = response.data.price_with_tax;
                    onBasicChange('price_with_tax', currentObject.price_with_tax);
                    updatePriceWithtax(!!tax_id? tax_id : '');
            }),2000);
        if (key == "tax_id") {
           onBasicChange('tax', option);
        }
    }

    const onPriceWithTaxChange = (key, value) =>{
        const tax_id = key == 'tax_id' ? value : currentObject.tax_id;
        onBasicChange(key, value);
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }
        timeoutRef.current = setTimeout(() =>axios.get(`${window.location.origin}/getPriceFromPriceWithTax?tax_id=${!!tax_id? tax_id : ''}&price=${!!value ? value : ''}`)
            .then(response => {
                    if(response.data.new_price == -1)
                        return;
                    onBasicChange('price', response.data.new_price);
            }),2000);
    }

    const updatePriceWithtax = (tax_id) => {
        currentObject.price_tiers.forEach(price_tier => {
            axios.get(`${window.location.origin}/priceWithTax?tax_id=${tax_id}&price=${!!price_tier.price ? price_tier.price : ''}`)
            .then(response => {
                    price_tier.price_with_tax = response.data.price_with_tax;
                    onBasicChange('price_tiers', currentObject.price_tiers);
            });
        });
    }

    return (
        <>
        <div class="form-group">
            <div class="row">
                <div class="col-6">
                    <label for="cost" class="col-form-label">{translations.cost}</label>
                    <input type="number" min="0" step=".01" class="form-control form-control-solid custom-height" id="cost" value={!!currentObject.cost ? currentObject.cost : ''}
                        onChange={(e) => onBasicChange('cost', e.target.value)}
                        required></input>
                </div>
                <div class="col-6">
                    <label for="price" class="col-form-label">{translations.price}</label>
                    <input type="number" min="0" step=".01" class="form-control form-control-solid custom-height" id="price" value={!!currentObject.price ? currentObject.price : ''}
                        onChange={(e) => onPriceChange('price', e.target.value)}
                        required></input>
                </div>
            </div>
        </div>
        <div class="form-group">
          <div class="row">
            <div class="col-6">
              <label for="name_ar" class="col-form-label">{translations.taxes}</label>
              <Select
                    id="tax_id"
                    isMulti={false}
                    options={lov.taxes}
                    closeMenuOnSelect={true}
                    components={animatedComponents}
                    value={currentObject.tax}
                    onChange={val => onPriceChange('tax_id', val.value, val)}
                    menuPortalTarget={document.body} 
                    styles={{ menuPortal: base => ({ ...base, zIndex: 100000 }) }}
                />
            </div>
            <div class="col-6">
                <label for="price" class="col-form-label">{translations.priceWithTax}</label>
                <input type="number" min="0" step=".01" class="form-control form-control-solid custom-height" 
                id="price_with_tax" value={!!currentObject.price_with_tax ? currentObject.price_with_tax : ''}
                    onChange={(e) => onPriceWithTaxChange('price_with_tax', e.target.value)}></input>
            </div>
          </div>
        </div>

        <TreeTableEditorLocal
            translations={translations}
            dir={dir}
            header={true}
            addNewRow={true}
            type={"priceTier"}
            title={translations.priceTiers}
            currentNodes={[...currentObject.price_tiers]}
            defaultValue={{ }}
            cols={[
                {
                    key: "price_tier", title: "priceTier", autoFocus: true, searchUrl: "searchPriceTiers", type: "AsyncDropDown", width: '25%',
                    editable: true,
                    onChangeValue : (nodes, key, val, rowKey, postExecute) => {
                        if (timeoutRef.current) {
                            clearTimeout(timeoutRef.current);
                            console.log("Previous timeout canceled.");
                        }
                        timeoutRef.current = setTimeout(() =>axios.get(`${window.location.origin}/priceWithTax?tax_id=${!!currentObject.tax_id? currentObject.tax_id : ''}&price=
                            ${!!nodes[rowKey].data.price ? nodes[rowKey].data.price : ''}`)
                        .then(response => {
                            nodes[rowKey].data.price_with_tax = response.data.price_with_tax;
                            postExecute(nodes);
                    }),2000);
                        
                    }
                },
                {key: "price", autoFocus: true,  type: "Decimal", width: '20%', editable: true, required: true,
                    onChangeValue : (nodes, key, val, rowKey, postExecute) => {
                        if (timeoutRef.current) {
                            clearTimeout(timeoutRef.current);
                            console.log("Previous timeout canceled.");
                        }
                        timeoutRef.current = setTimeout(() => axios.get(`${window.location.origin}/priceWithTax?tax_id=${!!currentObject.tax_id? currentObject.tax_id : ''}&price=
                                            ${!!nodes[rowKey].data.price ? nodes[rowKey].data.price : ''}`)
                            .then(response => {
                                nodes[rowKey].data.price_with_tax = response.data.price_with_tax;
                                postExecute(nodes);
                        }),2000);
                    }
                },
                {key: "price_with_tax", title: "priceWithTax", autoFocus: true, type: "Decimal", editable: true,
                    width: '20%',
                    onChangeValue : (nodes, key, val, rowKey, postExecute) => {
                       if (timeoutRef.current) {
                           clearTimeout(timeoutRef.current);
                           console.log("Previous timeout canceled.");
                       }
                       timeoutRef.current = setTimeout(() => axios.get(`${window.location.origin}/getPriceFromPriceWithTax?tax_id=${!!currentObject.tax_id? currentObject.tax_id : ''}&price=
                                           ${!!nodes[rowKey].data.price_with_tax ? nodes[rowKey].data.price_with_tax : ''}`)
                           .then(response => {
                               nodes[rowKey].data.price = response.data.new_price;
                               postExecute(nodes, true);
                       }),2000);
                   }}
            ]}
            actions={[]}
            onUpdate={(nodes) => onBasicChange("price_tiers", nodes)}
            onDelete={handleDelete} />
        </>
    )
}

export default ModifierPriceTier;
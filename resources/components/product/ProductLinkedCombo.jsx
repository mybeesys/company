import { useState } from "react";
import TreeTableComponentLocal from "../comp/TreeTableComponentLocal";
import ProductComboUpchargeModal from "./ProductComboUpchargeModal";
import Select from "react-select";
import makeAnimated from 'react-select/animated';

const animatedComponents = makeAnimated();
const ProductLinkedCombo = ({ translations, dir, pormpts, product, linkedCombos, products, onComboChange }) => {

    const [showUpchargeDialog, setShowUpchargeDialog] = useState(false);
    const [currentCombo, setCurrentCombo] = useState({ combo_id: -1, products: [] });

    const showUpcharge = (combo, linkedComboId) => {
        let dataProducts = products.filter(x => combo.items.map(x=>x.item_id).includes(x.value))
        dataProducts = dataProducts.map(x => { return { id: x.value, value: x.value, label: x.label } });
        for (let index = 0; index < dataProducts.length; index++) {
            let linkedcomboIndex = product.linkedCombos.findIndex(x => x.linked_combo_id == linkedComboId);
            const prod = dataProducts[index];
            if (!!product.linkedCombos[linkedcomboIndex].upchargePrices &&
                product.linkedCombos[linkedcomboIndex].upchargePrices.filter(x => x.product_id == prod.value && x.product_combo_id == linkedComboId && x.combo_id ==  combo.id).length > 0) {
                const p = product.linkedCombos[linkedcomboIndex].upchargePrices.find(x => x.product_id == prod.value && x.product_combo_id == linkedComboId && x.combo_id ==  combo.id);
                dataProducts[index].price = p.price;
            }
        }
        setCurrentCombo({ id: combo.id, linked_combo_id: linkedComboId, products: dataProducts });
        setShowUpchargeDialog(true)
    }

    const linkedComboDetailCell = (data, key, editMode, editable) => {
        let linkedcombo = linkedCombos.find(x => x.value == data.linked_combo_id);
        return !!editMode? <></>:<div class="product__item__text">
            <ul>
                {!!linkedcombo ? linkedcombo.combos.map((v) => (
                    <li style={{cursor: "pointer"}} onClick={() => showUpcharge(v, linkedcombo.value)}> {v.name_en}</li>
                )) : <></>}
            </ul>
        </div >
    }

    const onClose = (data, products) => {
        let linkedcomboIndex = product.linkedCombos.findIndex(x => x.linked_combo_id == data.linked_combo_id);
        let linkedcombo = linkedCombos.find(x => x.value == data.linked_combo_id);
        let comboUpcharge = [];
        if(!!!product.linkedCombos[linkedcomboIndex].upchargePrices){
            product.linkedCombos[linkedcomboIndex].upchargePrices = [];
        }
        for (let index = 0; index < linkedcombo.combos.length; index++) {
            const combo = linkedcombo.combos[index];   
            if (combo.id == data.id) {
                for (let i = product.linkedCombos[linkedcomboIndex].upchargePrices.length - 1; i >= 0; i--) {
                    if (product.linkedCombos[linkedcomboIndex].upchargePrices[i].combo_id === combo.id) {
                        product.linkedCombos[linkedcomboIndex].upchargePrices.splice(i, 1);
                    }
                }
                
                for (let index1 = 0; index1 < linkedcombo.combos[index].items.length; index1++) {
                    let prod = combo.items[index1].item_id;
                    
                    if (products.filter(x => x.value == prod && !!x.price).length > 0) {
                        const p = products.find(x => x.value == prod && !!x.price);
                        product.linkedCombos[linkedcomboIndex].upchargePrices.push({ product_id: prod, price: p.price, combo_id: combo.id, product_combo_id:data.linked_combo_id });
                    }
               }
                continue;
            }
        }
        
        onComboChange("linkedCombos", product.linkedCombos);
        setShowUpchargeDialog(false);
    }

    return (
        <div class="card-body" dir={dir}>
            <div class="form-group">
                <div class="row pt-3">
                    <div class="d-flex  align-items-center ">
                        <label class="fs-6 fw-semibold mb-2 me-3 "
                            style={{width: "150px"}}>{translations.linkedCombo}</label>
                        <div class="form-check">
                            <input type="checkbox" style={{border: "1px solid #9f9f9f"}}
                                class="form-check-input my-2"
                                id="active" checked={!!product.linked_combo == 0 ? false : true}
                                onChange={(e) => onComboChange('linked_combo', e.target.checked ? 1 : 0)}/>
                        </div>
                    </div>
                </div>
                {!!product.linked_combo ?
                    <>
                        <div class="row">
                            <div class="col-6">
                                <label for="promot_upsell" class="col-form-label">{translations.promptForUpsell}</label>
                                <Select
                                    id="promot_upsell"
                                    isMulti={false}
                                    options={pormpts}
                                    closeMenuOnSelect={true}
                                    components={animatedComponents}
                                    value={pormpts[!!product.promot_upsell? product.promot_upsell : 0]}
                                    onChange={(val) => onComboChange('promot_upsell', val.value, val)} 
                                    menuPortalTarget={document.body} 
                                    styles={{ menuPortal: base => ({ ...base, zIndex: 100000 }) }}
                                />
                            </div>
                        </div>
                        <div class="row pt-3">
                        <ProductComboUpchargeModal
                            visible={showUpchargeDialog}
                            onClose={onClose}
                            translations={translations}
                            combo={currentCombo}
                            dir={dir}
                        />
                        <TreeTableComponentLocal
                            translations={translations}
                            dir={dir}
                            header={true}
                            addNewRow={true}
                            type={"linkedCombo"}
                            title={translations.linkedCombos}
                            currentNodes={[...product.linkedCombos]}
                            defaultValue={{ linked_combo_id: linkedCombos.length > 0 ? linkedCombos[0].value : null }}
                            cols={[
                                { key: "linked_combo_id", autoFocus: false, options: linkedCombos, type: "DropDown", width: '30%', editable: true, required: true },
                                {
                                    key: "products",
                                    customCell: linkedComboDetailCell,
                                    autoFocus: false, options: [], type: "DropDown", width: '30%', editable: false
                                },
                            ]}
                            actions={[]}
                            onUpdate={(nodes) => onComboChange("linkedCombos", nodes)}
                            onDelete={null}
                        />
                        </div>
                    </>
                    :
                    <></>
                }
            </div>
        </div>
    )

}

export default ProductLinkedCombo;
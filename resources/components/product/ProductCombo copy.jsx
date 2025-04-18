import { useState } from "react";
import TreeTableComponentLocal from "../comp/TreeTableComponentLocal";
import ProductComboUpchargeModal from "./ProductComboUpchargeModal";


const ProductCombo = ({translations, dir, product, products, onComboChange}) => {

    const [showUpchargeDialog, setShowUpchargeDialog] = useState(false);
    const [currentCombo, setCurrentCombo] = useState({combo_id: -1, products : []});

    const onClose = (id, products) =>{
        let comboUpcharge = [];
        for (let index = 0; index < product.combos.length; index++) {
            const combo = product.combos[index];
            if(combo.id == id){
                for (let index1 = 0; index1 < product.combos[index].products.length; index1++) {
                    let prod = combo.products[index1];
                    if(products.filter(x => x.value == prod && !!x.price).length > 0)
                    {
                        const p = products.find(x => x.value == prod && !!x.price);
                        comboUpcharge.push( {product_id : prod, price: p.price});
                    }
                }
                product.combos[index].upchargePrices = comboUpcharge;
                continue;
            }
        }
        onComboChange("combos", product.combos);
        setShowUpchargeDialog(false);
    }

    return (
        <div class="card-body" dir={dir}>
            <div class="form-group">
                <div class="row pt-3">
                    <div class="d-flex  align-items-center ">
                        <label class="fs-6 fw-semibold mb-2 me-3 "
                            style={{ width: "150px" }}>{translations.groupCombo}</label>
                        <div class="form-check">
                            <input type="checkbox" style={{ border: "1px solid #9f9f9f" }}
                                class="form-check-input my-2"
                                id="active" checked={!!product.group_combo == 0 ? false : true}
                                onChange={(e) => onComboChange('group_combo', e.target.checked ? 1 : 0)} />
                        </div>
                    </div>
                </div>
                {!!product.group_combo ?
                <>
                <div class="row">
                    <div class="col-6">
                        <div class="d-flex  align-items-center ">
                            <label class="fs-6 fw-semibold mb-2 me-3 "
                                style={{ width: "150px" }}>{translations.setPriceInCombo}</label>
                            <div class="form-check">
                                <input type="checkbox" style={{ border: "1px solid #9f9f9f" }}
                                    class="form-check-input my-2"
                                    id="active" checked={!!product.set_price == 0 ? false : true}
                                    onChange={(e) => onComboChange('set_price', e.target.checked ? 1 : 0)} />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                    <div class="d-flex  align-items-center ">
                            <label class="fs-6 fw-semibold mb-2 me-3 "
                                style={{ width: "150px" }}>{translations.useUpCharge}</label>
                            <div class="form-check">
                                <input type="checkbox" style={{ border: "1px solid #9f9f9f" }}
                                    class="form-check-input my-2"
                                    id="active" checked={!!product.use_upcharge == 0 ? false : true}
                                    onChange={(e) => onComboChange('use_upcharge', e.target.checked ? 1 : 0)} />
                            </div>
                        </div>
                    </div>
                </div>
                <ProductComboUpchargeModal
                    visible={showUpchargeDialog}
                    onClose={onClose}
                    translations={translations}
                    combo = {currentCombo}
                    dir={dir}
                />
                <TreeTableComponentLocal
                    translations={translations}
                    dir={dir}
                    header={true}
                    addNewRow={true}
                    type= {"productCombo"}
                    title={translations.productCombos}
                    currentNodes={[...product.combos]}
                    defaultValue={{price : 0, combo_saving : 0, quantity : 1}}
                    cols={[
                        {key : "name_en", autoFocus: true, options: [], type :"Text", width:'16%', editable:true, required:true},
                        {key : "name_ar", autoFocus: false, options: [], type :"Text", width:'15%', editable:true, required:true},
                        {key : "products", autoFocus: false, options: products, type :"MultiDropDown", width:'30%', editable:true, required:true},
                        {key : !!product.set_price ? "price" : "combo_saving", autoFocus: false, options: [], type : !!product.set_price ? "Decimal" : "Check", width:'12%', editable:true, required:false},
                        {key : "quantity", autoFocus: false, options: [], type :"Number", width:'12%', editable:true, required:true}
                    ]}
                    actions = {!!product.use_upcharge ? [
                        {execute: (data)=>{
                            let dataProducts = products.filter(x => data.products.includes(x.value))
                            dataProducts = dataProducts.map(x=>{return {id:x.value, value:x.value, label : x.label}});
                            let combo = product.combos.find(x=>x.id == data.id);
                            for (let index = 0; index < dataProducts.length; index++) {
                                const prod = dataProducts[index];
                                if(!!combo.upchargePrices &&
                                    combo.upchargePrices.filter(x=>x.product_id == prod.value).length > 0)
                                {
                                    const p = combo.upchargePrices.find(x => x.product_id == prod.value);
                                    dataProducts[index].price = p.price;
                                }
                            }
                            setCurrentCombo({id: data.id, products: dataProducts});
                            setShowUpchargeDialog(true)
                        } , icon:'ki-pencil'}
                    ] : []}
                    onUpdate={(nodes)=> onComboChange("combos", nodes)}
                    onDelete={null}
                />
                </>
                :
                <></>}
            </div>
        </div>
    )
}

export default ProductCombo;
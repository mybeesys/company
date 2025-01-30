
import React, { useRef, useState } from "react";
import Barcode from "react-barcode";
import { useReactToPrint } from "react-to-print";
import EditRowCompnent from "../comp/EditRowCompnent";
import TreeTableEditorLocal from "../comp/TreeTableEditorLocal";
import { InputSwitch } from "primereact/inputswitch";
import Select from "react-select";
import makeAnimated from 'react-select/animated';
import { getRowName } from '../lang/Utils';

const animatedComponents = makeAnimated();


const ProductBarcode = ({ translations, dir }) => {
    const pricingTypes =[
        { label: translations['beforTax'], value: 0},
        { label: translations['afterTax'], value: 1}
    ]

    const [currentObject, setcurrentObject] = useState({pricingType: pricingTypes[0], products : []});
    const barcodeRef = useRef(null);

    

    const onChange = (key, value) => {
        let r = {...currentObject};
        r[key] = value;
        console.log(currentObject);
        setcurrentObject({...r});
        return {message:"Done"};
    }

    const handlePrint = useReactToPrint({
        contentRef: barcodeRef, // Use contentRef instead of content
    });

    const getProductPrice = (row) => {
        if(!!row.priceTier)
            return currentObject.pricingType.value == 0 ? row.priceTier.data.price : row.priceTier.data.price_with_tax;
        else
            return currentObject.pricingType.value == 0 ? row.product.data.price : row.product.data.price_with_tax;
    }

    const barcodeValue = "509824854351";
    const numberOfBarcodes = 12;

    return (
        <EditRowCompnent
            type={translations.printBarcode}
            translations={translations}
            submitTitle ={translations.print}
            submitAction ={handlePrint}
            defaultMenu={[
                {
                    size:3,
                    key: 'printOptions',
                    visible: true,
                    comp:
                        <>
                        <div class="d-flex  align-items-center pt-3">
                            <label class="fs-6 fw-semibold mb-2 me-3 "
                                style={{ width: "120px" }}>{translations.name}</label>
                            <div class="form-check form-switch">
                                <InputSwitch checked={!!currentObject.name ? !!currentObject.name : false}
                                    onChange={(e) => onChange('name', e.value)} />
                            </div>
                        </div>
                        <div class="d-flex  align-items-center pt-3">
                            <label class="fs-6 fw-semibold mb-2 me-3 "
                                style={{ width: "120px" }}>{translations.price}</label>
                            <div class="form-check form-switch">
                                <InputSwitch checked={!!currentObject.price ? !!currentObject.price : false}
                                    onChange={(e) => onChange('price', e.value)} />
                            </div>
                        </div>
                        <div class="d-flex  align-items-center pt-3">
                            <label class="fs-6 fw-semibold mb-2 me-3 "
                                style={{ width: "120px" }}>{translations.expiryDate}</label>
                            <div class="form-check form-switch">
                                <InputSwitch checked={!!currentObject.expiryDate ? !!currentObject.expiryDate : false}
                                    onChange={(e) => onChange('expiryDate', e.value)} />
                            </div>
                        </div>
                        <div class="d-flex  align-items-center pt-3">
                            <label class="fs-6 fw-semibold mb-2 me-3 "
                                style={{ width: "120px" }}>{translations.sellDate}</label>
                            <div class="form-check form-switch">
                                <InputSwitch checked={!!currentObject.sellDate ? !!currentObject.sellDate : false}
                                    onChange={(e) => onChange('sellDate', e.value)} />
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="row">
                                <div class="col-12">
                                    <label for="priceDisplay" class="col-form-label">{translations.priceDisplay}</label>
                                    <Select
                                        id="priceDisplay"
                                        isMulti={false}
                                        options={pricingTypes}
                                        closeMenuOnSelect={true}
                                        components={animatedComponents}
                                        value={currentObject.pricingType}
                                        onChange={val => onChange('pricingType', val)}
                                        menuPortalTarget={document.body}
                                        styles={{ menuPortal: base => ({ ...base, zIndex: 100000 }) }}
                                    />
                                </div>
                            </div>
                        </div>
                        </>
                },
                {
                    key: 'products',
                    visible: true,
                    comp:
                        <TreeTableEditorLocal
                            translations={translations}
                            dir={dir}
                            header={false}
                            addNewRow={true}
                            type={"products"}
                            title={translations.products}
                            currentNodes={[...currentObject.products]}
                            defaultValue={{}}
                            cols={[
                                {
                                    key: "product", autoFocus: true, searchUrl: "searchProducts", type: "AsyncDropDown", width: '30%',
                                    editable: true, required: true,
                                    onChangeValue: (nodes, key, val, rowKey, postExecute) => {
                                        nodes[rowKey].data.SKU = val.SKU;
                                        postExecute(nodes, true);
                                    }
                                },
                                {
                                    key: "count", autoFocus: false, type: "Number", width: '10%', editable: true, required: true,
                                },
                                {
                                    key: "expiryDate", autoFocus: false, type: "Date", width: '20%', editable: true, required: true,
                                },
                                {
                                    key: "sellDate", autoFocus: false, type: "Date", width: '20%', editable: true, required: true,
                                },
                                {key : "priceTier", autoFocus: true, type :"AsyncDropDown", width:'25%', editable:true, required:true,
                                    searchUrl:"searchProductPriceTiers",
                                    relatedTo:{
                                        key: "id",
                                        relatedKey : "product.id"
                                    }
                                }

                            ]}
                            actions={[]}
                            onUpdate={(nodes) => onChange("products", nodes)}
                            onDelete={null} />
                },
                {
                    key: 'preview',
                    visible: true,
                    comp: <div
                        ref={barcodeRef}
                        style={{
                            display: "grid",
                            gridTemplateColumns: "repeat(3, 1fr)",
                            gap: "20px",
                            padding: "20px",
                            backgroundColor: "white",
                        }}
                    >
                        {currentObject.products.map((product, index) => (
                            Array.from({ length: product.count }).map((_, index) => (
                            <div key={index} style={{ textAlign: "center" }}>
                                {currentObject.name ? <div>{getRowName(product.product.data)}</div> : <></>}
                                {currentObject.price ? <div>{`${translations.price} ${getProductPrice(product)}`}</div> : <></>}
                                {currentObject.expiryDate && product.expiryDate ? <div>{`${translations.expiryDate} ${product.expiryDate}`}</div> : <></>}
                                {currentObject.sellDate && product.sellDate ? <div>{`${translations.sellDate} ${product.sellDate}`}</div> : <></>}
                                 <Barcode value={product.product.data.barcode} format="CODE128" width={1.5} height={50} />
                            </div>
                            ))
                        ))}
                    </div>
                }
            ]} />
        // <div className="p-4">
        //   <button onClick={handlePrint} className="mb-4 p-2 bg-blue-500 text-white">
        //     Print Barcodes
        //   </button>
    );
}

export default ProductBarcode;
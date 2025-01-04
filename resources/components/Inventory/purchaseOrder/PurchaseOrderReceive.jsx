import { useEffect, useState } from "react";
import EditRowCompnent from "../../comp/EditRowCompnent";
import BasicInfoComponent from "../../comp/BasicInfoComponent";
import TreeTableEditorLocal from "../../comp/TreeTableEditorLocal";

const PurchaseOrderReceive = ({ dir, translations }) => {
    const rootElement = document.getElementById('root');
    let purchaseOrder = JSON.parse(rootElement.getAttribute('purchaseOrder'));
    const [currentObject, setcurrentObject] = useState(purchaseOrder);
    
    useEffect(() => {
        updateTotals(currentObject);
    }, [currentObject]);

    const onBasicChange = (key, value) => {
        let r = {...currentObject};
        r[key] = value;
        setcurrentObject({...r});
    }

    const updateTotals = (row) =>{
        row.subtotal = row.items ? 
        row.items.reduce((accumulator, item) => accumulator + Number(item.total ?? 0), 0) : 0;
        row.total = row.subtotal + Number(row.tax ?? 0);
        row.grand_total = row.total + Number(row.shipping_amount ?? 0) + Number(row.misc_amount ?? 0);
    }

    const onProductChange = (key, val) =>{
        currentObject[key] = val;
        setcurrentObject({...currentObject});
        return {message:"Done"};
    }
    
    return (
        <EditRowCompnent
         defaultMenu={[
            { 
                key: 'pOInfo', 
                visible: true, 
                comp : <BasicInfoComponent
                        currentObject={currentObject}
                        translations={translations}
                        dir={dir}
                        onBasicChange={onBasicChange}
                        fields={
                            [   
                                {key:"subtotal" , title:"subTotal", type:"Decimal", readOnly: true, size:4, newRow: true},
                                {key:"tax" , title:"tax", type:"Decimal", size:4},
                                {key:"total" , title:"total", type:"Decimal", readOnly: true, size:4},
                                {key:"misc_amount" , title:"miscAmount", type:"Decimal", size:4, newRow: true},
                                {key:"shipping_amount" , title:"shippingAmount", type:"Decimal", size:4},
                                {key:"grand_total" , title:"grandTotal", type:"Decimal", readOnly: true, size:4}, 
                                {key:"notes" , title:"notes", type:"TextArea", newRow: true, size:8}
                            ]
                        }
                       />
            },
            { 
                key: 'items', 
                visible: true, 
                comp : 
                <TreeTableEditorLocal
                translations={translations}
                dir={dir}
                header={false}
                addNewRow={false}
                type= {"items"}
                title={translations.items}
                currentNodes={[...currentObject.items]}
                defaultValue={{taxed : 0}}
                cols={[
                    {key : "product", autoFocus: true, searchUrl:"searchProducts", type :"AsyncDropDown", width:'15%', editable:false},
                    {key : "unit", autoFocus: true, type :"AsyncDropDown", width:'15%', editable:false},
                    {key : "qty", autoFocus: true, type :"Decimal", width:'15%', editable:false} ,
                    {key : "recievd_qty", autoFocus: true, type :"Decimal", width:'15%', editable:true} ,
                    {key : "cost", autoFocus: true, type :"Decimal", width:'15%', editable:false},
                    {key : "total", autoFocus: true, type :"Decimal", width:'15%', editable:false}
                ]}
                actions = {[]}
                onUpdate={(nodes)=> onProductChange("items", nodes)}
                onDelete={null}/>
            }
          ]}
          currentObject={currentObject}
          translations={translations}
          dir={dir}
          apiUrl="updateRecive"
          afterSubmitUrl='../../purchaseOrder'
          type="purchaseOrder"
        />
    );
}

export default PurchaseOrderReceive;
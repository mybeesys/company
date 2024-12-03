import { useEffect, useState } from "react";
import EditRowCompnent from "../../comp/EditRowCompnent";
import BasicInfoComponent from "../../comp/BasicInfoComponent";
import TreeTableComponentLocal from "../../comp/TreeTableComponentLocal";

const TransferDetail = ({ dir, translations }) => {
    const rootElement = document.getElementById('root');
    let transfer = JSON.parse(rootElement.getAttribute('transfer'));
    const [currentObject, setcurrentObject] = useState(transfer);
    
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
        updateTotals(currentObject);
        setcurrentObject({...currentObject});
        return {message:"Done"};
    }
    
    return (
        <EditRowCompnent
         defaultMenu={[
            { 
                key: 'establishment', 
                visible: true, 
                comp : <BasicInfoComponent
                        currentObject={currentObject}
                        translations={translations}
                        dir={dir}
                        onBasicChange={onBasicChange}
                        fields={
                            [
                                {key:"establishment" , title:"establishment", searchUrl:"searchEstablishments", type:"Async", required : true},
                            ]
                        }
                       />
            },
            { 
                key: 'items', 
                visible: true, 
                comp : 
                <TreeTableComponentLocal
                translations={translations}
                dir={dir}
                header={true}
                addNewRow={true}
                type= {"items"}
                title={translations.items}
                currentNodes={[...currentObject.items]}
                defaultValue={{taxed : 0}}
                cols={[
                    {key : "product", autoFocus: true, searchUrl:"searchProducts", type :"AsyncDropDown", width:'15%', 
                        editable:true, required:true,
                        onChangeValue : (row, key, val, postExecute) => {
                            axios.get(`${window.location.origin}/getProductInventory/${val.id}`)
                            .then(response => {
                                let prod = response.data;
                                row.SKU = prod.SKU;
                                row.item_type = 'p';
                                if(!!prod.inventory){
                                    row.qty = 1;
                                    row.cost = prod.inventory.primary_vendor_default_price;
                                    row.unit = prod.inventory.unit;
                                    row.total = !!prod.inventory.primary_vendor_default_price && !!prod.inventory.primary_vendor_default_quantity 
                                                        ? prod.inventory.primary_vendor_default_price * prod.inventory.primary_vendor_default_quantity : 0;
                                }
                                else{
                                    row.qty = null;
                                    row.cost = null;
                                    row.unit = null;
                                    row.total = null;
                                }
                                postExecute(row);
                            })
                            .catch(error => {
                              console.error('Error fetching translations', error);
                            });
                        }
                    },
                    {key : "SKU", autoFocus: true, type :"Text", width:'15%', editable:false,
                        customCell:(data, key, currentEditing, editable)=>{
                            return <span>{!!data["product"] ? data["product"].SKU : ''}</span>
                        }
                    },
                    {key : "unit", autoFocus: true, type :"AsyncDropDown", width:'15%', editable:true,required:true,
                        searchUrl:"searchUnitTransfers",
                        relatedTo:{
                            key: "product_id",
                            relatedKey : "product.id"
                        }
                    },
                    {key : "qty", autoFocus: true, type :"Decimal", width:'15%', editable:true, required:true,
                        onChangeValue : (row, key, val, postExecute) => {
                            row.total = !!val && !!row.cost ? val * row.cost : null;
                            postExecute(row);
                        }
                    },
                    {key : "cost", autoFocus: true, type :"Decimal", width:'15%', editable:true, required:true,
                        onChangeValue : (row, key, val, postExecute) => {
                            row.total = !!row.qty && !!val ? row.qty* val : null;
                            postExecute(row);
                        }
                    },
                    {key : "total", autoFocus: true, type :"Decimal", width:'15%', editable:true}
                ]}
                actions = {[]}
                onUpdate={(nodes)=> onProductChange("items", nodes)}
                onDelete={null}/>
            },
            { 
                key: 'rmaInfo', 
                visible: true, 
                comp : <BasicInfoComponent
                        currentObject={currentObject}
                        translations={translations}
                        dir={dir}
                        onBasicChange={onBasicChange}
                        fields={
                            [  
                                {key:"op_date" , title:"date", type:"Date", required : true, size:4},
                                {key:"subtotal" , title:"subTotal", type:"Decimal", readOnly: true, size:4}, 
                                {key:"notes" , title:"notes", type:"TextArea", newRow: true, size:8}
                            ]
                        }
                       />
            }
          ]}
          currentObject={currentObject}
          translations={translations}
          dir={dir}
          apiUrl="inventoryOperation/store/4"
          afterSubmitUrl="../../transfer"
        />
    );
}

export default TransferDetail;
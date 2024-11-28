import { useEffect, useState } from "react";
import EditRowCompnent from "../../comp/EditRowCompnent";
import BasicInfoComponent from "../../comp/BasicInfoComponent";
import TreeTableComponentLocal from "../../comp/TreeTableComponentLocal";
import AsyncSelectComponent from "../../comp/AsyncSelectComponent";

const PrepDetail = ({ dir, translations }) => {
    const rootElement = document.getElementById('root');
    let prep = JSON.parse(rootElement.getAttribute('prep'));
    const [currentObject, setcurrentObject] = useState(prep);
    
    useEffect(() => {
        
    }, [currentObject]);

    const onBasicChange = (key, value) => {
        let r = {...currentObject};
        r[key] = value;
        setcurrentObject({...r});
        return {message:"Done"};
    }
    

    const onProductChange = (key, val) =>{
        currentObject[key] = val;
        if(key!='product'){
            return;
        }
        setcurrentObject({...currentObject});
        axios.get(`../listRecipebyProduct/${val.id}?with_ingredient='Y'`).then(response => {
            let items = response.data.map(obj => {
                const { quantity, products, ...rest } = obj;
                return { 
                    qty: quantity,
                    cost:1, 
                    product: products, 
                    unitTransfers: !!products.unitTransfers ? products.unitTransfers: [],
                    unit : !!products.unitTransfers ? products.unitTransfers.find(x=>x.unit2 == null) : null,
                    ...rest }; // Create a new object with `newKey`
              });
            currentObject.items = items;
            setcurrentObject({...currentObject});
        });
        return {message:"Done"};
    }
    
    return (
        <EditRowCompnent
         defaultMenu={[
            { 
                key: 'product', 
                visible: true, 
                comp : <BasicInfoComponent
                        currentObject={currentObject}
                        translations={translations}
                        dir={dir}
                        onBasicChange={onProductChange}
                        fields={
                            [
                                {key:"product" , title:"product", searchUrl:"searchProducts", type:"Async", required : true},
                                {key:"times" , title:"times1",  type:"Number", required : true, newRow:true},
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
                type= {"items"}
                title={translations.items}
                currentNodes={[...currentObject.items]}
                defaultValue={{taxed : 0}}
                cols={[
                    {key : "product", autoFocus: true, searchUrl:"searchProducts", type :"AsyncDropDown", width:'15%', 
                        editable:false, required:true
                    },
                    {key : "SKU", autoFocus: true, type :"Text", width:'15%', editable:false,
                        customCell:(data, key, currentEditing, editable)=>{
                            return <span>{!!data["product"] ? data["product"].SKU : ''}</span>
                        }
                    },
                    {key : "unit", autoFocus: true, type :"Text", width:'15%', editable:true, required:true,
                        customCell: (data, key, editMode, editable, onCahnge) =>{
                            return (!!!editMode? <>{!!data.unit? data.unit.unit1 : ''}</> :
                            <select class={`form-control number-indent-2`}
                                defaultValue={!!data.unit? data.unit.id : ''}
                                onChange={(e) => onCahnge(e.target.value, col.key)}
                                onKeyDown={(e) => e.stopPropagation()}
                                style={{ width: '100%' }}
                                required>
                                {!!data.product.unitTransfers ? data.product.unitTransfers.map((option) => (
                                    <option value={option.id}>{option.unit1}</option>
                                )): null
                                }
                            </select>)
                        }
                    },                    
                    {key : "qty", autoFocus: true, type :"Decimal", width:'15%', editable:true, required:true,
                        onChangeValue : (row, key, val, postExecute) => {
                            row.total = !!val && !!row.cost ? val * row.cost : null;
                            postExecute(row);
                        }
                    }
                ]}
                actions = {[]}
                onUpdate={(nodes)=> onBasicChange("items", nodes)}
                onDelete={null}/>
            }
          ]}
          currentObject={currentObject}
          translations={translations}
          dir={dir}
          apiUrl="inventoryOperation/store/1"
          afterSubmitUrl="../../prep"
          validateObject = {(obj)=>{
                if(!!currentObject.items && currentObject.items.filter(x=>!!!x.unit).length >0)
                    return translations['item_unit_error'];
                else
                    return 'Success' 
          }}
        />
    );
}

export default PrepDetail;
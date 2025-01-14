import { useEffect, useState } from "react";
import EditRowCompnent from "../../comp/EditRowCompnent";
import BasicInfoComponent from "../../comp/BasicInfoComponent";
import TreeTableEditorLocal from "../../comp/TreeTableEditorLocal";
import SweetAlert2 from 'react-sweetalert2';
import { getName } from "../../lang/Utils";

const PrepDetail = ({ dir, translations }) => {
    const rootElement = document.getElementById('root');
    let prep = JSON.parse(rootElement.getAttribute('prep'));
    const [currentObject, setcurrentObject] = useState(prep);
    const [showAlert, setShowAlert] = useState(false);

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
            onBasicChange(key, val);
            return;
        }
        setcurrentObject({...currentObject});
        axios.get(`${window.location.origin}/listRecipebyProduct/${val.id}?with_ingredient=Y`).then(response => {
            let items = response.data.map(obj => {
                const { quantity, products, ...rest } = obj;
                return { 
                    qty: quantity,
                    unit_price_before_discount:1, 
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

    const getErrorMessage = (data)=>{
        let res =''
        for (let index = 0; index < data.length; index++) {
            const element = data[index];
            res+=`<div>${getName(element.name_en, element.name_ar, dir)} : ${element.qty}</div>`;
        }
        return res;
    }

    const handleQuantityError = (data) => {
        setShowAlert(true);
        Swal.fire({
        show: showAlert,
        title: 'Error',
        html: `<div>${translations.notEnoughQuantity}</div>${getErrorMessage(data)}`,
        icon: "error",
        timer: 4000,
        showCancelButton: false,
        showConfirmButton: false,
        }).then(() => {
        setShowAlert(false); // Reset the state after alert is dismissed
        });
    }

    const validateObject = (data) =>{
        if(!!!data.establishment) return `${translations.establishment} ${translations.required}`;
        if(!!!data.product || data.product.length ==0) return `${translations.product} ${translations.required}`;
        if(!!currentObject.items && currentObject.items.filter(x=>!!!x.unit).length >0) return translations['item_unit_error'];
        return 'Success';
    }
    
    return (
        <>
        <SweetAlert2 />
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
                                {key:"establishment" , title:"establishment", searchUrl:"searchEstablishments", type:"Async", required : true},
                                {key:"product" , title:"product", searchUrl:"searchPrepProducts", type:"Async", required : true},
                                {key:"times" , title:"times1",  type:"Number", required : true, newRow:true},
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
                type= {"items"}
                title={translations.items}
                currentNodes={[...currentObject.items]}
                defaultValue={{taxed : 0}}
                cols={[
                    {key : "product", autoFocus: true, searchUrl:"searchProducts", type :"AsyncDropDown", width:'15%', 
                        editable:false, required:true
                    },
                    {key : "SKU", autoFocus: true, type :"Text", width:'15%', editable:false,
                        customCell:(data, key, editable, onChange)=>{
                            return <span>{!!data["product"] ? data["product"].SKU : ''}</span>
                        }
                    },
                    {key : "unit_transfer", title: "unit", autoFocus: true, type :"AsyncDropDown", width:'15%', editable:false},                    
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
          apiUrl="prep"
          afterSubmitUrl="../../prep"
          validateObject = {validateObject}
          type="prp"
          handleError={handleQuantityError}
        />
        </>
    );
}

export default PrepDetail;
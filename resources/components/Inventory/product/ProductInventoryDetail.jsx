import { useState } from "react";
import EditRowCompnent from "../../comp/EditRowCompnent";
import ProductInventoryBasicInfo from "./ProductInventoryBasicInfo";
import ProductInventoryVendor from "./ProductInventoryVendor";
import { getName } from "../../lang/Utils";

const ProductInventoryDetail = ({ dir, translations, p_type }) => {
    const rootElement = document.getElementById('root');
    let prodcutInventory = JSON.parse(rootElement.getAttribute(`${p_type}Inventory`));
    const [currentObject, setcurrentObject] = useState(prodcutInventory);

    const onBasicChange = (key, value) => {
        let r = {...currentObject};
        r[key] = value;
        setcurrentObject({...r});
    }

    const validateObject = (data) =>{
        if(!!!data.unit) return `${translations.unit} ${translations.required1}`;
        return 'Success';
    }
    
    return (
        <EditRowCompnent
         defaultMenu={[
            { 
                key: 'basicInfo', 
                visible: true, 
                comp : <ProductInventoryBasicInfo
                        p_type={p_type}
                        currentObject={currentObject}
                        translations={translations}
                        dir={dir}
                        onBasicChange={onBasicChange}
                       />
            },
            { 
                key: 'vendor', 
                visible: false , 
                comp : <ProductInventoryVendor
                    p_type={p_type}
                    currentObject={currentObject}
                    translations={translations}
                    dir={dir}
                    onBasicChange={onBasicChange}
                    /> }
          ]}
          currentObject={currentObject}
          translations={translations}
          dir={dir}
          apiUrl={`${p_type}Inventory`}
          type={getName(currentObject.name_en, currentObject.name_ar, dir)}
          validateObject = {validateObject}
        />
    );
}

export default ProductInventoryDetail;
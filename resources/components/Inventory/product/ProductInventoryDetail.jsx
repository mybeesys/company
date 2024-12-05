import { useState } from "react";
import EditRowCompnent from "../../comp/EditRowCompnent";
import ProductInventoryBasicInfo from "./ProductInventoryBasicInfo";
import ProductInventoryVendor from "./ProductInventoryVendor";

const ProductInventoryDetail = ({ dir, translations, p_type }) => {
    const rootElement = document.getElementById('root');
    let prodcutInventory = JSON.parse(rootElement.getAttribute(`${p_type}Inventory`));
    const [currentObject, setcurrentObject] = useState(prodcutInventory);

    const onBasicChange = (key, value) => {
        let r = {...currentObject};
        r[key] = value;
        setcurrentObject({...r});
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
        />
    );
}

export default ProductInventoryDetail;
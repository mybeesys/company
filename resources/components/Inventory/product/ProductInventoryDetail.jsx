import { useState } from "react";
import EditRowCompnent from "../../comp/EditRowCompnent";
import ProductInventoryBasicInfo from "./ProductInventoryBasicInfo";
import ProductInventoryVendor from "./ProductInventoryVendor";

const ProductInventoryDetail = ({ dir, translations }) => {
    const rootElement = document.getElementById('root');
    let prodcutInventory = JSON.parse(rootElement.getAttribute('productInventory'));
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
                    currentObject={currentObject}
                    translations={translations}
                    dir={dir}
                    onBasicChange={onBasicChange}
                    /> }
          ]}
          currentObject={currentObject}
          translations={translations}
          dir={dir}
          apiUrl="productInventory"
        />
    );
}

export default ProductInventoryDetail;
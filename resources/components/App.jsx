import React , { useState, useCallback, useEffect  } from 'react';
import ReactDOM from 'react-dom/client';
import ProductComponent from './product/ProductComponent';
import CategoryTree from './product/CategoryTree';
import Modifiertree from './modifier/modifiertree';
import Attributetree from './attributes/attributetree';
import CustomMenuTable from './custommenu/CustomMenuTable';
import CustomMenuDetail from './custommenu/CustomMenuDetail';
import ServiceFeeTable from './serviceFee/ServiceFeeTable';
import ServiceFeeDetail from './serviceFee/ServiceFeeDetail';
import DiscountTable from './discount/DiscountTable';
import Ingredient from './ingredients/Ingredient';
import IngredientDetail from './ingredients/IngredientDetail';
import DiscountDetail from './discount/DiscountDetail';
import LinkedComboTable from './linkedCombo/LinkedComboTable';
import LinkedComboDetail from './linkedCombo/LinkedComboDetail';
import ProductInventoryTable from './Inventory/product/ProductInventoryTable';
import ProductInventoryDetail from './Inventory/product/ProductInventoryDetail';



const App = ({nodeType, dir}) =>{
  const [translations, setTranslations] = useState({});
  const [loading, setLoading] = useState(true);

  const nodeElement = {
    category        : <CategoryTree translations={translations} dir={dir}/>,
    product         : <ProductComponent translations={translations} dir={dir}/>,
    modifier        : <Modifiertree translations={translations} dir={dir}/>,
    attribute       : <Attributetree translations={translations} dir={dir}/>,
    custommenu      : <CustomMenuTable translations={translations} dir={dir}/>,
    custommenuedit  : <CustomMenuDetail translations={translations} dir={dir}/>,
    serviceFee      : <ServiceFeeTable translations={translations} dir={dir}/>,
    servicefeeedit  : <ServiceFeeDetail translations={translations} dir={dir}/>,
    ingredient      : <Ingredient translations={translations} dir={dir}/>,
    ingredientedit: <IngredientDetail translations={translations} dir={dir}/>,
    discount        : <DiscountTable translations={translations} dir={dir}/>,
    discountedit    : <DiscountDetail translations={translations} dir={dir}/>,
    linkedCombo     : <LinkedComboTable translations={translations} dir={dir}/>,
    linkedComboedit    : <LinkedComboDetail translations={translations} dir={dir}/>,
    productinventory : <ProductInventoryTable translations={translations} dir={dir}/>,
    productinventoryedit : <ProductInventoryDetail translations={translations} dir={dir}/>
  }
  
  useEffect(() => {
    const loadTranslations = async () => {
      let transaltion ={};
      if(dir == 'ltr'){
        await import('./style.scss');
        transaltion = await import('./lang/en.json');
      }
      else{
       await import('./style.rtl.scss');
       transaltion = await import('./lang/ar.json');
      }
      setTranslations(transaltion);
      setLoading(false);
    };
    loadTranslations();
  }, []);

  if (loading) {
    return <div></div>;
  }

  let comp = nodeElement[nodeType];

  return comp;
}

var htmlElement = document.querySelector("html");
const dir =   htmlElement.getAttribute('dir');
const element = document.getElementById('root');
const root = ReactDOM.createRoot(element);
root.render(<App dir={dir} nodeType={element.getAttribute('type')}/>);
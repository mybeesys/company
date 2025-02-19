import React, { useEffect, useState } from 'react';
import ProductModiferDetail from './ProductModiferDetail';


const ProductModifier = ({translations, urlList, productId, productModifiers, onChange, onSelectAll}) => {
    const rootElement = document.getElementById('root');
    let dir = rootElement.getAttribute('dir');
    const [data, setData] = useState([]);

    useEffect(() => {
        axios.get(urlList)
        .then(response => {
          setData(response.data);
        })
        .catch(error => {
          console.error('Error fetching translations', error);
        });
    }, []);

    const handleChange = (modifierId, key, value) =>{
        onChange(modifierId, key, value)
    }

    const handleSelectAll = () =>{
        onSelectAll(data);
    }

    return (
        data.filter(x=>x.data.empty!='Y').map((modifierClass) => (
            <ProductModiferDetail
           
                translations={translations}
                productId ={productId}
                modifierId={modifierClass.data.id}
                title={dir== "rtl"? modifierClass.data.name_ar:  modifierClass.data.name_en}
                data={!!!modifierClass.children ? [] : modifierClass.children}
                productModifiers={productModifiers}
                onchange={handleChange}
                onSelectAll={handleSelectAll}/>
        ))
    );
  };

  export default ProductModifier;

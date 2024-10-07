import React , { useState, useCallback  } from 'react';
import ReactDOM from 'react-dom/client';
import TreeTableProduct from "./TreeTableProduct";


const ProductTree = () => {
    const rootElement = document.getElementById('react-root');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));
    const categoryurl = JSON.parse(rootElement.getAttribute('category-url'));
    const subcategoryurl = JSON.parse(rootElement.getAttribute('subcategory-url'));
    const producturl = JSON.parse(rootElement.getAttribute('product-url'));
    let  localizationurl = JSON.parse(rootElement.getAttribute('localization-url'));
    const [translations, setTranslations] = useState({});

    React.useEffect(() => {
      axios.get(localizationurl)
        .then(response => {
          setTranslations(response.data);
        })
        .catch(error => {
          console.error('Error fetching translations', error);
        });

    }, []);

    return (
      <div>
        <TreeTableProduct urlList = {urlList} 
         categoryurl = {categoryurl}
         subcategoryurl = {subcategoryurl}
         producturl = {producturl}
         translations ={translations}
          />
      </div>
    );
  };
  

  export default ProductTree;
import React from 'react';
import ReactDOM from 'react-dom/client';
import TreeTableProduct from "./TreeTableProduct";


const App = () => {
    const rootElement = document.getElementById('react-root');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));
    const categoryurl = JSON.parse(rootElement.getAttribute('category-url'));
    const subcategoryurl = JSON.parse(rootElement.getAttribute('subcategory-url'));
    const producturl = JSON.parse(rootElement.getAttribute('product-url'));

    return (
      <div>
        <TreeTableProduct urlList = {urlList} 
         categoryurl = {categoryurl}
         subcategoryurl = {subcategoryurl}
         producturl = {producturl}
          />
      </div>
    );
  };
  
ReactDOM.createRoot(document.getElementById('react-root')).render(<App />);
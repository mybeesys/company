import React from 'react';
import TreeTableProduct from './TreeTableModifier';


const CategoryTree = () => {
    const rootElement = document.getElementById('category-root');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));

    return (
      <div>
        <TreeTableProduct urlList = {urlList}
        rootElement ={rootElement}
          />
      </div>
    );
  };
  
  export default CategoryTree;
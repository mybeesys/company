import React from 'react';
import TreeTableProduct from './TreeTableModifier';


const CategoryTree = ({translations}) => {
    const rootElement = document.getElementById('root');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));

    return (
      <div>
        <TreeTableProduct urlList = {urlList}
        rootElement ={rootElement}
        translations={translations}
          />
      </div>
    );
  };
  
  export default CategoryTree;
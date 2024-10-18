import React from 'react';
import TreeTableAttribute from './TreeTableAttribute';


const attributetree = () => {
    const rootElement = document.getElementById('attribute-root');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));

    return (
      <div>
        <TreeTableAttribute urlList = {urlList}
        rootElement ={rootElement}
          />
      </div>
    );
  };
  
  export default attributetree;
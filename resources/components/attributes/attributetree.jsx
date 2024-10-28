import React from 'react';
import TreeTableAttribute from './TreeTableAttribute';


const attributetree = ({translations}) => {
    const rootElement = document.getElementById('root');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));

    return (
      <div>
        <TreeTableAttribute urlList = {urlList}
        rootElement ={rootElement}
        translations={translations}
          />
      </div>
    );
  };
  
  export default attributetree;
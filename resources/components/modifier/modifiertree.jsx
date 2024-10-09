import React from 'react';
import ReactDOM from 'react-dom/client';
import TreeTableModifier from './TreeTableModifier';


const modifiertree = () => {
    const rootElement = document.getElementById('modifier-root');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));

    return (
      <div>
        <TreeTableModifier urlList = {urlList}
        rootElement ={rootElement}
          />
      </div>
    );
  };
  
  export default modifiertree;
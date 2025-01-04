import React from 'react';
import TreeTableModifier from './TreeTableModifier';


const modifiertree = ({translations, dir}) => {
    const rootElement = document.getElementById('root');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));

    return (
      <div>
        <TreeTableModifier urlList = {urlList}
        rootElement ={rootElement}
        translations={translations}
        dir={dir}
          />
      </div>
    );
  };
  
  export default modifiertree;
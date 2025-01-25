import React from 'react';
import TreeTableArea from './TreeTableArea';


const AreaTable = ({translations, dir}) => {
    const rootElement = document.getElementById('root');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));

    return (
      <div>
        <TreeTableArea urlList = {urlList}
        rootElement ={rootElement}
        translations={translations}
        dir={dir}
          />
      </div>
    );
  };
  
  export default AreaTable;
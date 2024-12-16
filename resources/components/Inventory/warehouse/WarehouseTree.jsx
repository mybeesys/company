import React from 'react';
import TreeTableComponent from '../../comp/TreeTableComponent';


const WarehouseTree = ({translations, dir}) => {
    const rootElement = document.getElementById('root');
    const urlList = JSON.parse(rootElement.getAttribute('list-url'));

    return (
      <div>
        <TreeTableComponent
          translations={translations}
          dir={dir}
          urlList={`${urlList}`}
          editUrl={null}
          addUrl={null}
          canAddInline={true}
          title="warehouses"
          cols={[
            {key : "name_en", autoFocus: true, type :"Text", width:'15%'},
            {key : "name_ar", autoFocus: false, type :"Text", width:'15%'},
            {key : "order", autoFocus: false, type :"Number", width:'15%'}
          ]}
        />
      </div>
    );
  };
  
  export default WarehouseTree;
import React from 'react';
import TreeTableComponent from '../../comp/TreeTableComponent';


const PriceTierTree = ({translations, dir}) => {
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
          title="priceTiers"
          defaultValue={{active : 1}}
          cols={[
            {key : "name_en", autoFocus: true, type :"Text", width:'25%', required : true},
            {key : "name_ar", autoFocus: false, type :"Text", width:'25%', required : true},
            {key : "active", autoFocus: false, type :"Check", width:'20%'}
          ]}
        />
      </div>
    );
  };
  
  export default PriceTierTree;
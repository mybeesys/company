import React, { useEffect, useState } from 'react';
import axios from 'axios';
import TreeTableDiscount from './TreeTableDiscount';


const DiscountTable = ({dir, translations}) => {
  const rootElement = document.getElementById('root');
  const urlList = JSON.parse(rootElement.getAttribute('list-url'));
  
  const [lov, setLov] = useState([]);
  const [discountTypes, setDiscountTypes] = useState([]);
  const [discountFunctions, setDiscountFunctions] = useState([]);
  
  useEffect(() => {
    const fetchData = async () => {
      const res = await axios.get('/discountLovs');
      setDiscountTypes(res.data["discountType"].map(e => { return { name: translations[`discount_${e.name}`], value: e.value } }));
      setDiscountFunctions(res.data["discountFunction"].map(e => { return { name: translations[`discount_${e.name}`], value: e.value } }));
    }
    fetchData().catch(console.error);
  }, []);

  return (
    <div>
      <TreeTableDiscount urlList={urlList}
        rootElement={rootElement}
        translations={translations}
        discountFunctions={discountFunctions}
        discountTypes={discountTypes}
      />
    </div>
  );
};

export default DiscountTable;
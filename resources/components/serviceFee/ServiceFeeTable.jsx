import React, { useEffect, useState } from 'react';
import TreeTableServiceFee from './TreeTableServiceFee';


const ServiceFeeTable = ({dir, translations}) => {
  const rootElement = document.getElementById('root');
  const urlList = JSON.parse(rootElement.getAttribute('list-url'));

  const [feeTypes, setFeeTypes] = useState([]);
  const [appTypes, setAppTypes] = useState([]);
  const [calcMethods, setCalcMethods] = useState([]);

  useEffect(() => {
    const fetchData = async () => {
      const res2 = await axios.get('/serviceFeeTypeValues');
      const lFeeTypes = res2.data.map(e => { return { name: translations[`service_fee_type_${e.name}`], value: e.value } });
      const res3 = await axios.get('/serviceFeeAppTypeValues');
      const lAppTypes = res3.data.map(e => { return { name: translations[`service_fee_app_type_${e.name}`], value: e.value } });
      const res4 = await axios.get('/serviceFeeCalcMetheodValues');
      const lCalcMethods = res4.data.map(e => { return { name: translations[`service_fee_calc_method_${e.name}`], value: e.value } });
      setFeeTypes(lFeeTypes);
      setAppTypes(lAppTypes);
      setCalcMethods(lCalcMethods);
    }
    fetchData().catch(console.error);
  }, []);

  return (
    <div>
      <TreeTableServiceFee urlList={urlList}
        rootElement={rootElement}
        translations={translations}
        feeTypes={feeTypes}
        calcMethods={calcMethods}
        appTypes={appTypes}
      />
    </div>
  );
};

export default ServiceFeeTable;

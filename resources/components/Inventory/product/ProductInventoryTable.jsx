import { useEffect, useState } from 'react';
import TreeTableComponent from '../../comp/TreeTableComponent';
import { getName, getRowName } from '../../lang/Utils';
import makeAnimated from 'react-select/animated';
import Select from "react-select";
import ProductTransactionModal from './ProductTransactionModal';

const animatedComponents = makeAnimated();
const ProductInventoryTable = ({ dir, translations, p_type }) => {
  const rootElement = document.getElementById('root');
  const [urlList, setUrlList] = useState(JSON.parse(rootElement.getAttribute('list-url')));
  const [searchBy, setSearchBy] = useState({label: translations.establishment, value: 0});
  const [searchText, setSearchText] = useState();
  const [isTransactionModalVisible, setIsTransactionModalVisible] = useState(false);
  const [productTrnsaction, setProductTransaction] = useState([]);
  const canEditRow=(data)=>{
    return data.type == "product" || data.type == "Ingredient";
  }

  useEffect(() => {
    setUrlList(urlList);
  }, [urlList]);

  const search = () =>{
      if(!!searchText){
        setUrlList(`${JSON.parse(rootElement.getAttribute('list-url'))}?by=${searchBy.value}&key=${searchText}`);
      }
      else{
        setUrlList(`${JSON.parse(rootElement.getAttribute('list-url'))}?by=${-1}&key=`);
      }
  }

  const print = (type) => {
    if(!!searchText){
      window.open(`../productInventoryReport/1/productInventory_${type}?type=${p_type == 'product' ? 'p' : 'i'}&by=${searchBy.value}&key=${searchText}&t=1`, '_blank');
    }
    else{
      window.open(`../productInventoryReport/1/productInventory_${type}?type=${p_type == 'product' ? 'p' : 'i'}&by=${-1}&key=&t=1`, '_blank');
    }
  }

  const openTransactionModel = (data) => {
    axios.get(`/listTransactions?est=${data.establishment_id}&typ=${data.type}&id=${data.id}`).then(respponse => setProductTransaction(respponse.data));
    setIsTransactionModalVisible(true);
  }

  const handleClose = () => {
    setIsTransactionModalVisible(false);
  }

  return (
    <div >
      <div class="form-group">
          <div class="row">
            <div class="col-3">
              <label for="name_ar" class="col-form-label">{translations.search}</label>
              <ProductTransactionModal
                            visible={isTransactionModalVisible}
                            onClose={handleClose}
                            transactions={productTrnsaction}
                            translations ={translations}
                        />
              <Select
                    id="search_id"
                    isMulti={false}
                    options={
                      [
                        {label: translations.establishment, value: 0},
                        {label: translations.product, value: 1},
                      ]
                    }
                    closeMenuOnSelect={true}
                    components={animatedComponents}
                    value={searchBy}
                    onChange={val => setSearchBy(val)}
                    menuPortalTarget={document.body} 
                    styles={{ menuPortal: base => ({ ...base, zIndex: 100000 }) }}
                />
            </div>
            <div class="col-3 pt-12">
              <input type="text" class="form-control form-control-solid custom-height" id="name_en" value={searchText}
                onChange={(e) => setSearchText(e.target.value)} ></input>
            </div>
            <div class="col-3 pt-12" style={{"justify-content": "start", "display": "flex"}}>
                <div class="flex-center" style={{"display": "flex"}}>
                    <button onClick={search} class="btn btn-primary mx-2"
                        style={{"width": "12rem"}}>{translations.search}</button>

                </div>
            </div>
            <div class="col-3 pt-12" style={{"justify-content": "end", "display": "flex"}}>
                <div class="flex-center px-2" style={{"display": "flex"}}>
                  <a title={translations.visitLink} href="javascript:void(0);" onClick={(e)=>print('xls')} class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                    <img style={{width:50, height:60}} src="/assets/media/svg/files/icons8-excel.svg"/>
                  </a>
                </div>
                <div class="flex-center px-2" style={{"display": "flex"}}>
                  <a href="javascript:void(0);" onClick={(e)=>print('pdf')} class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                          <img style={{width:40, height:40}} src="/assets/media/svg/files/pdf.svg"/>
                  </a>
                </div>
            </div>
          </div>
        </div>
        <div className="pt-3" >
        <TreeTableComponent
          translations={translations}
          dir={dir}
          urlList={urlList}
          editUrl={`${p_type}Inventory/%/edit`}
          addUrl={null}
          canAddInline={false}
          canEditRow={canEditRow}
          title={`${p_type}s`}
          expander
          cols={[
            {
              key: "name", autoFocus: true, options: [], type: "Text", width: '16%',
              customCell: (data, key, editMode, editable) => {
                    console.log("row data is:", data);
                return  (<>
                      <span>{getRowName(data, dir)}</span>
                  </>);
              },
            },
            {key: "qty", autoFocus: false, options: [], type: "Decimal", width: '16%',
          customCell: (data, key, editMode, editable) => {
                return  (<>
                       {data.qty}
                  </>);
              },
              
            },
            { key: "vendor", autoFocus: false, options: [], type: "Text", width: '30%',
              customCell: (data, key, editMode, editable) => {
                return  (
                      (!!data.inventory && !!data.inventory.vendor) ? 
                      <span>{getName(data.inventory.vendor.name_en, data.inventory.vendor.name_ar, dir)}</span>
                      : <></>
                  );
              },
            },
            { key: "inventoryUOM", autoFocus: false, options: [], type: "Text", width: '30%',
              customCell: (data, key, editMode, editable) => {
                return  (<>
                  {data.unit1}
             </>);
              },
            },
            {key: "threshold", autoFocus: false, options: [], type: "Decimal", width: '16%',
              customCell: (data, key, editMode, editable) => {
                return  (
                      (!!data.inventory) ? 
                      <span>{data.inventory.threshold}</span>
                      : <></>
                  );
              },
            },
            {key: "", autoFocus: false, type: "Decimal", width: '16%',
              customCell: (data, key, editMode, editable) => {
                return  (
                  data.type == "product" || data.type == "Ingredient" ?
                  <a href="javascript:void(0);" onClick={() => openTransactionModel(data)} class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                    <i class="ki-outline ki-timer fs-2"></i>
                  </a>  : <></>
                  );
              },
            }
          ]}
        />
        </div>
    </div>
  );
};

export default ProductInventoryTable;
import TreeTableComponent from '../../comp/TreeTableComponent';
import { getName, getRowName } from '../../lang/Utils';


const ProductInventoryTable = ({ dir, translations, p_type }) => {
  const rootElement = document.getElementById('root');
  const urlList = JSON.parse(rootElement.getAttribute('list-url'));
  
  const canEditRow=(data)=>{
    return data.type == "product" || data.type == "Ingredient";
  }

  return (
    <div>
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
              return  (<>
                    <span>{getRowName(data, dir)}</span>

                </>);
            },
          },
          {key: "qty", autoFocus: false, options: [], type: "Decimal", width: '16%'},
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
              return  (
                    (!!data.inventory && !!data.inventory.unit) ? 
                    <span>{getName(data.inventory.unit.name_en, data.inventory.unit.name_ar, dir)}</span>
                    : <></>
                );
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
          }
        ]}
      />
    </div>
  );
};

export default ProductInventoryTable;
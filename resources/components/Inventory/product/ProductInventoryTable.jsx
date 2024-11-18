import TreeTableComponent from '../../comp/TreeTableComponent';
import { getName } from '../../lang/Utils';


const ProductInventoryTable = ({ dir, translations }) => {
  const rootElement = document.getElementById('root');
  const urlList = JSON.parse(rootElement.getAttribute('list-url'));

  const productDetailCell = (data, key, editMode, editable) => {
    return  (<>
        <div class="row">
          <span>{getName(data.name_en, data.name_ar, dir)}</span>
        </div>
        <div class="row">
          <span>{`${translations.barcode}: ${!!!data.barcode? '' : data.barcode}`}</span>
        </div>
      </>);
  }

  return (
    <div>
      <TreeTableComponent
        translations={translations}
        dir={dir}
        urlList={urlList}
        editUrl={'productInventory/%/edit'}
        addUrl={null}
        canAddInline={false}
        title="products"
        cols={[
          {
            key: "name", autoFocus: true, options: [], type: "Text", width: '16%',
            customCell: (data, key, editMode, editable) => {
              return  (<>
                  <div class="row">
                    <span>{getName(data.name_en, data.name_ar, dir)}</span>
                  </div>
                  <div class="row">
                    <span>{`${translations.barcode}: ${!!!data.barcode? '' : data.barcode}`}</span>
                  </div>
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
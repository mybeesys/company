import { useEffect, useState } from 'react';
import TreeTableComponent from '../comp/TreeTableComponent';


const TableAreaTable = ({ dir, translations }) => {
  const rootElement = document.getElementById('root');
  const urlList = JSON.parse(rootElement.getAttribute('list-url'));
  const listTableStatusUrl = JSON.parse(rootElement.getAttribute('listTableStatus-url'));
  const [tableStatusOptions, setTableStatusOptions] = useState([]);

  const fetchTableStatusOptions = async () => {
      try {
          let response = await axios.get(listTableStatusUrl);
          const tableStatus = response.data.map(status => ({
              name: translations[status.name],  // The text shown in the select options
              value: status.value,    // The value of the selected option
          }));
          setTableStatusOptions(tableStatus);
      } catch (error) {
          console.error("Error fetching options:", error);
      }
  };

  const validateObject = (data) => {
    if (!!!data.area) return `${translations.area} ${translations.required}`;
    return 'Success';
  }

  useEffect(() => {
    fetchTableStatusOptions();
  }, []);

  return (
    <div>
      <TreeTableComponent
        translations={translations}
        dir={dir}
        urlList={urlList}
        canAddInline={true}
        title="tables"
        defaultValue={{ active: 1 }}
        validateObject={validateObject}
        cols={[
          { key: "area", autoFocus: false, type: "AsyncDropDown", searchUrl: 'searchAreas', width: '30%', editable: true },
          { key: "code", autoFocus: true, type: "Text", width: '16%', editable: true , required : true},
          { key: "steating_capacity", title: "steatingCapacity", autoFocus: false, type: "Number", width: '15%', editable: true , required : true},
          { key: "table_status", title: "availability", autoFocus: false, type: "DropDown", options: tableStatusOptions, width: '15%', editable: true , required : true},
          { key: "active", autoFocus: false, options: [], type: "Check", width: '30%', editable: true }
        ]}
      />
    </div>
  );
};

export default TableAreaTable;
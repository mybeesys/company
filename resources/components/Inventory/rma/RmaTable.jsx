import axios from 'axios';
import { useState } from 'react';
import SweetAlert2 from 'react-sweetalert2';
import DropdownMenu from '../../comp/DropdownMenu';
import TreeTableComponent from '../../comp/TreeTableComponent';

const RmaTable = ({ dir, translations }) => {
  const rootElement = document.getElementById('root');
  const urlList = JSON.parse(rootElement.getAttribute('list-url'));
  const [showAlert, setShowAlert] = useState(false);
  const [currentRow, setCurrentRow] = useState({});

  const changeStatus = (data, status, afterExecute)=>{    
    axios.post('statusUpdate', {id: data.id, op_status: status})
        .then((resp)=>{
          data["op_status"] = resp.data.op_status;
          data["op_status_name"] = resp.data.op_status_name;
          Swal.fire({
            show: showAlert,
            title: `${translations['prep']}: ${data.no} ${translations[resp.data.op_status_name]}`,
            text: translations.technicalerror ,
            icon: "success",
            timer: 2000,
            showCancelButton: false,
            showConfirmButton: false,
           }).then(() => {
            setShowAlert(false); // Reset the state after alert is dismissed
          });
          afterExecute();
        })
    .catch((ex)=>{});
  }

  const statusCell = (data, key, editMode, editable) => {
    return !!editMode? <></>: <span class={`status status${data[key]}`}>{translations[data[`${key}_name`]]}</span>
  }

  const dropdownCell = (data, key, editMode, editable, refreshTree) => {
    let actions = [];
    if(data.op_status == 0)
      actions.push({key:"sent", action: (data, afterExecute)=>{
        changeStatus(data, 1, afterExecute);
      }});
    if(data.op_status != 6)
      actions.push({key:"approved", action: (data, afterExecute)=>{
        changeStatus(data, 6, afterExecute);
      }});
    return <DropdownMenu actions={actions} data={data} translations={translations} afterExecute={refreshTree}/>;
  }

  const canEditRow=(data)=>{
    return data.op_status == 0;
  }

  const onSave=(data)=>{
    
  }

  return (
    <div>
      <SweetAlert2 />
      <TreeTableComponent
        translations={translations}
        dir={dir}
        urlList={`${urlList}/2`}
        editUrl={'rma/%/edit'}
        addUrl={'rma/create'}
        canEditRow={canEditRow}
        canAddInline={false}
        title="rmas"
        cols={[
          {key : "no", autoFocus: true, type :"Text", width:'15%'},
          {key : "vendor", autoFocus: true, type :"AsyncDropDown", width:'15%'},
          {key : "total", autoFocus: true, type :"Decimal", width:'15%'},
          {key : "op_date", autoFocus: true, type :"Date", width:'15%'},
          {key : "op_status", autoFocus: true, type :"Date", width:'15%',
              customCell : statusCell
          },
          {key : "dd", autoFocus: true, type :"Date", width:'15%',
            customCell : dropdownCell
          }
        ]}
      />
    </div>
  );
};

export default RmaTable;